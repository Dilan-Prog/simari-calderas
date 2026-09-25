<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\SystemLog;
use Closure;
use Illuminate\Http\Request;
use Throwable;

/**
 * Bitácora de cada llamada a la API de integraciones, reutilizando
 * system_logs (misma tabla que AuditController/DevOpsController/etc. ya
 * escriben a mano vía SystemLog::create() -- el trait LogsActivity no aplica
 * aquí porque es para cambios de modelos Eloquent, no para requests HTTP
 * crudos). No debe poder tumbar un request real: cualquier fallo al
 * escribir el log se traga y se sigue.
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        try {
            $client = $request->user();

            SystemLog::create([
                'entity_type'         => 'api_request',
                'entity_id'           => $client instanceof ApiClient ? $client->id : 0,
                'action'              => $request->method() . ' ' . $request->path(),
                'description'         => $client instanceof ApiClient
                    ? "Token: {$client->name} (#{$client->id})"
                    : 'Sin cliente de API autenticado',
                'new_value'           => [
                    'status'     => $response->getStatusCode(),
                    'middleware' => $request->route()?->gatherMiddleware() ?? [],
                ],
                'performed_by_user_id' => null,
                'performed_at'         => now(),
                'ip_address'           => $request->ip(),
                'user_agent'           => (string) $request->userAgent(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return $response;
    }
}
