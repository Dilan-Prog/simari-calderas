<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\AdVisit;
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
    public function index(Request $request): View
    {
        $query = AdVisit::withCount('events');

        if ($request->filled('from')) {
            $query->whereDate('last_seen_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('last_seen_at', '<=', $request->to);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('gclid', 'like', "%{$q}%")
                    ->orWhere('wbraid', 'like', "%{$q}%");
            });
        }

        $visits = $query->latest('last_seen_at')->paginate(30)->withQueryString();

        return view('admin.ad-tracking.index', compact('visits'));
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
