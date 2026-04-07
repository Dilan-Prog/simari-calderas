<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware.php
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user()->role !== $role) {
            if($request->user()->role === 'admin') {
                return redirect()->route('admin.gestion-usuarios');
            } elseif($request->user()->role === 'employe') {
                return redirect()->route('employee.gestion-usuarios');
            } else {
                abort(403, 'Unauthorized');
            }
            
        }


        return $next($request);
    }
}
