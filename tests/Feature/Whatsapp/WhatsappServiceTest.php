<?php

namespace Tests\Feature\Whatsapp;

use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WhatsappService::sendTextMessage/sendTemplateMessage/isWithin24hWindow
 * contra Http::fake() — nunca se llama a la API real de Meta (no hay
 * credenciales reales de Meta Cloud API todavía, es una pregunta abierta
 * del plan). Solo se verifica que el servicio arma la llamada HTTP
 * correctamente y persiste el WhatsappMessage resultante.
 */
class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name'                    => 'Cuenta de prueba',
            'phone_number'            => '+52 55 1234 5678',
            'phone_number_id'         => '1234567890',
            'provider'                => 'meta_cloud_api',
            'is_active'               => true,
            'encrypted_access_token'  => WhatsappAccount::encryptAccessToken('fake-token'),
        ], $overrides));
    }

    private function makeConversation(WhatsappAccount $account, array $overrides = []): WhatsappConversation
    {
        return WhatsappConversation::create(array_merge([
            'account_id'    => $account->id,
            'contact_phone' => '5215512345678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ], $overrides));
    }

    public function test_send_text_message_calls_graph_api_and_persists_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.TEST123']],
            ], 200),
        ]);

        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        $message = app(WhatsappService::class)->sendTextMessage($conversation, 'Hola, ¿cómo estás?');

        Http::assertSent(function ($request) use ($account, $conversation) {
            return $request->url() === "https://graph.facebook.com/v19.0/{$account->phone_number_id}/messages"
                && $request->hasHeader('Authorization', 'Bearer fake-token')
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Hola, ¿cómo estás?'
                && $request['to'] === $conversation->contact_phone;
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'id'                    => $message->id,
            'conversation_id'       => $conversation->id,
            'sender_type'           => WhatsappMessage::SENDER_AGENT,
            'message_type'          => 'text',
            'content'               => 'Hola, ¿cómo estás?',
            'is_template'           => false,
            'external_message_id'   => 'wamid.TEST123',
        ]);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_send_template_message_calls_graph_api_with_template_payload(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.TEMPLATE123']],
            ], 200),
        ]);

        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        $message = app(WhatsappService::class)->sendTemplateMessage(
            $conversation,
            'bienvenida',
            ['Juan', 'Cotización #100']
        );

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://graph.facebook.com/v19.0/{$account->phone_number_id}/messages"
                && $request['type'] === 'template'
                && $request['template']['name'] === 'bienvenida'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan';
        });

        $this->assertDatabaseHas('whatsapp_messages', [
            'id'             => $message->id,
            'message_type'   => 'template',
            'is_template'    => true,
            'template_name'  => 'bienvenida',
        ]);
    }

    public function test_send_message_does_not_hit_real_meta_api(): void
    {
        // Ninguna URL real de Meta debe recibir tráfico en el test: si el
        // fake no cubriera todas las llamadas, Http::preventStrayRequests()
        // haría fallar el test en vez de golpear la red real.
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200),
        ]);

        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        app(WhatsappService::class)->sendTextMessage($conversation, 'Test');

        Http::assertSentCount(1);
    }

    public function test_is_within_24h_window_true_when_last_inbound_message_recent(): void
    {
        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => WhatsappMessage::SENDER_CONTACT,
            'message_type'    => 'text',
            'content'         => 'Hola',
            'sent_at'         => now()->subHours(2),
        ]);

        $this->assertTrue(app(WhatsappService::class)->isWithin24hWindow($conversation->fresh()));
    }

    public function test_is_within_24h_window_false_when_last_inbound_message_is_old(): void
    {
        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => WhatsappMessage::SENDER_CONTACT,
            'message_type'    => 'text',
            'content'         => 'Hola hace tiempo',
            'sent_at'         => now()->subHours(30),
        ]);

        $this->assertFalse(app(WhatsappService::class)->isWithin24hWindow($conversation->fresh()));
    }

    public function test_is_within_24h_window_false_when_no_inbound_messages_yet(): void
    {
        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        // Solo un mensaje saliente del agente, ningún mensaje entrante del
        // contacto todavía -- la ventana se mide desde el ÚLTIMO mensaje
        // del contacto, no de cualquier mensaje.
        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type'     => WhatsappMessage::SENDER_AGENT,
            'message_type'    => 'template',
            'content'         => 'bienvenida',
            'is_template'     => true,
            'sent_at'         => now(),
        ]);

        $this->assertFalse(app(WhatsappService::class)->isWithin24hWindow($conversation->fresh()));
    }

    public function test_access_token_is_never_exposed_in_plain_attributes(): void
    {
        $account = $this->makeAccount();

        $array = $account->toArray();

        $this->assertArrayNotHasKey('encrypted_access_token', $array);
        $this->assertSame('fake-token', $account->access_token);
    }
}
