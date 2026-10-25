<?php

namespace Tests\Feature\Collections;

use App\Models\Collection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Buscador del listado de Colecciones (?q= por nombre o slug, con paginación). */
class CollectionSearchTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function makeCollection(string $name, string $slug): Collection
    {
        return Collection::create(['name' => $name, 'slug' => $slug, 'type' => 'manual', 'is_active' => true]);
    }

    public function test_filtra_por_nombre_slug_sin_acentos_ni_mayusculas(): void
    {
        $admin = $this->adminUser();
        $this->makeCollection('Calentadores de Albercas', 'calentadores-de-albercas');
        $this->makeCollection('Control Honeywell', 'control-de-temperatura');
        $this->makeCollection('Bomba de calor', 'bomba-de-calor');

        $names = fn (string $q) => $this->actingAs($admin)->get(route('admin.collections.index', ['q' => $q]))
            ->assertOk()->viewData('collections')->pluck('name')->all();

        $this->assertSame(['Calentadores de Albercas'], $names('ALBÉRCAS'));
        $this->assertSame(['Control Honeywell'], $names('temperatura'));          // solo en el slug
        $this->assertSame(['Bomba de calor'], $names('bomba calor'));              // varias palabras
        $this->assertCount(3, $names(''));
        $this->assertSame([], $names('%'));                                        // comodín escapado
    }

    public function test_sin_resultados_muestra_mensaje_y_conserva_el_texto(): void
    {
        $admin = $this->adminUser();
        $this->makeCollection('Calderas', 'calderas');

        $res = $this->actingAs($admin)->get(route('admin.collections.index', ['q' => 'zzz']));
        $res->assertOk();
        $res->assertSee('Sin resultados.', false);
        $res->assertSee('value="zzz"', false);
        $res->assertSee('Buscar colección por nombre o slug', false);
        $this->assertSame(0, $res->viewData('collections')->total());
    }

    public function test_la_paginacion_conserva_la_busqueda(): void
    {
        $admin = $this->adminUser();
        for ($i = 1; $i <= 17; $i++) {
            $this->makeCollection('Honeywell ' . $i, 'honeywell-' . $i);
        }
        $this->makeCollection('Otra', 'otra');

        $res = $this->actingAs($admin)->get(route('admin.collections.index', ['q' => 'honeywell']));
        $res->assertOk();
        $this->assertSame(17, $res->viewData('collections')->total());
        $this->assertCount(15, $res->viewData('collections')->items());
        $res->assertSee('q=honeywell', false);

        $page2 = $this->actingAs($admin)->get(route('admin.collections.index', ['q' => 'honeywell', 'page' => 2]));
        $this->assertCount(2, $page2->viewData('collections')->items());
    }
}
