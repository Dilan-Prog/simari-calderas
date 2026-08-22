<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\AdVisit;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\GoogleConversion;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Módulo admin mínimo de solo lectura sobre ad_visits/ad_events -- listado
 * simple de visitantes de anuncios rastreados y un export "detallado
 * interno" de sus eventos, para diagnóstico/soporte (no reemplaza el export
 * de conversiones de GoogleAdsController, que es lo que se sube a Google).
 */
class AdTrackingAdminController extends Controller
{
    /**
     * Duplicado intencional de StoreOrderController::PAGADAS_STATUSES (esa
     * constante es `private`, no accesible desde aquí) -- estatus de
     * StoreOrder que cuentan como "convertido" para efectos de este reporte.
     */
    private const PAGADAS_STATUSES = ['pagado', 'en_preparacion', 'enviado', 'entregado'];

    /**
     * Tipos de AdEvent que cuentan como "evento de interés" para el KPI/
     * embudo -- deliberadamente NO incluye checkout_start (así lo especifica
     * el mockup: "Interés: WhatsApp · cotización · carrito").
     */
    private const INTEREST_EVENT_TYPES = ['whatsapp_click', 'add_to_cart', 'quote_start'];

    public function index(Request $request): View
    {
        [$range, $from, $to, $hasCustomDates] = $this->resolveDateRange($request);

        // Periodo anterior: misma duración que el rango actual, terminando
        // justo un segundo antes de que este empiece.
        $durationSeconds = $to->diffInSeconds($from);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($durationSeconds);

        // Conjunto GLOBAL (todo el histórico, sin acotar a ningún rango) de
        // visitor_uuid convertidos -- cotización aceptada O carrito cuyo
        // pedido llegó a un estatus "pagado". Se calcula UNA sola vez y se
        // reutiliza tanto para los KPIs/embudo (acotados por rango) como
        // para el filtro "solo convertidos" y el flag por fila de la tabla.
        $convertedUuids = $this->convertedVisitorUuids();
        $convertedLookup = array_flip($convertedUuids->all());

        // ---------- KPIs + embudo (SOLO usan el rango de fechas, nunca los
        // filtros de la tabla de abajo) ----------
        $identifiedCurrent = AdVisit::whereBetween('first_seen_at', [$from, $to])->count();
        $identifiedPrevious = AdVisit::whereBetween('first_seen_at', [$prevFrom, $prevTo])->count();

        $interestCurrent = AdVisit::whereBetween('first_seen_at', [$from, $to])
            ->whereHas('events', fn ($q) => $q->whereIn('event_type', self::INTEREST_EVENT_TYPES))
            ->count();

        $convertedCurrent = AdVisit::whereBetween('first_seen_at', [$from, $to])
            ->whereIn('visitor_uuid', $convertedUuids)
            ->count();
        $convertedPrevious = AdVisit::whereBetween('first_seen_at', [$prevFrom, $prevTo])
            ->whereIn('visitor_uuid', $convertedUuids)
            ->count();

        $rateCurrent = $identifiedCurrent > 0 ? ($convertedCurrent / $identifiedCurrent * 100) : 0.0;
        $ratePrevious = $identifiedPrevious > 0 ? ($convertedPrevious / $identifiedPrevious * 100) : 0.0;
        $interestRateCurrent = $identifiedCurrent > 0 ? ($interestCurrent / $identifiedCurrent * 100) : 0.0;

        // KPI 1: % de cambio vs. periodo anterior -- se omite si el periodo
        // anterior tuvo 0 visitantes (dividir entre cero no tiene sentido).
        $kpi1DeltaPct = $identifiedPrevious > 0
            ? (($identifiedCurrent - $identifiedPrevious) / $identifiedPrevious * 100)
            : null;

        // KPI 3: diferencia ABSOLUTA de conteo (no porcentaje).
        $kpi3DeltaAbs = $convertedCurrent - $convertedPrevious;

        // KPI 4: diferencia en PUNTOS PORCENTUALES.
        $kpi4DeltaPts = round($rateCurrent - $ratePrevious, 1);

        $kpis = [
            [
                'label' => 'Visitantes identificados',
                'value' => number_format($identifiedCurrent, 0, '.', ','),
                'delta' => $kpi1DeltaPct !== null ? sprintf('%+.1f%%', $kpi1DeltaPct) : null,
                'deltaIsPositive' => $kpi1DeltaPct === null || $kpi1DeltaPct >= 0,
                'hint' => 'Clics de anuncio con GCLID o WBRAID',
                'accent' => false,
            ],
            [
                'label' => 'Con evento de interés',
                'value' => number_format($interestCurrent, 0, '.', ','),
                // No es una comparativa temporal: es la TASA sobre el total
                // identificado del mismo rango, tal como lo hace el mockup.
                'delta' => number_format($interestRateCurrent, 1) . '%',
                'deltaIsPositive' => true,
                'hint' => 'WhatsApp, carrito o cotización',
                'accent' => false,
            ],
            [
                'label' => 'Convertidos',
                'value' => number_format($convertedCurrent, 0, '.', ','),
                'delta' => sprintf('%+d vs. periodo anterior', $kpi3DeltaAbs),
                'deltaIsPositive' => $kpi3DeltaAbs >= 0,
                'hint' => 'Llegaron a Cotizado o Compra',
                'accent' => false,
            ],
            [
                'label' => 'Tasa de conversión',
                'value' => number_format($rateCurrent, 1) . '%',
                'delta' => sprintf('%+.1f pts', $kpi4DeltaPts),
                'deltaIsPositive' => $kpi4DeltaPts >= 0,
                'hint' => 'Convertidos / identificados',
                'accent' => true,
            ],
        ];

        $funnel = [
            [
                'label' => 'Vista de página',
                'count' => number_format($identifiedCurrent, 0, '.', ','),
                'pct' => '100%',
                'width' => 100,
                'note' => 'Todos los visitantes con clic de anuncio',
                'tone' => 'gray',
            ],
            [
                'label' => 'Interés',
                'count' => number_format($interestCurrent, 0, '.', ','),
                'pct' => number_format($interestRateCurrent, 1) . '%',
                'width' => min(100, $interestRateCurrent),
                'note' => 'WhatsApp · cotización · carrito',
                'tone' => 'orange',
            ],
            [
                'label' => 'Conversión',
                'count' => number_format($convertedCurrent, 0, '.', ','),
                'pct' => number_format($rateCurrent, 1) . '%',
                'width' => min(100, $rateCurrent),
                'note' => 'Cotización aceptada o pedido pagado',
                'tone' => 'green',
            ],
        ];

        $rangeLabel = $hasCustomDates
            ? 'Del ' . $from->locale('es')->translatedFormat('d M') . ' al ' . $to->locale('es')->translatedFormat('d M, Y')
            : "Últimos {$range} días";

        // ---------- Filtros de la tabla (independientes de los KPIs/embudo
        // de arriba, salvo el rango de fechas que comparten) ----------
        $sourceOptions = AdVisit::whereNotNull('utm_source')->where('utm_source', '!=', '')
            ->distinct()->orderBy('utm_source')->pluck('utm_source');
        $campaignOptions = AdVisit::whereNotNull('utm_campaign')->where('utm_campaign', '!=', '')
            ->distinct()->orderBy('utm_campaign')->pluck('utm_campaign');

        $onlyConverted = $request->boolean('only_converted');

        $visitsQuery = AdVisit::withCount('events')
            ->whereBetween('first_seen_at', [$from, $to]);

        if ($request->filled('q')) {
            $q = $request->q;
            $visitsQuery->where(function ($sub) use ($q) {
                $sub->where('gclid', 'like', "%{$q}%")
                    ->orWhere('wbraid', 'like', "%{$q}%");
            });
        }

        if ($request->filled('source')) {
            $visitsQuery->where('utm_source', $request->source);
        }

        if ($request->filled('campaign')) {
            $visitsQuery->where('utm_campaign', $request->campaign);
        }

        if ($onlyConverted) {
            $visitsQuery->whereIn('visitor_uuid', $convertedUuids);
        }

        // Pie de tabla: los 3 números deben reflejar el conjunto YA
        // FILTRADO completo (q/rango/source/campaign/only_converted), no el
        // total global -- se clona el query builder (ya con todos los
        // filtros aplicados, antes de ordenar/paginar) para no recalcular
        // los filtros ni disparar N+1 consultas.
        $convertedInFiltered = (clone $visitsQuery)->whereIn('visitor_uuid', $convertedUuids)->count();
        $interestInFiltered = (clone $visitsQuery)
            ->whereHas('events', fn ($q) => $q->whereIn('event_type', self::INTEREST_EVENT_TYPES))
            ->count();

        $visits = $visitsQuery->latest('first_seen_at')->paginate(20)->withQueryString();

        // Flag "converted" por fila, calculado sobre el set ya resuelto --
        // evita una query por fila (N+1) para saber si cada visitante convirtió.
        $visits->getCollection()->each(function (AdVisit $visit) use ($convertedLookup) {
            $visit->converted = isset($convertedLookup[$visit->visitor_uuid]);
        });

        return view('admin.ad-tracking.index', [
            'kpis' => $kpis,
            'funnel' => $funnel,
            'rangeLabel' => $rangeLabel,
            'visits' => $visits,
            'sourceOptions' => $sourceOptions,
            'campaignOptions' => $campaignOptions,
            'filters' => [
                'q' => (string) $request->input('q', ''),
                'range' => (string) $range,
                'from' => (string) $request->input('from', ''),
                'to' => (string) $request->input('to', ''),
                'source' => (string) $request->input('source', ''),
                'campaign' => (string) $request->input('campaign', ''),
                'onlyConverted' => $onlyConverted,
            ],
            'tableTotals' => [
                'total' => $visits->total(),
                'converted' => $convertedInFiltered,
                'interest' => $interestInFiltered,
            ],
        ]);
    }

