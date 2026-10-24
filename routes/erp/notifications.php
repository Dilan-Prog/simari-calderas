<?php

use App\Http\Controllers\Backend\NotificationController;
use Illuminate\Support\Facades\Route;

// Campana de notificaciones: sondeo (feed), marcar como leídas y preferencia de duración del aviso.
// Se carga dentro del grupo admin (prefijo admin/, nombres admin.*). Solo requiere sesión; sin permisos notif-* responden vacío.
Route::get('notificaciones/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
Route::post('notificaciones/leer', [NotificationController::class, 'read'])->name('notifications.read');
Route::post('notificaciones/preferencias', [NotificationController::class, 'prefs'])->name('notifications.prefs');
