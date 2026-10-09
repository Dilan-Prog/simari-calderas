<?php

namespace App\Services\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryFilterGroup;
use App\Models\ProductSalesStat;
use App\Models\Products;
use Illuminate\Support\Facades\Cache;

/**
 * Filas candidatas del catálogo en caché.
 *
 * get() devuelve:
 * [
 *   'v'      => string  (CatalogCache::version() con la que se construyó),
 *   'rows'   => [ [ 'id'=>int, 'c'=>?int (category_id), 'b'=>?int (brand_id),
 *                   'p'=>float (base_price MXN sin IVA), 'cp'=>?float (compare_base),
 *                   'd'=>?int (% descuento, solo si compare>base),
 *                   'st'=>bool (en stock), 'f24'=>bool (available y stock>0),
 *                   'fg'=>bool (envío gratis: shippingInfo cost<=0),
 *                   'ls'=>bool (últimas piezas), 'nw'=>bool (nuevo),
 *                   'ts'=>int (created_at unix), 'feat'=>bool, 'u'=>int (unidades vendidas),
 *                   'o'=>int[] (ids de opciones de filtro técnico que cumple) ], ... ],
 *   'cats'   => [ id => ['id','parent_id','name','slug','sort_order','is_active'] ],
 *   'groups' => [ groupId => ['id','category_id','name','sort_order','options'=>[ ['id','label','tag_normalized'] ]] ],
 *   'brands' => [ id => ['id','name'] ],
 * ]
 * Solo productos publicados (is_active y publish_on_website). Precio/envío/
 * disponibilidad salen de los accessors de Products (fuente única de verdad).
 * 'cats' incluye TODAS las categorías (también inactivas, el alcance por
 * subárbol las necesita); 'groups' solo grupos activos con al menos una opción
 * activa; 'brands' solo marcas activas.
 *
 * Una sola caché global (clave 'catalog.index'), invalidada al cambiar
 * CatalogCache::version() y por TTL (catalog.cache_ttl_minutes); la
 * reconstrucción se protege con Cache::lock para evitar estampidas.
 */
class CatalogIndex
{
    private const KEY = 'catalog.index';
    private const LOCK = 'catalog.build';

    /** Memo por request/proceso (se invalida solo si cambia la versión). */
    private static ?array $memo = null;

