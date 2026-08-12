<?php

namespace Tests\Feature\Workflows;

use App\Jobs\SendMarketingEmailJob;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Services\WorkflowActionExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests de caracterización de WorkflowActionExecutor::execute() para las 6
 * action_type soportadas por supportedActions() (incluyendo el "desconocido"
 * ya cubierto en WorkflowEngineTest), cubriendo tanto el caso 'success' como
 * los casos 'skipped'/'failed' de cada una donde el código real los
 * contempla.
 *
 * Se usa Customer como enrollable "simple" (sin dependencias) y Deal solo en
 * los tests que ejercitan acciones que exigen explícitamente un Deal
 * (update_property, move_deal_stage) — igual que hace WorkflowEngineTest.
 */
class WorkflowActionExecutorTest extends TestCase
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

    private function makeDeal(array $overrides = []): Deal
    {
        $pipeline = Pipeline::create(['name' => 'Pipeline de prueba', 'is_default' => true, 'is_active' => true]);

        $stage = PipelineStage::create([
            'pipeline_id'     => $pipeline->id,
            'name'            => 'Contactado',
            'slug'            => 'contactado',
            'order'           => 1,
            'probability'     => 10,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        return Deal::create(array_merge([
            'pipeline_id'       => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name'              => 'Deal de prueba',
            'amount'            => 1000,
            'currency'          => 'MXN',
            'status'            => 'open',
        ], $overrides));
    }

    private function makeStep(Workflow $workflow, string $actionType, array $config = []): WorkflowStep
    {
        return WorkflowStep::create([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => $actionType,
            'action_config'  => $config,
        ]);
    }

    private function makeEnrollment(Workflow $workflow, $enrollable, WorkflowStep $step): WorkflowEnrollment
    {
        return WorkflowEnrollment::create([
            'workflow_id'     => $workflow->id,
            'enrollable_type' => $enrollable->getMorphClass(),
            'enrollable_id'   => $enrollable->getKey(),
            'current_step_id' => $step->id,
            'status'          => 'active',
            'enrolled_at'     => now(),
        ]);
    }

    private function logFor(WorkflowEnrollment $enrollment, WorkflowStep $step): ?WorkflowEnrollmentLog
    {
        return WorkflowEnrollmentLog::where('enrollment_id', $enrollment->id)
            ->where('step_id', $step->id)
            ->first();
    }

    // --- create_task ---------------------------------------------------

    public function test_create_task_success_creates_task_associated_to_enrollable(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'create_task', [
            'title'       => 'Dar seguimiento',
            'description' => 'Llamar al cliente',
            'due_at'      => null,
            'assigned_to' => null,
        ]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $task = Task::where('created_by_workflow_id', $workflow->id)->first();
        $this->assertNotNull($task);
        $this->assertEquals('Dar seguimiento', $task->title);
        $this->assertEquals($customer->getMorphClass(), $task->taskable_type);
        $this->assertEquals($customer->getKey(), $task->taskable_id);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('create_task', $log->action_taken);
        $this->assertEquals('success', $log->result);
    }

    // --- notify_rep (siempre no-op) -------------------------------------

    public function test_notify_rep_is_always_skipped(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'notify_rep');
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('notify_rep', $log->action_taken);
        $this->assertEquals('skipped', $log->result);
    }

    // --- update_property -------------------------------------------------

    public function test_update_property_success_updates_deal_field(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal(['notes' => 'original']);
        $step = $this->makeStep($workflow, 'update_property', ['field' => 'notes', 'value' => 'actualizado por workflow']);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $deal->refresh();
        $this->assertEquals('actualizado por workflow', $deal->notes);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_update_property_skipped_when_enrollable_is_not_a_deal(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'update_property', ['field' => 'notes', 'value' => 'x']);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);
        $this->assertStringContainsString('no es un Deal', $log->message);
    }

    public function test_update_property_skipped_when_field_missing(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal();
        $step = $this->makeStep($workflow, 'update_property', ['field' => null, 'value' => 'x']);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);
    }

    // --- move_deal_stage ---------------------------------------------------

    public function test_move_deal_stage_success_moves_deal_to_target_stage(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal();
        $originalStageId = $deal->pipeline_stage_id;

        $targetStage = PipelineStage::create([
            'pipeline_id'     => $deal->pipeline_id,
            'name'            => 'Propuesta enviada',
            'slug'            => 'propuesta-enviada',
            'order'           => 2,
            'probability'     => 50,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        $step = $this->makeStep($workflow, 'move_deal_stage', ['stage_id' => $targetStage->id]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $deal->refresh();
        $this->assertEquals($targetStage->id, $deal->pipeline_stage_id);
        $this->assertNotEquals($originalStageId, $deal->pipeline_stage_id);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_move_deal_stage_skipped_when_enrollable_is_not_a_deal(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'move_deal_stage', ['stage_id' => 999]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);
    }

    public function test_move_deal_stage_failed_when_stage_not_found(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal();
        $step = $this->makeStep($workflow, 'move_deal_stage', ['stage_id' => 999999]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);
        $this->assertStringContainsString('No se encontró la etapa', $log->message);
    }

    public function test_move_deal_stage_failed_when_required_fields_missing(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal(['expected_close_date' => null]);

        $targetStage = PipelineStage::create([
            'pipeline_id'     => $deal->pipeline_id,
            'name'            => 'Cierre',
            'slug'            => 'cierre',
            'order'           => 3,
            'probability'     => 90,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => ['expected_close_date'],
        ]);

        $step = $this->makeStep($workflow, 'move_deal_stage', ['stage_id' => $targetStage->id]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $deal->refresh();
        $this->assertNotEquals($targetStage->id, $deal->pipeline_stage_id);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);
    }

    // --- enroll_in_workflow -----------------------------------------------

    public function test_enroll_in_workflow_success_creates_enrollment_in_target_workflow(): void
    {
        $workflow = $this->makeWorkflow();
        $targetWorkflow = $this->makeWorkflow(['name' => 'Workflow destino']);
        $customer = $this->makeCustomer();

        $step = $this->makeStep($workflow, 'enroll_in_workflow', ['workflow_id' => $targetWorkflow->id]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $this->assertTrue(
            WorkflowEnrollment::where('workflow_id', $targetWorkflow->id)
                ->where('enrollable_type', $customer->getMorphClass())
                ->where('enrollable_id', $customer->getKey())
                ->exists()
        );

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_enroll_in_workflow_failed_when_target_workflow_not_found(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'enroll_in_workflow', ['workflow_id' => 999999]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);
    }

    // --- send_email ---------------------------------------------------------

    public function test_send_email_success_dispatches_job_and_creates_email_send(): void
    {
        Queue::fake();

        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $template = EmailTemplate::create([
            'name'      => 'Bienvenida',
            'subject'   => 'Hola',
            'html_body' => '<p>Hola</p>',
            'type'      => 'workflow',
        ]);

        $step = $this->makeStep($workflow, 'send_email', ['template_id' => $template->id]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $send = EmailSend::where('workflow_step_id', $step->id)->first();
        $this->assertNotNull($send);
        $this->assertEquals($customer->id, $send->customer_id);

        Queue::assertPushed(SendMarketingEmailJob::class, function ($job) use ($send) {
            return $job->send->id === $send->id;
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_send_email_failed_when_template_not_found(): void
    {
        Queue::fake();

        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'send_email', ['template_id' => 999999]);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);

        Queue::assertNothingPushed();
    }

    public function test_send_email_skipped_when_deal_has_no_customer(): void
    {
        Queue::fake();

        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal(['customer_id' => null]);
        $template = EmailTemplate::create([
            'name'      => 'Bienvenida',
            'subject'   => 'Hola',
            'html_body' => '<p>Hola</p>',
            'type'      => 'workflow',
        ]);
        $step = $this->makeStep($workflow, 'send_email', ['template_id' => $template->id]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);

        Queue::assertNothingPushed();
    }

    public function test_send_email_skipped_when_enrollable_has_no_associated_customer(): void
    {
        Queue::fake();

        // Un Workflow no es ni Deal ni Customer -- ejercita la rama final del
        // método que cubre cualquier otro tipo de enrollable.
        $workflow = $this->makeWorkflow();
        $otherWorkflow = $this->makeWorkflow(['name' => 'No es Deal ni Customer']);
        $template = EmailTemplate::create([
            'name'      => 'Bienvenida',
            'subject'   => 'Hola',
            'html_body' => '<p>Hola</p>',
            'type'      => 'workflow',
        ]);
        $step = $this->makeStep($workflow, 'send_email', ['template_id' => $template->id]);
        $enrollment = $this->makeEnrollment($workflow, $otherWorkflow, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);

        Queue::assertNothingPushed();
    }
}
