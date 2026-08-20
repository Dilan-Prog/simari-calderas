<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\User;
use App\Models\WhatsappAccount;
use App\Models\WhatsappConversation;
use App\Services\DealService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;

/**
 * "Embudo de Venta" (Fase 13 del plan): kanban de conversaciones de
 * WhatsApp agrupadas por etapa, sobre un Pipeline `channel='whatsapp'`
 * (Fase 12). Mover una conversación de etapa NUNCA toca el Deal vinculado
 * (decisión confirmada del plan — son dos tableros independientes que solo
 * comparten Customer/contacto de fondo).
 */
class WhatsappFunnelController extends Controller
{
    public function __construct(
        private WhatsappService $whatsappService,
        private DealService $dealService
    ) {}

    /**
     * Kanban principal. Acepta ?pipeline_id= para cambiar de embudo
     * (siempre restringido a pipelines channel='whatsapp'), y filtros
     * client-agnostic (search, agent_id, unread, stale) resueltos aquí en
     * servidor para no traer todas las conversaciones al cliente.
     */
    public function index(Request $request)
    {
        $pipelines = Pipeline::query()->whatsapp()->orderBy('name')->get();

        if ($request->filled('pipeline_id')) {
            $pipeline = $pipelines->firstWhere('id', (int) $request->pipeline_id);
        } else {
            $pipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
        }

        $stages = collect();
        $conversationsByStage = collect();

        if ($pipeline) {
            $stages = PipelineStage::where('pipeline_id', $pipeline->id)
                ->orderBy('order')
                ->get();

            // Se eager-carga messages() completo (ordenado ascendente, igual
            // que el modal) en vez de limitarlo por conversación — Eloquent
            // no soporta un limit() por-padre confiable en un eager load de
            // hasMany en este contexto; el volumen esperado por
            // conversación es bajo. La vista solo usa el último
            // (->messages->last()) para el preview de la tarjeta.
            $query = WhatsappConversation::with(['customer', 'assignedUser', 'deal', 'messages' => function ($q) {
                    $q->orderBy('sent_at');
                }])
                ->where('pipeline_id', $pipeline->id)
                ->open();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('contact_phone', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('agent_id')) {
                $query->where('assigned_user_id', $request->agent_id);
            }

            if ($request->boolean('unread')) {
                $query->where('unread_count', '>', 0);
            }

            if ($request->boolean('stale')) {
                $query->stale();
            }

            $conversationsByStage = $query->orderByDesc('last_message_at')
                ->get()
                ->groupBy('pipeline_stage_id');
        }

        $agents = User::orderBy('first_name')->get();
        $accounts = WhatsappAccount::active()->orderBy('name')->get();
        $templateAccount = $accounts->first();
        $templates = $templateAccount
            ? $this->whatsappService->approvedTemplates($templateAccount)
            : config('whatsapp.approved_templates', []);
        $dealPipelines = Pipeline::query()->deals()->with('stages')->orderBy('name')->get();

        return view('admin.whatsapp-funnel.index', compact(
            'pipelines',
            'pipeline',
            'stages',
            'conversationsByStage',
            'agents',
            'accounts',
            'templates',
            'dealPipelines'
        ));
    }

    /**
     * Endpoint AJAX del drag&drop del kanban — solo actualiza
     * whatsapp_conversations.pipeline_stage_id (y pipeline_id, por si la
     * columna destino pertenece a otro embudo). Jamás toca
     * deals.pipeline_stage_id aunque la conversación tenga deal_id.
     */
    public function moveStage(Request $request, WhatsappConversation $conversation)
    {
        $data = $request->validate([
            'to_stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $toStage = PipelineStage::findOrFail($data['to_stage_id']);

        $conversation->pipeline_stage_id = $toStage->id;
        $conversation->pipeline_id = $toStage->pipeline_id;
        $conversation->save();

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(['customer', 'assignedUser', 'deal']),
        ]);
    }

