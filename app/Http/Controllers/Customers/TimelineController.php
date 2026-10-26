<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\MaterialDeliveryReport;
use App\Models\MonitoringProgram;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\ServiceReport;
use App\Models\ServiceReportType;
use App\Models\StoreOrder;
use App\Models\TechnicalService;
use App\Support\StoreOrderStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * "Mi expediente" del portal de servicios: linea de tiempo cronologica con todo
 * lo ligado al cliente autenticado (cotizaciones, pedidos de entrega, reportes
 * de entrega, servicios, reportes de servicio, pedidos de la tienda y programas
 * de monitoreo).
 *
 * Reglas de visibilidad: todo se acota por customer_id; nunca borradores
 * (cotizaciones, reportes de entrega, servicios, reportes de servicio). Solo las
 * cotizaciones y los pedidos de la tienda muestran importes.
 *
 * Cada fuente se consulta una sola vez (columnas minimas, tope de 300 mas
 * recientes); la mezcla, el filtro de texto y la paginacion se hacen en PHP.
 */
class TimelineController extends Controller
{
    private const PER_PAGE   = 20;
    private const SOURCE_CAP = 300;

    /** Estados de reporte de servicio que el cliente puede consultar (ver ServiceHistoryController). */
    private const REPORT_STATUSES = ['completed', 'signed'];

    /** Etiqueta, tono por defecto y seccion de detalle de cada tipo de evento. */
    private const TYPES = [
        'quote'           => ['label' => 'Cotizaciones',           'one' => 'Cotización',           'index' => 'customer.quotes.index'],
        'sales_order'     => ['label' => 'Pedidos de entrega',     'one' => 'Pedido de entrega',    'index' => 'customer.deliveries.index'],
        'delivery_report' => ['label' => 'Reportes de entrega',    'one' => 'Reporte de entrega',   'index' => 'customer.deliveries.index'],
        'service'         => ['label' => 'Servicios técnicos',     'one' => 'Servicio técnico',     'index' => 'customer.technical-services.index'],
        'service_report'  => ['label' => 'Reportes de servicio',   'one' => 'Reporte de servicio',  'index' => 'customer.service-reports.index'],
        'store_order'     => ['label' => 'Pedidos de la tienda',   'one' => 'Pedido de la tienda',  'index' => 'customer.orders.index'],
        'monitoring'      => ['label' => 'Monitoreo',              'one' => 'Programa de monitoreo', 'index' => 'customer.monitoring.index'],
    ];

    public function index(Request $request)
    {
        $customerId = (int) auth('customer')->id();
        $typeKeys   = array_keys(self::TYPES);

        $request->validate([
            'types'   => ['nullable', 'array'],
            'types.*' => ['string', Rule::in($typeKeys)],
            'year'    => ['nullable', 'integer', 'between:2000,2100'],
            'q'       => ['nullable', 'string', 'max:100'],
        ]);

        $selected = array_values(array_intersect($typeKeys, (array) $request->query('types', [])));
        $year     = $request->filled('year') ? (int) $request->query('year') : null;
        $search   = trim((string) $request->query('q', ''));
        $wanted   = $selected ?: $typeKeys;

        // ── Resumen: total real y anios existentes de cada fuente (1 consulta por fuente) ──
        $counts = array_fill_keys($typeKeys, 0);
        $years  = [];
        foreach ($typeKeys as $type) {
            $rows = $this->scope($type, $customerId)
                ->selectRaw('YEAR(' . $this->dateExpr($type) . ') as y, COUNT(*) as c')
                ->groupBy('y')
                ->get();

            foreach ($rows as $row) {
                $counts[$type] += (int) $row->c;
                if ($row->y) {
                    $years[(int) $row->y] = true;
                }
            }
        }
        krsort($years);
        $years = array_keys($years);

        // ── Eventos (solo de los tipos pedidos y del anio elegido) ──
        $events = collect();
        foreach ($wanted as $type) {
            if ($counts[$type] === 0) {
                continue;
            }
            $events = $events->concat($this->events($type, $customerId, $year));
        }

        if ($search !== '') {
            $needle = Str::lower(Str::ascii($search));
            $events = $events->filter(fn (array $e) => str_contains(
                Str::lower(Str::ascii($e['title'] . ' ' . $e['subtitle'] . ' ' . $e['badge'] . ' ' . $e['type_label'])),
                $needle
            ));
        }

        $events = $events
            ->sortByDesc(fn (array $e) => $e['date']->getTimestamp())
            ->values();

        $page  = max(1, (int) $request->query('page', 1));
        $items = new LengthAwarePaginator(
            $events->forPage($page, self::PER_PAGE)->values(),
            $events->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );
        $items->withQueryString();

        // ── Resumen por tipo con enlace a su seccion ──
        $summary = [];
        foreach (self::TYPES as $key => $meta) {
            $summary[] = [
                'key'   => $key,
                'label' => $meta['label'],
                'count' => $counts[$key],
                'url'   => Route::has($meta['index']) ? route($meta['index']) : null,
            ];
        }

        return view('customers.timeline', [
            'items'    => $items,
            'summary'  => $summary,
            'types'    => self::TYPES,
            'selected' => $selected,
            'years'    => $years,
            'year'     => $year,
            'search'   => $search,
            'total'    => array_sum($counts),
            'next'     => $this->nextService($customerId),
        ]);
    }

