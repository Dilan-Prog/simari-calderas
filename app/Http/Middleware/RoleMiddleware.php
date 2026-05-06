<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $userRole = $request->user()->role?->name_role; // accede al string del name_role

        if ($userRole !== $role) {
            // El usuario no tiene el rol requerido, redirige según lo que SÍ es
            return match($userRole) {
                'admin'   => redirect()->route('admin.dashboard'),
                'employe' => redirect()->route('employee.dashboard'),
                default   => abort(403, 'No autorizado'),
            };
        }

        return $next($request);
    }

}
