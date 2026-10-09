<?php

namespace App\Services\Catalog;

use Illuminate\Support\Str;

/**
 * Normaliza etiquetas de producto para compararlas: minúsculas, sin acentos
 * (ASCII) y espacios colapsados. "1/4 DIN", "1/4  din" y "1/4 Din" coinciden;
 * "1/4DIN" (sin espacio) NO, a propósito.
 */
class TagNormalizer
{
    public static function normalize(string $tag): string
    {
        return Str::of($tag)->ascii()->lower()->squish()->toString();
    }

    /** Normaliza una lista de etiquetas (products.tags) descartando vacías y duplicadas. */
    public static function normalizeMany(?array $tags): array
    {
        return collect($tags ?? [])
            ->filter(fn ($t) => is_string($t) && trim($t) !== '')
            ->map(fn ($t) => self::normalize($t))
            ->unique()
            ->values()
            ->all();
    }
}
