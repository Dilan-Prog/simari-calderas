<?php

namespace App\Services\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Filtros del listado de Productos y del editor en lote (misma lógica para
 * ambas pantallas, vía ProductController::filteredProductsQuery()).
 *
 * Todos los parámetros son opcionales y se ignoran si vienen vacíos o con un
 * valor fuera de la lista permitida.
 */
class ProductFilters
{
    /** Banderas tri-estado: parámetro => [columna, etiqueta]. Valores '1' / '0'. */
    public const FLAGS = [
        'is_featured'             => ['is_featured', 'Destacado'],
        'is_new'                  => ['is_new', 'Nuevo'],
        'is_recommended'          => ['is_recommended', 'Recomendado'],
        'publish_on_website'      => ['publish_on_website', 'Publicado en web'],
        'accepts_msi'             => ['accepts_msi', 'Acepta MSI'],
        'show_in_merchant_center' => ['show_in_merchant_center', 'En Merchant Center'],
    ];

    /** "Datos faltantes": valor => etiqueta. */
    public const MISSING = [
        'image'       => 'Sin imagen',
        'sku'         => 'Sin SKU',
        'model'       => 'Sin modelo',
        'brand'       => 'Sin marca',
        'description' => 'Sin descripción',
        'seo'         => 'Sin SEO (título o descripción)',
        'tags'        => 'Sin etiquetas',
        'cost'        => 'Sin costo',
        'supplier'    => 'Sin proveedor',
    ];

    public const RANGES = [
        'price_min' => ['price', '>=', 'Precio desde'],
        'price_max' => ['price', '<=', 'Precio hasta'],
        'cost_min'  => ['cost', '>=', 'Costo desde'],
        'cost_max'  => ['cost', '<=', 'Costo hasta'],
        'stock_min' => ['stock', '>=', 'Stock desde'],
        'stock_max' => ['stock', '<=', 'Stock hasta'],
    ];

    public const DATES = [
        'created_from' => ['created_at', '>=', 'Creado desde'],
        'created_to'   => ['created_at', '<=', 'Creado hasta'],
        'updated_from' => ['updated_at', '>=', 'Actualizado desde'],
        'updated_to'   => ['updated_at', '<=', 'Actualizado hasta'],
    ];

    public const AVAILABILITY = ['available' => 'Disponible', 'out_of_stock' => 'Sin existencias', 'on_order' => 'Sobre pedido'];

    /**
     * "Ordenar por" del listado (?sort=…): valor => etiqueta. '' (o un valor
     * desconocido) = orden predeterminado de siempre (id ascendente).
     */
    public const SORTS = [
        ''              => 'Predeterminado',
        'newest'        => 'Más recientes',
        'oldest'        => 'Más antiguos',
        'price_asc'     => 'Precio: menor a mayor',
        'price_desc'    => 'Precio: mayor a menor',
        'best_selling'  => 'Más vendidos',
        'least_selling' => 'Menos vendidos',
    ];

    /**
     * Estatus de store_orders que cuentan como venta pagada (ver
     * App\Support\StoreOrderStatus::FLOW: todo lo posterior a pendiente_pago;
     * pago_parcial y cancelado no cuentan).
     */
    public const SOLD_STORE_STATUSES = ['pagado', 'en_preparacion', 'enviado', 'entregado'];

