<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de solo lectura para la vista "Forecast" del rediseño de
 * Negocios (Fase 14). Separado de DealReportService (que cubre velocidad /
 * conversión / tiempo por etapa, ya usados por Pipelines) para no
 * sobrecargarlo con las agregaciones nuevas de pronóstico y rendimiento
 * por ejecutivo.
 *
 * Para "salud"/estancado reutiliza DealReportService::stalledDeals()
 * (que a su vez usa el scope Deal::stalled()) en vez de reimplementar esa
 * lógica — ver stalledDeals() más abajo.
 */
class DealForecastService
{
    public function __construct(private DealReportService $dealReportService)
    {
    }

    /**
     * Pipeline ponderado: para cada etapa abierta (no won/lost) del
     * pipeline, SUM(amount * stage.probability / 100) de los deals
     * abiertos que están en ella, más el total ponderado del pipeline
     * completo.
     */
    public function weightedPipeline(int $pipelineId): array
    {
        $stages = PipelineStage::where('pipeline_id', $pipelineId)
            ->orderBy('order')
            ->get(['id', 'name', 'probability', 'is_won', 'is_lost']);

        $sums = Deal::query()
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open')
            ->whereIn('pipeline_stage_id', $stages->pluck('id'))
            ->select(
                'pipeline_stage_id',
                DB::raw('COUNT(*) as deals_count'),
                DB::raw('COALESCE(SUM(amount), 0) as raw_amount')
            )
            ->groupBy('pipeline_stage_id')
            ->get()
            ->keyBy('pipeline_stage_id');

        $byStage = $stages->map(function (PipelineStage $stage) use ($sums) {
            $row = $sums->get($stage->id);
            $dealsCount = $row ? (int) $row->deals_count : 0;
            $rawAmount = $row ? (float) $row->raw_amount : 0.0;
            $weightedAmount = round($rawAmount * ($stage->probability / 100), 2);

            return [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'probability' => (int) $stage->probability,
                'deals_count' => $dealsCount,
                'raw_amount' => $rawAmount,
                'weighted_amount' => $weightedAmount,
            ];
        });

        return [
            'by_stage' => $byStage,
            'total_raw_amount' => round((float) $byStage->sum('raw_amount'), 2),
            'total_weighted_amount' => round((float) $byStage->sum('weighted_amount'), 2),
            'total_deals_count' => (int) $byStage->sum('deals_count'),
        ];
    }

    /**
     * Rendimiento por ejecutivo (owner): negocios abiertos (monto crudo y
     * ponderado por su etapa actual), negocios ganados/perdidos y monto
     * ganado, dentro del pipeline dado. Solo incluye usuarios con al
     * menos un deal (abierto o cerrado) en el pipeline.
     */
    public function repPerformance(int $pipelineId): Collection
    {
        $deals = Deal::query()
            ->with('stage:id,probability')
            ->where('pipeline_id', $pipelineId)
            ->whereNotNull('owner_id')
            ->get(['id', 'owner_id', 'amount', 'status', 'pipeline_stage_id']);

        $ownerIds = $deals->pluck('owner_id')->unique()->filter()->values();

        if ($ownerIds->isEmpty()) {
            return collect();
        }

        $owners = User::whereIn('id', $ownerIds)->get(['id', 'first_name', 'last_name']);

        return $owners->map(function (User $owner) use ($deals) {
            $ownerDeals = $deals->where('owner_id', $owner->id);
            $open = $ownerDeals->where('status', 'open');
            $won = $ownerDeals->where('status', 'won');
            $lost = $ownerDeals->where('status', 'lost');

            $openWeighted = $open->sum(function (Deal $deal) {
                $probability = $deal->stage->probability ?? 0;

                return (float) $deal->amount * ($probability / 100);
            });

            $wonCount = $won->count();
            $lostCount = $lost->count();
            $closedCount = $wonCount + $lostCount;

            return [
                'owner_id' => $owner->id,
                'owner_name' => trim($owner->first_name . ' ' . $owner->last_name),
                'open_count' => $open->count(),
                'open_amount' => round((float) $open->sum('amount'), 2),
                'open_weighted_amount' => round($openWeighted, 2),
                'won_count' => $wonCount,
                'won_amount' => round((float) $won->sum('amount'), 2),
                'lost_count' => $lostCount,
                'win_rate' => $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 2) : 0.0,
            ];
        })->sortByDesc('open_weighted_amount')->values();
    }

    /**
     * Forecast por fecha de cierre esperada: agrupa los deals abiertos con
     * expected_close_date por periodo mensual ("YYYY-MM"), sumando monto
     * crudo y ponderado por la probabilidad de su etapa actual. Los deals
     * abiertos sin expected_close_date se agrupan bajo la clave
     * "sin_fecha" para no perderlos del total.
     */
    public function forecastByCloseDate(int $pipelineId): Collection
    {
        $deals = Deal::query()
            ->with('stage:id,probability')
            ->where('pipeline_id', $pipelineId)
            ->where('status', 'open')
            ->get(['id', 'amount', 'expected_close_date', 'pipeline_stage_id']);

        return $deals
            ->groupBy(function (Deal $deal) {
                return $deal->expected_close_date
                    ? $deal->expected_close_date->format('Y-m')
                    : 'sin_fecha';
            })
            ->map(function (EloquentCollection $group, string $period) {
                $weighted = $group->sum(function (Deal $deal) {
                    $probability = $deal->stage->probability ?? 0;

                    return (float) $deal->amount * ($probability / 100);
                });

                return [
                    'period' => $period,
                    'deals_count' => $group->count(),
                    'raw_amount' => round((float) $group->sum('amount'), 2),
                    'weighted_amount' => round($weighted, 2),
                ];
            })
            ->sortBy('period')
            ->values();
    }

    /**
     * Negocios estancados del pipeline — delega en
     * DealReportService::stalledDeals() (que a su vez usa el scope
     * Deal::stalled()) para no reimplementar esa lógica aquí.
     */
    public function stalledDeals(int $pipelineId, int $days = 14): EloquentCollection
    {
        return $this->dealReportService->stalledDeals($pipelineId, $days);
    }
}
