<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Las rutas de la tienda (/cuenta, name shop.*) redirigen al login de
        // clientes; el resto (admin y portal /customer) conserva el login
        // unificado existente.
        return $request->routeIs('shop.*') ? route('shop.login') : route('login');
    }
}
