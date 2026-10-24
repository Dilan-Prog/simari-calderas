<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Un permiso 'view' (= "recibir") por tipo de notificación. */
    private const PERMS = [
        'notif-lead-entrante'  => 'Recibir notificaciones: Lead entrante',
        'notif-lead-respuesta' => 'Recibir notificaciones: Respuesta de lead',
        'notif-tarea'          => 'Recibir notificaciones: Tareas',
    ];

    public function up(): void
    {
        $now = now();
        $ids = [];

        foreach (self::PERMS as $module => $name) {
            $existing = DB::table('permissions')->where('module', $module)->where('action', 'view')->value('id');
            $ids[] = $existing ?: DB::table('permissions')->insertGetId([
                'name' => $name, 'code' => null, 'module' => $module, 'action' => 'view',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Roles administrativos existentes (mismo criterio que Role::isAdmin()).
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
        $ids = DB::table('permissions')->whereIn('module', array_keys(self::PERMS))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
