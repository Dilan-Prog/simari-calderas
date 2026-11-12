<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Modo incrustado (?embed=1): la Ficha del lead del Embudo de Venta abre la Bandeja de leads dentro de un
 * iframe sin menú lateral. Los formularios de esa página redirigen a otra URL (p. ej. al enviar un correo) y
 * esa URL perdía el parámetro: la bandeja completa, con su menú, aparecía dentro del mini panel.
 * Si la petición venía en modo incrustado, la redirección del mismo sitio conserva embed=1.
 */
class KeepEmbedMode
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo la Bandeja de leads (Ficha del lead del Embudo): otros flujos incrustados, como el de
        // Cotizaciones, ya redirigen a su propia pantalla incrustada y no deben tocarse.
        if ($response instanceof RedirectResponse && $request->boolean('embed') && $request->routeIs('admin.leads-inbox.*')) {
            $url = $response->getTargetUrl();
            $parts = parse_url($url);
            $sameSite = empty($parts['host']) || strcasecmp($parts['host'], $request->getHost()) === 0;

            if ($sameSite && ! preg_match('/[?&]embed=/', $url)) {
                $fragment = '';
                if (($pos = strpos($url, '#')) !== false) {
                    $fragment = substr($url, $pos);
                    $url = substr($url, 0, $pos);
                }
                $response->setTargetUrl($url . (str_contains($url, '?') ? '&' : '?') . 'embed=1' . $fragment);
            }
        }

        return $response;
    }
}
