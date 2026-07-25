<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El portal /customer (reportes de servicio) es un privilegio que el admin
 * activa por cliente (customers.portal_access). Un cliente autenticado sin
 * acceso es redirigido a su cuenta de la tienda.
 */
class EnsurePortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer || ! $customer->portal_access) {
            return redirect()
                ->route('shop.account')
                ->with('status', 'Tu cuenta aún no tiene acceso al portal de servicios. Puedes solicitarlo desde Mi Cuenta.');
        }

        return $next($request);
    }
}
