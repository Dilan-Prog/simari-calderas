<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fase 4 del plan de integración N8N (Cotizaciones y Pedidos). No hay un
 * FormRequest de Quote en Backend/ para reutilizar -- Backend\QuoteController
 * valida inline con $request->validate(), así que store() replica esas
 * mismas reglas aquí (adaptadas a un arreglo `items` en JSON en vez del
 * `items_json` string que usa el formulario del panel).
 *
 * accept() NO reimplementa el auto-split a SalesOrder: reutiliza tal cual
 * QuoteService::processAcceptance(), el mismo método que llama
 * Backend\QuoteController::updateStatus() cuando el status pasa a
 * "accepted" (ver comentario ahí sobre el guard de idempotencia, replicado
 * abajo). El efecto secundario de GoogleConversion que updateStatus() dispara
 * además es específico de atribución de anuncios ligada a una sesión de
 * navegador (visitor_uuid) -- no aplica a una aceptación disparada por N8N,
 * así que deliberadamente no se replica aquí.
 */
class QuoteController extends Controller
{
    public function __construct(private QuoteService $quoteService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Quote::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100) ?: 15;
        $quotes = $query->paginate($perPage);

        return response()->json([
            'data' => QuoteResource::collection($quotes->items()),
            'meta' => [
                'current_page' => $quotes->currentPage(),
                'last_page'    => $quotes->lastPage(),
                'per_page'     => $quotes->perPage(),
                'total'        => $quotes->total(),
            ],
        ]);
    }

    public function show(Quote $quote): JsonResponse
    {
        $quote->load(['customer', 'items', 'salesOrders']);

        return response()->json(['data' => new QuoteResource($quote)]);
    }

    public function store(Request $request): JsonResponse
    {
        // Mismas reglas que Backend\QuoteController::store(), salvo
        // items_json (string JSON del formulario) reemplazado por `items`
        // (arreglo nativo, más natural para un caller HTTP como N8N).
        $data = $request->validate([
            'customer_id'      => ['required', 'exists:customers,id'],
            'guest_name'       => ['required', 'string', 'max:180'],
            'guest_email'      => ['nullable', 'email', 'max:255'],
            'guest_phone'      => ['nullable', 'string', 'max:30'],
            'guest_company'    => ['nullable', 'string', 'max:255'],
            'guest_rfc'        => ['nullable', 'string', 'max:20'],
            'valid_until'      => ['nullable', 'date'],
            'tax_rate'         => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_total'   => ['nullable', 'numeric', 'min:0'],
            'isr_retention_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency'         => ['required', 'in:MXN,USD'],
            'exchange_rate'    => ['required', 'numeric', 'min:0.01'],
            'notes'            => ['nullable', 'string'],
            'terms_conditions' => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['nullable', 'integer', 'exists:products,id'],
            'items.*.service_page_id'   => ['nullable', 'integer', 'exists:service_pages,id'],
            'items.*.product_name'      => ['required', 'string', 'max:255'],
            'items.*.product_sku'       => ['nullable', 'string', 'max:100'],
            'items.*.quantity'          => ['required', 'integer', 'min:1'],
            'items.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.line_total'        => ['required', 'numeric', 'min:0'],
            'items.*.notes'             => ['nullable', 'string'],
        ]);

        // ApiClient no es un User -- created_by_user_id de quotes es NOT
        // NULL contra la tabla users (ver migración create_quotes_table), así
        // que se atribuye al usuario humano dueño del ApiClient (quien creó
        // la integración en el panel, ver Backend\ApiClientController::store()).
        $creatorUserId = $request->user()->created_by;

        if (! $creatorUserId) {
            throw ValidationException::withMessages([
                'client' => ['Este cliente de API no tiene un usuario asociado para atribuir la cotización.'],
            ]);
        }

        $quote = $this->quoteService->store($data, $creatorUserId);
        $quote->load(['customer', 'items']);

        return response()->json(['data' => new QuoteResource($quote)], 201);
    }

    public function accept(Quote $quote): JsonResponse
    {
        // Mismo guard de idempotencia que Backend\QuoteController::updateStatus():
        // aceptar una cotización ya aceptada no debe generar un segundo
        // SalesOrder por reintento de N8N.
        if ($quote->status !== 'accepted') {
            DB::transaction(function () use ($quote) {
                $quote->update(['status' => 'accepted', 'accepted_at' => now()]);
                $this->quoteService->processAcceptance($quote);
            });
        }

        $quote->load(['customer', 'items', 'salesOrders.items']);

        return response()->json(['data' => new QuoteResource($quote)]);
    }

    /**
     * Para el flujo de seguimiento de N8N: registra cuándo se mandó el
     * último recordatorio, sin tocar status/sent_at -- un recordatorio no
     * cambia el estatus de la cotización, solo cuenta como "ya se le avisó".
     */
    public function markReminderSent(Quote $quote): JsonResponse
    {
        $quote->update(['last_reminder_sent_at' => now()]);

        return response()->json(['data' => new QuoteResource($quote)]);
    }
}
