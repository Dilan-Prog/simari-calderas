<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Capa que habla con Meta Cloud API (WhatsApp Business). No usa ningún SDK
 * externo — llamadas HTTP planas vía Http::post()/Http::get(), igual que el
 * resto de integraciones del proyecto (ver ExternalDatabaseConnectionService
 * para la convención de servicios que hablan con sistemas externos).
 *
 * IMPORTANTE: no hay credenciales reales de Meta todavía (pregunta abierta
 * del plan) — este servicio es funcional de punta a punta pero solo puede
 * probarse end-to-end contra el webhook real de Meta cuando existan.
 */
class WhatsappService
{
    private const GRAPH_API_VERSION = 'v19.0';
    private const GRAPH_API_BASE = 'https://graph.facebook.com';

    private function baseUrl(WhatsappAccount $account): string
    {
        return self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION . '/' . $account->phone_number_id;
    }

    /**
     * Envía un mensaje de texto libre. Solo es válido dentro de la ventana
     * de 24h desde el último mensaje del contacto — el caller debe validar
     * isWithin24hWindow() antes de llamar esto; el servicio no lo bloquea
     * aquí para no acoplar la regla de negocio a la llamada HTTP en sí
     * (Meta la rechazaría de todas formas y el error se propaga igual).
     */
    public function sendTextMessage(WhatsappConversation $conversation, string $text): WhatsappMessage
    {
        $account = $conversation->account;

        $response = Http::withToken($account->access_token)
            ->post($this->baseUrl($account) . '/messages', [
                'messaging_product' => 'whatsapp',
                'to'                => $conversation->contact_phone,
                'type'              => 'text',
                'text'              => ['body' => $text],
            ]);

        return $this->recordOutboundMessage($conversation, $response, [
            'message_type' => 'text',
            'content'      => $text,
            'is_template'  => false,
        ]);
    }

