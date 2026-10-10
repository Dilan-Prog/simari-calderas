<?php

namespace Tests\Feature\Products;

use App\Models\Brand;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use App\Services\Products\ProductFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

/** Filtros compartidos del listado de Productos y del editor en lote. */
class ProductFiltersTest extends TestCase
{
    use CreatesBadgeFixtures;
    use RefreshDatabase;

    private function names(array $query): array
    {
        $q = Products::query();
        ProductFilters::apply($q, Request::create('/x', 'GET', $query));

        return $q->orderBy('name')->pluck('name')->all();
    }

    public function test_categoria_incluye_descendientes_y_marca_multiple(): void
    {
        $root = $this->makeCategory(['name' => 'Raiz']);
        $child = $this->makeCategory(['name' => 'Hija', 'parent_id' => $root->id]);
        $other = $this->makeCategory(['name' => 'Otra']);
        $b1 = Brand::create(['name' => 'B1', 'slug' => 'b1']);
        $b2 = Brand::create(['name' => 'B2', 'slug' => 'b2']);

        $this->makeProduct(['name' => 'P-root', 'category_id' => $root->id, 'brand_id' => $b1->id]);
        $this->makeProduct(['name' => 'P-child', 'category_id' => $child->id, 'brand_id' => $b2->id]);
        $this->makeProduct(['name' => 'P-other', 'category_id' => $other->id]);

        $this->assertSame(['P-child', 'P-root'], $this->names(['category_id' => $root->id]));
        $this->assertSame(['P-child', 'P-root'], $this->names(['brand_id' => [$b1->id, $b2->id]]));
        $this->assertSame(['P-root'], $this->names(['brand_id' => [$b1->id]]));
    }

    public function test_rangos_banderas_moneda_y_stock_bajo(): void
    {
        $this->makeProduct(['name' => 'Barato', 'price' => 50, 'stock' => 0, 'is_featured' => true]);
        $this->makeProduct(['name' => 'Caro', 'price' => 5000, 'currency' => 'USD', 'stock' => 3, 'reorder_point' => 5]);
        $this->makeProduct(['name' => 'Medio', 'price' => 500, 'stock' => 20, 'reorder_point' => 5]);

        $this->assertSame(['Caro', 'Medio'], $this->names(['price_min' => '100']));
        $this->assertSame(['Barato', 'Medio'], $this->names(['price_max' => '1,000']));
        $this->assertSame(['Barato'], $this->names(['is_featured' => '1']));
        $this->assertSame(['Caro'], $this->names(['currency' => 'USD']));
        $this->assertSame(['Caro'], $this->names(['stock' => 'low']));
        $this->assertSame(['Barato'], $this->names(['stock' => 'out_of_stock']));
        // Valores basura se ignoran sin error
        $this->assertCount(3, $this->names(['price_min' => 'abc', 'currency' => 'EUR', 'stock' => 'zz', 'brand_id' => 'x', 'missing' => 'hack']));
    }

    public function test_datos_faltantes_etiqueta_y_busqueda_con_comodines(): void
    {
        $this->makeProduct(['name' => 'Completo', 'model' => 'M1', 'description' => 'd', 'cost' => 10, 'tags' => ['oferta'], 'seo_title' => 't', 'seo_description' => 'd']);
        $this->makeProduct(['name' => 'Pelon 100%', 'tags' => []]);

        $this->assertSame(['Pelon 100%'], $this->names(['missing' => ['model']]));
        $this->assertSame(['Pelon 100%'], $this->names(['missing' => ['tags', 'seo']]));
        $this->assertSame(['Completo'], $this->names(['tag' => 'oferta']));
        $this->assertSame(['Pelon 100%'], $this->names(['search' => '100%']));
        $this->assertSame([], $this->names(['search' => '%%%']));
    }

    public function test_listado_y_editor_en_lote_renderizan_panel_y_chips(): void
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
        $this->makeProduct(['name' => 'Visible', 'price' => 900]);
        $this->makeProduct(['name' => 'Oculto', 'price' => 10]);

        foreach (['admin.products.index', 'admin.products.bulk-edit'] as $route) {
            $res = $this->actingAs($admin)->get(route($route, ['price_min' => '100', 'missing' => ['image'], 'is_new' => '0']));
            $res->assertOk();
            $res->assertSee('Más filtros', false);
            $res->assertSee('Precio desde 100', false);
            $res->assertSee('No Nuevo', false);
            $res->assertSee('Visible', false);
            $res->assertDontSee('Oculto', false);
        }
    }

    public function test_chips_activos_y_pantallas_cargan_con_filtros(): void
    {
        $chips = ProductFilters::activeChips(Request::create('/x', 'GET', ['price_min' => '10', 'missing' => ['image'], 'is_new' => '0', 'stock' => 'low']));
        $labels = array_column($chips, 'label');
        $this->assertContains('Precio desde 10', $labels);
        $this->assertContains('Sin imagen', $labels);
        $this->assertContains('No Nuevo', $labels);
        $this->assertContains('Stock bajo', $labels);
    }
}
