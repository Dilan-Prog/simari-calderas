<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\TechnicalServiceResource;
use App\Models\TechnicalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Agenda de servicios técnicos para la app móvil (solo lectura).
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
}
