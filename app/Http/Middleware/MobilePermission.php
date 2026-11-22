<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Equivalente JSON del middleware `permission` (CheckPermission) para la API
 * móvil: mismo criterio (User::hasPermission, admin => todo) pero siempre
 * responde 403 JSON en lugar de redirigir al panel. Uso:
 * `mobile.permission:service-reports,view`.
 */
class MobilePermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): mixed
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (! $user->hasPermission($module, $action)) {
            return response()->json(['message' => 'No tienes permiso para acceder a este módulo.'], 403);
        }

        return $next($request);
    }
}
