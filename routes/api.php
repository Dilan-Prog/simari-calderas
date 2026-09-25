<?php

use App\Http\Controllers\Api\AdTrackingController;
use App\Http\Controllers\Api\PoolCalculatorLeadController;
use App\Http\Controllers\Backend\Marketing\GoogleConversionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes — called from the frontend when a visitor arrives from a Google Ads click
Route::prefix('v1/google-ads')->group(function () {
    Route::post('/', [GoogleConversionController::class, 'store']);
});

// Protected routes — internal use only (listing, retry)
Route::prefix('v1/google-ads')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [GoogleConversionController::class, 'index']);
    Route::post('/retry', [GoogleConversionController::class, 'retry']);
});

// Public routes — llamadas desde el JS de tracking del frontend para
// persistir la identidad del visitante (visitor_uuid) y sus eventos de
// interés relacionados con anuncios de Google Ads. Throttle por IP aquí;
// storeEvent además aplica un segundo throttle por visitor_uuid internamente.
Route::prefix('v1/ad-tracking')->middleware('throttle:60,1')->group(function () {
    Route::post('/visit', [AdTrackingController::class, 'storeVisit']);
    Route::post('/event', [AdTrackingController::class, 'storeEvent']);
});

// Public route — llamada desde el JS de la calculadora de dimensionamiento
// de bomba de calor para alberca al capturar un lead.
Route::prefix('v1/pool-calculator')->middleware('throttle:60,1')->group(function () {
    Route::post('/leads', [PoolCalculatorLeadController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| API de integraciones (N8N y otros clientes externos de confianza)
|--------------------------------------------------------------------------
|
| Autenticación: auth:sanctum, contra tokens emitidos a App\Models\ApiClient
| (panel Integraciones > API / N8N, ver IntegrationController). Autorización:
| token.ability:xxx por ruta (App\Http\Middleware\EnsureTokenAbility, NO el
| middleware `permission` de roles humanos -- ver comentario en esa clase).
| Rate limit propio 'api-n8n' (RouteServiceProvider), separado del limiter
| público 'api' que usan las rutas de arriba. Cada archivo requerido es
| dueño exclusivo de sus propias rutas -- así se pueden tocar en paralelo
| sin pisarse entre sí.
|
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api-n8n', 'log.api'])
    ->group(function () {
        require __DIR__ . '/api/workflows.php';
        require __DIR__ . '/api/crm.php';
        require __DIR__ . '/api/quotes.php';
        require __DIR__ . '/api/catalog.php';
        require __DIR__ . '/api/whatsapp-marketing.php';
    });
