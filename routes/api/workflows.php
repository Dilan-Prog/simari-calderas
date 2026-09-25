<?php

use App\Http\Controllers\Api\N8n\WorkflowTriggerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fase 2 — Puente con el motor de Workflows
|--------------------------------------------------------------------------
| Ver app/Http/Controllers/Api/N8n/WorkflowTriggerController.php.
*/

Route::controller(WorkflowTriggerController::class)->group(function () {
    Route::get('/workflows', 'index')
        ->middleware('token.ability:workflows:read');

    Route::post('/workflows/{workflow}/enroll', 'enroll')
        ->middleware('token.ability:workflows:trigger');

    Route::get('/workflow-enrollments/{workflowEnrollment}', 'showEnrollment')
        ->middleware('token.ability:workflows:read');
});
