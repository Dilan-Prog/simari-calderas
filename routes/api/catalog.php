<?php

use App\Http\Controllers\Api\Catalog\CollectionController;
use App\Http\Controllers\Api\Catalog\ProductController;
use App\Http\Controllers\Api\Customers\CustomerAddressController;
use App\Http\Controllers\Api\Customers\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fase 5 — Catálogo (Products / Collections) y Clientes
|--------------------------------------------------------------------------
| Ver plan: C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md
| Abilities disponibles (config/api_abilities.php): products:read,
| collections:read, customers:read, customers:write.
|
| Este archivo se incluye tal cual desde routes/api.php dentro del grupo v1
| (auth:sanctum + throttle:api-n8n + log.api ya aplicados) -- cada ruta solo
| agrega su propio token.ability:xxx encima de eso.
*/

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index'])
        ->middleware('token.ability:products:read');

    Route::get('/{product}', [ProductController::class, 'show'])
        ->middleware('token.ability:products:read');
});

Route::prefix('collections')->group(function () {
    Route::get('/', [CollectionController::class, 'index'])
        ->middleware('token.ability:collections:read');

    Route::get('/{collection}', [CollectionController::class, 'show'])
        ->middleware('token.ability:collections:read');
});

Route::prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])
        ->middleware('token.ability:customers:read');

    Route::post('/', [CustomerController::class, 'store'])
        ->middleware('token.ability:customers:write');

    Route::get('/{customer}', [CustomerController::class, 'show'])
        ->middleware('token.ability:customers:read');

    Route::put('/{customer}', [CustomerController::class, 'update'])
        ->middleware('token.ability:customers:write');

    Route::patch('/{customer}', [CustomerController::class, 'update'])
        ->middleware('token.ability:customers:write');

    // Anidada: solo lectura de las direcciones de UN cliente concreto.
    Route::get('/{customer}/addresses', [CustomerAddressController::class, 'index'])
        ->middleware('token.ability:customers:read');
});
