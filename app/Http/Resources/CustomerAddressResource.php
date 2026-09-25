<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'customer_id'    => $this->customer_id,
            'label'          => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone'          => $this->phone,
            'country'        => $this->country,
            'state'          => $this->state,
            'city'           => $this->city,
            'postal_code'    => $this->postal_code,
            'address_line1'  => $this->address_line1,
            'address_line2'  => $this->address_line2,
            'reference'      => $this->reference,
            'is_default'     => (bool) $this->is_default,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