    /**
     * Resuelve el rango de fechas efectivo del reporte: si el usuario llenó
     * Desde/Hasta libres, esos ganan sobre el selector rápido 7/30/90 días
     * (decisión ya confirmada). Reporta por first_seen_at (fecha del CLIC),
     * no por last_seen_at -- es el estándar de reporting de anuncios.
     *
     * @return array{0:int,1:Carbon,2:Carbon,3:bool} [range, from, to, hasCustomDates]
     */
    private function resolveDateRange(Request $request): array
    {
        $range = (int) $request->input('range', 30);
        if (!in_array($range, [7, 30, 90], true)) {
            $range = 30;
        }

        $hasCustomDates = $request->filled('from') || $request->filled('to');

        if ($hasCustomDates) {
            $to = $request->filled('to')
                ? Carbon::parse($request->to)->endOfDay()
                : now()->endOfDay();
            $from = $request->filled('from')
                ? Carbon::parse($request->from)->startOfDay()
                : $to->copy()->subDays($range)->startOfDay();
        } else {
            $to = now()->endOfDay();
            $from = now()->subDays($range)->startOfDay();
        }

        return [$range, $from, $to, $hasCustomDates];
    }

    /**
     * Set global (todo el histórico) de visitor_uuid "convertidos": llegaron
     * a una Quote aceptada O a un Cart cuyo pedido de tienda quedó en un
     * estatus "pagado". Se calcula una sola vez por request y se reutiliza
     * para KPIs, filtro "solo convertidos" y el flag por fila de la tabla.
     */
    private function convertedVisitorUuids(): \Illuminate\Support\Collection
    {
        $fromQuotes = Quote::query()
            ->where('status', 'accepted')
            ->whereNotNull('visitor_uuid')
            ->pluck('visitor_uuid');

        $fromOrders = Cart::query()
            ->whereNotNull('visitor_uuid')
            ->whereNotNull('converted_to_store_order_id')
            ->whereHas('convertedToStoreOrder', fn ($q) => $q->whereIn('status', self::PAGADAS_STATUSES))
            ->pluck('visitor_uuid');

        return $fromQuotes->merge($fromOrders)->unique()->values();
    }

