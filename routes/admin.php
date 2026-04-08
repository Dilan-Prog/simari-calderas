<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\UserManageController;
use Illuminate\Support\Facades\Route;

Route::get('/',[AdminController::class,'dashboard'])->name('dashboard');
// Partial de Usuarios — devuelve solo el HTML del contenido (para fetch desde el dashboard)
Route::get('/usuarios-partial', function () {
    $roles = \App\Models\Role::all();
    return view('admin.users.partial', compact('roles'));
})->name('admin.users.partial');
// Vista completa de Usuarios (con layout propio)
Route::get('/admin/usuarios', [UserManageController::class, 'index'])->name('admin.users.index');

// Crear usuario
Route::post('/admin/usuarios', [UserManageController::class, 'store'])->name('admin.users.store');


// Route::controller(AdminController::class)->group(function () {
//     Route::get('/gestion-usuarios', 'index')->name('users.index');
//     Route::post('/gestion-usuarios/crear',  'store')->name('users.store');
// });
