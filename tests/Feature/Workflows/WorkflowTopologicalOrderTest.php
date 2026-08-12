<?php

namespace Tests\Feature\Workflows;

use App\Http\Controllers\Backend\WorkflowController;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de caracterización del algoritmo de orden topológico multi-pasada
 * que WorkflowController::duplicate() y WorkflowSnapshotService::
 * reconcileSteps() implementan cada uno por su lado (mismo patrón, sin
 * código compartido) para poder crear/reinsertar un árbol de WorkflowStep
 * sin importar el orden en que aparezcan los datos de entrada, siempre que
 * cada padre quede resuelto antes que sus hijos.
 *
 * Se ejercita un árbol de 3 niveles (root -> condition -> hijos de branch,
 * con nietos debajo de un hijo) para que una sola pasada ingenua NO
 * bastaría -- hace falta el multi-pasada real de ambos algoritmos.
 */
class WorkflowTopologicalOrderTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name'                  => 'Workflow de prueba',
            'type'                  => 'customer',
            'enrollment_trigger'    => ['type' => 'manual'],
            'is_active'             => true,
            'reenrollment_allowed'  => false,
        ], $overrides));
    }

    /**
     * Arbol de 3 niveles:
     *   root (action)
     *     -> condition (branch_key null, hijo de root)
     *          -> yesChild (branch_key='yes')
     *               -> grandchild (branch_key=null, hijo de yesChild)
     *          -> noChild (branch_key='no')
     */
    private function buildThreeLevelTree(Workflow $workflow): array
    {
        $root = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ]);

        $condition = WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => $root->id,
            'order'            => 0,
            'step_type'        => 'condition',
            'action_type'      => null,
            'action_config'    => [],
            'branch_condition' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        ]);

        $yesChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $condition->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
            'branch_key'     => 'yes',
        ]);

        $noChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $condition->id,
            'order'          => 1,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
            'branch_key'     => 'no',
        ]);

        $grandchild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $yesChild->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ]);

        return compact('root', 'condition', 'yesChild', 'noChild', 'grandchild');
    }

    // --- WorkflowController::duplicate() ------------------------------

    public function test_duplicate_preserves_three_level_tree_structure(): void
    {
        $workflow = $this->makeWorkflow();
        $tree = $this->buildThreeLevelTree($workflow);

        $controller = app(WorkflowController::class);
        $controller->duplicate($workflow);

        $clone = Workflow::where('name', $workflow->name . ' (copia)')->firstOrFail();

        $this->assertCount(5, WorkflowStep::where('workflow_id', $clone->id)->get());

        $newRoot = WorkflowStep::where('workflow_id', $clone->id)->whereNull('parent_step_id')->firstOrFail();
        $this->assertEquals('action', $newRoot->step_type);

        $newCondition = WorkflowStep::where('workflow_id', $clone->id)->where('parent_step_id', $newRoot->id)->firstOrFail();
        $this->assertEquals('condition', $newCondition->step_type);

        $newYesChild = WorkflowStep::where('workflow_id', $clone->id)
            ->where('parent_step_id', $newCondition->id)
            ->where('branch_key', 'yes')
            ->firstOrFail();

        $newNoChild = WorkflowStep::where('workflow_id', $clone->id)
            ->where('parent_step_id', $newCondition->id)
            ->where('branch_key', 'no')
            ->firstOrFail();

        $newGrandchild = WorkflowStep::where('workflow_id', $clone->id)
            ->where('parent_step_id', $newYesChild->id)
            ->firstOrFail();

        $this->assertNotEquals($newYesChild->id, $newNoChild->id);
        $this->assertEquals($newYesChild->id, $newGrandchild->parent_step_id);

        // Los ids son nuevos: nada del clon reutiliza los ids originales.
        $originalIds = collect($tree)->pluck('id')->all();
        $this->assertEmpty(array_intersect($originalIds, [
            $newRoot->id, $newCondition->id, $newYesChild->id, $newNoChild->id, $newGrandchild->id,
        ]));
    }

    // --- WorkflowSnapshotService::reconcileSteps() ------------------------

    public function test_reconcile_steps_reinserts_three_level_tree_regardless_of_input_order(): void
    {
        $workflow = $this->makeWorkflow();
        $tree = $this->buildThreeLevelTree($workflow);

        [$stepsSnapshot] = $this->snapshotPayloadFor($workflow);

        // El snapshot llega en orden "peor caso": nieto, hoja, condición,
        // raíz -- exactamente al revés de la dependencia real.
        $reversed = array_reverse($stepsSnapshot);

        WorkflowStep::where('workflow_id', $workflow->id)->delete();
        $this->assertCount(0, WorkflowStep::where('workflow_id', $workflow->id)->get());

        app(WorkflowSnapshotService::class)->reconcileSteps($workflow, $reversed);

        $reinserted = WorkflowStep::where('workflow_id', $workflow->id)->get()->keyBy('id');

        $this->assertCount(5, $reinserted);
        $this->assertEquals($tree['root']->id, $reinserted[$tree['condition']->id]->parent_step_id);
        $this->assertEquals($tree['condition']->id, $reinserted[$tree['yesChild']->id]->parent_step_id);
        $this->assertEquals($tree['condition']->id, $reinserted[$tree['noChild']->id]->parent_step_id);
        $this->assertEquals($tree['yesChild']->id, $reinserted[$tree['grandchild']->id]->parent_step_id);
        $this->assertNull($reinserted[$tree['root']->id]->parent_step_id);
    }

    /**
     * Confirma que ambos algoritmos (duplicate() y reconcileSteps())
     * producen el mismo árbol de parentescos relativos a partir del mismo
     * árbol original, aunque cada uno construya/reconstruya sus filas de
     * forma distinta (INSERT con nuevos ids vs. re-INSERT preservando ids).
     */
    public function test_duplicate_and_reconcile_steps_agree_on_the_resulting_tree_shape(): void
    {
        $workflowForDuplicate = $this->makeWorkflow(['name' => 'Original para duplicar']);
        $this->buildThreeLevelTree($workflowForDuplicate);

        $workflowForReconcile = $this->makeWorkflow(['name' => 'Original para reconciliar']);
        $treeToReconcile = $this->buildThreeLevelTree($workflowForReconcile);

        // 1. duplicate(): construye un árbol nuevo con ids nuevos.
        app(WorkflowController::class)->duplicate($workflowForDuplicate);
        $clone = Workflow::where('name', 'Original para duplicar (copia)')->firstOrFail();
        $shapeFromDuplicate = $this->relativeShape(WorkflowStep::where('workflow_id', $clone->id)->get());

        // 2. reconcileSteps(): reinserta el mismo árbol tras borrarlo,
        // preservando los ids originales, a partir de un snapshot en orden
        // invertido.
        [$stepsSnapshot] = $this->snapshotPayloadFor($workflowForReconcile);
        WorkflowStep::where('workflow_id', $workflowForReconcile->id)->delete();
        app(WorkflowSnapshotService::class)->reconcileSteps($workflowForReconcile, array_reverse($stepsSnapshot));
        $shapeFromReconcile = $this->relativeShape(WorkflowStep::where('workflow_id', $workflowForReconcile->id)->get());

        $this->assertEquals($shapeFromReconcile, $shapeFromDuplicate);
    }

    /**
     * Payload steps_snapshot tal como lo arma WorkflowSnapshotService (mismo
     * shape que usa capture()/undo()/redo()).
     */
    private function snapshotPayloadFor(Workflow $workflow): array
    {
        $stepsSnapshot = $workflow->allSteps()->get([
            'id', 'parent_step_id', 'branch_key', 'order', 'step_type',
            'action_type', 'action_config', 'branch_condition',
            'position_x', 'position_y',
        ])->toArray();

        return [$stepsSnapshot];
    }

    /**
     * Forma "relativa" de un árbol de steps: por cada step_type/branch_key,
     * cuenta cuántos hijos tiene y a qué profundidad está, sin depender de
     * los ids reales (que difieren entre duplicate() y reconcileSteps()).
     */
    private function relativeShape($steps): array
    {
        $byId = $steps->keyBy('id');

        $depthOf = function ($step) use (&$depthOf, $byId) {
            $depth = 0;
            while ($step->parent_step_id !== null) {
                $step = $byId[$step->parent_step_id];
                $depth++;
            }
            return $depth;
        };

        $shape = $steps->map(fn ($step) => [
            'step_type'    => $step->step_type,
            'branch_key'   => $step->branch_key,
            'depth'        => $depthOf($step),
            'child_count'  => $steps->where('parent_step_id', $step->id)->count(),
        ])->sortBy(fn ($row) => $row['depth'] . '-' . $row['step_type'] . '-' . ($row['branch_key'] ?? ''))->values()->all();

        return $shape;
    }
}
