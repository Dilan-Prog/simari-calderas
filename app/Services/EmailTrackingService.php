<?php

namespace App\Services;

use App\Mail\MarketingEmailMailable;
use App\Models\CustomerEmailSubscription;
use App\Models\EmailLinkClick;
use App\Models\EmailSend;
use Illuminate\Support\Facades\Mail;

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

    /**
     * Reescribe los href="..." de los <a> dentro de $html para que pasen por
     * el redirect de click-tracking (email.click) antes de llegar a su
     * destino real. No toca mailto:, tel:, anclas (#...) ni el link de
     * unsubscribe (que ya tiene su propio mecanismo de tracking).
     *
     * Función pura: sin efectos secundarios, solo string in -> string out.
     */
    public function wrapLinksForTracking(string $html, string $token): string
    {
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=(["\'])(.*?)\2([^>]*)>/is',
            function (array $matches) use ($token) {
                [$full, $before, $quote, $href, $after] = $matches;

                $trimmedHref = trim($href);

                if ($trimmedHref === ''
                    || stripos($trimmedHref, 'mailto:') === 0
                    || stripos($trimmedHref, 'tel:') === 0
                    || str_starts_with($trimmedHref, '#')
                    || str_contains($trimmedHref, '/e/unsubscribe/')
                ) {
                    return $full;
                }

                $trackedUrl = route('email.click', [
                    'token' => $token,
                    'url'   => urlencode($trimmedHref),
                ]);

                return '<a ' . $before . 'href=' . $quote . $trackedUrl . $quote . $after . '>';
            },
            $html
        );
    }

    /**
     * Helper compartido para envíos manuales de correo (Cotizaciones,
     * prueba de SMTP en Integraciones, etc.) que NO provienen de una
     * campaña/secuencia/workflow -- esos tres orígenes ya tienen su propio
     * flujo de tracking en SendMarketingEmailJob/EmailCampaignService.
     *
     * Crea el EmailSend (con su tracking_token autogenerado), envuelve los
     * links del html para click-tracking, agrega el pixel de open-tracking y
     * envía el correo vía MarketingEmailMailable.
     *
     * @param array{subject: string, html: string} $rendered
     * @param array{content: string, filename: string, mime: string}|null $pdfAttachment
     */
    public function sendTracked(
        string $recipientEmail,
        array $rendered,
        array $emailSendAttributes,
        ?array $pdfAttachment = null
    ): EmailSend {
        $emailSend = EmailSend::create(array_merge($emailSendAttributes, [
            'sent_at' => now(),
        ]));

        $trackedHtml = $this->wrapLinksForTracking($rendered['html'], $emailSend->tracking_token);

        $openTrackingUrl = route('email.open', ['token' => $emailSend->tracking_token]);
        $trackingPixel   = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
        $trackedHtml    .= $trackingPixel;

        Mail::to($recipientEmail)->send(new MarketingEmailMailable([
            'subject' => $rendered['subject'],
            'html'    => $trackedHtml,
        ], $pdfAttachment));

        return $emailSend;
    }
}
