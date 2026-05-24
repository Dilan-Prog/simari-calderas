<?php

use App\Http\Controllers\Admin\GoogleAdsController;
use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\ClientManageController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\QuoteController;
use App\Http\Controllers\Backend\UserManageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\SupplierManageController;


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
    Route::post('/clientes/parse-cfdi', 'parseCfdi')->name('clients.parse-cfdi');
    Route::get('/clientes/editar-cliente/{id}', 'edit')->name('clients.edit');
    Route::put('/clientes/editar-cliente/{id}', 'update')->name('clients.update');
    Route::delete('/clientes/eliminar-cliente/{id}', 'destroy')->name('clients.destroy');
    Route::get('/clientes/informacion/{id}', 'information')->name('clients.information');
});

Route::controller(SupplierManageController::class)->group(function () {
    Route::get('/proveedores', 'index')->name('suppliers.index');
    Route::get('/proveedores/mostrar-proveedor/{id}', 'show')->name('suppliers.show');
    Route::post('/proveedores/crear-proveedor', 'store')->name('suppliers.store');
    Route::get('/proveedores/editar-proveedor/{id}', 'edit')->name('suppliers.edit');
    Route::put('/proveedores/editar-proveedor/{id}', 'update')->name('suppliers.update');
    Route::delete('/proveedores/eliminar-proveedor/{id}', 'destroy')->name('suppliers.destroy');
    Route::get('/proveedores/informacion/{id}', 'information')->name('suppliers.information');
});

Route::controller(ProductController::class)->group(function () {
    Route::get('/productos', 'index')->name('products.index');
    Route::get('/productos/nuevo', 'create')->name('products.create');
    Route::post('/productos/nuevo', 'store')->name('products.store');
    Route::get('/productos/editar/{id}', 'edit')->name('products.edit');
    Route::put('/productos/editar/{id}', 'update')->name('products.update');
    Route::delete('/productos/eliminar/{id}', 'destroy')->name('products.destroy');
});

Route::controller(CategoryController::class)->group(function () {
    Route::get('/categorias', 'index')->name('categories.index');
    Route::post('/categorias/crear', 'store')->name('categories.store');
    Route::get('/categorias/editar/{id}', 'edit')->name('categories.edit');
    Route::put('/categorias/editar/{id}',       'update')->name('categories.update');
    Route::delete('/categorias/eliminar/{id}',  'destroy')->name('categories.destroy');
});

Route::controller(ProductController::class)->group(function () {
    Route::get('/productos', 'index')->name('products.index');
    Route::get('/productos/crear-producto', 'create')->name('products.create');
});

Route::controller(QuoteController::class)->prefix('cotizaciones')->name('quotes.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/crear', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/buscar-productos', 'searchProducts')->name('search-products');
    Route::get('/{quote}', 'show')->name('show');
    Route::get('/{quote}/editar', 'edit')->name('edit');
    Route::put('/{quote}', 'update')->name('update');
    Route::delete('/{quote}', 'destroy')->name('destroy');
    Route::get('/{quote}/pdf', 'downloadPdf')->name('pdf');
    Route::get('/{quote}/pdf-preview', 'previewPdf')->name('pdf-preview');
    Route::post('/{quote}/enviar-correo', 'sendEmail')->name('send-email');
    Route::patch('/{quote}/estado', 'updateStatus')->name('update-status');
});

Route::controller(GoogleAdsController::class)->prefix('google-ads')->name('google-ads.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/datatable', 'datatable')->name('datatable');
    Route::get('/{id}', 'show')->name('show');
});


