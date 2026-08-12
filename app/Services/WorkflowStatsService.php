<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use Illuminate\Support\Collection;

/**
 * Estadísticas agregadas de workflows: tasas de éxito de inscripciones,
 * catálogo de acciones usadas por un workflow y KPIs globales.
 */
class WorkflowStatsService
{
    /**
     * Catálogo de presentación (initials/bg/color) por action_type.
     * Las labels vienen de WorkflowActionExecutor::supportedActions().
     */
    private static array $actionPresentation = [
        'create_task' => ['initials' => 'CT', 'bg' => '#eef0f3', 'color' => '#4b5563'],
        'notify_rep' => ['initials' => 'NR', 'bg' => '#eef0f3', 'color' => '#4b5563'],
        'update_property' => ['initials' => 'UP', 'bg' => '#eef0f3', 'color' => '#4b5563'],
        'move_deal_stage' => ['initials' => 'MS', 'bg' => '#e7e8ea', 'color' => '#141516'],
        'enroll_in_workflow' => ['initials' => 'EW', 'bg' => '#e7e8ea', 'color' => '#141516'],
        'send_email' => ['initials' => 'EM', 'bg' => '#fff2e9', 'color' => '#de4a00'],
    ];

    /**
     * Mapa de éxito de inscripciones por workflow_id, para los últimos $days días.
     *
     * @param  Collection  $workflowIds
     * @param  int  $days
     * @return array<int, array{total: int, failed: int, success_pct: float, last_enrolled_at: \Carbon\Carbon|null, last_ok: bool}>
     */
    public function enrollmentSuccessMap(Collection $workflowIds, int $days = 30): array
    {
        $result = [];

        $enrollments = WorkflowEnrollment::whereIn('workflow_id', $workflowIds)
            ->where('enrolled_at', '>=', now()->subDays($days))
            ->with('logs')
            ->get()
            ->groupBy('workflow_id');

        foreach ($enrollments as $workflowId => $group) {
            $total = $group->count();
            $failed = $group->filter(function (WorkflowEnrollment $enrollment) {
                return $enrollment->logs->contains(fn ($log) => $log->result === 'failed');
            })->count();

            $result[$workflowId] = [
                'total' => $total,
                'failed' => $failed,
                'success_pct' => $total > 0 ? round(($total - $failed) / $total * 100) : 0,
                'last_enrolled_at' => null,
                'last_ok' => true,
            ];
        }

        // "Última ejecución" real, sin límite de fecha.
        $latestEnrollments = WorkflowEnrollment::whereIn('workflow_id', $workflowIds)
            ->with('logs')
            ->get()
            ->groupBy('workflow_id')
            ->map(fn (Collection $group) => $group->sortByDesc('enrolled_at')->first());

        foreach ($latestEnrollments as $workflowId => $latest) {
            if (!$latest) {
                continue;
            }

            if (!isset($result[$workflowId])) {
                $result[$workflowId] = [
                    'total' => 0,
                    'failed' => 0,
                    'success_pct' => 0,
                    'last_enrolled_at' => null,
                    'last_ok' => true,
                ];
            }

            $result[$workflowId]['last_enrolled_at'] = $latest->enrolled_at;
            $result[$workflowId]['last_ok'] = !$latest->logs->contains(fn ($log) => $log->result === 'failed');
        }

        return $result;
    }

    /**
     * Stack de acciones (con presentación) usadas por los steps de un workflow.
     *
     * @return array<int, array{key: string, label: string, initials: string, bg: string, color: string}>
     */
    public function actionStackFor(Workflow $workflow): array
    {
        $supportedActions = WorkflowActionExecutor::supportedActions();

        return $workflow->allSteps()
            ->whereNotNull('action_type')
            ->distinct()
            ->pluck('action_type')
            ->map(function (string $actionType) use ($supportedActions) {
                $presentation = self::$actionPresentation[$actionType] ?? [
                    'initials' => strtoupper(substr($actionType, 0, 2)),
                    'bg' => '#eef0f3',
                    'color' => '#4b5563',
                ];

                return array_merge(
                    ['key' => $actionType, 'label' => $supportedActions[$actionType]['label'] ?? $actionType],
                    $presentation
                );
            })
            ->values()
            ->all();
    }

    /**
     * KPIs globales para una colección de workflows.
     *
     * @param  Collection  $workflows
     * @param  int  $days
     * @return array{active_count: int, total_count: int, executions_30d: int, success_rate_pct: float, workflows_with_errors: int}
     */
    public function globalKpis(Collection $workflows, int $days = 30): array
    {
        $since = now()->subDays($days);

        $activeCount = $workflows->where('is_active', true)->count();
        $totalCount = $workflows->count();

        $executions30d = WorkflowEnrollment::where('enrolled_at', '>=', $since)->count();

        $failedGlobal30d = WorkflowEnrollment::where('enrolled_at', '>=', $since)
            ->whereHas('logs', fn ($query) => $query->where('result', 'failed'))
            ->count();

        $successRatePct = $executions30d > 0
            ? round(($executions30d - $failedGlobal30d) / $executions30d * 100, 2)
            : 0;

        $successMap = $this->enrollmentSuccessMap($workflows->pluck('id'), $days);

        $workflowsWithErrors = collect($successMap)->filter(fn (array $stats) => $stats['failed'] > 0)->count();

        return [
            'active_count' => $activeCount,
            'total_count' => $totalCount,
            'executions_30d' => $executions30d,
            'success_rate_pct' => $successRatePct,
            'workflows_with_errors' => $workflowsWithErrors,
        ];
    }
}
