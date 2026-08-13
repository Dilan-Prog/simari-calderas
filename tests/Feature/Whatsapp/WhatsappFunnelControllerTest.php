<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 13 del plan (Embudo de Venta): el endpoint moveStage nunca
 * toca el Deal vinculado a una conversación (son dos tableros de etapas
 * independientes que solo comparten Customer/contacto de fondo), y el botón
 * "Crear negocio" reutiliza el flujo normal de alta de Deals (DealService)
 * y liga whatsapp_conversations.deal_id de vuelta.
 */
class WhatsappFunnelControllerTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role' => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'position' => 'Administrador',
            'phone' => '5555555555',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XAXX010101000',
            'role_id' => $role->id,
        ]);
    }

    private function whatsappPipelineWithStages(): array
    {
        $pipeline = Pipeline::create([
            'name' => 'Embudo de Venta WhatsApp',
            'channel' => Pipeline::CHANNEL_WHATSAPP,
            'is_active' => true,
        ]);

        $stageA = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Nuevo contacto',
            'slug' => 'nuevo-contacto',
            'order' => 0,
        ]);

        $stageB = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'En conversación',
            'slug' => 'en-conversacion',
            'order' => 1,
        ]);

        return [$pipeline, $stageA, $stageB];
    }

    private function dealsPipelineWithStage(): array
    {
        $pipeline = Pipeline::create([
            'name' => 'Ventas',
            'channel' => Pipeline::CHANNEL_DEALS,
            'is_active' => true,
        ]);

        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Prospecto',
            'slug' => 'prospecto',
            'order' => 0,
        ]);

        return [$pipeline, $stage];
    }

    private function makeAccount(): WhatsappAccount
    {
        return WhatsappAccount::create([
            'name' => 'Cuenta de prueba',
            'phone_number' => '+52 55 1234 5678',
            'phone_number_id' => '1234567890',
            'provider' => 'meta_cloud_api',
            'is_active' => true,
            'encrypted_access_token' => WhatsappAccount::encryptAccessToken('fake-token'),
        ]);
    }

    public function test_index_renders_the_kanban_board_for_the_whatsapp_pipeline(): void
    {
        $admin = $this->adminUser();
        [$waPipeline, $stageA, $stageB] = $this->whatsappPipelineWithStages();
        $account = $this->makeAccount();

        WhatsappConversation::create([
            'account_id' => $account->id,
            'pipeline_id' => $waPipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 2,
        ]);

        $response = $this->actingAs($admin)->get('/admin/embudo-de-venta');

        $response->assertOk();
        $response->assertSee('Embudo de Venta');
        $response->assertSee($stageA->name);
        $response->assertSee($stageB->name);
    }

    public function test_move_stage_updates_the_conversation_but_never_touches_a_linked_deal(): void
    {
        $admin = $this->adminUser();
        [$waPipeline, $stageA, $stageB] = $this->whatsappPipelineWithStages();
        [$dealPipeline, $dealStage] = $this->dealsPipelineWithStage();
        $account = $this->makeAccount();

        $deal = Deal::create([
            'pipeline_id' => $dealPipeline->id,
            'pipeline_stage_id' => $dealStage->id,
            'name' => 'Negocio vinculado',
            'status' => 'open',
        ]);

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'pipeline_id' => $waPipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'deal_id' => $deal->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/mover-etapa",
            ['to_stage_id' => $stageB->id]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $conversation->refresh();
        $deal->refresh();

        // La conversación sí se movió...
        $this->assertSame($stageB->id, $conversation->pipeline_stage_id);

        // ...pero el Deal vinculado se quedó exactamente en su etapa
        // original — son dos tableros independientes.
        $this->assertSame($dealStage->id, $deal->pipeline_stage_id);
    }

    public function test_move_stage_does_not_affect_deals_unrelated_to_the_conversation(): void
    {
        $admin = $this->adminUser();
        [$waPipeline, $stageA, $stageB] = $this->whatsappPipelineWithStages();
        [$dealPipeline, $dealStage] = $this->dealsPipelineWithStage();
        $account = $this->makeAccount();

        // Conversación SIN deal_id — confirma que moveStage tampoco crea o
        // toca ningún Deal cuando no hay vínculo.
        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'pipeline_id' => $waPipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'contact_phone' => '5215512345679',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        $dealCountBefore = Deal::count();

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/mover-etapa",
            ['to_stage_id' => $stageB->id]
        );

        $response->assertOk();
        $this->assertSame($dealCountBefore, Deal::count());
        $this->assertNull($conversation->fresh()->deal_id);
    }

    public function test_create_deal_creates_a_real_deal_and_links_it_back_to_the_conversation(): void
    {
        $admin = $this->adminUser();
        [$waPipeline, $stageA] = $this->whatsappPipelineWithStages();
        [$dealPipeline, $dealStage] = $this->dealsPipelineWithStage();
        $account = $this->makeAccount();

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'pipeline_id' => $waPipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/crear-negocio",
            [
                'pipeline_id' => $dealPipeline->id,
                'name' => 'Negocio desde WhatsApp',
                'amount' => 5000,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $conversation->refresh();
        $this->assertNotNull($conversation->deal_id);

        $deal = Deal::find($conversation->deal_id);
        $this->assertNotNull($deal);
        $this->assertSame('Negocio desde WhatsApp', $deal->name);
        $this->assertSame($dealPipeline->id, $deal->pipeline_id);
        // create() sin pipeline_stage_id explícito usa la primera etapa
        // del pipeline (mismo comportamiento que DealController::store()).
        $this->assertSame($dealStage->id, $deal->pipeline_stage_id);
        $this->assertSame('whatsapp', $deal->source);
        $this->assertSame($conversation->contact_phone, $deal->contact_snapshot_phone);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $conversation->id,
            'deal_id' => $deal->id,
        ]);
    }

    public function test_create_deal_rejects_a_conversation_that_already_has_a_linked_deal(): void
    {
        $admin = $this->adminUser();
        [$waPipeline, $stageA] = $this->whatsappPipelineWithStages();
        [$dealPipeline, $dealStage] = $this->dealsPipelineWithStage();
        $account = $this->makeAccount();

        $existingDeal = Deal::create([
            'pipeline_id' => $dealPipeline->id,
            'pipeline_stage_id' => $dealStage->id,
            'name' => 'Ya existente',
            'status' => 'open',
        ]);

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'pipeline_id' => $waPipeline->id,
            'pipeline_stage_id' => $stageA->id,
            'deal_id' => $existingDeal->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        $dealCountBefore = Deal::count();

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/crear-negocio",
            [
                'pipeline_id' => $dealPipeline->id,
                'name' => 'Duplicado',
            ]
        );

        $response->assertStatus(422);
        $this->assertSame($dealCountBefore, Deal::count());
    }
}
