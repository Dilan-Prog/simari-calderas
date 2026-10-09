<?php

namespace Tests\Feature\Backend;

use App\Models\Category;
use App\Models\CategoryFilterGroup;
use App\Models\CategoryFilterOption;
use App\Models\Permission;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin de filtros técnicos por categoría: GET/PUT /admin/categorias/{id}/filtros.
 * Auth: rol 'Administrador' (bypass de permisos); para el 403 se arma un rol
 * real con solo categories/view.
 */
class CategoryFilterControllerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, array $permissions = []): User
    {
        $role = Role::create(['name_role' => $roleName, 'name_role_es' => $roleName]);
        foreach ($permissions as [$module, $action]) {
            $perm = Permission::firstOrCreate(
                ['module' => $module, 'action' => $action],
                ['name' => "$module $action", 'code' => "$module.$action"]
            );
            $role->permissions()->attach($perm->id);
        }

        return User::create([
            'first_name' => 'U', 'last_name' => 'Test', 'position' => $roleName,
            'phone' => '5555555555', 'email' => 'u-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -10)), 'role_id' => $role->id,
        ]);
    }

    private ?User $adminUser = null;

    private function admin(): User
    {
        return $this->adminUser ??= $this->userWithRole('Administrador');
    }

    private function cat(string $name, ?Category $parent = null): Category
    {
        return Category::create([
            'name' => $name, 'slug' => 'c-' . uniqid(), 'is_active' => true,
            'parent_id' => $parent?->id,
        ]);
    }

    private function product(Category $cat, array $tags, array $over = []): Products
    {
        return Products::create(array_merge([
            'category_id' => $cat->id, 'name' => 'P', 'slug' => 'p-' . uniqid(),
            'sku' => 'SKU-' . uniqid(), 'price' => 100, 'stock' => 5,
            'is_active' => true, 'publish_on_website' => true, 'tags' => $tags,
        ], $over));
    }

    private function url(Category $c): string
    {
        return "/admin/categorias/{$c->id}/filtros";
    }

    private function payload(array $groups): array
    {
        return ['groups' => $groups];
    }

    private function group(string $name, array $options, array $extra = []): array
    {
        return array_merge(['name' => $name, 'is_active' => true, 'options' => $options], $extra);
    }

    private function opt(string $label, ?string $tag = null, array $extra = []): array
    {
        return array_merge(['label' => $label, 'tag' => $tag ?? $label, 'is_active' => true], $extra);
    }

    // --- rutas -----------------------------------------------------------

    public function test_routes_exist(): void
    {
        $routes = app('router')->getRoutes();
        $show = $routes->getByName('admin.categories.filters.show');
        $update = $routes->getByName('admin.categories.filters.update');

        $this->assertNotNull($show);
        $this->assertNotNull($update);
        $this->assertSame('admin/categorias/{id}/filtros', $show->uri());
        $this->assertContains('GET', $show->methods());
        $this->assertContains('PUT', $update->methods());
        $this->assertContains('permission:categories,edit', $update->gatherMiddleware());
        $this->assertContains('permission:categories', $show->gatherMiddleware());
    }

    // --- vista ---------------------------------------------------------------

    // Nota: index.blade.php declara renderCategoryRow() como función global, así
    // que solo se puede renderizar UNA vez por proceso: una prueba por rol.
    public function test_index_renders_filters_button_and_modal_for_editors(): void
    {
        $cat = $this->cat('Cat visible');

        $html = $this->actingAs($this->admin())->get('/admin/categorias')->assertOk()->getContent();
        $this->assertStringContainsString('btn-category-filters', $html);
        $this->assertStringContainsString('data-category-id="' . $cat->id . '"', $html);
        $this->assertStringContainsString('id="categoryFiltersModal"', $html);
        $this->assertStringContainsString('/admin/categorias', $html);
    }

    // --- GET -------------------------------------------------------------

    public function test_show_returns_structure_counts_subtree_inherited_and_suggestions(): void
    {
        $root = $this->cat('Controles');
        $mid  = $this->cat('Temperatura', $root);
        $leaf = $this->cat('Digitales', $mid);
        $other = $this->cat('Otra');

        // Grupo heredado (en el abuelo) y grupo propio.
        $inh = CategoryFilterGroup::create(['category_id' => $root->id, 'name' => 'Marca', 'sort_order' => 0]);
        CategoryFilterOption::create(['group_id' => $inh->id, 'label' => 'Honeywell', 'tag' => 'Honeywell', 'sort_order' => 0]);
        $own = CategoryFilterGroup::create(['category_id' => $mid->id, 'name' => 'Tamaño DIN', 'sort_order' => 0]);
        $o1 = CategoryFilterOption::create(['group_id' => $own->id, 'label' => '1/4 DIN', 'tag' => '1/4 DIN', 'sort_order' => 0]);
        $o2 = CategoryFilterOption::create(['group_id' => $own->id, 'label' => '1/8 DIN', 'tag' => '1/8 din', 'sort_order' => 1]);

        // Productos: uno en la propia, uno en la hija (subárbol), uno en otra, uno no publicado.
        $this->product($mid, ['1/4 DIN', 'Termopar']);
        $this->product($leaf, ['1/4  din', 'Termopar', 'Honeywell']);
        $this->product($other, ['1/4 DIN']);
        $this->product($mid, ['1/4 DIN'], ['publish_on_website' => false]);
        $this->product($mid, ['1/4 DIN'], ['is_active' => false]);

        $res = $this->actingAs($this->admin())->getJson($this->url($mid))->assertOk();

        $res->assertJsonPath('category.id', $mid->id)
            ->assertJsonPath('category.name', 'Temperatura')
            ->assertJsonPath('category.path', 'Controles › Temperatura');

        $groups = $res->json('groups');
        $this->assertCount(1, $groups);
        $this->assertSame('Tamaño DIN', $groups[0]['name']);
        $this->assertSame(['id', 'label', 'tag', 'sort_order', 'is_active', 'products_count'], array_keys($groups[0]['options'][0]));
        $this->assertSame(2, $groups[0]['options'][0]['products_count']); // 1/4 DIN: propia + hija (normalizado)
        $this->assertSame(0, $groups[0]['options'][1]['products_count']);

        $inherited = $res->json('inherited');
        $this->assertCount(1, $inherited);
        $this->assertSame($root->id, $inherited[0]['category_id']);
        $this->assertSame('Controles', $inherited[0]['category_name']);
        $this->assertSame('Marca', $inherited[0]['groups'][0]['name']);

        // Sugerencias: solo "Termopar" (1/4 DIN y Honeywell ya están cubiertas).
        $this->assertSame([['tag' => 'Termopar', 'count' => 2]], $res->json('tag_suggestions'));
        $this->assertArrayNotHasKey('_ctx', $res->json());
    }

    public function test_show_inherited_is_ordered_farthest_first_and_skips_empty(): void
    {
        $a = $this->cat('A');
        $b = $this->cat('B', $a);
        $c = $this->cat('C', $b);
        foreach ([$b, $a] as $i => $cat) {
            $g = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'G' . $cat->name]);
            CategoryFilterOption::create(['group_id' => $g->id, 'label' => 'x' . $i, 'tag' => 'x' . $i]);
        }

        $res = $this->actingAs($this->admin())->getJson($this->url($c))->assertOk();
        $this->assertSame(['A', 'B'], array_column($res->json('inherited'), 'category_name'));
    }

    public function test_suggestions_are_sorted_by_count_and_limited_to_40(): void
    {
        $cat = $this->cat('Cat');
        $tags = [];
        for ($i = 1; $i <= 45; $i++) {
            $tags[] = sprintf('tag%02d', $i);
        }
        $this->product($cat, $tags);
        $this->product($cat, ['tag45']);

        $res = $this->actingAs($this->admin())->getJson($this->url($cat))->assertOk();
        $sug = $res->json('tag_suggestions');
        $this->assertCount(40, $sug);
        $this->assertSame(['tag' => 'tag45', 'count' => 2], $sug[0]);
    }

    public function test_show_404_for_unknown_category(): void
    {
        $this->actingAs($this->admin())->getJson('/admin/categorias/99999/filtros')->assertNotFound();
    }

    // --- PUT: sincronización --------------------------------------------

    public function test_update_creates_groups_with_order_and_tag_normalized(): void
    {
        $cat = $this->cat('Cat');
        $this->product($cat, ['1/4 DIN']);

        $res = $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('Tamaño', [$this->opt('1/4 DIN'), $this->opt('1/8 DIN', 'Ñandú 1/8')]),
            $this->group('Entrada', [$this->opt('Termopar', 'Termopar', ['is_active' => false])], ['is_active' => false]),
        ]))->assertOk()->assertJsonPath('success', true);

        $groups = CategoryFilterGroup::where('category_id', $cat->id)->orderBy('sort_order')->get();
        $this->assertSame(['Tamaño', 'Entrada'], $groups->pluck('name')->all());
        $this->assertSame([0, 1], $groups->pluck('sort_order')->all());
        $this->assertFalse($groups[1]->is_active);

        $opts = $groups[0]->options;
        $this->assertSame(['1/4 din', 'nandu 1/8'], $opts->pluck('tag_normalized')->all());
        $this->assertSame([0, 1], $opts->pluck('sort_order')->all());
        $this->assertFalse($groups[1]->options[0]->is_active);

        $this->assertSame('Tamaño', $res->json('groups.0.name'));
        $this->assertSame(1, $res->json('groups.0.options.0.products_count'));
        $this->assertIsArray($res->json('warnings'));
    }

    public function test_update_keeps_ids_updates_reorders_and_deletes_missing(): void
    {
        $cat = $this->cat('Cat');
        $g1 = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'Uno', 'sort_order' => 0]);
        $g2 = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'Dos', 'sort_order' => 1]);
        $a = CategoryFilterOption::create(['group_id' => $g1->id, 'label' => 'A', 'tag' => 'a', 'sort_order' => 0]);
        $b = CategoryFilterOption::create(['group_id' => $g1->id, 'label' => 'B', 'tag' => 'b', 'sort_order' => 1]);
        $c = CategoryFilterOption::create(['group_id' => $g2->id, 'label' => 'C', 'tag' => 'c', 'sort_order' => 0]);

        // Se invierte el orden de grupos y opciones, se renombra, se borra B,
        // se agrega una nueva y se elimina por completo el grupo "Dos".
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('Uno renombrado', [
                $this->opt('A nueva', 'a2', ['id' => $a->id]),
                $this->opt('Nueva', 'n'),
            ], ['id' => $g1->id]),
        ]))->assertOk();

        $this->assertDatabaseHas('category_filter_groups', ['id' => $g1->id, 'name' => 'Uno renombrado']);
        $this->assertDatabaseMissing('category_filter_groups', ['id' => $g2->id]);
        $this->assertDatabaseMissing('category_filter_options', ['id' => $b->id]);
        $this->assertDatabaseMissing('category_filter_options', ['id' => $c->id]);
        $this->assertDatabaseHas('category_filter_options', ['id' => $a->id, 'label' => 'A nueva', 'tag' => 'a2', 'tag_normalized' => 'a2', 'sort_order' => 0]);
        $this->assertSame(2, CategoryFilterOption::count());
    }

    public function test_update_can_swap_tags_between_options_without_unique_collision(): void
    {
        $cat = $this->cat('Cat');
        $g = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'G']);
        $a = CategoryFilterOption::create(['group_id' => $g->id, 'label' => 'A', 'tag' => 'uno']);
        $b = CategoryFilterOption::create(['group_id' => $g->id, 'label' => 'B', 'tag' => 'dos']);

        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('G', [
                $this->opt('B', 'dos', ['id' => $b->id]),
                $this->opt('A', 'uno', ['id' => $a->id]),
                // y ahora swap real de etiquetas:
            ], ['id' => $g->id]),
        ]))->assertOk();
        $this->assertSame([$b->id, $a->id], $g->options()->pluck('id')->all());

        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('G', [
                $this->opt('B', 'uno', ['id' => $b->id]),
                $this->opt('A', 'dos', ['id' => $a->id]),
            ], ['id' => $g->id]),
        ]))->assertOk();
        $this->assertSame('uno', $b->fresh()->tag_normalized);
        $this->assertSame('dos', $a->fresh()->tag_normalized);
    }

    public function test_update_with_empty_groups_removes_everything(): void
    {
        $cat = $this->cat('Cat');
        $g = CategoryFilterGroup::create(['category_id' => $cat->id, 'name' => 'G']);
        CategoryFilterOption::create(['group_id' => $g->id, 'label' => 'A', 'tag' => 'a']);

        $this->actingAs($this->admin())->putJson($this->url($cat), ['groups' => []])
            ->assertOk()->assertJsonPath('groups', []);
        $this->assertSame(0, CategoryFilterGroup::count());
        $this->assertSame(0, CategoryFilterOption::count());
    }

    public function test_update_does_not_touch_other_categories_groups(): void
    {
        $cat = $this->cat('Cat');
        $other = $this->cat('Otra');
        $g = CategoryFilterGroup::create(['category_id' => $other->id, 'name' => 'Ajeno']);
        $o = CategoryFilterOption::create(['group_id' => $g->id, 'label' => 'A', 'tag' => 'a']);

        // Usar ids ajenos es un error de validación y no modifica nada.
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('X', [$this->opt('A', 'a', ['id' => $o->id])], ['id' => $g->id]),
        ]))->assertStatus(422);

        $this->assertDatabaseHas('category_filter_groups', ['id' => $g->id, 'name' => 'Ajeno', 'category_id' => $other->id]);
    }

    // --- PUT: validación --------------------------------------------------

    public function test_validation_group_name_required_and_max(): void
    {
        $cat = $this->cat('Cat');
        $res = $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('', [$this->opt('A')]),
            $this->group(str_repeat('x', 61), [$this->opt('B')]),
        ]))->assertStatus(422);

        $res->assertJsonValidationErrors(['groups.0.name', 'groups.1.name']);
        $this->assertSame('El nombre del grupo es obligatorio.', $res->json('errors')['groups.0.name'][0]);
        $this->assertStringContainsString('60 caracteres', $res->json('errors')['groups.1.name'][0]);
        $this->assertSame(0, CategoryFilterGroup::count());
    }

    public function test_validation_group_name_unique_normalized(): void
    {
        $cat = $this->cat('Cat');
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('Tamaño DIN', [$this->opt('A')]),
            $this->group('  tamano   din ', [$this->opt('B')]),
        ]))->assertStatus(422)->assertJsonValidationErrors(['groups.1.name']);
    }

    public function test_validation_max_groups_and_options_and_min_option(): void
    {
        $cat = $this->cat('Cat');

        $eleven = [];
        for ($i = 0; $i < 11; $i++) {
            $eleven[] = $this->group("G$i", [$this->opt("o$i")]);
        }
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload($eleven))
            ->assertStatus(422)->assertJsonValidationErrors(['groups']);

        $sixteen = [];
        for ($i = 0; $i < 16; $i++) {
            $sixteen[] = $this->opt("op$i");
        }
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([$this->group('G', $sixteen)]))
            ->assertStatus(422)->assertJsonValidationErrors(['groups.0.options']);

        $res = $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([$this->group('G', [])]))
            ->assertStatus(422)->assertJsonValidationErrors(['groups.0.options']);
        $this->assertSame('Agrega al menos una opción al grupo.', $res->json('errors')['groups.0.options'][0]);

        // Exactamente 10 grupos y 15 opciones sí pasa.
        $ten = [];
        for ($i = 0; $i < 10; $i++) {
            $ten[] = $this->group("G$i", $i === 0 ? array_map(fn ($n) => $this->opt("o$n"), range(1, 15)) : [$this->opt("z$i")]);
        }
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload($ten))->assertOk();
    }

    public function test_validation_label_and_tag_required_and_max(): void
    {
        $cat = $this->cat('Cat');
        $res = $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('G', [
                ['label' => '', 'tag' => '', 'is_active' => true],
                $this->opt(str_repeat('l', 81), str_repeat('t', 81)),
            ]),
        ]))->assertStatus(422);

        $res->assertJsonValidationErrors([
            'groups.0.options.0.label', 'groups.0.options.0.tag',
            'groups.0.options.1.label', 'groups.0.options.1.tag',
        ]);
        $this->assertSame('La etiqueta visible es obligatoria.', $res->json('errors')['groups.0.options.0.label'][0]);
        $this->assertSame('La etiqueta de producto es obligatoria.', $res->json('errors')['groups.0.options.0.tag'][0]);
    }

    public function test_validation_tag_unique_across_whole_category(): void
    {
        $cat = $this->cat('Cat');
        $res = $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('Uno', [$this->opt('A', '1/4 DIN')]),
            $this->group('Dos', [$this->opt('B', 'otra'), $this->opt('C', '1/4  din')]),
        ]))->assertStatus(422);

        $res->assertJsonValidationErrors(['groups.1.options.1.tag']);
        $this->assertStringContainsString('Uno', $res->json('errors')['groups.1.options.1.tag'][0]);
        $this->assertSame(0, CategoryFilterOption::count());
    }

    public function test_validation_tag_without_alphanumerics_is_rejected(): void
    {
        $cat = $this->cat('Cat');
        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('G', [$this->opt('Emoji', '🔥')]),
        ]))->assertStatus(422)->assertJsonValidationErrors(['groups.0.options.0.tag']);
    }

    // --- PUT: avisos -------------------------------------------------------

    public function test_warnings_zero_products_ancestor_descendant_and_similar(): void
    {
        $root = $this->cat('Root');
        $mid = $this->cat('Mid', $root);
        $leaf = $this->cat('Leaf', $mid);

        $gr = CategoryFilterGroup::create(['category_id' => $root->id, 'name' => 'GRoot']);
        CategoryFilterOption::create(['group_id' => $gr->id, 'label' => 'R', 'tag' => 'Repetida']);
        $gl = CategoryFilterGroup::create(['category_id' => $leaf->id, 'name' => 'GLeaf']);
        CategoryFilterOption::create(['group_id' => $gl->id, 'label' => 'L', 'tag' => 'Hija']);

        $this->product($leaf, ['Con productos']);

        $res = $this->actingAs($this->admin())->putJson($this->url($mid), $this->payload([
            $this->group('G', [
                $this->opt('Con productos'),
                $this->opt('Sin productos', 'Nadie'),
                $this->opt('Repetida'),
                $this->opt('Hija'),
                $this->opt('1/4 DIN'),
                $this->opt('1/4DIN'),
            ]),
        ]))->assertOk();

        $types = collect($res->json('warnings'))->groupBy('type');
        $this->assertSame(
            ['ancestor_duplicate', 'descendant_duplicate', 'no_products', 'similar'],
            $types->keys()->sort()->values()->all()
        );
        // "Con productos" no avisa; las otras 5 sin productos sí.
        $this->assertCount(5, $types['no_products']);
        $this->assertStringContainsString('Nadie', $types['no_products']->pluck('message')->implode(' '));
        $this->assertStringNotContainsString('«Con productos»', $types['no_products']->pluck('message')->implode(' '));
        $this->assertStringContainsString('«Root»', $types['ancestor_duplicate'][0]['message']);
        $this->assertStringContainsString('«Leaf»', $types['descendant_duplicate'][0]['message']);
        $this->assertStringContainsString('1/4DIN', $types['similar'][0]['message']);
    }

    public function test_no_warnings_when_everything_is_clean(): void
    {
        $cat = $this->cat('Cat');
        $this->product($cat, ['Uno', 'Dos']);

        $this->actingAs($this->admin())->putJson($this->url($cat), $this->payload([
            $this->group('G', [$this->opt('Uno'), $this->opt('Dos')]),
        ]))->assertOk()->assertJsonPath('warnings', []);
    }

    // --- permisos -----------------------------------------------------------

    public function test_put_requires_categories_edit_permission(): void
    {
        $cat = $this->cat('Cat');
        $viewer = $this->userWithRole('Consulta', [['categories', 'view']]);

        $this->actingAs($viewer)->putJson($this->url($cat), $this->payload([
            $this->group('G', [$this->opt('A')]),
        ]))->assertForbidden();
        $this->assertSame(0, CategoryFilterGroup::count());

        // Con permiso de lectura sí puede consultar.
        $this->actingAs($viewer)->getJson($this->url($cat))->assertOk();
    }

    public function test_user_without_categories_access_cannot_read(): void
    {
        $cat = $this->cat('Cat');
        $nobody = $this->userWithRole('Otro', [['products', 'view']]);

        $this->actingAs($nobody)->getJson($this->url($cat))->assertForbidden();
    }

    public function test_user_with_edit_permission_can_save(): void
    {
        $cat = $this->cat('Cat');
        $editor = $this->userWithRole('Editor', [['categories', 'view'], ['categories', 'edit']]);

        $this->actingAs($editor)->putJson($this->url($cat), $this->payload([
            $this->group('G', [$this->opt('A')]),
        ]))->assertOk();
        $this->assertSame(1, CategoryFilterGroup::count());
    }

    public function test_guest_cannot_access(): void
    {
        $cat = $this->cat('Cat');
        $this->getJson($this->url($cat))->assertStatus(401)->assertJsonMissing(['groups']);
    }
}
