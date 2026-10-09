<?php

namespace App\Services\Catalog;

/**
 * Rangos rápidos de precio del catálogo ("Hasta $X", "$X a $Y", "Más de $Y").
 *
 * Los puntos de corte salen de los cuantiles 33 % / 66 % de los precios del
 * alcance de categoría (SIN los demás filtros, para que los rangos no salten al
 * filtrar), redondeados "bonito" a 1-2-2.5-5-10 × 10^k. Todos los límites son
 * enteros en MXN sin IVA e INCLUSIVOS, y el filtro real usa el mismo predicado
 * (within) que el conteo de cada rango.
 */
class PriceBuckets
{
    /** Predicado ÚNICO del filtro de precio y del conteo por rango (límites inclusivos). */
    public static function within(float $price, ?int $min, ?int $max): bool
    {
        if ($min !== null && $price < $min) {
            return false;
        }

        return !($max !== null && $price > $max);
    }

    /**
     * Rangos [min, max] (null = abierto) a partir de los precios del alcance.
     * Devuelve [] si no hay precios suficientes para dividir.
     *
     * @param  float[] $prices
     * @return array<int, array{min: ?int, max: ?int}>
     */
    public static function ranges(array $prices): array
    {
        $prices = array_values($prices);
        $n = count($prices);
        if ($n < 2) {
            return [];
        }

        sort($prices);
        if ($prices[0] == $prices[$n - 1]) {
            return [];
        }

        $cuts = [];
        foreach ([0.33, 0.66] as $quantile) {
            $value = $prices[min($n - 1, (int) floor($n * $quantile))];
            $cut = self::nice($value);
            if ($cut > 0) {
                $cuts[$cut] = $cut;
            }
        }
        $cuts = array_values($cuts);
        sort($cuts);

        if (!$cuts) {
            return [];
        }

        $ranges = [['min' => null, 'max' => $cuts[0]]];
        for ($i = 1; $i < count($cuts); $i++) {
            $ranges[] = ['min' => $cuts[$i - 1], 'max' => $cuts[$i]];
        }
        $ranges[] = ['min' => end($cuts), 'max' => null];

        return $ranges;
    }

    /** Redondea al candidato 1-2-2.5-5-10 × 10^k más cercano (entero). */
    public static function nice(float $value): int
    {
        if ($value <= 0) {
            return 0;
        }
        if ($value < 10) {
            return max(1, (int) round($value));
        }

        $magnitude = (int) floor(log10($value));
        $best = null;
        foreach ([$magnitude - 1, $magnitude, $magnitude + 1] as $k) {
            if ($k < 0) {
                continue;
            }
            foreach ([1, 2, 2.5, 5, 10] as $mult) {
                $candidate = $mult * (10 ** $k);
                if ($candidate < 1 || floor($candidate) != $candidate) {
                    continue;
                }
                if ($best === null || abs($candidate - $value) < abs($best - $value)) {
                    $best = $candidate;
                }
            }
        }

        return (int) $best;
    }

    /** "$5,000" */
    public static function format(int $value): string
    {
        return '$' . number_format($value);
    }

    /** "Hasta $X" / "$X a $Y" / "Más de $Y" */
    public static function label(?int $min, ?int $max): string
    {
        if ($min === null && $max === null) {
            return '';
        }
        if ($min === null) {
            return 'Hasta ' . self::format($max);
        }
        if ($max === null) {
            return 'Más de ' . self::format($min);
        }

        return self::format($min) . ' a ' . self::format($max);
    }
}
