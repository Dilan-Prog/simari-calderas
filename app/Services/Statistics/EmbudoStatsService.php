<?php

namespace App\Services\Statistics;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Services\DealReportService;
use App\Services\PrebuiltReportService;

/**
 * Wrapper de composición, no un servicio de datos propio: reutiliza
 * PrebuiltReportService/DealReportService (motor de Reportes del CRM) para
 * que "Estadísticas > Embudo" nunca diverja de admin.reports.prebuilt.*.
 * Se mantiene como *StatsService solo para que StatisticsController pueda
 * despachar las 7 secciones con el mismo patrón uniforme.
 */
class EmbudoStatsService
{
    public function __construct(
        protected PrebuiltReportService $prebuilt = new PrebuiltReportService(),
        protected DealReportService $dealReports = new DealReportService()
    ) {
    }

    public function summary(array $period): array
    {
        $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();

        if (!$pipeline) {
            return [
                'kpis' => [],
                'panels' => [[
                    'id' => 'e0', 'title' => 'Embudo de ventas', 'subtitle' => 'Sin pipeline configurado',
                    'modes' => ['table'], 'rows' => [], 'state' => 'empty',
                ]],
            ];
        }

        $from = $period['from'];
        $to = $period['to'];

        $velocity = $this->dealReports->pipelineVelocity($pipeline->id, $from, $to);
        $lost = Deal::where('pipeline_id', $pipeline->id)->where('status', 'lost')
            ->whereBetween('closed_at', [$from, $to])->get();
        $abiertos = Deal::where('pipeline_id', $pipeline->id)->where('status', 'open')->count();
        $totalCerrados = $velocity['total_won'] + $lost->count();

        $kpis = [
            [
                'label' => 'Negocios ganados', 'value' => (string) $velocity['total_won'],
                'trend' => StatisticsFormatter::compactMoney($velocity['total_amount']), 'trendUp' => true,
                'hint' => 'monto total ganado en el rango', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5 5L20 6.5"/></svg>',
            ],
            [
                'label' => 'Tasa de conversión', 'value' => $totalCerrados > 0 ? StatisticsFormatter::pct($velocity['total_won'] / $totalCerrados * 100) : '0%',
                'trend' => $velocity['total_won'] . ' ganados / ' . $lost->count() . ' perdidos', 'trendUp' => true,
                'hint' => 'ganados sobre negocios cerrados en el rango', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 7v5l3.5 2"/><circle cx="12" cy="12" r="9"/></svg>',
            ],
            [
                'label' => 'Días promedio de cierre', 'value' => $velocity['avg_days_to_close'] . ' d',
                'trend' => 'de creación a cierre ganado', 'trendUp' => true,
                'hint' => 'promedio de negocios ganados en el rango', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3.5h9l4 4v13H6z"/></svg>',
            ],
            [
                'label' => 'Negocios abiertos', 'value' => (string) $abiertos,
                'trend' => 'en el pipeline "' . $pipeline->name . '"', 'trendUp' => true,
                'hint' => 'total actual, no depende del rango de fechas', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M5 20a7 7 0 0114 0"/></svg>',
            ],
        ];

        $panels = [
            $this->embudoPanel($pipeline->id),
            $this->desempenoPorEtapaPanel($pipeline->id),
            $this->motivosPerdidaPanel($lost),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function embudoPanel(int $pipelineId): array
    {
        $stages = $this->prebuilt->funnelAnalysis($pipelineId);
        $rows = array_map(fn ($s) => [$s['stage'], $s['count'], 'brand'], $stages);

        return [
            'id' => 'e1', 'title' => 'Embudo de ventas', 'subtitle' => 'Negocios únicos que pasaron por cada etapa (histórico completo del pipeline)',
            'modes' => ['bars', 'table'], 'span' => true, 'noPct' => true,
            'colLabel' => 'Etapa', 'colValue' => 'Negocios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Mismos datos que admin.reports.prebuilt.funnel-analysis — no es un cálculo independiente.',
        ];
    }

    private function desempenoPorEtapaPanel(int $pipelineId): array
    {
        $performance = $this->prebuilt->pipelinePerformance($pipelineId);
        $rows = array_map(fn ($r) => [$r['stage_name'], $r['avg_days'] ?? 0, 'info'], $performance['rows']);

        return [
            'id' => 'e2', 'title' => 'Tiempo promedio por etapa', 'subtitle' => 'Días que un negocio permanece en cada etapa antes de avanzar',
            'modes' => ['bars', 'table'], 'noPct' => true, 'colLabel' => 'Etapa', 'colValue' => 'Días promedio', 'totalLabel' => 'días',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
        ];
    }

    private function motivosPerdidaPanel($lost): array
    {
        $rows = $lost->countBy(fn ($d) => trim((string) $d->lost_reason) !== '' ? trim($d->lost_reason) : 'Sin motivo especificado')
            ->map(fn ($count, $reason) => [$reason, $count, 'err'])
            ->sortByDesc(fn ($r) => $r[1])->values()->all();

        return [
            'id' => 'e3', 'title' => 'Motivos de pérdida', 'subtitle' => 'Negocios perdidos en el rango, agrupados por el texto capturado',
            'modes' => ['bars', 'table'], 'span' => true, 'noPct' => true,
            'colLabel' => 'Motivo', 'colValue' => 'Negocios perdidos', 'totalLabel' => 'negocios',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'El motivo es texto libre capturado al marcar un negocio como perdido, no un catálogo controlado — variantes similares aparecen como filas distintas.',
        ];
    }
}
