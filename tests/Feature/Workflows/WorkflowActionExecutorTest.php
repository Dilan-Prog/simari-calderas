<?php

namespace Tests\Feature\Workflows;

use App\Jobs\SendMarketingEmailJob;
use App\Models\Credential;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowEnrollmentLog;
use App\Models\WorkflowStep;
use App\Services\WorkflowActionExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    private function makeQuote(array $overrides = []): Quote
    {
        $user = User::factory()->create();

        return Quote::create(array_merge([
            'quote_number'       => 'COT-' . uniqid(),
            'created_by_user_id' => $user->id,
            'status'             => 'draft',
            'guest_name'         => 'Cliente de prueba',
            'currency'           => 'MXN',
            'tax_rate'           => 16.00,
            'exchange_rate'      => 1.0000,
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

    /**
     * Fase 17: create_task resuelve tokens {{ }} en title/description antes
     * de guardar la tarea -- prueba tanto un campo plano ('first_name') como
     * uno con dot-notation vía relación ('customer.company' sobre un Deal).
     */
    public function test_create_task_resolves_tokens_in_title_and_description(): void
    {
        $workflow = $this->makeWorkflow(['type' => 'deal']);
        $customer = $this->makeCustomer(['first_name' => 'Ana', 'company' => 'Industrias ACME']);
        $deal = $this->makeDeal(['customer_id' => $customer->id, 'name' => 'Caldera 500L']);
        $step = $this->makeStep($workflow, 'create_task', [
            'title'       => 'Seguimiento a {{ customer.first_name }}',
            'description' => 'Negocio: {{ name }} / Empresa: {{ customer.company }}',
            'due_at'      => null,
            'assigned_to' => null,
        ]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $task = Task::where('created_by_workflow_id', $workflow->id)->first();
        $this->assertEquals('Seguimiento a Ana', $task->title);
        $this->assertEquals('Negocio: Caldera 500L / Empresa: Industrias ACME', $task->description);
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

    /**
     * Fase 17: update_property se de-hardcodeó de `instanceof Deal` a
     * genérico vía isFillable() -- un Customer con un campo realmente
     * fillable ('notes' también es fillable en Customer) ahora SÍ se
     * actualiza, a diferencia del comportamiento previo a la generalización
     * (que rechazaba cualquier enrollable que no fuera Deal).
     */
    public function test_update_property_success_updates_any_module_when_field_is_fillable(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer(['company' => 'ACME']);
        $step = $this->makeStep($workflow, 'update_property', ['field' => 'company', 'value' => 'Otra Empresa']);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $customer->refresh();
        $this->assertEquals('Otra Empresa', $customer->company);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    /**
     * Reemplaza al viejo test "skipped_when_enrollable_is_not_a_deal": el
     * chequeo de tipo fijo se quitó, lo que queda como guarda real es
     * isFillable() -- un campo que no está en $fillable del modelo real se
     * omite en vez de actualizarse en silencio.
     */
    public function test_update_property_skipped_when_field_is_not_fillable(): void
    {
        $workflow = $this->makeWorkflow();
        $customer = $this->makeCustomer();
        $step = $this->makeStep($workflow, 'update_property', ['field' => 'id', 'value' => '999']);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);
        $this->assertStringContainsString('no es editable', $log->message);
    }

    /**
     * update_property también resuelve tokens {{ }} en `value` (Fase 17)
     * antes de guardar -- confirma que WorkflowTokenResolver se aplica
     * dentro de la acción, no solo se prueba de forma aislada.
     */
    public function test_update_property_resolves_tokens_in_value(): void
    {
        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal(['name' => 'Caldera industrial']);
        $step = $this->makeStep($workflow, 'update_property', ['field' => 'notes', 'value' => 'Seguimiento a {{ name }}']);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $deal->refresh();
        $this->assertEquals('Seguimiento a Caldera industrial', $deal->notes);
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
        // Fase 17: send_email se generalizó a leer `customer_relation` desde
        // AutomatableModuleRegistry en vez del if/elseif fijo a Deal -- el
        // mensaje ya no es específico de Deal, pero el resultado (skip) es
        // idéntico al comportamiento previo.
        $this->assertStringContainsString('no tiene un Customer asociado', $log->message);

        Queue::assertNothingPushed();
    }

    /**
     * Fase 17: prueba el camino realmente genérico de resolveEmailRecipient()
     * -- un enrollable que NO es Deal ni Customer (WhatsappConversation) pero
     * cuyo `type` en el registro declara `customer_relation => 'customer'`
     * resuelve el destinatario igual que Deal, sin ningún código dedicado a
     * WhatsappConversation en sendEmail().
     */
    public function test_send_email_success_via_generic_customer_relation_from_registry(): void
    {
        Queue::fake();

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $customer = $this->makeCustomer();
        $account = $this->makeWhatsappAccount();
        $conversation = $this->makeWhatsappConversation($account, ['customer_id' => $customer->id]);
        $template = EmailTemplate::create([
            'name'      => 'Bienvenida',
            'subject'   => 'Hola',
            'html_body' => '<p>Hola</p>',
            'type'      => 'workflow',
        ]);

        $step = $this->makeStep($workflow, 'send_email', ['template_id' => $template->id]);
        $enrollment = $this->makeEnrollment($workflow, $conversation, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $send = EmailSend::where('workflow_step_id', $step->id)->first();
        $this->assertNotNull($send);
        $this->assertEquals($customer->id, $send->customer_id);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);

        Queue::assertPushed(SendMarketingEmailJob::class);
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

    // --- send_whatsapp_message (Fase 15) --------------------------------
    //
    // Nunca se llama a la API real de Meta -- Http::fake() en todos los
    // casos, mismo patrón que tests/Feature/Whatsapp/WhatsappServiceTest.php.

    private function makeWhatsappAccount(): WhatsappAccount
    {
        return WhatsappAccount::create([
            'name'                    => 'Cuenta de prueba',
            'phone_number'            => '+52 55 1234 5678',
            'phone_number_id'         => '1234567890',
            'provider'                => 'meta_cloud_api',
            'is_active'               => true,
            'encrypted_access_token'  => WhatsappAccount::encryptAccessToken('fake-token'),
        ]);
    }

    private function makeWhatsappConversation(WhatsappAccount $account, array $overrides = []): WhatsappConversation
    {
        return WhatsappConversation::create(array_merge([
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ], $overrides));
    }

    public function test_send_whatsapp_message_success_sends_free_text_within_24h_window(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEXT']]], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $account = $this->makeWhatsappAccount();
        $conversation = $this->makeWhatsappConversation($account);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => WhatsappMessage::SENDER_CONTACT,
            'message_type'    => 'text',
            'content'         => 'Hola',
            'sent_at'         => now()->subHours(1),
        ]);

        $step = $this->makeStep($workflow, 'send_whatsapp_message', ['text' => 'Gracias por tu mensaje']);
        $enrollment = $this->makeEnrollment($workflow, $conversation->fresh(), $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request['type'] === 'text' && $request['text']['body'] === 'Gracias por tu mensaje';
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    /**
     * Fase 17: el texto libre de send_whatsapp_message también pasa por
     * WorkflowTokenResolver antes de enviarse.
     */
    public function test_send_whatsapp_message_resolves_tokens_in_free_text(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TOKEN']]], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $account = $this->makeWhatsappAccount();
        $conversation = $this->makeWhatsappConversation($account, ['contact_phone' => '5215500011122']);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => WhatsappMessage::SENDER_CONTACT,
            'message_type'    => 'text',
            'content'         => 'Hola',
            'sent_at'         => now()->subHours(1),
        ]);

        $step = $this->makeStep($workflow, 'send_whatsapp_message', ['text' => 'Hola {{ contact_phone }}, gracias']);
        $enrollment = $this->makeEnrollment($workflow, $conversation->fresh(), $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request['type'] === 'text' && $request['text']['body'] === 'Hola 5215500011122, gracias';
        });
    }

    public function test_send_whatsapp_message_falls_back_to_template_outside_24h_window(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TPL']]], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $account = $this->makeWhatsappAccount();
        // Sin mensaje entrante -> ventana de 24h cerrada.
        $conversation = $this->makeWhatsappConversation($account);

        $step = $this->makeStep($workflow, 'send_whatsapp_message', [
            'text' => 'Este texto libre no debería enviarse',
            'template_name' => 'seguimiento',
            'params' => ['Juan'],
        ]);
        $enrollment = $this->makeEnrollment($workflow, $conversation, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request['type'] === 'template' && $request['template']['name'] === 'seguimiento';
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_send_whatsapp_message_failed_when_outside_window_and_no_template_configured(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $account = $this->makeWhatsappAccount();
        $conversation = $this->makeWhatsappConversation($account);

        $step = $this->makeStep($workflow, 'send_whatsapp_message', ['text' => 'Solo texto libre']);
        $enrollment = $this->makeEnrollment($workflow, $conversation, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);

        Http::assertNothingSent();
    }

    public function test_send_whatsapp_message_skipped_when_enrollable_has_no_conversation(): void
    {
        Http::fake();

        $workflow = $this->makeWorkflow(['type' => 'whatsapp_conversation']);
        $customer = $this->makeCustomer();

        $step = $this->makeStep($workflow, 'send_whatsapp_message', ['text' => 'Hola']);
        $enrollment = $this->makeEnrollment($workflow, $customer, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('skipped', $log->result);

        Http::assertNothingSent();
    }

    public function test_send_whatsapp_message_resolves_conversation_linked_to_deal(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.DEAL']]], 200),
        ]);

        $workflow = $this->makeWorkflow();
        $deal = $this->makeDeal();
        $account = $this->makeWhatsappAccount();
        $conversation = $this->makeWhatsappConversation($account, ['deal_id' => $deal->id]);

        $step = $this->makeStep($workflow, 'send_whatsapp_message', [
            'template_name' => 'bienvenida',
            'params' => [],
        ]);
        $enrollment = $this->makeEnrollment($workflow, $deal, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conversation->id,
            'template_name'   => 'bienvenida',
        ]);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    // --- call_webhook -----------------------------------------------------
    //
    // Nunca se llama a un endpoint real -- Http::fake() en todos los casos,
    // mismo patrón que send_whatsapp_message. El enrollable es un Quote para
    // ejercitar la resolución de tokens {{ }} sobre un campo propio del
    // enrollable (quote_number), vía WorkflowTokenResolver::resolveFlatPath().

    public function test_call_webhook_success_resolves_tokens_in_body(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote(['quote_number' => 'COT-000123']);
        $step = $this->makeStep($workflow, 'call_webhook', [
            'url'     => 'https://example.com/hook',
            'method'  => 'POST',
            'headers' => [['key' => 'X-Custom', 'value' => '{{ quote_number }}']],
            'body'    => [['key' => 'folio', 'value' => '{{ quote_number }}']],
        ]);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/hook'
                && $request['folio'] === 'COT-000123'
                && $request->hasHeader('X-Custom', 'COT-000123');
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('call_webhook', $log->action_taken);
        $this->assertEquals('success', $log->result);
    }

    public function test_call_webhook_applies_header_from_webhook_credential(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();

        $credential = Credential::create([
            'name'              => 'Webhook de prueba',
            'type'              => 'webhook',
            'encrypted_payload' => Credential::storePayload([
                'header_name'  => 'Authorization',
                'header_value' => 'Bearer test-token-123',
            ]),
        ]);

        $step = $this->makeStep($workflow, 'call_webhook', [
            'url'           => 'https://example.com/hook',
            'method'        => 'POST',
            'credential_id' => $credential->id,
        ]);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-token-123');
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_call_webhook_skipped_when_url_is_empty(): void
    {
        Http::fake();

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();
        $step = $this->makeStep($workflow, 'call_webhook', ['url' => '']);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('call_webhook', $log->action_taken);
        $this->assertEquals('skipped', $log->result);

        Http::assertNothingSent();
    }

    public function test_call_webhook_failed_on_non_2xx_response(): void
    {
        Http::fake([
            '*' => Http::response('Server error', 500),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();
        $step = $this->makeStep($workflow, 'call_webhook', ['url' => 'https://example.com/hook']);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);
    }

    public function test_call_webhook_failed_on_thrown_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();
        $step = $this->makeStep($workflow, 'call_webhook', ['url' => 'https://example.com/hook']);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('failed', $log->result);
        $this->assertStringContainsString('Connection timed out', $log->message);
    }

    // --- call_webhook con webhook_id (entidad Webhook reutilizable) --------
    //
    // Cuando action_config.webhook_id apunta a un Webhook existente, la
    // llamada usa la url/method/headers/credential_id de ese registro (no
    // los de action_config), pero body siempre sigue viniendo de
    // action_config -- ver docblock de callWebhook().

    public function test_call_webhook_uses_registered_webhook_url_method_headers(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote(['quote_number' => 'COT-000456']);

        $webhook = Webhook::create([
            'name'    => 'CRM externo',
            'url'     => 'https://example.com/registered-hook',
            'method'  => 'PUT',
            'headers' => [['key' => 'X-Registered', 'value' => 'yes']],
        ]);

        $step = $this->makeStep($workflow, 'call_webhook', [
            'webhook_id' => $webhook->id,
            // Estos valores directos deben ser ignorados: los reales vienen
            // del registro Webhook de arriba.
            'url'        => 'https://example.com/should-not-be-used',
            'method'     => 'POST',
            'headers'    => [['key' => 'X-Ad-Hoc', 'value' => 'no']],
            'body'       => [['key' => 'folio', 'value' => '{{ quote_number }}']],
        ]);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/registered-hook'
                && $request->method() === 'PUT'
                && $request->hasHeader('X-Registered', 'yes')
                && !$request->hasHeader('X-Ad-Hoc')
                && $request['folio'] === 'COT-000456';
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('call_webhook', $log->action_taken);
        $this->assertEquals('success', $log->result);
    }

    public function test_call_webhook_applies_credential_from_registered_webhook(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();

        $credential = Credential::create([
            'name'              => 'Webhook de prueba (registrado)',
            'type'              => 'webhook',
            'encrypted_payload' => Credential::storePayload([
                'header_name'  => 'Authorization',
                'header_value' => 'Bearer registered-token-456',
            ]),
        ]);

        $webhook = Webhook::create([
            'name'          => 'CRM externo con credencial',
            'url'           => 'https://example.com/registered-hook-auth',
            'method'        => 'POST',
            'credential_id' => $credential->id,
        ]);

        $step = $this->makeStep($workflow, 'call_webhook', ['webhook_id' => $webhook->id]);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer registered-token-456');
        });

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('success', $log->result);
    }

    public function test_call_webhook_skipped_when_webhook_id_not_found(): void
    {
        Http::fake();

        $workflow = $this->makeWorkflow(['type' => 'quote']);
        $quote = $this->makeQuote();
        $step = $this->makeStep($workflow, 'call_webhook', ['webhook_id' => 999999]);
        $enrollment = $this->makeEnrollment($workflow, $quote, $step);

        app(WorkflowActionExecutor::class)->execute($enrollment, $step);

        $log = $this->logFor($enrollment, $step);
        $this->assertEquals('call_webhook', $log->action_taken);
        $this->assertEquals('skipped', $log->result);

        Http::assertNothingSent();
    }

    // Nota: el modo "ad hoc" (sin webhook_id, url/method/headers/body
    // directos en action_config) ya está cubierto por
    // test_call_webhook_success_resolves_tokens_in_body() y
    // test_call_webhook_applies_header_from_webhook_credential() arriba, sin
    // ningún cambio de comportamiento -- no se duplica aquí.
}
