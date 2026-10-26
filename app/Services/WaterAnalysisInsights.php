<?php

namespace App\Services;

use App\Models\WaterAnalysisParameter;
use App\Models\WaterAnalysisReport;

/**
 * Datos agregados del Reporte de Análisis Químico de un cliente para las gráficas de
 * Portal > Monitoreo (línea por parámetro, área de cumplimiento diario y pastel de lecturas).
 * El estado de cada lectura se evalúa contra los límites VIGENTES del parámetro.
 */
class WaterAnalysisInsights
{
    public const PERIODS = [7, 30, 90];

    public function forCustomer(int $customerId, int $days = 30): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 30;

        $end = now()->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $parameters = WaterAnalysisParameter::where('is_active', true)->orderBy('sort_order')->get();

        $reports = WaterAnalysisReport::where('customer_id', $customerId)
            ->whereDate('report_date', '>=', $start->toDateString())
            ->whereDate('report_date', '<=', $end->toDateString())
            ->with('results')
            ->get()
            ->keyBy(fn ($r) => $r->report_date->toDateString());

        $dates = [];
        $labels = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->toDateString();
            $labels[] = $d->locale('es')->isoFormat('D MMM');
        }

        $series = [];
        foreach ($parameters as $p) {
            $series[] = [
                'code'     => $p->code,
                'name'     => $p->name,
                'unit'     => $p->unit,
                'min'      => $p->min_value !== null ? (float) $p->min_value : null,
                'max'      => $p->max_value !== null ? (float) $p->max_value : null,
                'decimals' => (int) $p->decimals,
                'values'   => array_map(function ($date) use ($reports, $p) {
                    $res = $reports->get($date)?->results->firstWhere('parameter_id', $p->id);

                    return $res ? (float) $res->value : null;
                }, $dates),
            ];
        }

        // Área: parámetros dentro / fuera de rango / sin rango por día (null = día sin registro).
        $area = ['ok' => [], 'out' => [], 'none' => []];
        $dist = [
            WaterAnalysisParameter::STATUS_OK   => 0,
            WaterAnalysisParameter::STATUS_LOW  => 0,
            WaterAnalysisParameter::STATUS_HIGH => 0,
            WaterAnalysisParameter::STATUS_NONE => 0,
        ];
        foreach ($dates as $date) {
            $report = $reports->get($date);
            if (! $report) {
                $area['ok'][] = $area['out'][] = $area['none'][] = null;
                continue;
            }
            $ok = $out = $none = 0;
            foreach ($parameters as $p) {
                $res = $report->results->firstWhere('parameter_id', $p->id);
                $status = $res ? $p->statusFor($res->value) : null;
                if ($status === null) {
                    continue;
                }
                $dist[$status]++;
                match ($status) {
                    WaterAnalysisParameter::STATUS_OK => $ok++,
                    WaterAnalysisParameter::STATUS_NONE => $none++,
                    default => $out++,
                };
            }
            $area['ok'][] = $ok;
            $area['out'][] = $out;
            $area['none'][] = $none;
        }

        // Último registro del periodo.
        $latest = null;
        $lastReport = $reports->sortByDesc(fn ($r) => $r->report_date)->first();
        if ($lastReport) {
            $latest = [
                'date'   => $lastReport->report_date->locale('es')->isoFormat('D MMM YYYY'),
                'values' => $parameters->map(function ($p) use ($lastReport) {
                    $res = $lastReport->results->firstWhere('parameter_id', $p->id);

                    return [
                        'name'   => $p->name,
                        'unit'   => $p->unit,
                        'value'  => $res ? $p->formatValue($res->value) : null,
                        'status' => $res ? $p->statusFor($res->value) : null,
                        'min'    => $p->min_value !== null ? $p->formatValue($p->min_value) : null,
                        'max'    => $p->max_value !== null ? $p->formatValue($p->max_value) : null,
                    ];
                })->all(),
            ];
        }

        $ranged = $dist[WaterAnalysisParameter::STATUS_OK] + $dist[WaterAnalysisParameter::STATUS_LOW] + $dist[WaterAnalysisParameter::STATUS_HIGH];

        return [
            'days'        => $days,
            'has_data'    => $reports->isNotEmpty(),
            'labels'      => $labels,
            'series'      => $series,
            'area'        => $area,
            'distribution' => $dist,
            'latest'      => $latest,
            'kpis'        => [
                'days_logged'  => $reports->count(),
                'days_window'  => $days,
                'readings'     => array_sum($dist),
                'compliance'   => $ranged > 0 ? (int) round($dist[WaterAnalysisParameter::STATUS_OK] / $ranged * 100) : null,
                'out_of_range' => $dist[WaterAnalysisParameter::STATUS_LOW] + $dist[WaterAnalysisParameter::STATUS_HIGH],
            ],
        ];
    }
}
