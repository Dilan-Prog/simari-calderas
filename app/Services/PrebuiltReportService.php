<?php

namespace App\Services;

use App\Models\DealStageHistory;
use App\Models\PipelineStage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Envuelve/reformatea la salida de DealReportService en estructuras listas
 * para consumo directo por vistas y Chart.js (tablas, gráficos de barras,
 * funnels, etc). No contiene lógica de negocio propia salvo cuando es más
 * simple duplicar una query pequeña que reutilizar el servicio base.
 */
class PrebuiltReportService
{
    public function __construct(
        protected DealReportService $dealReportService = new DealReportService()
    ) {
    }

    /**
     * Combina averageTimeInStage() y conversionRateByStage() en un solo
     * array indexado por etapa, listo para renderizar tanto una tabla
     * como un gráfico de barras (labels + datasets).
     */
    public function pipelinePerformance(int $pipelineId): array
    {
        $averages = $this->dealReportService->averageTimeInStage($pipelineId);
        $conversions = $this->dealReportService->conversionRateByStage($pipelineId);

        $conversionsByStageId = $conversions->keyBy('stage_id');

        $rows = $averages->map(function (array $avg) use ($conversionsByStageId) {
            $conversion = $conversionsByStageId->get($avg['stage_id']);

            return [
                'stage_id' => $avg['stage_id'],
                'stage_name' => $avg['stage_name'],
                'avg_seconds' => $avg['avg_seconds'],
                'avg_days' => $avg['avg_seconds'] !== null ? round($avg['avg_seconds'] / 86400, 2) : null,
                'deals_count' => $conversion['deals_count'] ?? 0,
                'conversion_pct' => $conversion['conversion_pct'] ?? 0.0,
            ];
        })->values();

        return [
            'rows' => $rows->all(),
            'chart' => [
                'labels' => $rows->pluck('stage_name')->all(),
                'datasets' => [
                    [
                        'label' => 'Tiempo promedio (días)',
                        'data' => $rows->pluck('avg_days')->all(),
                    ],
                    [
                        'label' => 'Tasa de conversión (%)',
                        'data' => $rows->pluck('conversion_pct')->all(),
                    ],
                ],
            ],
        ];
    }

    /**
     * Cuenta cuántos registros de deal_stage_history tiene cada
     * 'moved_by' (owner/rep) en el rango de fechas dado (por moved_at),
     * agrupado y con join a users.name, ordenado descendente por conteo.
     */
    public function repActivity(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = DealStageHistory::query()
            ->join('users', 'users.id', '=', 'deal_stage_history.moved_by')
            ->whereNotNull('deal_stage_history.moved_by');

        if ($from) {
            $query->where('deal_stage_history.moved_at', '>=', $from);
        }

        if ($to) {
            $query->where('deal_stage_history.moved_at', '<=', $to);
        }

        return $query
            ->select(
                'deal_stage_history.moved_by as user_id',
                'users.name as user_name',
                DB::raw('COUNT(*) as moves_count')
            )
            ->groupBy('deal_stage_history.moved_by', 'users.name')
            ->orderByDesc('moves_count')
            ->get();
    }

    /**
     * Para cada etapa del pipeline (ordenada), cuenta cuántos deals
     * únicos pasaron por ella alguna vez. Forma lista para un gráfico
     * funnel: [{'stage' => name, 'count' => n}].
     */
    public function funnelAnalysis(int $pipelineId): array
    {
        $stages = PipelineStage::where('pipeline_id', $pipelineId)
            ->orderBy('order')
            ->get(['id', 'name']);

        if ($stages->isEmpty()) {
            return [];
        }

        $counts = DealStageHistory::query()
            ->whereIn('to_stage_id', $stages->pluck('id'))
            ->select('to_stage_id', DB::raw('COUNT(DISTINCT deal_id) as deals_count'))
            ->groupBy('to_stage_id')
            ->pluck('deals_count', 'to_stage_id');

        return $stages->map(function (PipelineStage $stage) use ($counts) {
            return [
                'stage' => $stage->name,
                'count' => (int) ($counts[$stage->id] ?? 0),
            ];
        })->values()->all();
    }
}
