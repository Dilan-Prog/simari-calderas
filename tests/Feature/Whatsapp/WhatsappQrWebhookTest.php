<?php

namespace Tests\Feature\Whatsapp;

use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Verifica el webhook público del microservicio de Baileys/QR
 * (WhatsappQrWebhookController::receive(), POST /whatsapp-qr/webhook). Mismo
 * patrón que WebhookSignatureTest (Meta): un Authorization: Bearer contra
 * services.whatsapp_qr.secret, con el mismo criterio de "no romper por
 * falta de configuración" que WhatsappWebhookController/
 * EmailBounceWebhookController -- si no hay secreto configurado, se omite
 * la verificación.
 */
class WhatsappQrWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name'            => 'Cuenta QR de prueba',
            'phone_number'    => '+52 55 9876 5432',
            'connection_type' => 'baileys_qr',
            'session_id'      => 'session-webhook-test',
            'is_active'       => true,
        ], $overrides));
    }

    public function test_receive_with_valid_secret_and_message_event_creates_conversation_and_message(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $account = $this->makeAccount();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer qr-shared-secret',
        ])->postJson('/whatsapp-qr/webhook', [
            'session_id'           => 'session-webhook-test',
            'event'                => 'message',
            'from'                 => '5219991234567',
            'body'                 => 'Hola',
            'external_message_id'  => 'abc123',
            'timestamp'            => 1737400000,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id'    => $account->id,
            'contact_phone' => '5219991234567',
        ]);

        $conversation = WhatsappConversation::where('account_id', $account->id)
            ->where('contact_phone', '5219991234567')
            ->firstOrFail();

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id'      => $conversation->id,
            'sender_type'          => WhatsappMessage::SENDER_CONTACT,
            'content'              => 'Hola',
            'external_message_id'  => 'abc123',
        ]);
    }

    public function test_receive_message_event_reuses_existing_conversation_for_same_account_and_phone(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $account = $this->makeAccount();
        $conversation = WhatsappConversation::create([
            'account_id'    => $account->id,
            'contact_phone' => '5219991234567',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer qr-shared-secret',
        ])->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'session-webhook-test',
            'event'      => 'message',
            'from'       => '5219991234567',
            'body'       => 'Segundo mensaje',
        ])->assertOk();

        $this->assertSame(1, WhatsappConversation::where('account_id', $account->id)->count());
        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conversation->id,
            'content'         => 'Segundo mensaje',
        ]);
    }

    public function test_receive_with_valid_secret_and_status_event_updates_session_status(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $account = $this->makeAccount(['session_status' => 'qr_pending']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer qr-shared-secret',
        ])->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'session-webhook-test',
            'event'      => 'status',
            'status'     => 'connected',
        ]);

        $response->assertOk();

        $this->assertSame('connected', $account->fresh()->session_status);
    }

    public function test_receive_rejects_invalid_secret_when_one_is_configured(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $this->makeAccount();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-secret',
        ])->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'session-webhook-test',
            'event'      => 'status',
            'status'     => 'connected',
        ]);

        $response->assertStatus(401);
    }

    public function test_receive_rejects_missing_secret_when_one_is_configured(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $this->makeAccount();

        $response = $this->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'session-webhook-test',
            'event'      => 'status',
            'status'     => 'connected',
        ]);

        $response->assertStatus(401);
    }

    public function test_receive_accepts_missing_secret_when_none_is_configured_anywhere(): void
    {
        Config::set('services.whatsapp_qr.secret', null);

        $account = $this->makeAccount();

        $response = $this->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'session-webhook-test',
            'event'      => 'status',
            'status'     => 'connected',
        ]);

        $response->assertOk();
        $this->assertSame('connected', $account->fresh()->session_status);
    }

    public function test_receive_with_unknown_session_id_is_a_no_op_without_error(): void
    {
        Config::set('services.whatsapp_qr.secret', 'qr-shared-secret');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer qr-shared-secret',
        ])->postJson('/whatsapp-qr/webhook', [
            'session_id' => 'no-such-session',
            'event'      => 'message',
            'from'       => '5219991234567',
            'body'       => 'Hola',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 0);
        $this->assertDatabaseCount('whatsapp_messages', 0);
    }
}
