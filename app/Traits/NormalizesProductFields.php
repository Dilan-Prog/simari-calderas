<?php

namespace App\Traits;

trait NormalizesProductFields
{
    /**
     * Acepta tanto un número plano (10000) como texto con formato de
     * moneda ("$10,000.00") escrito a mano en una celda de Excel. Si tras
     * quitarle símbolos de moneda/miles no queda un número válido, devuelve
     * null y quien llama decide si eso es un error de validación.
     */
    private function sanitizePrice($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function normalizeProductBool($value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $v = strtolower(trim((string) $value));

        return in_array($v, ['1', 'si', 'sí', 'true', 'x', 'yes'], true);
    }

    private function normalizeProductEnum($value, array $allowed, string $default, bool $caseSensitiveAllowed = false): string
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }
        $v = trim((string) $value);

        foreach ($allowed as $option) {
            if ($caseSensitiveAllowed ? $v === $option : strtolower($v) === strtolower($option)) {
                return $option;
            }
        }

        return $default;
    }

    private function normalizeProductAvailability($value): string
    {
        $map = [
            'disponible' => 'available',
            'agotado' => 'out_of_stock',
            'sobre_pedido' => 'on_order',
            'sobre pedido' => 'on_order',
            'available' => 'available',
            'out_of_stock' => 'out_of_stock',
            'on_order' => 'on_order',
        ];
        $v = strtolower(trim((string) $value));

        return $map[$v] ?? 'available';
    }

    /** @param  array<string,int>  $namesToIdsLowerTrimmed */
    private function resolveIdByName(?string $name, array $namesToIdsLowerTrimmed): ?int
    {
        return $namesToIdsLowerTrimmed[strtolower(trim($name ?? ''))] ?? null;
    }
}
