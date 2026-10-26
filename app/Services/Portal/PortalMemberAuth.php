<?php

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\CustomerPortalUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Inicio de sesión de los usuarios del equipo de un cliente (CustomerPortalUser).
 *
 * El guard `customer` solo sabe autenticar a un Customer, y los controladores del
 * portal filtran sus datos por esa cuenta. Por eso un usuario del equipo entra como
 * su titular (Auth::guard('customer')->login($titular)) y se marca en la sesión con
 * `portal_member_id`; PortalContext lee esa marca para aplicar los permisos del rol.
 *
 * Se invoca desde los dos logins que intentan el guard customer (tienda y /login).
 */
class PortalMemberAuth
{
    public const SESSION_KEY = 'portal_member_id';

    /**
     * Intenta autenticar a un usuario del equipo.
     *
     * @return CustomerPortalUser|null  null = el correo no es de un usuario del equipo o la
     *                                  contraseña no coincide (el llamador trata el intento como fallido).
     * @throws ValidationException      credenciales correctas pero el acceso está bloqueado.
     */
    public function attempt(string $email, string $password): ?CustomerPortalUser
    {
        $email = strtolower(trim($email));

        // Un correo de titular siempre sigue el flujo normal de Customer.
        if (Customer::where('email', $email)->exists()) {
            return null;
        }

        $member = CustomerPortalUser::with(['customer', 'role'])->where('email', $email)->first();

        if (! $member) {
            Hash::make($password); // iguala tiempos con el caso de usuario existente
            return null;
        }

        if (! Hash::check($password, $member->password)) {
            return null;
        }

        $owner = $member->customer;

        $block = match (true) {
            ! $owner || $owner->status !== 'active' => 'La cuenta de tu empresa está desactivada. Contáctanos.',
            ! $owner->portal_access                 => 'La cuenta de tu empresa no tiene acceso al portal de servicios. Contáctanos.',
            ! $member->isActive()                   => 'Tu usuario está desactivado. Contacta al titular de tu cuenta.',
            ! $member->role_id || ! $member->role   => 'Tu usuario no tiene un rol asignado. Contacta al titular de tu cuenta.',
            default                                 => null,
        };

        if ($block) {
            throw ValidationException::withMessages(['email' => $block]);
        }

        $this->login($owner, $member);

        return $member;
    }

    /**
     * Abre la sesión del portal para un usuario del equipo. Nunca con "recordarme":
     * la cookie recaller del guard restauraría al titular sin la marca del usuario
     * (y con acceso total) al expirar la sesión.
     */
    public function login(Customer $owner, CustomerPortalUser $member): void
    {
        Auth::guard('customer')->login($owner, false);
        session([self::SESSION_KEY => $member->id]);

        $member->forceFill(['last_login_at' => now()])->saveQuietly();
    }

    /** Quita la marca de usuario del equipo (login de titular o cierre de sesión). */
    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
