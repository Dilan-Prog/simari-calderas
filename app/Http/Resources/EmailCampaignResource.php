<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'template_id'      => $this->template_id,
            'list_id'          => $this->list_id,
            'status'           => $this->status,
            'scheduled_at'     => $this->scheduled_at,
            'sent_at'          => $this->sent_at,
            'ab_variant_of'    => $this->ab_variant_of,
            'ab_split_percent' => $this->ab_split_percent,
            'open_rate'        => $this->openRate(),
            'click_rate'       => $this->clickRate(),
        ];
    }
}
