<?php

use App\Http\Controllers\Api\Crm\DealContactController;
use App\Http\Controllers\Api\Crm\DealController;
use App\Http\Controllers\Api\Crm\PipelineStageController;
use App\Http\Controllers\Api\Crm\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fase 3 — CRM (Deals / Pipelines / Tasks / DealContacts)
|--------------------------------------------------------------------------
| Ver plan: C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md
| Abilities disponibles (config/api_abilities.php): deals:read, deals:write,
| tasks:write, workflows:read (para exponer pipeline_stages si aplica).
|
| Este archivo se incluye tal cual desde routes/api.php dentro del grupo v1
| (auth:sanctum + throttle:api-n8n + log.api ya aplicados) -- cada ruta trae
| además su propio token.ability:xxx.
|
| index de tasks usa deals:read (es lectura relacionada a un deal/taskable,
| no requiere poder crear tareas) -- criterio confirmado en el encargo de
| esta fase.
*/

Route::get('/deals', [DealController::class, 'index'])->middleware('token.ability:deals:read');
Route::get('/deals/{deal}', [DealController::class, 'show'])->middleware('token.ability:deals:read');
Route::post('/deals', [DealController::class, 'store'])->middleware('token.ability:deals:write');
Route::put('/deals/{deal}', [DealController::class, 'update'])->middleware('token.ability:deals:write');
Route::patch('/deals/{deal}', [DealController::class, 'update'])->middleware('token.ability:deals:write');
Route::post('/deals/{deal}/move-stage', [DealController::class, 'moveStage'])->middleware('token.ability:deals:write');

Route::get('/deals/{deal}/contacts', [DealContactController::class, 'index'])->middleware('token.ability:deals:read');
Route::post('/deals/{deal}/contacts', [DealContactController::class, 'store'])->middleware('token.ability:deals:write');

Route::get('/pipeline-stages', [PipelineStageController::class, 'index'])->middleware('token.ability:deals:read');

Route::get('/tasks', [TaskController::class, 'index'])->middleware('token.ability:deals:read');
Route::post('/tasks', [TaskController::class, 'store'])->middleware('token.ability:tasks:write');
