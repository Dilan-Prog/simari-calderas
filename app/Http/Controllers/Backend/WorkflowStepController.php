<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreWorkflowStepRequest;
use App\Http\Requests\Backend\UpdateWorkflowStepRequest;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowStep;
use App\Services\WorkflowSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD de pasos de Workflow (admin). Sigue el mismo patrón modal/AJAX-JSON
 * que PipelineController: sin vistas propias, todas las acciones se llaman
 * vía AJAX desde el builder de pasos y responden JSON.
 */
class WorkflowStepController extends Controller
{
    public function store(StoreWorkflowStepRequest $request, Workflow $workflow, WorkflowSnapshotService $snapshots)
    {
        $snapshots->capture($workflow);

        $data = $request->validated();

        $step = DB::transaction(function () use ($workflow, $data) {
            $nextOrder = WorkflowStep::where('workflow_id', $workflow->id)
                ->where('parent_step_id', $data['parent_step_id'] ?? null)
                ->where('branch_key', $data['branch_key'] ?? null)
                ->max('order');

            $nextOrder = $nextOrder === null ? 0 : $nextOrder + 1;

            return WorkflowStep::create(array_merge($data, [
                'workflow_id' => $workflow->id,
                'order' => $nextOrder,
            ]));
        });

        return response()->json([
            'success' => true,
            'step' => $step,
        ]);
    }

    public function update(UpdateWorkflowStepRequest $request, Workflow $workflow, WorkflowStep $step, WorkflowSnapshotService $snapshots)
    {
        $snapshots->capture($workflow);

        $validated = $request->validated();
        $data = $request->only(array_keys($validated));

        // Re-parenting real: solo si 'parent_step_id' vino en el payload y difiere del actual.
        if (array_key_exists('parent_step_id', $data) && $data['parent_step_id'] != $step->parent_step_id) {
            $newParentId = $data['parent_step_id'];

            if ($newParentId !== null) {
                if ((int) $newParentId === (int) $step->id) {
                    return response()->json([
                        'message' => 'No se puede conectar un nodo a su propio descendiente',
                    ], 422);
                }

                // Sube por la cadena de padres del nuevo parent candidato; si en algún
                // punto llegamos a $step, es un ciclo (estaríamos colgando $step de un
                // descendiente suyo).
                $currentId = $newParentId;
                $visited = [];

                while ($currentId !== null) {
                    if ((int) $currentId === (int) $step->id) {
                        return response()->json([
                            'message' => 'No se puede conectar un nodo a su propio descendiente',
                        ], 422);
                    }

                    // Protección extra contra bucles infinitos por datos corruptos.
                    if (isset($visited[$currentId])) {
                        break;
                    }
                    $visited[$currentId] = true;

                    $currentId = WorkflowStep::where('id', $currentId)->value('parent_step_id');
                }
            }

            // Recalcula 'order' dentro del nuevo grupo (nuevo parent_step_id + branch_key del payload).
            $branchKey = array_key_exists('branch_key', $data) ? $data['branch_key'] : $step->branch_key;

            $nextOrder = WorkflowStep::where('workflow_id', $step->workflow_id)
                ->where('parent_step_id', $newParentId)
                ->where('branch_key', $branchKey)
                ->where('id', '!=', $step->id)
                ->max('order');

            $data['order'] = $nextOrder === null ? 0 : $nextOrder + 1;
        }

        if (array_key_exists('step_type', $data)
            && $data['step_type'] !== $step->step_type
            && $step->children()->exists()
        ) {
            return response()->json([
                'message' => 'No se puede cambiar el tipo de un paso que tiene sub-pasos, bórralo y créalo de nuevo',
            ], 422);
        }

        $step->update($data);

        return response()->json([
            'success' => true,
            'step' => $step,
        ]);
    }

    public function destroy(Workflow $workflow, WorkflowStep $step, WorkflowSnapshotService $snapshots)
    {
        $snapshots->capture($workflow);

        $descendantIds = $this->collectDescendantIds($step);
        $blockedIds = array_merge([$step->id], $descendantIds);

        $hasActiveEnrollment = WorkflowEnrollment::whereIn('status', ['active', 'waiting'])
            ->whereIn('current_step_id', $blockedIds)
            ->exists();

        if ($hasActiveEnrollment) {
            return response()->json([
                'message' => 'No se puede borrar: hay inscripciones activas en este paso o en sus sub-pasos',
            ], 422);
        }

        $step->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'step_ids' => 'required|array',
            'step_ids.*' => 'integer',
            'branch_key' => 'nullable|string|in:yes,no',
            'parent_step_id' => 'nullable|integer',
        ]);

        $branchKey = $data['branch_key'] ?? null;
        $parentStepId = $data['parent_step_id'] ?? null;

        $belongingCount = WorkflowStep::where('workflow_id', $workflow->id)
            ->where('parent_step_id', $parentStepId)
            ->where('branch_key', $branchKey)
            ->whereIn('id', $data['step_ids'])
            ->count();

        if ($belongingCount !== count($data['step_ids'])) {
            return response()->json([
                'message' => 'Uno o más pasos no pertenecen a este grupo de hermanos',
            ], 422);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['step_ids'] as $index => $stepId) {
                WorkflowStep::where('id', $stepId)->update(['order' => $index]);
            }
        });

        return response()->json(['success' => true]);
    }

    public function saveLayout(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|integer',
            'positions.*.position_x' => 'required|integer',
            'positions.*.position_y' => 'required|integer',
        ]);

        $updated = 0;

        DB::transaction(function () use ($workflow, $data, &$updated) {
            foreach ($data['positions'] as $position) {
                $step = WorkflowStep::where('id', $position['id'])
                    ->where('workflow_id', $workflow->id)
                    ->first();

                if (! $step) {
                    abort(422, 'Uno o más pasos no pertenecen a este workflow');
                }

                $step->update([
                    'position_x' => $position['position_x'],
                    'position_y' => $position['position_y'],
                ]);

                $updated++;
            }
        });

        return response()->json(['success' => true, 'updated' => $updated]);
    }

    /**
     * Recorre recursivamente los descendientes de un paso y devuelve sus ids.
     */
    private function collectDescendantIds(WorkflowStep $step): array
    {
        $ids = [];

        foreach ($step->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->collectDescendantIds($child));
        }

        return $ids;
    }
}
