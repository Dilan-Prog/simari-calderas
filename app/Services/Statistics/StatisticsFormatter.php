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
            return ['label' => $current > 0 ? '+100%' : '0%', 'up' => $current >= $previous];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'label' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
            'up' => $change >= 0,
        ];
    }
}
