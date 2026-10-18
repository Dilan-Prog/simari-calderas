<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/leads -- registra una solicitud llegada por correo (n8n escuchando el buzón).
 * Token de ApiClient con ability leads:write.
 *
 * Idempotente por `message_id` (cabecera Message-ID del correo): reenviar el mismo correo
 * devuelve el mismo resultado con `duplicate: true` y no crea otro lead ni otra tarea.
 * Si ya hay un lead abierto con ese correo lo actualiza en vez de duplicarlo.
 */
class LeadEmailController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'message_id'  => 'required|string|max:255',
            'email'       => 'required|email|max:150',
            'name'        => 'nullable|string|max:150',
            'phone'       => 'nullable|string|max:40',
            'subject'     => 'nullable|string|max:255',
            'message'     => 'nullable|string|max:5000',
            'received_at' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'create_task' => 'nullable|boolean',
        ]);

        $result = $leads->captureFromEmail($data);

        return response()->json(['data' => $result], $result['duplicate'] ? 200 : 201);
    }
}
