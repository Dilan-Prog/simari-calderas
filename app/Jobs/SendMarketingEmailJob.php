<?php

namespace App\Jobs;

use App\Mail\MarketingEmailMailable;
use App\Models\EmailSend;
use App\Models\Quote;
use App\Services\EmailCampaignService;
use App\Services\EmailTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMarketingEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly EmailSend $send) {}

    public function handle(): void
    {
        if ($this->send->email_campaign_id !== null) {
            $this->send->loadMissing('campaign', 'customer');

            $rendered = app(EmailCampaignService::class)->renderForRecipient(
                $this->send->campaign,
                $this->send
            );
        } elseif ($this->send->email_sequence_step_id !== null) {
            $this->send->loadMissing('sequenceStep.template', 'customer');

            $rendered = app(EmailTemplateService::class)->render(
                $this->send->sequenceStep->template,
                $this->send->customer
            );

            // Reemplazo del token de unsubscribe y del pixel de tracking,
            // igual que hace EmailCampaignService::renderForRecipient. Se
            // duplica aquí a propósito para v1 (ver comentario en esa clase
            // sobre por qué EmailTemplateService::render no puede hacerlo).
            $unsubscribeUrl = route('email.unsubscribe', ['token' => $this->send->tracking_token]);
            $rendered['html'] = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $rendered['html']);

            $openTrackingUrl = route('email.open', ['token' => $this->send->tracking_token]);
            $trackingPixel   = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
            $rendered['html'] .= $trackingPixel;
        } elseif ($this->send->workflow_step_id !== null) {
            $this->send->loadMissing('workflowStep', 'customer', 'enrollment.enrollable');

            $templateId = $this->send->workflowStep->action_config['template_id'] ?? null;
            $template   = $templateId ? \App\Models\EmailTemplate::find($templateId) : null;

            if (!$template) {
                return;
            }

            $enrollable = $this->send->enrollment?->enrollable;
            $quote      = $enrollable instanceof Quote ? $enrollable : null;

            $rendered = app(EmailTemplateService::class)->render(
                $template,
                $this->send->customer,
                null,
                $quote,
                $this->send->customer_id === null ? $this->send->guest_name : null,
                $this->send->customer_id === null ? $this->send->guest_email : null
            );

            // Mismo reemplazo de token de unsubscribe y pixel de tracking que
            // en las ramas de campaña/secuencia arriba.
            $unsubscribeUrl = route('email.unsubscribe', ['token' => $this->send->tracking_token]);
            $rendered['html'] = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $rendered['html']);

            $openTrackingUrl = route('email.open', ['token' => $this->send->tracking_token]);
            $trackingPixel   = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
            $rendered['html'] .= $trackingPixel;

            // Adjunta el PDF de la Quote cuando el paso lo pide vía
            // action_config.attach_source = 'quote_pdf'. Mismo mecanismo que
            // QuoteMail::attachments() (misma vista, mismos datos), pero
            // generando el contenido crudo para pasarlo a
            // MarketingEmailMailable en vez de usar ->attach() directo.
            if ($this->send->attach_source === 'quote_pdf' && $quote !== null) {
                $pdf = Pdf::loadView('admin.quotes.pdf', ['quote' => $quote])->setPaper('a4', 'portrait');

                $pdfAttachment = [
                    'content'  => $pdf->output(),
                    'filename' => "{$quote->quote_number}.pdf",
                    'mime'     => 'application/pdf',
                ];
            }
        } else {
            return;
        }

        $recipientEmail = $this->send->recipientEmail();

        if (!$recipientEmail) {
            return;
        }

        Mail::to($recipientEmail)->send(new MarketingEmailMailable($rendered, $pdfAttachment ?? null));

        $this->send->sent_at = now();
        $this->send->save();
    }
}
