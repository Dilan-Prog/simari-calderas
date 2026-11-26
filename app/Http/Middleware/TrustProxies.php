<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Proxies de confianza desde TRUSTED_PROXIES (config app.trusted_proxies):
     * vacío = ninguno (como antes); "*" = cualquiera (detrás de Cloudflare o
     * del proxy del hosting); o IPs/rangos separados por coma.
     */
    protected function proxies()
    {
        $value = trim((string) config('app.trusted_proxies', ''));
        if ($value === '') {
            return $this->proxies;
        }
        if ($value === '*' || $value === '**') {
            return $value;
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
