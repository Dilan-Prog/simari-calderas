<?php

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Support\PortalContext;

/**
 * Roles predeterminados del portal. Se crean una sola vez por cuenta (de forma
 * perezosa, la primera vez que el titular abre "Usuarios y roles"). Son is_system:
 * se pueden editar pero no eliminar.
 */
class PortalRoleDefaults
{
    public static function ensureFor(Customer $customer): void
    {
        if ($customer->portalRoles()->where('is_system', true)->exists()) {
            return;
        }

        foreach (self::definitions() as $name => $def) {
            $role = CustomerPortalRole::firstOrCreate(
                ['customer_id' => $customer->id, 'name' => $name],
                ['description' => $def['description'], 'is_system' => true]
            );

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($def['permissions']);
            }
        }
    }

    /** @return array<string, array{description: string, permissions: string[]}> */
    public static function definitions(): array
    {
        $assignable = PortalContext::assignableModules();

        $all = [];
        $viewOnly = [];
        foreach ($assignable as $key => $module) {
            foreach ($module['actions'] as $action) {
                $all[] = $key . ':' . $action;
            }
            $viewOnly[] = $key . ':view';
        }

        return [
            'Administrador del portal' => [
                'description' => 'Acceso a todo el portal, excepto la administración de usuarios y roles.',
                'permissions' => $all,
            ],
            'Laboratorio / Operación' => [
                'description' => 'Registra análisis químicos, monitoreo y equipos; consulta servicios y reportes.',
                'permissions' => [
                    'water-analysis:view', 'water-analysis:create', 'water-analysis:edit', 'water-analysis:delete',
                    'monitoring:view', 'monitoring:create', 'monitoring:edit', 'monitoring:delete',
                    'equipment:view', 'equipment:create', 'equipment:edit', 'equipment:delete',
                    'service-requests:view', 'service-requests:create',
                    'service-reports:view', 'technical-services:view', 'calendar:view',
                    'history:view', 'timeline:view',
                ],
            ],
            'Compras' => [
                'description' => 'Sube y gestiona órdenes de compra; consulta cotizaciones, pedidos y entregas.',
                'permissions' => [
                    'purchase-orders:view', 'purchase-orders:create', 'purchase-orders:delete',
                    'quotes:view', 'orders:view', 'deliveries:view',
                ],
            ],
            'Solo consulta' => [
                'description' => 'Puede ver la información de todos los módulos, sin crear, editar ni eliminar.',
                'permissions' => $viewOnly,
            ],
        ];
    }
}
