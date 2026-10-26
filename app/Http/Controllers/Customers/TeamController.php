<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPortalUser;
use App\Services\Portal\PortalRoleDefaults;
use App\Support\PortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * "Usuarios y roles" del portal: el titular de la cuenta da de alta a su equipo.
 * Pantalla con dos pestañas (Usuarios / Roles); los roles se administran en
 * TeamRoleController. Todo se filtra por la cuenta del titular autenticado.
 */
class TeamController extends Controller
{
    private function owner(): Customer
    {
        return auth('customer')->user();
    }

    public function index(Request $request)
    {
        $owner = $this->owner();
        PortalRoleDefaults::ensureFor($owner);

        $roles = $owner->portalRoles()
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $users = $owner->portalUsers()->with('role')->orderBy('first_name')->orderBy('last_name')->get();

        return view('customers.team.index', [
            'owner'        => $owner,
            'users'        => $users,
            'roles'        => $roles,
            'modules'      => PortalContext::assignableModules(),
            'moduleGroups' => TeamRoleController::moduleGroups(),
            'tab'          => $request->query('tab') === 'roles' ? 'roles' : 'usuarios',
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $owner = $this->owner();
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate(
            $this->rules($owner, null) + ['password' => ['required', 'string', Password::min(8)]],
            $this->messages(),
            $this->attributes()
        );

        CustomerPortalUser::create([
            'customer_id' => $owner->id,
            'role_id'     => $data['role_id'],
            'first_name'  => trim($data['first_name']),
            'last_name'   => trim($data['last_name'] ?? ''),
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'password'    => $data['password'],
            'status'      => 'active',
        ]);

        return redirect()->route('customer.team.index')->with('success', 'El usuario se creó correctamente.');
    }

    public function updateUser(Request $request, int $id): RedirectResponse
    {
        $owner = $this->owner();
        $member = $owner->portalUsers()->findOrFail($id);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate($this->rules($owner, $member), $this->messages(), $this->attributes());

        $member->update([
            'role_id'    => $data['role_id'],
            'first_name' => trim($data['first_name']),
            'last_name'  => trim($data['last_name'] ?? ''),
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
        ]);

        return redirect()->route('customer.team.index')->with('success', 'El usuario se actualizó correctamente.');
    }

    public function toggleUser(int $id): RedirectResponse
    {
        $member = $this->owner()->portalUsers()->findOrFail($id);
        $member->update(['status' => $member->isActive() ? 'inactive' : 'active']);

        return redirect()->route('customer.team.index')->with(
            'success',
            $member->isActive() ? 'El usuario fue activado.' : 'El usuario fue desactivado y su sesión se cerrará.'
        );
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $member = $this->owner()->portalUsers()->findOrFail($id);

        $request->validate(
            ['password' => ['required', 'string', Password::min(8)]],
            ['password.required' => 'Escribe la nueva contraseña.', 'password.min' => 'La contraseña debe tener al menos 8 caracteres.'],
            ['password' => 'contraseña']
        );

        $member->forceFill([
            'password'       => $request->input('password'),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->route('customer.team.index')->with('success', 'La contraseña de ' . $member->full_name . ' se actualizó.');
    }

    public function destroyUser(int $id): RedirectResponse
    {
        $member = $this->owner()->portalUsers()->findOrFail($id);
        $member->delete();

        return redirect()->route('customer.team.index')->with('success', 'El usuario se eliminó.');
    }

    private function rules(Customer $owner, ?CustomerPortalUser $ignore): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => ['required', 'string', 'email', 'max:255', $this->uniqueEmail($ignore)],
            'phone'      => ['nullable', 'string', 'max:30'],
            'role_id'    => ['required', 'integer', Rule::exists('customer_portal_roles', 'id')->where('customer_id', $owner->id)],
        ];
    }

    /** El correo debe ser único entre usuarios del equipo Y titulares (customers), sin distinguir mayúsculas. */
    private function uniqueEmail(?CustomerPortalUser $ignore): \Closure
    {
        return function ($attribute, $value, $fail) use ($ignore) {
            $email = strtolower((string) $value);

            $inCustomers = Customer::whereRaw('LOWER(email) = ?', [$email])->exists();
            $inMembers = CustomerPortalUser::whereRaw('LOWER(email) = ?', [$email])
                ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
                ->exists();

            if ($inCustomers || $inMembers) {
                $fail('Este correo ya está registrado.');
            }
        };
    }

    private function messages(): array
    {
        return [
            'required'       => 'El campo :attribute es obligatorio.',
            'max'            => 'El campo :attribute no puede tener más de :max caracteres.',
            'email'          => 'Escribe un correo electrónico válido.',
            'password.min'   => 'La contraseña debe tener al menos 8 caracteres.',
            'role_id.exists' => 'Selecciona un rol válido.',
        ];
    }

    private function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name'  => 'apellido',
            'email'      => 'correo electrónico',
            'phone'      => 'teléfono',
            'role_id'    => 'rol',
            'password'   => 'contraseña',
        ];
    }
}
