<?php

namespace App\Services\Statistics;

use App\Models\TechnicalService;
use Illuminate\Support\Collection;

/**
 * Servicios Técnicos. Nota importante: el sistema NO tiene concepto de
 * "técnico líder" — `assignedTechnicians()` es un many-to-many sin rol de
 * liderazgo (solo un flag `role_verified` de asistencia). El panel de
 * técnicos mide participación/carga de trabajo, nunca liderazgo. Tampoco
 * hay un campo de "fecha real de cierre" distinto de `updated_at` (que
 * cambia con cualquier edición) — por eso no se construye un panel de
 * "cumplimiento de fecha programada" con datos que no existen de verdad.
 */
class ServiciosStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $services = TechnicalService::with('assignedTechnicians')
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $completed = $services->where('status', 'completed');
        $inProgress = $services->where('status', 'in_progress');
        $technicianCount = $services->flatMap(fn ($s) => $s->assignedTechnicians->pluck('id'))->unique()->count();

        $kpis = [
            [
                'label' => 'Servicios atendidos', 'value' => (string) $services->count(),
                'trend' => $completed->count() . ' completados', 'trendUp' => true,
                'hint' => $completed->count() . ' completados en el periodo', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4.5l5 5-9 9H5.5v-5z"/></svg>',
            ],
            [
                'label' => 'Servicios completados', 'value' => (string) $completed->count(),
                'trend' => $services->count() > 0 ? round($completed->count() / $services->count() * 100) . '%' : '0%',
                'trendUp' => true, 'hint' => 'del total del periodo', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 6.5"/></svg>',
            ],
            [
                'label' => 'Servicios en curso', 'value' => (string) $inProgress->count(),
                'trend' => 'asignados a ' . $technicianCount . ' técnicos', 'trendUp' => true,
                'hint' => 'asignados a ' . $technicianCount . ' técnicos', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 7v5l3.5 2"/><circle cx="12" cy="12" r="9"/></svg>',
            ],
            [
                'label' => 'Técnicos activos', 'value' => (string) $technicianCount,
                'trend' => 'con al menos un servicio', 'trendUp' => true,
                'hint' => 'con al menos un servicio asignado', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M5 20a7 7 0 0114 0"/></svg>',
            ],
        ];

        $panels = [
            $this->porEstatusPanel($services),
            $this->participacionTecnicoPanel($services),
            $this->porPrioridadPanel($services),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function porEstatusPanel(Collection $services): array
    {
        $labels = ['completed' => 'Completado', 'in_progress' => 'En curso', 'scheduled' => 'Programado', 'draft' => 'Borrador', 'cancelled' => 'Cancelado'];
        $colors = ['completed' => 'ok', 'in_progress' => 'warn', 'scheduled' => 'info', 'draft' => 'gray', 'cancelled' => 'err'];
        $counts = $services->countBy('status');

        $rows = collect($labels)->map(fn ($label, $status) => [$label, $counts->get($status, 0), $colors[$status]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 's1', 'title' => 'Servicios por estatus', 'subtitle' => 'Órdenes de servicio técnico',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Servicios', 'totalLabel' => 'servicios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function participacionTecnicoPanel(Collection $services): array
    {
        $counts = [];
        foreach ($services as $service) {
            foreach ($service->assignedTechnicians as $tech) {
                $counts[$tech->id] ??= ['label' => $tech->full_name ?? trim(($tech->first_name ?? '') . ' ' . ($tech->last_name ?? '')), 'count' => 0];
                $counts[$tech->id]['count']++;
            }
        }

        $rows = collect($counts)->map(fn ($r) => [$r['label'], $r['count'], ''])
            ->sortByDesc(fn ($r) => $r[1])->values()->all();

        return [
            'id' => 's2', 'title' => 'Participación por técnico', 'subtitle' => 'Servicios en los que participó cada técnico en el rango',
            'modes' => ['rank', 'bars', 'table'], 'span' => true, 'rank' => true, 'noPct' => true,
            'colLabel' => 'Técnico', 'colValue' => 'Servicios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'La asignación es una lista de técnicos por servicio: no existe rol de técnico líder, así que un mismo servicio cuenta para todos los que participaron y la suma de la columna puede superar el total de servicios.',
        ];
    }

    private function porPrioridadPanel(Collection $services): array
    {
        $labels = ['urgent' => 'Urgente', 'high' => 'Alta', 'normal' => 'Normal'];
        $colors = ['urgent' => 'err', 'high' => 'warn', 'normal' => 'info'];
        $counts = $services->countBy(fn ($s) => $s->priority ?: 'normal');

        $rows = collect($labels)->map(fn ($label, $key) => [$label, $counts->get($key, 0), $colors[$key]])
            ->filter(fn ($r) => $r[1] > 0)->values()->all();

        return [
            'id' => 's3', 'title' => 'Servicios por prioridad', 'subtitle' => 'Distribución del periodo',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Prioridad', 'colValue' => 'Servicios', 'totalLabel' => 'servicios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }
}
