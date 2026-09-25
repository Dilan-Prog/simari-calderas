<?php

namespace Tests\Feature\Whatsapp;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

    // ------------------------------------------------------------------
    // Resolución de servicio según connection_type (baileys_qr vs
    // meta_cloud_api) -- ver WhatsappFunnelController::resolveService().
    // ------------------------------------------------------------------

    private function makeQrAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name' => 'Cuenta QR de prueba',
            'phone_number' => '+52 55 9876 5432',
            'connection_type' => 'baileys_qr',
            'session_id' => 'session-funnel-test',
            'is_active' => true,
        ], $overrides));
    }

    public function test_send_message_for_baileys_qr_account_uses_the_qr_service_not_meta(): void
    {
        Config::set('services.whatsapp_qr.url', 'https://qr.example.test');
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $admin = $this->adminUser();
        $account = $this->makeQrAccount();

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'qr.example.test/*' => Http::response(['success' => true, 'id' => 'baileys-1'], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/mensajes",
            ['type' => 'text', 'text' => 'Hola desde el embudo']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://qr.example.test/sessions/{$account->session_id}/send"
                && ! str_contains($request->url(), 'graph.facebook.com');
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Hola desde el embudo',
            'sender_type' => WhatsappMessage::SENDER_AGENT,
        ]);
    }

    public function test_send_message_for_baileys_qr_account_allows_free_text_outside_any_window(): void
    {
        // A diferencia de Meta, Baileys/QR no tiene ventana de 24h -- el
        // envío de texto libre nunca debe ser rechazado con 422 por esta
        // regla, aunque no exista ningún mensaje entrante previo.
        Config::set('services.whatsapp_qr.url', 'https://qr.example.test');
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $admin = $this->adminUser();
        $account = $this->makeQrAccount();

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        Http::fake([
            'qr.example.test/*' => Http::response(['success' => true, 'id' => 'baileys-2'], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/mensajes",
            ['type' => 'text', 'text' => 'Texto libre sin ventana']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_send_message_for_meta_cloud_api_account_is_unchanged(): void
    {
        // Regresión: una cuenta meta_cloud_api (comportamiento normal, sin
        // connection_type explícito) debe seguir usando WhatsappService
        // (graph.facebook.com), nunca el servicio de QR.
        $admin = $this->adminUser();
        $account = $this->makeAccount();
        // connection_type no se pasa explícitamente al crear -- Eloquent no
        // recarga el default de columna de la BD en la instancia en
        // memoria devuelta por create(), así que se verifica vía fresh().
        $this->assertSame('meta_cloud_api', $account->fresh()->connection_type);

        $conversation = WhatsappConversation::create([
            'account_id' => $account->id,
            'contact_phone' => '5215512345678',
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => WhatsappMessage::SENDER_CONTACT,
            'message_type' => 'text',
            'content' => 'Hola',
            'sent_at' => now()->subHours(1),
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.FUNNEL1']],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(
            "/admin/embudo-de-venta/{$conversation->id}/mensajes",
            ['type' => 'text', 'text' => 'Hola vía Meta']
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://graph.facebook.com/v19.0/{$account->phone_number_id}/messages";
        });
    }

    public function test_new_chat_for_baileys_qr_account_sends_plain_text_not_a_forced_template(): void
    {
        Config::set('services.whatsapp_qr.url', 'https://qr.example.test');
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $admin = $this->adminUser();
        [$waPipeline, $stageA] = $this->whatsappPipelineWithStages();
        $account = $this->makeQrAccount();

        Http::preventStrayRequests();
        Http::fake([
            'qr.example.test/*' => Http::response(['success' => true, 'id' => 'baileys-newchat'], 200),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/embudo-de-venta/nuevo-chat', [
            'account_id' => $account->id,
            'contact_phone' => '5215512345678',
            'pipeline_stage_id' => $stageA->id,
            'template_name' => 'Hola, te contactamos por WhatsApp',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Se envía como texto plano al endpoint /send del microservicio de
        // QR -- nunca se construye un payload de plantilla de Meta.
        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://qr.example.test/sessions/{$account->session_id}/send"
                && $request['text'] === 'Hola, te contactamos por WhatsApp';
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'message_type' => 'text',
            'is_template' => false,
            'content' => 'Hola, te contactamos por WhatsApp',
        ]);
    }

    public function test_new_chat_for_meta_cloud_api_account_still_sends_a_template(): void
    {
        // Regresión: newChat sobre una cuenta meta_cloud_api debe seguir
        // forzando una plantilla aprobada, nunca texto libre.
        $admin = $this->adminUser();
        [$waPipeline, $stageA] = $this->whatsappPipelineWithStages();
        $account = $this->makeAccount();

        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.NEWCHAT1']],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/embudo-de-venta/nuevo-chat', [
            'account_id' => $account->id,
            'contact_phone' => '5215512345678',
            'pipeline_stage_id' => $stageA->id,
            'template_name' => 'bienvenida',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://graph.facebook.com/v19.0/{$account->phone_number_id}/messages"
                && $request['type'] === 'template'
                && $request['template']['name'] === 'bienvenida';
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'message_type' => 'template',
            'is_template' => true,
            'template_name' => 'bienvenida',
        ]);
    }
}
