<?php

namespace App\Services\Statistics;

use App\Models\EmailSend;

/**
 * Sección "Marketing por correo" de Estadísticas — KPIs y paneles de
 * apertura/clics agregados sobre TODOS los envíos de correo registrados
 * en `email_sends` (manuales, de campaña, de secuencia y de
 * automatización/workflow), sin importar su origen. No escribe datos:
 * la creación de filas EmailSend (y el tracking de apertura/clic) la
 * resuelven otros módulos (envío manual de Cotizaciones, prueba SMTP,
 * campañas, secuencias, workflows) — este servicio solo reporta.
 */
class MarketingStatsService
{
    public function summary(array $period): array
    {
        $from = $period['from'];
        $to = $period['to'];

        $sends = EmailSend::whereBetween('sent_at', [$from, $to])->get();
        $total = $sends->count();
        $opened = $sends->whereNotNull('opened_at')->count();
        $clicked = $sends->whereNotNull('clicked_at')->count();
        $bounced = $sends->whereNotNull('bounced_at')->count();

        $kpis = [
            [
                'label' => 'Correos enviados', 'value' => (string) $total,
                'trend' => 'en el periodo', 'trendUp' => true,
                'hint' => 'manuales y automáticos', 'color' => 'brand',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4z"/><path d="m4 6 8 7 8-7"/></svg>',
            ],
            [
                'label' => 'Tasa de apertura', 'value' => $total > 0 ? StatisticsFormatter::pct($opened / $total * 100, 1) : '0%',
                'trend' => $opened . ' abiertos', 'trendUp' => true,
                'hint' => $opened . ' de ' . number_format($total) . ' abiertos', 'color' => 'ok',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
            ],
            [
                'label' => 'Tasa de clics', 'value' => $total > 0 ? StatisticsFormatter::pct($clicked / $total * 100, 1) : '0%',
                'trend' => $clicked . ' con clic', 'trendUp' => true,
                'hint' => $clicked . ' de ' . number_format($total) . ' con clic', 'color' => 'info',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 9l11 4-4.5 2L14 20z"/></svg>',
            ],
            [
                'label' => 'Rebotados', 'value' => (string) $bounced,
                'trend' => 'requieren revisión', 'trendUp' => $bounced === 0,
                'hint' => 'de ' . number_format($total) . ' enviados', 'color' => 'violet',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5M12 17h.01M10.3 3.9L2.5 18h19z"/></svg>',
            ],
        ];

        $panels = [
            $this->origenPanel($sends),
            $this->porMesPanel(),
        ];

        return ['kpis' => $kpis, 'panels' => $panels];
    }

    private function origenPanel($sends): array
    {
        $groups = [
            'Manual'          => 0,
            'Campaña'         => 0,
            'Secuencia'       => 0,
            'Automatización'  => 0,
        ];
        $groupOpens = [
            'Manual'          => 0,
            'Campaña'         => 0,
            'Secuencia'       => 0,
            'Automatización'  => 0,
        ];

        foreach ($sends as $send) {
            $key = match (true) {
                !empty($send->email_campaign_id)      => 'Campaña',
                !empty($send->email_sequence_step_id) => 'Secuencia',
                !empty($send->workflow_step_id)       => 'Automatización',
                default                                 => 'Manual',
            };

            $groups[$key]++;

            if (!is_null($send->opened_at)) {
                $groupOpens[$key]++;
            }
        }

        $colors = [
            'Manual'         => 'brand',
            'Campaña'        => 'info',
            'Secuencia'      => 'violet',
            'Automatización' => 'ok',
        ];

        $rows = collect($groups)
            ->map(function ($count, $label) use ($colors) {
                return [$label, $count, $colors[$label]];
            })
            ->filter(fn ($r) => $r[1] > 0)
            ->sortByDesc(fn ($r) => $r[1])
            ->values()
            ->all();

        return [
            'id' => 'm1', 'title' => 'Envíos por origen', 'subtitle' => 'Manual, campaña, secuencia y automatización',
            'modes' => ['donut', 'bars', 'table'], 'colLabel' => 'Origen', 'colValue' => 'Correos', 'totalLabel' => 'correos',
            'rows' => $rows, 'state' => empty($rows) ? 'empty' : null,
            'foot' => 'Origen determinado por la referencia guardada en cada envío (campaña, paso de secuencia o paso de automatización); sin ninguna de las tres, se considera envío manual.',
        ];
    }

    private function porMesPanel(): array
    {
        $months = StatisticsPeriodResolver::trailingMonths(6);
        $sent = [];
        $opened = [];

        foreach ($months as $m) {
            $rows = EmailSend::whereBetween('sent_at', [$m['from'], $m['to']])->get();
            $sent[] = $rows->count();
            $opened[] = $rows->whereNotNull('opened_at')->count();
        }

        return [
            'id' => 'm2', 'title' => 'Envíos por mes', 'subtitle' => 'Enviados vs. abiertos (últimos 6 meses)',
            'modes' => ['line', 'columns', 'table'], 'noPct' => true,
            'axis' => array_column($months, 'label'), 'series' => $sent, 'compareSeries' => $opened,
            'compareLabel' => 'Abiertos',
            'colLabel' => 'Mes', 'colValue' => 'Enviados',
            'rows' => array_map(fn ($lbl, $v) => [$lbl, $v, 'brand'], array_column($months, 'label'), $sent),
            'foot' => 'Conteo de correos enviados y abiertos por mes, todos los orígenes.',
        ];
    }
}
