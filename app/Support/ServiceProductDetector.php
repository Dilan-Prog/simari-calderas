<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Detecta PRODUCTOS que en realidad son servicios (p. ej. "Mantenimiento
 * Preventivo Mayor…", "Servicio de Calibración…"), para migrar las
 * cotizaciones que los usan hacia las páginas de servicio (ServicePage).
 *
 * Heurística (cualquiera de las dos basta):
 *  1. El producto pertenece a una categoría llamada "Servicios" (o a una
 *     descendiente de ella).
 *  2. El nombre EMPIEZA con alguna palabra configurada (sin importar
 *     mayúsculas ni acentos): Servicio, Mantenimiento, Reparación,
 *     Instalación, Calibración, Diagnóstico.
 *
 * Configurable con el setting `services.detector_patterns` (JSON: lista de
 * palabras/frases de inicio de nombre) y `services.detector_categories`
 * (JSON: nombres de categoría; por omisión ["Servicios"]).
 */
class ServiceProductDetector
{
    public const DEFAULT_PATTERNS = [
        'Servicio', 'Mantenimiento', 'Reparación', 'Instalación', 'Calibración', 'Diagnóstico',
    ];

    public const DEFAULT_CATEGORIES = ['Servicios'];

    /** Palabras de inicio de nombre (tal como se configuraron). */
    public static function patterns(): array
    {
        $raw = Setting::get('services.detector_patterns');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $raw);
        }

        if (!is_array($raw) || $raw === []) {
            return self::DEFAULT_PATTERNS;
        }

        $clean = array_values(array_filter(array_map(fn ($p) => trim((string) $p), $raw), fn ($p) => $p !== ''));

        return $clean === [] ? self::DEFAULT_PATTERNS : $clean;
    }

    /** Nombres de categoría que cuentan como "Servicios". */
    public static function categoryNames(): array
    {
        $raw = Setting::get('services.detector_categories');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $raw);
        }

        if (!is_array($raw) || $raw === []) {
            return self::DEFAULT_CATEGORIES;
        }

        $clean = array_values(array_filter(array_map(fn ($p) => trim((string) $p), $raw), fn ($p) => $p !== ''));

        return $clean === [] ? self::DEFAULT_CATEGORIES : $clean;
    }

    /** minúsculas + sin acentos, para comparar. */
    public static function normalize(?string $text): string
    {
        return Str::lower(Str::ascii(trim((string) $text)));
    }

    /** ¿El nombre empieza con alguno de los patrones? */
    public static function nameMatches(?string $name, ?array $patterns = null): bool
    {
        $needle = self::normalize($name);
        if ($needle === '') {
            return false;
        }

        foreach ($patterns ?? self::patterns() as $pattern) {
            $p = self::normalize($pattern);
            if ($p === '') {
                continue;
            }
            // Empieza con el patrón y termina en límite de palabra (tolera plural: "Servicios").
            if (preg_match('/^' . preg_quote($p, '/') . '(s)?(?![a-z0-9])/', $needle)) {
                return true;
            }
        }

        return false;
    }

    /** IDs de las categorías "Servicios" y todas sus descendientes. */
    public static function serviceCategoryIds(): array
    {
        $names = array_map([self::class, 'normalize'], self::categoryNames());

        $roots = Category::query()->get(['id', 'name', 'parent_id'])
            ->filter(fn ($c) => in_array(self::normalize($c->name), $names, true));

        if ($roots->isEmpty()) {
            return [];
        }

        $all = Category::query()->get(['id', 'parent_id']);
        $ids = $roots->pluck('id')->all();

        do {
            $before = count($ids);
            foreach ($all as $c) {
                if ($c->parent_id && in_array($c->parent_id, $ids, true) && !in_array($c->id, $ids, true)) {
                    $ids[] = $c->id;
                }
            }
        } while (count($ids) > $before);

        return $ids;
    }

    /**
     * ¿Este producto (modelo o datos mínimos: name, category_id) parece servicio?
     */
    public static function isServiceProduct($product, ?array $categoryIds = null, ?array $patterns = null): bool
    {
        if (!$product) {
            return false;
        }

        $categoryIds ??= self::serviceCategoryIds();
        $categoryId = is_array($product) ? ($product['category_id'] ?? null) : $product->category_id;
        $name = is_array($product) ? ($product['name'] ?? '') : $product->name;

        if ($categoryId && in_array((int) $categoryId, $categoryIds, true)) {
            return true;
        }

        return self::nameMatches($name, $patterns);
    }

    /**
     * Para una línea de cotización: un producto-servicio es la que NO viene de
     * una página de servicio y cuyo producto (o, sin producto, su nombre) parece servicio.
     */
    public static function isServiceLine($item, ?array $categoryIds = null, ?array $patterns = null): bool
    {
        if (!empty($item->service_page_id)) {
            return false;
        }

        $categoryIds ??= self::serviceCategoryIds();

        if ($item->product_id) {
            $product = (method_exists($item, 'relationLoaded') && $item->relationLoaded('product'))
                ? $item->product
                : \App\Models\Products::query()->select('id', 'name', 'category_id')->find($item->product_id);
            if ($product && self::isServiceProduct($product, $categoryIds, $patterns)) {
                return true;
            }
        }

        return self::nameMatches($item->product_name, $patterns);
    }

    /**
     * Restringe una consulta de PRODUCTOS a los que parecen servicio.
     * $query: Eloquent/Query builder sobre `products` (o equivalente).
     */
    public static function scopeServiceProducts($query, string $nameCol = 'name', string $catCol = 'category_id'): void
    {
        $query->where(function ($q) use ($nameCol, $catCol) {
            self::applyMatch($q, $nameCol, $catCol);
        });
    }

    /** Excluye de una consulta de PRODUCTOS los que parecen servicio. */
    public static function scopeExcludeServiceProducts($query, string $nameCol = 'name', string $catCol = 'category_id'): void
    {
        $ids = self::serviceCategoryIds();

        if ($ids) {
            $query->where(function ($q) use ($catCol, $ids) {
                $q->whereNull($catCol)->orWhereNotIn($catCol, $ids);
            });
        }

        foreach (self::likePatterns() as $like) {
            $query->where($nameCol, 'not like', $like);
        }
    }

    /**
     * Restringe una consulta de LÍNEAS de cotización (quote_items) a las
     * líneas de producto-servicio (sin service_page_id).
     */
    public static function scopeServiceLines($query): void
    {
        $query->whereNull('quote_items.service_page_id')
            ->where(function ($q) {
                $ids = self::serviceCategoryIds();
                if ($ids) {
                    $q->orWhereIn('quote_items.product_id', function ($sub) use ($ids) {
                        $sub->select('id')->from('products')->whereIn('category_id', $ids);
                    });
                }
                foreach (self::likePatterns() as $like) {
                    $q->orWhere('quote_items.product_name', 'like', $like);
                }
                // Producto cuyo nombre actual coincide aunque la línea guarde otro nombre.
                $q->orWhereIn('quote_items.product_id', function ($sub) {
                    $sub->select('id')->from('products')->where(function ($w) {
                        foreach (self::likePatterns() as $like) {
                            $w->orWhere('name', 'like', $like);
                        }
                    });
                });
            });
    }

    /** Patrones "LIKE" de inicio de nombre, escapando comodines. */
    private static function likePatterns(): array
    {
        return array_map(
            fn ($p) => str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $p) . '%',
            self::patterns()
        );
    }

    private static function applyMatch($q, string $nameCol, string $catCol): void
    {
        $ids = self::serviceCategoryIds();
        if ($ids) {
            $q->orWhereIn($catCol, $ids);
        }
        foreach (self::likePatterns() as $like) {
            $q->orWhere($nameCol, 'like', $like);
        }
        if (!$ids && !self::likePatterns()) {
            $q->whereRaw('1 = 0');
        }
    }
}
