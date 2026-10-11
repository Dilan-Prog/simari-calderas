<?php

use App\Http\Controllers\Backend\PickupController;
use App\Http\Controllers\Backend\ShipmentB2bController;
use Illuminate\Support\Facades\Route;

// ERP · Logística. Heredan prefijo admin./admin.

// Envíos B2B (los de tienda siguen en routes/admin.php). 'crear' va antes del wildcard {shipment}.
Route::controller(ShipmentB2bController::class)
    ->middleware('permission:shipments')
    ->prefix('envios')
    ->name('shipments.')
    ->group(function () {
        Route::get('/crear', 'create')->name('create')->middleware('permission:shipments,create');
        Route::post('/', 'store')->name('store-b2b')->middleware('permission:shipments,create');
        Route::get('/{shipment}', 'show')->name('show')->whereNumber('shipment');
        Route::get('/{shipment}/editar', 'edit')->name('edit')->whereNumber('shipment')->middleware('permission:shipments,edit');
        Route::put('/{shipment}', 'update')->name('update')->whereNumber('shipment')->middleware('permission:shipments,edit');
    });

// Recolecciones
Route::controller(PickupController::class)
    ->middleware('permission:pickups')
    ->prefix('recolecciones')
    ->name('pickups.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/ruta', 'dayRoute')->name('route');
        Route::get('/crear', 'create')->name('create')->middleware('permission:pickups,create');
        Route::post('/', 'store')->name('store')->middleware('permission:pickups,create');
        Route::get('/{pickup}', 'show')->name('show')->whereNumber('pickup');
        Route::get('/{pickup}/movil', 'mobile')->name('mobile')->whereNumber('pickup')->middleware('permission:pickups,edit');
        Route::get('/{pickup}/editar', 'edit')->name('edit')->whereNumber('pickup')->middleware('permission:pickups,edit');
        Route::put('/{pickup}', 'update')->name('update')->whereNumber('pickup')->middleware('permission:pickups,edit');
        Route::delete('/{pickup}', 'destroy')->name('destroy')->whereNumber('pickup')->middleware('permission:pickups,delete');
        Route::patch('/{pickup}/estatus', 'updateStatus')->name('update-status')->whereNumber('pickup')->middleware('permission:pickups,edit');
        Route::post('/{pickup}/evidencias', 'storeEvidence')->name('evidences.store')->whereNumber('pickup')->middleware('permission:pickups,edit');
        Route::delete('/{pickup}/evidencias/{evidence}', 'destroyEvidence')->name('evidences.destroy')->whereNumber('pickup')->middleware('permission:pickups,delete');
        Route::post('/{pickup}/firma', 'sign')->name('sign')->whereNumber('pickup')->middleware('permission:pickups,edit');
    });
