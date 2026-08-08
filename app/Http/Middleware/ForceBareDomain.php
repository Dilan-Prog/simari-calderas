<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * El dominio oficial del sitio es SIN "www" (https://equitermindustries.com.mx).
 * Ambas variantes (con y sin www) resolvían de forma independiente sin
 * redirect entre ellas, lo cual Google puede tratar como contenido
 * duplicado y repartir la señal de indexación entre las dos. Este
 * middleware fuerza un 301 permanente desde la variante con "www" hacia la
 * oficial, para que exista una sola versión indexable.
 *
 * Chequeo por host exacto (no un patrón genérico "www.*") a propósito: así
 * nunca afecta entornos locales/staging (simari-calderas.test, localhost,
 * etc.) que no tienen nada que ver con este dominio de producción.
 */
class ForceBareDomain
{
    private const WWW_HOST = 'www.equitermindustries.com.mx';
    private const BARE_HOST = 'equitermindustries.com.mx';

    public function handle(Request $request, Closure $next)
    {
        if ($request->getHost() === self::WWW_HOST) {
            return redirect()->to('https://' . self::BARE_HOST . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
