<?php

namespace Tests\Feature\Whatsapp;

use App\Models\WhatsappAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Verifica la firma X-Hub-Signature-256 que WhatsappWebhookController::receive()
 * exige cuando al menos una cuenta tiene app_secret configurado, y el modo de
 * compatibilidad hacia atrás (se omite la verificación) cuando ninguna cuenta
 * lo tiene todavía. QUEUE_CONNECTION=sync en testing (ver
 * WhatsappWebhookControllerTest), pero aquí se usa Queue::fake() para poder
 * afirmar explícitamente que el job se despacha (o no) según el resultado de
 * la verificación de firma, sin depender de los efectos secundarios del job.
 */
class WebhookSignatureTest extends TestCase
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

    private function syntheticPayload(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry'  => [
                [
                    'id'      => 'WABA_ID',
                    'changes' => [],
                ],
            ],
        ];
    }

    public function test_receive_accepts_request_with_valid_signature(): void
    {
        Queue::fake();

        $appSecret = 'mi-app-secret-de-meta';
        $this->makeAccount([
            'encrypted_app_secret' => WhatsappAccount::encryptAppSecret($appSecret),
        ]);

        $payload = $this->syntheticPayload();
        $body = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $appSecret);

        $response = $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-Hub-Signature-256' => $signature,
                'CONTENT_TYPE'             => 'application/json',
            ],
            $body
        );

        $response->assertOk();
        $response->assertSeeText('EVENT_RECEIVED');

        Queue::assertPushed(\App\Jobs\ProcessWhatsappWebhookJob::class);
    }

    public function test_receive_rejects_request_with_invalid_signature(): void
    {
        Queue::fake();

        $appSecret = 'mi-app-secret-de-meta';
        $this->makeAccount([
            'encrypted_app_secret' => WhatsappAccount::encryptAppSecret($appSecret),
        ]);

        $payload = $this->syntheticPayload();
        $body = json_encode($payload);

        $response = $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'HTTP_X-Hub-Signature-256' => 'sha256=' . str_repeat('0', 64),
                'CONTENT_TYPE'             => 'application/json',
            ],
            $body
        );

        $response->assertStatus(403);

        Queue::assertNotPushed(\App\Jobs\ProcessWhatsappWebhookJob::class);
    }

    public function test_receive_rejects_request_with_missing_signature_when_account_has_secret(): void
    {
        Queue::fake();

        $appSecret = 'mi-app-secret-de-meta';
        $this->makeAccount([
            'encrypted_app_secret' => WhatsappAccount::encryptAppSecret($appSecret),
        ]);

        $payload = $this->syntheticPayload();

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertStatus(403);

        Queue::assertNotPushed(\App\Jobs\ProcessWhatsappWebhookJob::class);
    }

    public function test_receive_accepts_missing_signature_when_no_account_has_app_secret_configured(): void
    {
        Queue::fake();

        // Cuenta existente pero sin app_secret configurado todavía —
        // compatibilidad hacia atrás durante la migración.
        $this->makeAccount();

        $payload = $this->syntheticPayload();

        $response = $this->postJson('/whatsapp/webhook', $payload);

        $response->assertOk();
        $response->assertSeeText('EVENT_RECEIVED');

        Queue::assertPushed(\App\Jobs\ProcessWhatsappWebhookJob::class);
    }
}
