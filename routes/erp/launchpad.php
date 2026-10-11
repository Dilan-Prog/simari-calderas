<?php

use App\Http\Controllers\Backend\LaunchpadController;
use Illuminate\Support\Facades\Route;

// Launchpad de Operaciones + búsqueda global (Ctrl+K). Heredan prefijo admin./admin.
Route::get('/operaciones', [LaunchpadController::class, 'index'])->name('launchpad');
Route::get('/operaciones/buscar', [LaunchpadController::class, 'search'])->name('launchpad.search');
