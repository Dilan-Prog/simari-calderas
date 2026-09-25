<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberadamente NO expone tax_certificate_path (ruta de archivo en disco,
 * equivalente a lo que el plan llama "encrypted_payload" -- no aporta nada a
 * un consumidor externo y filtra estructura del servidor) ni datos de pago
 * con tarjeta (esos ni siquiera se guardan en este modelo, ver
 * MercadoPagoPayment / payments()). El resto de los campos fiscales (rfc,
 * razon_social, uso_cfdi, regimen_fiscal, cp_fiscal) sí se exponen: son
 * datos de facturación legítimos que una automatización de N8N necesitaría
 * para procesos de facturación.
 */
class StoreOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'order_number'           => $this->order_number,
            'customer_id'            => $this->customer_id,
            'contact_name'           => $this->contact_name,
            'contact_email'          => $this->contact_email,
            'contact_phone'          => $this->contact_phone,
            'shipping_address_line1' => $this->shipping_address_line1,
            'shipping_address_line2' => $this->shipping_address_line2,
            'shipping_reference'     => $this->shipping_reference,
            'shipping_city'          => $this->shipping_city,
            'shipping_state'         => $this->shipping_state,
            'shipping_postal_code'   => $this->shipping_postal_code,
            'shipping_country'       => $this->shipping_country,
            'payment_method_id'      => $this->payment_method_id,
            'subtotal'               => $this->subtotal,
            'shipping_total'         => $this->shipping_total,
            'discount_total'         => $this->discount_total,
            'tax_total'              => $this->tax_total,
            'total'                  => $this->total,
            'currency'               => $this->currency,
            'status'                 => $this->status,
            'terms_accepted_at'      => $this->terms_accepted_at,
            'notes'                  => $this->notes,
            'requires_invoice'       => $this->requires_invoice,
            'rfc'                    => $this->requires_invoice ? $this->rfc : null,
            'uso_cfdi'               => $this->requires_invoice ? $this->uso_cfdi : null,
            'razon_social'           => $this->requires_invoice ? $this->razon_social : null,
            'regimen_fiscal'         => $this->requires_invoice ? $this->regimen_fiscal : null,
            'cp_fiscal'              => $this->requires_invoice ? $this->cp_fiscal : null,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
            'items'                  => StoreOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
