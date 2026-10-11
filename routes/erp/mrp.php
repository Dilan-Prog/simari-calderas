<?php

use App\Http\Controllers\Backend\MrpController;
use Illuminate\Support\Facades\Route;

// ERP Fase 2 — MRP y reservas. Heredan prefijo admin./admin.
Route::controller(MrpController::class)
    ->prefix('mrp')
    ->name('mrp.')
    ->middleware('permission:mrp')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:mrp,create');
        Route::post('/', 'store')->name('store')->middleware('permission:mrp,create');
        Route::post('/ordenes-compra-lote', 'generatePurchaseOrdersBatch')->name('generate-purchase-orders-batch')->middleware('permission:mrp,create');
        Route::get('/buscar-productos', 'searchProducts')->name('search-products');
        Route::post('/desde-pedido/{salesOrder}', 'fromSalesOrder')->name('from-sales-order')->middleware('permission:mrp,create')->whereNumber('salesOrder');
        Route::get('/{requirement}', 'show')->name('show')->whereNumber('requirement');
        Route::post('/{requirement}/recalcular', 'recalculate')->name('recalculate')->middleware('permission:mrp,edit')->whereNumber('requirement');
        Route::post('/{requirement}/reservar', 'reserve')->name('reserve')->middleware('permission:mrp,edit')->whereNumber('requirement');
        Route::post('/{requirement}/liberar', 'release')->name('release')->middleware('permission:mrp,edit')->whereNumber('requirement');
        Route::post('/{requirement}/cancelar', 'cancel')->name('cancel')->middleware('permission:mrp,delete')->whereNumber('requirement');
        Route::post('/{requirement}/ordenes-compra', 'generatePurchaseOrders')->name('generate-purchase-orders')->middleware('permission:mrp,create')->whereNumber('requirement');
    });
