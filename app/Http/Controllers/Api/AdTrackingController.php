<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\AdVisit;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Endpoints públicos (sin auth) llamados por el JS de tracking del
 * frontend para persistir la identidad del visitante (visitor_uuid) y sus
 * eventos de interés relacionados con anuncios de Google Ads. Nunca deben
 * tronar visiblemente para el visitante -- los únicos 4xx esperados son
 * los 422 de validación estándar de Laravel.
 */
class AdTrackingController extends Controller
{
    private const LAST_TOUCH_FIELDS = [
        'gclid', 'wbraid',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'utm_matchtype',
        'landing_url',
    ];

    public function storeVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_uuid'   => ['required', 'uuid'],
            'gclid'          => ['nullable', 'string', 'max:255'],
            'wbraid'         => ['nullable', 'string', 'max:255'],
            'utm_source'     => ['nullable', 'string', 'max:255'],
            'utm_medium'     => ['nullable', 'string', 'max:255'],
            'utm_campaign'   => ['nullable', 'string', 'max:255'],
            'utm_content'    => ['nullable', 'string', 'max:255'],
            'utm_term'       => ['nullable', 'string', 'max:255'],
            'utm_matchtype'  => ['nullable', 'string', 'max:255'],
            'landing_url'    => ['nullable', 'string', 'max:2048'],
        ]);

        // last-touch: solo se pisan los campos si la request trae al menos
        // uno no vacío -- una visita de retorno sin parámetros de ad no debe
        // borrar el last-touch ya guardado.
        $lastTouch = collect($validated)
            ->only(self::LAST_TOUCH_FIELDS)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $now = now();
        $extendedExpiry = $now->copy()->addDays(90);

        try {
            $this->upsertVisit($validated['visitor_uuid'], $validated, $lastTouch, $now, $extendedExpiry);
        } catch (QueryException $e) {
            // Condición de carrera: dos requests casi simultáneas (dos
            // pestañas abriendo la misma landing) intentaron crear la misma
            // fila y una de las dos chocó contra el unique de visitor_uuid.
            // Reintenta como actualización simple sobre la fila que sí se
            // alcanzó a crear.
            $visit = AdVisit::where('visitor_uuid', $validated['visitor_uuid'])->first();

            if ($visit) {
                $updates = ['last_seen_at' => $now, 'expires_at' => $extendedExpiry];
                if (! empty($lastTouch)) {
                    $updates = array_merge($updates, $lastTouch);
                }
                $visit->update($updates);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function upsertVisit(string $visitorUuid, array $validated, array $lastTouch, $now, $extendedExpiry): void
    {
        $visit = AdVisit::where('visitor_uuid', $visitorUuid)->first();

        if (! $visit) {
            AdVisit::create([
                'visitor_uuid' => $visitorUuid,

                'first_gclid'         => $validated['gclid'] ?? null,
                'first_wbraid'        => $validated['wbraid'] ?? null,
                'first_utm_source'    => $validated['utm_source'] ?? null,
                'first_utm_medium'    => $validated['utm_medium'] ?? null,
                'first_utm_campaign'  => $validated['utm_campaign'] ?? null,
                'first_utm_content'   => $validated['utm_content'] ?? null,
                'first_utm_term'      => $validated['utm_term'] ?? null,
                'first_utm_matchtype' => $validated['utm_matchtype'] ?? null,
                'first_landing_url'   => $validated['landing_url'] ?? null,
                'first_seen_at'       => $now,

                'gclid'         => $validated['gclid'] ?? null,
                'wbraid'        => $validated['wbraid'] ?? null,
                'utm_source'    => $validated['utm_source'] ?? null,
                'utm_medium'    => $validated['utm_medium'] ?? null,
                'utm_campaign'  => $validated['utm_campaign'] ?? null,
                'utm_content'   => $validated['utm_content'] ?? null,
                'utm_term'      => $validated['utm_term'] ?? null,
                'utm_matchtype' => $validated['utm_matchtype'] ?? null,
                'landing_url'   => $validated['landing_url'] ?? null,
                'last_seen_at'  => $now,

                'expires_at' => $extendedExpiry,
            ]);

            return;
        }

        $updates = ['last_seen_at' => $now, 'expires_at' => $extendedExpiry];
        if (! empty($lastTouch)) {
            $updates = array_merge($updates, $lastTouch);
        }

        $visit->update($updates);
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_uuid' => ['required', 'uuid'],
            'event_type'   => ['required', Rule::in(AdEvent::EVENT_TYPES)],
            'url'          => ['required', 'string', 'max:2048'],
            'product_id'   => ['nullable', 'integer', 'exists:products,id'],
        ]);

        // Segundo throttle, por visitor_uuid además del throttle:60,1 por IP
        // ya puesto en la ruta -- el de IP es fácil de esquivar con proxies.
        $throttleKey = 'ad-event:' . $validated['visitor_uuid'];
        if (RateLimiter::tooManyAttempts($throttleKey, 120)) {
            return response()->json(['ok' => true]);
        }
        RateLimiter::hit($throttleKey, 60);

        $visit = AdVisit::where('visitor_uuid', $validated['visitor_uuid'])->first();

        // Evento huérfano (visitor_uuid sin AdVisit todavía) se descarta en
        // silencio -- nunca debe fallar visiblemente para el visitante.
        if ($visit) {
            AdEvent::create([
                'ad_visit_id' => $visit->id,
                'event_type'  => $validated['event_type'],
                'url'         => $validated['url'],
                'product_id'  => $validated['product_id'] ?? null,
                // Siempre del servidor -- se ignora cualquier 'occurred_at'
                // u otro timestamp que venga en el request.
                'occurred_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
