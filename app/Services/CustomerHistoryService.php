<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPurchaseOrder;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\MaterialDeliveryReport;
use App\Models\MonitoringProgram;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\SalesOrder;
use App\Models\ServiceReport;
use App\Models\StoreOrder;
use App\Models\TechnicalService;
use App\Models\User;
use App\Support\StoreOrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Historial completo de un cliente para su ficha del admin: listas por área
 * (cotizaciones, pedidos y entregas, servicios, tienda, negocios, cobranza) y
 * una línea de tiempo que las junta. Cada área solo se incluye si el usuario
 * tiene el permiso del módulo correspondiente.
 *
 * Cada fila: type, icon, date (Carbon|null), folio, title, status, amount (string|null), url (string|null).
 */
class CustomerHistoryService
{
    /** Máximo de filas por origen (las más recientes). */
    private const LIMIT = 100;

    /** Etiquetas de los tipos de evento, usadas por la línea de tiempo y sus filtros. */
    public const TYPES = [
        'quote'    => 'Cotización',
        'purchase_order' => 'Orden de compra del cliente',
        'order'    => 'Pedido de entrega',
        'delivery' => 'Reporte de entrega',
        'service'  => 'Servicio técnico',
        'report'   => 'Reporte de servicio',
        'store'    => 'Orden de tienda',
        'monitor'  => 'Monitoreo',
        'deal'     => 'Negocio',
        'project'  => 'Proyecto',
        'lead'     => 'Lead',
        'receivable' => 'Cuenta por cobrar',
        'payment'  => 'Pago',
    ];

    /**
     * @return array{sections: array<string, array>, timeline: Collection, kpis: array, types: array<string,string>}
     */
    public function build(Customer $customer, User $user): array
    {
        $can = fn (string $module) => $user->hasPermission($module);
        $rows = [];

        if ($can('quotes')) {
            $rows['quote'] = $this->quotes($customer);
        }
        if ($can('clients')) {
            $rows['purchase_order'] = $this->purchaseOrders($customer);
        }
        if ($can('sales-orders')) {
            $rows['order'] = $this->orders($customer);
        }
        if ($can('material-delivery-reports')) {
            $rows['delivery'] = $this->deliveries($customer);
        }
        if ($can('technical-services')) {
            $rows['service'] = $this->services($customer);
        }
        if ($can('service-reports')) {
            $rows['report'] = $this->reports($customer);
        }
        if ($can('monitoring')) {
            $rows['monitor'] = $this->monitoring($customer);
        }
        if ($can('orders')) {
            $rows['store'] = $this->storeOrders($customer);
        }
        if ($can('deals')) {
            $rows['deal'] = $this->deals($customer);
        }
        if ($can('projects')) {
            $rows['project'] = $this->projects($customer);
        }
        if ($can('leads-inbox')) {
            $rows['lead'] = $this->leads($customer);
        }
        if ($can('receivables')) {
            $rows['receivable'] = $this->receivables($customer);
        }
        if ($can('payments')) {
            $rows['payment'] = $this->payments($customer);
        }

        // Pestañas: cada una agrupa uno o más orígenes.
        $tabs = [
            'quotes'   => ['label' => 'Cotizaciones', 'groups' => ['quote' => 'Cotizaciones']],
            'po'       => ['label' => 'Órdenes de compra', 'groups' => ['purchase_order' => 'Órdenes de compra subidas por el cliente']],
            'delivery' => ['label' => 'Pedidos y entregas', 'groups' => ['order' => 'Pedidos de entrega', 'delivery' => 'Reportes de entrega de material']],
            'services' => ['label' => 'Servicios', 'groups' => ['service' => 'Servicios técnicos', 'report' => 'Reportes de servicio', 'monitor' => 'Programas de monitoreo']],
            'store'    => ['label' => 'Órdenes de tienda', 'groups' => ['store' => 'Órdenes de la tienda en línea']],
            'crm'      => ['label' => 'Negocios y proyectos', 'groups' => ['deal' => 'Negocios', 'project' => 'Proyectos', 'lead' => 'Leads']],
            'finance'  => ['label' => 'Cobranza', 'groups' => ['receivable' => 'Cuentas por cobrar', 'payment' => 'Pagos recibidos']],
        ];

        $sections = [];
        foreach ($tabs as $key => $tab) {
            $groups = [];
            foreach ($tab['groups'] as $type => $label) {
                if (array_key_exists($type, $rows)) {
                    $groups[] = ['type' => $type, 'label' => $label, 'rows' => $rows[$type]['rows'], 'total' => $rows[$type]['total']];
                }
            }
            if ($groups) {
                $sections[$key] = [
                    'label' => $tab['label'],
                    'groups' => $groups,
                    'count' => array_sum(array_column($groups, 'total')),
                ];
            }
        }

        $timeline = collect($rows)
            ->flatMap(fn ($r) => $r['rows'])
            ->sortByDesc(fn ($e) => $e['date']?->timestamp ?? 0)
            ->values()
            ->take(300);

        return [
            'sections' => $sections,
            'timeline' => $timeline,
            'kpis'     => $this->kpis($customer, $rows, $user),
            'types'    => self::TYPES,
        ];
    }

