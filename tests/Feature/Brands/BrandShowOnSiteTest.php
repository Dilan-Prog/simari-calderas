<?php

namespace Tests\Feature\Brands;

use App\Models\Brand;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\CatalogIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Gestión de Marcas > "Mostrar en sitio web": una marca puede estar Activa (se usa en productos, cotizaciones…)
 * y no mostrarse en la tienda pública (carrusel de marcas y filtro de Marcas del catálogo).
 */
class BrandShowOnSiteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    public function test_la_columna_existe_y_las_marcas_nuevas_se_muestran_por_defecto(): void
    {
        $this->assertTrue(Schema::hasColumn('brands', 'show_on_site'));

        $b = Brand::create(['name' => 'Sin indicar', 'slug' => 'sin-indicar', 'is_active' => true]);
        $this->assertTrue($b->fresh()->show_on_site);
    }

    public function test_crear_y_editar_guardan_el_campo_y_sin_enviarlo_queda_visible(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.brands.store'), ['name' => 'Oculta', 'slug' => 'oculta', 'is_active' => 1, 'show_on_site' => 0])->assertOk();
        $brand = Brand::where('slug', 'oculta')->firstOrFail();
        $this->assertFalse($brand->show_on_site);

        $this->actingAs($this->admin)->putJson(route('admin.brands.update', $brand->id), ['name' => 'Oculta', 'slug' => 'oculta', 'is_active' => 1, 'show_on_site' => 1])->assertOk();
        $this->assertTrue($brand->fresh()->show_on_site);

        $this->actingAs($this->admin)->postJson(route('admin.brands.store'), ['name' => 'Normal', 'slug' => 'normal', 'is_active' => 1])->assertOk();
        $this->assertTrue(Brand::where('slug', 'normal')->firstOrFail()->show_on_site);
    }

    public function test_el_listado_y_el_formulario_muestran_el_campo(): void
    {
        Brand::create(['name' => 'Visible SA', 'slug' => 'visible-sa', 'is_active' => true, 'show_on_site' => true]);
        Brand::create(['name' => 'Oculta SA', 'slug' => 'oculta-sa', 'is_active' => true, 'show_on_site' => false]);

        $html = $this->actingAs($this->admin)->get(route('admin.brands.index'))->assertOk()->getContent();

        $this->assertStringContainsString('SITIO WEB', $html);
        $this->assertStringContainsString('Mostrar en sitio web', $html);
        $this->assertStringContainsString('id="brandShowOnSite"', $html);
        $this->assertStringContainsString('data-site="visible"', $html);
        $this->assertStringContainsString('data-site="hidden"', $html);
        $this->assertStringContainsString('data-col="sitio_web"', $html);
    }

    public function test_el_carrusel_de_la_tienda_no_muestra_las_marcas_ocultas_ni_las_inactivas(): void
    {
        Brand::create(['name' => 'Marca Visible', 'slug' => 'marca-visible', 'is_active' => true, 'show_on_site' => true]);
        Brand::create(['name' => 'Marca Oculta', 'slug' => 'marca-oculta', 'is_active' => true, 'show_on_site' => false]);
        Brand::create(['name' => 'Marca Inactiva', 'slug' => 'marca-inactiva', 'is_active' => false, 'show_on_site' => true]);

        $html = view('frontend.shop.home.sections.brand-carousel')->render();

        $this->assertStringContainsString('Marca Visible', $html);
        $this->assertStringNotContainsString('Marca Oculta', $html);
        $this->assertStringNotContainsString('Marca Inactiva', $html);
    }

    public function test_el_filtro_de_marcas_del_catalogo_excluye_las_ocultas(): void
    {
        $visible = Brand::create(['name' => 'Marca Visible', 'slug' => 'marca-visible', 'is_active' => true, 'show_on_site' => true]);
        $oculta = Brand::create(['name' => 'Marca Oculta', 'slug' => 'marca-oculta', 'is_active' => true, 'show_on_site' => false]);

        CatalogIndex::flushMemo();
        $ids = array_keys(CatalogIndex::get()['brands']);
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($oculta->id, $ids);

        // Al cambiar el campo, el índice en caché se actualiza solo.
        $oculta->update(['show_on_site' => true]);
        CatalogIndex::flushMemo();
        $this->assertContains($oculta->id, array_keys(CatalogIndex::get()['brands']));
    }

    public function test_una_marca_oculta_sigue_activa_para_el_resto_del_sistema(): void
    {
        $b = Brand::create(['name' => 'Solo Interna', 'slug' => 'solo-interna', 'is_active' => true, 'show_on_site' => false]);

        $this->assertTrue(Brand::where('is_active', true)->whereKey($b->id)->exists());
        $this->assertFalse(Brand::visibleOnSite()->whereKey($b->id)->exists());
    }

    public function test_la_edicion_en_lote_acepta_el_campo(): void
    {
        $b = Brand::create(['name' => 'Lote', 'slug' => 'lote', 'is_active' => true, 'show_on_site' => true]);

        $this->actingAs($this->admin)->postJson(route('admin.brands.bulk.save'), [
            'changes' => [['id' => $b->id, 'field' => 'show_on_site', 'value' => false]],
        ])->assertOk()->assertJsonPath('results.0.ok', true);

        $this->assertFalse($b->fresh()->show_on_site);

        $html = $this->actingAs($this->admin)->get(route('admin.brands.bulk-edit'))->assertOk()->getContent();
        $this->assertStringContainsString('Mostrar en sitio web', $html);
    }
}
