<?php

use App\Http\Controllers\Backend\Crm\LeadStageController;
use Illuminate\Support\Facades\Route;

// Etapa de un lead en el Embudo de Venta. Se carga dentro del grupo admin.
Route::post('leads/{lead}/etapa', [LeadStageController::class, 'update'])
    ->whereNumber('lead')->middleware('permission:leads-inbox,edit')->name('leads-inbox.stage.update');
