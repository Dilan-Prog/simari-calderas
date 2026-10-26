<?php

namespace App\Http\Middleware;

use App\Support\PortalContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permisos por módulo del portal de clientes: ->middleware('portal.permission:water-analysis,create').
 * El titular de la cuenta pasa siempre; los usuarios del equipo según su rol (ver PortalContext).
 */
class PortalPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        if (PortalContext::can($module, $action)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'No tienes permiso para esta sección.'], 403);
        }

        return redirect()
            ->route('customer.dashboard')
            ->with('error', 'No tienes permiso para acceder a esa sección.');
    }
}
