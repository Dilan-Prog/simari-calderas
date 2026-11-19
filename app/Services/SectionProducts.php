<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Collection as ProductCollection;
use App\Models\Products;
use App\Models\ProductSalesStat;
use Illuminate\Support\Collection;

/**
 * Resuelve los productos de un bloque de secciones (carruseles del Home,
 * producto, colección y categoría, y la cuadrícula `product_grid`).
 *
 * Antes esta lógica vivía copiada dentro de product-carousel(.blade/-banner);
 * se extrajo aquí SIN cambiar su comportamiento para esos carruseles:
 *  - fuentes: featured (default) | new | recommended | category (con hijas vía
 *    idsWithChildren) | brand | collection | manual | tag | related_category |
 *    related_brand | best_sellers (nuevo).
 *  - solo productos activos y publicados en la web.
 *  - orden por defecto: más nuevos primero (created_at desc).
 *
 * Novedades:
 *  - source=category sin category_id usa la categoría de la página ($context),
 *    con sus hijas: una plantilla heredada muestra los productos correctos en
 *    cada subcategoría.
 *  - source=best_sellers: productos con ventas (product_sales_stats.units > 0),
 *    más vendidos primero.
 *  - config['sort']: newest (default) | price_asc | price_desc | best_selling.
 *    Si no viene, cada fuente conserva su orden de siempre.
 */
class SectionProducts
{
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'best_selling'];

    /**
     * @return Collection<int, Products>
     */
    public static function resolve(array $config, ?Category $context, int $limit, ?Products $currentProduct = null): Collection
    {
        return self::resolveDetailed($config, $context, $limit, $currentProduct)['products'];
    }

    /**
     * Igual que resolve(), pero también devuelve la colección fuente (para el
     * enlace "Ver todo" de los carruseles con source=collection).
     *
     * @return array{products: Collection<int, Products>, source_collection: ?ProductCollection}
     */
    public static function resolveDetailed(array $config, ?Category $context, int $limit, ?Products $currentProduct = null): array
    {
        $source = $config['source'] ?? 'featured';
        $limit = max(1, $limit);
        $sort = in_array($config['sort'] ?? null, self::SORTS, true) ? $config['sort'] : null;
        // Por defecto el producto que se está viendo no aparece en sus propios
        // bloques (en cualquier fuente).
        $excludeCurrent = $currentProduct && ($config['exclude_current'] ?? true);
        $with = ['images' => fn ($q) => $q->orderBy('sort_order'), 'brand', 'category'];

        // Sin collection_id la fuente "collection" cae al match de abajo
        // (default => destacados), igual que el carrusel original.
        if ($source === 'collection' && !empty($config['collection_id'])) {
            $sourceCollection = ProductCollection::with('rules')->find($config['collection_id']);

            if (!$sourceCollection) {
                return ['products' => collect(), 'source_collection' => null];
            }

            $query = $sourceCollection->productsQuery()->with($with);
            if ($sort) {
                $query->reorder();
                self::applySort($query, $sort);
            }
            $products = $query->limit($excludeCurrent ? $limit + 1 : $limit)->get();
            if ($excludeCurrent) {
                $products = $products->reject(fn ($p) => $p->id === $currentProduct->id)->take($limit)->values();
            }

            return ['products' => $products, 'source_collection' => $sourceCollection];
        }

        $query = Products::query()
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->with($with);

        // Mismo alcance que /catalogo/{slug}: la categoría elegida con TODAS
        // sus descendientes (Category::idsWithChildren()).
        $categoryIds = [0];
        if ($source === 'category') {
            $category = !empty($config['category_id']) ? Category::find($config['category_id']) : $context;
            $categoryIds = $category?->idsWithChildren() ?: [0];
        } elseif ($source === 'related_category') {
            $categoryIds = ($currentProduct ? Category::find($currentProduct->category_id)?->idsWithChildren() : null) ?: [0];
        }

        $tag = trim((string) ($config['tag'] ?? ''));

        match ($source) {
            'category'    => $query->whereIn('category_id', $categoryIds),
            'brand'       => $query->where('brand_id', $config['brand_id'] ?? 0),
            'new'         => $query->where('is_new', true),
            'recommended' => $query->where('is_recommended', true),
            'manual'      => $query->whereIn('id', array_filter((array) ($config['product_ids'] ?? []))),
            // Etiqueta (products.tags, JSON array de strings); sin etiqueta
            // configurada no resuelve nada.
            'tag'         => $tag !== '' ? $query->whereJsonContains('tags', $tag) : $query->whereRaw('1 = 0'),
            'best_sellers' => $query->whereIn('id', ProductSalesStat::query()->select('product_id')->where('units', '>', 0)),
            // Fuentes relativas al producto en cuyo contexto se renderiza la
            // sección (solo página de producto; fuera de ella degradan a vacío).
            'related_category' => $query->whereIn('category_id', $categoryIds),
            'related_brand'    => $query->where('brand_id', $currentProduct ? ($currentProduct->brand_id ?? 0) : 0),
            default       => $query->where('is_featured', true),
        };

        // Las fuentes relativas siempre excluyen al producto actual; en el
        // resto depende de config['exclude_current'] (default true).
        if ($currentProduct && ($excludeCurrent || in_array($source, ['related_category', 'related_brand'], true))) {
            $query->where('id', '!=', $currentProduct->id);
        }

        self::applySort($query, $sort ?? ($source === 'best_sellers' ? 'best_selling' : 'newest'));

        return ['products' => $query->take($limit)->get(), 'source_collection' => null];
    }

    /** Aplica el orden pedido; siempre desempata por más nuevo e id. */
    private static function applySort($query, string $sort): void
    {
        $table = 'products';

        match ($sort) {
            'price_asc'    => $query->orderBy("$table.base_price"),
            'price_desc'   => $query->orderByDesc("$table.base_price"),
            // Subconsulta (no join) para no chocar con el pivote de las
            // colecciones manuales; sin fila de ventas = 0 (NULL queda al final).
            'best_selling' => $query->orderByDesc(
                ProductSalesStat::query()->select('units')->whereColumn('product_sales_stats.product_id', "$table.id")->limit(1)
            ),
            default        => null,
        };

        $query->orderByDesc("$table.created_at");
        if ($sort !== 'newest') {
            $query->orderByDesc("$table.id");
        }
    }
}
