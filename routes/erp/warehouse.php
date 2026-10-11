<?php

use App\Http\Controllers\Backend\StockCountController;
use App\Http\Controllers\Backend\StockReservationController;
use App\Http\Controllers\Backend\WarehouseLocationController;
use App\Http\Controllers\Backend\WarehouseTransferController;
use Illuminate\Support\Facades\Route;

// Almacén (ampliaciones). Heredan prefijo admin./admin.

// Traspasos entre almacenes
Route::controller(WarehouseTransferController::class)
    ->middleware('permission:warehouse-transfers')
    ->prefix('traspasos')
    ->name('warehouse-transfers.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:warehouse-transfers,create');
        Route::post('/', 'store')->name('store')->middleware('permission:warehouse-transfers,create');
        Route::get('/productos', 'products')->name('products');
        Route::get('/{transfer}', 'show')->name('show');
        Route::get('/{transfer}/editar', 'edit')->name('edit')->middleware('permission:warehouse-transfers,edit');
        Route::put('/{transfer}', 'update')->name('update')->middleware('permission:warehouse-transfers,edit');
        Route::post('/{transfer}/enviar', 'ship')->name('ship')->middleware('permission:warehouse-transfers,edit');
        Route::post('/{transfer}/recibir', 'receive')->name('receive')->middleware('permission:warehouse-transfers,edit');
        Route::post('/{transfer}/cancelar', 'cancel')->name('cancel')->middleware('permission:warehouse-transfers,edit');
        Route::delete('/{transfer}', 'destroy')->name('destroy')->middleware('permission:warehouse-transfers,delete');
    });

// Conteos físicos
Route::controller(StockCountController::class)
    ->middleware('permission:stock-counts')
    ->prefix('conteos-fisicos')
    ->name('stock-counts.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:stock-counts,create');
        Route::post('/', 'store')->name('store')->middleware('permission:stock-counts,create');
        Route::get('/productos', 'products')->name('products');
        Route::get('/{count}', 'show')->name('show');
        Route::patch('/{count}/lineas/{line}', 'updateLine')->name('lines.update')->middleware('permission:stock-counts,edit');
        Route::post('/{count}/cerrar', 'close')->name('close')->middleware('permission:stock-counts,edit');
        Route::post('/{count}/ajustar', 'adjust')->name('adjust')->middleware('permission:stock-counts,edit');
        Route::get('/{count}/diferencias.csv', 'export')->name('export');
        Route::delete('/{count}', 'destroy')->name('destroy')->middleware('permission:stock-counts,delete');
    });

// Ubicaciones de almacén (permiso del módulo Inventario)
Route::controller(WarehouseLocationController::class)
    ->middleware('permission:inventory')
    ->prefix('ubicaciones-almacen')
    ->name('warehouse-locations.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store')->middleware('permission:inventory,create');
        Route::post('/asignar', 'assign')->name('assign')->middleware('permission:inventory,edit');
        Route::put('/{location}', 'update')->name('update')->middleware('permission:inventory,edit');
        Route::delete('/{location}', 'destroy')->name('destroy')->middleware('permission:inventory,delete');
    });

// Reservas de material por proyecto (permiso del módulo Inventario)
Route::controller(StockReservationController::class)
    ->middleware('permission:inventory')
    ->prefix('reservas-stock')
    ->name('stock-reservations.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/liberar', 'release')->name('release')->middleware('permission:inventory,edit');
    });
