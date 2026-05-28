<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\EmployeController;

Route::get('/dashboard', [EmployeController::class, 'dashboard'])->name('dashboard');
