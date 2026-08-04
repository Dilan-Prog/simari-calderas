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

    private function present(?string $value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    /**
     * Resuelve la cadena jerárquica Categoría Principal > Subcategoría >
     * Categoría Hija a un único category_id final. Si un nivel indicado NO
     * resuelve (typo), nunca hace fallback silencioso al nivel anterior:
     * devuelve el error correspondiente y deja category_id en null — es
     * responsabilidad de quien llama rechazar la fila.
     *
     * @param  array<int,array<string,int>>  $categoriesByParentId  [parent_id (0 = raíz) => [nombre_lower_trim => id]]
     * @return array{0: int|null, 1: array<string,string>} [categoryId, errores por campo ('categoria_principal'|'subcategoria'|'categoria_hija')]
     */
    private function resolveCategoryChain(?string $principalName, ?string $subName, ?string $childName, array $categoriesByParentId): array
    {
        $principalPresent = $this->present($principalName);
        $subPresent = $this->present($subName);
        $childPresent = $this->present($childName);

        if (!$principalPresent && !$subPresent && !$childPresent) {
            return [null, []];
        }

        if (!$principalPresent) {
            $field = $subPresent ? 'subcategoria' : 'categoria_hija';

            return [null, [$field => 'Debes indicar Categoría Principal si llenas Subcategoría o Categoría Hija.']];
        }

        $principalId = $categoriesByParentId[0][strtolower(trim($principalName))] ?? null;
        if (!$principalId) {
            // rules() ya reporta este error (categoría principal inexistente);
            // no lo duplicamos aquí.
            return [null, []];
        }

        $subId = null;
        if ($subPresent) {
            $subId = $categoriesByParentId[$principalId][strtolower(trim($subName))] ?? null;
            if (!$subId) {
                return [null, ['subcategoria' => "\"{$subName}\" no es una subcategoría válida de \"{$principalName}\"."]];
            }
        }

        if ($childPresent && !$subPresent) {
            return [null, ['categoria_hija' => 'Debes indicar Subcategoría para poder usar Categoría Hija.']];
        }

        $childId = null;
        if ($childPresent) {
            $childId = $categoriesByParentId[$subId][strtolower(trim($childName))] ?? null;
            if (!$childId) {
                return [null, ['categoria_hija' => "\"{$childName}\" no es una categoría hija válida de \"{$subName}\"."]];
            }
        }

        return [$childId ?? $subId ?? $principalId, []];
    }
}
