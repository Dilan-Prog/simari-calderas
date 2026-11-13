<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Redirect;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\CategorySlugs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Slug jerárquico de categorías: la URL pública /catalogo/{slug} lleva la ruta completa con los ancestros
 * ("calentadores-de-agua/boiler-de-paso"). El admin la arma siempre desde el padre y una reparación corrige
 * las que quedaron sueltas, con redirect 301 de la URL vieja.
 */
class CategorySlugHierarchyTest extends TestCase
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

    private function cat(string $name, string $slug, ?int $parentId = null): Category
    {
        return Category::create(['name' => $name, 'slug' => $slug, 'parent_id' => $parentId, 'is_active' => true, 'sort_order' => 0]);
    }

    // ── Admin: crear y editar ───────────────────────────────────────────

    public function test_una_subcategoria_con_slug_suelto_se_guarda_con_la_ruta_de_su_padre(): void
    {
        $padre = $this->cat('Calentadores de Agua', 'calentadores-de-agua');

        $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
            'name' => 'Boiler de Paso', 'slug' => 'boiler-de-paso', 'parent_id' => $padre->id,
        ])->assertOk()->assertJsonPath('category.slug', 'calentadores-de-agua/boiler-de-paso');

        // Escribiendo la ruta completa queda igual; escribiendo una ruta equivocada, se corrige.
        $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
            'name' => 'Instantáneos', 'slug' => 'otra-cosa/instantaneos', 'parent_id' => $padre->id,
        ])->assertOk()->assertJsonPath('category.slug', 'calentadores-de-agua/instantaneos');
    }

    public function test_tercer_nivel_y_categoria_principal(): void
    {
        $a = $this->cat('Tratamiento de Agua', 'tratamiento-de-agua');
        $b = $this->cat('Filtros', 'tratamiento-de-agua/filtros', $a->id);

        $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
            'name' => 'Lecho profundo', 'slug' => 'lecho-profundo', 'parent_id' => $b->id,
        ])->assertOk()->assertJsonPath('category.slug', 'tratamiento-de-agua/filtros/lecho-profundo');

        $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
            'name' => 'Nueva Principal', 'slug' => 'Nueva Principal!',
        ])->assertOk()->assertJsonPath('category.slug', 'nueva-principal');
    }

    public function test_editar_cambia_la_ruta_registra_el_301_y_arrastra_a_los_hijos(): void
    {
        $a = $this->cat('Calentadores', 'calentadores');
        $b = $this->cat('Servicios', 'servicios');
        $hija = $this->cat('Boiler', 'calentadores/boiler', $a->id);
        $nieta = $this->cat('Boiler Pro', 'calentadores/boiler/boiler-pro', $hija->id);

        // Se mueve "Boiler" bajo "Servicios".
        $this->actingAs($this->admin)->putJson(route('admin.categories.update', $hija->id), [
            'name' => 'Boiler', 'slug' => 'boiler', 'parent_id' => $b->id,
        ])->assertOk()->assertJsonPath('category.slug', 'servicios/boiler');

        $this->assertSame('servicios/boiler/boiler-pro', $nieta->fresh()->slug);
        $this->assertSame('/catalogo/servicios/boiler', Redirect::resolve('/catalogo/calentadores/boiler')->new_path);
        $this->assertSame('/catalogo/servicios/boiler/boiler-pro', Redirect::resolve('/catalogo/calentadores/boiler/boiler-pro')->new_path);
        $this->assertSame(301, Redirect::resolve('/catalogo/calentadores/boiler')->status_code);
    }

    public function test_choque_de_url_y_ciclos_se_rechazan(): void
    {
        $padre = $this->cat('Calentadores', 'calentadores');
        $this->cat('Boiler', 'calentadores/boiler', $padre->id);

        $this->actingAs($this->admin)->postJson(route('admin.categories.store'), [
            'name' => 'Otro Boiler', 'slug' => 'boiler', 'parent_id' => $padre->id,
        ])->assertStatus(422)->assertJsonValidationErrors('slug');

        $hija = Category::where('slug', 'calentadores/boiler')->firstOrFail();
        $this->actingAs($this->admin)->putJson(route('admin.categories.update', $padre->id), [
            'name' => 'Calentadores', 'slug' => 'calentadores', 'parent_id' => $hija->id,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_id');
        $this->actingAs($this->admin)->putJson(route('admin.categories.update', $padre->id), [
            'name' => 'Calentadores', 'slug' => 'calentadores', 'parent_id' => $padre->id,
        ])->assertStatus(422)->assertJsonValidationErrors('parent_id');
    }

    public function test_guardar_sin_cambios_no_genera_redirects(): void
    {
        $padre = $this->cat('Calentadores', 'calentadores');
        $hija = $this->cat('Boiler', 'calentadores/boiler', $padre->id);

        $this->actingAs($this->admin)->putJson(route('admin.categories.update', $hija->id), [
            'name' => 'Boiler de Paso', 'slug' => 'calentadores/boiler', 'parent_id' => $padre->id,
        ])->assertOk();

        $this->assertSame(0, Redirect::count());
    }

    // ── Reparación de las existentes ────────────────────────────────────

    public function test_la_reparacion_corrige_los_slugs_sueltos_pegados_y_a_medias_con_su_301(): void
    {
        $agua = $this->cat('Calentadores de Agua', 'calentadores-de-agua');
        $this->cat('Boiler de Paso', 'boiler-de-paso', $agua->id);
        $this->cat('Instantáneos', 'calentadores-de-agua/instantaneos', $agua->id); // ya correcta

        $mass = $this->cat('Calentadores Masstercal', 'calentadores-masstercal');
        $this->cat('Calentador Servicios Generales', 'calentadores-masstercalcalentador-servicios-generales', $mass->id); // pegado

        $trat = $this->cat('Tratamiento de Agua', 'tratamiento-de-agua');
        $filtros = $this->cat('Filtros', 'tratamiento-de-agua/filtros', $trat->id);
        $this->cat('Lecho profundo', 'filtros/lecho-profundo', $filtros->id); // a medias

        $result = CategorySlugs::repairAll();

        $this->assertCount(3, $result['changed']);
        $this->assertSame([], $result['conflicts']);
        $this->assertSame('calentadores-de-agua/boiler-de-paso', Category::where('name', 'Boiler de Paso')->value('slug'));
        $this->assertSame('calentadores-masstercal/calentador-servicios-generales', Category::where('name', 'Calentador Servicios Generales')->value('slug'));
        $this->assertSame('tratamiento-de-agua/filtros/lecho-profundo', Category::where('name', 'Lecho profundo')->value('slug'));
        $this->assertSame('calentadores-de-agua/instantaneos', Category::where('name', 'Instantáneos')->value('slug'), 'la que ya estaba bien no cambia');

        // Cada URL vieja redirige (en un solo salto) a la nueva.
        $this->assertSame('/catalogo/calentadores-de-agua/boiler-de-paso', Redirect::resolve('/catalogo/boiler-de-paso')->new_path);
        $this->assertSame('/catalogo/calentadores-masstercal/calentador-servicios-generales', Redirect::resolve('/catalogo/calentadores-masstercalcalentador-servicios-generales')->new_path);
        $this->assertSame('/catalogo/tratamiento-de-agua/filtros/lecho-profundo', Redirect::resolve('/catalogo/filtros/lecho-profundo')->new_path);

        // Idempotente.
        $this->assertCount(0, CategorySlugs::repairAll()['changed']);
        $this->assertSame([], CategorySlugs::plan());
    }

    public function test_dry_run_no_escribe_y_un_conflicto_no_se_pisa(): void
    {
        $agua = $this->cat('Calentadores de Agua', 'calentadores-de-agua');
        $suelta = $this->cat('Boiler de Paso', 'boiler-de-paso', $agua->id);

        $dry = CategorySlugs::repairAll(true);
        $this->assertCount(1, $dry['changed']);
        $this->assertSame('boiler-de-paso', $suelta->fresh()->slug);
        $this->assertSame(0, Redirect::count());

        // Otra categoría ya ocupa la URL correcta: se reporta y no se cambia.
        $this->cat('Boiler duplicado', 'calentadores-de-agua/boiler-de-paso', $agua->id);
        $result = CategorySlugs::repairAll();
        $this->assertSame([], $result['changed']);
        $this->assertCount(1, $result['conflicts']);
        $this->assertSame('boiler-de-paso', $suelta->fresh()->slug);
    }

    public function test_la_url_vieja_redirige_en_la_tienda_y_la_nueva_responde(): void
    {
        $agua = $this->cat('Calentadores de Agua', 'calentadores-de-agua');
        $this->cat('Boiler de Paso', 'boiler-de-paso', $agua->id);

        $this->get('/catalogo/boiler-de-paso')->assertOk(); // antes de reparar sigue existiendo

        CategorySlugs::repairAll();

        $this->get('/catalogo/boiler-de-paso')->assertStatus(301)->assertRedirect('/catalogo/calentadores-de-agua/boiler-de-paso');
        $this->get('/catalogo/calentadores-de-agua/boiler-de-paso')->assertOk();
    }

    public function test_la_migracion_de_reparacion_corrige_los_datos(): void
    {
        $agua = $this->cat('Calentadores de Agua', 'calentadores-de-agua');
        $this->cat('Boiler de Paso', 'boiler-de-paso', $agua->id);

        (require database_path('migrations/2026_11_12_110000_fix_category_slugs_hierarchy.php'))->up();

        $this->assertSame('calentadores-de-agua/boiler-de-paso', Category::where('name', 'Boiler de Paso')->value('slug'));
    }
}
