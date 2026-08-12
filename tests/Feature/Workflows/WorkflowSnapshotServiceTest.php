<?php

namespace Tests\Feature\Workflows;

use App\Models\Workflow;
use App\Models\WorkflowEditSnapshot;
use App\Models\WorkflowStep;
use App\Services\WorkflowSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de caracterización de WorkflowSnapshotService: capture()/undo()/
 * redo()/reconcileSteps(), incluyendo el caso que ya tuvo un bug real
 * (undo() una vez y luego redo(), confirmar que hay a dónde volver) y el
 * caso de reinserción de pasos borrados por una acción posterior al
 * snapshot al que se retrocede.
 */
class WorkflowSnapshotServiceTest extends TestCase
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

    private function makeStep(Workflow $workflow, array $overrides = []): WorkflowStep
    {
        return WorkflowStep::create(array_merge([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ], $overrides));
    }

    // --- capture() -----------------------------------------------------

    public function test_capture_creates_snapshot_with_current_steps_and_keeps_workflow_live(): void
    {
        $workflow = $this->makeWorkflow();
        $this->makeStep($workflow, ['order' => 0]);

        $service = app(WorkflowSnapshotService::class);
        $snapshot = $service->capture($workflow);

        $this->assertInstanceOf(WorkflowEditSnapshot::class, $snapshot);
        $this->assertEquals($workflow->id, $snapshot->workflow_id);
        $this->assertCount(1, $snapshot->steps_snapshot);

        $workflow->refresh();
        $this->assertNull($workflow->current_snapshot_id);
    }

    public function test_capture_discards_redo_branch_ahead_of_current_position(): void
    {
        $workflow = $this->makeWorkflow();
        $step = $this->makeStep($workflow, ['order' => 0]);

        $service = app(WorkflowSnapshotService::class);

        // Dos capturas normales, luego un undo() para posicionarnos en medio
        // de la historia (con algo "por delante" al que redo() podría ir).
        $service->capture($workflow);
        $step->update(['order' => 1]);
        $service->capture($workflow);
        $step->update(['order' => 2]);

        $service->undo($workflow);
        $workflow->refresh();
        $this->assertNotNull($workflow->current_snapshot_id);
        $this->assertTrue($service->canRedo($workflow));

        // Una nueva edición capturada desde este punto intermedio debe
        // descartar la rama de "rehacer" y volver a dejar al workflow "en vivo".
        $service->capture($workflow);
        $workflow->refresh();

        $this->assertNull($workflow->current_snapshot_id);
        $this->assertFalse($service->canRedo($workflow));
    }

    // --- undo() / redo() -------------------------------------------------

    public function test_undo_reverts_step_changes_to_previous_snapshot(): void
    {
        $workflow = $this->makeWorkflow();
        $step = $this->makeStep($workflow, ['order' => 0, 'action_config' => ['label' => 'v1']]);

        $service = app(WorkflowSnapshotService::class);

        // Captura el estado "v1" antes de aplicar el cambio a "v2".
        $service->capture($workflow);
        $step->update(['action_config' => ['label' => 'v2']]);

        $state = $service->undo($workflow);

        $step->refresh();
        $this->assertEquals(['label' => 'v1'], $step->action_config);
        $this->assertTrue($state['can_redo']);
    }

    public function test_undo_is_noop_when_there_is_no_history(): void
    {
        $workflow = $this->makeWorkflow();
        $this->makeStep($workflow, ['order' => 0]);

        $service = app(WorkflowSnapshotService::class);
        $state = $service->undo($workflow);

        $workflow->refresh();
        $this->assertNull($workflow->current_snapshot_id);
        $this->assertFalse($state['can_undo']);
        $this->assertFalse($state['can_redo']);
    }

    public function test_undo_then_redo_returns_to_the_state_being_undone(): void
    {
        // Caso que ya tuvo un bug real: tras un solo undo(), redo() debe
        // tener a dónde volver (el estado "en vivo" justo antes del undo).
        $workflow = $this->makeWorkflow();
        $step = $this->makeStep($workflow, ['order' => 0, 'action_config' => ['label' => 'v1']]);

        $service = app(WorkflowSnapshotService::class);

        $service->capture($workflow);
        $step->update(['action_config' => ['label' => 'v2']]);

        $undoState = $service->undo($workflow);
        $step->refresh();
        $this->assertEquals(['label' => 'v1'], $step->action_config);
        $this->assertTrue($undoState['can_redo']);

        $redoState = $service->redo($workflow);
        $step->refresh();

        $this->assertEquals(['label' => 'v2'], $step->action_config);
        $this->assertTrue($redoState['can_undo']);
        $this->assertFalse($redoState['can_redo']);

        $workflow->refresh();
        $this->assertNull($workflow->current_snapshot_id);
    }

    public function test_redo_is_noop_when_already_live(): void
    {
        $workflow = $this->makeWorkflow();
        $this->makeStep($workflow, ['order' => 0]);

        $service = app(WorkflowSnapshotService::class);
        $state = $service->redo($workflow);

        $this->assertFalse($state['can_redo']);
    }

    // --- reconcileSteps() -------------------------------------------------

    public function test_reconcile_steps_deletes_steps_not_present_in_snapshot(): void
    {
        $workflow = $this->makeWorkflow();
        $step1 = $this->makeStep($workflow, ['order' => 0]);
        $step2 = $this->makeStep($workflow, ['order' => 1]);

        $snapshotOfStep1Only = [
            [
                'id'               => $step1->id,
                'parent_step_id'   => null,
                'branch_key'       => null,
                'order'            => 0,
                'step_type'        => 'action',
                'action_type'      => 'notify_rep',
                'action_config'    => [],
                'branch_condition' => null,
                'position_x'       => null,
                'position_y'       => null,
            ],
        ];

        app(WorkflowSnapshotService::class)->reconcileSteps($workflow, $snapshotOfStep1Only);

        $this->assertNotNull(WorkflowStep::find($step1->id));
        $this->assertNull(WorkflowStep::find($step2->id));
    }

    public function test_reconcile_steps_reinserts_a_step_deleted_after_the_snapshot_was_taken(): void
    {
        $workflow = $this->makeWorkflow();
        $step = $this->makeStep($workflow, ['order' => 0, 'action_config' => ['label' => 'original']]);
        $deletedId = $step->id;

        $snapshot = [
            [
                'id'               => $deletedId,
                'parent_step_id'   => null,
                'branch_key'       => null,
                'order'            => 0,
                'step_type'        => 'action',
                'action_type'      => 'notify_rep',
                'action_config'    => ['label' => 'original'],
                'branch_condition' => null,
                'position_x'       => null,
                'position_y'       => null,
            ],
        ];

        // El paso fue borrado por una acción posterior al snapshot.
        $step->delete();
        $this->assertNull(WorkflowStep::find($deletedId));

        app(WorkflowSnapshotService::class)->reconcileSteps($workflow, $snapshot);

        $reinserted = WorkflowStep::find($deletedId);
        $this->assertNotNull($reinserted);
        $this->assertEquals($deletedId, $reinserted->id);
        $this->assertEquals(['label' => 'original'], $reinserted->action_config);
    }

    public function test_reconcile_steps_updates_existing_step_fields(): void
    {
        $workflow = $this->makeWorkflow();
        $step = $this->makeStep($workflow, ['order' => 0, 'action_config' => ['label' => 'v1']]);

        $snapshot = [
            [
                'id'               => $step->id,
                'parent_step_id'   => null,
                'branch_key'       => null,
                'order'            => 5,
                'step_type'        => 'action',
                'action_type'      => 'notify_rep',
                'action_config'    => ['label' => 'v2'],
                'branch_condition' => null,
                'position_x'       => null,
                'position_y'       => null,
            ],
        ];

        app(WorkflowSnapshotService::class)->reconcileSteps($workflow, $snapshot);

        $step->refresh();
        $this->assertEquals(5, $step->order);
        $this->assertEquals(['label' => 'v2'], $step->action_config);
    }

    /**
     * Orden topológico multi-pasada: reconcileSteps() debe poder reinsertar
     * un padre y su hijo aunque el array de entrada los traiga en orden
     * inverso (hijo antes que el padre), sin violar la FK parent_step_id.
     */
    public function test_reconcile_steps_reinserts_parent_and_child_regardless_of_input_order(): void
    {
        $workflow = $this->makeWorkflow();
        $parent = $this->makeStep($workflow, ['order' => 0, 'step_type' => 'condition', 'action_type' => null]);
        $child = $this->makeStep($workflow, ['order' => 0, 'parent_step_id' => $parent->id, 'branch_key' => 'yes']);

        $parentId = $parent->id;
        $childId = $child->id;

        // El array llega con el hijo primero -- el algoritmo debe resolverlo
        // igual, insertando primero al padre.
        $snapshot = [
            [
                'id'               => $childId,
                'parent_step_id'   => $parentId,
                'branch_key'       => 'yes',
                'order'            => 0,
                'step_type'        => 'action',
                'action_type'      => 'notify_rep',
                'action_config'    => [],
                'branch_condition' => null,
                'position_x'       => null,
                'position_y'       => null,
            ],
            [
                'id'               => $parentId,
                'parent_step_id'   => null,
                'branch_key'       => null,
                'order'            => 0,
                'step_type'        => 'condition',
                'action_type'      => null,
                'action_config'    => null,
                'branch_condition' => null,
                'position_x'       => null,
                'position_y'       => null,
            ],
        ];

        // Ambos borrados, simulando que una acción posterior eliminó la rama
        // completa.
        WorkflowStep::whereIn('id', [$parentId, $childId])->delete();

        app(WorkflowSnapshotService::class)->reconcileSteps($workflow, $snapshot);

        $reinsertedParent = WorkflowStep::find($parentId);
        $reinsertedChild = WorkflowStep::find($childId);

        $this->assertNotNull($reinsertedParent);
        $this->assertNotNull($reinsertedChild);
        $this->assertEquals($parentId, $reinsertedChild->parent_step_id);
    }
}
