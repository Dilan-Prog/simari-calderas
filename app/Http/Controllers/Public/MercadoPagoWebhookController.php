<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMercadoPagoWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint público del webhook de Mercado Pago, sin ningún guard de
 * autenticación — Mercado Pago llama esto directamente. Mismo patrón que
 * WhatsappWebhookController/EmailBounceWebhookController: registrado en
 * routes/web.php, no en api.php.
 */
class MercadoPagoWebhookController extends Controller
{
    /**
     * POST de notificación (IPN / webhook): Mercado Pago manda
     * ?data.id=X&type=payment en la URL, y el header x-request-id por
     * separado. Nunca se confía en el status del payload -- se encola el
     * procesamiento real (ProcessMercadoPagoWebhookJob), que hace la
     * llamada server-to-server de verdad, y se responde 200 inmediato
     * (Mercado Pago reintenta agresivo si no recibe 200 rápido).
     */
    public function receive(Request $request): Response
    {
        if (!$this->hasValidSignature($request)) {
            return response('Unauthorized', 401);
        }

        $mpPaymentId = $request->query('data.id');

        // Distingue el webhook clásico de Pagos (type=payment, data.id=id de
        // pago, rol Tarjeta/api) del nuevo de Orders (type=order, data.id=id
        // de orden, rol checkout_pro); si MP no manda type se asume el
        // formato clásico por compatibilidad hacia atrás.
        $resourceType = (string) $request->query('type', 'payment');

        ProcessMercadoPagoWebhookJob::dispatch($mpPaymentId, $request->all(), $resourceType);

        return response('OK', 200);
    }

    /**
     * Verifica el header x-signature (formato "ts=<timestamp>,v1=<hash>")
     * -- mismo criterio "sin secreto configurado, se omite verificación"
     * que WhatsappWebhookController::hasValidSignature(), para no romper
     * instalaciones a medio migrar.
     *
     * TODO-VERIFICAR-FORMATO-MP: el manifest armado abajo
     * ("id:{data.id};request-id:{x-request-id};ts:{ts};") sigue el formato
     * documentado públicamente por Mercado Pago para "Cómo implementar
     * notificaciones webhook" (data.id + x-request-id + ts del propio
     * header x-signature), pero no se pudo confirmar contra una llamada
     * real de su servidor en este entorno (sin acceso a su documentación
     * ni a credenciales de prueba). Revisar contra la documentación real
     * de Mercado Pago antes de producción.
     */
    private function hasValidSignature(Request $request): bool
    {
        // Cada rol (Tarjeta/api, Checkout Pro) es una Aplicación distinta en
        // el panel de Mercado Pago, y MP asigna la firma secreta del webhook
        // POR APLICACIÓN -- así que puede haber hasta N secretos válidos, uno
        // por rol configurado. El payload no indica de qué Aplicación viene
        // la notificación, así que se acepta si la firma coincide con
        // CUALQUIERA de los secretos configurados.
        $secrets = collect(config('services.mercadopago.roles', []))
            ->map(fn ($role) => config("services.mercadopago.{$role}.webhook_secret"))
            ->filter()
            ->values();

        if ($secrets->isEmpty()) {
            return true;
        }

        $signatureHeader = (string) $request->header('x-signature', '');

        if (blank($signatureHeader)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $chunk) {
            [$partKey, $partValue] = array_pad(explode('=', $chunk, 2), 2, null);
            if ($partKey !== null) {
                $parts[trim($partKey)] = trim((string) $partValue);
            }
        }

        $ts = $parts['ts'] ?? null;
        $providedHash = $parts['v1'] ?? null;

        if (blank($ts) || blank($providedHash)) {
            return false;
        }

        // Los IDs de pago clásicos son numéricos, pero los de la API de
        // Orders (rol checkout_pro) son alfanuméricos con mayúsculas (ej.
        // "ORDTST01M..."). La documentación de Mercado Pago exige convertir
        // data.id a minúsculas antes de construir el manifest si viene con
        // mayúsculas -- sin esto, todo webhook de Checkout Pro se rechazaría
        // con 401 en cuanto hubiera un webhook_secret configurado, aunque
        // viniera legítimo de Mercado Pago.
        $dataId = strtolower((string) $request->query('data.id', $request->input('data.id')));
        $requestId = (string) $request->header('x-request-id', '');

        // TODO-VERIFICAR-FORMATO-MP: manifest según el patrón documentado
        // por Mercado Pago -- confirmar el orden/separadores exactos antes
        // de depender de esto en producción.
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";

        foreach ($secrets as $secret) {
            $expectedHash = hash_hmac('sha256', $manifest, $secret);
            if (hash_equals($expectedHash, $providedHash)) {
                return true;
            }
        }

        return false;
    }
}
