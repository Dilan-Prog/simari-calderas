<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Permission extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'code',
        'module',
        'action',
    ];

    /** Acciones que tiene cada módulo del menú (los permisos notif-* solo usan 'view' = "recibir"). */
    public const ACTIONS = [
        'view'   => 'Ver',
        'create' => 'Crear',
        'edit'   => 'Editar',
        'delete' => 'Eliminar',
        'log'    => 'Ver bitácora de',
    ];

    /**
     * Garantiza que exista la fila de permiso de cada módulo y acción definidos en config/modules.php
     * (y de los tipos de aviso de config/notifications.php). Sin esa fila, marcar el módulo en Roles no
     * guarda nada: el permiso no existe en la base y el rol se queda sin acceso aunque la casilla se vea
     * marcada. Es idempotente y barata (una consulta + inserciones solo de lo que falte).
     *
     * @return array<int, array{module:string, action:string}> lo que se creó en esta llamada
     */
    public static function syncFromConfig(): array
    {
        $existing = [];
        foreach (DB::table('permissions')->get(['module', 'action']) as $p) {
            $existing[$p->module . ':' . $p->action] = true;
        }

        $wanted = [];
        foreach (config('modules', []) as $module => $def) {
            foreach (self::ACTIONS as $action => $verb) {
                $wanted[] = [$module, $action, trim($verb . ' ' . ($def['name'] ?? $module))];
            }
        }
        foreach (config('notifications.permissions', []) as $module => $def) {
            $wanted[] = [$module, 'view', 'Recibir aviso: ' . ($def['label'] ?? $module)];
        }

        $created = [];
        $now = now();
        foreach ($wanted as [$module, $action, $name]) {
            if (isset($existing[$module . ':' . $action])) {
                continue;
            }
            DB::table('permissions')->insert([
                'name' => mb_substr($name, 0, 100), 'code' => null, 'module' => $module, 'action' => $action,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $created[] = ['module' => $module, 'action' => $action];
        }

        return $created;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }
}
