<?php

namespace Tests\Feature\Whatsapp;

use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Services\WhatsappBaileysService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * App\Services\WhatsappBaileysService contra Http::fake() -- nunca se llama
 * al microservicio Node.js real de whatsapp-qr-service/. Mismo patrón que
 * WhatsappServiceTest (Meta Cloud API): se verifica que el servicio arma la
 * llamada HTTP correctamente, persiste el WhatsappMessage resultante, y
 * jamás lanza una excepción hacia el caller ante un microservicio caído.
 */
class WhatsappBaileysServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureQrService(string $url = 'https://qr.example.test', string $secret = 'qr-shared-secret'): void
    {
        Config::set('services.whatsapp_qr.url', $url);
        Config::set('services.whatsapp_qr.secret', $secret);
    }

    private function makeAccount(array $overrides = []): WhatsappAccount
    {
        return WhatsappAccount::create(array_merge([
            'name'            => 'Cuenta QR de prueba',
            'phone_number'    => '+52 55 9876 5432',
            'connection_type' => 'baileys_qr',
            'is_active'       => true,
        ], $overrides));
    }

    private function makeConversation(WhatsappAccount $account, array $overrides = []): WhatsappConversation
    {
        return WhatsappConversation::create(array_merge([
            'account_id'    => $account->id,
            'contact_phone' => '+52 1 55 1234 5678',
            'status'        => 'open',
            'started_at'    => now(),
            'unread_count'  => 0,
        ], $overrides));
    }

    public function test_send_text_message_posts_to_qr_service_and_persists_message(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'session-abc123']);
        $conversation = $this->makeConversation($account);

        Http::fake([
            'qr.example.test/*' => Http::response([
                'success' => true,
                'id'      => 'baileys-msg-1',
            ], 200),
        ]);

        $message = app(WhatsappBaileysService::class)->sendTextMessage($conversation, 'Hola desde QR');

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://qr.example.test/sessions/{$account->session_id}/send"
                && $request->hasHeader('Authorization', 'Bearer qr-shared-secret')
                && $request['to'] === '5215512345678'
                && $request['text'] === 'Hola desde QR';
        });

        $this->assertInstanceOf(WhatsappMessage::class, $message);
        $this->assertDatabaseHas('whatsapp_messages', [
            'id'                   => $message->id,
            'conversation_id'      => $conversation->id,
            'sender_type'          => WhatsappMessage::SENDER_AGENT,
            'message_type'         => 'text',
            'content'              => 'Hola desde QR',
            'is_template'          => false,
            'external_message_id'  => 'baileys-msg-1',
        ]);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_send_text_message_does_not_throw_on_non_2xx_response_and_still_creates_message(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'session-fail']);
        $conversation = $this->makeConversation($account);

        Http::fake([
            'qr.example.test/*' => Http::response(['success' => false, 'error' => 'session not connected'], 500),
        ]);

        $message = app(WhatsappBaileysService::class)->sendTextMessage($conversation, 'Texto que falla');

        // No lanza excepción (llegar hasta aquí ya lo confirma) y, al igual
        // que WhatsappService, el WhatsappMessage se crea de todas formas
        // (registro local del intento de envío) -- solo sin
        // external_message_id porque el microservicio no confirmó éxito.
        $this->assertDatabaseHas('whatsapp_messages', [
            'id'                   => $message->id,
            'conversation_id'      => $conversation->id,
            'content'              => 'Texto que falla',
            'external_message_id'  => null,
        ]);
    }

    public function test_send_text_message_does_not_throw_on_connection_exception(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'session-down']);
        $conversation = $this->makeConversation($account);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $message = app(WhatsappBaileysService::class)->sendTextMessage($conversation, 'Servicio caído');

        $this->assertDatabaseHas('whatsapp_messages', [
            'id'                   => $message->id,
            'content'              => 'Servicio caído',
            'external_message_id'  => null,
        ]);
    }

    public function test_approved_templates_always_returns_empty_array(): void
    {
        $account = $this->makeAccount();

        $this->assertSame([], app(WhatsappBaileysService::class)->approvedTemplates($account));
    }

    public function test_is_within_24h_window_always_returns_true(): void
    {
        $account = $this->makeAccount();
        $conversation = $this->makeConversation($account);

        // Sin ningún mensaje entrante todavía -- WhatsappService devolvería
        // false en este caso, pero Baileys/QR no tiene ventana de 24h.
        $this->assertTrue(app(WhatsappBaileysService::class)->isWithin24hWindow($conversation));
    }

    public function test_start_session_generates_session_id_when_missing_and_persists_status(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount();
        $this->assertNull($account->session_id);

        Http::fake([
            'qr.example.test/*' => Http::response(['status' => 'qr_pending', 'qr' => 'data:image/png;base64,AAAA'], 200),
        ]);

        $result = app(WhatsappBaileysService::class)->startSession($account);

        $this->assertSame('qr_pending', $result['status']);
        $this->assertSame('data:image/png;base64,AAAA', $result['qr']);

        $account->refresh();
        $this->assertNotNull($account->session_id);
        $this->assertSame('qr_pending', $account->session_status);

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://qr.example.test/sessions/{$account->session_id}/start"
                && $request->method() === 'POST';
        });
    }

    public function test_start_session_reuses_existing_session_id(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'already-existing']);

        Http::fake([
            'qr.example.test/*' => Http::response(['status' => 'connected', 'qr' => null], 200),
        ]);

        app(WhatsappBaileysService::class)->startSession($account);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://qr.example.test/sessions/already-existing/start';
        });

        $this->assertSame('already-existing', $account->fresh()->session_id);
    }

    public function test_start_session_degrades_to_disconnected_when_service_unreachable(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_status' => 'connected']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $result = app(WhatsappBaileysService::class)->startSession($account);

        $this->assertSame(['status' => 'disconnected', 'qr' => null], $result);

        // El servicio real NO sobrescribe session_status en la BD cuando la
        // llamada falla (excepción o respuesta no exitosa) -- solo persiste
        // el nuevo estado cuando el microservicio responde con éxito (ver
        // WhatsappBaileysService::callSessionEndpoint()). El último estado
        // conocido se preserva en vez de pisarlo con un fallo transitorio.
        $this->assertSame('connected', $account->fresh()->session_status);
    }

    public function test_start_session_degrades_to_disconnected_on_non_2xx_response(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount();

        Http::fake([
            'qr.example.test/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $result = app(WhatsappBaileysService::class)->startSession($account);

        $this->assertSame(['status' => 'disconnected', 'qr' => null], $result);
    }

    public function test_session_status_is_read_only_get_request(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'session-xyz']);

        Http::fake([
            'qr.example.test/*' => Http::response(['status' => 'connected', 'qr' => null], 200),
        ]);

        $result = app(WhatsappBaileysService::class)->sessionStatus($account);

        $this->assertSame('connected', $result['status']);

        Http::assertSent(function ($request) use ($account) {
            return $request->url() === "https://qr.example.test/sessions/{$account->session_id}/status"
                && $request->method() === 'GET';
        });

        $this->assertSame('connected', $account->fresh()->session_status);
    }

    public function test_session_status_degrades_to_disconnected_when_service_unreachable(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount(['session_id' => 'session-xyz']);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $result = app(WhatsappBaileysService::class)->sessionStatus($account);

        $this->assertSame(['status' => 'disconnected', 'qr' => null], $result);
    }

    public function test_session_status_without_session_id_returns_disconnected_without_calling_service(): void
    {
        $this->configureQrService();

        $account = $this->makeAccount();
        $this->assertNull($account->session_id);

        Http::preventStrayRequests();
        Http::fake();

        $result = app(WhatsappBaileysService::class)->sessionStatus($account);

        $this->assertSame(['status' => 'disconnected', 'qr' => null], $result);
        Http::assertNothingSent();
    }
}
