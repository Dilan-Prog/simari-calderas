<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\DealStageHistory;
use App\Models\PipelineStage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de solo lectura para reportes/analítica del módulo de Deals
 * (pipeline de ventas). No realiza escrituras ni requiere transacciones.
 */
class DealReportService
{
    /**
     * Promedio de duration_in_previous_stage_seconds por etapa del pipeline,
     * calculado sobre los registros de deal_stage_history cuyo to_stage_id
     * corresponde a esa etapa.
     */
    public function averageTimeInStage(int $pipelineId): Collection
    {
        $stages = PipelineStage::where('pipeline_id', $pipelineId)
            ->orderBy('order')
            ->get(['id', 'name']);

        $averages = DealStageHistory::query()
            ->whereIn('to_stage_id', $stages->pluck('id'))
            ->select('to_stage_id', DB::raw('AVG(duration_in_previous_stage_seconds) as avg_seconds'))
            ->groupBy('to_stage_id')
            ->pluck('avg_seconds', 'to_stage_id');

        return $stages->map(function (PipelineStage $stage) use ($averages) {
            return [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'avg_seconds' => isset($averages[$stage->id]) ? (float) $averages[$stage->id] : null,
            ];
        });
    }

    /**
     * Para cada etapa (ordenada), cuenta deals únicos (histórico completo)
     * que alguna vez pasaron por ella, y el porcentaje respecto al total de
     * deals que pasaron por la primera etapa del pipeline.
     */
    public function conversionRateByStage(int $pipelineId): Collection
    {
        $stages = PipelineStage::where('pipeline_id', $pipelineId)
            ->orderBy('order')
            ->get(['id', 'name']);

        if ($stages->isEmpty()) {
            return collect();
        }

        $counts = DealStageHistory::query()
            ->whereIn('to_stage_id', $stages->pluck('id'))
            ->select('to_stage_id', DB::raw('COUNT(DISTINCT deal_id) as deals_count'))
            ->groupBy('to_stage_id')
            ->pluck('deals_count', 'to_stage_id');

        $firstStageId = $stages->first()->id;
        $baseCount = (int) ($counts[$firstStageId] ?? 0);

        return $stages->map(function (PipelineStage $stage) use ($counts, $baseCount) {
            $dealsCount = (int) ($counts[$stage->id] ?? 0);

            return [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'deals_count' => $dealsCount,
                'conversion_pct' => $baseCount > 0 ? round(($dealsCount / $baseCount) * 100, 2) : 0.0,
            ];
        });
    }

    /**
     * Deals estancados del pipeline (status='open') usando el scope
     * Deal::stalled($days) definido en el modelo.
     */
    public function stalledDeals(int $pipelineId, int $days = 14): EloquentCollection
    {
        return Deal::query()
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open')
            ->stalled($days)
            ->get();
    }

    /**
     * Velocidad del pipeline: total de deals ganados en el rango de fechas
     * (por closed_at), monto total ganado, y tiempo promedio en días desde
     * created_at hasta closed_at.
     */
    public function pipelineVelocity(int $pipelineId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Deal::query()
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'won');

        if ($from) {
            $query->where('closed_at', '>=', $from);
        }

        if ($to) {
            $query->where('closed_at', '<=', $to);
        }

        $deals = $query->get(['id', 'amount', 'created_at', 'closed_at']);

        $totalWon = $deals->count();
        $totalAmount = (float) $deals->sum('amount');

        $avgDays = $totalWon > 0
            ? $deals->avg(function (Deal $deal) {
                return $deal->created_at->diffInSeconds($deal->closed_at) / 86400;
            })
            : 0.0;

        return [
            'total_won' => $totalWon,
            'total_amount' => $totalAmount,
            'avg_days_to_close' => round((float) $avgDays, 2),
        ];
    }
}
