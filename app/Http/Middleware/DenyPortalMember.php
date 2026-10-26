<?php

namespace App\Http\Middleware;

use App\Support\PortalContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los usuarios del equipo de un cliente (roles del portal) viven SOLO en /customer.
 * Como el guard `customer` los autentica como su titular, las áreas de la tienda que
 * actúan sobre la cuenta (Mi cuenta, direcciones guardadas, carrito, checkout, pedidos)
 * les están vedadas: de lo contrario operarían como el titular.
 */
class DenyPortalMember
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('customer')->check() && PortalContext::memberId() !== null) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Esta sección no está disponible para usuarios del portal.'], 403);
            }

            return redirect()
                ->route('customer.dashboard')
                ->with('error', 'Esa sección de la tienda solo está disponible para el titular de la cuenta.');
        }

        return $next($request);
    }
}
