<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\TechnicalServiceResource;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Services\TechnicalServiceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Agenda de servicios técnicos para la app móvil: consulta (index) y alta
 * desde «Programar servicio» (store).
 *
 * Visibilidad: espejo del panel (Backend\TechnicalServiceController::index /
 * TechnicalServiceService::getCalendarEvents), que con technical-services,view
 * muestra todos los servicios sin acotar por técnico.
 */
class TechnicalServiceController extends Controller
{
    /** Rango máximo permitido entre from y to (días). */
    private const MAX_RANGE_DAYS = 92;

    /** GET /api/mobile/v1/servicios?from=Y-m-d&to=Y-m-d (por defecto hoy .. +14 días) */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = isset($data['from']) ? Carbon::createFromFormat('Y-m-d', $data['from'])->startOfDay() : today();
        $to   = isset($data['to']) ? Carbon::createFromFormat('Y-m-d', $data['to'])->startOfDay() : $from->copy()->addDays(14);

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }
        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            throw ValidationException::withMessages(['to' => 'El rango máximo es de ' . self::MAX_RANGE_DAYS . ' días.']);
        }

        $services = TechnicalService::query()
            ->with(['customer', 'serviceType', 'assignedTechnicians'])
            ->whereDate('service_date', '>=', $from->toDateString())
            ->whereDate('service_date', '<=', $to->toDateString())
            ->orderBy('service_date')
            ->orderBy('service_time')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => TechnicalServiceResource::collection($services)->resolve($request),
            'meta' => [
                'from'  => $from->toDateString(),
                'to'    => $to->toDateString(),
                'total' => $services->count(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/v1/servicios — «Programar servicio» desde la app.
     *
     * Reutiliza el alta del panel (TechnicalServiceService::store: folio
     * SVC-AAAA-NNNN, bitácora) y deja el servicio como el asistente al confirmar
     * el paso 4: técnicos asignados, estatus `scheduled`, sin draft_token.
     * Idempotente por el header Idempotency-Key (o el campo idempotency_key):
     * repetir la clave devuelve el mismo servicio con 200 y
     * `Idempotent-Replayed: true`; la primera vez responde 201.
     *
     * Acepta el cuerpo de la app (multipart aplanado): service_type (nombre) o
     * service_type_id, scheduled_at ("Y-m-d H:i[:s]" o ISO 8601) o
     * service_date + service_time, technician_id o technician_ids[].
     */
    public function store(Request $request, TechnicalServiceService $tsService): JsonResponse
    {
        $user = $request->user();
        $key  = $request->header('Idempotency-Key') ?: $request->input('idempotency_key');
        $key  = is_string($key) && trim($key) !== '' ? trim($key) : null;

        if ($key !== null) {
            if (mb_strlen($key) > 100) {
                throw ValidationException::withMessages(['idempotency_key' => 'La clave de idempotencia no debe pasar de 100 caracteres.']);
            }
            if ($existing = TechnicalService::where('idempotency_key', $key)->first()) {
                return $this->replay($request, $existing, $user->id);
            }
        }

        $data = $this->validatedServiceData($request);

        $payload = [
            'customer_id'        => $data['customer_id'],
            'service_type_id'    => $data['service_type_id'],
            'service_date'       => $data['service_date'],
            'service_time'       => $data['service_time'] ?? null,
            'estimated_duration' => isset($data['estimated_duration']) ? (string) $data['estimated_duration'] : null,
            'priority'           => $data['priority'] ?? 'normal',
            'description'        => $data['description'] ?? null,
            'address'            => $data['address'] ?? null,
            'reference'          => $data['reference'] ?? null,
            'location'           => $data['location'] ?? null,
        ];
        $technicianIds = array_values(array_unique(array_filter(array_map('intval', array_merge(
            $data['technician_ids'] ?? [],
            isset($data['technician_id']) ? [$data['technician_id']] : []
        )))));

        try {
            $service = DB::transaction(function () use ($tsService, $payload, $technicianIds, $key, $user) {
                $service = $tsService->store($payload, $user->id);

                if ($key !== null) {
                    $service->forceFill(['idempotency_key' => $key])->save();
                }
                if ($technicianIds) {
                    $tsService->syncTechnicians($service, $technicianIds);
                }

                // Igual que el paso 4 del asistente del panel: confirmar y programar.
                $tsService->updateStatus($service, 'scheduled', 'Servicio programado desde la app móvil.', $user->id);
                $service->forceFill(['current_step' => 4, 'draft_token' => null])->save();

                return $service;
            });
        } catch (QueryException $e) {
            // Dos envíos simultáneos con la misma clave: gana el primero.
            if ($key !== null && (int) ($e->errorInfo[1] ?? 0) === 1062 && str_contains($e->getMessage(), 'idempotency_key')) {
                return $this->replay($request, TechnicalService::where('idempotency_key', $key)->firstOrFail(), $user->id);
            }
            throw $e;
        }

        return response()->json(['data' => $this->present($request, $service)], 201);
    }

    private function replay(Request $request, TechnicalService $service, int $userId): JsonResponse
    {
        // Nunca devolver el servicio de otro usuario si la clave colisionara.
        if ((int) $service->created_by_user_id !== $userId) {
            return response()->json(['message' => 'La clave de idempotencia ya se usó en otro servicio.'], 409);
        }

        return response()
            ->json(['data' => $this->present($request, $service)], 200)
            ->header('Idempotent-Replayed', 'true');
    }

    private function present(Request $request, TechnicalService $service): array
    {
        $service = $service->fresh(['customer', 'serviceType', 'assignedTechnicians']);

        return (new TechnicalServiceResource($service))->resolve($request);
    }

    /**
     * Normaliza el cuerpo de la app a los campos del panel y lo valida con las
     * reglas de Backend\TechnicalServiceController::store (+ técnicos del paso 2).
     */
    private function validatedServiceData(Request $request): array
    {
        $input = $request->all();

        // scheduled_at → service_date + service_time (zona horaria de la app).
        if (empty($input['service_date']) && ! empty($input['scheduled_at']) && is_string($input['scheduled_at'])) {
            try {
                $at = Carbon::parse($input['scheduled_at'])->setTimezone(config('app.timezone'));
            } catch (\Throwable) {
                throw ValidationException::withMessages(['scheduled_at' => 'La fecha y hora del servicio no son válidas.']);
            }
            $input['service_date'] = $at->format('Y-m-d');
            if (empty($input['service_time']) && preg_match('/\d{1,2}:\d{2}/', $input['scheduled_at'])) {
                $input['service_time'] = $at->format('H:i');
            }
        }
        // "10:30:00" → "10:30" (el panel guarda H:i).
        if (! empty($input['service_time']) && is_string($input['service_time'])
            && preg_match('/^(\d{2}:\d{2}):\d{2}$/', $input['service_time'], $m)) {
            $input['service_time'] = $m[1];
        }

        // service_type (nombre, como lo manda la app) → service_type_id.
        if (empty($input['service_type_id']) && ! empty($input['service_type']) && is_string($input['service_type'])) {
            $input['service_type_id'] = $this->resolveServiceTypeId($input['service_type']);
        }

        $activeTechnician = Rule::exists('users', 'id')->where('status', 'active');

        return validator($input, [
            'customer_id'        => ['required', 'integer', 'exists:customers,id'],
            'service_type_id'    => ['required', 'integer', 'exists:service_types,id'],
            'service_date'       => ['required', 'date_format:Y-m-d'],
            'service_time'       => ['nullable', 'date_format:H:i'],
            'estimated_duration' => ['nullable', 'max:20'],
            'priority'           => ['nullable', 'in:normal,high,urgent'],
            'description'        => ['nullable', 'string', 'max:5000'],
            'address'            => ['nullable', 'string', 'max:255'],
            'reference'          => ['nullable', 'string', 'max:255'],
            'location'           => ['nullable', 'string', 'max:200'],
            'technician_id'      => ['nullable', 'integer', $activeTechnician],
            'technician_ids'     => ['nullable', 'array'],
            'technician_ids.*'   => ['integer', $activeTechnician],
        ], [
            'customer_id.required'     => 'Elige un cliente del catálogo (desde la app no se pueden dar de alta clientes nuevos).',
            'service_type_id.required' => 'Elige el tipo de servicio.',
            'service_date.required'    => 'Indica la fecha del servicio.',
            'technician_id.exists'     => 'El técnico elegido no existe o no está activo.',
            'technician_ids.*.exists'  => 'El técnico elegido no existe o no está activo.',
        ])->validate();
    }

    /** Tipo de servicio activo por nombre: exacto y luego parcial, sin distinguir mayúsculas. */
    private function resolveServiceTypeId(string $name): int
    {
        $name = trim($name);
        if (ctype_digit($name)) {
            return (int) $name;
        }

        $types = ServiceType::active()->orderBy('name')->get(['id', 'name']);
        $lower = mb_strtolower($name);

        $match = $types->first(fn ($t) => mb_strtolower($t->name) === $lower)
            ?? $types->first(fn ($t) => str_contains(mb_strtolower($t->name), $lower));

        if (! $match) {
            throw ValidationException::withMessages([
                'service_type' => "Tipo de servicio no reconocido: «{$name}». Opciones: " . $types->pluck('name')->implode(', ') . '.',
            ]);
        }

        return $match->id;
    }
}
