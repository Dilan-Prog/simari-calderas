<?php

namespace Tests\Feature\Workflows;

use App\Models\Customer;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Services\WorkflowActionExecutor;
use App\Services\WorkflowEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de caracterización del motor de workflows: documentan el
 * comportamiento ACTUAL del código, ya con el fix del branching de
 * condition steps aplicado (branch_condition en el step 'condition',
 * branch_key='yes'/'no' en los hijos).
 *
 * Se usa Customer como "enrollable" en lugar de Deal: Deal depende de
 * Pipeline/PipelineStage (y de un User creador), mientras que Customer
 * no tiene dependencias adicionales más allá de sus propias columnas,
 * lo que mantiene el arranque de cada test mínimo. El motor trata al
 * enrollable de forma polimórfica (morphTo) así que cualquiera de los
 * dos sirve para ejercer processStep()/resumeWaiting().
 *
 * No existen factories para Workflow/WorkflowStep/Customer en el
 * proyecto (solo UserFactory), así que los registros se crean
 * directamente con ::create().
 */
class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Perez',
            'email'         => 'juan.perez.' . uniqid() . '@example.com',
            'phone'         => '5555555555',
            'document_type' => 'RFC',
            'status'        => 'active',
            'source'        => 'test',
            'company'       => 'ACME',
        ], $overrides));
    }

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

    public function test_linear_action_steps_run_in_order_and_complete(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();

        $step1 = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep', // acción "skipped" pero registrada, no requiere dependencias
            'action_config'  => [],
        ]);

        $step2 = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 1,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ]);

        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);

        $enrollment->refresh();

        $this->assertEquals('completed', $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertNull($enrollment->current_step_id);

        $logs = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->orderBy('id')->get();

        $this->assertCount(2, $logs);
        $this->assertEquals($step1->id, $logs[0]->step_id);
        $this->assertEquals($step2->id, $logs[1]->step_id);
        $this->assertEquals('skipped', $logs[0]->result);
        $this->assertEquals('skipped', $logs[1]->result);
    }

    public function test_wait_step_pauses_and_resume_waiting_advances_to_next_step(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();

        $waitStep = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'wait',
            'action_type'    => null,
            'action_config'  => ['unit' => 'hours', 'amount' => 3],
        ]);

        $afterWaitStep = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 1,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ]);

        $before = now();
        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);
        $enrollment->refresh();

        $this->assertEquals('waiting', $enrollment->status);
        $this->assertEquals($waitStep->id, $enrollment->current_step_id);
        $this->assertNotNull($enrollment->resume_at);
        // amount=3, unit=hours => resume_at ~ now()+3h
        $this->assertTrue($enrollment->resume_at->between($before->copy()->addHours(3)->subSeconds(5), $before->copy()->addHours(3)->addSeconds(5)));

        $waitLog = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $waitStep->id)->first();
        $this->assertNotNull($waitLog);
        $this->assertEquals('wait', $waitLog->action_taken);
        $this->assertEquals('scheduled', $waitLog->result);

        app(WorkflowEngineService::class)->resumeWaiting($enrollment);
        $enrollment->refresh();

        $this->assertEquals('completed', $enrollment->status);
        $this->assertNull($enrollment->resume_at);
        $this->assertNull($enrollment->current_step_id);

        $afterLog = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $afterWaitStep->id)->first();
        $this->assertNotNull($afterLog);
        $this->assertEquals('skipped', $afterLog->result);
    }

    /**
     * Comportamiento CORRECTO del motor tras el fix de branching: el step
     * 'condition' evalúa su propio branch_condition (no el de los hijos) y
     * navega al hijo cuyo branch_key coincide con el resultado ('yes'/'no').
     * Caso: la condición evalúa a TRUE -> debe tomar la rama 'yes'.
     */
    public function test_condition_step_takes_yes_branch_when_condition_is_true(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer(['status' => 'active']);

        $conditionStep = WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => null,
            'order'            => 0,
            'step_type'        => 'condition',
            'action_type'      => null,
            'action_config'    => [],
            'branch_condition' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        ]);

        $yesChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $conditionStep->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => ['message' => 'yes-branch'],
            'branch_key'     => 'yes',
        ]);

        $noChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $conditionStep->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => ['message' => 'no-branch'],
            'branch_key'     => 'no',
        ]);

        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);
        $enrollment->refresh();

        $this->assertEquals('completed', $enrollment->status);

        $conditionLog = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $conditionStep->id)->first();
        $this->assertNotNull($conditionLog);
        $this->assertEquals('condition', $conditionLog->action_taken);
        $this->assertEquals('yes', $conditionLog->result);

        $logs = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->get();
        $this->assertCount(2, $logs);
        $this->assertTrue(
            WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $yesChild->id)->exists()
        );
        $this->assertFalse(
            WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $noChild->id)->exists()
        );
    }

    /**
     * Caso inverso: la condición evalúa a FALSE -> debe tomar la rama 'no'.
     * Confirma que el branching es realmente bidireccional (no siempre gana
     * el mismo hijo).
     */
    public function test_condition_step_takes_no_branch_when_condition_is_false(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer(['status' => 'inactive']);

        $conditionStep = WorkflowStep::create([
            'workflow_id'      => $workflow->id,
            'parent_step_id'   => null,
            'order'            => 0,
            'step_type'        => 'condition',
            'action_type'      => null,
            'action_config'    => [],
            'branch_condition' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        ]);

        $yesChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $conditionStep->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => ['message' => 'yes-branch'],
            'branch_key'     => 'yes',
        ]);

        $noChild = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => $conditionStep->id,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => ['message' => 'no-branch'],
            'branch_key'     => 'no',
        ]);

        $enrollment = app(WorkflowEngineService::class)->enroll($workflow, $customer);
        $enrollment->refresh();

        $this->assertEquals('completed', $enrollment->status);

        $conditionLog = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $conditionStep->id)->first();
        $this->assertNotNull($conditionLog);
        $this->assertEquals('condition', $conditionLog->action_taken);
        $this->assertEquals('no', $conditionLog->result);

        $logs = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->get();
        $this->assertCount(2, $logs);
        $this->assertTrue(
            WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $noChild->id)->exists()
        );
        $this->assertFalse(
            WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $yesChild->id)->exists()
        );
    }

    public function test_action_executor_logs_skipped_result_for_unknown_action_type(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();

        $step = WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'esto_no_existe',
            'action_config'  => [],
        ]);

        $enrollment = WorkflowEnrollment::create([
            'workflow_id'     => $workflow->id,
            'enrollable_type' => $customer->getMorphClass(),
            'enrollable_id'   => $customer->getKey(),
            'current_step_id' => $step->id,
            'status'          => 'active',
            'enrolled_at'     => now(),
        ]);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)->where('step_id', $step->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals('skipped', $log->result);
        $this->assertEquals('esto_no_existe', $log->action_taken);
    }

    public function test_reenrollment_not_allowed_returns_existing_active_enrollment(): void
    {
        $workflow = $this->makeWorkflow(['reenrollment_allowed' => false]);
        $customer = $this->makeCustomer();

        // Un único step 'wait' para que la primera inscripción quede en
        // estado 'waiting' (no 'completed') y así sea elegible como
        // "existing" activo/waiting en el segundo enroll().
        WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'wait',
            'action_type'    => null,
            'action_config'  => ['unit' => 'minutes', 'amount' => 10],
        ]);

        $engine = app(WorkflowEngineService::class);

        $first = $engine->enroll($workflow, $customer);
        $this->assertEquals('waiting', $first->status);

        $second = $engine->enroll($workflow, $customer);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(
            1,
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_type', $customer->getMorphClass())
                ->where('enrollable_id', $customer->getKey())
                ->count()
        );
    }
}
