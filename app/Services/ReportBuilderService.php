<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\DealStageHistory;
use App\Models\EmailCampaign;
use App\Models\PipelineStage;
use App\Models\Report;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Construye la estructura de datos ['labels' => [], 'datasets' => [...]]
 * lista para Chart.js a partir de un Report (data_source + config).
 *
 * config esperado:
 * {
 *   "metric": "count|sum_amount|avg_days_in_stage",
 *   "group_by": "stage|owner|month|status",
 *   "filters": {"pipeline_id": 1, "date_from": "...", "date_to": "..."}
 * }
 */
class ReportBuilderService
{
    /**
     * Resultado vacío usado como fallback para combinaciones no soportadas,
     * en vez de lanzar excepción.
     */
    private const EMPTY_RESULT = ['labels' => [], 'datasets' => []];

    public function build(Report $report): array
    {
        $config = is_array($report->config) ? $report->config : [];

        try {
            return match ($report->data_source) {
                'deals' => $this->buildDeals($config),
                'email_campaigns' => $this->buildEmailCampaigns($config),
                'workflows' => $this->buildWorkflows($config),
                'contacts', 'companies' => $this->buildCustomers($config),
                default => self::EMPTY_RESULT,
            };
        } catch (\Throwable $e) {
            return self::EMPTY_RESULT;
        }
    }

    /**
     * data_source = 'deals'
     * metric: count | sum_amount | avg_days_in_stage
     * group_by: stage | owner | month
     */
    private function buildDeals(array $config): array
    {
        $metric = $config['metric'] ?? 'count';
        $groupBy = $config['group_by'] ?? 'stage';
        $filters = $config['filters'] ?? [];

        if ($metric === 'avg_days_in_stage') {
            return $this->buildDealsAvgDaysInStage($filters);
        }

        if (!in_array($metric, ['count', 'sum_amount'], true)) {
            return self::EMPTY_RESULT;
        }

        return match ($groupBy) {
            'stage' => $this->buildDealsByStage($metric, $filters),
            'owner' => $this->buildDealsByOwner($metric, $filters),
            'month' => $this->buildDealsByMonth($metric, $filters),
            default => self::EMPTY_RESULT,
        };
    }

