<?php

use App\Http\Controllers\Api\Sales\QuoteController;
use App\Http\Controllers\Api\Sales\SalesOrderController;
use App\Http\Controllers\Api\Sales\StoreOrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fase 4 — Cotizaciones y Pedidos de venta
|--------------------------------------------------------------------------
| Ver plan: C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md
| Abilities disponibles (config/api_abilities.php): quotes:read, quotes:write,
| sales-orders:read, store-orders:read.
|
| Este archivo se incluye tal cual desde routes/api.php dentro del grupo v1
| (auth:sanctum + throttle:api-n8n + log.api ya aplicados) -- solo agregar
| rutas con su propio token.ability:xxx aquí dentro.
|
| SalesOrder/StoreOrder son solo lectura en esta fase (el plan es explícito
| en esto -- sincronización hacia afuera, no creación vía API todavía).
| Quote::accept() reutiliza QuoteService::processAcceptance() (el mismo
| auto-split a SalesOrder que ya dispara el panel), no lo reimplementa.
*/

Route::controller(QuoteController::class)->prefix('quotes')->group(function () {
    Route::get('/', 'index')->middleware('token.ability:quotes:read');
    Route::get('/{quote}', 'show')->middleware('token.ability:quotes:read');
    Route::post('/', 'store')->middleware('token.ability:quotes:write');
    Route::post('/{quote}/accept', 'accept')->middleware('token.ability:quotes:write');
});

Route::controller(SalesOrderController::class)->prefix('sales-orders')->group(function () {
    Route::get('/', 'index')->middleware('token.ability:sales-orders:read');
    Route::get('/{salesOrder}', 'show')->middleware('token.ability:sales-orders:read');
});

Route::controller(StoreOrderController::class)->prefix('store-orders')->group(function () {
    Route::get('/', 'index')->middleware('token.ability:store-orders:read');
    Route::get('/{storeOrder}', 'show')->middleware('token.ability:store-orders:read');
});
