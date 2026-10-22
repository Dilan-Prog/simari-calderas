<?php

use App\Http\Controllers\Backend\Crm\LeadInboxLiveController;
use Illuminate\Support\Facades\Route;

// Bandeja de leads "en vivo": la pagina consulta esta huella cada pocos segundos y, si cambio,
// refresca solo la lista y la conversacion (sin recargar ni perder lo que se esta escribiendo).
// Se carga dentro del grupo admin (prefijo admin/, nombres admin.*).
Route::get('leads/live', [LeadInboxLiveController::class, 'fingerprint'])
    ->middleware('permission:leads-inbox')
    ->name('leads-inbox.live');
