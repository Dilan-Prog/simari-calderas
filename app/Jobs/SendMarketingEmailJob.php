<?php

namespace App\Jobs;

use App\Mail\MarketingEmailMailable;
use App\Models\EmailSend;
use App\Services\EmailCampaignService;
use App\Services\EmailTemplateService;
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
            $this->send->loadMissing('workflowStep', 'customer');

            $templateId = $this->send->workflowStep->action_config['template_id'] ?? null;
            $template   = $templateId ? \App\Models\EmailTemplate::find($templateId) : null;

            if (!$template) {
                return;
            }

            $rendered = app(EmailTemplateService::class)->render($template, $this->send->customer);

            // Mismo reemplazo de token de unsubscribe y pixel de tracking que
            // en las ramas de campaña/secuencia arriba.
            $unsubscribeUrl = route('email.unsubscribe', ['token' => $this->send->tracking_token]);
            $rendered['html'] = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $rendered['html']);

            $openTrackingUrl = route('email.open', ['token' => $this->send->tracking_token]);
            $trackingPixel   = '<img src="' . $openTrackingUrl . '" width="1" height="1" style="display:none;" alt="" />';
            $rendered['html'] .= $trackingPixel;
        } else {
            return;
        }

        Mail::to($this->send->customer->email)->send(new MarketingEmailMailable($rendered));

        $this->send->sent_at = now();
        $this->send->save();
    }
}
