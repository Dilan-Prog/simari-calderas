<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rol del portal de clientes. Es propio de cada cuenta (customer_id): cada
 * empresa define los suyos. Equivale a App\Models\Role del admin.
 */
class CustomerPortalRole extends Model
{
    protected $table = 'customer_portal_roles';

    protected $fillable = ['customer_id', 'name', 'description', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function permissions()
    {
        return $this->hasMany(CustomerPortalRolePermission::class, 'role_id');
    }

    public function users()
    {
        return $this->hasMany(CustomerPortalUser::class, 'role_id');
    }

    /** Permisos como lista plana "modulo:accion". */
    public function permissionKeys(): array
    {
        return $this->permissions->map(fn ($p) => $p->module . ':' . $p->action)->all();
    }

    /**
     * Reemplaza los permisos del rol. $pairs: lista de "modulo:accion". Solo se guardan
     * módulos del catálogo (sin owner_only) con acciones permitidas, y toda acción
     * distinta de 'view' exige 'view' en el mismo módulo.
     */
    public function syncPermissions(array $pairs): void
    {
        $catalog = collect(config('portal_modules', []))->reject(fn ($m) => ! empty($m['owner_only']));

        $byModule = [];
        foreach ($pairs as $pair) {
            [$module, $action] = array_pad(explode(':', (string) $pair, 2), 2, null);
            if (! $module || ! $action || ! isset($catalog[$module])) {
                continue;
            }
            if (! in_array($action, $catalog[$module]['actions'] ?? ['view'], true)) {
                continue;
            }
            $byModule[$module][$action] = true;
        }

        $rows = [];
        foreach ($byModule as $module => $actions) {
            if (! isset($actions['view'])) {
                continue;
            }
            foreach (array_keys($actions) as $action) {
                $rows[] = ['module' => $module, 'action' => $action];
            }
        }

        $this->permissions()->delete();
        if ($rows) {
            $this->permissions()->createMany($rows);
        }
        $this->unsetRelation('permissions');
    }
}
