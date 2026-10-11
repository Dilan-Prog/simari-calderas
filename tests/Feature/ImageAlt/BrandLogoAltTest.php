<?php

namespace Tests\Feature\ImageAlt;

use App\Models\Brand;
use App\Models\BrandBulkEditView;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ALT del logotipo de las marcas: modal crear/editar, editor en lote,
 * acción de relleno y render público (carrusel de marcas).
 */
class BrandLogoAltTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'position'   => 'Administrador',
            'phone'      => '5555555555',
            'email'      => 'admin-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge(['name' => 'Honeywell', 'slug' => 'honeywell', 'is_active' => 1], $over);
    }

    private function makeBrand(array $over = []): Brand
    {
        static $n = 0;
        $n++;

        return Brand::create(array_merge([
            'name' => "Marca $n", 'slug' => "marca-$n", 'is_active' => true,
        ], $over));
    }

    public function test_store_y_update_guardan_el_alt_recortado(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.brands.store'), $this->payload([
            'logo_url' => '/uploads/h.png', 'logo_alt' => '  Logotipo de Honeywell  ',
        ]))->assertOk();
        $brand = Brand::where('slug', 'honeywell')->firstOrFail();
        $this->assertSame('Logotipo de Honeywell', $brand->logo_alt);

        $this->actingAs($admin)->putJson(route('admin.brands.update', $brand->id), $this->payload([
            'logo_url' => '/uploads/h.png', 'logo_alt' => 'Nuevo ALT',
        ]))->assertOk();
        $this->assertSame('Nuevo ALT', $brand->fresh()->logo_alt);

        $this->actingAs($admin)->putJson(route('admin.brands.update', $brand->id), $this->payload([
            'logo_url' => '/uploads/h.png', 'logo_alt' => '   ',
        ]))->assertOk();
        $this->assertNull($brand->fresh()->logo_alt);
    }

    public function test_sin_logo_el_alt_se_guarda_null(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.brands.store'), $this->payload(['logo_alt' => 'Huérfano']))
            ->assertOk();
        $this->assertNull(Brand::where('slug', 'honeywell')->first()->logo_alt);
    }

    public function test_rechaza_alt_mayor_a_255(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.brands.store'), $this->payload([
            'logo_url' => '/uploads/h.png', 'logo_alt' => str_repeat('a', 256),
        ]))->assertStatus(422)->assertJsonValidationErrors('logo_alt');
    }

    public function test_edicion_en_lote_valida_y_guarda_logo_alt(): void
    {
        $admin = $this->adminUser();
        $brand = $this->makeBrand(['logo_url' => '/uploads/a.png']);

        $res = $this->actingAs($admin)->postJson(route('admin.brands.bulk.save'), ['changes' => [
            ['id' => $brand->id, 'field' => 'logo_alt', 'value' => '  ALT en lote  '],
        ]])->assertOk();
        $this->assertTrue($res->json('results.0.ok'));
        $this->assertSame('ALT en lote', $brand->fresh()->logo_alt);

        $res = $this->actingAs($admin)->postJson(route('admin.brands.bulk.save'), ['changes' => [
            ['id' => $brand->id, 'field' => 'logo_alt', 'value' => str_repeat('b', 256)],
        ]])->assertOk();
        $this->assertFalse($res->json('results.0.ok'));
        $this->assertSame('ALT en lote', $brand->fresh()->logo_alt);

        // Vaciar el ALT lo deja null.
        $this->actingAs($admin)->postJson(route('admin.brands.bulk.save'), ['changes' => [
            ['id' => $brand->id, 'field' => 'logo_alt', 'value' => ''],
        ]])->assertOk();
        $this->assertNull($brand->fresh()->logo_alt);
    }

    public function test_edicion_en_lote_sin_logo_no_conserva_alt(): void
    {
        $admin = $this->adminUser();
        $brand = $this->makeBrand();

        $this->actingAs($admin)->postJson(route('admin.brands.bulk.save'), ['changes' => [
            ['id' => $brand->id, 'field' => 'logo_alt', 'value' => 'Sin logo'],
        ]])->assertOk();
        $this->assertNull($brand->fresh()->logo_alt);
    }

    public function test_accion_de_relleno_no_sobrescribe(): void
    {
        $admin = $this->adminUser();
        $vacio = $this->makeBrand(['name' => 'Vacio', 'slug' => 'vacio', 'logo_url' => '/uploads/v.png']);
        $propio = $this->makeBrand(['name' => 'Propio', 'slug' => 'propio', 'logo_url' => '/uploads/p.png', 'logo_alt' => 'Mi ALT']);
        $sinLogo = $this->makeBrand(['name' => 'SinLogo', 'slug' => 'sinlogo']);

        $this->actingAs($admin)->postJson(route('admin.brands.bulk.fill-alt'))
            ->assertOk()->assertJson(['success' => true, 'filled' => 1]);

        $this->assertSame('Vacio', $vacio->fresh()->logo_alt);
        $this->assertSame('Mi ALT', $propio->fresh()->logo_alt);
        $this->assertNull($sinLogo->fresh()->logo_alt);
    }

    public function test_pantalla_de_edicion_en_lote_renderiza_la_columna(): void
    {
        $admin = $this->adminUser();
        $this->makeBrand(['logo_url' => '/uploads/a.png', 'logo_alt' => 'ALT visible']);
        BrandBulkEditView::create([
            'user_id' => $admin->id, 'name' => 'Vieja', 'columns' => ['slug', 'logo_url'], 'widths' => null,
        ]);

        $this->withoutVite()->actingAs($admin)->get(route('admin.brands.bulk-edit'))
            ->assertOk()
            ->assertSee('ALT del logotipo')
            ->assertSee('data-field="logo_alt"', false)
            ->assertSee('ALT visible')
            ->assertSee('Rellenar ALT vacíos con el nombre de la marca');
    }

    public function test_vistas_guardadas_aceptan_logo_alt_y_las_viejas_siguen_validas(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.brands.bulk-edit.views.store'), [
            'name' => 'Con ALT', 'columns' => ['slug', 'logo_alt'], 'widths' => ['logo_alt' => 200],
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.brands.bulk-edit.views.store'), [
            'name' => 'Clásica', 'columns' => ['slug', 'logo_url'],
        ])->assertOk();
    }

    public function test_carrusel_publico_usa_alt_con_fallback_al_nombre(): void
    {
        $this->makeBrand(['name' => 'ConAlt', 'slug' => 'con-alt', 'logo_url' => '/uploads/a.png', 'logo_alt' => 'Logotipo especial']);
        $this->makeBrand(['name' => 'SinAlt', 'slug' => 'sin-alt', 'logo_url' => '/uploads/b.png']);

        $html = view('frontend.shop.home.sections.brand-carousel')->render();

        $this->assertStringContainsString('alt="Logotipo especial"', $html);
        $this->assertStringContainsString('alt="SinAlt"', $html);
    }
}
