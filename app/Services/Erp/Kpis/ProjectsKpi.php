<?php

namespace App\Services\Erp\Kpis;

use App\Models\Project;

class ProjectsKpi
{
    /** Proyectos en ejecución / con fecha límite vencida. */
    public static function kpi(): ?array
    {
        $running = Project::where('status', 'en_ejecucion')->count();
        $overdue = Project::whereNotIn('status', ['cerrado'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        return [
            'value' => $running,
            'label' => $overdue > 0 ? "en ejecución · {$overdue} vencido" . ($overdue === 1 ? '' : 's') : 'en ejecución',
            'tone'  => $overdue > 0 ? 'bad' : 'ok',
        ];
    }
}
