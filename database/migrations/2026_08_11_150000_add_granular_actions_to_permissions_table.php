<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Acciones granulares que se agregan por módulo, además de 'view'
     * (que ya existe como fila única por módulo desde la migración
     * 2026_06_09_044252_add_column_action_permission_table.php).
     */
    private const NEW_ACTIONS = ['create', 'edit', 'delete', 'log'];

    public function up(): void
    {
        $modules = config('modules'); // 33 módulos hoy — fuente de verdad, puede crecer antes de que corras esto
        $now = now();

        foreach ($modules as $key => $module) {
            foreach (self::NEW_ACTIONS as $action) {
                $exists = DB::table('permissions')
                    ->where('module', $key)
                    ->where('action', $action)
                    ->exists();

                if (!$exists) {
                    DB::table('permissions')->insert([
                        'name'       => ucfirst($this->actionLabel($action)) . ' ' . $module['name'],
                        'code'       => null,
                        'module'     => $key,
                        'action'     => $action,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Backfill de seguridad: todo role_permission existente con action='view'
        // en un módulo recibe también las 4 acciones nuevas del MISMO módulo, para
        // que ningún rol pierda acceso a algo que ya tenía el día que se active el
        // middleware de acción en las rutas (otro paquete de este mismo pase).
        $rolesWithView = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.action', 'view')
            ->select('role_permissions.role_id', 'permissions.module')
            ->get();

        foreach ($rolesWithView as $row) {
            $newPermIds = DB::table('permissions')
                ->where('module', $row->module)
                ->whereIn('action', self::NEW_ACTIONS)
                ->pluck('id');

            foreach ($newPermIds as $permId) {
                DB::table('role_permissions')->insertOrIgnore([
                    'role_id'       => $row->role_id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Nunca tocar filas action='view' — solo revertir las acciones nuevas
        // agregadas por esta migración (esto también elimina las filas
        // correspondientes en role_permissions vía la FK, si existiera cascade;
        // como no la hay, las borramos explícitamente primero).
        $permissionIds = DB::table('permissions')
            ->whereIn('action', self::NEW_ACTIONS)
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('action', self::NEW_ACTIONS)->delete();
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'create' => 'crear',
            'edit'   => 'editar',
            'delete' => 'eliminar',
            'log'    => 'ver bitácora de',
            default  => $action,
        };
    }
};
