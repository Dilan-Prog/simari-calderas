<?php

namespace App\Jobs;

use App\Models\GoogleConversion;
use App\Models\GoogleConversionUpload;
use App\Services\GoogleConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Compatibilidad con el panel legado (Google Ads > conversiones): cada fila de
 * google_conversions se sube por la misma ruta real que las del CRM
 * (GoogleConversionService / Data Manager API) y su estatus legado se
 * sincroniza: sent / failed / pending; 'stored' si no hay credenciales
 * (comportamiento anterior: solo queda guardada).
 */
class SendGoogleConversionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly GoogleConversion $conversion) {}

    public function handle(GoogleConversionService $service): void
    {
        $c = $this->conversion;

        $identifierType = $c->gclid ? 'gclid' : ($c->wbraid ? 'wbraid' : 'none');
        if ($identifierType === 'none') {
            $c->update(['status' => 'failed', 'error_message' => 'La conversión no tiene GCLID ni WBRAID.']);

            return;
        }

        $dedupe = 'legacy-' . $c->id;
        $upload = GoogleConversionUpload::firstOrCreate(
            ['dedupe_key' => $dedupe],
            [
                'legacy_conversion_id' => $c->id,
                'action'               => $c->conversion_name,
                'operation'            => 'upload',
                'order_id'             => $c->order_id ?: 'legacy-' . $c->id,
                'identifier_type'      => $identifierType,
                'identifier'           => $identifierType === 'gclid' ? $c->gclid : $c->wbraid,
                'conversion_date_time' => ($c->conversion_time ?? now())->copy()->utc(),
                'timezone'             => config('google_conversions.timezone', 'America/Mexico_City'),
                'conversion_value'     => $c->conversion_value,
                'currency'             => $c->currency_code ?: 'MXN',
                'status'               => 'pendiente',
            ]
        );

        if ($upload->status === 'fallida') {
            $upload->update(['status' => 'pendiente', 'attempts' => 0]); // el botón "reintentar" del panel legado
        }

        $upload = $service->process($upload);

        match ($upload->status) {
            'enviada'  => $c->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]),
            'fallida'  => $c->update(['status' => 'failed', 'error_message' => $upload->last_error]),
            'omitida'  => $c->update(['status' => 'failed', 'error_message' => $upload->last_error]),
            default    => $c->update([
                'status'        => $service->uploader()->isConfigured() ? 'pending' : 'stored',
                'error_message' => $service->uploader()->isConfigured() ? $upload->last_error : null,
            ]),
        };
    }
}
