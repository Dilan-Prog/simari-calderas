<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\UserManageController;
use Illuminate\Support\Facades\Route;

Route::controller(AdminController::class)->group(function () {
    Route::get('/gestion-usuarios', 'index')->name('users.index');
    Route::post('/gestion-usuarios/crear',  'store')->name('users.store');
});
// Route::post('/administrador/gestion-usuarios/crear', [UserManageController::class, 'store'])->name('users.store');