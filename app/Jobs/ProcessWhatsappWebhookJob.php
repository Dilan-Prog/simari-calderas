<?php

namespace App\Jobs;

use App\Services\WhatsappService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Procesa el payload de un webhook de Meta (WhatsApp Cloud API) de forma
 * asíncrona — el controller público solo valida la firma/estructura mínima
 * y encola esto, para responder 200 a Meta rápido y no arriesgar timeouts
 * si el procesamiento (crear conversación/mensaje, disparar triggers de
 * automatización) tarda.
 */
class ProcessWhatsappWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $payload) {}

    public function handle(WhatsappService $whatsapp): void
    {
        $whatsapp->receiveWebhookPayload($this->payload);
    }
}
