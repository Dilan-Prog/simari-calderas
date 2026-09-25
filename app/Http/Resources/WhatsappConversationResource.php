<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'account_id'        => $this->account_id,
            'customer_id'       => $this->customer_id,
            'pipeline_id'       => $this->pipeline_id,
            'pipeline_stage_id' => $this->pipeline_stage_id,
            'deal_id'           => $this->deal_id,
            'assigned_user_id'  => $this->assigned_user_id,
            'contact_phone'     => $this->contact_phone,
            'status'            => $this->status,
            'unread_count'      => $this->unread_count,
            'started_at'        => $this->started_at,
            'last_message_at'   => $this->last_message_at,
        ];
    }
}
