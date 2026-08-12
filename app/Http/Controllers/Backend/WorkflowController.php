<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Services\WorkflowEngineService;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowEngineService $workflowEngineService
    ) {}

    /**
     * Listado de workflows con el conteo de inscripciones activas/en espera
     * de cada uno, para dar una idea rápida de qué automatizaciones están
     * corriendo en este momento.
     */
    public function index()
    {
        $workflows = Workflow::withCount([
            'enrollments as active_enrollments_count' => function ($query) {
                $query->whereIn('status', ['active', 'waiting']);
            },
        ])->latest()->get();

        return view('admin.workflows.index', compact('workflows'));
    }

    public function create()
    {
        return view('admin.workflows.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'type'                  => 'required|string|max:100',
            'enrollment_trigger'    => 'nullable|json',
            'is_active'             => 'nullable|boolean',
            'reenrollment_allowed'  => 'nullable|boolean',
        ]);

        $workflow = Workflow::create([
            'name'                 => $data['name'],
            'type'                 => $data['type'],
            'enrollment_trigger'   => isset($data['enrollment_trigger'])
                ? json_decode($data['enrollment_trigger'], true)
                : null,
            'is_active'            => $request->boolean('is_active'),
            'reenrollment_allowed' => $request->boolean('reenrollment_allowed'),
            'created_by'           => auth()->id(),
        ]);

        return redirect()->route('admin.workflows.edit', $workflow)
            ->with('success', "Workflow \"{$workflow->name}\" creado exitosamente. Ahora agrega los pasos.");
    }

    /**
     * Carga el workflow con sus steps de primer nivel y sus hijos anidados
     * (para condiciones/ramas) — la vista consume este árbol para el
     * step-builder.
     */
    public function edit(Workflow $workflow)
    {
        $workflow->load(['steps.children.children']);

        return view('admin.workflows.edit', compact('workflow'));
    }

    public function update(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'type'                  => 'required|string|max:100',
            'enrollment_trigger'    => 'nullable|json',
            'is_active'             => 'nullable|boolean',
            'reenrollment_allowed'  => 'nullable|boolean',
        ]);

        $workflow->update([
            'name'                 => $data['name'],
            'type'                 => $data['type'],
            'enrollment_trigger'   => isset($data['enrollment_trigger'])
                ? json_decode($data['enrollment_trigger'], true)
                : null,
            'is_active'            => $request->boolean('is_active'),
            'reenrollment_allowed' => $request->boolean('reenrollment_allowed'),
        ]);

        return redirect()->route('admin.workflows.edit', $workflow)
            ->with('success', "Workflow \"{$workflow->name}\" actualizado exitosamente.");
    }

    /**
     * Vista de debug de ejecuciones: inscripciones más recientes con su
     * bitácora de pasos ejecutados.
     */
    public function show(Workflow $workflow)
    {
        $enrollments = $workflow->enrollments()
            ->with('logs')
            ->latest('enrolled_at')
            ->paginate(20);

        return view('admin.workflows.show', compact('workflow', 'enrollments'));
    }

    /**
     * Borrado seguro: si el workflow tiene inscripciones activas o en
     * espera, no se permite borrarlo (mismo patrón que
     * PipelineService::deletePipeline).
     */
    public function destroy(Workflow $workflow)
    {
        try {
            $activeCount = $workflow->enrollments()
                ->whereIn('status', ['active', 'waiting'])
                ->count();

            if ($activeCount > 0) {
                throw new \RuntimeException(
                    "El workflow \"{$workflow->name}\" tiene {$activeCount} inscripción(es) activa(s) o en espera. ".
                    'Espera a que terminen o cancélalas antes de borrar el workflow.'
                );
            }

            $workflow->allSteps()->delete();
            $workflow->delete();
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.workflows.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.workflows.index')
            ->with('success', "Workflow \"{$workflow->name}\" eliminado.");
    }

    public function toggleActive(Workflow $workflow)
    {
        $workflow->update(['is_active' => !$workflow->is_active]);

        $status = $workflow->is_active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Workflow \"{$workflow->name}\" {$status}.");
    }
}
