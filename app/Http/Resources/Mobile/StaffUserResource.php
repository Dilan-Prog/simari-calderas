<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario del personal en la lista de «Usuarios» de la app (solo lectura).
 *
 * Lista blanca de campos: nunca password, remember_token, tokens ni datos
 * personales sensibles (RFC, CURP, NSS, fecha de nacimiento).
 *
 * Requiere cargada: role. Opcional: tokens_max_last_used_at (withMax).
 *
 * @mixin \App\Models\User
 */
class StaffUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role     = $this->role;
        $lastSeen = $this->tokens_max_last_used_at ?? null;

        return [
            'id'           => $this->id,
            'name'         => trim($this->full_name),
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'email'        => $this->email,
            'phone'        => $this->phone ?: null,
            'position'     => $this->position ?: null,
            'status'       => $this->status,
            'is_active'    => $this->status === 'active',
            'role'         => $role ? [
                'id'   => $role->id,
                'name' => $role->name_role_es ?: $role->name_role,
            ] : null,
            'is_admin'     => $role?->isAdmin() ?? false,
            // Último uso de un token de la app (no hay registro de accesos al panel).
            'last_seen_at' => $lastSeen ? \Illuminate\Support\Carbon::parse($lastSeen)->toIso8601String() : null,
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
