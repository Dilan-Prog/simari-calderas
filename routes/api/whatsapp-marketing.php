<?php

use App\Http\Controllers\Api\Marketing\EmailCampaignController;
use App\Http\Controllers\Api\Whatsapp\WhatsappConversationController;
use App\Http\Controllers\Api\Whatsapp\WhatsappSendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fase 6 — WhatsApp y Marketing por correo
|--------------------------------------------------------------------------
| Ver plan: C:\Users\dilon\.claude\plans\replicated-frolicking-clarke.md
| Abilities disponibles (config/api_abilities.php): whatsapp:read,
| whatsapp:send, email-campaigns:trigger, email-campaigns:read.
|
| Se incluye tal cual desde routes/api.php dentro del grupo v1
| (auth:sanctum + throttle:api-n8n + log.api ya aplicados) -- cada ruta
| agrega además su propio token.ability:xxx.
*/

Route::post('/whatsapp/accounts/{account}/send', [WhatsappSendController::class, 'send'])
    ->middleware('token.ability:whatsapp:send');

Route::get('/whatsapp/conversations', [WhatsappConversationController::class, 'index'])
    ->middleware('token.ability:whatsapp:read');

Route::post('/email-campaigns/{campaign}/send', [EmailCampaignController::class, 'send'])
    ->middleware('token.ability:email-campaigns:trigger');

Route::get('/email-sends', [EmailCampaignController::class, 'sends'])
    ->middleware('token.ability:email-campaigns:read');
