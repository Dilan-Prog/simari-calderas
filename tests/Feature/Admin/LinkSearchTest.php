<?php

namespace Tests\Feature\Admin;

use App\Models\Collection;
use App\Models\Role;
use App\Models\ServicePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Buscador de destinos internos para enlaces en los editores en vivo.
 * Requiere que la ruta admin.links.destinations (GET /admin/enlaces/destinos) esté
 * registrada en routes/admin.php.
 */
class LinkSearchTest extends TestCase
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

    public function test_requires_authentication(): void
    {
        $this->getJson(route('admin.links.destinations', ['q' => 'cal']))->assertUnauthorized();
    }

    public function test_short_query_returns_empty(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(route('admin.links.destinations', ['q' => 'a']))
            ->assertOk()->assertExactJson([]);
    }

    public function test_finds_active_collections_and_services_with_relative_urls(): void
    {
        Collection::create(['name' => 'Calderas industriales', 'slug' => 'calderas-ind', 'type' => 'manual', 'is_active' => true]);
        Collection::create(['name' => 'Calderas ocultas', 'slug' => 'calderas-ocultas', 'type' => 'manual', 'is_active' => false]);
        ServicePage::create(['name' => 'Mantenimiento de calderas', 'slug' => 'mant-calderas', 'page_type' => ServicePage::TYPE_SERVICE, 'is_active' => true]);

        $res = $this->actingAs($this->adminUser())
            ->getJson(route('admin.links.destinations', ['q' => 'calderas']))
            ->assertOk()->json();

        $byType = collect($res)->groupBy('type');
        $this->assertCount(1, $byType['coleccion']);
        $this->assertSame('/coleccion/calderas-ind', $byType['coleccion'][0]['url']);
        $this->assertSame('/servicio/mant-calderas', $byType['servicio'][0]['url']);
        foreach ($res as $row) {
            $this->assertStringStartsWith('/', $row['url']);
            $this->assertSame(['type', 'label', 'url', 'hint'], array_keys($row));
        }
    }

    public function test_types_filter(): void
    {
        Collection::create(['name' => 'Calderas', 'slug' => 'calderas', 'type' => 'manual', 'is_active' => true]);
        ServicePage::create(['name' => 'Calderas serv', 'slug' => 'calderas-serv', 'page_type' => ServicePage::TYPE_SERVICE, 'is_active' => true]);

        $res = $this->actingAs($this->adminUser())
            ->getJson(route('admin.links.destinations', ['q' => 'calderas', 'types' => 'coleccion']))
            ->assertOk()->json();

        $this->assertSame(['coleccion'], array_values(array_unique(array_column($res, 'type'))));
    }
}
