<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsappWebhookJob;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoint público del webhook de Meta Cloud API (WhatsApp), sin ningún
 * guard de autenticación — Meta llama esto directamente. Mismo patrón que
 * EmailTrackingController (Frontend/email marketing): registrado en
 * routes/web.php, no admin.php.
 */
class WhatsappWebhookController extends Controller
{
    /**
     * GET de verificación que Meta hace una sola vez al configurar el
     * webhook: hub.mode=subscribe, hub.verify_token, hub.challenge. Se
     * responde con hub.challenge en texto plano si el token coincide con
     * el de ALGUNA cuenta activa (Meta no indica a qué cuenta pertenece la
     * verificación en este paso).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $tokenIsValid = filled($token)
            && WhatsappAccount::where('webhook_verify_token', $token)->exists();

        if ($mode === 'subscribe' && $tokenIsValid) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * POST de recepción: mensajes entrantes y actualizaciones de estado
     * (delivered/read). Se encola el procesamiento real vía
     * ProcessWhatsappWebhookJob (cola database) y se responde 200
     * inmediatamente — Meta reintenta agresivamente si no recibe 200 rápido.
     */
    public function receive(Request $request): Response
    {
        $payload = $request->json()->all();

        if (!empty($payload)) {
            ProcessWhatsappWebhookJob::dispatch($payload);
        }

        return response('EVENT_RECEIVED', 200);
    }
}
