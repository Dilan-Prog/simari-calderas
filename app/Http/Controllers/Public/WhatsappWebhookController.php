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
     *
     * Antes de encolar se verifica la firma del header
     * X-Hub-Signature-256 (formato "sha256=<hex>") contra el app_secret de
     * CUALQUIER cuenta que lo tenga configurado, con hash_equals() para
     * comparación segura contra timing attacks — mismo criterio "matches
     * ANY account" que verify() ya usa, porque Meta no indica a qué WABA
     * pertenece la llamada. Si NINGUNA cuenta tiene app_secret configurado
     * todavía, se omite la verificación por completo (no rompe instalaciones
     * existentes a medio migrar).
     */
    public function receive(Request $request): Response
    {
        if (!$this->hasValidSignature($request)) {
            return response('Forbidden', 403);
        }

        $payload = $request->json()->all();

        if (!empty($payload)) {
            ProcessWhatsappWebhookJob::dispatch($payload);
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function hasValidSignature(Request $request): bool
    {
        $accountsWithSecret = WhatsappAccount::whereNotNull('encrypted_app_secret')->get()
            ->filter(fn (WhatsappAccount $account) => filled($account->app_secret));

        if ($accountsWithSecret->isEmpty()) {
            return true;
        }

        $signatureHeader = (string) $request->header('X-Hub-Signature-256', '');

        if (!str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $providedSignature = substr($signatureHeader, strlen('sha256='));
        $body = $request->getContent();

        foreach ($accountsWithSecret as $account) {
            $expectedSignature = hash_hmac('sha256', $body, $account->app_secret);

            if (hash_equals($expectedSignature, $providedSignature)) {
                return true;
            }
        }

        return false;
    }
}
