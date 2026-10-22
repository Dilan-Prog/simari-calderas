<?php

use App\Http\Controllers\Backend\Crm\LeadCustomerController;
use Illuminate\Support\Facades\Route;

// Agregar un lead (de cualquier canal) a la base de clientes. Se carga dentro del grupo admin.
Route::prefix('leads')->name('leads-inbox.customer.')->group(function () {
    Route::get('/{lead}/cliente', [LeadCustomerController::class, 'form'])
        ->whereNumber('lead')->middleware('permission:leads-inbox,edit')->name('form');
    Route::post('/{lead}/cliente', [LeadCustomerController::class, 'store'])
        ->whereNumber('lead')->middleware(['permission:leads-inbox,edit', 'permission:clients,create'])->name('store');
});
