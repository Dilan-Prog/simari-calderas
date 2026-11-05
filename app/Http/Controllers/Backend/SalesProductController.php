<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Products;
use Illuminate\Support\Str;

/**
 * Panel "Productos" del grupo Ventas: tabla de datos personalizada para el
 * equipo comercial. Independiente del CRUD de catálogo (admin.products.*,
 * grupo Ecommerce), pero lee los mismos productos reales.
 *
 * Cada columna se declara una sola vez en columns(): la vista arma de ahí el
 * menú "Columnas", los filtros y el orden. Para exponer otro campo del
 * producto basta con agregarlo a columns() y a rows().
 */
class SalesProductController extends Controller
{
    public function index()
    {
        $groups = $this->columnGroups();

        return view('admin.sales-products.index', [
            'columns' => array_map(fn (array $c) => $c + ['group' => $groups[$c['key']]], $this->columns()),
            'rows'    => $this->rows(),
        ]);
    }

    /**
     * Grupo de cada columna en el menú "Columnas" (mismos grupos que usa el
     * listado de Productos del catálogo para que se sienta igual).
     *
     * @return array<string, string>
     */
    private function columnGroups(): array
    {
        $groups = [
            'Identificación'      => ['sku', 'name', 'model', 'supplier_sku', 'brand', 'category', 'short_description', 'slug', 'created_at'],
            'Precios'             => ['price', 'compare_price', 'cost', 'currency', 'price_includes_tax', 'shipping_cost', 'free_shipping_threshold'],
            'Inventario y medidas' => ['stock', 'stock_unit', 'reorder_point', 'weight', 'height', 'width', 'length'],
            'Publicación'         => ['is_active', 'publish_on_website', 'is_featured', 'is_new', 'is_recommended', 'show_in_merchant_center', 'accepts_msi'],
        ];

        $map = [];
        foreach ($groups as $name => $keys) {
            foreach ($keys as $key) {
                $map[$key] = $name;
            }
        }

        return $map;
    }

    /**
     * type: text | list (valores repetidos, filtro por casillas) | num (rango)
     * fmt:  int | money | dec (solo num)
     * default: aparece al abrir la pantalla; el resto se activa en "Columnas"
     * sum: la fila de totales suma esta columna
     *
     * @return array<int, array{key: string, label: string, type: string, fmt?: string, default?: bool, sum?: bool}>
     */
    private function columns(): array
    {
        return [
            ['key' => 'sku',                     'label' => 'SKU',                    'type' => 'text', 'default' => true],
            ['key' => 'name',                    'label' => 'Producto',               'type' => 'text', 'default' => true],
            ['key' => 'model',                   'label' => 'Modelo',                 'type' => 'text'],
            ['key' => 'supplier_sku',            'label' => 'SKU proveedor',          'type' => 'text'],
            ['key' => 'brand',                   'label' => 'Marca',                  'type' => 'list', 'default' => true],
            ['key' => 'category',                'label' => 'Categoría',              'type' => 'list', 'default' => true],
            ['key' => 'short_description',       'label' => 'Descripción corta',      'type' => 'text'],
            ['key' => 'price',                   'label' => 'Precio',                 'type' => 'num', 'fmt' => 'money', 'default' => true],
            ['key' => 'compare_price',           'label' => 'Precio comparativo',     'type' => 'num', 'fmt' => 'money'],
            ['key' => 'cost',                    'label' => 'Costo',                  'type' => 'num', 'fmt' => 'money'],
            ['key' => 'currency',                'label' => 'Moneda',                 'type' => 'list'],
            ['key' => 'price_includes_tax',      'label' => '¿Precio incluye IVA?',   'type' => 'list'],
            ['key' => 'shipping_cost',           'label' => 'Costo de envío',         'type' => 'num', 'fmt' => 'money'],
            ['key' => 'free_shipping_threshold', 'label' => 'Envío gratis desde',     'type' => 'num', 'fmt' => 'money'],
            ['key' => 'stock',                   'label' => 'Stock',                  'type' => 'num', 'fmt' => 'int', 'default' => true, 'sum' => true],
            ['key' => 'stock_unit',              'label' => 'Unidad de stock',        'type' => 'list'],
            ['key' => 'reorder_point',           'label' => 'Punto de reorden',       'type' => 'num', 'fmt' => 'int'],
            ['key' => 'weight',                  'label' => 'Peso',                   'type' => 'num', 'fmt' => 'dec'],
            ['key' => 'height',                  'label' => 'Alto',                   'type' => 'num', 'fmt' => 'dec'],
            ['key' => 'width',                   'label' => 'Ancho',                  'type' => 'num', 'fmt' => 'dec'],
            ['key' => 'length',                  'label' => 'Largo',                  'type' => 'num', 'fmt' => 'dec'],
            ['key' => 'is_active',               'label' => 'Activo',                 'type' => 'list'],
            ['key' => 'publish_on_website',      'label' => 'Publicado en web',       'type' => 'list'],
            ['key' => 'is_featured',             'label' => 'Destacado',              'type' => 'list'],
            ['key' => 'is_new',                  'label' => 'Nuevo',                  'type' => 'list'],
            ['key' => 'is_recommended',          'label' => 'Recomendado',            'type' => 'list'],
            ['key' => 'show_in_merchant_center', 'label' => 'Merchant Center',        'type' => 'list'],
            ['key' => 'accepts_msi',             'label' => 'Acepta MSI',             'type' => 'list'],
            ['key' => 'slug',                    'label' => 'Slug',                   'type' => 'text'],
            ['key' => 'created_at',              'label' => 'Creado',                 'type' => 'text'],
        ];
    }

