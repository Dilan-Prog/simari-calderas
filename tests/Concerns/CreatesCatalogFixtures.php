<?php

namespace Tests\Concerns;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryFilterGroup;
use App\Models\CategoryFilterOption;
use App\Models\Products;
use App\Models\Setting;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogIndex;
use App\Services\Catalog\CatalogParams;
use App\Services\Catalog\CatalogQuery;
use App\Services\Catalog\CatalogResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Datos y helpers para las pruebas del catálogo público (RefreshDatabase en la
 * clase que lo usa; correr con DB_DATABASE propia). Llamar a
 * resetCatalogState() en setUp() DESPUÉS de parent::setUp(): la versión de
 * CatalogCache, el memo de CatalogIndex y Setting::$cache son estáticos y
 * sobreviven entre pruebas aunque la BD y la caché se reinicien.
 */
trait CreatesCatalogFixtures
{
    private int $catalogSeq = 0;

    protected function resetCatalogState(): void
    {
        CatalogCache::flushMemo();
        CatalogIndex::flushMemo();

        $cache = new \ReflectionProperty(Setting::class, 'cache');
        $cache->setAccessible(true);
        $cache->setValue(null, []);

        Cache::driver('array')->forget('shipping_rules.active');
    }

    protected function makeCategory(string $name = 'Categoria', ?Category $parent = null, array $overrides = []): Category
    {
        $own = \Illuminate\Support\Str::slug($name) . '-' . (++$this->catalogSeq);

        return Category::create(array_merge([
            'name'       => $name,
            'slug'       => ($parent ? $parent->slug . '/' : '') . $own,
            'parent_id'  => $parent?->id,
            'is_active'  => true,
            'sort_order' => $this->catalogSeq,
        ], $overrides));
    }

    protected function makeBrand(string $name = 'Marca', array $overrides = []): Brand
    {
        return Brand::create(array_merge([
            'name'      => $name,
            'slug'      => \Illuminate\Support\Str::slug($name) . '-' . (++$this->catalogSeq),
            'is_active' => true,
        ], $overrides));
    }

    /**
     * Producto publicado. 'availability', 'created_at' y similares no están en
     * $fillable, por eso se asignan con forceFill.
     */
    protected function makeProduct(?Category $category = null, array $overrides = []): Products
    {
        $category ??= $this->makeCategory();
        $n = ++$this->catalogSeq;

        $attributes = array_merge([
            'category_id'        => $category->id,
            'name'               => 'Producto ' . $n,
            'slug'               => 'producto-' . $n,
            'sku'                => 'SKU-' . $n,
            'price'              => 100,
            'stock'              => 5,
            'availability'       => 'available',
            'is_active'          => true,
            'publish_on_website' => true,
        ], $overrides);

        $product = new Products();
        $product->forceFill($attributes)->save();

        return $product->fresh();
    }

    /**
     * Grupo de filtro técnico con sus opciones: ['Etiqueta visible' => 'etiqueta de producto', ...].
     *
     * @return array{group: CategoryFilterGroup, options: array<string, CategoryFilterOption>}
     */
    protected function makeFilterGroup(Category $category, string $name, array $options, array $overrides = []): array
    {
        $group = CategoryFilterGroup::create(array_merge([
            'category_id' => $category->id,
            'name'        => $name,
            'sort_order'  => 0,
            'is_active'   => true,
        ], $overrides));

        $created = [];
        $sort = 0;
        foreach ($options as $label => $tag) {
            $created[$label] = CategoryFilterOption::create([
                'group_id'   => $group->id,
                'label'      => $label,
                'tag'        => $tag,
                'sort_order' => $sort++,
                'is_active'  => true,
            ]);
        }

        return ['group' => $group, 'options' => $created];
    }

    /** Ejecuta el servicio como lo hace el controlador. */
    protected function runCatalog(array $query = [], ?Category $scope = null, int $perPage = 24): CatalogResult
    {
        $baseUrl = $scope ? route('catalog.category', $scope->slug) : route('catalog.index');
        $request = Request::create($baseUrl, 'GET', $query);
        $params = CatalogParams::fromRequest($request, $scope);

        return app(CatalogQuery::class)->run($params, $scope, $baseUrl, $perPage);
    }

    /** ids de los productos de la página actual, en orden. */
    protected function pageIds(CatalogResult $result): array
    {
        return $result->paginator->getCollection()->pluck('id')->all();
    }

    /** Busca un elemento por clave en una lista de facetas. */
    protected function facetItem(array $items, string $key, mixed $value): ?array
    {
        foreach ($items as $item) {
            if (($item[$key] ?? null) === $value) {
                return $item;
            }
        }

        return null;
    }
}
