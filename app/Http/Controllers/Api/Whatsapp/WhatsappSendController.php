<?php

namespace App\Http\Controllers\Api\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhatsappMessageResource;
use App\Models\Customer;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Services\WhatsappBaileysService;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Puente de entrada N8N -> envío de WhatsApp. Wrapper delgado: NO reimplementa
 * el envío (eso vive en WhatsappService/WhatsappBaileysService), solo valida
 * la entrada, resuelve/crea la WhatsappConversation destino y llama al mismo
 * contrato público (sendTextMessage/sendTemplateMessage/isWithin24hWindow)
 * que ya usa el panel (Embudo de Venta, WhatsappFunnelController).
 *
 * resolveService() está duplicado a propósito desde
 * WhatsappFunnelController::resolveService() (privado ahí, sin punto de
 * extensión compartido) — mismo criterio de selección por
 * `connection_type`, ver comentario en el original.
 */
class WhatsappSendController extends Controller
{
    public function __construct(private WhatsappService $whatsappService) {}

    private function resolveService(WhatsappAccount $account): WhatsappService|WhatsappBaileysService
    {
        return $account->connection_type === 'baileys_qr'
            ? app(WhatsappBaileysService::class)
            : $this->whatsappService;
    }

    public function send(WhatsappAccount $account, Request $request): JsonResponse
    {
        $data = $request->validate([
            'to'            => ['required', 'string', 'max:32'],
            'type'          => ['required', 'in:text,template'],
            'text'          => ['required_if:type,text', 'nullable', 'string'],
            'template_name' => ['required_if:type,template', 'nullable', 'string', 'max:150'],
            'params'        => ['nullable', 'array'],
        ]);

        if (!$account->is_active) {
            throw ValidationException::withMessages([
                'account' => ['Esta cuenta de WhatsApp está desactivada.'],
            ]);
        }

        $conversation = WhatsappConversation::firstOrCreate(
            [
                'account_id'    => $account->id,
                'contact_phone' => $data['to'],
            ],
            [
                'customer_id'  => Customer::where('phone', $data['to'])->value('id'),
                'status'       => 'open',
                'started_at'   => now(),
                'unread_count' => 0,
            ]
        );

        $service = $this->resolveService($account);

        if ($data['type'] === 'text') {
            if (!$service->isWithin24hWindow($conversation)) {
                throw ValidationException::withMessages([
                    'type' => ['La ventana de 24h para texto libre está cerrada. Usa type=template con una plantilla aprobada para reabrir la conversación.'],
                ]);
            }

            $message = $service->sendTextMessage($conversation, $data['text']);
        } else {
            $message = $service->sendTemplateMessage(
                $conversation,
                $data['template_name'],
                $data['params'] ?? []
            );
        }

        return response()->json(['data' => new WhatsappMessageResource($message)], 201);
    }
}
