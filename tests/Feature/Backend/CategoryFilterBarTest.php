<?php

namespace Tests\Feature\Backend;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Barra de búsqueda de Categorías: el filtro corre en el navegador sobre el árbol
 * completo, así que la prueba cubre los datos que necesita (slug, padre, nivel,
 * estado) y los controles de la barra.
 */
class CategoryFilterBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_arbol_expone_slug_padre_nivel_y_estado_para_el_filtro(): void
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);

        $root = Category::create(['name' => 'Calefacción', 'slug' => 'calefaccion', 'is_active' => true]);
        $sub = Category::create(['name' => 'Calentadores', 'slug' => 'calefaccion/calentadores', 'parent_id' => $root->id, 'is_active' => true]);
        $leaf = Category::create(['name' => 'MiniMasster', 'slug' => 'calefaccion/calentadores/minimasster', 'parent_id' => $sub->id, 'is_active' => false]);

        $res = $this->actingAs($admin)->get(route('admin.categories.index'));
        $res->assertOk();
        $res->assertSee('id="categorySearch"', false);
        $res->assertSee('Buscar por nombre o slug', false);
        $res->assertSee('id="categoryNoResults"', false);
        $res->assertSee('data-id="' . $leaf->id . '"', false);
        $res->assertSee('data-parent="' . $sub->id . '"', false);
        $res->assertSee('data-slug="calefaccion/calentadores/minimasster"', false);
        $res->assertSee('data-level="3"', false);
        $res->assertSee('data-status="inactive"', false);
        // el nombre se publica en minúsculas y escapado para comparar sin mayúsculas
        $res->assertSee('data-name="calefacción"', false);
    }
}
