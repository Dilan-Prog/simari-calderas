<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServicePage;
use App\Models\ServiceRating;
use App\Models\ServiceReport;
use App\Services\SatisfactionSurveyService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Admin: pantalla de calificación (modo quiosco, la usa el cliente en el dispositivo del técnico)
 * y "Opiniones de servicio" (listado, detalle, exportación y asignación del servicio web).
 */
class ServiceRatingController extends Controller
{
    public function __construct(private SatisfactionSurveyService $survey)
    {
    }

    // ── Pantalla del cliente (quiosco) ──────────────────────────────────────

    public function rate(Request $request, ServiceReport $report)
    {
        $show = route('admin.service-reports.show', $report);

        if ($request->isMethod('post')) {
            try {
                $this->survey->submit($report, [
                    'card_id' => $request->input('card_id'),
                    'chips'   => (array) $request->input('chips', []),
                    'comment' => $request->input('comment'),
                ]);
            } catch (ValidationException $e) {
                return redirect(route('admin.service-reports.rate', $report) . ($request->boolean('editar') ? '?editar=1' : ''))->withInput()->withErrors($e->errors());
            }

            return redirect()->route('admin.service-reports.rate', $report)->with('rated', true);
        }

        $data = $this->survey->screenData($report, (bool) session('rated'), $request->boolean('editar'));
        if ($data === null) {
            return redirect($show)->with('error', 'Este reporte no se puede calificar (debe estar firmado y la encuesta activa).');
        }

        return view('service-ratings.rate', $data + [
            'action'     => route('admin.service-reports.rate', $report),
            'skipAction' => route('admin.service-reports.rate.skip', $report),
            'backUrl'    => $show,
            'reportUrl'  => $show,
            'editUrl'    => route('admin.service-reports.rate', $report) . '?editar=1',
        ]);
    }

    public function skip(ServiceReport $report)
    {
        if ($report->status === 'signed') {
            $this->survey->skip($report);
        }

        return redirect()->route('admin.service-reports.show', $report)
            ->with('success', 'Calificación omitida. El cliente podrá calificar después desde su portal.');
    }

    // ── Opiniones de servicio ───────────────────────────────────────────────

    private function filtered(Request $request)
    {
        $q = ServiceRating::query()->with(['report', 'servicePage', 'card'])
            ->withCount([
                'answers as chips_good' => fn ($a) => $a->where('kind', 'positive'),
                'answers as chips_bad'  => fn ($a) => $a->where('kind', 'negative'),
            ]);

        if ($request->filled('service_page_id')) {
            $request->service_page_id === 'none'
                ? $q->whereNull('service_page_id')
                : $q->where('service_page_id', (int) $request->service_page_id);
        }
        if ($request->filled('stars')) {
            $q->where('rating', (int) $request->stars);
        }
        if ($request->filled('status') && in_array($request->status, ['pending', 'submitted', 'skipped'], true)) {
            $q->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('customer_name', 'like', "%{$term}%")
                    ->orWhereHas('report', fn ($r) => $r->where('report_number', 'like', "%{$term}%"));
            });
        }

        return $q;
    }

    public function index(Request $request)
    {
        $ratings = $this->filtered($request)->latest('id')->paginate(25)->withQueryString();

        $statusLabels = ['pending' => 'Pendiente', 'submitted' => 'Enviada', 'skipped' => 'Omitida'];
        $chips = [];
        if ($request->filled('service_page_id')) {
            $name = $request->service_page_id === 'none' ? 'Sin servicio' : optional(ServicePage::find($request->service_page_id))->name;
            $chips[] = ['label' => 'Servicio: ' . $name, 'url' => $request->fullUrlWithoutQuery(['service_page_id', 'page'])];
        }
        if ($request->filled('stars')) {
            $chips[] = ['label' => 'Estrellas: ' . (int) $request->stars, 'url' => $request->fullUrlWithoutQuery(['stars', 'page'])];
        }
        if ($request->filled('status')) {
            $chips[] = ['label' => 'Estado: ' . ($statusLabels[$request->status] ?? $request->status), 'url' => $request->fullUrlWithoutQuery(['status', 'page'])];
        }
        foreach (['date_from' => 'Desde', 'date_to' => 'Hasta', 'q' => 'Búsqueda'] as $key => $label) {
            if ($request->filled($key)) {
                $chips[] = ['label' => $label . ': ' . $request->input($key), 'url' => $request->fullUrlWithoutQuery([$key, 'page'])];
            }
        }

        $submitted = ServiceRating::submitted();

        return view('admin.service-ratings.index', [
            'ratings'      => $ratings,
            'chips'        => $chips,
            'statusLabels' => $statusLabels,
            'servicePages' => ServicePage::orderBy('name')->get(['id', 'name']),
            'kpi'          => [
                'total'   => (clone $submitted)->count(),
                'average' => round((float) (clone $submitted)->avg('rating'), 2),
                'noPage'  => ServiceRating::whereNull('service_page_id')->where('status', 'submitted')->count(),
            ],
        ]);
    }

    public function show(ServiceRating $rating)
    {
        $rating->load(['report', 'servicePage', 'card', 'answers', 'customer']);

        return view('admin.service-ratings.show', [
            'rating'       => $rating,
            'servicePages' => ServicePage::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function assignService(Request $request, ServiceRating $rating)
    {
        $data = $request->validate(['service_page_id' => 'nullable|integer|exists:service_pages,id']);
        $rating->update(['service_page_id' => $data['service_page_id'] ?? null]);

        return back()->with('success', 'Servicio web de la calificación actualizado.');
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->latest('id')->get();
        $filename = 'opiniones-de-servicio-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Cliente', 'Reporte', 'Servicio web', 'Estrellas', 'Tarjeta', 'Opciones destacadas', 'Opciones a mejorar', 'Comentario', 'Estado', 'Fecha']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $this->csvSafe($r->customer_name),
                    $r->report?->report_number,
                    $r->servicePage?->name,
                    $r->rating,
                    $r->card_label,
                    (int) $r->chips_good,
                    (int) $r->chips_bad,
                    $this->csvSafe($r->comment),
                    ['pending' => 'Pendiente', 'submitted' => 'Enviada', 'skipped' => 'Omitida'][$r->status] ?? $r->status,
                    ($r->submitted_at ?? $r->created_at)?->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Evita inyección de fórmulas al abrir el CSV en Excel. */
    private function csvSafe(?string $v): ?string
    {
        return $v !== null && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "	", ""], true) ? "'" . $v : $v;
    }
}
