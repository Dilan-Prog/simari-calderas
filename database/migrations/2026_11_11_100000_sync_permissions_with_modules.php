<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Roles y permisos: crea las filas de permiso que faltaban.
     *
     * Muchos módulos (Proyectos, MRP, Finanzas, Bandeja de leads, etc.) se agregaron a config/modules.php sin
     * sembrar su fila en `permissions`, y Pipelines/Negocios/Automatizaciones/etc. nunca tuvieron la acción
     * 'view'. Resultado: marcar el módulo en Roles no guardaba nada. Aquí se crean todas las filas faltantes
     * (Permission::syncFromConfig) y se cuidan dos casos para no quitarle acceso a nadie:
     *
     *  - Si un rol ya tenía alguna acción de un módulo y su fila 'view' se crea ahora, el rol la recibe
     *    (la pantalla de Roles siempre exige 'view' junto con las demás acciones).
     *  - 'Embudo de Venta' pasa a tener permiso propio ('sales-funnel'): lo reciben, con las mismas acciones,
     *    los roles que ya tenían WhatsApp (antes ese permiso abría el embudo).
     *
     * Es idempotente: solo reparte lo que acaba de crear, nunca vuelve a otorgar lo que un admin quitó.
     */
    public function up(): void
    {
        $created = collect(Permission::syncFromConfig());

        // 1) Roles con acciones de un módulo cuya fila 'view' se acaba de crear.
        foreach ($created->where('action', 'view')->pluck('module')->unique() as $module) {
            $viewId = DB::table('permissions')->where('module', $module)->where('action', 'view')->value('id');
            if (! $viewId) {
                continue;
            }
            $roleIds = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('permissions.module', $module)
                ->distinct()
                ->pluck('role_permissions.role_id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $viewId]);
            }
        }

        // 2) Embudo de Venta: lo conservan quienes tenían WhatsApp.
        if ($created->contains('module', 'sales-funnel')) {
            $funnelIds = DB::table('permissions')->where('module', 'sales-funnel')->pluck('id', 'action');
            $rows = DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('permissions.module', 'whatsapp')
                ->get(['role_permissions.role_id', 'permissions.action']);
            foreach ($rows as $row) {
                if (isset($funnelIds[$row->action])) {
                    DB::table('role_permissions')->insertOrIgnore(['role_id' => $row->role_id, 'permission_id' => $funnelIds[$row->action]]);
                }
            }
        }
    }

    public function down(): void
    {
        // Solo se revierte lo propio de esta migración: el permiso del Embudo de Venta. Las demás filas son
        // datos que debieron existir siempre y se dejan.
        $ids = DB::table('permissions')->where('module', 'sales-funnel')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
