<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WhatsappMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'conversation_id'      => $this->conversation_id,
            'sender_type'          => $this->sender_type,
            'message_type'         => $this->message_type,
            'content'              => $this->content,
            'media_url'            => $this->media_url,
            'is_template'          => $this->is_template,
            'template_name'        => $this->template_name,
            'external_message_id'  => $this->external_message_id,
            'sent_at'              => $this->sent_at,
            'delivered_at'         => $this->delivered_at,
            'read_at'              => $this->read_at,
            'created_at'           => $this->created_at,
        ];
    }
}
