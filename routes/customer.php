<?php

use App\Http\Controllers\Backend\Customers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CustomerController::class, 'index'])->name('dashboard');