    private function applyDealFilters($query, array $filters)
    {
        if (!empty($filters['pipeline_id'])) {
            $query->where('pipeline_id', $filters['pipeline_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function metricSelectExpression(string $metric): string
    {
        return $metric === 'sum_amount' ? 'SUM(deals.amount) as value' : 'COUNT(*) as value';
    }

    private function buildDealsByStage(string $metric, array $filters): array
    {
        $query = Deal::query()
            ->join('pipeline_stages', 'pipeline_stages.id', '=', 'deals.pipeline_stage_id')
            ->select('pipeline_stages.name as label', DB::raw($this->metricSelectExpression($metric)))
            ->groupBy('pipeline_stages.id', 'pipeline_stages.name')
            ->orderBy('pipeline_stages.id');

        $this->applyDealFilters($query, $filters);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => $metric === 'sum_amount' ? 'Monto total' : 'Cantidad de negocios',
                'data' => $rows->pluck('value')->map(fn ($v) => $metric === 'sum_amount' ? (float) $v : (int) $v)->values()->all(),
            ]],
        ];
    }

    private function buildDealsByOwner(string $metric, array $filters): array
    {
        $query = Deal::query()
            ->join('users', 'users.id', '=', 'deals.owner_id')
            ->select('users.name as label', DB::raw($this->metricSelectExpression($metric)))
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name');

        $this->applyDealFilters($query, $filters);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => $metric === 'sum_amount' ? 'Monto total' : 'Cantidad de negocios',
                'data' => $rows->pluck('value')->map(fn ($v) => $metric === 'sum_amount' ? (float) $v : (int) $v)->values()->all(),
            ]],
        ];
    }

    private function buildDealsByMonth(string $metric, array $filters): array
    {
        // Agrupado portable año-mes: DATE_FORMAT en MySQL, strftime en sqlite.
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', deals.created_at)"
            : "DATE_FORMAT(deals.created_at, '%Y-%m')";

        $query = Deal::query()
            ->select(DB::raw("$monthExpr as label"), DB::raw($this->metricSelectExpression($metric)))
            ->groupBy(DB::raw($monthExpr))
            ->orderBy(DB::raw($monthExpr));

        $this->applyDealFilters($query, $filters);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => $metric === 'sum_amount' ? 'Monto total' : 'Cantidad de negocios',
                'data' => $rows->pluck('value')->map(fn ($v) => $metric === 'sum_amount' ? (float) $v : (int) $v)->values()->all(),
            ]],
        ];
    }

    /**
     * metric = avg_days_in_stage: promedio de
     * duration_in_previous_stage_seconds (convertido a días) por etapa,
     * calculado sobre deal_stage_history.to_stage_id. Solo tiene sentido
     * agrupado por 'stage'; para cualquier otro group_by retorna vacío.
     */
    private function buildDealsAvgDaysInStage(array $filters): array
    {
        $stagesQuery = PipelineStage::query()->orderBy('order');

        if (!empty($filters['pipeline_id'])) {
            $stagesQuery->where('pipeline_id', $filters['pipeline_id']);
        }

        $stages = $stagesQuery->get(['id', 'name']);

        if ($stages->isEmpty()) {
            return self::EMPTY_RESULT;
        }

        $historyQuery = DealStageHistory::query()
            ->whereIn('to_stage_id', $stages->pluck('id'))
            ->select('to_stage_id', DB::raw('AVG(duration_in_previous_stage_seconds) as avg_seconds'))
            ->groupBy('to_stage_id');

        if (!empty($filters['date_from'])) {
            $historyQuery->whereDate('moved_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $historyQuery->whereDate('moved_at', '<=', $filters['date_to']);
        }

        $averages = $historyQuery->pluck('avg_seconds', 'to_stage_id');

        $data = $stages->map(function (PipelineStage $stage) use ($averages) {
            $seconds = $averages[$stage->id] ?? null;
            return $seconds !== null ? round(((float) $seconds) / 86400, 2) : 0.0;
        });

        return [
            'labels' => $stages->pluck('name')->values()->all(),
            'datasets' => [[
                'label' => 'Promedio de días en etapa',
                'data' => $data->values()->all(),
            ]],
        ];
    }

    /**
     * data_source = 'email_campaigns'
     * Por defecto: count agrupado por status. Simplificado.
     */
    private function buildEmailCampaigns(array $config): array
    {
        $rows = EmailCampaign::query()
            ->select('status as label', DB::raw('COUNT(*) as value'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => 'Campañas',
                'data' => $rows->pluck('value')->map(fn ($v) => (int) $v)->values()->all(),
            ]],
        ];
    }

    /**
     * data_source = 'workflows'
     * Cuenta de WorkflowEnrollment agrupado por status.
     */
    private function buildWorkflows(array $config): array
    {
        $rows = WorkflowEnrollment::query()
            ->select('status as label', DB::raw('COUNT(*) as value'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => 'Inscripciones',
                'data' => $rows->pluck('value')->map(fn ($v) => (int) $v)->values()->all(),
            ]],
        ];
    }

    /**
     * data_source = 'contacts' | 'companies'
     * Cuenta de Customer agrupado por status (default) o source si el
     * config lo pide explícitamente.
     */
    private function buildCustomers(array $config): array
    {
        $groupBy = ($config['group_by'] ?? 'status') === 'source' ? 'source' : 'status';

        $rows = Customer::query()
            ->select("$groupBy as label", DB::raw('COUNT(*) as value'))
            ->groupBy($groupBy)
            ->orderBy($groupBy)
            ->get();

        return [
            'labels' => $rows->pluck('label')->values()->all(),
            'datasets' => [[
                'label' => 'Clientes',
                'data' => $rows->pluck('value')->map(fn ($v) => (int) $v)->values()->all(),
            ]],
        ];
    }
}
