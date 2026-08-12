<?php

namespace App\Services;

use App\Models\CustomerEmailSubscription;
use App\Models\EmailLinkClick;
use App\Models\EmailSend;

class EmailTrackingService
{
    /**
     * Registra la apertura de un correo a partir de su tracking token.
     * Llamado desde una ruta pública sin auth: si el token no existe o ya
     * estaba marcado como abierto, simplemente no hace nada.
     */
    public function recordOpen(string $trackingToken): void
    {
        $send = EmailSend::where('tracking_token', $trackingToken)->first();

        if (! $send || ! is_null($send->opened_at)) {
            return;
        }

        $send->update(['opened_at' => now()]);
    }

    /**
     * Registra el click sobre un enlace de un correo y retorna la URL real
     * a la que se debe redirigir. Si el EmailSend no existe, retorna la
     * misma URL para no romper la redirección aunque el tracking falle.
     */
    public function recordClick(string $trackingToken, string $url): string
    {
        $send = EmailSend::where('tracking_token', $trackingToken)->first();

        if (! $send) {
            return $url;
        }

        if (is_null($send->clicked_at)) {
            $send->update(['clicked_at' => now()]);
        }

        EmailLinkClick::create([
            'email_send_id' => $send->id,
            'url'           => $url,
            'clicked_at'    => now(),
        ]);

        return $url;
    }

    /**
     * Marca el EmailSend como unsubscribed y actualiza (o crea) la
     * suscripción del cliente para dejarla como no suscrito.
     */
    public function unsubscribe(string $trackingToken): void
    {
        $send = EmailSend::where('tracking_token', $trackingToken)->first();

        if (! $send) {
            return;
        }

        $send->update(['unsubscribed_at' => now()]);

        CustomerEmailSubscription::updateOrCreate(
            ['customer_id' => $send->customer_id],
            ['subscribed' => false, 'unsubscribed_at' => now()]
        );
    }
}
