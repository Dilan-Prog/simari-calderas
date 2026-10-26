<?php

use App\Http\Controllers\Backend\Crm\GoogleConversionUploadController;
use App\Http\Controllers\Backend\Crm\LeadInboxController;
use Illuminate\Support\Facades\Route;

// Fase 7 — CRM propio. Se cargan dentro del grupo admin (prefijo admin/, nombres admin.*).

// Bandeja unificada de leads
Route::prefix('leads')->name('leads-inbox.')->middleware('permission:leads-inbox')->group(function () {
    Route::get('/', [LeadInboxController::class, 'index'])->name('index');
    Route::post('/', [LeadInboxController::class, 'store'])->name('store')->middleware('permission:leads-inbox,create');
    Route::get('/{lead}', [LeadInboxController::class, 'show'])->whereNumber('lead')->name('show');
    Route::put('/{lead}', [LeadInboxController::class, 'update'])->whereNumber('lead')->name('update')->middleware('permission:leads-inbox,edit');
    Route::put('/{lead}/contacto', [LeadInboxController::class, 'updateContact'])->whereNumber('lead')->name('contact.update')->middleware('permission:leads-inbox,edit');
    Route::get('/{lead}/whatsapp/plantillas', [LeadInboxController::class, 'whatsappTemplates'])->whereNumber('lead')->name('whatsapp.templates')->middleware('permission:leads-inbox,edit');
    Route::post('/{lead}/whatsapp', [LeadInboxController::class, 'sendWhatsapp'])->whereNumber('lead')->name('whatsapp.send')->middleware('permission:leads-inbox,edit');
    // Correo desde la bandeja: responder (SMTP de Integraciones), plantilla renderizada para el lead,
    // y acciones a partir de un correo (negocio / cotización).
    Route::post('/{lead}/correo', [LeadInboxController::class, 'sendEmail'])->whereNumber('lead')->name('email.send')->middleware(['permission:leads-inbox,edit', 'throttle:10,1']);
    Route::get('/{lead}/correo/plantilla/{template}', [LeadInboxController::class, 'emailTemplate'])->whereNumber(['lead', 'template'])->name('email.template')->middleware('permission:leads-inbox,edit');
    Route::post('/{lead}/correo/negocio', [\App\Http\Controllers\Backend\Crm\LeadEmailActionsController::class, 'createDeal'])->whereNumber('lead')->name('email.deal')->middleware('permission:leads-inbox,edit');
    Route::get('/{lead}/correo/cotizacion', [\App\Http\Controllers\Backend\Crm\LeadEmailActionsController::class, 'createQuote'])->whereNumber('lead')->name('email.quote')->middleware('permission:leads-inbox,edit');
    Route::post('/{lead}/asignar', [LeadInboxController::class, 'assign'])->whereNumber('lead')->name('assign')->middleware('permission:leads-inbox,edit');
    Route::post('/{lead}/convertir', [LeadInboxController::class, 'convert'])->whereNumber('lead')->name('convert')->middleware('permission:leads-inbox,edit');
    Route::post('/{lead}/notas', [LeadInboxController::class, 'storeNote'])->whereNumber('lead')->name('notes.store')->middleware('permission:leads-inbox,create');
    Route::post('/{lead}/llamadas', [LeadInboxController::class, 'storeCall'])->whereNumber('lead')->name('calls.store')->middleware('permission:leads-inbox,create');
});

// Conversiones a Google (Data Manager API) — convive con el panel legado admin.google-ads.*
Route::prefix('conversiones-google')->name('google-conversions.')->middleware('permission:google-conversions')->group(function () {
    Route::get('/', [GoogleConversionUploadController::class, 'index'])->name('index');
    Route::post('/reintentar-fallidas', [GoogleConversionUploadController::class, 'retryFailed'])->name('retry-failed')->middleware('permission:google-conversions,edit');
    Route::get('/{upload}', [GoogleConversionUploadController::class, 'show'])->whereNumber('upload')->name('show');
    Route::post('/{upload}/reintentar', [GoogleConversionUploadController::class, 'retry'])->whereNumber('upload')->name('retry')->middleware('permission:google-conversions,edit');
});
