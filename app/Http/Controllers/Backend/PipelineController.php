<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\PipelineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD de Pipelines de ventas (admin). Sigue el mismo patrón modal-based
 * (respuestas JSON, sin páginas separadas) que PaymentMethodController.
 * La lógica de negocio (transacciones, reorden, reasignación de deals)
 * vive en PipelineService; este controlador solo valida y delega.
 */
class PipelineController extends Controller
{
    public function __construct(private PipelineService $pipelineService)
    {
    }

    public function index()
    {
        $pipelines = Pipeline::with('stages')->get();

        return view('admin.pipelines.index', compact('pipelines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'channel' => ['nullable', 'string', Rule::in([Pipeline::CHANNEL_DEALS, Pipeline::CHANNEL_WHATSAPP])],
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'stages' => 'nullable|array',
            'stages.*.name' => 'required|string|max:150',
            'stages.*.order' => 'nullable|integer|min:0',
            'stages.*.probability' => 'nullable|integer|min:0|max:100',
            'stages.*.is_won' => 'nullable|boolean',
            'stages.*.is_lost' => 'nullable|boolean',
        ]);

        $pipeline = $this->pipelineService->createPipeline($data);

        return response()->json([
            'success' => true,
            'pipeline' => $pipeline,
        ]);
    }

    // Nota: update() deliberadamente NO acepta "channel" — el tipo de
    // pipeline (Negocios / Embudo de Venta WhatsApp) es inmutable una vez
    // creado (Fase 12 del plan de Pipeline de Negocios).
    public function update(Request $request, Pipeline $pipeline)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $pipeline = $this->pipelineService->updatePipeline($pipeline, $data);

        return response()->json([
            'success' => true,
            'pipeline' => $pipeline,
        ]);
    }

    public function destroy(Pipeline $pipeline)
    {
        try {
            $this->pipelineService->deletePipeline($pipeline);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function reorderStages(Request $request, Pipeline $pipeline)
    {
        $data = $request->validate([
            'stage_ids' => 'required|array',
            'stage_ids.*' => 'integer',
        ]);

        $this->pipelineService->reorderStages($pipeline, $data['stage_ids']);

        return response()->json(['success' => true]);
    }

    public function deleteStage(Request $request, PipelineStage $stage)
    {
        $data = $request->validate([
            'reassign_to_stage_id' => 'nullable|integer',
        ]);

        try {
            $this->pipelineService->deleteStage($stage, $data['reassign_to_stage_id'] ?? null);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }
}
