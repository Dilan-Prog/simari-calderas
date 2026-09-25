<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Services\DealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Fase 3 (API N8N) — CRM / Deals.
 *
 * Reutiliza App\Services\DealService (mismo servicio que ya usa el panel,
 * app/Http/Controllers/Backend/DealController.php) para create/update/
 * moveStage en vez de reescribir esa lógica: así el observer genérico
 * (App\Observers\AutomatableModelObserver, registrado sobre Deal vía
 * config/automatable_modules.php) sigue disparando normal cuando N8N crea,
 * edita o mueve de etapa un Deal, porque DealService siempre pasa por
 * ->save()/->update()/Deal::create() -- nunca UPDATE crudo.
 */
class DealController extends Controller
{
    public function __construct(private DealService $dealService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Deal::query();

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->pipeline_id);
        }

        if ($request->filled('pipeline_stage_id')) {
            $query->where('pipeline_stage_id', $request->pipeline_stage_id);
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }

        $deals = $query->latest()->paginate((int) ($request->per_page ?? 25));

        return response()->json([
            'data' => DealResource::collection($deals->items()),
            'meta' => [
                'current_page' => $deals->currentPage(),
                'last_page'    => $deals->lastPage(),
                'per_page'     => $deals->perPage(),
                'total'        => $deals->total(),
            ],
        ]);
    }

    public function show(Deal $deal): JsonResponse
    {
        return response()->json(['data' => new DealResource($deal)]);
    }

    /**
     * Mismas reglas de validación que
     * App\Http\Controllers\Backend\DealController::store() (copiadas, no
     * reutilizadas directo porque ese controlador no expone un FormRequest
     * separado) para no divergir de lo que ya acepta el alta desde el panel.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pipeline_id'            => 'required|exists:pipelines,id',
            'pipeline_stage_id'      => 'nullable|exists:pipeline_stages,id',
            'name'                   => 'required|string|max:255',
            'amount'                 => 'nullable|numeric|min:0',
            'currency'               => 'nullable|in:MXN,USD',
            'expected_close_date'    => 'nullable|date',
            'owner_id'               => 'nullable|exists:users,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'company_snapshot'       => 'nullable|string|max:255',
            'contact_snapshot_name'  => 'nullable|string|max:180',
            'contact_snapshot_email' => 'nullable|email|max:255',
            'contact_snapshot_phone' => 'nullable|string|max:30',
            'source'                 => 'nullable|string|max:100',
            'notes'                  => 'nullable|string',
        ]);

        $deal = $this->dealService->create($data);

        return response()->json(['data' => new DealResource($deal)], 201);
    }

    /**
     * Mismas reglas que Backend\DealController::update() -- ver comentario
     * en store(). No acepta pipeline_stage_id aquí (igual que el panel):
     * mover de etapa es responsabilidad exclusiva de moveStage().
     */
    public function update(Request $request, Deal $deal): JsonResponse
    {
        $data = $request->validate([
            'pipeline_id'            => 'sometimes|required|exists:pipelines,id',
            'name'                   => 'sometimes|required|string|max:255',
            'amount'                 => 'nullable|numeric|min:0',
            'currency'               => 'nullable|in:MXN,USD',
            'expected_close_date'    => 'nullable|date',
            'owner_id'               => 'nullable|exists:users,id',
            'customer_id'            => 'nullable|exists:customers,id',
            'company_snapshot'       => 'nullable|string|max:255',
            'contact_snapshot_name'  => 'nullable|string|max:180',
            'contact_snapshot_email' => 'nullable|email|max:255',
            'contact_snapshot_phone' => 'nullable|string|max:30',
            'source'                 => 'nullable|string|max:100',
            'status'                 => 'nullable|in:open,won,lost',
            'lost_reason'            => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $this->dealService->update($deal, $data);

        return response()->json(['data' => new DealResource($deal->fresh())]);
    }

    /**
     * Equivalente API de Backend\DealController::moveStage(), reutilizando
     * DealService::moveStage() (valida required_fields de la etapa destino,
     * registra deal_stage_history y deja que el observer dispare
     * automatizaciones). Sin usuario humano autenticado en este contexto
     * (el principal es un ApiClient) -- moved_by queda null en el historial,
     * igual que ya soporta el servicio ($user es nullable).
     */
    public function moveStage(Request $request, Deal $deal): JsonResponse
    {
        $data = $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
        ]);

        $toStage = PipelineStage::findOrFail($data['pipeline_stage_id']);

        try {
            $deal = $this->dealService->moveStage($deal, $toStage, null);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Error de validación.', 'errors' => $e->errors()], 422);
        }

        return response()->json(['data' => new DealResource($deal->fresh())]);
    }
}
