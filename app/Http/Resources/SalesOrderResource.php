<?php

namespace App\Http\Resources;

use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'order_number' => $this->order_number,
            'quote_id'     => $this->quote_id,
            'customer_id'  => $this->customer_id,
            'status'       => $this->status,
            'status_label' => SalesOrder::statusLabel($this->status),
            'notes'        => $this->notes,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'items'        => SalesOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
