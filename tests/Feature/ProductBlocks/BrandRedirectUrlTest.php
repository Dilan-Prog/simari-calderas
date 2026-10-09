<?php

namespace Tests\Feature\ProductBlocks;

use App\Models\Brand;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * URL de redirección por marca (Gestión de Marcas): el logo del carrusel de
 * marcas se vuelve enlace a esa URL. Auth: mismo patrón que WebhookControllerTest.
 */
class BrandRedirectUrlTest extends TestCase
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

    public function test_se_guarda_la_url_de_redireccion_al_crear_y_editar(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson(route('admin.brands.store'), $this->payload(['redirect_url' => 'https://www.honeywell.com/mx']))
            ->assertOk();
        $brand = Brand::where('slug', 'honeywell')->firstOrFail();
        $this->assertSame('https://www.honeywell.com/mx', $brand->redirect_url);

        $this->actingAs($admin)->putJson(route('admin.brands.update', $brand->id), $this->payload(['redirect_url' => '/catalogo/honeywell']))
            ->assertOk();
        $this->assertSame('/catalogo/honeywell', $brand->fresh()->redirect_url);

        $this->actingAs($admin)->putJson(route('admin.brands.update', $brand->id), $this->payload(['redirect_url' => '']))
            ->assertOk();
        $this->assertNull($brand->fresh()->redirect_url);
    }

    public function test_rechaza_esquemas_peligrosos(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('admin.brands.store'), $this->payload(['redirect_url' => 'javascript:alert(1)']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('redirect_url');
    }

    public function test_el_carrusel_enlaza_solo_las_marcas_con_url(): void
    {
        Brand::create(['name' => 'Con URL', 'slug' => 'con-url', 'is_active' => true, 'redirect_url' => 'https://externo.example.com/x']);
        Brand::create(['name' => 'Interna', 'slug' => 'interna', 'is_active' => true, 'redirect_url' => '/catalogo/interna']);
        Brand::create(['name' => 'Sin URL', 'slug' => 'sin-url', 'is_active' => true]);
        Brand::create(['name' => 'Peligrosa', 'slug' => 'peligrosa', 'is_active' => true, 'redirect_url' => 'javascript:alert(1)']);

        $html = view('frontend.shop.home.sections.brand-carousel')->render();

        // Externa: enlace en pestaña nueva
        $this->assertMatchesRegularExpression('#<a href="https://externo.example.com/x"[^>]*target="_blank"[^>]*>#', $html);
        // Interna: enlace sin pestaña nueva
        $this->assertMatchesRegularExpression('#<a href="/catalogo/interna"(?![^>]*target=)[^>]*>#', $html);
        // Sin URL y con esquema peligroso: nunca enlace
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertSame(2, substr_count($html, 'brand-carousel__item--link'));
        $this->assertStringContainsString('Sin URL', $html);
        $this->assertStringContainsString('Peligrosa', $html);
    }
}