    // ── Consultas por fuente ───────────────────────────────────────────────

    /** Expresion SQL de la fecha del evento (siempre con respaldo a created_at). */
    private function dateExpr(string $type): string
    {
        return match ($type) {
            'quote'           => 'COALESCE(sent_at, created_at)',
            'delivery_report' => 'COALESCE(delivery_date, created_at)',
            'service', 'service_report' => 'COALESCE(service_date, created_at)',
            'monitoring'      => 'COALESCE(start_date, created_at)',
            default           => 'created_at',
        };
    }

    /** Consulta base de una fuente: solo del cliente y solo estados visibles. */
    private function scope(string $type, int $customerId)
    {
        return match ($type) {
            'quote' => Quote::query()
                ->where('customer_id', $customerId)
                ->whereIn('status', ['sent', 'accepted', 'rejected', 'expired']),
            'sales_order' => SalesOrder::query()
                ->where('customer_id', $customerId),
            'delivery_report' => MaterialDeliveryReport::query()
                ->where('customer_id', $customerId)
                ->where('status', 'signed'),
            'service' => TechnicalService::query()
                ->where('customer_id', $customerId)
                ->where('status', '!=', 'draft'),
            'service_report' => ServiceReport::query()
                ->where('customer_id', $customerId)
                ->whereIn('status', self::REPORT_STATUSES),
            'store_order' => StoreOrder::query()
                ->where('customer_id', $customerId),
            'monitoring' => MonitoringProgram::query()
                ->where('customer_id', $customerId),
        };
    }

    /** Eventos de una fuente (los mas recientes, con tope). */
    private function events(string $type, int $customerId, ?int $year): Collection
    {
        $expr = $this->dateExpr($type);

        $query = $this->scope($type, $customerId);
        $query = match ($type) {
            'quote' => $query->select(['id', 'quote_number', 'status', 'currency', 'total', 'valid_until']),
            'sales_order' => $query->select(['id', 'order_number', 'status', 'quote_id'])
                ->with('quote:id,quote_number'),
            'delivery_report' => $query->select(['id', 'report_number', 'status', 'sales_order_id', 'delivery_location'])
                ->with('salesOrder:id,order_number'),
            'service' => $query->select(['id', 'service_number', 'status', 'service_type_id', 'service_time', 'created_by_user_id', 'requested_at'])
                ->with('serviceType:id,name'),
            'service_report' => $query->select(['id', 'report_number', 'status', 'service_type', 'custom_service_type']),
            'store_order' => $query->select(['id', 'order_number', 'status', 'currency', 'total'])
                ->withCount('items'),
            'monitoring' => $query->select(['id', 'name', 'status', 'weeks']),
        };

        $rows = $query
            ->selectRaw($expr . ' as ev_date')
            ->when($year, fn ($q) => $q->whereRaw('YEAR(' . $expr . ') = ?', [$year]))
            ->orderByDesc('ev_date')
            ->orderByDesc('id')
            ->limit(self::SOURCE_CAP)
            ->get();

        $reportTypes = $type === 'service_report'
            ? ServiceReportType::pluck('label', 'key')
            : collect();

        return $rows->map(function ($m) use ($type, $reportTypes) {
            $date = Carbon::parse($m->ev_date);

            return match ($type) {
                'quote' => $this->event($type, $date, (string) $m->quote_number,
                    $m->valid_until ? 'Vigencia hasta ' . $m->valid_until->format('d/m/Y') : '',
                    Quote::statusLabel((string) $m->status), $this->tone('quote', $m->status),
                    $this->money($m->total, $m->currency),
                    $this->url('customer.quotes.show', $m->id)),

                'sales_order' => $this->event($type, $date, (string) $m->order_number,
                    $m->quote ? 'Cotización ' . $m->quote->quote_number : '',
                    SalesOrder::statusLabel((string) $m->status), $this->tone('sales_order', $m->status),
                    null, $this->url('customer.deliveries.orders.show', $m->id)),

                'delivery_report' => $this->event($type, $date, (string) $m->report_number,
                    trim(($m->salesOrder ? 'Pedido ' . $m->salesOrder->order_number : '')
                        . ($m->delivery_location ? ($m->salesOrder ? ' · ' : '') . Str::limit($m->delivery_location, 70) : '')),
                    'Firmado', 'ok',
                    null, $this->url('customer.deliveries.reports.show', $m->id)),

                'service' => $this->event($type, $date, (string) $m->service_number,
                    trim(($m->serviceType?->name ?? '')
                        . ($m->service_time ? ' · ' . substr((string) $m->service_time, 0, 5) . ' h' : ''), ' ·'),
                    $m->status_label, $this->tone('service', $m->status),
                    null, $this->url('customer.technical-services.show', $m->id)),

                'service_report' => $this->event($type, $date, (string) $m->report_number,
                    (string) ($m->service_type === 'custom' && $m->custom_service_type
                        ? $m->custom_service_type
                        : ($reportTypes[$m->service_type] ?? $m->service_type)),
                    $m->status_label, 'ok',
                    null, $this->url('customer.service-reports.show', $m->id)),

                'store_order' => $this->event($type, $date, (string) $m->order_number,
                    $m->items_count . ' ' . ($m->items_count === 1 ? 'artículo' : 'artículos'),
                    StoreOrderStatus::meta((string) $m->status)['label'] ?? (string) $m->status,
                    $this->tone('store_order', $m->status),
                    $this->money($m->total, $m->currency),
                    $this->url('customer.orders.show', $m->order_number)),

                'monitoring' => $this->event($type, $date, (string) $m->name,
                    'Programa de ' . $m->weeks . ' ' . ((int) $m->weeks === 1 ? 'semana' : 'semanas'),
                    $m->status_label, $this->tone('monitoring', $m->status),
                    null, $this->url('customer.monitoring.show', $m->id)),
            };
        });
    }

