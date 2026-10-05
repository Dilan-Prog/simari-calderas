<?php

namespace Tests\Feature\ProductBlocks;

use App\Models\HomeSection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Pestaña "Plantillas de producto" del admin: lista con nombre interno,
 * nombre que se muestra, badge superior (eyebrow), colección/fuente y destino.
 * Auth: mismo patrón que WebhookControllerTest (rol 'Administrador').
 */
class TemplatesIndexPageTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

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

    public function test_la_lista_muestra_nombre_interno_titulo_badge_coleccion_y_destino(): void
    {
        $collection = $this->makeCollection(['name' => 'Honeywell DC1010']);
        $this->makeTemplate([
            'name'         => 'Plantilla DC1010 interna',
            'title'        => 'Conoce toda la familia DC1010',
            'zone'         => HomeSection::ZONE_SIDEBAR,
            'config'       => ['source' => 'collection', 'collection_id' => $collection->id, 'eyebrow' => 'Controladores DC1010'],
            'heading_link' => ['type' => 'collection', 'id' => $collection->id, 'url' => null, 'new_tab' => false],
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.home-sections.index', ['pagina' => 'plantillas']));

        $response->assertOk();
        $response->assertSeeText('Nombre interno');
        $response->assertSeeText('Nombre que se muestra');
        $response->assertSeeText('Badge superior');
        $response->assertSeeText('Plantilla DC1010 interna');
        $response->assertSeeText('Conoce toda la familia DC1010');
        $response->assertSeeText('Controladores DC1010');
        $response->assertSeeText('Colección: Honeywell DC1010');
        $response->assertSee('hs-eyebrow-badge', false);
    }

    public function test_plantilla_sin_badge_ni_enlace_muestra_guiones(): void
    {
        $this->makeTemplate(['name' => 'Sin extras', 'type' => 'banner', 'config' => []]);

        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.home-sections.index', ['pagina' => 'plantillas']));

        $response->assertOk();
        $response->assertSeeText('Sin extras');
        $response->assertSeeText('Sin enlace');
        $response->assertDontSee('hs-eyebrow-badge', false);
    }

    public function test_la_pestana_de_inicio_sigue_funcionando(): void
    {
        $this->actingAs($this->adminUser())
            ->get(route('admin.home-sections.index'))
            ->assertOk()
            ->assertDontSeeText('Badge superior');
    }
}