    /**
     * Envía un mensaje de plantilla aprobada por Meta Business Manager —
     * obligatorio para iniciar conversación (fuera de la ventana de 24h) o
     * cuando no hay ventana abierta. $params son las variables posicionales
     * {{1}}, {{2}}... del cuerpo de la plantilla.
     */
    public function sendTemplateMessage(WhatsappConversation $conversation, string $templateName, array $params = [], string $languageCode = 'es_MX'): WhatsappMessage
    {
        $account = $conversation->account;

        $components = [];
        if (!empty($params)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params),
            ];
        }

        $response = Http::withToken($account->access_token)
            ->post($this->baseUrl($account) . '/messages', [
                'messaging_product' => 'whatsapp',
                'to'                => $conversation->contact_phone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $templateName,
                    'language'   => ['code' => $languageCode],
                    'components' => $components,
                ],
            ]);

        return $this->recordOutboundMessage($conversation, $response, [
            'message_type'  => 'template',
            'content'       => $templateName . (empty($params) ? '' : (' | ' . implode(', ', $params))),
            'is_template'   => true,
            'template_name' => $templateName,
        ]);
    }

    private function recordOutboundMessage(WhatsappConversation $conversation, \Illuminate\Http\Client\Response $response, array $attributes): WhatsappMessage
    {
        if (!$response->successful()) {
            Log::warning('WhatsappService: envío fallido', [
                'conversation_id' => $conversation->id,
                'status'          => $response->status(),
                'body'            => $response->json() ?? $response->body(),
            ]);
        }

        $externalId = $response->json('messages.0.id');

        $message = $conversation->messages()->create(array_merge([
            'sender_type'          => WhatsappMessage::SENDER_AGENT,
            'external_message_id'  => $externalId,
            'sent_at'               => now(),
        ], $attributes));

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * WhatsApp solo permite mensajería de texto libre dentro de las 24h
     * siguientes al ÚLTIMO mensaje del CONTACTO (no del agente) — fuera de
     * esa ventana, solo plantillas aprobadas. Sin mensajes entrantes
     * todavía, se considera cerrada (hay que abrir con plantilla).
     */
    public function isWithin24hWindow(WhatsappConversation $conversation): bool
    {
        $lastInbound = $conversation->lastInboundMessage;

        if (!$lastInbound || !$lastInbound->sent_at) {
            return false;
        }

        return $lastInbound->sent_at->diffInHours(now()) < 24;
    }

    /**
     * Procesa el payload del webhook de Meta (formato estándar de la Cloud
     * API: entry[].changes[].value.{messages[],statuses[],contacts[]}).
     * Crea/actualiza WhatsappConversation + WhatsappMessage reales.
     * Se invoca desde ProcessWhatsappWebhookJob (encolado), nunca
     * directamente desde el controller, para responder rápido a Meta.
     */
    public function receiveWebhookPayload(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

                $account = $phoneNumberId
                    ? WhatsappAccount::where('phone_number_id', $phoneNumberId)->first()
                    : null;

                if (!$account) {
                    Log::warning('WhatsappService: webhook para phone_number_id desconocido', [
                        'phone_number_id' => $phoneNumberId,
                    ]);
                    continue;
                }

                $contactsByWaId = collect($value['contacts'] ?? [])->keyBy('wa_id');

                foreach (($value['messages'] ?? []) as $incoming) {
                    $this->storeInboundMessage($account, $incoming, $contactsByWaId->get($incoming['from'] ?? null));
                }

                foreach (($value['statuses'] ?? []) as $status) {
                    $this->applyStatusUpdate($status);
                }
            }
        }
    }

    private function storeInboundMessage(WhatsappAccount $account, array $incoming, ?array $contact): void
    {
        $phone = $incoming['from'] ?? null;
        if (!$phone) {
            return;
        }

        $conversation = WhatsappConversation::where('account_id', $account->id)
            ->where('contact_phone', $phone)
            ->first();

        if (!$conversation) {
            $customer = Customer::where('phone', $phone)->first();

            $conversation = WhatsappConversation::create([
                'account_id'    => $account->id,
                'customer_id'   => $customer?->id,
                'contact_phone' => $phone,
                'status'        => 'open',
                'started_at'    => now(),
                'unread_count'  => 0,
            ]);
        }

        $sentAt = isset($incoming['timestamp']) ? \Illuminate\Support\Carbon::createFromTimestamp((int) $incoming['timestamp']) : now();

        $type = $incoming['type'] ?? 'text';
        $content = match ($type) {
            'text'     => $incoming['text']['body'] ?? null,
            'button'   => $incoming['button']['text'] ?? null,
            'interactive' => $incoming['interactive']['button_reply']['title']
                ?? $incoming['interactive']['list_reply']['title']
                ?? null,
            default    => null,
        };
        $mediaUrl = $incoming[$type]['id'] ?? null; // Meta entrega un media id, no una URL directa; se resuelve aparte si se necesita.

        $conversation->messages()->create([
            'sender_type'          => WhatsappMessage::SENDER_CONTACT,
            'message_type'         => $type,
            'content'              => $content,
            'media_url'            => is_string($mediaUrl) ? $mediaUrl : null,
            'external_message_id'  => $incoming['id'] ?? null,
            'is_template'          => false,
            'sent_at'              => $sentAt,
        ]);

        $conversation->update([
            'last_message_at' => $sentAt,
            'unread_count'    => $conversation->unread_count + 1,
        ]);
    }

    private function applyStatusUpdate(array $status): void
    {
        $externalId = $status['id'] ?? null;
        if (!$externalId) {
            return;
        }

        $message = WhatsappMessage::where('external_message_id', $externalId)->first();
        if (!$message) {
            return;
        }

        $timestamp = isset($status['timestamp'])
            ? \Illuminate\Support\Carbon::createFromTimestamp((int) $status['timestamp'])
            : now();

        match ($status['status'] ?? null) {
            'delivered' => $message->update(['delivered_at' => $timestamp]),
            'read'      => $message->update(['read_at' => $timestamp]),
            default     => null,
        };
    }
}
