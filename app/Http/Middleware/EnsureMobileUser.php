<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Protege la API móvil (/api/mobile/v1, routes/api/mobile.php). Va después de
 * auth:sanctum y exige que el principal autenticado sea un App\Models\User
 * (personal de la empresa) con un token emitido por el login móvil (ability
 * 'mobile'). Es el espejo de EnsureTokenAbility: un token de ApiClient
 * (integraciones N8N) NO puede usar la API móvil, y un token de User NO puede
 * usar las rutas de integraciones /api/v1 -- ambas historias de auth quedan
 * separadas aunque los dos modelos usen HasApiTokens.
 *
 * También rechaza a usuarios suspendidos después de iniciar sesión
 * (status 'suspended'), mismo criterio que el login móvil.
 */
class EnsureMobileUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'Token no válido para la API móvil.'], 401);
        }

        if (! $user->currentAccessToken()?->can('mobile')) {
            return response()->json(['message' => 'Token no válido para la API móvil.'], 401);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Tu cuenta está desactivada. Contacta al administrador.'], 403);
        }

        return $next($request);
    }
}
