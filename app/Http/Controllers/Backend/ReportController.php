<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\Report;
use App\Services\PrebuiltReportService;
use App\Services\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        private ReportBuilderService $reportBuilderService,
        private PrebuiltReportService $prebuiltReportService
    ) {}

    /**
     * Listado de reports guardados (custom, no prebuilt) más un catálogo
     * estático de los reports prebuilt disponibles (no viven en la tabla
     * reports, se listan aparte para poder enlazar a sus rutas dedicadas).
     */
    public function index()
    {
        $reports = Report::with('creator')
            ->where('is_prebuilt', false)
            ->latest()
            ->get();

        $prebuiltReports = [
            [
                'key' => 'pipeline-performance',
                'name' => 'Rendimiento de pipeline',
                'description' => 'Tiempo promedio y tasa de conversión por etapa.',
                'route' => 'admin.reports.prebuilt.pipeline-performance',
            ],
            [
                'key' => 'rep-activity',
                'name' => 'Actividad por vendedor',
                'description' => 'Movimientos de etapa realizados por cada usuario.',
                'route' => 'admin.reports.prebuilt.rep-activity',
            ],
            [
                'key' => 'funnel-analysis',
                'name' => 'Análisis de embudo',
                'description' => 'Deals únicos que pasaron por cada etapa del pipeline.',
                'route' => 'admin.reports.prebuilt.funnel-analysis',
            ],
        ];

        return view('admin.reports.index', compact('reports', 'prebuiltReports'));
    }

    /**
     * Vista builder: selects de data_source/chart_type/metric/group_by/filtros.
     */
    public function create()
    {
        $pipelines = Pipeline::orderBy('name')->get();

        return view('admin.reports.create', compact('pipelines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'data_source'          => 'required|in:deals,email_campaigns,workflows,contacts,companies',
            'chart_type'           => 'required|in:bar,line,pie,doughnut,table',
            'config'               => 'nullable|array',
            'config.metric'        => 'nullable|string',
            'config.group_by'      => 'nullable|string',
            'config.filters'       => 'nullable|array',
        ]);

        $data['created_by'] = auth()->id();
        $data['is_prebuilt'] = false;

        $report = Report::create($data);

        return redirect()->route('admin.reports.show', $report)
            ->with('success', "Reporte \"{$report->name}\" creado exitosamente.");
    }

    /**
     * Endpoint AJAX para previsualizar el resultado sin guardar: construye
     * un Report no persistido con los atributos recibidos y lo pasa
     * directo al ReportBuilderService (build() solo lee los atributos).
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'data_source'      => 'required|in:deals,email_campaigns,workflows,contacts,companies',
            'config'           => 'nullable|array',
            'config.metric'    => 'nullable|string',
            'config.group_by'  => 'nullable|string',
            'config.filters'   => 'nullable|array',
        ]);

        $report = new Report([
            'data_source' => $data['data_source'],
            'config' => $data['config'] ?? [],
        ]);

        $result = $this->reportBuilderService->build($report);

        return response()->json($result);
    }

    public function show(Report $report)
    {
        $result = $this->reportBuilderService->build($report);

        return view('admin.reports.show', compact('report', 'result'));
    }

    public function export(Report $report)
    {
        $result = $this->reportBuilderService->build($report);

        $labels = $result['labels'] ?? [];
        $datasets = $result['datasets'] ?? [];

        $headings = array_merge(['Etiqueta'], array_map(fn ($d) => $d['label'] ?? 'Valor', $datasets));

        $rows = collect($labels)->map(function ($label, $index) use ($datasets) {
            $row = [$label];

            foreach ($datasets as $dataset) {
                $row[] = $dataset['data'][$index] ?? null;
            }

            return $row;
        });

        $fileName = str($report->name ?: 'reporte')->slug() . '.xlsx';

        return Excel::download(new ReportExport($rows, $headings), $fileName);
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return redirect()->route('admin.reports.index')
            ->with('success', "Reporte \"{$report->name}\" eliminado.");
    }

    /**
     * Prebuilt: rendimiento de pipeline (tiempo promedio + conversión por etapa).
     */
    public function pipelinePerformance(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:pipelines,id',
        ]);

        $result = $this->prebuiltReportService->pipelinePerformance((int) $request->pipeline_id);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        $pipelines = Pipeline::orderBy('name')->get();
        $pipeline = $pipelines->firstWhere('id', (int) $request->pipeline_id);

        return view('admin.reports.prebuilt.pipeline-performance', compact('result', 'pipelines', 'pipeline'));
    }

    /**
     * Prebuilt: actividad por vendedor (movimientos de etapa por usuario).
     */
    public function repActivity(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : null;
        $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : null;

        $rows = $this->prebuiltReportService->repActivity($from, $to);

        if ($request->wantsJson()) {
            return response()->json($rows);
        }

        return view('admin.reports.prebuilt.rep-activity', compact('rows'));
    }

    /**
     * Prebuilt: análisis de embudo (deals únicos por etapa del pipeline).
     */
    public function funnelAnalysis(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:pipelines,id',
        ]);

        $result = $this->prebuiltReportService->funnelAnalysis((int) $request->pipeline_id);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        $pipelines = Pipeline::orderBy('name')->get();
        $pipeline = $pipelines->firstWhere('id', (int) $request->pipeline_id);

        return view('admin.reports.prebuilt.funnel-analysis', compact('result', 'pipelines', 'pipeline'));
    }
}
