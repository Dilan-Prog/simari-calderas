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

        foreach ($pipelines as $pipeline) {
            $isWhatsapp = $pipeline->channel === Pipeline::CHANNEL_WHATSAPP;
            $pipelineNegociosCount = 0;
            $pipelineValorPonderado = 0;

            foreach ($pipeline->stages as $stage) {
                $negociosCount = $isWhatsapp
                    ? $stage->whatsappConversations()->count()
                    : $stage->deals()->count();

                $valorBruto = $isWhatsapp
                    ? 0
                    : (float) $stage->deals()->sum('amount');

                if ($stage->is_lost) {
                    $valorPonderado = 0;
                } elseif ($stage->is_won) {
                    $valorPonderado = $valorBruto;
                } else {
                    $valorPonderado = $valorBruto * ($stage->probability ?? 0) / 100;
                }

                $stage->negocios_count = $negociosCount;
                $stage->valor_bruto = $isWhatsapp ? null : $this->formatMoney($valorBruto);
                $stage->valor_ponderado = $this->formatMoney($valorPonderado);

                $pipelineNegociosCount += $negociosCount;
                $pipelineValorPonderado += $valorPonderado;
            }

            $pipeline->etapas_count = $pipeline->stages->count();
            $pipeline->negocios_count = $pipelineNegociosCount;
            $pipeline->valor_ponderado = $this->formatMoney($pipelineValorPonderado);
            $pipeline->tiene_negocios = $pipelineNegociosCount > 0;

            // Destinos posibles de reasignación al borrar: otros pipelines
            // del mismo channel.
            $pipeline->reassign_targets = $pipelines
                ->where('channel', $pipeline->channel)
                ->reject(fn ($p) => $p->id === $pipeline->id)
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
                ->values();
        }

        $kpis = [
            'pipelines_total' => $pipelines->count(),
            'pipelines_activos' => $pipelines->where('is_active', true)->count(),
            'etapas_total' => $pipelines->sum('etapas_count'),
            'negocios_total' => $pipelines->sum('negocios_count'),
            'valor_ponderado_total' => $this->formatMoney(
                $pipelines->sum(fn ($p) => (float) str_replace(['$', ','], '', $p->valor_ponderado))
            ),
        ];

        return view('admin.pipelines.index', compact('pipelines', 'kpis'));
    }

    /** Formatea un monto como '$1,234' (es-MX simple, sin librería nueva). */
    private function formatMoney(float $value): string
    {
        return '$'.number_format(round($value), 0, '.', ',');
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
            'stages.*.wip_limit' => 'nullable|integer|min:0',
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
            'stages' => 'nullable|array',
            'stages.*.id' => 'nullable|integer',
            'stages.*.name' => 'required_with:stages|string|max:150',
            'stages.*.probability' => 'nullable|integer|min:0|max:100',
            'stages.*.is_won' => 'nullable|boolean',
            'stages.*.is_lost' => 'nullable|boolean',
            'stages.*.wip_limit' => 'nullable|integer|min:0',
        ]);

        try {
            $pipeline = $this->pipelineService->updatePipeline($pipeline, $data);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'pipeline' => $pipeline,
        ]);
    }

    public function destroy(Request $request, Pipeline $pipeline)
    {
        $data = $request->validate([
            'reassign_to_pipeline_id' => 'nullable|integer|exists:pipelines,id',
        ]);

        try {
            $this->pipelineService->deletePipeline($pipeline, $data['reassign_to_pipeline_id'] ?? null);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function duplicate(Pipeline $pipeline)
    {
        $pipeline = $this->pipelineService->duplicatePipeline($pipeline);

        return response()->json([
            'success' => true,
            'pipeline' => $pipeline,
        ]);
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