    // ── KPIs ───────────────────────────────────────────────────────────────

    private function kpis(Customer $customer, array $rows, User $user): array
    {
        $k = [];

        if (isset($rows['quote'])) {
            $accepted = Quote::where('customer_id', $customer->id)->where('status', 'accepted');
            $k['quotes_total']    = $rows['quote']['total'];
            $k['quotes_accepted'] = (clone $accepted)->count();
            $k['accepted_amount'] = $this->sumByCurrency($accepted, 'total');
        }
        if (isset($rows['service'])) {
            $k['services_done'] = TechnicalService::where('customer_id', $customer->id)->where('status', 'completed')->count();
        }
        if (isset($rows['receivable'])) {
            $k['balance'] = $this->sumByCurrency(
                Receivable::where('customer_id', $customer->id)->where('balance', '>', 0)->whereNotIn('status', ['cancelled', 'canceled', 'cancelada']),
                'balance'
            );
        }

        $last = collect($rows)->flatMap(fn ($r) => $r['rows'])->map(fn ($e) => $e['date'])->filter()->max();
        $k['last_activity'] = $last;

        return $k;
    }

    /** "$1,200.00 MXN · $300.00 USD" a partir de una consulta agrupada por moneda. */
    private function sumByCurrency($query, string $column): ?string
    {
        $sums = (clone $query)->selectRaw("COALESCE(NULLIF(currency, ''), 'MXN') as cur, SUM({$column}) as s")->groupBy('cur')->pluck('s', 'cur');
        $sums = $sums->filter(fn ($s) => (float) $s != 0.0);

        return $sums->isEmpty() ? null : $sums->map(fn ($s, $cur) => $this->money($s, $cur))->implode(' · ');
    }

    // ── Orígenes ───────────────────────────────────────────────────────────

    private function quotes(Customer $c): array
    {
        $base = Quote::where('customer_id', $c->id);

        return $this->pack($base, 'created_at', fn (Quote $q) => $this->row(
            'quote', $q->created_at, $q->quote_number, null,
            Quote::statusLabel($q->status) . ($q->valid_until ? ' · vigencia ' . $q->valid_until->format('d/m/Y') : ''),
            $this->money($q->total, $q->currency), $this->url('admin.quotes.show', $q->id)
        ));
    }

    /** PDF que el cliente sube en el portal (Órdenes de Compra); el enlace descarga el archivo. */
    private function purchaseOrders(Customer $c): array
    {
        return $this->pack(CustomerPurchaseOrder::where('customer_id', $c->id)->with('quote:id,quote_number'), 'created_at', fn (CustomerPurchaseOrder $o) => $this->row(
            'purchase_order', $o->created_at, $o->po_number ?: $o->original_name,
            collect([
                $o->po_number ? $o->original_name : null,
                $o->quote ? 'Cotización ' . $o->quote->quote_number : null,
                $o->sizeLabel(),
            ])->filter()->implode(' · '),
            $o->uploaded_by_member_id ? 'Subida por un usuario del equipo' : 'Subida por el titular',
            null, $this->url('admin.customer-purchase-orders.download', $o->id)
        ));
    }

    private function orders(Customer $c): array
    {
        return $this->pack(SalesOrder::where('customer_id', $c->id)->with('quote:id,quote_number'), 'created_at', fn (SalesOrder $o) => $this->row(
            'order', $o->created_at, $o->order_number,
            $o->quote ? 'Cotización ' . $o->quote->quote_number : null,
            SalesOrder::statusLabel($o->status), null, $this->url('admin.sales-orders.show', $o->id)
        ));
    }

    private function deliveries(Customer $c): array
    {
        return $this->pack(MaterialDeliveryReport::where('customer_id', $c->id), 'delivery_date', fn (MaterialDeliveryReport $r) => $this->row(
            'delivery', $r->delivery_date ?? $r->created_at, $r->report_number,
            $r->received_by_name ? 'Recibió: ' . $r->received_by_name : null,
            $r->status_label, null, $this->url('admin.material-delivery-reports.show', $r->id)
        ));
    }

