<?php

namespace App\Http\Resources\Mobile;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Rol con su matriz de permisos para la app (solo lectura).
 *
 * `permissions` usa el mismo formato que /me ({modulo: [acciones]}). El rol
 * administrador recibe el catálogo completo, igual que User::permissionsMap(),
 * porque hasPermission() le concede todo sin importar sus filas.
 *
 * Requiere: users_count (withCount) y permissions cargados.
 *
 * @mixin \App\Models\Role
 */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $this->isAdmin();

        $permissions = $isAdmin
            ? self::catalog()
            : $this->permissions
                ->groupBy('module')
                ->map(fn ($rows) => $rows->pluck('action')->unique()->values()->all())
                ->all();

        return [
            'id'          => $this->id,
            'name'        => $this->name_role_es ?: $this->name_role,
            'key'         => $this->name_role,
            'description' => $this->description_role ?: null,
            'users_count' => (int) ($this->users_count ?? 0),
            'is_admin'    => $isAdmin,
            // (object) para que un mapa vacío se serialice como {} y no como [].
            'permissions' => (object) $permissions,
        ];
    }

    /** Catálogo completo (módulo => acciones); normalmente hay un solo rol admin. */
    private static function catalog(): array
    {
        return Permission::query()
            ->get(['module', 'action'])
            ->groupBy('module')
            ->map(fn ($rows) => $rows->pluck('action')->unique()->values()->all())
            ->all();
    }
}
