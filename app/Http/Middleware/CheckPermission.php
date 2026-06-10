<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): mixed
    {
        /** @var User|null $user */
        $user = auth()->user();

        if(!$user)
            {
                return $next($request);
            }
        if($user->isAdmin())
            {
                return $next($request);
            }
        if(!$user->role)
            {
                if($request->expectsJson()) {
                    return response()->json(['message' => 'No tienes un rol asignado. Acceso denegado.'], 403);
                }
                return redirect()
                ->route('admin.dashboard')
                ->with('error', 'No tienes un rol asignado. Contacta al administrador.');
            }
        if (!$user->hasPermission($module)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'No tienes permiso para acceder a este módulo.'
                    ], 403);
                }
                return redirect()
                    ->route('admin.dashboard')
                    ->with('error', 'No tienes permiso para acceder a ese módulo.');
            }

            return $next($request);
    }
}
