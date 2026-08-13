<?php

namespace Tests\Feature\Whatsapp;

use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GET de verificación + POST de recepción de WhatsappWebhookController con
 * un payload sintético de Meta -- nunca se llama a la API real de Meta.
 * QUEUE_CONNECTION=sync en testing (ver phpunit.xml), así que
 * ProcessWhatsappWebhookJob se ejecuta inline al despachar, sin necesidad
 * de Queue::fake()/Queue::assertPushed() para probar el efecto final.
 */
class WhatsappWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name'                    => 'Cuenta de prueba',
            'phone_number'            => '+52 55 1234 5678',
            'phone_number_id'         => '1234567890',
            'provider'                => 'meta_cloud_api',
            'webhook_verify_token'    => 'super-secreto',
            'is_active'               => true,
        ], $overrides));
    }

    public function test_verify_returns_challenge_when_token_matches(): void
    {
        $this->makeAccount();

        $response = $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=super-secreto&hub.challenge=123456');

        $response->assertOk();
        $response->assertSeeText('123456');
    }

    public function test_verify_returns_forbidden_when_token_does_not_match(): void
    {
        $this->makeAccount();

        $response = $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=token-incorrecto&hub.challenge=123456');

        $response->assertStatus(403);
    }

    /**
     * Payload sintético con la forma real de un webhook de mensajes
     * entrantes de la Meta Cloud API (entry[].changes[].value.messages[]).
     */
    private function syntheticInboundPayload(string $phoneNumberId, string $from, string $text): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'WABA_ID',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '525512345678',
                                    'phone_number_id'       => $phoneNumberId,
                                ],
                                'contacts' => [
                                    ['profile' => ['name' => 'Juan Pérez'], 'wa_id' => $from],
                                ],
                                'messages' => [
                                    [
                                        'from'      => $from,
                                        'id'        => 'wamid.INBOUND123',
                                        'timestamp' => (string) now()->timestamp,
                                        'text'      => ['body' => $text],
                                        'type'      => 'text',
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_receive_creates_conversation_and_message_from_synthetic_payload(): void
    {
        $account = $this->makeAccount();

        $payload = $this->syntheticInboundPayload($account->phone_number_id, '5215512345678', 'Hola, quiero información');

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertOk();
        $response->assertSeeText('EVENT_RECEIVED');

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
        ]);

        $conversation = WhatsappConversation::where('contact_phone', '5215512345678')->firstOrFail();

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id'      => $conversation->id,
            'sender_type'          => WhatsappMessage::SENDER_CONTACT,
            'message_type'         => 'text',
            'content'              => 'Hola, quiero información',
            'external_message_id'  => 'wamid.INBOUND123',
        ]);

        $this->assertSame(1, $conversation->fresh()->unread_count);
    }

    public function test_receive_reuses_existing_conversation_for_same_contact(): void
    {
        $account = $this->makeAccount();

        $existing = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now()->subDay(),
            'unread_count'  => 0,
        ]);

        $payload = $this->syntheticInboundPayload($account->phone_number_id, '5215512345678', 'Segundo mensaje');

        $this->postJson('/whatsapp/webhook', $payload)->assertOk();

        $this->assertSame(1, WhatsappConversation::where('contact_phone', '5215512345678')->count());
        $this->assertSame(1, $existing->fresh()->unread_count);
    }

    public function test_receive_ignores_payload_for_unknown_phone_number_id(): void
    {
        $this->makeAccount();

        $payload = $this->syntheticInboundPayload('9999999999', '5215512345678', 'Hola');

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertOk();
        $this->assertDatabaseCount('whatsapp_conversations', 0);
    }
}