    public static function apply(Builder $query, Request $request): Builder
    {
        // Búsqueda por palabras: cada palabra debe aparecer en el nombre, SKU, modelo, SKU de
        // proveedor o marca (en cualquier orden), así "bomba grundfos" encuentra una bomba de
        // esa marca aunque el nombre no diga "grundfos". La colación utf8mb4_unicode_ci ya ignora
        // mayúsculas y acentos; los espacios sobrantes se descartan.
        foreach (self::searchTerms($request->input('search')) as $term) {
            $like = '%' . addcslashes($term, '\%_') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('supplier_sku', 'like', $like)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                    ->orWhereHas('suppliers', fn ($s) => $s->where('suppliers_products.sku', 'like', $like));
            });
        }

        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        match ($request->input('stock')) {
            'out_of_stock' => $query->where('stock', '<=', 0),
            'in_stock'     => $query->where('stock', '>', 0),
            // Stock bajo: con existencias pero en o por debajo del punto de reorden.
            'low'          => $query->where('stock', '>', 0)->whereNotNull('reorder_point')->whereColumn('stock', '<=', 'reorder_point'),
            default        => null,
        };

        if ($categoryId = (int) $request->input('category_id')) {
            $query->whereIn('category_id', self::categoryWithDescendants($categoryId));
        }

        if ($brandIds = self::ids($request->input('brand_id'))) {
            $query->whereIn('brand_id', $brandIds);
        }

        if ($supplierId = (int) $request->input('supplier_id')) {
            $query->whereHas('suppliers', fn ($s) => $s->where('suppliers.id', $supplierId));
        }

        if (in_array($request->input('currency'), ['MXN', 'USD'], true)) {
            $query->where('currency', $request->input('currency'));
        }

        $availability = $request->input('availability');
        if (is_string($availability) && isset(self::AVAILABILITY[$availability])) {
            $query->where('availability', $availability);
        }

        foreach (self::RANGES as $param => [$col, $op]) {
            $v = $request->input($param);
            if (is_scalar($v) && $v !== '' && is_numeric(str_replace(',', '', (string) $v))) {
                $query->where($col, $op, (float) str_replace(',', '', (string) $v));
            }
        }

        foreach (self::DATES as $param => [$col, $op]) {
            $v = $request->input($param);
            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                $op === '>='
                    ? $query->where($col, '>=', $v . ' 00:00:00')
                    : $query->where($col, '<=', $v . ' 23:59:59');
            }
        }

        foreach (self::FLAGS as $param => [$col]) {
            $v = $request->input($param);
            if ($v === '1' || $v === '0') {
                $query->where($col, $v === '1');
            }
        }

        $tag = $request->input('tag');
        if (is_string($tag) && trim($tag) !== '') {
            $query->whereJsonContains('tags', trim($tag));
        }

        foreach (array_intersect(array_filter((array) $request->input('missing'), 'is_string'), array_keys(self::MISSING)) as $m) {
            self::applyMissing($query, $m);
        }

        return $query;
    }

    /** Valor de ?sort= válido, o '' (predeterminado). */
    public static function sortKey(Request $request): string
    {
        $sort = $request->input('sort');

        return is_string($sort) && $sort !== '' && isset(self::SORTS[$sort]) ? $sort : '';
    }

    /**
     * Aplica el "Ordenar por" (siempre con id como desempate estable, para que
     * la paginación no repita ni salte productos). Recibe el Request o
     * directamente el valor de ?sort= (lo usa ProductsExport). Las columnas se califican
     * con products. porque "vendidos" agrega un LEFT JOIN.
     */
    public static function applySort(Builder $query, Request|string|null $sort): Builder
    {
        $sort = $sort instanceof Request ? self::sortKey($sort) : (isset(self::SORTS[(string) $sort]) ? (string) $sort : '');

        if ($sort === 'best_selling' || $sort === 'least_selling') {
            self::withUnitsSold($query);
            $query->orderBy('units_sold', $sort === 'best_selling' ? 'desc' : 'asc');
        }

        match ($sort) {
            'newest'     => $query->orderBy('products.created_at', 'desc')->orderBy('products.id', 'desc'),
            'oldest'     => $query->orderBy('products.created_at', 'asc')->orderBy('products.id', 'asc'),
            'price_asc'  => $query->orderBy('products.price', 'asc')->orderBy('products.id', 'asc'),
            'price_desc' => $query->orderBy('products.price', 'desc')->orderBy('products.id', 'asc'),
            default      => $query->orderBy('products.id', 'asc'),
        };

        return $query;
    }

    /**
     * Agrega la columna units_sold (COALESCE(…, 0)) con un único LEFT JOIN a
     * la suma agregada de unidades vendidas — sin N+1.
     */
    public static function withUnitsSold(Builder $query): Builder
    {
        if (empty($query->getQuery()->columns)) {
            $query->select('products.*');
        }

        return $query
            ->leftJoinSub(self::unitsSoldQuery(), 'product_sales', 'product_sales.product_id', '=', 'products.id')
            ->addSelect(DB::raw('COALESCE(product_sales.units_sold, 0) as units_sold'));
    }

    /**
     * "Unidades vendidas" por producto (product_id, units_sold):
     *   - cantidades en cotizaciones ACEPTADAS (misma métrica que la insignia
     *     "+N unidades vendidas" de la ficha, App\Support\QuoteSales), más
     *   - cantidades en pedidos de la tienda en línea pagados
     *     (store_orders.status en SOLD_STORE_STATUSES).
     */
    public static function unitsSoldQuery(): QueryBuilder
    {
        $quotes = DB::table('quote_items')
            ->join('quotes', 'quotes.id', '=', 'quote_items.quote_id')
            ->where('quotes.status', \App\Support\QuoteSales::STATUS)
            ->whereNotNull('quote_items.product_id')
            ->select('quote_items.product_id', 'quote_items.quantity');

        $store = DB::table('store_order_items')
            ->join('store_orders', 'store_orders.id', '=', 'store_order_items.store_order_id')
            ->whereIn('store_orders.status', self::SOLD_STORE_STATUSES)
            ->select('store_order_items.product_id', 'store_order_items.quantity');

        return DB::query()
            ->fromSub($quotes->unionAll($store), 'sold_lines')
            ->groupBy('sold_lines.product_id')
            ->selectRaw('sold_lines.product_id as product_id, SUM(sold_lines.quantity) as units_sold');
    }

    /**
     * Unidades vendidas solo de estos productos (una consulta).
     *
     * @param  int[]  $ids
     * @return array<int,int>
     */
    public static function unitsSoldFor(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return DB::query()->fromSub(self::unitsSoldQuery(), 's')
            ->whereIn('s.product_id', $ids)
            ->pluck('s.units_sold', 's.product_id')
            ->map(fn ($u) => (int) round((float) $u))
            ->all();
    }

    /** @return string[] palabras de la búsqueda (máx. 8), sin espacios sobrantes. */
    public static function searchTerms(mixed $search): array
    {
        if (!is_scalar($search)) {
            return [];
        }
        $terms = preg_split('/\s+/u', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_slice($terms, 0, 8);
    }

    private static function applyMissing(Builder $q, string $m): void
    {
        $blank = fn (string $col) => $q->where(fn ($w) => $w->whereNull($col)->orWhere($col, ''));

        match ($m) {
            'image'       => $q->where(fn ($w) => $w->whereNull('cover_image_url')->orWhere('cover_image_url', ''))->whereDoesntHave('images'),
            'sku'         => $blank('sku'),
            'model'       => $blank('model'),
            'description' => $blank('description'),
            'cost'        => $q->where(fn ($w) => $w->whereNull('cost')->orWhere('cost', '<=', 0)),
            'brand'       => $q->whereNull('brand_id'),
            'supplier'    => $q->whereDoesntHave('suppliers'),
            'seo'         => $q->where(fn ($w) => $w->whereNull('seo_title')->orWhere('seo_title', '')
                ->orWhereNull('seo_description')->orWhere('seo_description', '')),
            'tags'        => $q->where(fn ($w) => $w->whereNull('tags')->orWhereJsonLength('tags', 0)),
            default       => null,
        };
    }

    /** @return int[] */
    private static function ids(mixed $v): array
    {
        return array_values(array_unique(array_filter(array_map('intval', array_filter((array) $v, 'is_scalar')))));
    }

    /** @return int[] la categoría y todas sus descendientes (hasta 6 niveles). */
    public static function categoryWithDescendants(int $id): array
    {
        $all = [$id];
        $frontier = [$id];
        for ($i = 0; $i < 6 && $frontier; $i++) {
            $frontier = Category::whereIn('parent_id', $frontier)->pluck('id')->all();
            $all = array_merge($all, $frontier);
        }

        return array_values(array_unique($all));
    }

    /** Filtros activos para los chips: [['param' => ..., 'value' => ..., 'label' => ...]]. */
    public static function activeChips(Request $request): array
    {
        $chips = [];
        $add = function (string $param, string $label, ?string $value = null) use (&$chips) {
            $chips[] = ['param' => $param, 'value' => $value, 'label' => $label];
        };

        if (($s = trim((string) $request->input('search', ''))) !== '') {
            $add('search', 'Búsqueda: ' . $s);
        }
        if ($request->input('status') === 'active') {
            $add('status', 'Activos');
        }
        if ($request->input('status') === 'inactive') {
            $add('status', 'Inactivos');
        }
        $stockLabels = ['in_stock' => 'En stock', 'out_of_stock' => 'Agotados', 'low' => 'Stock bajo'];
        $stockParam = $request->input('stock');
        if (is_string($stockParam) && isset($stockLabels[$stockParam])) {
            $add('stock', $stockLabels[$stockParam]);
        }
        if ($cid = (int) $request->input('category_id')) {
            $add('category_id', 'Categoría: ' . (Category::find($cid)?->name ?? $cid));
        }
        foreach (self::ids($request->input('brand_id')) as $bid) {
            $add('brand_id', 'Marca: ' . (Brand::find($bid)?->name ?? $bid), (string) $bid);
        }
        if ($sid = (int) $request->input('supplier_id')) {
            $add('supplier_id', 'Proveedor: ' . (Supplier::find($sid)?->company_name ?? $sid));
        }
        if (in_array($request->input('currency'), ['MXN', 'USD'], true)) {
            $add('currency', 'Moneda: ' . $request->input('currency'));
        }
        $av = $request->input('availability');
        if (is_string($av) && isset(self::AVAILABILITY[$av])) {
            $add('availability', self::AVAILABILITY[$av]);
        }
        foreach (self::RANGES as $p => [, , $label]) {
            $v = $request->input($p);
            if (is_scalar($v) && $v !== '' && is_numeric(str_replace(',', '', (string) $v))) {
                $add($p, $label . ' ' . $v);
            }
        }
        foreach (self::DATES as $p => [, , $label]) {
            $v = $request->input($p);
            if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                $add($p, $label . ' ' . $v);
            }
        }
        foreach (self::FLAGS as $p => [, $label]) {
            $v = $request->input($p);
            if ($v === '1' || $v === '0') {
                $add($p, ($v === '1' ? '' : 'No ') . $label);
            }
        }
        $t = $request->input('tag');
        if (is_string($t) && trim($t) !== '') {
            $add('tag', 'Etiqueta: ' . trim($t));
        }
        foreach (array_intersect(array_filter((array) $request->input('missing'), 'is_string'), array_keys(self::MISSING)) as $m) {
            $add('missing', self::MISSING[$m], $m);
        }

        return $chips;
    }
}