    /**
     * Línea de tiempo de un visitante: todos sus eventos en orden
     * cronológico, más cualquier Cliente/Cotización/Pedido ya conectado a
     * él vía visitor_uuid -- para responder "¿este visitante convirtió?"
     * sin que el staff tenga que ir a buscarlo a otro módulo.
     */
    public function show(AdVisit $adVisit): View
    {
        $events = $adVisit->events()->with('product')->orderBy('occurred_at')->get();

        $customer = Customer::where('visitor_uuid', $adVisit->visitor_uuid)->first();

        $quote = Quote::where('visitor_uuid', $adVisit->visitor_uuid)
            ->latest('id')
            ->first();

        $cart = Cart::where('visitor_uuid', $adVisit->visitor_uuid)
            ->whereNotNull('converted_to_store_order_id')
            ->latest('id')
            ->first();

        $storeOrder = $cart?->convertedToStoreOrder;

        $converted = $storeOrder !== null || $quote?->status === 'accepted';

        // "Compra completada" no es una fila real en ad_events (solo existen
        // los 6 tipos de App\Models\AdEvent::EVENT_TYPES) -- se sintetiza en
        // memoria a partir de la GoogleConversion ya creada al aceptar la
        // cotización o marcar pagado el pedido, para que la línea de tiempo
        // muestre el desenlace sin inventar una fila en BD.
        if ($converted) {
            $orderId = $storeOrder !== null
                ? $storeOrder->order_number
                : 'QUOTE-' . $quote->quote_number;

            $conversion = GoogleConversion::where('order_id', $orderId)
                ->where('status', 'stored')
                ->first();

            if ($conversion) {
                $purchaseEvent = new AdEvent([
                    'event_type'  => 'purchase',
                    'occurred_at' => $conversion->conversion_time,
                    'url'         => $storeOrder !== null ? 'Confirmación de pedido' : 'Cotización aceptada',
                ]);

                $events = $events->push($purchaseEvent);
            }
        }

        $events = $events->sortBy('occurred_at')->values();

        // Agrupado por día para la línea de tiempo, con un pill de fecha en
        // español ("Miércoles 12 de agosto, 2026") encabezando cada grupo --
        // Carbon con locale('es') devuelve el nombre del día en minúsculas,
        // de ahí el ucfirst (seguro aquí: todos los días de la semana en
        // español empiezan con una letra ASCII).
        $eventsByDay = $events->groupBy(
            fn (AdEvent $e) => ucfirst($e->occurred_at->locale('es')->translatedFormat('l d \d\e F, Y'))
        );

        return view('admin.ad-tracking.show', compact('adVisit', 'events', 'eventsByDay', 'customer', 'quote', 'storeOrder', 'converted'));
    }