    private function event(string $type, Carbon $date, string $title, string $subtitle, string $badge, string $tone, ?string $amount, ?string $url): array
    {
        return [
            'type'       => $type,
            'type_label' => self::TYPES[$type]['one'],
            'date'       => $date,
            'title'      => $title,
            'subtitle'   => $subtitle,
            'badge'      => $badge,
            'tone'       => $tone,
            'amount'     => $amount,
            'url'        => $url,
        ];
    }

    private function url(string $route, mixed $param): ?string
    {
        return Route::has($route) ? route($route, $param) : null;
    }

    private function money(mixed $amount, ?string $currency): ?string
    {
        if ($amount === null) {
            return null;
        }

        return '$' . number_format((float) $amount, 2) . ' ' . ($currency ?: 'MXN');
    }

    /** Tono visual del estado (ok / info / warn / bad / neutral); el texto siempre se muestra. */
    private function tone(string $type, ?string $status): string
    {
        return match ($type . ':' . $status) {
            'quote:accepted', 'sales_order:entregado', 'service:completed', 'store_order:entregado', 'monitoring:completed' => 'ok',
            'quote:sent', 'service:scheduled', 'store_order:pagado', 'store_order:enviado', 'monitoring:active' => 'info',
            'sales_order:en_preparacion', 'sales_order:parcialmente_entregado', 'service:in_progress',
            'store_order:en_preparacion', 'store_order:pago_parcial', 'monitoring:paused' => 'warn',
            'quote:rejected', 'sales_order:cancelado', 'service:cancelled', 'store_order:cancelado' => 'bad',
            default => 'neutral',
        };
    }

    /** Proximo servicio programado o en proceso (null si no hay). */
    private function nextService(int $customerId): ?array
    {
        $service = TechnicalService::query()
            ->select(['id', 'service_number', 'customer_id', 'service_type_id', 'service_date', 'service_time', 'status'])
            ->where('customer_id', $customerId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('service_date', '>=', now()->toDateString())
            ->with('serviceType:id,name')
            ->orderBy('service_date')
            ->orderBy('service_time')
            ->first();

        if (! $service || ! $service->service_date) {
            return null;
        }

        return [
            'number' => (string) $service->service_number,
            'type'   => $service->serviceType?->name ?? '',
            'date'   => $service->service_date,
            'time'   => $service->service_time ? substr((string) $service->service_time, 0, 5) : null,
            'status' => $service->status_label,
            'url'    => $this->url('customer.technical-services.show', $service->id),
        ];
    }
}
