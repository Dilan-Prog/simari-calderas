<?php

namespace App\Services\Statistics;

use App\Models\ServiceReport;
use App\Models\ServiceReportMeasurement;

class ReportesStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $reports = ServiceReport::whereBetween('created_at', [$from, $to])->get();
        $signed = $reports->whereNotNull('signed_at');
        $pendingLong = $reports->whereNull('signed_at')->filter(fn ($r) => $r->created_at->lt(now()->subDays(15)));

        $measurements = ServiceReportMeasurement::whereHas('report', fn ($q) => $q->whereBetween('created_at', [$from, $to]))->get();
        // ->where('in_range', false) usa comparación débil: en PHP null == false
        // es true, así que una medición sin evaluar (in_range NULL — sin resultado
        // capturado, resultado no numérico, o sin límites configurados, los 3 casos
        // reales de ServiceReportService::calculateInRange()) se contaría como
        // "fuera de rango" sin serlo. Filtro estricto para excluir los NULL.
        $outOfRange = $measurements->filter(fn ($m) => $m->in_range === false);

        $kpis = [
            [
                'label' => 'Reportes emitidos', 'value' => (string) $reports->count(),
                'trend' => 'ligados a servicios cerrados', 'trendUp' => true,
                'hint' => 'ligados a servicios técnicos', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9l4 4v13H6z"/></svg>',
            ],
            [
                'label' => 'Reportes firmados', 'value' => $reports->count() > 0 ? StatisticsFormatter::pct($signed->count() / $reports->count() * 100) : '0%',
                'trend' => $signed->count() . ' firmados', 'trendUp' => true,
                'hint' => $signed->count() . ' con firma del cliente', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 6.5"/></svg>',
            ],
            [
                'label' => 'Mediciones fuera de rango', 'value' => (string) $outOfRange->count(),
                'trend' => 'de ' . $measurements->count() . ' mediciones', 'trendUp' => true,
                'hint' => 'de ' . number_format($measurements->count()) . ' mediciones tomadas', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 17h.01M10.3 3.9L2.5 18h19z"/></svg>',
            ],
            [
                'label' => 'Sin firma +15 días', 'value' => (string) $pendingLong->count(),
                'trend' => 'requieren seguimiento', 'trendUp' => $pendingLong->count() === 0,
                'hint' => 'requieren seguimiento', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 7v5l3.5 2"/><circle cx="12" cy="12" r="9"/></svg>',
            ],
        ];

        if ($prevRange = StatisticsFormatter::previousRange($period)) {
            $prevReports = ServiceReport::whereBetween('created_at', $prevRange)->get();
            $prevMeasurements = ServiceReportMeasurement::whereHas('report', fn ($q) => $q->whereBetween('created_at', $prevRange))->get();
            $prevPendingLong = $prevReports->whereNull('signed_at')->filter(fn ($r) => $r->created_at->lt(now()->subDays(15)))->count();
            $signedPct = fn ($rs, $sg) => $rs->count() > 0 ? $sg->count() / $rs->count() * 100 : 0.0;

            $kpis[0] = StatisticsFormatter::withComparison($kpis[0], $period, (float) $reports->count(), (float) $prevReports->count(), fn (float $v) => (string) (int) $v);
            $kpis[1] = StatisticsFormatter::withComparison($kpis[1], $period, $signedPct($reports, $signed), $signedPct($prevReports, $prevReports->whereNotNull('signed_at')), fn (float $v) => StatisticsFormatter::pct($v), false, true);
            $kpis[2] = StatisticsFormatter::withComparison($kpis[2], $period, (float) $outOfRange->count(), (float) $prevMeasurements->filter(fn ($m) => $m->in_range === false)->count(), fn (float $v) => (string) (int) $v, true);
            $kpis[3] = StatisticsFormatter::withComparison($kpis[3], $period, (float) $pendingLong->count(), (float) $prevPendingLong, fn (float $v) => (string) (int) $v, true);
        }

        $panels = [
            $this->fueraDeRangoPanel($outOfRange, $measurements->count()),
            $this->firmadosPanel($reports),
            $this->porMesPanel(),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function fueraDeRangoPanel($outOfRange, int $total): array
    {
        $rows = $outOfRange->countBy('parameter')
            ->map(fn ($count, $param) => [$param ?: 'Sin especificar', $count, 'err'])
            ->sortByDesc(fn ($r) => $r[1])->values()->take(10)->all();

        return [
            'id' => 'p1', 'title' => 'Mediciones fuera de rango', 'subtitle' => 'Parámetros químicos por tipo',
            'modes' => ['bars', 'table', 'donut'], 'span' => true, 'noPct' => true,
            'colLabel' => 'Parámetro', 'colValue' => 'Fuera de rango', 'totalLabel' => 'mediciones',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Marca la bandera "en rango" capturada en cada medición del reporte de servicio. ' . $outOfRange->count() . ' fuera de rango de ' . number_format($total) . ' tomadas.',
        ];
    }

    private function firmadosPanel($reports): array
    {
        $signed = $reports->whereNotNull('signed_at')->count();
        $pendingLong = $reports->whereNull('signed_at')->filter(fn ($r) => $r->created_at->lt(now()->subDays(15)))->count();
        $pendingRecent = $reports->whereNull('signed_at')->count() - $pendingLong;

        $rows = array_values(array_filter([
            ['Firmados', $signed, 'ok'],
            ['Pendientes de firma', max(0, $pendingRecent), 'warn'],
            ['Sin firma tras 15 días', $pendingLong, 'err'],
        ], fn ($r) => $r[1] > 0));

        return [
            'id' => 'p2', 'title' => 'Reportes firmados', 'subtitle' => 'Firma del cliente al cierre del servicio',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Estatus', 'colValue' => 'Reportes', 'totalLabel' => 'reportes',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function porMesPanel(): array
    {
        $months = StatisticsPeriodResolver::trailingMonths(6);
        $counts = [];

        foreach ($months as $m) {
            $counts[] = ServiceReport::whereBetween('created_at', [$m['from'], $m['to']])->count();
        }

        return [
            'id' => 'p3', 'title' => 'Reportes por mes', 'subtitle' => 'Volumen de reportes de servicio emitidos (últimos 6 meses)',
            'modes' => ['line', 'columns', 'table'], 'noPct' => true,
            'axis' => array_column($months, 'label'), 'series' => $counts,
            'colLabel' => 'Mes', 'colValue' => 'Reportes',
            'rows' => array_map(fn ($lbl, $v) => [$lbl, $v, 'brand'], array_column($months, 'label'), $counts),
            'foot' => 'Conteo de reportes emitidos por periodo.',
        ];
    }
}
