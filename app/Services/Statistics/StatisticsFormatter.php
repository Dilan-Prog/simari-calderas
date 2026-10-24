<?php

namespace App\Services\Statistics;

/**
 * Helpers de formato compartidos por los servicios de dominio de
 * Estadísticas — los valores de KPI viajan ya formateados como string
 * (mismo criterio que el mockup de diseño: '$6.30M', '45%', '16.5 d'),
 * el JS del panel solo formatea los valores de rows/series (enteros o
 * money simple), no los KPIs.
 */
class StatisticsFormatter
{
    public static function compactMoney(float $amount): string
    {
        $abs = abs($amount);

        if ($abs >= 1_000_000) {
            return '$' . number_format($amount / 1_000_000, 2) . 'M';
        }

        if ($abs >= 1_000) {
            return '$' . number_format($amount / 1_000, 1) . 'k';
        }

        return '$' . number_format($amount, 0);
    }

    public static function money(float $amount): string
    {
        return '$' . number_format($amount, 0);
    }

    public static function pct(float $value, int $decimals = 0): string
    {
        return number_format($value, $decimals) . '%';
    }

    public static function trendPct(float $current, float $previous): array
    {
        if ($previous <= 0) {
            // Sin base de comparación no existe un porcentaje real (dividir
            // entre 0): "Nuevo" si ya hay actividad, "0%" si ambos son 0.
            return ['label' => $current > 0 ? 'Nuevo' : '0%', 'up' => $current >= $previous];
        }

        $change = (($current - $previous) / $previous) * 100;

        if (round($change, 1) == 0) {
            return ['label' => '0%', 'up' => true];
        }

        return [
            'label' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
            'up' => $change >= 0,
        ];
    }

    /**
     * Superpone la comparación contra el periodo anterior sobre un KPI ya
     * armado. Sin comparación activa (o sin valor previo) devuelve el KPI
     * intacto.
     *
     * - trend: variación porcentual ("+12.5%"), o puntos porcentuales
     *   ("+3.2 pp") cuando $points es true (métricas que ya son un %).
     * - trendUp: si el cambio es favorable (con $lowerIsBetter, bajar es bueno).
     * - hint: valor del periodo anterior y su rango.
     *
     * @param callable(float): string $format formatea el valor previo para el hint
     */
    public static function withComparison(array $kpi, array $period, float $current, ?float $previous, callable $format, bool $lowerIsBetter = false, bool $points = false): array
    {
        if (empty($period['compare']) || $previous === null) {
            return $kpi;
        }

        if ($points) {
            $diff = round($current - $previous, 1);
            $label = $diff == 0 ? '0 pp' : (($diff > 0 ? '+' : '') . number_format($diff, 1) . ' pp');
            $improved = $diff >= 0;
        } else {
            $trend = self::trendPct($current, $previous);
            $label = $trend['label'];
            $improved = $trend['up'];
        }

        $changed = abs($current - $previous) > 1e-9;
        if ($lowerIsBetter) {
            $improved = $changed ? !$improved : true;
        }

        $kpi['trend'] = $label;
        $kpi['trendUp'] = $improved;
        // El rango exacto del periodo anterior se muestra en la barra de filtros;
        // aquí solo el valor, para que quepa en la tarjeta.
        $kpi['hint'] = 'vs ' . $format($previous) . ' · ' . ($period['compareLabel'] ?? 'periodo anterior');

        return $kpi;
    }

    /**
     * Rango anterior listo para consultar, o null si no hay comparación.
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    public static function previousRange(array $period): ?array
    {
        if (empty($period['compare']) || empty($period['compareFrom']) || empty($period['compareTo'])) {
            return null;
        }

        return [$period['compareFrom'], $period['compareTo']];
    }
}
