<?php

namespace App\Http\Middleware;

use App\Support\PortalContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El portal /customer (reportes de servicio) es un privilegio que el admin
 * activa por cliente (customers.portal_access). Un cliente autenticado sin
 * acceso es redirigido a su cuenta de la tienda. Un cliente con la cuenta
 * desactivada/suspendida (status != active) pierde la sesión y vuelve al login.
 */
class EnsurePortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && $customer->status !== 'active') {
            Auth::guard('customer')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('shop.login')
                ->withErrors(['email' => 'Tu cuenta está desactivada. Contáctanos.']);
        }

        if (! $customer || ! $customer->portal_access) {
            return redirect()
                ->route('shop.account')
                ->with('status', 'Tu cuenta aún no tiene acceso al portal de servicios. Puedes solicitarlo desde Mi Cuenta.');
        }

        // Usuario del equipo (roles del portal): se revalida en cada petición que
        // siga existiendo, activo y con rol; si no, pierde la sesión.
        if (PortalContext::memberId() !== null) {
            $member = PortalContext::member();

            if (! $member || ! $member->isActive() || ! $member->role_id) {
                Auth::guard('customer')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('shop.login')
                    ->withErrors(['email' => 'Tu acceso al portal fue desactivado o modificado. Contacta al titular de tu cuenta.']);
            }
        }

        return $next($request);
    }
}
