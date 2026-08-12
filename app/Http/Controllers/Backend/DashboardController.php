<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\Report;
use App\Services\ReportBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD de Dashboards (paneles personalizados armados a partir de Reports).
 * Sigue la misma convención que PipelineController: métodos delgados,
 * validación inline, respuestas JSON para las acciones AJAX (agregar/quitar/
 * reordenar reports, compartir) y vistas Blade para index/create/edit/show.
 */
class DashboardController extends Controller
{
    public function __construct(private ReportBuilderService $reportBuilderService)
    {
    }

    public function index()
    {
        $userId = auth()->id();

        $dashboards = Dashboard::query()
            ->where('owner_id', $userId)
            ->orWhere('is_shared', true)
            ->orWhereHas('sharedWith', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->with('owner')
            ->latest()
            ->get();

        return view('admin.dashboards.index', compact('dashboards'));
    }

    public function create()
    {
        $reports = Report::orderBy('name')->get();

        return view('admin.dashboards.create', compact('reports'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'is_shared' => 'nullable|boolean',
        ]);

        $dashboard = Dashboard::create([
            'name' => $data['name'],
            'owner_id' => auth()->id(),
            'is_shared' => $data['is_shared'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }

    public function edit(Dashboard $dashboard)
    {
        $dashboard->load(['reports', 'sharedWith']);

        $availableReports = Report::whereNotIn('id', $dashboard->reports->pluck('id'))
            ->orderBy('name')
            ->get();

        // Usuarios disponibles para el panel "Compartir con" (multi-select).
        $users = \App\Models\User::orderBy('first_name')->get();

        return view('admin.dashboards.edit', compact('dashboard', 'availableReports', 'users'));
    }

    public function update(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'is_shared' => 'nullable|boolean',
        ]);

        $dashboard->update([
            'name' => $data['name'],
            'is_shared' => $data['is_shared'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'dashboard' => $dashboard,
        ]);
    }

    public function addReport(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'report_id' => 'required|integer|exists:reports,id',
        ]);

        if ($dashboard->reports()->where('reports.id', $data['report_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El reporte ya está en este dashboard.',
            ], 422);
        }

        $position = (int) $dashboard->reports()->max('dashboard_reports.position') + 1;

        $dashboard->reports()->attach($data['report_id'], ['position' => $position]);

        return response()->json(['success' => true]);
    }

    public function removeReport(Dashboard $dashboard, Report $report)
    {
        $dashboard->reports()->detach($report->id);

        return response()->json(['success' => true]);
    }

    public function reorderReports(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($dashboard, $data) {
            foreach ($data['report_ids'] as $index => $reportId) {
                $dashboard->reports()->updateExistingPivot($reportId, ['position' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function show(Dashboard $dashboard)
    {
        abort_unless($dashboard->isVisibleTo(auth()->user()), 403);

        $dashboard->load('reports');

        $builtReports = [];
        foreach ($dashboard->reports as $report) {
            $builtReports[$report->id] = $this->reportBuilderService->build($report);
        }

        return view('admin.dashboards.show', compact('dashboard', 'builtReports'));
    }

    public function share(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $dashboard->sharedWith()->sync($data['user_ids'] ?? []);

        return response()->json(['success' => true]);
    }

    public function destroy(Dashboard $dashboard)
    {
        $dashboard->delete();

        return response()->json(['success' => true]);
    }
}
