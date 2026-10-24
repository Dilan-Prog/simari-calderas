<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use App\Support\Notif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON de la campana de notificaciones del panel (sondeo cada pocos segundos desde el layout).
 * Solo exige sesión admin; si el usuario no tiene ningún permiso notif-* (ni recibió copias) responde vacío.
 */
class NotificationController extends Controller
{
    public const FEED_LIMIT = 30;

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $seconds = (int) ($user->notification_toast_seconds ?? 30);

        if (! NotificationService::canReceiveAny($user)) {
            return response()->json($this->empty($seconds));
        }

        $q = AdminNotification::where('user_id', $user->id);
        if ($request->filled('after_id')) {
            $items = (clone $q)->where('id', '>', (int) $request->input('after_id'))->orderBy('id')->limit(100)->get();
        } else {
            $items = (clone $q)->orderByDesc('id')->limit(self::FEED_LIMIT)->get()->sortByDesc('id')->values();
        }

        return response()->json([
            'unread'        => (clone $q)->whereNull('read_at')->count(),
            'toast_seconds' => $seconds,
            'latest_id'     => (int) (clone $q)->max('id'),
            'items'         => $items->map(fn (AdminNotification $n) => [
                'id'           => $n->id,
                'type'         => $n->type,
                'title'        => $n->title,
                'body'         => $n->body,
                'url'          => $n->url,
                'subject_type' => $n->subject_type,
                'subject_id'   => $n->subject_id,
                'read'         => $n->read_at !== null,
                'created_at'   => $n->created_at?->toIso8601String(),
                'ago'          => self::ago($n->created_at),
            ])->values(),
            'hot'           => [
                'leads' => Notif::hotLeadIds(),
                'tasks' => Notif::hotTaskIds($user),
            ],
        ]);
    }

    public function read(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'nullable|array', 'ids.*' => 'integer', 'all' => 'nullable|boolean']);
        $user = $request->user();

        $q = AdminNotification::where('user_id', $user->id)->whereNull('read_at');
        if (! $request->boolean('all')) {
            $q->whereIn('id', (array) $request->input('ids', []));
        }
        $q->update(['read_at' => now()]);

        return response()->json([
            'unread' => AdminNotification::where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    public function prefs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'toast_seconds' => ['required', 'integer', function ($attr, $value, $fail) {
                if ((int) $value !== 0 && ((int) $value < 5 || (int) $value > 300)) {
                    $fail('La duración debe ser 0 (no se cierra solo) o de 5 a 300 segundos.');
                }
            }],
        ]);

        $user = $request->user();
        if (NotificationService::canReceiveAny($user)) {
            $user->forceFill(['notification_toast_seconds' => (int) $data['toast_seconds']])->save();
        }

        return response()->json(['toast_seconds' => (int) ($user->notification_toast_seconds ?? 30)]);
    }

    private function empty(int $seconds): array
    {
        return ['unread' => 0, 'toast_seconds' => $seconds, 'latest_id' => 0, 'items' => [], 'hot' => ['leads' => [], 'tasks' => []]];
    }

    /** "ahora", "hace 2 min", "hace 3 h", "hace 4 d". */
    public static function ago(?\Carbon\CarbonInterface $at): string
    {
        if (! $at) {
            return '';
        }
        $s = max(0, $at->diffInSeconds(now()));

        return match (true) {
            $s < 60    => 'ahora',
            $s < 3600  => 'hace ' . intdiv($s, 60) . ' min',
            $s < 86400 => 'hace ' . intdiv($s, 3600) . ' h',
            default    => 'hace ' . intdiv($s, 86400) . ' d',
        };
    }
}
