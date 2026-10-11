<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\ServiceReport;
use App\Models\SystemLog;
use App\Models\TechnicalService;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /** Tipos de documento asociables: clave => [modelo, columna de folio, etiqueta]. */
    private const DOC_TYPES = [
        'quote'  => [Quote::class, 'quote_number', 'Cotización'],
        'service' => [TechnicalService::class, 'service_number', 'Servicio técnico'],
        'report' => [ServiceReport::class, 'report_number', 'Reporte de servicio'],
        'order'  => [SalesOrder::class, 'order_number', 'Pedido'],
    ];

    public function __construct(private ProjectService $projects)
    {
    }

    public function index(Request $request)
    {
        $query = Project::with(['customer', 'manager'])->withCount('phases');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('project_number', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('company', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('company', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status') && isset(Project::STATUSES[$request->status])) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->customer_id);
        }
        if ($request->filled('manager_id')) {
            $query->where('manager_user_id', (int) $request->manager_id);
        }
        if ($request->input('due') === 'vencido') {
            $query->where('status', '!=', 'cerrado')->whereDate('due_date', '<', now()->toDateString());
        }

        $view = $request->input('view') === 'board' ? 'board' : 'list';

        if ($view === 'board') {
            $all = $query->orderByRaw('due_date is null')->orderBy('due_date')->get();
            $projects = null;
            $columns = collect(Project::STATUSES)->map(fn ($label, $key) => $all->where('status', $key)->values());
        } else {
            $projects = $query->latest()->paginate(15)->withQueryString();
            $columns = null;
        }

        $stats = [
            'total'    => Project::count(),
            'running'  => Project::where('status', 'en_ejecucion')->count(),
            'overdue'  => Project::where('status', '!=', 'cerrado')->whereDate('due_date', '<', now()->toDateString())->count(),
            'closed'   => Project::where('status', 'cerrado')->count(),
        ];

        $managers = User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $filterCustomer = $request->filled('customer_id') ? Customer::find((int) $request->customer_id) : null;

        return view('admin.projects.index', compact('projects', 'columns', 'view', 'stats', 'managers', 'filterCustomer'));
    }

    public function create()
    {
        return view('admin.projects.form', [
            'project'   => new Project(['status' => 'levantamiento']),
            'phases'    => [],
            'users'     => $this->users(),
            'preset'    => $this->presetFromQuote(request('quote')),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $project = $this->projects->create($data, $request->input('phases', []), auth()->id());

        // Cotización de origen (botón "Crear proyecto" desde una cotización).
        if ($request->filled('from_quote') && ($quote = Quote::find((int) $request->from_quote))) {
            $this->projects->attach($project, $quote);
        }

        return redirect()->route('admin.projects.show', $project)->with('success', 'Proyecto creado.');
    }

    public function show(Project $project)
    {
        $project->load(['customer', 'manager', 'createdBy', 'phases.assignees', 'quotes.customer', 'salesOrders', 'technicalServices.customer', 'serviceReports']);

        $progress = $this->projects->progress($project);
        $logs = SystemLog::with('performedBy')
            ->where('entity_type', 'project')->where('entity_id', $project->id)
            ->latest('performed_at')->limit(100)->get();

        return view('admin.projects.show', [
            'project'  => $project,
            'progress' => $progress,
            'logs'     => $logs,
            'timeline' => $this->timeline($project),
        ]);
    }

    public function edit(Project $project)
    {
        $project->load(['customer', 'phases.assignees']);

        return view('admin.projects.form', [
            'project' => $project,
            'phases'  => $project->phases,
            'users'   => $this->users(),
            'preset'  => null,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $this->validated($request);
        $this->projects->update($project, $data, $request->input('phases', []));
        $this->projects->syncDocumentFlow($project);

        return redirect()->route('admin.projects.show', $project)->with('success', 'Proyecto actualizado.');
    }

    public function destroy(Project $project)
    {
        DocumentFlow::unlinkAll($project);
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Proyecto eliminado.');
    }

    public function updateStatus(Request $request, Project $project)
    {
        $request->validate(['status' => ['required', Rule::in(array_keys(Project::STATUSES))]]);
        $this->projects->changeStatus($project, $request->status);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $project->status, 'label' => Project::statusLabel($project->status)]);
        }

        return back()->with('success', 'Estado actualizado a ' . Project::statusLabel($project->status) . '.');
    }

    public function updatePhase(Request $request, Project $project, ProjectPhase $phase)
    {
        abort_unless($phase->project_id === $project->id, 404);
        $data = $request->validate([
            'status'   => ['required', Rule::in(array_keys(ProjectPhase::STATUSES))],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $progress = $data['status'] === 'completada' ? 100 : (int) ($data['progress'] ?? $phase->progress);
        $phase->update(['status' => $data['status'], 'progress' => $progress]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'project_progress' => $this->projects->progress($project->fresh())])
            : back()->with('success', 'Fase actualizada.');
    }

    // ── Asociación de documentos ─────────────────────────────────────────

    public function attachDocument(Request $request, Project $project)
    {
        [$doc] = $this->resolveDocument($request);
        $this->projects->attach($project, $doc);

        return back()->with('success', 'Documento asociado al proyecto.');
    }

    public function detachDocument(Request $request, Project $project)
    {
        [$doc] = $this->resolveDocument($request);
        $this->projects->detach($project, $doc);

        return back()->with('success', 'Documento desasociado del proyecto.');
    }

    /** Asocia/desasocia desde la pantalla del documento (project_id vacío = quitar). */
    public function linkDocument(Request $request)
    {
        [$doc] = $this->resolveDocument($request);
        $request->validate(['project_id' => ['nullable', 'integer', 'exists:projects,id']]);

        if ($doc->project_id && (int) $doc->project_id !== (int) $request->project_id) {
            $old = Project::find($doc->project_id);
            if ($old) {
                $this->projects->detach($old, $doc);
            }
        }
        if ($request->filled('project_id')) {
            $this->projects->attach(Project::findOrFail($request->project_id), $doc);
        }

        return back()->with('success', $request->filled('project_id') ? 'Proyecto asociado.' : 'Proyecto desasociado.');
    }

    public function searchDocuments(Request $request)
    {
        $type = $request->input('type');
        abort_unless(isset(self::DOC_TYPES[$type]), 422);
        [$model, $col, $label] = self::DOC_TYPES[$type];
        $q = trim((string) $request->input('q', ''));

        $rows = $model::query()
            ->when($q !== '', fn ($query) => $query->where($col, 'like', "%{$q}%"))
            ->when($request->filled('exclude_project'), fn ($query) => $query->where(fn ($w) => $w->whereNull('project_id')->orWhere('project_id', '!=', (int) $request->exclude_project)))
            ->latest('id')->limit(15)->get()
            ->map(fn ($d) => [
                'id'      => $d->id,
                'number'  => $d->{$col},
                'project' => $d->project_id ? optional(Project::find($d->project_id))->project_number : null,
            ]);

        return response()->json($rows);
    }

    public function searchProjects(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(
            Project::where('status', '!=', 'cerrado')
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('project_number', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
                ->latest()->limit(15)->get(['id', 'project_number', 'name'])
        );
    }

    public function searchCustomers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        return response()->json(
            Customer::where(fn ($w) => $w->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%"))
                ->limit(10)->get(['id', 'first_name', 'last_name', 'company'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => trim($c->first_name . ' ' . $c->last_name), 'company' => $c->company])
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function resolveDocument(Request $request): array
    {
        $request->validate([
            'type' => ['required', Rule::in(array_keys(self::DOC_TYPES))],
            'id'   => ['required', 'integer'],
        ]);
        [$model] = self::DOC_TYPES[$request->type];

        return [$model::findOrFail((int) $request->id)];
    }

    private function users()
    {
        return User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);
    }

    private function presetFromQuote($quoteId): ?array
    {
        if (!$quoteId || !($q = Quote::with('customer')->find((int) $quoteId))) {
            return null;
        }

        return [
            'quote_id'    => $q->id,
            'quote_number' => $q->quote_number,
            'customer_id' => $q->customer_id,
            'customer'    => $q->customer ? trim($q->customer->first_name . ' ' . $q->customer->last_name) : null,
            'company'     => $q->guest_company,
        ];
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'phases'                  => ['nullable', 'array'],
            'phases.*.name'           => ['nullable', 'string', 'max:255'],
            'phases.*.description'    => ['nullable', 'string'],
            'phases.*.requirements'   => ['nullable', 'string'],
            'phases.*.status'         => ['nullable', Rule::in(array_keys(ProjectPhase::STATUSES))],
            'phases.*.progress'       => ['nullable', 'integer', 'min:0', 'max:100'],
            'phases.*.start_date'     => ['nullable', 'date'],
            'phases.*.end_date'       => ['nullable', 'date'],
            'phases.*.assignees'      => ['nullable', 'array'],
            'phases.*.assignees.*'    => ['integer', 'exists:users,id'],
        ]);

        return $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'customer_id'             => ['nullable', 'integer', 'exists:customers,id'],
            'company'                 => ['nullable', 'string', 'max:255'],
            'manager_user_id'         => ['nullable', 'integer', 'exists:users,id'],
            'estimated_duration_days' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'estimated_time'          => ['nullable', 'string', 'max:255'],
            'start_date'              => ['nullable', 'date'],
            'due_date'                => array_filter(['nullable', 'date', $request->filled('start_date') ? 'after_or_equal:start_date' : null]),
            'status'                  => ['nullable', Rule::in(array_keys(Project::STATUSES))],
            'notes'                   => ['nullable', 'string'],
        ]);
    }

    /**
     * Datos para el Gantt simple: rango global y, por fase, offset/ancho en %.
     */
    private function timeline(Project $project): array
    {
        $dates = collect();
        foreach ($project->phases as $ph) {
            $dates->push($ph->start_date, $ph->end_date);
        }
        $dates->push($project->start_date, $project->due_date);
        $dates = $dates->filter();

        if ($dates->isEmpty()) {
            return ['min' => null, 'max' => null, 'rows' => [], 'today' => null];
        }

        $min = $dates->min()->copy()->startOfDay();
        $max = $dates->max()->copy()->startOfDay();
        $span = max(1, $min->diffInDays($max) + 1);

        $rows = [];
        foreach ($project->phases as $ph) {
            $s = $ph->start_date ?? $ph->end_date;
            $e = $ph->end_date ?? $ph->start_date;
            if (!$s) {
                $rows[] = ['phase' => $ph, 'left' => 0, 'width' => 0];
                continue;
            }
            $left = $min->diffInDays($s->copy()->startOfDay()) / $span * 100;
            $width = (($s->copy()->startOfDay()->diffInDays($e->copy()->startOfDay()) + 1) / $span) * 100;
            $rows[] = ['phase' => $ph, 'left' => round($left, 2), 'width' => round(max($width, 1.5), 2)];
        }

        $today = now()->startOfDay();
        $todayPct = ($today->gte($min) && $today->lte($max)) ? round($min->diffInDays($today) / $span * 100, 2) : null;

        return ['min' => $min, 'max' => $max, 'rows' => $rows, 'today' => $todayPct];
    }
}