    public static function get(): array
    {
        $version = CatalogCache::version();

        if (self::$memo !== null && self::$memo['v'] === $version) {
            return self::$memo;
        }

        $cached = Cache::get(self::KEY);
        if (self::isFresh($cached, $version)) {
            return self::$memo = $cached;
        }

        $lock = null;
        $acquired = false;
        try {
            $lock = Cache::lock(self::LOCK, 30);
            $lock->block(5);
            $acquired = true;
        } catch (\Throwable $e) {
            // Sin soporte de locks o timeout: se reconstruye igual (solo cuesta CPU).
        }

        try {
            if ($acquired) {
                // Otro proceso pudo terminar la reconstrucción mientras esperábamos.
                $cached = Cache::get(self::KEY);
                if (self::isFresh($cached, $version)) {
                    return self::$memo = $cached;
                }
            }

            $payload = self::build($version);
            $ttl = max(1, (int) CatalogSettings::get('cache_ttl_minutes'));
            Cache::put(self::KEY, $payload, now()->addMinutes($ttl));
        } finally {
            if ($acquired) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // el lock expira solo
                }
            }
        }

        return self::$memo = $payload;
    }

    /** Solo para pruebas: olvida el memo de este proceso. */
    public static function flushMemo(): void
    {
        self::$memo = null;
    }

    /**
     * Ids de la categoría + TODOS sus descendientes (incluye inactivos: misma
     * semántica que Category::idsWithChildren()), como set [id => id].
     */
    public static function subtreeIds(array $index, int $categoryId): array
    {
        $children = [];
        foreach ($index['cats'] as $cat) {
            if ($cat['parent_id'] !== null) {
                $children[$cat['parent_id']][] = $cat['id'];
            }
        }

        $set = [];
        $stack = [$categoryId];
        while ($stack) {
            $id = array_pop($stack);
            if (isset($set[$id])) {
                continue;
            }
            $set[$id] = $id;
            foreach ($children[$id] ?? [] as $childId) {
                $stack[] = $childId;
            }
        }

        return $set;
    }

    /** Ancestros de la categoría, del padre inmediato hacia la raíz. */
    public static function ancestorIds(array $index, int $categoryId): array
    {
        $out = [];
        $seen = [$categoryId => true];
        $current = $index['cats'][$categoryId]['parent_id'] ?? null;
        while ($current !== null && !isset($seen[$current]) && isset($index['cats'][$current])) {
            $out[] = $current;
            $seen[$current] = true;
            $current = $index['cats'][$current]['parent_id'];
        }

        return $out;
    }

    /**
     * Grupos de filtro técnico VIGENTES para la categoría de la ruta: los
     * definidos en ella y en sus ancestros (no en hijas, hermanas ni en
     * /catalogo). Ordenados de la raíz hacia la categoría y luego por
     * sort_order. Devuelve [groupId => group].
     */
    public static function scopeGroups(array $index, ?int $categoryId): array
    {
        if ($categoryId === null) {
            return [];
        }

        $chain = array_merge(array_reverse(self::ancestorIds($index, $categoryId)), [$categoryId]);

        $out = [];
        foreach ($chain as $catId) {
            foreach ($index['groups'] as $group) {
                if ($group['category_id'] === $catId) {
                    $out[$group['id']] = $group;
                }
            }
        }

        return $out;
    }

    private static function isFresh(mixed $cached, string $version): bool
    {
        return is_array($cached)
            && ($cached['v'] ?? null) === $version
            && isset($cached['rows'], $cached['cats'], $cached['groups'], $cached['brands']);
    }

    private static function build(string $version): array
    {
        // ── Filtros técnicos: solo grupos activos con opciones activas ───────
        $groups = [];
        $tagToOptions = [];
        $groupModels = CategoryFilterGroup::query()
            ->where('is_active', true)
            ->with(['options' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($groupModels as $group) {
            if ($group->options->isEmpty()) {
                continue;
            }
            $groups[$group->id] = [
                'id'          => (int) $group->id,
                'category_id' => (int) $group->category_id,
                'name'        => (string) $group->name,
                'sort_order'  => (int) $group->sort_order,
                'options'     => $group->options->map(fn ($o) => [
                    'id'             => (int) $o->id,
                    'label'          => (string) $o->label,
                    'tag_normalized' => (string) $o->tag_normalized,
                ])->all(),
            ];
            foreach ($group->options as $option) {
                $tagToOptions[$option->tag_normalized][] = (int) $option->id;
            }
        }

        // ── Ventas (sin fila = 0) ─────────────────────────────────────────────
        $sales = ProductSalesStat::query()->pluck('units', 'product_id')->all();

        $lastUnits = (int) CatalogSettings::get('last_units_threshold');
        $newDays = (int) CatalogSettings::get('new_days');
        $newSince = $newDays > 0 ? now()->subDays($newDays)->getTimestamp() : null;

        // ── Filas ────────────────────────────────────────────────────────────
        $rows = [];
        Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->select([
                'id', 'category_id', 'brand_id', 'price', 'compare_price', 'price_includes_tax',
                'currency', 'shipping_cost', 'free_shipping_threshold', 'stock', 'availability',
                'is_featured', 'is_new', 'created_at', 'tags',
            ])
            ->chunkById(500, function ($chunk) use (&$rows, $tagToOptions, $sales, $lastUnits, $newSince) {
                foreach ($chunk as $product) {
                    $base = (float) $product->base_price;
                    $compare = $product->compare_base_price;
                    $discount = null;
                    if ($compare !== null && $compare > $base && $compare > 0) {
                        $pct = (int) round(($compare - $base) / $compare * 100);
                        $discount = $pct >= 1 ? $pct : null;
                    }

                    $stock = (int) $product->stock;
                    $timestamp = $product->created_at?->getTimestamp() ?? 0;

                    $options = [];
                    if ($tagToOptions) {
                        foreach (TagNormalizer::normalizeMany($product->tags) as $tag) {
                            foreach ($tagToOptions[$tag] ?? [] as $optionId) {
                                $options[$optionId] = $optionId;
                            }
                        }
                    }

                    $rows[] = [
                        'id'   => (int) $product->id,
                        'c'    => $product->category_id !== null ? (int) $product->category_id : null,
                        'b'    => $product->brand_id !== null ? (int) $product->brand_id : null,
                        'p'    => $base,
                        'cp'   => $compare,
                        'd'    => $discount,
                        'st'   => $stock > 0 && $product->is_purchasable,
                        'f24'  => $stock > 0 && $product->availability === 'available',
                        'fg'   => $product->shippingInfo()['cost'] <= 0,
                        'ls'   => $product->availability === 'available' && $stock >= 1 && $stock <= $lastUnits,
                        'nw'   => (bool) $product->is_new || ($newSince !== null && $timestamp >= $newSince),
                        'ts'   => $timestamp,
                        'feat' => (bool) $product->is_featured,
                        'u'    => (int) ($sales[$product->id] ?? 0),
                        'o'    => array_values($options),
                    ];
                }
            });

        // ── Árbol de categorías y marcas ─────────────────────────────────────
        $cats = [];
        foreach (Category::query()->get(['id', 'parent_id', 'name', 'slug', 'sort_order', 'is_active']) as $cat) {
            $cats[(int) $cat->id] = [
                'id'         => (int) $cat->id,
                'parent_id'  => $cat->parent_id !== null ? (int) $cat->parent_id : null,
                'name'       => (string) $cat->name,
                'slug'       => (string) $cat->slug,
                'sort_order' => (int) $cat->sort_order,
                'is_active'  => (bool) $cat->is_active,
            ];
        }

        $brands = [];
        foreach (Brand::query()->where('is_active', true)->get(['id', 'name']) as $brand) {
            $brands[(int) $brand->id] = ['id' => (int) $brand->id, 'name' => (string) $brand->name];
        }

        return [
            'v'      => $version,
            'rows'   => $rows,
            'cats'   => $cats,
            'groups' => $groups,
            'brands' => $brands,
        ];
    }
}
