<?php

namespace Tests\Feature\Workflows;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Services\WorkflowTriggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de WorkflowTriggerService::handleConversationEvent()/handleConversationStale()
 * (Fase 15) -- mismo patrón que WorkflowEngineTest para handleDealEvent, pero
 * la inscripción se ejercita a través del observer real
 * (WhatsappConversationObserver, registrado en AppServiceProvider::boot())
 * en vez de llamar al servicio directamente, para probar el cableado
 * create/update de punta a punta tal como ocurre en producción.
 */
class WorkflowTriggerServiceConversationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): WhatsappAccount
    {
        return WhatsappAccount::create([
            'name'         => 'Cuenta de prueba',
            'phone_number' => '5215500000000',
            'is_active'    => true,
        ]);
    }

    private function makeConversationWorkflow(array $trigger, array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name'                 => 'Workflow de conversación',
            'type'                 => 'whatsapp_conversation',
            'enrollment_trigger'   => $trigger,
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ], $overrides));
    }

    public function test_conversation_created_event_enrolls_matching_workflow(): void
    {
        $workflow = $this->makeConversationWorkflow(['event' => 'created']);
        $account = $this->makeAccount();

        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215511111111',
            'status'        => 'open',
            'started_at'    => now(),
        ]);

        $this->assertTrue(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_type', $conversation->getMorphClass())
                ->where('enrollable_id', $conversation->getKey())
                ->exists()
        );
    }

    public function test_conversation_created_event_does_not_enroll_workflow_of_other_type(): void
    {
        $dealWorkflow = Workflow::create([
            'name'                 => 'Workflow de negocio',
            'type'                 => 'deal',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);
        $account = $this->makeAccount();

        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215522222222',
            'status'        => 'open',
            'started_at'    => now(),
        ]);

        $this->assertFalse(
            WorkflowEnrollment::where('workflow_id', $dealWorkflow->id)
                ->where('enrollable_type', $conversation->getMorphClass())
                ->where('enrollable_id', $conversation->getKey())
                ->exists()
        );
    }

    public function test_conversation_updated_event_with_field_trigger_enrolls_only_when_field_changed(): void
    {
        $workflow = $this->makeConversationWorkflow([
            'event' => 'updated',
            'field' => 'pipeline_stage_id',
        ]);
        $account = $this->makeAccount();

        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215533333333',
            'status'        => 'open',
            'started_at'    => now(),
        ]);

        // El 'created' de arriba ya pudo intentar inscribir (no coincide,
        // trigger es 'updated'); confirma que aún no hay inscripción.
        $this->assertFalse(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_id', $conversation->getKey())
                ->exists()
        );

        // Actualizar un campo que NO es 'pipeline_stage_id' no debe inscribir.
        $conversation->update(['status' => 'closed']);
        $this->assertFalse(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_id', $conversation->getKey())
                ->exists()
        );

        // Actualizar 'pipeline_stage_id' sí debe inscribir.
        $pipeline = Pipeline::create(['name' => 'Embudo WhatsApp', 'channel' => 'whatsapp', 'is_default' => false, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id'     => $pipeline->id,
            'name'            => 'Nuevo',
            'slug'            => 'nuevo',
            'order'           => 1,
            'probability'     => 10,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);
        $conversation->update(['pipeline_stage_id' => $stage->id]);
        $this->assertTrue(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_id', $conversation->getKey())
                ->exists()
        );
    }

    public function test_handle_conversation_stale_enrolls_conversations_past_threshold(): void
    {
        $workflow = $this->makeConversationWorkflow(['event' => 'stale', 'hours' => 24]);
        $account = $this->makeAccount();

        $staleConversation = WhatsappConversation::create([
            'account_id'      => $account->id,
            'contact_phone'   => '5215544444444',
            'status'          => 'open',
            'started_at'      => now()->subDays(3),
            'last_message_at' => now()->subHours(48),
        ]);

        $freshConversation = WhatsappConversation::create([
            'account_id'      => $account->id,
            'contact_phone'   => '5215555555555',
            'status'          => 'open',
            'started_at'      => now()->subHour(),
            'last_message_at' => now()->subMinutes(10),
        ]);

        app(WorkflowTriggerService::class)->handleConversationStale();

        $this->assertTrue(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_id', $staleConversation->getKey())
                ->exists()
        );

        $this->assertFalse(
            WorkflowEnrollment::where('workflow_id', $workflow->id)
                ->where('enrollable_id', $freshConversation->getKey())
                ->exists()
        );
    }
}
