<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use App\Models\WorkflowCanvasNote;
use App\Models\WorkflowVariable;
use App\Services\AutomatableModuleRegistry;
use App\Services\WorkflowEngineService;
use App\Services\WorkflowSnapshotService;
use App\Services\WorkflowStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowEngineService $workflowEngineService,
        private WorkflowSnapshotService $workflowSnapshotService,
        private AutomatableModuleRegistry $automatableModuleRegistry
    ) {}

    /**
     * Listado de workflows con el conteo de inscripciones activas/en espera
     * de cada uno, para dar una idea rápida de qué automatizaciones están
     * corriendo en este momento.
     */
    /**
     * Listado de workflows (no plantillas) con su tasa de éxito de
     * inscripciones, stack de acciones usadas y KPIs globales, para dar
     * una idea rápida de qué automatizaciones están corriendo y cómo van.
     */
    public function index()
    {
        $workflows = Workflow::where('is_template', false)
            ->withCount('allSteps as node_count')
            ->latest()
            ->get();

        $stats = app(WorkflowStatsService::class);
        $successMap = $stats->enrollmentSuccessMap($workflows->pluck('id'));

        $workflows->each(function (Workflow $workflow) use ($stats, $successMap) {
            $workflow->stats = $successMap[$workflow->id] ?? [
                'total' => 0, 'failed' => 0, 'success_pct' => 0,
                'last_enrolled_at' => null, 'last_ok' => true,
            ];
            $workflow->action_stack = $stats->actionStackFor($workflow);
        });

        $kpis = $stats->globalKpis($workflows);

        return view('admin.workflows.index', compact('workflows', 'kpis'));
    }

    /**
     * Catálogo de workflows marcados como plantilla, listos para duplicarse
     * como punto de partida de un nuevo workflow.
     */
    public function templates()
    {
        $workflows = Workflow::where('is_template', true)->get();

        $stats = app(WorkflowStatsService::class);
        $workflows->each(function (Workflow $workflow) use ($stats) {
            $workflow->action_stack = $stats->actionStackFor($workflow);
        });

        return view('admin.workflows.templates', compact('workflows'));
    }

    /**
     * Duplica un workflow (plantilla o no) junto con todo su árbol de
     * steps, preservando la jerarquía parent_step_id vía un mapa de IDs
     * viejo->nuevo resuelto por dependencia (no por orden de la consulta).
     * La copia queda inactiva para que se revise antes de activarla.
     */
    public function duplicate(Workflow $workflow)
    {
        $clone = DB::transaction(function () use ($workflow) {
            $newWorkflow = Workflow::create([
                'name' => $workflow->name . ' (copia)',
                'description' => $workflow->description,
                'type' => $workflow->type,
                'enrollment_trigger' => $workflow->enrollment_trigger,
                'is_active' => false,
                'reenrollment_allowed' => $workflow->reenrollment_allowed,
                'is_template' => false,
                'created_by' => auth()->id(),
            ]);

            $oldSteps = $workflow->allSteps()->orderBy('parent_step_id')->orderBy('order')->get();
            $idMap = [];

            // Primero los raíz (parent_step_id null), luego el resto en pasadas
            // sucesivas hasta que todos tengan su nuevo padre resuelto -- así no
            // importa el orden real de $oldSteps, se resuelve por dependencia.
            $pending = $oldSteps->keyBy('id');
            while ($pending->isNotEmpty()) {
                $progressed = false;
                foreach ($pending as $oldId => $oldStep) {
                    $canCreate = $oldStep->parent_step_id === null || array_key_exists($oldStep->parent_step_id, $idMap);
                    if (!$canCreate) continue;

                    $newStep = WorkflowStep::create([
                        'workflow_id' => $newWorkflow->id,
                        'parent_step_id' => $oldStep->parent_step_id !== null ? $idMap[$oldStep->parent_step_id] : null,
                        'branch_key' => $oldStep->branch_key,
                        'order' => $oldStep->order,
                        'step_type' => $oldStep->step_type,
                        'action_type' => $oldStep->action_type,
                        'action_config' => $oldStep->action_config,
                        'branch_condition' => $oldStep->branch_condition,
                        'position_x' => $oldStep->position_x,
                        'position_y' => $oldStep->position_y,
                    ]);

                    $idMap[$oldId] = $newStep->id;
                    $pending->forget($oldId);
                    $progressed = true;
                }
                if (!$progressed) {
                    // Dato corrupto (ciclo o padre huérfano) -- evita loop infinito.
                    break;
                }
            }

            return $newWorkflow;
        });

        return redirect()->route('admin.workflows.edit', $clone)
            ->with('success', 'Workflow duplicado. Revisa y activa la copia cuando esté lista.');
    }

    /**
     * Crea un workflow vacío con valores por defecto y lleva directo al
     * editor (canvas), sin pasar por el formulario de create().
     */
    public function quickCreate()
    {
        $workflow = Workflow::create([
            'name'                 => 'Nuevo workflow',
            'type'                 => 'deal',
            'enrollment_trigger'   => [],
            'is_active'            => false,
            'reenrollment_allowed' => false,
            'created_by'           => auth()->id(),
        ]);

        return redirect()->route('admin.workflows.edit', $workflow);
    }

    /**
     * Vista con los datos básicos del workflow; el step-builder (canvas)
     * carga sus propios datos vía canvas() por AJAX.
     */
    public function edit(Workflow $workflow)
    {
        return view('admin.workflows.edit', compact('workflow'));
    }

    /**
     * Datos del workflow, su árbol de steps (plano) y el catálogo de
     * acciones/operadores disponibles, para el step-builder tipo canvas.
     */
    public function canvas(Workflow $workflow)
    {
        return response()->json([
            'workflow' => $workflow->only(['id', 'name', 'type', 'enrollment_trigger', 'is_active']),
            'steps' => $workflow->allSteps()->get([
                'id', 'parent_step_id', 'branch_key', 'order', 'step_type',
                'action_type', 'action_config', 'branch_condition',
                'position_x', 'position_y',
            ]),
            'notes' => WorkflowCanvasNote::where('workflow_id', $workflow->id)->get(),
            'variables' => WorkflowVariable::where('workflow_id', $workflow->id)
                ->orWhereNull('workflow_id')
                ->get(),
            'can_undo' => $this->workflowSnapshotService->canUndo($workflow),
            'can_redo' => $this->workflowSnapshotService->canRedo($workflow),
            'catalog' => [
                'action_types' => \App\Services\WorkflowActionExecutor::supportedActions(),
                'wait_units' => ['minutes' => 'Minutos', 'hours' => 'Horas', 'days' => 'Días'],
                'condition_operators' => [
                    'equals' => 'Igual a',
                    'not_equals' => 'Distinto de',
                    'greater_than' => 'Mayor que',
                    'less_than' => 'Menor que',
                    'contains' => 'Contiene',
                ],
                'modules' => collect($this->automatableModuleRegistry->all())->map(function ($entry, $type) {
                    return [
                        'type' => $type,
                        'label' => $entry['label'] ?? $type,
                        'group' => $entry['group'] ?? 'Otro',
                        'fields' => $this->automatableModuleRegistry->fieldsFor($type),
                        'relations' => $entry['relations'] ?? [],
                        'supports_stale' => $entry['supports_stale'] ?? false,
                    ];
                })->values(),
                // Sugerencias de VALOR real para campos que referencian un id de
                // catálogo (ej. "pipeline_stage_id") -- sin esto, escribir
                // "value": 4 en el trigger obliga a adivinar a qué etapa
                // corresponde ese id. Mapa por nombre de campo (compartido por
                // Deal/WhatsappConversation, ambos usan pipeline_stage_id), no
                // por módulo -- se extiende agregando más claves aquí conforme
                // aparezcan otros campos de id que lo justifiquen.
                'field_value_sources' => [
                    'pipeline_stage_id' => PipelineStage::with('pipeline')
                        ->orderBy('pipeline_id')
                        ->orderBy('order')
                        ->get()
                        ->map(fn (PipelineStage $stage) => [
                            'value' => $stage->id,
                            'hint' => ($stage->pipeline->name ?? '?') . ' → ' . $stage->name,
                        ])
                        ->values(),
                ],
            ],
        ]);
    }

    public function update(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'name'                  => 'sometimes|required|string|max:255',
            'type'                  => 'sometimes|required|string|max:100',
            'enrollment_trigger'    => 'sometimes|nullable|json',
            'is_active'             => 'sometimes|nullable|boolean',
            'reenrollment_allowed'  => 'sometimes|nullable|boolean',
        ]);

        $updates = $request->only(array_keys($data));

        if (array_key_exists('enrollment_trigger', $updates)) {
            $updates['enrollment_trigger'] = $updates['enrollment_trigger'] !== null
                ? json_decode($updates['enrollment_trigger'], true)
                : null;
        }

        if (array_key_exists('is_active', $updates)) {
            $updates['is_active'] = $request->boolean('is_active');
        }

        if (array_key_exists('reenrollment_allowed', $updates)) {
            $updates['reenrollment_allowed'] = $request->boolean('reenrollment_allowed');
        }

        $this->workflowSnapshotService->capture($workflow);

        $workflow->update($updates);

        return redirect()->route('admin.workflows.edit', $workflow)
            ->with('success', "Workflow \"{$workflow->name}\" actualizado exitosamente.");
    }

    public function undo(Workflow $workflow)
    {
        return response()->json($this->workflowSnapshotService->undo($workflow));
    }

    public function redo(Workflow $workflow)
    {
        return response()->json($this->workflowSnapshotService->redo($workflow));
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

    /**
     * Modo de PRUEBA: recorre el árbol del workflow con datos de ejemplo
     * (proporcionados o por defecto) SIN ejecutar ninguna acción real.
     */
    public function test(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'sample_data' => 'nullable|array',
        ]);

        $sampleData = $data['sample_data'] ?? [
            'status' => 'open',
            'amount' => 1000,
            'name'   => 'Cliente de ejemplo',
        ];

        $log = app(\App\Services\WorkflowSimulationService::class)->run($workflow, $sampleData);

        return response()->json(['success' => true, 'log' => $log]);
    }
}
