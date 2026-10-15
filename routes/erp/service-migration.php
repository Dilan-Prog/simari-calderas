<?php

use App\Http\Controllers\Backend\ServiceMigrationController;
use Illuminate\Support\Facades\Route;

// Migración de cotizaciones a servicios web (Servicio ID). Heredan prefijo admin. / admin.
Route::controller(ServiceMigrationController::class)->group(function () {
    Route::prefix('migracion-servicios')->name('service-migration.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:service-pages');
        Route::get('/exportar', 'export')->name('export')->middleware('permission:service-pages');
        // Buscador de páginas de servicio para los selectores (cotizaciones, servicios técnicos, reportes):
        // el permiso se valida dentro del controlador (cualquiera de esos módulos).
        Route::get('/buscar-servicios', 'searchServicePages')->name('search');
        Route::post('/asignar-lote', 'bulkAssign')->name('bulk-assign')->middleware(['permission:service-pages', 'permission:quotes,edit']);
        Route::post('/modo-nuevo', 'toggleNewMode')->name('new-mode')->middleware('permission:service-pages,edit');
    });

    // Servicio ID de una cotización (vista show, asistente y pantalla de migración).
    Route::patch('cotizaciones/{quote}/servicio-web', 'assignQuote')->name('quotes.service-page')
        ->middleware('permission:quotes,edit');
});
