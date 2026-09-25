<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'type'                  => $this->type,
            'is_active'             => $this->is_active,
            'reenrollment_allowed'  => $this->reenrollment_allowed,
            'created_at'            => $this->created_at,
        ];
    }
}
