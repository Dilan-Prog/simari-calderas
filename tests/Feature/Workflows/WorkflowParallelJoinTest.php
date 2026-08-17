<?php

namespace Tests\Feature\Workflows;

use App\Models\Customer;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentBranch;
use App\Models\WorkflowStep;
use App\Services\WorkflowEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug real detectado y corregido: un step 'parallel' con Join, donde UNA
 * hoja de rama termina en callejón sin salida (sin joins_into_step_id
 * explícito -- ej. el "No" de una condición interna sin acción después),
 * completaba la inscripción entera de golpe si esa rama resultaba ser la
 * última en terminar, saltándose el Join y todo lo que sigue. El fix:
 * advanceBranchOrComplete() ahora busca un 'join' hermano del 'parallel'
 * (mismo parent_step_id) y converge ahí automáticamente en vez de asumir
 * "rama suelta" -- ya no depende de que CADA hoja de CADA rama declare
 * joins_into_step_id a mano.
 */
class WorkflowParallelJoinTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'first_name'    => 'Juan',
            'last_name'     => 'Perez',
            'email'         => 'juan.perez.' . uniqid() . '@example.com',
            'phone'         => '5555555555',
            'document_type' => 'RFC',
            'status'        => 'active',
            'source'        => 'test',
            'company'       => 'ACME',
        ]);
    }

    private function makeWorkflow(): Workflow
    {
        return Workflow::create([
            'name'                 => 'Workflow de prueba paralelo/join',
            'type'                 => 'customer',
            'enrollment_trigger'   => ['type' => 'manual'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);
    }

    public function test_dead_end_branch_without_explicit_join_still_converges_and_continues_past_join(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();

        $parallel = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'parallel',
            'action_config'  => ['branches' => [
                ['branch_key' => 'branch_1', 'label' => 'Rama con join explícito'],
                ['branch_key' => 'branch_2', 'label' => 'Rama en callejón sin salida'],
            ]],
        ]);

        $join = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $parallel->id,
            'branch_key'     => null,
            'order'          => 0,
            'step_type'      => 'join',
        ]);

        // Sibling del join: el step que debe ejecutarse DESPUÉS del join.
        // Si el bug reapareciera, este create_task nunca correría.
        $afterJoin = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $parallel->id,
            'branch_key'     => null,
            'order'          => 1,
            'step_type'      => 'action',
            'action_type'    => 'create_task',
            'action_config'  => ['title' => 'Después del join', 'description' => null, 'due_at' => null, 'assigned_to' => null],
        ]);

        // Rama 1: una acción con joins_into_step_id explícito -- camino ya
        // soportado antes del fix, debe seguir funcionando igual.
        $branch1Leaf = WorkflowStep::create([
            'workflow_id'        => $workflow->id,
            'parent_step_id'     => $parallel->id,
            'branch_key'         => 'branch_1',
            'order'              => 0,
            'step_type'          => 'action',
            'action_type'        => 'create_task',
            'action_config'      => ['title' => 'Rama 1', 'description' => null, 'due_at' => null, 'assigned_to' => null],
            'joins_into_step_id' => $join->id,
        ]);

        // Rama 2: callejón sin salida A PROPÓSITO -- sin joins_into_step_id,
        // sin next sibling, sin loop envolvente. Esta es la hoja que
        // reproducía el bug.
        $branch2Leaf = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $parallel->id,
            'branch_key'     => 'branch_2',
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'create_task',
            'action_config'  => ['title' => 'Rama 2 (callejón sin salida)', 'description' => null, 'due_at' => null, 'assigned_to' => null],
        ]);

        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);

        // El bug hacía que esto quedara en 'completed' saltándose el join
        // y afterJoin -- con el fix, debe llegar hasta el final del flujo
        // principal (que sí termina en 'completed', pero DESPUÉS de correr
        // afterJoin).
        $this->assertSame('completed', $enrollment->fresh()->status);

        // La prueba real de que no se saltó el join: el step "después del
        // join" sí se ejecutó -- se ve porque su Task existe.
        $this->assertTrue(
            Task::where('title', 'Después del join')->exists(),
            'El step después del Join no se ejecutó -- la inscripción se completó de golpe saltándose el Join (bug reproducido).'
        );

        $this->assertTrue(Task::where('title', 'Rama 1')->exists());
        $this->assertTrue(Task::where('title', 'Rama 2 (callejón sin salida)')->exists());

        // Las filas de WorkflowEnrollmentBranch son estado transitorio --
        // deben quedar limpiadas una vez que el join completó.
        $this->assertSame(0, WorkflowEnrollmentBranch::where('enrollment_id', $enrollment->id)->count());
    }

    public function test_dead_end_branch_without_any_join_still_completes_enrollment_normally(): void
    {
        // Caso genuino de "fire and forget": un Paralelo SIN ningún Join
        // asociado. El fix no debe cambiar este comportamiento -- callejones
        // sin salida en ramas de un Paralelo sin Join deben seguir
        // completando la inscripción normalmente cuando termina la última.
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();

        $parallel = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'parallel',
            'action_config'  => ['branches' => [
                ['branch_key' => 'branch_1', 'label' => 'Rama única'],
            ]],
        ]);

        WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $parallel->id,
            'branch_key'     => 'branch_1',
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'create_task',
            'action_config'  => ['title' => 'Rama única sin join', 'description' => null, 'due_at' => null, 'assigned_to' => null],
        ]);

        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);

        $this->assertSame('completed', $enrollment->fresh()->status);
        $this->assertTrue(Task::where('title', 'Rama única sin join')->exists());
        $this->assertSame(0, WorkflowEnrollmentBranch::where('enrollment_id', $enrollment->id)->count());
    }
}
