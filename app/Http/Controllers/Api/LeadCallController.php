<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/leads/call -- registra una llamada (telefonía/call tracking
 * externo o integración). Token de ApiClient con ability leads:write.
 * Llamada perdida o de duración corta crea una Task de seguimiento.
 */
class LeadCallController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'phone'            => 'required|string|max:40',
            'name'             => 'nullable|string|max:150',
            'email'            => 'nullable|email|max:150',
            'status'           => 'required|in:contestada,perdida',
            'direction'        => 'nullable|in:entrante,saliente',
            'duration_seconds' => 'nullable|integer|min:0|max:86400',
            'started_at'       => 'nullable|date',
            'external_id'      => 'nullable|string|max:120',
            'tracking_number'  => 'nullable|string|max:40',
            'visitor_uuid'     => 'nullable|uuid',
            'notes'            => 'nullable|string|max:1000',
        ]);

        if (LeadService::normalizePhone($data['phone']) === null) {
            return response()->json(['message' => 'Teléfono inválido.', 'errors' => ['phone' => ['Teléfono inválido.']]], 422);
        }

        $call = $leads->recordCall($data);

        return response()->json([
            'data' => [
                'call_id'    => $call->id,
                'lead_id'    => $call->lead_id,
                'status'     => $call->status,
                'follow_up'  => $call->lead->tasks()->where('description', 'like', 'Llamada #' . $call->id . ' %')->exists(),
            ],
        ], 201);
    }
}
