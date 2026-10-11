<?php

use App\Http\Controllers\Backend\BomController;
use Illuminate\Support\Facades\Route;

// ERP Fase 2A — Listas de materiales (BOM). Heredan prefijo admin./admin.
Route::controller(BomController::class)
    ->prefix('listas-de-materiales')
    ->name('boms.')
    ->middleware('permission:boms')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:boms,create');
        Route::post('/', 'store')->name('store')->middleware('permission:boms,create');
        Route::get('/buscar-productos', 'searchProducts')->name('search-products');
        Route::get('/{bom}', 'show')->name('show')->whereNumber('bom');
        Route::get('/{bom}/editar', 'edit')->name('edit')->middleware('permission:boms,edit')->whereNumber('bom');
        Route::put('/{bom}', 'update')->name('update')->middleware('permission:boms,edit')->whereNumber('bom');
        Route::delete('/{bom}', 'destroy')->name('destroy')->middleware('permission:boms,delete')->whereNumber('bom');
        Route::post('/{bom}/duplicar', 'duplicate')->name('duplicate')->middleware('permission:boms,create')->whereNumber('bom');
        Route::post('/{bom}/vigente', 'activate')->name('activate')->middleware('permission:boms,edit')->whereNumber('bom');
    });
