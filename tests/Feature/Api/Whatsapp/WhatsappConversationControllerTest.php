<?php

namespace Tests\Feature\Api\Whatsapp;

use App\Models\ApiClient;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 6 del plan de integración N8N: GET /api/v1/whatsapp/conversations
 * (solo lectura, filtros status/account_id).
 */
class WhatsappConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(): WhatsappAccount
    {
        return WhatsappAccount::create([
            'name'                   => 'Cuenta de prueba',
            'phone_number'           => '+52 55 1234 5678',
            'phone_number_id'        => '1234567890',
            'provider'               => 'meta_cloud_api',
            'is_active'              => true,
            'encrypted_access_token' => WhatsappAccount::encryptAccessToken('fake-token'),
        ]);
    }

    private function tokenWithAbilities(array $abilities): string
    {
        $client = ApiClient::create([
            'name'      => 'N8N test client',
            'is_active' => true,
        ]);

        return $client->createToken('test-token', $abilities)->plainTextToken;
    }

    public function test_index_without_token_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/whatsapp/conversations');

        $response->assertStatus(401);
    }

    public function test_index_with_wrong_ability_is_forbidden(): void
    {
        $token = $this->tokenWithAbilities(['whatsapp:send']);

        $response = $this->withToken($token)->getJson('/api/v1/whatsapp/conversations');

        $response->assertStatus(403);
    }

    public function test_index_with_correct_ability_lists_and_filters_conversations(): void
    {
        $account = $this->makeAccount();
        $otherAccount = $this->makeAccount();

        $open = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ]);

        WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215512399999',
            'status'        => 'closed',
            'started_at'    => now(),
            'unread_count'  => 0,
        ]);

        WhatsappConversation::create([
            'account_id'    => $otherAccount->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ]);

        $token = $this->tokenWithAbilities(['whatsapp:read']);

        $response = $this->withToken($token)->getJson(
            "/api/v1/whatsapp/conversations?status=open&account_id={$account->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $open->id);
    }
}
