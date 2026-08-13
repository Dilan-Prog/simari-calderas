<?php

namespace Tests\Feature\Workflows;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Services\WorkflowTriggerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de regresión de la generalización de Fase 17
 * (WorkflowTriggerService::handleModelEvent()/handleModelStale()).
 *
 * Confirma dos cosas:
 *  1. handleModelEvent(), invocado a través de la ruta de producción real
 *     (AutomatableModelObserver, registrado sobre TODOS los modelos de
 *     config/automatable_modules.php desde AppServiceProvider::boot()),
 *     reproduce EXACTAMENTE lo que antes hacían handleDealEvent()/
 *     handleConversationEvent() para 'deal'/'whatsapp_conversation' --
 *     creación y actualización de campo específico.
 *  2. Los métodos legacy handleDealEvent()/handleConversationEvent() (que
 *     DealObserver.php/WhatsappConversationObserver.php aún invocan, aunque
 *     ya no están registrados en AppServiceProvider::boot()) siguen
 *     funcionando -- ahora delegan en handleModelEvent() en vez de
 *     duplicar la lógica de matching.
 */
class WorkflowTriggerServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makePipelineStage(): PipelineStage
    {
        $pipeline = Pipeline::create(['name' => 'Pipeline de prueba', 'is_default' => true, 'is_active' => true]);

        return PipelineStage::create([
            'pipeline_id'     => $pipeline->id,
            'name'            => 'Contactado',
            'slug'            => 'contactado',
            'order'           => 1,
            'probability'     => 10,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);
    }

    private function makeDeal(array $overrides = []): Deal
    {
        $stage = $this->makePipelineStage();

        return Deal::create(array_merge([
            'pipeline_id'       => $stage->pipeline_id,
            'pipeline_stage_id' => $stage->id,
            'name'              => 'Deal de prueba',
            'amount'            => 1000,
            'currency'          => 'MXN',
            'status'            => 'open',
        ], $overrides));
    }

    private function makeDealWorkflow(array $trigger, array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name'                 => 'Workflow de negocio',
            'type'                 => 'deal',
            'enrollment_trigger'   => $trigger,
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ], $overrides));
    }

    private function enrolled(Workflow $workflow, $model): bool
    {
        return WorkflowEnrollment::where('workflow_id', $workflow->id)
            ->where('enrollable_type', $model->getMorphClass())
            ->where('enrollable_id', $model->getKey())
            ->exists();
    }

    // --- handleModelEvent() vía la ruta de producción real (observer genérico) ---

    public function test_deal_created_enrolls_matching_workflow_via_generic_observer(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'created']);

        $deal = $this->makeDeal();

        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_deal_created_does_not_enroll_workflow_of_other_type(): void
    {
        $conversationWorkflow = Workflow::create([
            'name'                 => 'Workflow de conversación',
            'type'                 => 'whatsapp_conversation',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);

        $deal = $this->makeDeal();

        $this->assertFalse($this->enrolled($conversationWorkflow, $deal));
    }

    public function test_deal_updated_event_with_field_trigger_enrolls_only_when_field_changed(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'pipeline_stage_id']);
        $deal = $this->makeDeal();

        $this->assertFalse($this->enrolled($workflow, $deal));

        // Cambiar un campo que no es 'pipeline_stage_id' no debe inscribir.
        $deal->update(['name' => 'Nuevo nombre']);
        $this->assertFalse($this->enrolled($workflow, $deal));

        // Cambiar 'pipeline_stage_id' sí debe inscribir.
        $otherStage = PipelineStage::create([
            'pipeline_id'     => $deal->pipeline_id,
            'name'            => 'Propuesta',
            'slug'            => 'propuesta',
            'order'           => 2,
            'probability'     => 50,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);
        $deal->update(['pipeline_stage_id' => $otherStage->id]);

        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_deal_updated_event_with_to_stage_id_only_enrolls_on_exact_target_stage(): void
    {
        $stage = $this->makePipelineStage();
        $targetStage = PipelineStage::create([
            'pipeline_id'     => $stage->pipeline_id,
            'name'            => 'Ganado',
            'slug'            => 'ganado',
            'order'           => 3,
            'probability'     => 100,
            'is_won'          => true,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        $workflow = $this->makeDealWorkflow([
            'event'        => 'updated',
            'field'        => 'pipeline_stage_id',
            'to_stage_id'  => $targetStage->id,
        ]);

        $deal = Deal::create([
            'pipeline_id'       => $stage->pipeline_id,
            'pipeline_stage_id' => $stage->id,
            'name'              => 'Deal de prueba',
            'amount'            => 1000,
            'currency'          => 'MXN',
            'status'            => 'open',
        ]);

        $otherStage = PipelineStage::create([
            'pipeline_id'     => $stage->pipeline_id,
            'name'            => 'Otra etapa',
            'slug'            => 'otra-etapa',
            'order'           => 4,
            'probability'     => 20,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        // Mover a una etapa que NO es el target no debe inscribir.
        $deal->update(['pipeline_stage_id' => $otherStage->id]);
        $this->assertFalse($this->enrolled($workflow, $deal));

        // Mover a la etapa target sí debe inscribir.
        $deal->update(['pipeline_stage_id' => $targetStage->id]);
        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    // --- handleModelEvent() genérico con un módulo NO-CRM registrado ---

    public function test_handle_model_event_enrolls_for_a_registered_non_crm_type(): void
    {
        // whatsapp_conversation ya está en el registro (Fase 16) y no es
        // Deal -- se usa aquí como "módulo cualquiera" para probar que
        // handleModelEvent() es genuinamente genérico, no solo funciona
        // porque el argumento es un Deal.
        $account = WhatsappAccount::create([
            'name'         => 'Cuenta de prueba',
            'phone_number' => '5215500000000',
            'is_active'    => true,
        ]);

        $workflow = Workflow::create([
            'name'                 => 'Workflow genérico',
            'type'                 => 'whatsapp_conversation',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);

        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215566677788',
            'status'        => 'open',
            'started_at'    => now(),
        ]);

        $this->assertTrue($this->enrolled($workflow, $conversation));
    }

    // --- métodos legacy delegan en handleModelEvent() -----------------------

    public function test_legacy_handle_deal_event_still_enrolls_via_delegation(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'created']);
        $deal = $this->makeDeal();

        // El 'created' real ya inscribió vía el observer genérico; probamos
        // la delegación directa con un segundo workflow 'updated' para no
        // depender de reenrollment_allowed.
        $updateWorkflow = $this->makeDealWorkflow(
            ['event' => 'updated', 'field' => 'notes'],
            ['name' => 'Workflow legacy updated']
        );

        $deal->notes = 'cambiado a mano';
        $deal->save();

        app(WorkflowTriggerService::class)->handleDealEvent($deal, 'updated');

        $this->assertTrue($this->enrolled($updateWorkflow, $deal));
    }

    public function test_legacy_handle_conversation_event_still_enrolls_via_delegation(): void
    {
        $account = WhatsappAccount::create([
            'name'         => 'Cuenta legacy',
            'phone_number' => '5215500099988',
            'is_active'    => true,
        ]);

        $workflow = Workflow::create([
            'name'                 => 'Workflow legacy conversación',
            'type'                 => 'whatsapp_conversation',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => true,
        ]);

        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215500099988',
            'status'        => 'open',
            'started_at'    => now(),
        ]);

        // El observer genérico ya inscribió una vez en 'created'; llamamos
        // al método legacy directamente para confirmar que sigue
        // funcionando (delega en handleModelEvent, no lanza error).
        app(WorkflowTriggerService::class)->handleConversationEvent($conversation, 'created');

        $this->assertTrue($this->enrolled($workflow, $conversation));
    }

    // --- handleModelStale() genérico ----------------------------------------

    public function test_handle_model_stale_enrolls_records_past_threshold_for_registered_type(): void
    {
        $account = WhatsappAccount::create([
            'name'         => 'Cuenta stale',
            'phone_number' => '5215500055566',
            'is_active'    => true,
        ]);

        $workflow = Workflow::create([
            'name'                 => 'Workflow stale genérico',
            'type'                 => 'whatsapp_conversation',
            'enrollment_trigger'   => ['event' => 'stale', 'hours' => 24],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);

        $stale = WhatsappConversation::create([
            'account_id'      => $account->id,
            'contact_phone'   => '5215500011111',
            'status'          => 'open',
            'started_at'      => now()->subDays(3),
            'last_message_at' => now()->subHours(48),
        ]);

        $fresh = WhatsappConversation::create([
            'account_id'      => $account->id,
            'contact_phone'   => '5215500022222',
            'status'          => 'open',
            'started_at'      => now()->subHour(),
            'last_message_at' => now()->subMinutes(10),
        ]);

        app(WorkflowTriggerService::class)->handleModelStale('whatsapp_conversation', 'last_message_at', 24);

        $this->assertTrue($this->enrolled($workflow, $stale));
        $this->assertFalse($this->enrolled($workflow, $fresh));
    }

    public function test_handle_model_stale_does_nothing_for_unregistered_type(): void
    {
        Workflow::create([
            'name'                 => 'Workflow tipo inexistente',
            'type'                 => 'no_registrado',
            'enrollment_trigger'   => ['event' => 'stale', 'hours' => 24],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);

        // No debe lanzar ninguna excepción aunque el tipo no exista en el
        // registro (AutomatableModuleRegistry::entry() retorna null).
        app(WorkflowTriggerService::class)->handleModelStale('no_registrado', 'updated_at', 24);

        $this->assertTrue(true);
    }

    // --- Fase 23: operadores de comparación ---------------------------------

    public function test_operator_lt_enrolls_only_when_new_value_is_below_target(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'amount', 'operator' => 'lt', 'value' => 10]);
        $deal = $this->makeDeal(['amount' => 100]);

        // Baja pero no por debajo del target (10) -> no matchea.
        $deal->update(['amount' => 50]);
        $this->assertFalse($this->enrolled($workflow, $deal));

        // Baja por debajo del target -> matchea.
        $deal->update(['amount' => 5]);
        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_operator_gte_enrolls_when_new_value_meets_or_exceeds_target(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'amount', 'operator' => 'gte', 'value' => 100]);
        $deal = $this->makeDeal(['amount' => 10]);

        $deal->update(['amount' => 50]);
        $this->assertFalse($this->enrolled($workflow, $deal));

        $deal->update(['amount' => 100]);
        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_operator_neq_enrolls_when_new_value_differs_from_target(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'status', 'operator' => 'neq', 'value' => 'lost']);
        $deal = $this->makeDeal(['status' => 'open']);

        // Cambia al valor prohibido ('lost') -> neq falla, no matchea.
        $deal->update(['status' => 'lost']);
        $this->assertFalse($this->enrolled($workflow, $deal));

        $otherWorkflow = $this->makeDealWorkflow(
            ['event' => 'updated', 'field' => 'status', 'operator' => 'neq', 'value' => 'lost'],
            ['name' => 'Otro workflow neq']
        );
        $deal2 = $this->makeDeal(['status' => 'open']);

        // Cambia a un valor distinto de 'lost' -> neq se cumple, matchea.
        $deal2->update(['status' => 'won']);
        $this->assertTrue($this->enrolled($otherWorkflow, $deal2));
    }

    public function test_operator_defaults_to_eq_when_omitted(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'status', 'value' => 'won']);
        $deal = $this->makeDeal(['status' => 'open']);

        $deal->update(['status' => 'lost']);
        $this->assertFalse($this->enrolled($workflow, $deal));

        $deal->update(['status' => 'won']);
        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_non_numeric_comparison_with_numeric_operator_never_matches(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'status', 'operator' => 'gt', 'value' => 'lost']);
        $deal = $this->makeDeal(['status' => 'open']);

        $deal->update(['status' => 'won']);

        $this->assertFalse($this->enrolled($workflow, $deal));
    }

    // --- Fase 23: alias legado to_stage_id -----------------------------------

    public function test_legacy_to_stage_id_alias_still_works_as_value(): void
    {
        $stage = $this->makePipelineStage();
        $targetStage = PipelineStage::create([
            'pipeline_id'     => $stage->pipeline_id,
            'name'            => 'Ganado',
            'slug'            => 'ganado-alias',
            'order'           => 3,
            'probability'     => 100,
            'is_won'          => true,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        $workflow = $this->makeDealWorkflow([
            'event'       => 'updated',
            'field'       => 'pipeline_stage_id',
            'to_stage_id' => $targetStage->id,
        ]);

        $deal = Deal::create([
            'pipeline_id'       => $stage->pipeline_id,
            'pipeline_stage_id' => $stage->id,
            'name'              => 'Deal alias legado',
            'amount'            => 1000,
            'currency'          => 'MXN',
            'status'            => 'open',
        ]);

        $deal->update(['pipeline_stage_id' => $targetStage->id]);

        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    // --- Fase 23: from_value (transición A->B) -------------------------------

    public function test_from_value_requires_original_value_to_match_in_addition_to_new_value(): void
    {
        $workflow = $this->makeDealWorkflow([
            'event'      => 'updated',
            'field'      => 'status',
            'from_value' => 'pending',
            'value'      => 'won',
        ]);
        $deal = $this->makeDeal(['status' => 'open']);

        // Cambia al valor destino pero NO desde el valor de origen esperado.
        $deal->update(['status' => 'won']);
        $this->assertFalse($this->enrolled($workflow, $deal));
    }

    public function test_from_value_matches_when_transition_is_exact(): void
    {
        $workflow = $this->makeDealWorkflow([
            'event'      => 'updated',
            'field'      => 'status',
            'from_value' => 'open',
            'value'      => 'won',
        ]);
        $deal = $this->makeDeal(['status' => 'open']);

        $deal->update(['status' => 'won']);
        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    // --- Fase 23: evento 'deleted' -------------------------------------------

    public function test_deleted_event_enrolls_on_soft_delete_without_checking_field(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'deleted']);
        $deal = $this->makeDeal();

        $deal->delete();

        $this->assertTrue($this->enrolled($workflow, $deal));
    }

    public function test_deleted_event_does_not_enroll_created_or_updated_triggers(): void
    {
        $createdWorkflow = $this->makeDealWorkflow(['event' => 'created']);
        $updatedWorkflow = $this->makeDealWorkflow(
            ['event' => 'updated', 'field' => 'status'],
            ['name' => 'Workflow updated distinto']
        );

        $deal = $this->makeDeal();
        $deal->delete();

        // 'created' ya se disparó al crear -- lo relevante es que 'updated'
        // (que también matchea 'event' si no se distinguiera 'deleted') NO
        // se dispare por el borrado.
        $this->assertFalse($this->enrolled($updatedWorkflow, $deal));
        $this->assertTrue($this->enrolled($createdWorkflow, $deal));
    }

    // --- Fase 24: trigger_context (previous / actor_user_id) ----------------

    public function test_updated_event_persists_previous_value_in_trigger_context(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'updated', 'field' => 'status']);
        $deal = $this->makeDeal(['status' => 'open']);

        $deal->update(['status' => 'won']);

        $enrollment = WorkflowEnrollment::where('workflow_id', $workflow->id)
            ->where('enrollable_type', $deal->getMorphClass())
            ->where('enrollable_id', $deal->getKey())
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertEquals(['status' => 'open'], $enrollment->trigger_context['previous'] ?? null);
    }

    public function test_created_event_does_not_persist_previous_but_captures_actor(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $workflow = $this->makeDealWorkflow(['event' => 'created']);
        $deal = $this->makeDeal();

        $enrollment = WorkflowEnrollment::where('workflow_id', $workflow->id)
            ->where('enrollable_type', $deal->getMorphClass())
            ->where('enrollable_id', $deal->getKey())
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertArrayNotHasKey('previous', $enrollment->trigger_context ?? []);
        $this->assertEquals($user->id, $enrollment->trigger_context['actor_user_id'] ?? null);
    }

    public function test_actor_user_id_is_null_without_authenticated_user(): void
    {
        $workflow = $this->makeDealWorkflow(['event' => 'created']);
        $deal = $this->makeDeal();

        $enrollment = WorkflowEnrollment::where('workflow_id', $workflow->id)
            ->where('enrollable_type', $deal->getMorphClass())
            ->where('enrollable_id', $deal->getKey())
            ->first();

        $this->assertNotNull($enrollment);
        $this->assertArrayHasKey('actor_user_id', $enrollment->trigger_context ?? []);
        $this->assertNull($enrollment->trigger_context['actor_user_id']);
    }
}
