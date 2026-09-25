<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'workflow_id'      => $this->workflow_id,
            'enrollable_type'  => $this->enrollable_type,
            'enrollable_id'    => $this->enrollable_id,
            'current_step_id'  => $this->current_step_id,
            'status'           => $this->status,
            'enrolled_at'      => $this->enrolled_at,
            'completed_at'     => $this->completed_at,
        ];
    }
}
