<?php

namespace App\Services\Catalog;

use App\Models\Products;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Etiquetas de la tarjeta de producto.
 *
 * for($product) devuelve como máximo 2 elementos, en orden de prioridad
 * best_seller → discount → last_units → new, cada uno:
 *   ['key' => string, 'label' => string, 'bg' => '#RRGGBB', 'fg' => '#RRGGBB']
 * (fg se elige por contraste WCAG). Respeta catalog.badge_enabled_* y los
 * umbrales de CatalogSettings.
 *
 * Es barato por tarjeta: el set de ids "más vendidos" se calcula UNA vez (dos
 * consultas ligeras, sin cargar modelos), se cachea con la clave
 * 'catalog.badges.' . CatalogCache::version() y se memoiza por request.
 */
class BadgeResolver
{
    public const MAX_BADGES = 2;

    /** Prioridad de las etiquetas. */
    private const ORDER = ['best_seller', 'discount', 'last_units', 'new'];

    /** @var array{version:string, ids:array<int,true>}|null */
    private static ?array $memo = null;

    /** @return array<int, array{key:string,label:string,bg:string,fg:string}> */
    public static function for(Products $product): array
    {
        $badges = [];

        foreach (self::ORDER as $key) {
            if (count($badges) >= self::MAX_BADGES) {
                break;
            }

            if (! self::enabled($key)) {
                continue;
            }

            $label = match ($key) {
                'best_seller' => self::qualifiesBestSeller($product) ? 'Más vendido' : null,
                'discount'    => self::discountLabel($product),
                'last_units'  => self::qualifiesLastUnits($product) ? 'Últimas piezas' : null,
                'new'         => self::qualifiesNew($product) ? 'Nuevo' : null,
            };

            if ($label === null) {
                continue;
            }

            $bg = self::color($key);

            $badges[] = [
                'key'   => $key,
                'label' => $label,
                'bg'    => $bg,
                'fg'    => self::foregroundFor($bg),
            ];
        }

        return $badges;
    }

    /**
     * Ids de productos con la etiqueta "Más vendido" (top % por categoría
     * directa, con mínimos). Devuelve una lista de ids enteros.
     *
     * @return array<int, int>
     */
    public static function bestSellerIds(): array
    {
        return array_keys(self::bestSellerSet());
    }

    /** Solo para pruebas: olvida el set memoizado en este proceso. */
    public static function flushMemo(): void
    {
        self::$memo = null;
    }

    // ── Reglas ─────────────────────────────────────────────────────────────

    private static function qualifiesBestSeller(Products $product): bool
    {
        return isset(self::bestSellerSet()[(int) $product->id]);
    }

    private static function discountLabel(Products $product): ?string
    {
        $compare = $product->compare_base_price;
        $base = $product->base_price;

        if (! $compare || $compare <= 0 || $compare <= $base) {
            return null;
        }

        $percent = (int) round((1 - ($base / $compare)) * 100);
        $min = max(1, (int) CatalogSettings::get('discount_min_percent'));

        return $percent >= $min ? "-{$percent}%" : null;
    }

    private static function qualifiesLastUnits(Products $product): bool
    {
        if (($product->availability ?? 'available') !== 'available') {
            return false;
        }

        $stock = (int) $product->stock;

        return $stock >= 1 && $stock <= (int) CatalogSettings::get('last_units_threshold');
    }

    private static function qualifiesNew(Products $product): bool
    {
        if ($product->is_new) {
            return true;
        }

        $days = (int) CatalogSettings::get('new_days');

        return $days > 0
            && $product->created_at !== null
            && $product->created_at->greaterThan(now()->subDays($days));
    }

    private static function enabled(string $key): bool
    {
        return (bool) CatalogSettings::get('badge_enabled_' . $key);
    }

    // ── Más vendidos ───────────────────────────────────────────────────────

    /** @return array<int, true> */
    private static function bestSellerSet(): array
    {
        $version = CatalogCache::version();

        if (self::$memo !== null && self::$memo['version'] === $version) {
            return self::$memo['ids'];
        }

        $ttl = (int) CatalogSettings::get('cache_ttl_minutes');
        $ids = Cache::remember(
            'catalog.badges.' . $version,
            now()->addMinutes($ttl > 0 ? $ttl : 10),
            fn () => self::computeBestSellerIds()
        );

        $set = array_fill_keys(array_map('intval', (array) $ids), true);
        self::$memo = ['version' => $version, 'ids' => $set];

        return $set;
    }

    /**
     * Calcula el set desde product_sales_stats y los productos publicados.
     * Por categoría directa: N = publicados, k = max(1, ceil(N × top%)),
     * solo califican units >= min_units y solo si la categoría tiene al menos
     * best_seller_min_products productos con ventas; gana el top k.
     *
     * @return array<int, int>
     */
    private static function computeBestSellerIds(): array
    {
        $minUnits = max(1, (int) CatalogSettings::get('best_seller_min_units'));
        $minProducts = max(1, (int) CatalogSettings::get('best_seller_min_products'));
        $topPercent = (float) CatalogSettings::get('best_seller_top_percent');

        if ($topPercent <= 0) {
            return [];
        }

        $published = fn () => DB::table('products')
            ->where('products.is_active', true)
            ->where('products.publish_on_website', true)
            ->whereNotNull('products.category_id');

        $totals = $published()
            ->selectRaw('products.category_id, COUNT(*) as total')
            ->groupBy('products.category_id')
            ->pluck('total', 'category_id');

        $sold = $published()
            ->join('product_sales_stats', 'product_sales_stats.product_id', '=', 'products.id')
            ->where('product_sales_stats.units', '>', 0)
            ->select('products.id', 'products.category_id', 'product_sales_stats.units')
            ->get()
            ->groupBy('category_id');

        $winners = [];

        foreach ($sold as $categoryId => $rows) {
            if ($rows->count() < $minProducts) {
                continue;
            }

            $k = max(1, (int) ceil(((int) ($totals[$categoryId] ?? 0)) * $topPercent / 100));

            $top = $rows
                ->filter(fn ($row) => (int) $row->units >= $minUnits)
                ->sort(fn ($a, $b) => [(int) $b->units, (int) $a->id] <=> [(int) $a->units, (int) $b->id])
                ->take($k);

            foreach ($top as $row) {
                $winners[] = (int) $row->id;
            }
        }

        return $winners;
    }

    // ── Colores ────────────────────────────────────────────────────────────

    private static function color(string $key): string
    {
        $default = (string) CatalogSettings::DEFAULTS['badge_color_' . $key];

        return self::normalizeHex(CatalogSettings::get('badge_color_' . $key)) ?? $default;
    }

    /** Devuelve '#RRGGBB' en mayúsculas o null si no es un hex válido (#RGB o #RRGGBB). */
    private static function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $hex = ltrim(trim($value), '#');

        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return preg_match('/^[0-9a-fA-F]{6}$/', $hex) ? '#' . strtoupper($hex) : null;
    }

    /** Blanco o negro, el que dé mayor ratio de contraste WCAG contra $bg. */
    private static function foregroundFor(string $bg): string
    {
        $luminance = self::relativeLuminance($bg);
        $whiteRatio = 1.05 / ($luminance + 0.05);
        $blackRatio = ($luminance + 0.05) / 0.05;

        return $whiteRatio >= $blackRatio ? '#FFFFFF' : '#000000';
    }

    private static function relativeLuminance(string $hex): float
    {
        $channels = array_map(function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
