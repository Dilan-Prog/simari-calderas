<?php

namespace Tests\Feature\ProductBlocks;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Products;
use App\Models\ShippingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Products::shippingInfo() -- misma precedencia que Cart::shippingGroups():
 * costo propio > regla de marca > regla de categoría > gratis.
 */
class ProductShippingInfoTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private function product(array $attrs = []): Products
    {
        // Las reglas activas se cachean por request (cache "array"): se
        // limpia para que cada caso lea las reglas que acaba de crear.
        Cache::driver('array')->forget('shipping_rules.active');

        return $this->makeProduct($attrs);
    }

    public function test_sin_costo_propio_ni_regla_es_gratis(): void
    {
        $info = $this->product()->shippingInfo();

        $this->assertSame(0.0, $info['cost']);
        $this->assertNull($info['source']);
    }

    public function test_costo_propio_gana_sobre_la_regla_de_marca(): void
    {
        $brand = Brand::create(['name' => 'B', 'slug' => 'b']);
        ShippingRule::create(['brand_id' => $brand->id, 'shipping_cost' => 500, 'is_active' => true]);
        $p = $this->product(['brand_id' => $brand->id, 'shipping_cost' => 120, 'free_shipping_threshold' => 3000]);

        $info = $p->shippingInfo();

        $this->assertSame('product', $info['source']);
        $this->assertSame(120.0, $info['cost']);
        $this->assertSame(3000.0, $info['threshold']);
    }

    public function test_regla_de_marca_cobra_aunque_el_producto_no_tenga_costo(): void
    {
        $brand = Brand::create(['name' => 'B2', 'slug' => 'b2']);
        ShippingRule::create(['brand_id' => $brand->id, 'shipping_cost' => 250, 'free_shipping_threshold' => 5000, 'is_active' => true]);
        $p = $this->product(['brand_id' => $brand->id]);

        $info = $p->shippingInfo();

        $this->assertSame('brand', $info['source']);
        $this->assertSame(250.0, $info['cost']);
        $this->assertSame(5000.0, $info['threshold']);
    }

    public function test_regla_inactiva_se_ignora(): void
    {
        $brand = Brand::create(['name' => 'B3', 'slug' => 'b3']);
        ShippingRule::create(['brand_id' => $brand->id, 'shipping_cost' => 250, 'is_active' => false]);

        $this->assertNull($this->product(['brand_id' => $brand->id])->shippingInfo()['source']);
    }
}
