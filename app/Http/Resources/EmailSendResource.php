<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailSendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'email_campaign_id'  => $this->email_campaign_id,
            'customer_id'        => $this->customer_id,
            'guest_email'        => $this->guest_email,
            'guest_name'         => $this->guest_name,
            'recipient_email'    => $this->recipientEmail(),
            'sent_at'            => $this->sent_at,
            'opened_at'          => $this->opened_at,
            'clicked_at'         => $this->clicked_at,
            'bounced_at'         => $this->bounced_at,
            'unsubscribed_at'    => $this->unsubscribed_at,
        ];
    }
}
