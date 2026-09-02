<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdVisit;
use App\Models\PoolCalculatorLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Endpoint público (sin auth) llamado por el JS de la calculadora de
 * dimensionamiento de bomba de calor para alberca al capturar un lead.
 * Nunca debe tronar visiblemente para el visitante -- los únicos 4xx
 * esperados son los 422 de validación estándar de Laravel.
 */
class PoolCalculatorLeadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // visitor_uuid es tolerante a nivel de validación: NO se rechaza con
        // un 422 si no corresponde a ningún AdVisit (localStorage corrupto,
        // o el visitante nunca trajo gclid/wbraid/gbraid) -- mismo criterio
        // ya establecido en AdTrackingController::storeEvent() de que un
        // problema de atribución nunca debe bloquear la captura del dato
        // principal.
        $validated = $request->validate([
            'visitor_uuid'    => ['nullable', 'uuid'],
            'home_section_id' => ['nullable', 'integer', 'exists:home_sections,id'],
            'payload'         => ['required', 'array'],
        ]);

        $visitorUuid = $validated['visitor_uuid'] ?? null;

        // La columna visitor_uuid SÍ tiene un foreign key real hacia
        // ad_visits.visitor_uuid (a diferencia de la validación de arriba).
        // Un uuid con formato válido pero sin AdVisit correspondiente
        // haría fallar el INSERT con una QueryException (500 visible para
        // el visitante) si se manda tal cual -- se verifica su existencia
        // aquí y se descarta en silencio, igual que un evento huérfano en
        // AdTrackingController::storeEvent(), para que el lead nunca se
        // pierda por un detalle de atribución.
        if ($visitorUuid !== null && ! AdVisit::where('visitor_uuid', $visitorUuid)->exists()) {
            $visitorUuid = null;
        }

        $ref = $this->generateUniqueRef();

        PoolCalculatorLead::create([
            'ref'             => $ref,
            'visitor_uuid'    => $visitorUuid,
            'home_section_id' => $validated['home_section_id'] ?? null,
            'payload'         => $validated['payload'],
            'status'          => 'nuevo',
        ]);

        return response()->json(['ref' => $ref]);
    }

    private function generateUniqueRef(): string
    {
        do {
            $ref = 'EQ-' . strtoupper(Str::random(4));
        } while (PoolCalculatorLead::where('ref', $ref)->exists());

        return $ref;
    }
}
