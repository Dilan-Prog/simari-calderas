<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // La API móvil (/api/mobile/*) tiene su propio limiter 'mobile' (abajo);
            // se excluye de este para que el tope de 60/min del grupo `api` no la recorte.
            if ($request->is('api/mobile/*')) {
                return Limit::none();
            }

            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // API móvil del personal (routes/api/mobile.php): 120/min por usuario
        // autenticado, o por IP en las rutas sin token (login, PDF firmado).
        RateLimiter::for('mobile', function (Request $request) {
            return Limit::perMinute(120)->by(
                $request->user() ? 'user:' . $request->user()->getAuthIdentifier() : 'ip:' . $request->ip()
            );
        });

        // Login móvil: 5 intentos por minuto por combinación email + IP
        // (mismo criterio de llave que LoginRequest::throttleKey del login web).
        RateLimiter::for('mobile-login', function (Request $request) {
            $key = \Illuminate\Support\Str::transliterate(
                \Illuminate\Support\Str::lower((string) $request->input('email')) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($key)->response(fn (Request $request, array $headers) => response()->json([
                'message' => 'Demasiados intentos de inicio de sesión. Intenta de nuevo en un minuto.',
            ], 429, $headers));
        });

        // Clientes de API de confianza (N8N, etc. -- App\Models\ApiClient,
        // autenticados con auth:sanctum + token.ability). Límite más alto que
        // el limiter 'api' público de arriba (usado por ad-tracking/pool-calculator),
        // por-token en vez de por-IP para no mezclar cuotas entre integraciones
        // distintas que compartan salida de red.
        RateLimiter::for('api-n8n', function (Request $request) {
            return Limit::perMinute(300)->by(
                $request->user()?->currentAccessToken()?->id ?: $request->ip()
            );
        });

        // Catálogo público (/catalogo, /catalogo/{categoría}): las peticiones
        // AJAX de filtros (Accept: application/json) tienen un tope por IP más
        // bajo que la navegación normal.
        RateLimiter::for('catalog', function (Request $request) {
            return $request->wantsJson()
                ? Limit::perMinute(90)->by($request->ip())
                : Limit::perMinute(240)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::middleware(['web', 'auth'])
                ->prefix('admin')
                ->as('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web', 'auth:customer', 'portal.access'])
                ->prefix('customer')
                ->as('customer.')
                ->group(base_path('routes/customer.php'));

            Route::middleware(['web', 'auth', 'role:supplier'])
                ->prefix('supplier')
                ->as('supplier.')
                ->group(base_path('routes/supplier.php'));

        });
    }
}
