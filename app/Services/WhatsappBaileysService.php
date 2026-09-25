<?php

namespace App\Services;

use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Capa que habla con el microservicio Node.js de WhatsApp vía Baileys/QR
 * (whatsapp-qr-service/, repo aparte — no forma parte de este proyecto
 * Laravel). Replica el contrato público de WhatsappService (Meta Cloud API)
 * para que WhatsappFunnelController pueda tratar ambos tipos de conexión de
 * forma intercambiable vía resolveService().
 *
 * Toda llamada usa un timeout corto (8s) y nunca lanza excepción hacia el
 * caller — un microservicio caído/lento jamás debe colgar una request de
 * Laravel; se registra un Log::warning() y se devuelve un valor por defecto
 * seguro, mismo criterio de degradación que WhatsappWebhookController /
 * EmailBounceWebhookController ya usan para configuración ausente.
 */
class WhatsappBaileysService
{
    private function client()
    {
        return Http::timeout(8)->withToken(config('services.whatsapp_qr.secret'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.whatsapp_qr.url'), '/');
    }

    /**
     * Envía texto libre. Baileys/QR no tiene ventana de 24h ni plantillas,
     * así que esto es válido en cualquier momento (ver isWithin24hWindow()).
     */
    public function sendTextMessage(WhatsappConversation $conversation, string $text): WhatsappMessage
    {
        $account = $conversation->account;
        $sessionId = $account?->session_id;

        $externalId = null;

        if ($sessionId) {
            try {
                $response = $this->client()
                    ->post($this->baseUrl() . "/sessions/{$sessionId}/send", [
                        'to'   => $this->digitsOnly($conversation->contact_phone),
                        'text' => $text,
                    ]);

                if ($response->successful() && ($response->json('success') === true)) {
                    $externalId = $response->json('id');
                } else {
                    Log::warning('WhatsappBaileysService: envío fallido', [
                        'conversation_id' => $conversation->id,
                        'session_id'      => $sessionId,
                        'status'          => $response->status(),
                        'body'            => $response->json() ?? $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('WhatsappBaileysService: excepción al enviar', [
                    'conversation_id' => $conversation->id,
                    'session_id'      => $sessionId,
                    'error'           => $e->getMessage(),
                ]);
            }
        } else {
            Log::warning('WhatsappBaileysService: envío sin session_id configurado en la cuenta', [
                'conversation_id' => $conversation->id,
                'account_id'      => $account?->id,
            ]);
        }

        $message = $conversation->messages()->create([
            'sender_type'          => WhatsappMessage::SENDER_AGENT,
            'external_message_id'  => $externalId,
            'sent_at'               => now(),
            'message_type' => 'text',
            'content'      => $text,
            'is_template'  => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * Baileys/QR no tiene concepto de plantilla — se delega a
     * sendTextMessage() con un texto plano razonable, para que nada se rompa
     * si algún caller todavía invoca esto sobre una cuenta QR.
     */
    public function sendTemplateMessage(WhatsappConversation $conversation, string $templateName, array $params = [], string $languageCode = 'es_MX'): WhatsappMessage
    {
        $text = empty($params) ? $templateName : implode(' ', $params);

        return $this->sendTextMessage($conversation, $text);
    }

    public function approvedTemplates(WhatsappAccount $account): array
    {
        return [];
    }

    /**
     * Baileys/QR no tiene ventana de 24h de Meta — siempre se puede enviar
     * texto libre.
     */
    public function isWithin24hWindow(WhatsappConversation $conversation): bool
    {
        return true;
    }

    /**
     * Inicia (o retoma) la sesión de Baileys para esta cuenta. Si la cuenta
     * todavía no tiene session_id, se genera uno y se persiste antes de
     * llamar al microservicio.
     */
    public function startSession(WhatsappAccount $account): array
    {
        if (empty($account->session_id)) {
            $account->session_id = (string) Str::random(32);
            $account->save();
        }

        return $this->callSessionEndpoint($account, 'post', "/sessions/{$account->session_id}/start");
    }

    public function sessionStatus(WhatsappAccount $account): array
    {
        if (empty($account->session_id)) {
            return ['status' => 'disconnected', 'qr' => null];
        }

        return $this->callSessionEndpoint($account, 'get', "/sessions/{$account->session_id}/status");
    }

    private function callSessionEndpoint(WhatsappAccount $account, string $method, string $path): array
    {
        $default = ['status' => 'disconnected', 'qr' => null];

        try {
            $response = $method === 'post'
                ? $this->client()->post($this->baseUrl() . $path)
                : $this->client()->get($this->baseUrl() . $path);

            if (!$response->successful()) {
                Log::warning('WhatsappBaileysService: respuesta no exitosa del microservicio', [
                    'account_id' => $account->id,
                    'path'       => $path,
                    'status'     => $response->status(),
                    'body'       => $response->json() ?? $response->body(),
                ]);

                return $default;
            }

            $result = [
                'status' => $response->json('status') ?? $default['status'],
                'qr'     => $response->json('qr'),
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsappBaileysService: excepción al llamar al microservicio', [
                'account_id' => $account->id,
                'path'       => $path,
                'error'      => $e->getMessage(),
            ]);

            return $default;
        }

        $account->update(['session_status' => $result['status']]);

        return $result;
    }

    private function digitsOnly(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }
}
