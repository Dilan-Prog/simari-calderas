<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            'assigned_to_email' => 'nullable|email|exists:users,email',
            'create_task' => 'nullable|boolean',
        ]);

        // Responsable por correo: no depende del ID, que cambia entre entornos.
        if (empty($data['assigned_to']) && ! empty($data['assigned_to_email'])) {
            $data['assigned_to'] = User::where('email', $data['assigned_to_email'])->value('id');
        }

        $result = $leads->captureFromEmail($data);

        return response()->json(['data' => $result], $result['duplicate'] ? 200 : 201);
    }

    /**
     * POST /api/v1/leads/outbound-email -- correo enviado por administracion@ (carpeta Enviados, n8n).
     * Solo se guarda si el destinatario es un cliente con tarea; si no, responde matched=false y no guarda nada.
     */
    public function storeOutbound(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'message_id'    => 'required|string|max:255',
            'from_email'    => 'nullable|string|max:190',
            'to'            => 'required',
            'cc'            => 'nullable',
            'subject'       => 'nullable|string|max:500',
            'body'          => 'nullable|string|max:200000',
            'sent_at'       => 'nullable|date',
            'in_reply_to'   => 'nullable|string|max:255',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'nullable|string|max:255',
        ]);

        $result = $leads->recordOutboundEmail($data);

        return response()->json(['data' => $result], $result['matched'] && ! $result['duplicate'] ? 201 : 200);
    }
}
