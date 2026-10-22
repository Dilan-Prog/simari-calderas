<?php

use App\Http\Controllers\Backend\Crm\LeadTaskController;
use Illuminate\Support\Facades\Route;

// Crear una tarea ligada a un lead (panel "Proxima tarea" de la bandeja). Se carga dentro del grupo admin.
Route::post('leads/{lead}/tarea', [LeadTaskController::class, 'store'])
    ->whereNumber('lead')->middleware('permission:leads-inbox,edit')->name('leads-inbox.task.store');
