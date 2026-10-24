<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Notificaciones persistentes del panel (campana + avisos emergentes).
 *
 * Destinatarios:
 *  - Lead: usuarios activos con el permiso del tipo (el responsable del lead entra por el mismo permiso).
 *  - Tarea: responsable, creador y usuarios activos con 'notif-tarea'; nunca el actor del movimiento.
 *  - A todos ellos se suman sus "copias" (user_notification_copies), que pueden no tener permiso.
 *
 * Tolerante a fallos: cualquier excepción se reporta y se traga para no romper el flujo principal.
 * Idempotente: con $dedupeKey (p. ej. id externo del mensaje) no se crea dos veces la misma notificación.
 */
class NotificationService
{
    /** tipo -> permiso (módulo, acción 'view') que da derecho a recibirlo. */
    public const PERMISSIONS = [
        'lead_incoming' => 'notif-lead-entrante',
        'lead_reply'    => 'notif-lead-respuesta',
        'task'          => 'notif-tarea',
    ];

    public const PREVIEW_LIMIT = 140;

    /** Etiqueta del canal para el título de una respuesta de lead. */
    public static function channelTitle(?string $source): string
    {
        return match ($source) {
            'whatsapp'   => 'Embudo de Venta WhatsApp',
            'correo'     => 'Correo',
            'formulario' => 'Formulario',
            'llamada'    => 'Llamada',
            default      => 'Lead',
        };
    }

    public static function preview(?string $text, int $limit = self::PREVIEW_LIMIT): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

        return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit - 1)) . '…' : $text;
    }

    public static function leadUrl(Lead $lead): string
    {
        return route('admin.leads-inbox.index', ['lead' => $lead->id]);
    }

    /** ¿Tiene el usuario algún permiso notif-* (la campana se muestra)? */
    public static function canReceiveAny(User $user): bool
    {
        foreach (self::PERMISSIONS as $perm) {
            if ($user->hasPermission($perm)) {
                return true;
            }
        }

        // Una "copia" puede no tener permisos pero sí haber recibido avisos.
        return AdminNotification::where('user_id', $user->id)->exists();
    }

    /**
     * Avisa de un lead (type: lead_incoming | lead_reply).
     *
     * @return int notificaciones creadas
     */
    public function notifyLead(Lead $lead, string $type, string $title, ?string $body, ?string $dedupeKey = null): int
    {
        try {
            $permission = self::PERMISSIONS[$type] ?? null;
            if (! $permission || $this->alreadyNotified($type, 'lead', $lead->id, $dedupeKey)) {
                return 0;
            }

            $recipients = $this->activeUsers()->filter(fn (User $u) => $u->hasPermission($permission, 'view'));
            // El responsable recibe el aviso si tiene el permiso del tipo (ya incluido arriba); sin permiso no.
            $recipients = $this->withCopies($recipients, null);

            return $this->store($recipients, $type, $title, $body, self::leadUrl($lead), 'lead', $lead->id, $dedupeKey);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /**
     * Avisa de un movimiento de tarea. $actor = quien lo hizo (no se le notifica); null = sistema.
     *
     * @return int notificaciones creadas
     */
    public function notifyTask(Task $task, string $title, ?string $body, ?User $actor = null, ?string $dedupeKey = null): int
    {
        try {
            if ($this->alreadyNotified('task', 'task', $task->id, $dedupeKey)) {
                return 0;
            }

            $permission = self::PERMISSIONS['task'];

            // Solo reciben quienes tienen habilitado "Tareas" en Notificaciones (el responsable y el creador tambien necesitan el permiso).
            $recipients = $this->activeUsers()->filter(fn (User $u) => $u->hasPermission($permission, 'view'));
            $recipients = $this->withCopies($recipients, $actor);

            return $this->store($recipients, 'task', $title, $body, route('admin.tasks.show', $task->id), 'task', $task->id, $dedupeKey);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }

    /** @return Collection<int,User> usuarios activos */
    private function activeUsers(): Collection
    {
        return User::with('role')->where('status', 'active')->get();
    }

    /** Agrega las copias de cada destinatario y quita al actor. */
    private function withCopies(Collection $recipients, ?User $actor): Collection
    {
        $ids = $recipients->pluck('id');
        if ($ids->isNotEmpty()) {
            $copyIds = DB::table('user_notification_copies')->whereIn('user_id', $ids)->pluck('copy_user_id');
            $recipients = $recipients->concat(User::where('status', 'active')->whereIn('id', $copyIds)->get());
        }

        return $recipients
            ->unique('id')
            ->reject(fn (User $u) => $actor && (int) $u->id === (int) $actor->id)
            ->values();
    }

    private function alreadyNotified(string $type, string $subjectType, int $subjectId, ?string $key): bool
    {
        return $key !== null && AdminNotification::where('type', $type)
            ->where('subject_type', $subjectType)->where('subject_id', $subjectId)
            ->where('data->key', $key)->exists();
    }

    private function store(Collection $recipients, string $type, string $title, ?string $body, string $url, string $subjectType, int $subjectId, ?string $key): int
    {
        $now = now();
        $rows = $recipients->map(fn (User $u) => [
            'user_id'      => $u->id,
            'type'         => $type,
            'title'        => Str::limit($title, 250, ''),
            'body'         => $body,
            'url'          => $url,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'data'         => $key !== null ? json_encode(['key' => $key]) : null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ])->all();

        if ($rows) {
            AdminNotification::insert($rows);
        }

        return count($rows);
    }
}
