<?php

use App\Http\Controllers\Admin\GoogleAdsController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\ClientManageController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\UserManageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

Route::controller(UserManageController::class)->group(function () {
    Route::get('/usuarios', 'index')->name('users.index');
    Route::get('/usuarios/mostrar-usuario/{id}', 'show')->name('users.show');
    Route::post('/usuarios/crear-usuario', action: 'store')->name('users.store');
    Route::put('/usuarios/editar-usuario/{id}', 'update')->name('users.update');
    Route::delete('/usuarios/eliminar-usuario/{id}', 'destroy')->name('users.destroy');
});

Route::controller(ClientManageController::class)->group(function () {
    Route::get('/clientes', 'index')->name('clients.index');
    Route::post('/clientes/crear-usuario/', action: 'store')->name('clients.store');
    Route::get('/clientes/editar-cliente/{id}', 'edit')->name('clients.edit');
    Route::put('/clientes/editar-cliente/{id}', 'update')->name('clients.update');
    Route::delete('/clientes/eliminar-cliente/{id}', 'destroy')->name('clients.destroy');
    Route::get('/clientes/informacion/{id}', 'information')->name('clients.information');
});

Route::controller(ProductController::class)->group(function () {
    Route::get('/productos', 'index')->name('products.index');
    Route::get('/productos/crear-producto', 'create')->name('products.create');
});

Route::controller(GoogleAdsController::class)->prefix('google-ads')->name('google-ads.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/datatable', 'datatable')->name('datatable');
    Route::get('/{id}', 'show')->name('show');
});


