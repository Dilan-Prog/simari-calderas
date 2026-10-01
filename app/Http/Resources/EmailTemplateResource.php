<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'subject'      => $this->subject,
            'type'         => $this->type,
            'is_system'    => (bool) $this->is_system,
            'system_key'   => $this->system_key,
            'builder_mode' => $this->builder_mode,
            // Variables tipo {{nombre}} vienen sin sustituir -- es la
            // plantilla cruda, igual que se guarda/edita en el panel admin.
            'html_body'    => $this->html_body,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
