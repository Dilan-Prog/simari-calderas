<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'service_page_id'   => $this->service_page_id,
            'product_name'      => $this->product_name,
            'product_sku'       => $this->product_sku,
            'quantity'          => $this->quantity,
            'unit_price'        => $this->unit_price,
            'discount_percent'  => $this->discount_percent,
            'tax_percent'       => $this->tax_percent,
            'line_total'        => $this->line_total,
            'notes'             => $this->notes,
            'sort_order'        => $this->sort_order,
        ];
    }
}
