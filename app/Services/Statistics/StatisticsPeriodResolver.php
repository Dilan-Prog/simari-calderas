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

    /**
     * $dateFrom/$dateTo (formato Y-m-d, del selector de rango personalizado)
     * tienen prioridad sobre $period cuando ambos son fechas válidas — el
     * usuario eligió un rango explícito, no un preset.
     *
     * Devuelve además, cuando $compare es true, el rango de comparación
     * (compareFrom/compareTo/compareRangeLabel) y `query`: los parámetros de
     * query string que reproducen este mismo filtro (sirve para conservar el
     * estado al navegar entre secciones).
     */
    public static function resolve(?string $period, bool $compare = false, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if ($custom = self::resolveCustomRange($dateFrom, $dateTo, $compare)) {
            return $custom;
        }

        $period = array_key_exists($period ?? '', self::LABELS) ? $period : self::DEFAULT_PERIOD;

        [$from, $to] = self::range($period, 0);

        $result = [
            'period'            => $period,
            'label'             => self::LABELS[$period],
            'from'              => $from,
            'to'                => $to,
            'rangeLabel'        => self::formatRange($from, $to),
            'compare'           => $compare,
            'compareFrom'       => null,
            'compareTo'         => null,
            'compareRangeLabel' => null,
            'compareLabel'      => self::PREVIOUS_LABELS[$period],
            'query'             => array_filter(['period' => $period, 'compare' => $compare ? 1 : null]),
        ];

        if ($compare) {
            [$compareFrom, $compareTo] = self::previousRange($period, $from);
            $result['compareFrom'] = $compareFrom;
            $result['compareTo'] = $compareTo;
            $result['compareRangeLabel'] = self::formatRange($compareFrom, $compareTo);
        }

        return $result;
    }

    private static function resolveCustomRange(?string $dateFrom, ?string $dateTo, bool $compare): ?array
    {
        if (!self::isValidDate($dateFrom) || !self::isValidDate($dateTo)) {
            return null;
        }

        $from = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $result = [
            'period'            => 'custom',
            'label'             => 'Personalizado',
            'from'              => $from,
            'to'                => $to,
            'rangeLabel'        => self::formatRange($from, $to),
            'compare'           => $compare,
            'compareFrom'       => null,
            'compareTo'         => null,
            'compareRangeLabel' => null,
            'compareLabel'      => 'periodo anterior equivalente',
            'query'             => array_filter([
                'date_from' => $from->format('Y-m-d'),
                'date_to'   => $to->format('Y-m-d'),
                'compare'   => $compare ? 1 : null,
            ]),
        ];

        if ($compare) {
            [$compareFrom, $compareTo, $label] = self::customComparison($from, $to);
            $result['compareFrom'] = $compareFrom;
            $result['compareTo'] = $compareTo;
            $result['compareRangeLabel'] = self::formatRange($compareFrom, $compareTo);
            $result['compareLabel'] = $label;
        }

        return $result;
    }

    /**
     * Rango equivalente anterior de un rango personalizado.
     *  - Mes (o parte de un mes desde el día 1, p. ej. "Este mes" = 1 → hoy):
     *    el mes calendario anterior, con el mismo avance de días.
     *  - Bloque de meses completos (1.º de un mes → fin de otro mes, incluye
     *    trimestres y años): los mismos N meses calendario inmediatamente antes.
     *  - Cualquier otro rango: los mismos N días inmediatamente antes.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private static function customComparison(Carbon $from, Carbon $to): array
    {
        $fromIsFirst = $from->day === 1;
        $toIsEom = $to->isSameDay($to->copy()->endOfMonth());

        if ($fromIsFirst && $from->isSameMonth($to)) {
            $compareFrom = $from->copy()->subMonthNoOverflow()->startOfMonth();
            $compareTo = $toIsEom
                ? $compareFrom->copy()->endOfMonth()
                : self::min($to->copy()->subMonthNoOverflow()->endOfDay(), $compareFrom->copy()->endOfMonth());

            return [$compareFrom, $compareTo, 'mes anterior'];
        }

        if ($fromIsFirst && $toIsEom) {
            $months = ($to->year - $from->year) * 12 + ($to->month - $from->month) + 1;
            $compareFrom = $from->copy()->subMonthsNoOverflow($months)->startOfMonth();
            $compareTo = $from->copy()->subDay()->endOfDay();

            return [$compareFrom, $compareTo, $months === 12 ? 'año anterior' : 'periodo anterior equivalente'];
        }

        // Mismo número de días, justo antes del rango elegido.
        $days = self::daysBetween($from, $to) + 1;
        $compareTo = $from->copy()->subDay()->endOfDay();
        $compareFrom = $from->copy()->subDays($days)->startOfDay();

        return [$compareFrom, $compareTo, 'periodo anterior equivalente'];
    }

    private static function isValidDate(?string $date): bool
    {
        if (!$date || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) {
            return false;
        }

        // Acotado a un rango razonable: años absurdos (0001, 9999) rompen el
        // cálculo del rango anterior y no tienen datos reales.
        if ((int) $m[1] < 1990 || (int) $m[1] > 2100) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    /**
     * Días calendario entre dos fechas (ignora la hora y los cambios de horario).
     */
    private static function daysBetween(Carbon $a, Carbon $b): int
    {
        return Carbon::parse($a->toDateString(), 'UTC')->diffInDays(Carbon::parse($b->toDateString(), 'UTC'));
    }

    private static function min(Carbon $a, Carbon $b): Carbon
    {
        return $a->lte($b) ? $a : $b;
    }

    /**
     * Rango de un preset, $periodsAgo periodos hacia atrás. Siempre se ancla
     * al INICIO del periodo antes de restar: restar meses a una fecha del
     * 29-31 desbordaba (31 mar − 1 mes = 3 mar, así que "mes anterior" salía
     * marzo y no febrero).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function range(string $period, int $periodsAgo): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'hoy':
                $day = $now->copy()->startOfDay()->subDays($periodsAgo);
                return [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
            case 'semana':
                $week = $now->copy()->startOfWeek()->subWeeks($periodsAgo);
                return [$week->copy()->startOfWeek(), $week->copy()->endOfWeek()];
            case 'trimestre':
                $quarter = $now->copy()->startOfQuarter()->subMonthsNoOverflow(3 * $periodsAgo);
                return [$quarter->copy()->startOfQuarter(), $quarter->copy()->endOfQuarter()];
            case 'anio':
                $year = $now->copy()->startOfYear()->subYears($periodsAgo);
                return [$year->copy()->startOfYear(), $year->copy()->endOfYear()];
            default:
                $month = $now->copy()->startOfMonth()->subMonthsNoOverflow($periodsAgo);
                return [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()];
        }
    }

    /**
     * Periodo anterior equivalente de un preset. Si el periodo actual aún
     * no termina (p. ej. hoy es 24 oct y el preset es "Este mes"), el
     * anterior se recorta al mismo avance de días (1–24 sep) para comparar
     * con el mismo número de días, en vez de contra el mes completo.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function previousRange(string $period, Carbon $currentFrom): array
    {
        [$prevFrom, $prevTo] = self::range($period, 1);

        $elapsedDays = self::daysBetween($currentFrom, Carbon::now()) + 1;
        $truncated = $prevFrom->copy()->addDays($elapsedDays - 1)->endOfDay();

        return [$prevFrom, self::min($truncated, $prevTo)];
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
        // Anclado al inicio del mes: desde el 29-31 restar meses desbordaba
        // y repetía/saltaba meses en los paneles de tendencia.
        $currentMonth = Carbon::now()->startOfMonth();

        for ($i = $count - 1; $i >= 0; $i--) {
            $month = $currentMonth->copy()->subMonthsNoOverflow($i);
            $months[] = [
                'label' => $month->translatedFormat('M'),
                'from'  => $month->copy()->startOfMonth(),
                'to'    => $month->copy()->endOfMonth(),
            ];
        }

        return $months;
    }
}
