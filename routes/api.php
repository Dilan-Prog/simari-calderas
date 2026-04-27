<?php

use App\Http\Controllers\Backend\Marketing\GoogleConversionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes — called from the frontend when a visitor arrives from a Google Ads click
Route::prefix('v1/google-ads')->group(function () {
    Route::post('/', [GoogleConversionController::class, 'store']);
});

// Protected routes — internal use only (listing, retry)
Route::prefix('v1/google-ads')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [GoogleConversionController::class, 'index']);
    Route::post('/retry', [GoogleConversionController::class, 'retry']);
});
