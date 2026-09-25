<?php

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'quote_number'          => $this->quote_number,
            'status'                => $this->status,
            'status_label'          => Quote::statusLabel($this->status),
            'customer_id'           => $this->customer_id,
            // Solo los campos seguros de exponer -- nunca password_hash
            // (Customer::$hidden ya lo cubre, pero se listan explícitamente
            // aquí para no depender de eso si el modelo cambia).
            'customer'              => $this->whenLoaded('customer', fn () => [
                'id'         => $this->customer->id,
                'first_name' => $this->customer->first_name,
                'last_name'  => $this->customer->last_name,
                'email'      => $this->customer->email,
                'phone'      => $this->customer->phone,
                'company'    => $this->customer->company,
                'rfc'        => $this->customer->rfc,
            ]),
            'guest_name'            => $this->guest_name,
            'guest_email'           => $this->guest_email,
            'guest_phone'           => $this->guest_phone,
            'guest_company'         => $this->guest_company,
            'guest_rfc'             => $this->guest_rfc,
            'currency'              => $this->currency,
            'exchange_rate'         => $this->exchange_rate,
            'subtotal'              => $this->subtotal,
            'discount_total'        => $this->discount_total,
            'tax_rate'              => $this->tax_rate,
            'tax_total'             => $this->tax_total,
            'isr_retention_rate'    => $this->isr_retention_rate,
            'isr_retention_total'   => $this->isr_retention_total,
            'total'                 => $this->total,
            'valid_until'           => $this->valid_until,
            'sent_at'               => $this->sent_at,
            'notes'                 => $this->notes,
            'terms_conditions'      => $this->terms_conditions,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
            'items'                 => QuoteItemResource::collection($this->whenLoaded('items')),
            // Pedido(s) de venta generados por el auto-split al aceptar (ver
            // QuoteService::processAcceptance()) -- solo presente cuando el
            // caller pidió cargar la relación (accept()/show() con ?with=).
            'sales_orders'          => SalesOrderResource::collection($this->whenLoaded('salesOrders')),
        ];
    }
}
