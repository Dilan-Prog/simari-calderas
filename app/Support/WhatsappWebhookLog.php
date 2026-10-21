<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Bitácora ligera (en caché, sin migración) de lo que llega al webhook público de
 * WhatsApp (Meta Cloud API), para que el panel "Probar conexión" de Integraciones
 * pueda decir si Meta ya verificó la URL, si están llegando eventos y por qué se
 * rechazó una llamada (token de verificación o firma incorrectos).
 *
 * Solo guarda metadatos (fecha, tipo, conteos, motivo): nunca el contenido de los
 * mensajes ni credenciales.
 */
class WhatsappWebhookLog
{
    private const TTL_DAYS = 30;

    private const KEY_VERIFIED = 'whatsapp.webhook.verified_at';
    private const KEY_VERIFY_FAILED = 'whatsapp.webhook.verify_failed';
    private const KEY_REJECTED = 'whatsapp.webhook.rejected';
    private const KEY_EVENT = 'whatsapp.webhook.last_event.';

    private static function put(string $key, mixed $value): void
    {
        try {
            Cache::put($key, $value, now()->addDays(self::TTL_DAYS));
        } catch (\Throwable $e) {
            // Sin caché disponible: la bitácora nunca debe romper el webhook.
        }
    }

    private static function get(string $key): mixed
    {
        try {
            return Cache::get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Meta hizo el GET de verificación y el token coincidió. */
    public static function markVerified(): void
    {
        self::put(self::KEY_VERIFIED, now()->toIso8601String());
    }

    /** Meta (o alguien) intentó verificar con un token que no coincide con ninguna cuenta. */
    public static function markVerifyFailed(): void
    {
        self::put(self::KEY_VERIFY_FAILED, now()->toIso8601String());
    }

    /** POST rechazado por firma X-Hub-Signature-256 inválida o ausente. */
    public static function markRejected(string $reason): void
    {
        self::put(self::KEY_REJECTED, ['at' => now()->toIso8601String(), 'reason' => $reason]);
    }

    /**
     * Registra un POST válido: por cada phone_number_id del payload guarda el
     * último evento (mensajes y/o estados, con su cantidad).
     */
    public static function recordEvent(array $payload): void
    {
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $phoneNumberId = (string) ($value['metadata']['phone_number_id'] ?? '');

                self::put(self::KEY_EVENT . ($phoneNumberId !== '' ? $phoneNumberId : 'unknown'), [
                    'at'       => now()->toIso8601String(),
                    'field'    => (string) ($change['field'] ?? ''),
                    'messages' => count($value['messages'] ?? []),
                    'statuses' => count($value['statuses'] ?? []),
                ]);
            }
        }
    }

    public static function verifiedAt(): ?string
    {
        return self::get(self::KEY_VERIFIED);
    }

    public static function verifyFailedAt(): ?string
    {
        return self::get(self::KEY_VERIFY_FAILED);
    }

    /** @return array{at:string, reason:string}|null */
    public static function lastRejected(): ?array
    {
        return self::get(self::KEY_REJECTED);
    }

    /** @return array{at:string, field:string, messages:int, statuses:int}|null */
    public static function lastEvent(?string $phoneNumberId): ?array
    {
        return self::get(self::KEY_EVENT . (filled($phoneNumberId) ? $phoneNumberId : 'unknown'));
    }
}
