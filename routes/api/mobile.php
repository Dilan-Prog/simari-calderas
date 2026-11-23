<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CustomerController;
use App\Http\Controllers\Api\Mobile\RoleController;
use App\Http\Controllers\Api\Mobile\ServiceReportController;
use App\Http\Controllers\Api\Mobile\TechnicalServiceController;
use App\Http\Controllers\Api\Mobile\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API móvil v1 — app del personal (React Native / Expo, carpeta app-movil/)
|--------------------------------------------------------------------------
| Prefijo: /api/mobile/v1 (se incluye desde routes/api.php, FUERA del grupo
| v1 de integraciones). Documentación: docs/api-movil.md.
|
| Autenticación: auth:sanctum con tokens emitidos a App\Models\User por
| POST /login (ability 'mobile') + mobile.user, que rechaza tokens de
| ApiClient. Autorización por módulo: mobile.permission:modulo,accion (mismo
| criterio que el middleware `permission` del panel, pero respondiendo JSON).
| Rate limit propio 'mobile' (120/min por usuario o IP) y 'mobile-login'
| (5/min por email + IP) definidos en RouteServiceProvider.
*/

Route::middleware('throttle:mobile')->name('mobile.v1.')->group(function () {
    // ── Público ────────────────────────────────────────────────────────────
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:mobile-login')
        ->name('login');

    // PDF del reporte: sin token, protegido por URL firmada temporal (la app la
    // obtiene en `pdf_url` del detalle y la abre en el navegador / WebView).
    Route::get('/reportes/{report}/pdf', [ServiceReportController::class, 'pdf'])
        ->whereNumber('report')
        ->middleware('signed')
        ->name('reportes.pdf');

    // ── Autenticado (token de usuario del personal) ───────────────────────
    Route::middleware(['auth:sanctum', 'mobile.user'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');

        Route::middleware('mobile.permission:service-reports,view')->group(function () {
            Route::get('/reportes', [ServiceReportController::class, 'index'])->name('reportes.index');
            Route::get('/reportes/{id}', [ServiceReportController::class, 'show'])
                ->whereNumber('id')
                ->name('reportes.show');
        });

        // Alta de reporte desde la app (asistente de 5 pasos, ya firmado).
        // Idempotente por el header Idempotency-Key.
        Route::post('/reportes', [ServiceReportController::class, 'store'])
            ->middleware('mobile.permission:service-reports,create')
            ->name('reportes.store');

        // Buscador de clientes (paso «Cliente» del asistente y «Programar
        // servicio»). El permiso se revisa en el controlador: basta con
        // service-reports,create o technical-services,create.
        Route::get('/clientes', [CustomerController::class, 'index'])->name('clientes.index');

        Route::get('/servicios', [TechnicalServiceController::class, 'index'])
            ->middleware('mobile.permission:technical-services,view')
            ->name('servicios.index');
        Route::post('/servicios', [TechnicalServiceController::class, 'store'])
            ->middleware('mobile.permission:technical-services,create')
            ->name('servicios.store');

        // Usuarios y roles: solo lectura, mismo permiso que el panel.
        Route::get('/usuarios', [UserController::class, 'index'])
            ->middleware('mobile.permission:users,view')
            ->name('usuarios.index');
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('mobile.permission:roles,view')
            ->name('roles.index');
    });
});
