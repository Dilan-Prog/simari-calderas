<?php

namespace App\Services;

use App\Models\CustomerEmailSubscription;
use App\Models\EmailCampaign;
use App\Models\EmailSend;
use Illuminate\Support\Facades\DB;

class EmailCampaignService
{
    /**
     * Encola el envío de una campaña de email para todos los destinatarios
     * suscritos de su lista.
     *
     * Nunca envía de forma síncrona: por cada destinatario crea un EmailSend
     * y despacha SendMarketingEmailJob, que es quien realmente entrega el
     * correo (job creado en la fase de Jobs-Mail).
     */
    public function send(EmailCampaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            $campaign->status = 'sending';
            $campaign->save();

            $recipients = $campaign->list->resolveRecipients();

            $subscribedRecipients = $recipients->filter(
                fn ($customer) => CustomerEmailSubscription::isSubscribed($customer->id)
            );

            foreach ($subscribedRecipients as $recipient) {
                $send = EmailSend::create([
                    'email_campaign_id' => $campaign->id,
                    'customer_id'       => $recipient->id,
                ]);

                \App\Jobs\SendMarketingEmailJob::dispatch($send);
            }

            // Simplificación v1: marcamos la campaña como 'sent' en cuanto se
            // encolan todos los jobs, no cuando todos han terminado de
            // ejecutarse. En un sistema real, 'sent' debería reflejar que
            // TODOS los jobs de envío ya se procesaron (por ejemplo, vía un
            // batch de jobs o un contador de envíos completados).
            $campaign->status  = 'sent';
            $campaign->sent_at = now();
            $campaign->save();
        });
    }

    /**
     * Renderiza el asunto y html de una campaña para un envío (EmailSend)
     * específico, reemplazando el token de unsubscribe e inyectando el pixel
     * de tracking de apertura. Usado por el Job de envío.
     *
     * @return array{subject: string, html: string}
     */
    public function renderForRecipient(EmailCampaign $campaign, EmailSend $send): array
    {
        $rendered = app(EmailTemplateService::class)->render(
            $campaign->template,
            $send->customer
        );

        $unsubscribeUrl = route('email.unsubscribe', ['token' => $send->tracking_token]);

        $html = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $rendered['html']);

        $openTrackingUrl = route('email.open', ['token' => $send->tracking_token]);

        $trackingPixel = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" alt="" />';

        $html .= $trackingPixel;

        return [
            'subject' => $rendered['subject'],
            'html'    => $html,
        ];
    }
}
