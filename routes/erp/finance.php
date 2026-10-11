<?php

use App\Http\Controllers\Backend\Finance\PayableController;
use App\Http\Controllers\Backend\Finance\PaymentController;
use App\Http\Controllers\Backend\Finance\ReceivableController;
use Illuminate\Support\Facades\Route;

// Finanzas (gestión interna). Heredan prefijo admin y nombres admin.*
// Las rutas estáticas van antes de /{id} para que no se interpreten como comodín.

// ── Cuentas por cobrar ──────────────────────────────────────
Route::controller(ReceivableController::class)
    ->middleware('permission:receivables')
    ->prefix('cuentas-por-cobrar')
    ->name('receivables.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::get('/antiguedad', 'aging')->name('aging');
        Route::get('/estado-de-cuenta/{party}', 'statement')->whereNumber('party')->name('statement');
        Route::get('/nueva', 'create')->name('create')->middleware('permission:receivables,create');
        Route::post('/', 'store')->name('store')->middleware('permission:receivables,create');
        Route::post('/desde-pedido/{salesOrder}', 'fromSalesOrder')->name('from-sales-order')->middleware('permission:receivables,create');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::get('/{id}/editar', 'edit')->whereNumber('id')->name('edit')->middleware('permission:receivables,edit');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update')->middleware('permission:receivables,edit');
        Route::patch('/{id}/cancelar', 'cancel')->whereNumber('id')->name('cancel')->middleware('permission:receivables,edit');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy')->middleware('permission:receivables,delete');
    });

// ── Cuentas por pagar ───────────────────────────────────────
Route::controller(PayableController::class)
    ->middleware('permission:payables')
    ->prefix('cuentas-por-pagar')
    ->name('payables.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::get('/antiguedad', 'aging')->name('aging');
        Route::get('/estado-de-cuenta/{party}', 'statement')->whereNumber('party')->name('statement');
        Route::get('/nueva', 'create')->name('create')->middleware('permission:payables,create');
        Route::post('/', 'store')->name('store')->middleware('permission:payables,create');
        Route::post('/desde-orden-compra/{purchaseOrder}', 'fromPurchaseOrder')->name('from-purchase-order')->middleware('permission:payables,create');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::get('/{id}/editar', 'edit')->whereNumber('id')->name('edit')->middleware('permission:payables,edit');
        Route::put('/{id}', 'update')->whereNumber('id')->name('update')->middleware('permission:payables,edit');
        Route::patch('/{id}/cancelar', 'cancel')->whereNumber('id')->name('cancel')->middleware('permission:payables,edit');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy')->middleware('permission:payables,delete');
    });

// ── Pagos ───────────────────────────────────────────────────
Route::controller(PaymentController::class)
    ->middleware('permission:payments')
    ->prefix('pagos')
    ->name('payments.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::get('/documentos-abiertos', 'openDocuments')->name('open-documents');
        Route::get('/nuevo', 'create')->name('create')->middleware('permission:payments,create');
        Route::post('/', 'store')->name('store')->middleware('permission:payments,create');
        Route::get('/{id}', 'show')->whereNumber('id')->name('show');
        Route::get('/{id}/comprobante', 'proof')->whereNumber('id')->name('proof');
        Route::post('/{id}/aplicar', 'apply')->whereNumber('id')->name('apply')->middleware('permission:payments,edit');
        Route::delete('/{id}/aplicaciones/{application}', 'unapply')->whereNumber('id')->whereNumber('application')->name('unapply')->middleware('permission:payments,edit');
        Route::patch('/{id}/conciliar', 'reconcile')->whereNumber('id')->name('reconcile')->middleware('permission:payments,edit');
        Route::patch('/{id}/desconciliar', 'unreconcile')->whereNumber('id')->name('unreconcile')->middleware('permission:payments,edit');
        Route::patch('/{id}/cancelar', 'cancel')->whereNumber('id')->name('cancel')->middleware('permission:payments,edit');
        Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy')->middleware('permission:payments,delete');
    });
