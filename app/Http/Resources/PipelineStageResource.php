<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 3 (API N8N) — CRM. Solo lectura: expone las etapas de un Pipeline
 * para que N8N sepa a qué pipeline_stage_id mover un Deal.
 */
class PipelineStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'pipeline_id'     => $this->pipeline_id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'order'           => $this->order,
            'probability'     => $this->probability,
            'wip_limit'       => $this->wip_limit,
            'is_won'          => $this->is_won,
            'is_lost'         => $this->is_lost,
            'required_fields' => $this->required_fields,
        ];
    }
}
