<?php

namespace App\Http\Controllers\Api\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsappConversationResource;
use App\Models\WhatsappConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Solo lectura. Filtros por `status` (open/closed, ver
 * WhatsappConversation::scopeOpen) y `account_id` -- suficiente para que N8N
 * pueda hacer polling de conversaciones abiertas por cuenta sin traer todo
 * el historial.
 */
class WhatsappConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'     => ['nullable', 'string'],
            'account_id' => ['nullable', 'integer'],
        ]);

        $conversations = WhatsappConversation::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->orderByDesc('last_message_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => WhatsappConversationResource::collection($conversations->items()),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'per_page'     => $conversations->perPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }
}
