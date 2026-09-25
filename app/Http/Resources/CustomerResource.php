<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 5 del plan de integración N8N.
 *
 * ADVERTENCIA DE SEGURIDAD (explícita en el plan): `customers` tiene
 * `password_hash` (hash bcrypt del portal) y `remember_token` (columna
 * agregada por 2026_06_25_161427_add_remember_token_to_customers_table.php,
 * NINGUNA de las dos está en Customer::$hidden salvo password_hash, y
 * remember_token no está oculta en absoluto) -- por eso este Resource NUNCA
 * debe delegar a Customer::toArray()/JsonResource::toArray($this->resource)
 * crudo. Es una lista blanca explícita a propósito; agregar un campo nuevo
 * aquí debe ser una decisión consciente, no un fallthrough. Ver
 * tests/Feature/Api/Customers/CustomerResourceSecurityTest.php.
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'full_name'     => trim($this->first_name . ' ' . $this->last_name),
            'email'         => $this->email,
            'phone'         => $this->phone,
            'company'       => $this->company,
            'rfc'           => $this->rfc,
            'tipo_persona'  => $this->tipo_persona,
            'document_type' => $this->document_type,
            'status'        => $this->status,
            'source'        => $this->source,
            'notes'         => $this->notes,
            'portal_access' => (bool) $this->portal_access,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
