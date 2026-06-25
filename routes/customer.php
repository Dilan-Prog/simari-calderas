<?php

use App\Http\Controllers\Backend\Customers\CustomerController;
use App\Http\Controllers\Customers\ServiceReportController;
use App\Http\Controllers\Customers\TechnicalServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerController::class, 'index'])->name('dashboard');

Route::controller(ServiceReportController::class)
    ->prefix('service-reports')
    ->name('service-reports.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{report}', 'show')->name('show');
        Route::get('/{report}/pdf', 'downloadPdf')->name('download-pdf');
        Route::get('/{report}/pdf-preview', 'previewPdf')->name('pdf-preview');
    });

Route::controller(TechnicalServiceController::class)
    ->prefix('technical-services')
    ->name('technical-services.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{service}', 'show')->name('show');
    });
