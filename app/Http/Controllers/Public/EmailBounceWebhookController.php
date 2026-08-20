<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receptor del webhook del buzón de correo (Hostinger "Agentic Mail" / hMail,
 * evento message.received) — sin documentación pública del formato del
 * payload todavía (función en Beta), así que por ahora SOLO registra el
 * request completo (headers + cuerpo) en el log de canal 'email-bounce', sin
 * intentar interpretarlo.
 *
 * Una vez que llegue un rebote real y se vea la forma exacta del JSON, este
 * método se completa para: detectar que es un rebote (viene de
 * MAILER-DAEMON/Mail Delivery System), extraer el destinatario original y el
 * motivo, buscar el EmailSend correspondiente, y marcar bounced_at.
 */
class EmailBounceWebhookController extends Controller
{
    public function receive(Request $request)
    {
        // Hostinger autentica este webhook con "Authorization: Bearer <token>"
        // (token mostrado una sola vez al crearlo en su panel). Si no hay
        // token configurado todavía, se deja pasar sin verificar -- mismo
        // criterio de "no romper por falta de configuración" que ya usa
        // WhatsappWebhookController con app_secret.
        $expectedToken = config('services.hostinger_mail.webhook_token');

        if ($expectedToken && $request->bearerToken() !== $expectedToken) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        Log::channel('email-bounce')->info('Webhook de correo recibido', [
            'headers' => $request->headers->all(),
            'body'    => $request->getContent(),
        ]);

        return response()->json(['received' => true]);
    }
}
