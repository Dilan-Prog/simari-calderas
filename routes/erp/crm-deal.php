<?php

use App\Http\Controllers\Backend\Crm\LeadDealController;
use Illuminate\Support\Facades\Route;

// Negocio del lead (tarjeta "Negocio" de la bandeja): crear y ligar cotizaciones. Se carga dentro del grupo admin.
Route::prefix('leads/{lead}/negocio')->whereNumber('lead')->name('leads-inbox.deal.')->group(function () {
    Route::post('/', [LeadDealController::class, 'store'])->middleware(['permission:leads-inbox,edit', 'permission:deals,create'])->name('store');
    Route::post('/cotizaciones', [LeadDealController::class, 'linkQuote'])->middleware(['permission:leads-inbox,edit', 'permission:deals,edit'])->name('quotes.link');
    Route::delete('/cotizaciones/{quote}', [LeadDealController::class, 'unlinkQuote'])->whereNumber('quote')->middleware(['permission:leads-inbox,edit', 'permission:deals,edit'])->name('quotes.unlink');
});