    public function export(Request $request)
    {
        $query = AdEvent::with(['adVisit', 'product']);

        if ($request->filled('from')) {
            $query->whereDate('occurred_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('occurred_at', '<=', $request->to);
        }

        $events = $query->orderBy('occurred_at', 'desc')->get();

        $rows = $events->map(fn (AdEvent $e) => [
            'fecha_evento'  => $e->occurred_at?->format('Y-m-d H:i:s'),
            'tipo_evento'   => $e->event_type,
            'url'           => $e->url,
            'producto'      => $e->product?->name,
            'gclid'         => $e->adVisit?->gclid,
            'wbraid'        => $e->adVisit?->wbraid,
            'utm_source'    => $e->adVisit?->utm_source,
            'utm_medium'    => $e->adVisit?->utm_medium,
            'utm_campaign'  => $e->adVisit?->utm_campaign,
            'first_gclid'   => $e->adVisit?->first_gclid,
            'first_seen_at' => $e->adVisit?->first_seen_at?->format('Y-m-d H:i:s'),
        ]);

        $headings = [
            'Fecha evento', 'Tipo de evento', 'URL', 'Producto',
            'GCLID', 'WBRAID', 'UTM Source', 'UTM Medium', 'UTM Campaign',
            'First GCLID', 'Primera visita',
        ];

        return Excel::download(
            new ReportExport($rows, $headings),
            'ad-events-' . now()->format('Y-m-d') . '.csv',
            ExcelFormat::CSV
        );
    }
}