    /**
     * Carga el hilo completo para el modal de chat. Marca la conversación
     * como leída (unread_count=0) al abrirse — es el único lugar donde el
     * contador se resetea, ya que representa que un agente la revisó.
     */
    public function messages(WhatsappConversation $conversation)
    {
        $conversation->load(['messages' => function ($q) {
            $q->orderBy('sent_at');
        }, 'customer', 'assignedUser', 'deal', 'account']);

        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $templates = $conversation->account
            ? $this->whatsappService->approvedTemplates($conversation->account)
            : config('whatsapp.approved_templates', []);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $conversation->messages,
            'within_window' => $this->whatsappService->isWithin24hWindow($conversation),
            'templates' => $templates,
        ]);
    }

    /**
     * Envía un mensaje desde el modal de chat. type=text solo es válido
     * dentro de la ventana de 24h (WhatsappService no lo bloquea a nivel
     * HTTP, así que la regla se aplica aquí antes de llamar al servicio).
     * type=template siempre está permitido (es la única forma de reabrir
     * conversación fuera de ventana).
     */
    public function sendMessage(Request $request, WhatsappConversation $conversation)
    {
        $data = $request->validate([
            'type' => 'required|in:text,template',
            'text' => 'required_if:type,text|nullable|string',
            'template_name' => 'required_if:type,template|nullable|string|max:150',
            'params' => 'nullable|array',
        ]);

        if (!$conversation->account) {
            return response()->json([
                'message' => 'Esta conversación no tiene una cuenta de WhatsApp asociada.',
            ], 422);
        }

        if ($data['type'] === 'text') {
            if (!$this->whatsappService->isWithin24hWindow($conversation)) {
                return response()->json([
                    'message' => 'La ventana de 24h para texto libre está cerrada. Usa una plantilla aprobada para reabrir la conversación.',
                ], 422);
            }

            $message = $this->whatsappService->sendTextMessage($conversation, $data['text']);
        } else {
            $message = $this->whatsappService->sendTemplateMessage(
                $conversation,
                $data['template_name'],
                $data['params'] ?? []
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Botón "Reasignar agente" del panel derecho del chat.
     */
    public function reassignAgent(Request $request, WhatsappConversation $conversation)
    {
        $data = $request->validate([
            'assigned_user_id' => 'nullable|exists:users,id',
        ]);

        $conversation->update(['assigned_user_id' => $data['assigned_user_id'] ?? null]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(['assignedUser']),
        ]);
    }

    /**
     * Botón "Crear negocio" del panel derecho del chat. Reutiliza
     * DealService::create() (el flujo normal de alta de Deals) en vez de
     * duplicar la lógica de folio/historial de etapa — solo se encarga de
     * prellenar los datos de contacto desde la conversación/Customer y de
     * setear whatsapp_conversations.deal_id al terminar. El Deal resultante
     * es un negocio real y normal del módulo de Negocios, con su propio
     * pipeline/etapa, independiente del embudo de WhatsApp.
     */
    public function createDeal(Request $request, WhatsappConversation $conversation)
    {
        if ($conversation->deal_id) {
            return response()->json([
                'message' => 'Esta conversación ya tiene un negocio vinculado.',
            ], 422);
        }

        $data = $request->validate([
            'pipeline_id' => 'required|exists:pipelines,id',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'name' => 'required|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $customer = $conversation->customer;

        $dealData = array_merge($data, [
            'customer_id' => $customer?->id,
            'company_snapshot' => $customer?->company,
            'contact_snapshot_name' => $customer
                ? trim($customer->first_name . ' ' . $customer->last_name)
                : null,
            'contact_snapshot_email' => $customer?->email,
            'contact_snapshot_phone' => $conversation->contact_phone,
            'source' => 'whatsapp',
        ]);

        $deal = $this->dealService->create($dealData);

        $conversation->update(['deal_id' => $deal->id]);

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'redirect' => route('admin.deals.show', $deal),
        ]);
    }

    /**
     * Quick action "WhatsApp" de la tarjeta de Deal en Negocios (Fase 14):
     * busca o crea la WhatsappConversation vinculada a este Deal (a través
     * de su customer_id) y devuelve la URL del Embudo de Venta con
     * ?pipeline_id=&open_conversation= para que whatsapp-funnel-board.js
     * abra el modal de chat ya existente sobre ella — nunca se duplica el
     * modal, solo se reutiliza vía este parámetro.
     */
    public function fromDeal(Deal $deal)
    {
        $deal->loadMissing('customer');

        $conversation = WhatsappConversation::where('deal_id', $deal->id)->first();

        if (!$conversation && $deal->customer_id) {
            $conversation = WhatsappConversation::where('customer_id', $deal->customer_id)
                ->whereNull('deal_id')
                ->orderByDesc('last_message_at')
                ->first();

            if ($conversation) {
                $conversation->update(['deal_id' => $deal->id]);
            }
        }

        if (!$conversation) {
            $phone = $deal->customer?->phone ?: $deal->contact_snapshot_phone;

            if (!$phone) {
                return response()->json([
                    'message' => 'Este negocio no tiene un teléfono de contacto registrado (ni en el cliente vinculado ni en el negocio).',
                ], 422);
            }

            $pipeline = Pipeline::query()->whatsapp()->where('is_default', true)->first()
                ?? Pipeline::query()->whatsapp()->first();

            if (!$pipeline) {
                return response()->json([
                    'message' => 'No hay ningún pipeline de tipo Embudo de Venta configurado todavía. Crea uno en Pipelines.',
                ], 422);
            }

            $account = WhatsappAccount::active()->first();

            if (!$account) {
                return response()->json([
                    'message' => 'No hay ninguna cuenta de WhatsApp activa configurada todavía.',
                ], 422);
            }

            $stage = PipelineStage::where('pipeline_id', $pipeline->id)
                ->orderBy('order')
                ->first();

            $conversation = WhatsappConversation::create([
                'customer_id' => $deal->customer_id,
                'account_id' => $account->id,
                'pipeline_id' => $pipeline->id,
                'pipeline_stage_id' => $stage?->id,
                'deal_id' => $deal->id,
                'contact_phone' => $phone,
                'status' => 'open',
                'started_at' => now(),
                'unread_count' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'redirect' => route('admin.whatsapp-funnel.index', [
                'pipeline_id' => $conversation->pipeline_id,
                'open_conversation' => $conversation->id,
            ]),
        ]);
    }

    /**
     * "+ Nuevo chat": inicia una conversación saliente. WhatsApp no permite
     * abrir conversación con texto libre — siempre se envía una plantilla
     * aprobada vía WhatsappService::sendTemplateMessage().
     */
    public function newChat(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:whatsapp_accounts,id',
            'contact_phone' => 'required|string|max:30',
            'name' => 'nullable|string|max:180',
            'company' => 'nullable|string|max:255',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'template_name' => 'required|string|max:150',
            'params' => 'nullable|array',
        ]);

        $toStage = PipelineStage::findOrFail($data['pipeline_stage_id']);

        $customer = Customer::where('phone', $data['contact_phone'])->first();

        $conversation = WhatsappConversation::create([
            'customer_id' => $customer?->id,
            'account_id' => $data['account_id'],
            'pipeline_id' => $toStage->pipeline_id,
            'pipeline_stage_id' => $toStage->id,
            'assigned_user_id' => $data['assigned_user_id'] ?? null,
            'contact_phone' => $data['contact_phone'],
            'status' => 'open',
            'started_at' => now(),
            'unread_count' => 0,
        ]);

        $this->whatsappService->sendTemplateMessage(
            $conversation,
            $data['template_name'],
            $data['params'] ?? []
        );

        return response()->json([
            'success' => true,
            'conversation' => $conversation->fresh(['customer', 'assignedUser']),
        ]);
    }
}
