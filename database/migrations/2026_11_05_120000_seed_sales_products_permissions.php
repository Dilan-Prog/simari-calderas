<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MODULE = 'sales-products';

    private const NAMES = [
        'view'   => 'Ver Listado de Productos',
        'create' => 'Crear Listado de Productos',
        'edit'   => 'Editar Listado de Productos',
        'delete' => 'Eliminar Listado de Productos',
        'log'    => 'Ver bitácora de Listado de Productos',
    ];

    /**
     * Permiso propio del panel Ventas > Productos (antes usaba 'products', el del
     * catálogo Ecommerce). Se crean las 5 acciones; solo los roles administrativos
     * lo reciben de entrada -- el resto se asigna a mano desde Administración > Roles.
     */
    public function up(): void
    {
        $now = now();
        $ids = [];

        foreach (self::NAMES as $action => $name) {
            $existing = DB::table('permissions')->where('module', self::MODULE)->where('action', $action)->value('id');
            $ids[] = $existing ?: DB::table('permissions')->insertGetId([
                'name' => $name, 'code' => null, 'module' => self::MODULE, 'action' => $action,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Mismo criterio que Role::isAdmin().
        $roleIds = DB::table('roles')
            ->whereIn(DB::raw('LOWER(name_role)'), ['administrador', 'admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($ids as $permId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permId]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('module', self::MODULE)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