    private function services(Customer $c): array
    {
        return $this->pack(TechnicalService::where('customer_id', $c->id)->with('serviceType:id,name'), 'service_date', fn (TechnicalService $s) => $this->row(
            'service', $s->service_date ?? $s->created_at, $s->service_number ?: ('Servicio #' . $s->id),
            $s->serviceType?->name, $s->status_label, null, $this->url('admin.technical-services.show', $s->id)
        ));
    }

    private function reports(Customer $c): array
    {
        return $this->pack(ServiceReport::where('customer_id', $c->id), 'service_date', fn (ServiceReport $r) => $this->row(
            'report', $r->service_date ?? $r->created_at, $r->report_number, $r->service_type_label ?? null,
            $r->status_label, null, $this->url('admin.service-reports.show', $r->id)
        ));
    }

    private function monitoring(Customer $c): array
    {
        return $this->pack(MonitoringProgram::where('customer_id', $c->id), 'start_date', fn (MonitoringProgram $p) => $this->row(
            'monitor', $p->start_date, $p->name, $p->weeks . ' semanas', $p->status_label, null, $this->url('admin.monitoring.show', $p->id)
        ));
    }

    private function storeOrders(Customer $c): array
    {
        return $this->pack(StoreOrder::where('customer_id', $c->id), 'created_at', fn (StoreOrder $o) => $this->row(
            'store', $o->created_at, $o->order_number, null,
            StoreOrderStatus::meta((string) $o->status)['label'] ?? (string) $o->status,
            $this->money($o->total, $o->currency ?? 'MXN'), $this->url('admin.orders.show', $o->id)
        ));
    }

    private function deals(Customer $c): array
    {
        return $this->pack(Deal::where('customer_id', $c->id)->with('stage:id,name'), 'created_at', fn (Deal $d) => $this->row(
            'deal', $d->created_at, $d->folio, $d->name, $d->stage?->name ?? '—',
            $d->amount ? $this->money($d->amount, $d->currency) : null, $this->url('admin.deals.show', $d->id)
        ));
    }

    private function projects(Customer $c): array
    {
        return $this->pack(Project::where('customer_id', $c->id), 'created_at', fn (Project $p) => $this->row(
            'project', $p->created_at, $p->project_number, $p->name, Project::statusLabel($p->status), null, $this->url('admin.projects.show', $p->id)
        ));
    }

    private function leads(Customer $c): array
    {
        return $this->pack(Lead::where('customer_id', $c->id), 'created_at', fn (Lead $l) => $this->row(
            'lead', $l->created_at, 'Lead #' . $l->id, $l->source ? 'Origen: ' . $l->source : null, $l->status_label, null, null
        ));
    }

    private function receivables(Customer $c): array
    {
        return $this->pack(Receivable::where('customer_id', $c->id), 'issue_date', fn (Receivable $r) => $this->row(
            'receivable', $r->issue_date ?? $r->created_at, $r->number,
            'Saldo ' . $this->money($r->balance, $r->currency) . ($r->due_date ? ' · vence ' . $r->due_date->format('d/m/Y') : ''),
            $this->humanize($r->status), $this->money($r->total, $r->currency), $this->url('admin.receivables.show', $r->id)
        ));
    }

    private function payments(Customer $c): array
    {
        return $this->pack(Payment::where('customer_id', $c->id), 'payment_date', fn (Payment $p) => $this->row(
            'payment', $p->payment_date ?? $p->created_at, $p->number, $p->reference ? 'Ref. ' . $p->reference : null,
            Payment::statusLabel((string) $p->status), $this->money($p->amount, $p->currency), $this->url('admin.payments.show', $p->id)
        ));
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Total real + las LIMIT filas más recientes mapeadas con $map. */
    private function pack($query, string $dateColumn, callable $map): array
    {
        $total = (clone $query)->count();
        $rows = $query->orderByDesc($dateColumn)->orderByDesc('id')->limit(self::LIMIT)->get()->map($map)->all();

        return ['total' => $total, 'rows' => $rows];
    }

    private function row(string $type, $date, ?string $folio, ?string $title, ?string $status, ?string $amount, ?string $url): array
    {
        return [
            'type'   => $type,
            'date'   => $date ? Carbon::parse($date) : null,
            'folio'  => $folio,
            'title'  => $title,
            'status' => $status,
            'amount' => $amount,
            'url'    => $url,
        ];
    }

    private function url(string $name, $id): ?string
    {
        return Route::has($name) ? route($name, $id) : null;
    }

    private function money($amount, ?string $currency): string
    {
        return '$' . number_format((float) $amount, 2) . ' ' . ($currency ?: 'MXN');
    }

    private function humanize(?string $value): string
    {
        return $value === null || $value === '' ? '—' : ucfirst(str_replace('_', ' ', $value));
    }
}