    /**
     * Una fila plana por producto, con las mismas claves de columns().
     * Las columnas tipo "list" nunca van vacías ("—") para que el filtro por
     * casillas pueda seleccionarlas; las numéricas conservan null.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        $yesNo = fn ($v) => $v ? 'Sí' : 'No';
        $num   = fn ($v) => $v === null ? null : (float) $v;
        $dash  = fn ($v) => ($v === null || $v === '') ? '—' : (string) $v;

        return Products::query()
            ->with(['brand:id,name', 'category:id,name'])
            ->select([
                'id', 'brand_id', 'category_id', 'sku', 'name', 'model', 'supplier_sku', 'short_description',
                'price', 'compare_price', 'cost', 'currency', 'price_includes_tax', 'shipping_cost',
                'free_shipping_threshold', 'stock', 'stock_unit', 'reorder_point', 'weight', 'height',
                'width', 'length', 'is_active', 'publish_on_website', 'is_featured', 'is_new',
                'is_recommended', 'show_in_merchant_center', 'accepts_msi', 'slug', 'created_at',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Products $p) => [
                'sku'                     => (string) $p->sku,
                'name'                    => (string) $p->name,
                'model'                   => (string) $p->model,
                'supplier_sku'            => (string) $p->supplier_sku,
                'brand'                   => $dash($p->brand?->name),
                'category'                => $dash($p->category?->name),
                'short_description'       => Str::limit(trim(strip_tags((string) $p->short_description)), 160),
                'price'                   => $num($p->price),
                'compare_price'           => $num($p->compare_price),
                'cost'                    => $num($p->cost),
                'currency'                => $dash($p->currency),
                'price_includes_tax'      => $yesNo($p->price_includes_tax),
                'shipping_cost'           => $num($p->shipping_cost),
                'free_shipping_threshold' => $num($p->free_shipping_threshold),
                'stock'                   => $num($p->stock),
                'stock_unit'              => $dash($p->stock_unit),
                'reorder_point'           => $num($p->reorder_point),
                'weight'                  => $num($p->weight),
                'height'                  => $num($p->height),
                'width'                   => $num($p->width),
                'length'                  => $num($p->length),
                'is_active'               => $yesNo($p->is_active),
                'publish_on_website'      => $yesNo($p->publish_on_website),
                'is_featured'             => $yesNo($p->is_featured),
                'is_new'                  => $yesNo($p->is_new),
                'is_recommended'          => $yesNo($p->is_recommended),
                'show_in_merchant_center' => $yesNo($p->show_in_merchant_center),
                'accepts_msi'             => $yesNo($p->accepts_msi),
                'slug'                    => (string) $p->slug,
                'created_at'              => $p->created_at?->format('Y-m-d') ?? '—',
            ])
            ->all();
    }
}
