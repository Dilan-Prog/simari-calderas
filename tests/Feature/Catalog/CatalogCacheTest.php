<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryFilterGroup;
use App\Models\CategoryFilterOption;
use App\Models\ProductSalesStat;
use App\Models\Setting;
use App\Models\ShippingRule;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesCatalogFixtures;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase, CreatesCatalogFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function row(int $productId): ?array
    {
        return collect(CatalogIndex::get()['rows'])->firstWhere('id', $productId);
    }

    private function productQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function ($query) use (&$count) {
            if (str_contains($query->sql, 'from `products`')) {
                $count++;
            }
        });
        $callback();

        return $count;
    }

    public function test_index_is_built_once_and_served_from_cache_afterwards(): void
    {
        $c = $this->makeCategory();
        $this->makeProduct($c);

        $built = $this->productQueries(fn () => CatalogIndex::get());
        $this->assertGreaterThan(0, $built);

        // Nueva "request": se pierde el memo del proceso pero la caché global sigue.
        CatalogIndex::flushMemo();
        CatalogCache::flushMemo();
        $cached = $this->productQueries(fn () => CatalogIndex::get());
        $this->assertSame(0, $cached, 'Segunda lectura sin tocar products');

        // Memo en la misma request: ni siquiera toca la caché.
        $this->assertSame(0, $this->productQueries(fn () => CatalogIndex::get()));
    }

    public function test_payload_carries_version_and_contract_shape(): void
    {
        $c = $this->makeCategory('Cat');
        $brand = $this->makeBrand('Marca');
        $g = $this->makeFilterGroup($c, 'Grupo', ['Opt' => 'Etiqueta']);
        $product = $this->makeProduct($c, [
            'brand_id' => $brand->id, 'tags' => ['ETIQUETA'], 'price' => 100, 'compare_price' => 200,
            'is_featured' => true, 'is_new' => true, 'stock' => 2,
        ]);
        ProductSalesStat::create(['product_id' => $product->id, 'units' => 7]);

        $index = CatalogIndex::get();

        $this->assertSame(CatalogCache::version(), $index['v']);
        $this->assertSame($index['v'], Cache::get('catalog.index')['v']);
        $this->assertEqualsCanonicalizing(['v', 'rows', 'cats', 'groups', 'brands'], array_keys($index));

        $row = $this->row($product->id);
        $this->assertEqualsCanonicalizing(
            ['id', 'c', 'b', 'p', 'cp', 'd', 'st', 'f24', 'fg', 'ls', 'nw', 'ts', 'feat', 'u', 'o'],
            array_keys($row)
        );
        $this->assertSame($c->id, $row['c']);
        $this->assertSame($brand->id, $row['b']);
        $this->assertSame(100.0, $row['p']);
        $this->assertSame(200.0, $row['cp']);
        $this->assertSame(50, $row['d']);
        $this->assertTrue($row['st']);
        $this->assertTrue($row['f24']);
        $this->assertTrue($row['fg']);
        $this->assertTrue($row['ls'], 'stock 2 <= umbral 3');
        $this->assertTrue($row['nw']);
        $this->assertTrue($row['feat']);
        $this->assertSame(7, $row['u']);
        $this->assertSame([$g['options']['Opt']->id], $row['o']);
        $this->assertArrayHasKey($c->id, $index['cats']);
        $this->assertSame($brand->name, $index['brands'][$brand->id]['name']);
        $this->assertSame('Grupo', $index['groups'][$g['group']->id]['name']);
    }

    public function test_cache_is_stored_with_the_configured_ttl(): void
    {
        Setting::set('catalog.cache_ttl_minutes', 3);
        $this->makeProduct();
        CatalogIndex::get();

        $this->travel(2)->minutes();
        CatalogIndex::flushMemo();
        $this->assertNotNull(Cache::get('catalog.index'), 'Sigue vigente antes del TTL');

        $this->travel(2)->minutes();
        $this->assertNull(Cache::get('catalog.index'), 'Expira pasado el TTL');
        $this->travelBack();
    }

    public function test_build_lock_is_released_after_building(): void
    {
        $this->makeProduct();
        CatalogIndex::get();

        $lock = Cache::lock('catalog.build', 5);
        $this->assertTrue($lock->get(), 'El lock de reconstrucción no debe quedar tomado');
        $lock->release();
    }

    public function test_saving_or_deleting_a_product_invalidates_the_index(): void
    {
        $c = $this->makeCategory();
        $product = $this->makeProduct($c, ['price' => 100]);
        $this->assertSame(100.0, $this->row($product->id)['p']);

        $v1 = CatalogCache::version();
        $product->price = 250;
        $product->save();
        $this->assertNotSame($v1, CatalogCache::version());
        $this->assertSame(250.0, $this->row($product->id)['p'], 'La fila refleja el cambio sin esperar el TTL');

        $new = $this->makeProduct($c);
        $this->assertNotNull($this->row($new->id), 'Producto nuevo visible de inmediato');

        $new->delete();
        $this->assertNull($this->row($new->id));
    }

    public function test_each_catalog_model_bumps_the_version_on_save_and_delete(): void
    {
        $cat = $this->makeCategory('Raiz');
        $brand = $this->makeBrand('Marca');

        $cases = [
            'category.save'  => fn () => $cat->update(['name' => 'Raiz 2']),
            'category.create' => fn () => $this->makeCategory('Otra'),
            'brand.save'     => fn () => $brand->update(['name' => 'Marca 2']),
            'brand.create'   => fn () => $this->makeBrand('Nueva'),
            'rule.create'    => function () use ($brand) {
                $this->shippingRule = ShippingRule::create(['brand_id' => $brand->id, 'shipping_cost' => 10, 'is_active' => true]);
            },
            'rule.save'      => fn () => $this->shippingRule->update(['shipping_cost' => 20]),
            'rule.delete'    => fn () => $this->shippingRule->delete(),
            'group.create'   => function () use ($cat) {
                $this->group = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'G', 'sort_order' => 0, 'is_active' => true]);
            },
            'option.create'  => function () {
                $this->option = CategoryFilterOption::create(['group_id' => $this->group->id, 'label' => 'O', 'tag' => 'o', 'sort_order' => 0, 'is_active' => true]);
            },
            'option.save'    => fn () => $this->option->update(['label' => 'O2']),
            'option.delete'  => fn () => $this->option->delete(),
            'group.save'     => fn () => $this->group->update(['name' => 'G2']),
            'group.delete'   => fn () => $this->group->delete(),
            'brand.delete'   => fn () => Brand::where('name', 'Nueva')->first()->delete(),
            'category.delete' => fn () => Category::where('name', 'Otra')->first()->delete(),
            'setting.ecommerce' => fn () => Setting::set('ecommerce.iva_rate', 8),
            'setting.catalog'   => fn () => Setting::set('catalog.new_days', 5),
        ];

        foreach ($cases as $label => $action) {
            $before = CatalogCache::version();
            $action();
            $this->assertNotSame($before, CatalogCache::version(), "{$label} debe invalidar la caché del catálogo");
        }
    }

    private $shippingRule;
    private $group;
    private $option;

    public function test_unrelated_settings_do_not_invalidate(): void
    {
        $before = CatalogCache::version();
        Setting::set('footer.address_city', 'Monterrey');
        Setting::set('site.name', 'Equiterm');
        $this->assertSame($before, CatalogCache::version());
    }

    public function test_exchange_rate_and_iva_changes_recompute_prices(): void
    {
        $c = $this->makeCategory();
        $usd = $this->makeProduct($c, ['price' => 10, 'currency' => 'USD']);
        $taxed = $this->makeProduct($c, ['price' => 116, 'price_includes_tax' => true]);

        Setting::set('ecommerce.usd_to_mxn_rate', 20);
        Setting::set('ecommerce.iva_rate', 16);
        $this->assertSame(200.0, $this->row($usd->id)['p']);
        $this->assertSame(100.0, $this->row($taxed->id)['p']);

        Setting::set('ecommerce.usd_to_mxn_rate', 25);
        Setting::set('ecommerce.iva_rate', 8);
        $this->assertSame(250.0, $this->row($usd->id)['p']);
        $this->assertSame(107.41, $this->row($taxed->id)['p']);
    }

    public function test_shipping_rule_change_updates_free_shipping_flag(): void
    {
        $c = $this->makeCategory();
        $product = $this->makeProduct($c);
        $this->assertTrue($this->row($product->id)['fg']);

        $rule = ShippingRule::create(['category_id' => $c->id, 'shipping_cost' => 99, 'is_active' => true]);
        $this->assertFalse($this->row($product->id)['fg']);

        $rule->update(['is_active' => false]);
        $this->assertTrue($this->row($product->id)['fg']);
    }

    public function test_stale_cache_with_another_version_is_rebuilt(): void
    {
        $c = $this->makeCategory();
        $p = $this->makeProduct($c);
        CatalogIndex::get();

        // Otro proceso subió la versión: el payload cacheado ya no es válido.
        Cache::forever('catalog.version', 'otra-version');
        CatalogCache::flushMemo();
        CatalogIndex::flushMemo();

        $rebuilt = CatalogIndex::get();
        $this->assertSame('otra-version', $rebuilt['v']);
        $this->assertNotNull(collect($rebuilt['rows'])->firstWhere('id', $p->id));
        $this->assertSame('otra-version', Cache::get('catalog.index')['v']);
    }

    public function test_option_matching_uses_normalized_tags(): void
    {
        $c = $this->makeCategory();
        $g = $this->makeFilterGroup($c, 'DIN', ['1/4 DIN' => '1/4 DIN']);
        $match = $this->makeProduct($c, ['tags' => ['  1/4   din ', 'Otra']]);
        $accent = $this->makeProduct($c, ['tags' => ['1/4 DIN']]);
        $noMatch = $this->makeProduct($c, ['tags' => ['1/4DIN']]);
        $noTags = $this->makeProduct($c, ['tags' => null]);

        $optionId = $g['options']['1/4 DIN']->id;
        $this->assertSame([$optionId], $this->row($match->id)['o']);
        $this->assertSame([$optionId], $this->row($accent->id)['o']);
        $this->assertSame([], $this->row($noMatch->id)['o'], '"1/4DIN" sin espacio NO coincide (a propósito)');
        $this->assertSame([], $this->row($noTags->id)['o']);
    }
}
