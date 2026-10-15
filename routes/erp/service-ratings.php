<?php

use App\Http\Controllers\Backend\ServiceRatingController;
use App\Http\Controllers\Backend\SurveySettingController;
use Illuminate\Support\Facades\Route;

// "Califica este servicio". Se carga dentro del grupo admin (prefijo admin/, nombres admin.*).

// Pantalla de calificación del cliente (modo quiosco) tras firmar el reporte
Route::controller(ServiceRatingController::class)
    ->middleware('permission:service-reports,edit')
    ->prefix('service-reports')->name('service-reports.')
    ->group(function () {
        Route::match(['get', 'post'], '/{report}/calificar', 'rate')->whereNumber('report')->name('rate');
        Route::post('/{report}/calificar/omitir', 'skip')->whereNumber('report')->name('rate.skip');
    });

// Opiniones de servicio (listado, detalle, exportación)
Route::controller(ServiceRatingController::class)
    ->middleware('permission:service-reports')
    ->prefix('opiniones-servicio')->name('service-ratings.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/exportar', 'export')->name('export');
        Route::get('/{rating}', 'show')->whereNumber('rating')->name('show');
        Route::put('/{rating}/servicio', 'assignService')->whereNumber('rating')->name('assign-service')
            ->middleware('permission:service-reports,edit');
    });

// Panel "Encuesta de satisfacción" dentro de Configuración ERP
Route::controller(SurveySettingController::class)
    ->middleware('permission:erp-settings')
    ->prefix('erp/configuracion/encuesta')->name('erp-settings.survey.')
    ->group(function () {
        Route::put('/ajustes', 'updateSettings')->name('settings')->middleware('permission:erp-settings,edit');

        Route::post('/tarjetas', 'storeCard')->name('cards.store')->middleware('permission:erp-settings,create');
        Route::put('/tarjetas/{card}', 'updateCard')->whereNumber('card')->name('cards.update')->middleware('permission:erp-settings,edit');
        Route::post('/tarjetas/{card}/mover', 'moveCard')->whereNumber('card')->name('cards.move')->middleware('permission:erp-settings,edit');
        Route::delete('/tarjetas/{card}', 'destroyCard')->whereNumber('card')->name('cards.destroy')->middleware('permission:erp-settings,delete');

        Route::post('/variables', 'storeQuestion')->name('questions.store')->middleware('permission:erp-settings,create');
        Route::put('/variables/{question}', 'updateQuestion')->whereNumber('question')->name('questions.update')->middleware('permission:erp-settings,edit');
        Route::post('/variables/{question}/mover', 'moveQuestion')->whereNumber('question')->name('questions.move')->middleware('permission:erp-settings,edit');
        Route::delete('/variables/{question}', 'destroyQuestion')->whereNumber('question')->name('questions.destroy')->middleware('permission:erp-settings,delete');
    });
