<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;

/**
 * Protege las rutas de la API de integraciones (routes/api.php, grupo v1
 * autenticado con auth:sanctum). Deliberadamente independiente del
 * middleware `permission` (App\Http\Middleware\CheckPermission): ese es
 * para roles humanos del panel (hasPermission() sobre `permissions`/
 * `role_permissions`), este es para tokens de máquina con abilities de
 * Sanctum -- un cambio de permisos de un rol de panel no debe poder romper
 * en silencio una integración de N8N, y viceversa.
 *
 * Exige además que el principal autenticado sea un ApiClient (no un User
 * humano que por alguna razón tenga un token Sanctum propio, ej. `/api/user`)
 * -- así las dos historias de auth quedan completamente separadas aunque
 * ambos modelos usen HasApiTokens.
 */
class EnsureTokenAbility
{
    public function handle(Request $request, Closure $next, string $ability): mixed
    {
        $client = $request->user();

        if (! $client instanceof ApiClient) {
            return response()->json(['message' => 'No autenticado como cliente de API.'], 401);
        }

        if (! $client->is_active) {
            return response()->json(['message' => 'Este cliente de API está desactivado.'], 403);
        }

        if (! $request->user()->currentAccessToken()?->can($ability)) {
            return response()->json(['message' => "El token no tiene permiso para: {$ability}."], 403);
        }

        return $next($request);
    }
}
