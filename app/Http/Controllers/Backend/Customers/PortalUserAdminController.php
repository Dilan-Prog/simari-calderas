<?php

namespace App\Http\Controllers\Backend\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPortalUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Pestaña "Usuarios del portal" de la ficha del cliente (admin): el personal puede activar/desactivar,
 * cambiar la contraseña o eliminar a los usuarios del equipo que el titular dio de alta en el portal.
 * Siempre se verifica que el usuario pertenezca al cliente de la URL (404 si no).
 * La sesión viva de un usuario desactivado/eliminado la corta el middleware EnsurePortalAccess.
 */
class PortalUserAdminController extends Controller
{
    private function member(int $customerId, int $memberId): CustomerPortalUser
    {
        $customer = Customer::findOrFail($customerId);

        return $customer->portalUsers()->findOrFail($memberId);
    }

    private function back(int $customerId): string
    {
        return route('admin.clients.information', $customerId) . '#portal-users';
    }

    public function toggle(int $id, int $memberId): RedirectResponse
    {
        $member = $this->member($id, $memberId);
        $member->update(['status' => $member->isActive() ? 'inactive' : 'active']);

        return redirect()->to($this->back($id))->with(
            'success',
            $member->isActive()
                ? 'El usuario ' . $member->full_name . ' fue activado.'
                : 'El usuario ' . $member->full_name . ' fue desactivado y su sesión se cerrará.'
        );
    }

    public function resetPassword(Request $request, int $id, int $memberId): RedirectResponse
    {
        $member = $this->member($id, $memberId);

        $validator = validator(
            $request->only(['password', 'password_confirmation']),
            ['password' => ['required', 'string', 'confirmed', Password::min(8)]],
            [
                'password.required'  => 'Escribe la nueva contraseña.',
                'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
                'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            ],
            ['password' => 'contraseña']
        );

        if ($validator->fails()) {
            return redirect()->to($this->back($id))->withErrors($validator);
        }

        // Rota el remember_token para invalidar cualquier sesión recordada con la contraseña anterior.
        $member->forceFill([
            'password'       => $request->input('password'),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()->to($this->back($id))->with('success', 'La contraseña de ' . $member->full_name . ' se actualizó.');
    }

    public function destroy(int $id, int $memberId): RedirectResponse
    {
        $member = $this->member($id, $memberId);
        $name = $member->full_name;
        $member->delete();

        return redirect()->to($this->back($id))->with('success', 'El usuario ' . $name . ' se eliminó.');
    }
}
