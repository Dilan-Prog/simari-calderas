<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'product_id'         => $this->product_id,
            'product_name'       => $this->product_name,
            'product_sku'        => $this->product_sku,
            'unit'               => $this->unit,
            'quantity_ordered'   => $this->quantity_ordered,
            'quantity_delivered' => $this->quantity_delivered,
            'pending'            => $this->pending,
            'sort_order'         => $this->sort_order,
        ];
    }
}
