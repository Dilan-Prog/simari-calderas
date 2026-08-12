<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\EmailTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Endpoints públicos de tracking de correos (apertura, clicks, baja).
 * No dependen de ningún guard de autenticación: se acceden directamente
 * desde el cliente de correo del destinatario a través del tracking_token.
 */
class EmailTrackingController extends Controller
{
    /**
     * Pixel de tracking de apertura: retorna un GIF transparente 1x1.
     */
    public function open(string $token): Response
    {
        app(EmailTrackingService::class)->recordOpen($token);

        // GIF transparente 1x1 codificado en base64.
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');

        return response()->make($pixel, 200, [
            'Content-Type'  => 'image/gif',
            'Content-Length' => (string) strlen($pixel),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Registra el click sobre un enlace del correo y redirige a la URL real.
     */
    public function click(Request $request, string $token)
    {
        $url = (string) $request->query('url', '');

        if ($url === '') {
            return redirect('/');
        }

        $url = app(EmailTrackingService::class)->recordClick($token, $url);

        return redirect($url, 302);
    }

    /**
     * Da de baja al cliente asociado al EmailSend del token.
     */
    public function unsubscribe(string $token)
    {
        app(EmailTrackingService::class)->unsubscribe($token);

        return view('tracking.unsubscribed');
    }
}
