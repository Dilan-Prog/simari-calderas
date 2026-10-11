<?php

use App\Http\Controllers\Backend\ProjectController;
use Illuminate\Support\Facades\Route;

// Proyectos (Fase 1 ERP). Heredan prefijo admin. / admin.
Route::controller(ProjectController::class)
    ->middleware('permission:projects')
    ->prefix('proyectos')
    ->name('projects.')
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create')->middleware('permission:projects,create');
        Route::post('/', 'store')->name('store')->middleware('permission:projects,create');

        // Búsquedas AJAX (antes de {project})
        Route::get('/buscar-clientes', 'searchCustomers')->name('customers');
        Route::get('/buscar-proyectos', 'searchProjects')->name('search');
        Route::get('/buscar-documentos', 'searchDocuments')->name('documents');
        Route::post('/vincular-documento', 'linkDocument')->name('link-document')->middleware('permission:projects,edit');

        Route::get('/{project}', 'show')->name('show');
        Route::get('/{project}/editar', 'edit')->name('edit')->middleware('permission:projects,edit');
        Route::put('/{project}', 'update')->name('update')->middleware('permission:projects,edit');
        Route::delete('/{project}', 'destroy')->name('destroy')->middleware('permission:projects,delete');
        Route::patch('/{project}/estado', 'updateStatus')->name('update-status')->middleware('permission:projects,edit');
        Route::patch('/{project}/fases/{phase}', 'updatePhase')->name('update-phase')->middleware('permission:projects,edit');
        Route::post('/{project}/documentos', 'attachDocument')->name('attach')->middleware('permission:projects,edit');
        Route::delete('/{project}/documentos', 'detachDocument')->name('detach')->middleware('permission:projects,edit');
    });
