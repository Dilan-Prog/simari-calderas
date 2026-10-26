<?php

use App\Http\Controllers\Backend\WaterAnalysisParameterController;
use Illuminate\Support\Facades\Route;

// Parámetros del análisis químico del agua (límites mínimo/máximo que ve el cliente en su portal).
// Heredan prefijo admin./admin. y cuelgan del permiso de Configuración ERP.
Route::controller(WaterAnalysisParameterController::class)
    ->middleware('permission:erp-settings')
    ->prefix('erp/configuracion/analisis-agua')
    ->name('erp-settings.water-analysis.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/{parameter}', 'update')->whereNumber('parameter')->name('update')->middleware('permission:erp-settings,edit');
    });
