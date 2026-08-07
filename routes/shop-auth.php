<?php

use App\Http\Controllers\Frontend\Shop\AccountController;
use App\Http\Controllers\Frontend\Shop\Auth\LoginController;
use App\Http\Controllers\Frontend\Shop\Auth\PasswordResetController;
use App\Http\Controllers\Frontend\Shop\Auth\RegisterController;
use App\Http\Controllers\Frontend\Shop\PortalRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shop Auth Routes — autenticación de CLIENTES de la tienda pública
|--------------------------------------------------------------------------
|
| Guard exclusivo: customer. Independiente del login Breeze del admin
| (/login) y del portal /customer (reportes de servicio), que no se tocan.
|
*/

Route::prefix('cuenta')->name('shop.')->group(function () {
    Route::middleware('guest:customer')->group(function () {
        Route::get('iniciar-sesion', [LoginController::class, 'create'])->name('login');
        Route::post('iniciar-sesion', [LoginController::class, 'store'])->name('login.store');

        Route::get('registro', [RegisterController::class, 'create'])->name('register');
        Route::post('registro', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

        Route::get('olvide-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('olvide-contrasena', [PasswordResetController::class, 'email'])->name('password.email');
        Route::get('restablecer/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('restablecer', [PasswordResetController::class, 'update'])->name('password.update');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::get('/', [AccountController::class, 'index'])->name('account');
        Route::post('cerrar-sesion', [LoginController::class, 'destroy'])->name('logout');

        // Solicitud de acceso al portal de servicios (wizard con autosave)
        Route::post('portal/solicitud/respuesta', [PortalRequestController::class, 'answer'])->name('portal-request.answer');
        Route::post('portal/solicitud/finalizar', [PortalRequestController::class, 'finish'])->name('portal-request.finish');
    });
});
