<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->with('permissions')
            ->get();

        return view('admin.roles.index', [
            'roles'        => $roles,
            'totalRoles'   => $roles->count(),
            'totalUsers'   => User::whereNotNull('role_id')->count(),
            'totalModules' => Permission::distinct('module')->count('module'),
            'modules'      => config('modules'),
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
            'permissions' => $role->permissions->pluck('module')->toArray(),
            'isAdmin'     => $role->isAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_role'        => 'required|string|max:100|unique:roles,name_role',
            'description_role' => 'nullable|string|max:255',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'string',
        ]);

        $role = Role::create([
            'name_role'        => $validated['name_role'],
            'description_role' => $validated['description_role'] ?? null,
        ]);

        if (!empty($validated['permissions'])) {
            $permIds = Permission::whereIn('module', $validated['permissions'])
                ->pluck('id');
            $role->permissions()->sync($permIds);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name_role'        => 'required|string|max:100|unique:roles,name_role,' . $id,
            'description_role' => 'nullable|string|max:255',
            'permissions'      => 'nullable|array',
            'permissions.*'    => 'string',
        ]);

        $role->update([
            'name_role'        => $validated['name_role'],
            'description_role' => $validated['description_role'] ?? null,
        ]);

        $permIds = !empty($validated['permissions'])
            ? Permission::whereIn('module', $validated['permissions'])->pluck('id')
            : [];

        $role->permissions()->sync($permIds);

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

