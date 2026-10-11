<?php

use App\Http\Controllers\Api\LeadCallController;
use Illuminate\Support\Facades\Route;

/*
| Fase 7 — CRM propio. Se incluye desde routes/api.php dentro del grupo v1
| (auth:sanctum + throttle:api-n8n + log.api). POST /api/v1/leads/call
*/
Route::post('/leads/call', [LeadCallController::class, 'store'])->middleware('token.ability:leads:write');
