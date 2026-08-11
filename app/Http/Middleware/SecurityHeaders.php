<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Cabeceras de seguridad base — no existía ninguna antes de esto, ni en
 * middleware ni en .htaccess, y no hay config de servidor versionada en el
 * repo que las delegue. El CSP incluye explícitamente los orígenes que la
 * app ya carga (Google Tag Manager/Analytics, Google Fonts — ver
 * resources/views/frontend/layouts/partials/head-meta.blade.php) para no
 * romper nada existente.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval' es necesario porque Alpine.js (usado en todo el
            // storefront: mega-menú, carrito, checkout, galería de producto)
            // evalúa las expresiones de x-show/x-data/x-text con `new Function()`
            // internamente — sin esto, Alpine nunca lanza una excepción visible
            // al usuario, simplemente deja de evaluar condiciones y los
            // elementos x-show quedan permanentemente en su estado inicial del
            // DOM (visible), como el mega-menú "Próximamente" que nunca cierra.
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://www.google-analytics.com",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
