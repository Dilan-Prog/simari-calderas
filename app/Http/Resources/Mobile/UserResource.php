<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario del personal para la app móvil.
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->role;

        return [
            'id'          => $this->id,
            'name'        => trim($this->full_name),
            'email'       => $this->email,
            'role'        => $role ? [
                'id'   => $role->id,
                'name' => $role->name_role_es ?: $role->name_role,
            ] : null,
            'is_admin'    => $this->isAdmin(),
            // Misma fuente que User::hasPermission() (admin => todo el catálogo).
            // (object) para que un mapa vacío se serialice como {} y no como [].
            'permissions' => (object) $this->permissionsMap(),
        ];
    }
}
