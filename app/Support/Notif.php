<?php

namespace App\Support;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\User;

/**
 * Ayudantes para que las vistas resalten lo que tiene una notificación pendiente.
 *
 *  - hotLeadIds():      ids de leads con un mensaje entrante sin responder (leads.reply_pending_at no nulo).
 *                       Se fija al llegar un mensaje (WhatsApp/correo) y se limpia al responder o al abrir el lead.
 *  - hotTaskIds($user): ids de tareas con una notificación NO leída para ese usuario (por defecto el autenticado).
 *                       Se limpia sola al abrir la tarea (task show) o al marcar la notificación como leída.
 *
 * Memoizados por petición (una consulta por llamada); nunca lanzan.
 */
class Notif
{
    private static ?array $leads = null;
    private static array $tasks = [];

    /** @return int[] */
    public static function hotLeadIds(): array
    {
        try {
            return self::$leads ??= Lead::whereNotNull('reply_pending_at')->pluck('id')->map(fn ($i) => (int) $i)->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** @return int[] */
    public static function hotTaskIds(?User $user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }

        try {
            return self::$tasks[$user->id] ??= AdminNotification::where('user_id', $user->id)
                ->where('subject_type', 'task')->whereNull('read_at')
                ->distinct()->pluck('subject_id')->map(fn ($i) => (int) $i)->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** Solo para pruebas / largas ejecuciones: olvida lo memoizado. */
    public static function flush(): void
    {
        self::$leads = null;
        self::$tasks = [];
    }
}
