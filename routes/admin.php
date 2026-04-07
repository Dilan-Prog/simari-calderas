<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\UserManageController;

Route::controller(UserManageController::class)->group(function () {
    Route::get('/gestion-usuarios', 'index')->name('users.index');
    Route::get('/gestion-usuarios/crear', 'create')->name('users.create');
    Route::post('/gestion-usuarios', 'store')->name('users.store');
    Route::get('/gestion-usuarios/{id}', 'show')->name('users.show');
    Route::get('/gestion-usuarios/{id}/editar', 'edit')->name('users.edit');
    Route::put('/gestion-usuarios/{id}', 'update')->name('users.update');
    Route::delete('/gestion-usuarios/{id}', 'destroy')->name('users.destroy');
});
