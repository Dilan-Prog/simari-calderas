<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint público del webhook del microservicio Node.js de WhatsApp vía
 * Baileys/QR (whatsapp-qr-service/, repo aparte) — sin sesión ni CSRF, mismo
 * patrón que WhatsappWebhookController (Meta) y EmailBounceWebhookController
 * (Hostinger): registrado en routes/web.php, excluido de CSRF en
 * VerifyCsrfToken::$except.
 *
 * Autenticación vía "Authorization: Bearer <services.whatsapp_qr.secret>" —
 * si no hay secreto configurado todavía, se omite la verificación (mismo
 * criterio de "no romper por falta de configuración" que ya usan
 * WhatsappWebhookController/EmailBounceWebhookController).
 */
class WhatsappQrWebhookController extends Controller
{
    public function __construct(private WhatsappService $whatsappService) {}

    public function receive(Request $request)
    {
        $expectedSecret = config('services.whatsapp_qr.secret');

        if ($expectedSecret && $request->bearerToken() !== $expectedSecret) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        $sessionId = $request->input('session_id');
        $event = $request->input('event');

        $account = $sessionId
            ? WhatsappAccount::where('session_id', $sessionId)->first()
            : null;

        if (!$account) {
            Log::warning('WhatsappQrWebhookController: webhook para session_id desconocido', [
                'session_id' => $sessionId,
                'event'      => $event,
            ]);

            return response()->json(['received' => true]);
        }

        match ($event) {
            'message' => $this->handleMessage($account, $request),
            'status'  => $this->handleStatus($account, $request),
            default   => Log::warning('WhatsappQrWebhookController: evento desconocido', [
                'session_id' => $sessionId,
                'event'      => $event,
            ]),
        };

        return response()->json(['received' => true]);
    }

    private function handleMessage(WhatsappAccount $account, Request $request): void
    {
        $from = $request->input('from');

        if (!$from) {
            return;
        }

        $timestamp = $request->input('timestamp');

        $this->whatsappService->ingestInboundMessage($account, $from, [
            'message_type'        => 'text',
            'content'              => $request->input('body'),
            'external_message_id' => $request->input('external_message_id'),
            'sent_at'              => $timestamp ? Carbon::createFromTimestamp((int) $timestamp) : now(),
        ]);
    }

    private function handleStatus(WhatsappAccount $account, Request $request): void
    {
        $account->update(['session_status' => $request->input('status')]);
    }
}
