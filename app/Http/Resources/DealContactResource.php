<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fase 3 (API N8N) — CRM. Deal::contacts() es un belongsToMany hacia
 * Customer a través de la tabla pivote deal_contacts (sin modelo Eloquent
 * propio, ver migración 2026_08_11_201000_create_deal_contacts_table.php),
 * así que este resource envuelve instancias de Customer con su pivot
 * cargado. Expone solo los campos de Customer relevantes para un contacto de
 * negocio -- omite explícitamente password_hash (ya está en $hidden de
 * Customer, pero aquí ni siquiera se referencia) y demás columnas internas.
 */
class DealContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_id' => $this->id,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'company'     => $this->company,
            'role'        => $this->pivot?->role,
            'added_at'    => $this->pivot?->created_at,
        ];
    }
}
