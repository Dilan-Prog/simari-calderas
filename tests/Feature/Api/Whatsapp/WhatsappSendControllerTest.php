<?php

namespace Tests\Feature\Api\Whatsapp;

use App\Models\ApiClient;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fase 6 del plan de integración N8N: POST /api/v1/whatsapp/accounts/{account}/send.
 * Wrapper delgado sobre WhatsappService -- igual que WhatsappServiceTest, nunca
 * se llama a la API real de Meta (Http::fake()).
 */
class WhatsappSendControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name'                   => 'Cuenta de prueba',
            'phone_number'           => '+52 55 1234 5678',
            'phone_number_id'        => '1234567890',
            'provider'               => 'meta_cloud_api',
            'is_active'              => true,
            'encrypted_access_token' => WhatsappAccount::encryptAccessToken('fake-token'),
        ], $overrides));
    }

    private function tokenWithAbilities(array $abilities): string
    {
        $client = ApiClient::create([
            'name'      => 'N8N test client',
            'is_active' => true,
        ]);

        return $client->createToken('test-token', $abilities)->plainTextToken;
    }

    public function test_send_without_token_is_rejected(): void
    {
        $account = $this->makeAccount();

        $response = $this->postJson("/api/v1/whatsapp/accounts/{$account->id}/send", [
            'to'            => '5215512345678',
            'type'          => 'template',
            'template_name' => 'bienvenida',
        ]);

        $response->assertStatus(401);
    }

    public function test_send_with_wrong_ability_is_forbidden(): void
    {
        $account = $this->makeAccount();
        $token = $this->tokenWithAbilities(['whatsapp:read']);

        $response = $this->withToken($token)->postJson("/api/v1/whatsapp/accounts/{$account->id}/send", [
            'to'            => '5215512345678',
            'type'          => 'template',
            'template_name' => 'bienvenida',
        ]);

        $response->assertStatus(403);
    }

    public function test_send_template_message_with_correct_ability_creates_conversation_and_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages'          => [['id' => 'wamid.TEST999']],
            ], 200),
        ]);

        $account = $this->makeAccount();
        $token = $this->tokenWithAbilities(['whatsapp:send']);

        $response = $this->withToken($token)->postJson("/api/v1/whatsapp/accounts/{$account->id}/send", [
            'to'            => '5215512345678',
            'type'          => 'template',
            'template_name' => 'bienvenida',
            'params'        => ['Juan'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.message_type', 'template');
        $response->assertJsonPath('data.external_message_id', 'wamid.TEST999');

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'sender_type'   => WhatsappMessage::SENDER_AGENT,
            'is_template'   => true,
            'template_name' => 'bienvenida',
        ]);
    }

    public function test_send_text_message_outside_24h_window_is_rejected(): void
    {
        $account = $this->makeAccount();
        $token = $this->tokenWithAbilities(['whatsapp:send']);

        // Conversación existente sin ningún mensaje entrante -> ventana cerrada.
        WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ]);

        $response = $this->withToken($token)->postJson("/api/v1/whatsapp/accounts/{$account->id}/send", [
            'to'   => '5215512345678',
            'type' => 'text',
            'text' => 'Hola',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_on_inactive_account_is_rejected(): void
    {
        $account = $this->makeAccount(['is_active' => false]);
        $token = $this->tokenWithAbilities(['whatsapp:send']);

        $response = $this->withToken($token)->postJson("/api/v1/whatsapp/accounts/{$account->id}/send", [
            'to'            => '5215512345678',
            'type'          => 'template',
            'template_name' => 'bienvenida',
        ]);

        $response->assertStatus(422);
    }
}
