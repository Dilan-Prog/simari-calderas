<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificación persistente del panel (campana). Una fila por destinatario.
 * type: lead_incoming | lead_reply | task. Ver App\Services\NotificationService.
 */
class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'subject_type', 'subject_id', 'data', 'read_at'];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Marca como leídas las notificaciones no leídas de un usuario ligadas a un lead/tarea (al abrir su URL destino). */
    public static function markSubjectRead(?int $userId, string $subjectType, int $subjectId): void
    {
        if (! $userId) {
            return;
        }

        try {
            static::where('user_id', $userId)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
