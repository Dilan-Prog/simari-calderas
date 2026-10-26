<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPortalRole;
use App\Support\PortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Roles del equipo del portal: cada cuenta define los suyos (matriz módulo x acción).
 */
class TeamRoleController extends Controller
{
    private function owner(): Customer
    {
        return auth('customer')->user();
    }

    /** Agrupación visual de la matriz de permisos; lo que no esté listado cae en "Otros". */
    public static function moduleGroups(): array
    {
        $groups = [
            'Servicios' => ['service-requests', 'service-reports', 'technical-services', 'calendar', 'history', 'timeline'],
            'Operación' => ['monitoring', 'water-analysis', 'addresses'],
            'Comercial' => ['quotes', 'orders', 'deliveries', 'purchase-orders'],
        ];

        $assignable = array_keys(PortalContext::assignableModules());
        $known = array_merge(...array_values($groups));
        $others = array_values(array_diff($assignable, $known));

        $groups = array_map(fn ($keys) => array_values(array_intersect($keys, $assignable)), $groups);
        if ($others) {
            $groups['Otros'] = $others;
        }

        return array_filter($groups);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->owner();
        $data = $this->validated($request, $owner, null);

        $role = $owner->portalRoles()->create([
            'name'        => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_system'   => false,
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('customer.team.index', ['tab' => 'roles'])->with('success', 'El rol se creó correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $owner = $this->owner();
        $role = $owner->portalRoles()->findOrFail($id);
        $data = $this->validated($request, $owner, $role);

        $role->update([
            'name'        => trim($data['name']),
            'description' => $data['description'] ?? null,
        ]);
        $role->syncPermissions($data['permissions'] ?? []);
        PortalContext::flush();

        return redirect()->route('customer.team.index', ['tab' => 'roles'])->with('success', 'El rol se actualizó correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = $this->owner()->portalRoles()->withCount('users')->findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('customer.team.index', ['tab' => 'roles'])
                ->with('error', 'Los roles predeterminados no se pueden eliminar; puedes editar sus permisos.');
        }

        if ($role->users_count > 0) {
            return redirect()->route('customer.team.index', ['tab' => 'roles'])
                ->with('error', 'No se puede eliminar un rol con usuarios asignados. Cambia primero el rol de esos usuarios.');
        }

        $role->delete();

        return redirect()->route('customer.team.index', ['tab' => 'roles'])->with('success', 'El rol se eliminó.');
    }

    private function validated(Request $request, Customer $owner, ?CustomerPortalRole $ignore): array
    {
        return $request->validate([
            'name'          => [
                'required', 'string', 'max:100',
                Rule::unique('customer_portal_roles', 'name')
                    ->where('customer_id', $owner->id)
                    ->ignore($ignore?->id),
            ],
            'description'   => ['nullable', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'regex:/^[a-z0-9\-]+:(view|create|edit|delete)$/'],
        ], [
            'name.required' => 'Escribe el nombre del rol.',
            'name.max'      => 'El nombre del rol no puede tener más de 100 caracteres.',
            'name.unique'   => 'Ya tienes un rol con ese nombre.',
        ]);
    }
}
