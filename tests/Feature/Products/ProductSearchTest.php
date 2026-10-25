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

/**
 * Buscador del listado de Productos y del editor en lote (ProductFilters::apply):
 * por palabras, en nombre/SKU/modelo/marca, sin distinguir acentos/mayúsculas y
 * combinado con el resto de los filtros.
 */
class ProductSearchTest extends TestCase
{
    use CreatesBadgeFixtures;
    use RefreshDatabase;

    private function names(array $query): array
    {
        $q = Products::query();
        ProductFilters::apply($q, Request::create('/x', 'GET', $query));

        return $q->orderBy('name')->pluck('name')->all();
    }

    private function seed3(): void
    {
        $grundfos = Brand::create(['name' => 'Grundfos', 'slug' => 'grundfos']);
        $this->makeProduct(['name' => 'Bomba centrífuga', 'sku' => 'BC-100', 'model' => 'CR-5', 'brand_id' => $grundfos->id]);
        $this->makeProduct(['name' => 'Válvula de esfera', 'sku' => 'VE-200', 'supplier_sku' => 'PROV-77']);
        $this->makeProduct(['name' => 'Caldera de condensación', 'sku' => 'CC-300', 'stock' => 0, 'is_active' => false]);
    }

    public function test_busca_por_nombre_sku_modelo_marca_y_sku_de_proveedor(): void
    {
        $this->seed3();

        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'centrífuga']));
        $this->assertSame(['Válvula de esfera'], $this->names(['search' => 've-200']));
        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'cr-5']));
        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'grundfos']));
        $this->assertSame(['Válvula de esfera'], $this->names(['search' => 'prov-77']));
    }

    public function test_ignora_acentos_mayusculas_y_espacios_sobrantes(): void
    {
        $this->seed3();

        $this->assertSame(['Válvula de esfera'], $this->names(['search' => 'VALVULA']));
        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'centrifuga']));
        $this->assertSame(['Caldera de condensación'], $this->names(['search' => 'condensacion']));
        $this->assertSame(['Válvula de esfera'], $this->names(['search' => '   válvula    esfera  ']));
    }

    public function test_varias_palabras_se_buscan_en_cualquier_orden_y_campo(): void
    {
        $this->seed3();

        // "bomba" está en el nombre y "grundfos" solo en la marca
        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'bomba grundfos']));
        $this->assertSame(['Bomba centrífuga'], $this->names(['search' => 'grundfos bomba']));
        $this->assertSame([], $this->names(['search' => 'valvula grundfos']));
        $this->assertCount(3, $this->names(['search' => '']));
        $this->assertCount(3, $this->names(['search' => '   ']));
        // un valor no escalar no rompe ni filtra
        $this->assertCount(3, $this->names(['search' => ['x']]));
    }

    public function test_la_busqueda_respeta_los_demas_filtros(): void
    {
        $this->seed3();

        $this->assertSame(['Caldera de condensación'], $this->names(['search' => 'caldera', 'stock' => 'out_of_stock']));
        $this->assertSame([], $this->names(['search' => 'caldera', 'stock' => 'in_stock']));
        $this->assertSame(['Caldera de condensación'], $this->names(['search' => 'cc', 'status' => 'inactive']));
        $this->assertSame([], $this->names(['search' => 'cc', 'status' => 'active']));

        $cat = $this->makeCategory(['name' => 'Bombas']);
        $this->makeProduct(['name' => 'Bomba sumergible', 'category_id' => $cat->id]);
        $this->assertSame(['Bomba sumergible'], $this->names(['search' => 'bomba', 'category_id' => $cat->id]));
        $this->assertSame(['Bomba centrífuga', 'Bomba sumergible'], $this->names(['search' => 'bomba']));
    }

    public function test_listado_y_editor_en_lote_conservan_el_texto_buscado(): void
    {
        $this->seed3();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);

        foreach (['admin.products.index', 'admin.products.bulk-edit'] as $route) {
            $res = $this->actingAs($admin)->get(route($route, ['search' => 'bomba grundfos', 'stock' => 'in_stock']));
            $res->assertOk();
            $res->assertSee('value="bomba grundfos"', false);
            $res->assertSee('Buscar por nombre, SKU, modelo o marca', false);
            $res->assertSee('Bomba centrífuga', false);
            $res->assertDontSee('Válvula de esfera', false);
            $res->assertDontSee('Caldera de condensación', false);
        }
    }
}
