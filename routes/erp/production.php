<?php

use App\Http\Controllers\Backend\ProductionOrderController;
use App\Http\Controllers\Backend\ServiceCalendarController;
use Illuminate\Support\Facades\Route;

// ERP Fase 5 — Producción. Heredan prefijo admin. / admin.
Route::controller(ProductionOrderController::class)
    ->middleware('permission:production-orders')
    ->prefix('ordenes-produccion')
    ->name('production-orders.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:production-orders,create');
        Route::post('/', 'store')->name('store')->middleware('permission:production-orders,create');
        Route::get('/buscar-productos', 'searchProducts')->name('search-products');

        Route::get('/{productionOrder}', 'show')->name('show')->whereNumber('productionOrder');
        Route::get('/{productionOrder}/editar', 'edit')->name('edit')->middleware('permission:production-orders,edit')->whereNumber('productionOrder');
        Route::put('/{productionOrder}', 'update')->name('update')->middleware('permission:production-orders,edit')->whereNumber('productionOrder');
        Route::delete('/{productionOrder}', 'destroy')->name('destroy')->middleware('permission:production-orders,delete')->whereNumber('productionOrder');

        Route::post('/{productionOrder}/liberar', 'release')->name('release')->middleware('permission:production-orders,edit')->whereNumber('productionOrder');
        Route::post('/{productionOrder}/consumir', 'consume')->name('consume')->middleware('permission:production-orders,edit')->whereNumber('productionOrder');
        Route::post('/{productionOrder}/terminar', 'finish')->name('finish')->middleware('permission:production-orders,edit')->whereNumber('productionOrder');
        Route::post('/{productionOrder}/cancelar', 'cancel')->name('cancel')->middleware('permission:production-orders,delete')->whereNumber('productionOrder');
    });

Route::controller(ServiceCalendarController::class)
    ->middleware('permission:service-calendar')
    ->prefix('calendario-servicios')
    ->name('service-calendar.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/eventos', 'events')->name('events');
        Route::patch('/{service}/reprogramar', 'reschedule')->name('reschedule')->middleware('permission:service-calendar,edit')->whereNumber('service');
    });
