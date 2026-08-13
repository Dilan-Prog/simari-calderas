<?php

namespace App\Services\Statistics;

use Carbon\Carbon;

/**
 * Resuelve el slug de periodo del query string (?period=mes) a un rango de
 * fechas real, más el rango equivalente del periodo anterior para el
 * comparativo opcional (?compare=1). Un solo resultado se aplica a todos
 * los paneles de la página — el filtro es global, no por panel (ver plan
 * del módulo Estadísticas).
 */
class StatisticsPeriodResolver
{
    public const DEFAULT_PERIOD = 'mes';

    private const LABELS = [
        'hoy'        => 'Hoy',
        'semana'     => 'Esta semana',
        'mes'        => 'Este mes',
        'trimestre'  => 'Trimestre',
        'anio'       => 'Año',
    ];

    private const PREVIOUS_LABELS = [
        'hoy'        => 'ayer',
        'semana'     => 'semana anterior',
        'mes'        => 'mes anterior',
        'trimestre'  => 'trimestre anterior',
        'anio'       => 'año anterior',
    ];

    public static function periods(): array
    {
        return self::LABELS;
    }

    public static function resolve(?string $period, bool $compare = false): array
    {
        $period = array_key_exists($period ?? '', self::LABELS) ? $period : self::DEFAULT_PERIOD;

        [$from, $to] = self::range($period, 0);

        $result = [
            'period'      => $period,
            'label'       => self::LABELS[$period],
            'from'        => $from,
            'to'          => $to,
            'rangeLabel'  => self::formatRange($from, $to),
            'compare'     => $compare,
            'compareFrom' => null,
            'compareTo'   => null,
            'compareLabel' => self::PREVIOUS_LABELS[$period],
        ];

        if ($compare) {
            [$compareFrom, $compareTo] = self::range($period, 1);
            $result['compareFrom'] = $compareFrom;
            $result['compareTo'] = $compareTo;
        }

        return $result;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function range(string $period, int $periodsAgo): array
    {
        $now = Carbon::now();

        return match ($period) {
            'hoy'       => [$now->copy()->subDays($periodsAgo)->startOfDay(), $now->copy()->subDays($periodsAgo)->endOfDay()],
            'semana'    => [$now->copy()->subWeeks($periodsAgo)->startOfWeek(), $now->copy()->subWeeks($periodsAgo)->endOfWeek()],
            'trimestre' => [$now->copy()->subQuarters($periodsAgo)->startOfQuarter(), $now->copy()->subQuarters($periodsAgo)->endOfQuarter()],
            'anio'      => [$now->copy()->subYears($periodsAgo)->startOfYear(), $now->copy()->subYears($periodsAgo)->endOfYear()],
            default     => [$now->copy()->subMonths($periodsAgo)->startOfMonth(), $now->copy()->subMonths($periodsAgo)->endOfMonth()],
        };
    }

    private static function formatRange(Carbon $from, Carbon $to): string
    {
        if ($from->isSameDay($to)) {
            return $from->translatedFormat('d M Y');
        }

        if ($from->year === $to->year) {
            return $from->translatedFormat('d M') . ' – ' . $to->translatedFormat('d M Y');
        }

        return $from->translatedFormat('d M Y') . ' – ' . $to->translatedFormat('d M Y');
    }

    /**
     * Últimos N meses (label corto + rango), usado por los paneles de
     * tendencia (line/columns) de cada dominio — independiente del filtro
     * de periodo global, que solo acota el rango "actual" de los paneles
     * de proporción/ranking. Los paneles de tendencia siempre muestran los
     * últimos 6 meses para dar contexto de evolución.
     *
     * @return array<int, array{label: string, from: Carbon, to: Carbon}>
     */
    public static function trailingMonths(int $count = 6): array
    {
        $months = [];
        $now = Carbon::now();

        for ($i = $count - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $months[] = [
                'label' => $month->translatedFormat('M'),
                'from'  => $month->copy()->startOfMonth(),
                'to'    => $month->copy()->endOfMonth(),
            ];
        }

        return $months;
    }
}
