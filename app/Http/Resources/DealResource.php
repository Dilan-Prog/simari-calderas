<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 3 (API N8N) — CRM. Expone explícitamente los campos de $fillable de
 * Deal (app/Models/Deal.php); no hay columnas sensibles en este modelo, pero
 * se listan a mano de todas formas (mismo criterio que WorkflowResource) en
 * vez de Model::toArray() crudo, para no acoplar el contrato de la API a
 * cambios futuros de columnas internas del modelo.
 */
class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'folio'                  => $this->folio,
            'pipeline_id'            => $this->pipeline_id,
            'pipeline_stage_id'      => $this->pipeline_stage_id,
            'name'                   => $this->name,
            'amount'                 => $this->amount,
            'currency'               => $this->currency,
            'expected_close_date'    => $this->expected_close_date,
            'closed_at'              => $this->closed_at,
            'owner_id'               => $this->owner_id,
            'customer_id'            => $this->customer_id,
            'company_snapshot'       => $this->company_snapshot,
            'contact_snapshot_name'  => $this->contact_snapshot_name,
            'contact_snapshot_email' => $this->contact_snapshot_email,
            'contact_snapshot_phone' => $this->contact_snapshot_phone,
            'source'                 => $this->source,
            'status'                 => $this->status,
            'lost_reason'            => $this->lost_reason,
            'notes'                  => $this->notes,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
