<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Bloquea que un rol se renombre a algo que satisfaga Role::isAdmin()
     * ("admin"/"administrador", case-insensitive) a menos que quien hace la
     * petición ya sea admin — sin esto, en cuanto el permiso de Roles se
     * pueda delegar a un no-admin (ver fix del mismatch permission:role vs
     * config('modules.roles')), ese usuario podría renombrar su propio rol
     * a "Administrador" y auto-otorgarse acceso total.
     */
    private function reservedRoleNameRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (!auth()->user()?->isAdmin() && in_array(strtolower(trim($value)), ['admin', 'administrador'], true)) {
                $fail('Ese nombre de rol está reservado.');
            }
        };
    }

    /**
     * Agrupación puramente visual del grid de permisos del drawer de Roles —
     * mismo criterio de secciones que el sidebar (sidebar.blade.php
     * $groupSections), salvo que aquí "CRM" se separa de ERP para que el
     * admin ubique más rápido Clientes/Pipelines/Negocios/Automatizaciones.
     * No afecta rutas, permisos ni config('modules') — solo el orden de
     * renderizado del grid.
     */
    private function moduleGroups(): array
    {
        return [
            'Ecommerce' => [
                'dashboard', 'products', 'categories', 'brands', 'collections',
                'service-pages', 'gallery', 'home-sections', 'orders', 'inventory',
                'shipments', 'carriers', 'payment-methods', 'menu', 'settings',
            ],
            'ERP y Servicios' => [
                'quotes', 'suppliers', 'purchase-orders', 'sales-orders',
                'material-delivery-reports', 'chemical-planning', 'erp-settings',
                'technical-services', 'service-reports',
            ],
            'Administración' => [
                'roles', 'users', 'google-ads', 'email-marketing', 'analytics',
                'audit', 'blog', 'seo', 'whatsapp',
            ],
            'CRM' => [
                'clients', 'pipeline', 'deals', 'automations',
            ],
        ];
    }

    public function index()
    {
        $roles = Role::withCount([
                'users',
                'permissions as modules_count' => fn ($q) => $q->where('action', 'view'),
            ])
            ->with('permissions')
            ->get();

        $visibleColumns = \App\Models\UserColumnPreference::where('user_id', auth()->id())
            ->where('table_key', 'roles.index')
            ->value('columns');

        return view('admin.roles.index', [
            'roles'        => $roles,
            'totalRoles'   => $roles->count(),
            'totalUsers'   => User::whereNotNull('role_id')->count(),
            'totalModules' => Permission::distinct('module')->count('module'),
            'modules'      => config('modules'),
            'moduleGroups' => $this->moduleGroups(),
            'visibleColumns' => $visibleColumns,
        ]);
    }

    public function show(int $id)
    {
        $role = Role::withCount('users')->with('permissions')->findOrFail($id);

        return response()->json([
            'role'        => [
                'name_role'        => $role->name_role,
                'description_role' => $role->description_role,
                'users_count'      => $role->users_count,
                'created_at'       => $role->created_at
                    ? \Carbon\Carbon::parse($role->created_at)->format('d M Y')
                    : '—',
            ],
            'permissions' => $role->permissions->groupBy('module')->map(fn ($p) => $p->pluck('action')->values()),
            'isAdmin'     => $role->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_role'        => ['required', 'string', 'max:100', 'unique:roles,name_role', $this->reservedRoleNameRule()],
            'name_role_es'     => 'nullable|string|max:100',
            'description_role' => 'nullable|string|max:255',
            'permissions'      => 'nullable|array',
            'permissions.*'    => ['string', 'regex:/^[a-z0-9\-]+:(view|create|edit|delete|log)$/'],
        ]);

        $role = Role::create([
            'name_role'        => $validated['name_role'],
            'name_role_es'     => $validated['name_role_es'] ?? $validated['name_role'],
            'description_role' => $validated['description_role'] ?? null,
            'created_at'       => now(),
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($this->resolvePermissionIds($validated['permissions']));
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol creado correctamente.');
    }

    /**
     * Convierte el payload plano "modulo:accion" en IDs de Permission,
     * aplicando defensa en profundidad: descarta cualquier acción de un
     * módulo que no incluya también 'view' en el mismo payload, aunque el
     * cliente la haya enviado manipulada (la UI ya deshabilita esos
     * checkboxes, pero el servidor no debe confiar únicamente en eso).
     */
    private function resolvePermissionIds(array $permissions)
    {
        $pairs = collect($permissions)
            ->map(fn ($p) => explode(':', $p, 2))
            ->filter(fn ($p) => count($p) === 2);

        $byModule = $pairs->groupBy(fn ($p) => $p[0]);
        $validPairs = $byModule
            ->filter(fn ($actions) => $actions->contains(fn ($p) => $p[1] === 'view'))
            ->flatten(1);

        return Permission::where(function ($q) use ($validPairs) {
            foreach ($validPairs as [$module, $action]) {
                $q->orWhere(fn ($qq) => $qq->where('module', $module)->where('action', $action));
            }
        })->pluck('id');
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name_role'        => ['required', 'string', 'max:100', 'unique:roles,name_role,' . $id, $this->reservedRoleNameRule()],
            'name_role_es'     => 'nullable|string|max:100',
            'description_role' => 'nullable|string|max:255',
            'permissions'      => 'nullable|array',
            'permissions.*'    => ['string', 'regex:/^[a-z0-9\-]+:(view|create|edit|delete|log)$/'],
        ]);

        $role->update([
            'name_role'        => $validated['name_role'],
            'name_role_es'     => $validated['name_role_es'] ?? $validated['name_role'],
            'description_role' => $validated['description_role'] ?? null,
        ]);

        $permIds = !empty($validated['permissions'])
            ? $this->resolvePermissionIds($validated['permissions'])
            : [];

        $role->permissions()->sync($permIds);

        // Invalida la caché de permisos de los usuarios afectados: el
        // middleware/User::hasPermission() de otro agente memoiza permisos
        // por usuario, así que un sync() aquí no se reflejaría en sesiones
        // ya activas sin este paso.
        User::where('role_id', $role->id)->get()->each->clearPermissionsCache();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->isAdmin()) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'No se puede eliminar el rol administrador.');
        }

        if ($role->users_count > 0) {
            User::where('role_id', $id)->update(['role_id' => null]);
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol eliminado correctamente.');
    }
}

