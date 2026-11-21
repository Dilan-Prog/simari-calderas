<?php

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Editor en vivo de Categorías: bloques propios con herencia, cuadrícula de
 * productos con banners entre filas, zonas arriba/abajo de la página pública
 * e información general. Auth: mismo patrón que CollectionLiveEditorTest.
 */
class CategoryLiveEditorTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return $this->userWithRole('Administrador');
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::create(['name_role' => $roleName, 'name_role_es' => $roleName]);

        return User::create([
            'first_name' => 'Usuario',
            'last_name'  => 'Test',
            'position'   => $roleName,
            'phone'      => '5555555555',
            'email'      => 'user-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    private function makeCategory(string $name, string $slug, ?Category $parent = null): Category
    {
        return Category::create([
            'name'      => $name,
            'slug'      => $parent ? $parent->slug . '/' . $slug : $slug,
            'parent_id' => $parent?->id,
            'is_active' => true,
        ]);
    }

    private function makeProduct(Category $category, string $name): Products
    {
        return Products::create([
            'category_id'        => $category->id,
            'name'               => $name,
            'slug'               => 'prod-' . uniqid(),
            'sku'                => 'SKU-' . uniqid(),
            'price'              => 100,
            'base_price'         => 100,
            'stock'              => 5,
            'is_active'          => true,
            'publish_on_website' => true,
        ]);
    }

    private function gridSection(array $config = [], string $title = 'Lo mejor de {categoria}'): array
    {
        return [
            'type'   => 'product_grid',
            'title'  => $title,
            'config' => array_merge(['source' => 'category', 'per_row' => 4, 'rows' => 2], $config),
        ];
    }

    public function test_guarda_bloques_sin_borrar_por_omision_y_devuelve_secciones(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCategory('Calderas', 'calderas');

        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.save', $c->id), [
            'sections' => [
                $this->gridSection(),
                ['type' => 'html_block', 'title' => 'Dos', 'is_active' => false],
            ],
        ])->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'sections')
            ->assertJsonPath('sections.0.type', 'product_grid')
            ->assertJsonPath('sections.1.is_active', false)
            ->assertJsonPath('sections.1.sort_order', 1);

        $first = $c->sections()->first();
        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.save', $c->id), [
            'sections' => [['id' => $first->id, 'type' => 'product_grid', 'title' => 'Editado']],
        ])->assertOk();

        $this->assertSame(2, $c->sections()->count());
        $this->assertSame('Editado', $first->fresh()->title);
    }

    public function test_deleted_ids_borra_solo_lo_pedido_y_de_esa_categoria(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCategory('Calderas', 'calderas');
        $other = $this->makeCategory('Otra', 'otra');
        $a = $c->sections()->create(['type' => 'banner', 'title' => 'A', 'config' => [], 'sort_order' => 0]);
        $b = $c->sections()->create(['type' => 'banner', 'title' => 'B', 'config' => [], 'sort_order' => 1]);
        $x = $other->sections()->create(['type' => 'banner', 'title' => 'X', 'config' => [], 'sort_order' => 0]);

        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.save', $c->id), [
            'sections'    => [['id' => $a->id, 'type' => 'banner', 'title' => 'A']],
            'deleted_ids' => [$b->id, $x->id],
        ])->assertOk();

        $this->assertSame(['A'], $c->sections()->pluck('title')->all());
        $this->assertSame(1, $other->sections()->count());

        // Un id de otra categoría no se actualiza: se crea uno nuevo aquí.
        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.save', $c->id), [
            'sections' => [['id' => $a->id, 'type' => 'banner', 'title' => 'A'], ['id' => $x->id, 'type' => 'banner', 'title' => 'Robado']],
        ])->assertOk();
        $this->assertSame('X', $x->fresh()->title);
        $this->assertSame(['A', 'Robado'], $c->sections()->pluck('title')->all());
    }

    public function test_tipo_no_permitido_se_rechaza(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');

        $this->actingAs($this->adminUser())->putJson(route('admin.categories.live-editor.save', $c->id), [
            'sections' => [['type' => 'rich_header']],
        ])->assertStatus(422)->assertJsonValidationErrors('sections.0.type');

        $this->assertSame(0, $c->sections()->count());
    }

    public function test_requiere_permiso_de_categorias(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        $user = $this->userWithRole('Vendedor');

        $this->actingAs($user)->putJson(route('admin.categories.live-editor.save', $c->id), ['sections' => []])->assertStatus(403);
        $this->actingAs($user)->postJson(route('admin.categories.live-editor.preview', $c->id), [])->assertStatus(403);
        $this->actingAs($user)->getJson(route('admin.categories.live-editor.products-search', ['q' => 'x']))->assertStatus(403);
        $this->assertSame(0, $c->sections()->count());
    }

    public function test_preview_pinta_cuadricula_con_banner_tras_la_fila_1(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        $other = $this->makeCategory('Bombas', 'bombas');
        foreach (range(1, 6) as $i) {
            $this->makeProduct($c, "Caldera {$i}");
        }
        $this->makeProduct($other, 'Bomba ajena');

        $res = $this->actingAs($this->adminUser())
            ->postJson(route('admin.categories.live-editor.preview', $c->id), [
                'sections' => [
                    $this->gridSection(['banners' => [
                        ['after_row' => 1, 'image_url' => 'https://cdn.example.com/banner-fila.jpg', 'alt' => 'BANNER-FILA', 'no_link' => true],
                        ['after_row' => 9, 'image_url' => 'https://cdn.example.com/banner-final.jpg', 'alt' => 'BANNER-FINAL', 'link_url' => '/contacto'],
                    ]]),
                    ['type' => 'html_block', 'config' => ['html' => '<p>HIDDEN-MARK</p>', 'position' => 'bottom'], 'is_active' => false],
                ],
            ])->assertOk();

        $html = $res->json('html');
        $this->assertStringContainsString('product-grid-block', $html);
        $this->assertStringContainsString('Lo mejor de Calderas', $html);
        $this->assertStringNotContainsString('HIDDEN-MARK', $html);
        $this->assertSame(0, $c->sections()->count());

        // Dentro del bloque: fila (4) → banner → fila (2) → banner final.
        $start = strpos($html, '<section class="product-grid-block"');
        $block = substr($html, $start, strpos($html, '</section>', $start) - $start);
        $this->assertSame(2, substr_count($block, 'product-grid-block__row'));
        $this->assertSame(6, substr_count($block, 'product-grid-block__item'));
        $firstRowEnd = strpos($block, 'product-grid-block__row', strpos($block, 'product-grid-block__row') + 1);
        $bannerPos = strpos($block, 'BANNER-FILA');
        $this->assertNotFalse($bannerPos);
        $this->assertLessThan($firstRowEnd, $bannerPos, 'El banner va entre la fila 1 y la 2');
        $this->assertGreaterThan($firstRowEnd, strpos($block, 'BANNER-FINAL'), 'after_row fuera de rango va tras la última fila');
        $this->assertStringContainsString('href="/contacto"', $block);
        $this->assertStringContainsString('Caldera 1', $block);
        $this->assertStringNotContainsString('Bomba ajena', $block);
    }

    public function test_bloque_titulo_y_parrafo_separa_parrafos_y_escapa_html(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        $admin = $this->adminUser();

        $html = $this->actingAs($admin)
            ->postJson(route('admin.categories.live-editor.preview', $c->id), [
                'sections' => [[
                    'type'   => 'text_block',
                    'config' => [
                        'heading'       => 'Todo sobre {categoria}',
                        'heading_style' => ['heading_tag' => 'h3', 'text_align' => 'center'],
                        'body'          => "Primer párrafo\ncon salto.\n\nSegundo <script>x</script> párrafo.",
                    ],
                ]],
            ])->assertOk()->json('html');

        $this->assertStringContainsString('text-block-section', $html);
        $this->assertMatchesRegularExpression('/<h3 class="text-block-section__title"[^>]*text-align:center[^>]*>Todo sobre Calderas<\/h3>/', $html);
        $this->assertStringContainsString('Primer párrafo<br>con salto.', $html);
        $this->assertSame(2, substr_count($html, 'class="text-block-section__text"'));
        $this->assertStringNotContainsString('<script>x</script>', $html);

        // Permitido al guardar en los tres editores.
        $this->actingAs($admin)
            ->putJson(route('admin.categories.live-editor.save', $c->id), ['sections' => [['type' => 'text_block', 'config' => ['heading' => 'A']]]])
            ->assertOk();
        $this->assertContains('text_block', \App\Http\Controllers\Backend\CollectionController::SECTION_TYPES);
    }

    public function test_preview_sin_secciones_y_con_use_inherited(): void
    {
        $parent = $this->makeCategory('Calderas', 'calderas');
        $child = $this->makeCategory('Eléctricas', 'electricas', $parent);
        $parent->sections()->create(['type' => 'html_block', 'config' => ['html' => '<p>PARENT-MARK</p>'], 'sort_order' => 0]);
        $admin = $this->adminUser();

        $html = $this->actingAs($admin)->postJson(route('admin.categories.live-editor.preview', $child->id), ['sections' => []])
            ->assertOk()->json('html');
        $this->assertStringNotContainsString('PARENT-MARK', $html);

        $html = $this->actingAs($admin)->postJson(route('admin.categories.live-editor.preview', $child->id), ['sections' => [], 'use_inherited' => true])
            ->assertOk()->json('html');
        $this->assertStringContainsString('PARENT-MARK', $html);
    }

    public function test_hija_sin_bloques_hereda_los_del_padre_con_sus_propios_productos(): void
    {
        $parent = $this->makeCategory('Calderas', 'calderas');
        $childA = $this->makeCategory('Eléctricas', 'electricas', $parent);
        $childB = $this->makeCategory('De gas', 'de-gas', $parent);
        $this->makeProduct($childA, 'Producto Electrica');
        $this->makeProduct($childB, 'Producto Gas');
        $parent->sections()->create(array_merge($this->gridSection(), ['sort_order' => 0]));

        $res = $this->get('/catalogo/' . $childA->slug)->assertOk();
        $html = $res->getContent();
        $start = strpos($html, '<section class="product-grid-block"');
        $this->assertNotFalse($start, 'La hija debe heredar la cuadrícula del padre');
        $block = substr($html, $start, strpos($html, '</section>', $start) - $start);
        $this->assertStringContainsString('Producto Electrica', $block);
        $this->assertStringNotContainsString('Producto Gas', $block);
        $this->assertStringContainsString('Lo mejor de Eléctricas', $block);

        $effective = $childA->fresh()->effectiveSections();
        $this->assertSame($parent->id, $effective['inherited_from']->id);

        // Con bloques propios (aunque inactivos) ya no hereda.
        $childA->sections()->create(['type' => 'html_block', 'config' => ['html' => '<p>OWN</p>'], 'sort_order' => 0, 'is_active' => false]);
        $this->get('/catalogo/' . $childA->slug)->assertOk()->assertDontSee('product-grid-block', false);
    }

    public function test_bloques_por_zona_y_no_en_json_ni_en_pagina_2(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        foreach (range(1, 30) as $i) {
            $this->makeProduct($c, "Caldera {$i}");
        }
        $c->sections()->create(['type' => 'html_block', 'config' => ['html' => '<p>TOP-MARK</p>'], 'sort_order' => 0]);
        $c->sections()->create(['type' => 'html_block', 'config' => ['html' => '<p>BOTTOM-MARK</p>', 'position' => 'bottom'], 'sort_order' => 1]);

        $html = $this->get('/catalogo/calderas')->assertOk()->getContent();
        $top = strpos($html, 'TOP-MARK');
        $layout = strpos($html, 'class="catalog-layout"');
        $pagination = strpos($html, 'catalog-results__pagination');
        $bottom = strpos($html, 'BOTTOM-MARK');
        $this->assertNotFalse($top);
        $this->assertNotFalse($bottom);
        $this->assertLessThan($layout, $top);
        $this->assertGreaterThan($pagination, $bottom);

        $json = $this->getJson('/catalogo/calderas')->assertOk();
        $this->assertStringNotContainsString('TOP-MARK', $json->getContent());
        $this->assertStringNotContainsString('BOTTOM-MARK', $json->getContent());

        $this->get('/catalogo/calderas?page=2')->assertOk()
            ->assertDontSee('TOP-MARK', false)
            ->assertDontSee('BOTTOM-MARK', false);
    }

    public function test_titulo_y_descripcion_seo_se_aplican_en_la_pagina_publica(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');

        // Sin SEO: nombre — Equiterm Industries.
        $this->get('/catalogo/calderas')->assertOk()->assertSee('<title>Calderas — Equiterm Industries</title>', false);

        $c->update(['seo_title' => 'Calderas industriales en México', 'seo_description' => 'Venta de calderas con envío nacional.']);

        $this->get('/catalogo/calderas')->assertOk()
            ->assertSee('<title>Calderas industriales en México</title>', false)
            ->assertSee('<meta name="description" content="Venta de calderas con envío nacional."', false)
            ->assertSee('content="Calderas industriales en México"', false);
    }

    public function test_paginas_publicas_de_los_tres_niveles_cargan(): void
    {
        $main = $this->makeCategory('Calderas', 'calderas');
        $sub = $this->makeCategory('Eléctricas', 'electricas', $main);
        $child = $this->makeCategory('Residenciales', 'residenciales', $sub);
        $this->makeProduct($child, 'Caldera residencial');
        $main->sections()->create(['type' => 'product_carousel', 'title' => 'Carrusel {categoria}', 'config' => ['source' => 'category'], 'sort_order' => 0]);
        $main->sections()->create(['type' => 'banner', 'config' => ['image_url' => 'https://cdn.example.com/b.jpg'], 'sort_order' => 1]);
        $main->sections()->create(['type' => 'cta_final', 'config' => ['headline' => 'Cotiza'], 'sort_order' => 2]);

        foreach ([$main, $sub, $child] as $cat) {
            $this->get('/catalogo/' . $cat->slug)->assertOk()
                ->assertSee('Carrusel ' . $cat->name, false)
                ->assertSee('Caldera residencial', false)
                ->assertSee('data-section-id=', false);
        }
    }

    public function test_general_actualiza_slug_con_padre_seo_y_faqs(): void
    {
        $admin = $this->adminUser();
        $parent = $this->makeCategory('Calderas', 'calderas');
        $c = $this->makeCategory('Eléctricas', 'electricas', $parent);

        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.general', $c->id), [
            'name' => 'Eléctricas Pro', 'slug' => 'Electricas Pro', 'description' => 'desc',
            'seo_title' => 'SEO T', 'seo_description' => 'SEO D', 'is_active' => 1,
            'faq_items' => [
                ['question' => ' ¿Q? ', 'answer' => 'R'],
                ['question' => 'sin respuesta', 'answer' => ''],
            ],
        ])->assertOk()
            ->assertJsonPath('category.slug', 'calderas/electricas-pro')
            ->assertJsonPath('category.public_path', '/catalogo/calderas/electricas-pro')
            ->assertJsonPath('category.faqs.0.question', '¿Q?');

        $c->refresh();
        $this->assertSame($parent->id, $c->parent_id);
        $this->assertSame('SEO T', $c->seo_title);
        $this->assertCount(1, $c->faqs);
        $this->get('/catalogo/calderas/electricas')->assertRedirect('/catalogo/calderas/electricas-pro');

        // Sin slug conserva el segmento actual.
        $this->actingAs($admin)->putJson(route('admin.categories.live-editor.general', $c->id), ['name' => 'Otro nombre'])
            ->assertOk()->assertJsonPath('category.slug', 'calderas/electricas-pro');
    }

    public function test_busqueda_de_productos_responde_como_la_de_colecciones(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        $p = $this->makeProduct($c, 'Caldera buscable');
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->getJson(route('admin.categories.live-editor.products-search', ['q' => 'buscable']))
            ->assertOk()
            ->assertJsonPath('0.id', $p->id)
            ->assertJsonStructure([['id', 'name', 'sku', 'cover_image_url', 'price']]);

        // ?ids= devuelve exactamente esos productos (el selector pinta los ya elegidos).
        $q = $this->makeProduct($c, 'Otra caldera');
        $this->makeProduct($c, 'No pedida');
        $res = $this->actingAs($admin)
            ->getJson(route('admin.categories.live-editor.products-search', ['ids' => $p->id . ',' . $q->id]))
            ->assertOk()->assertJsonCount(2);
        $this->assertEqualsCanonicalizing([$p->id, $q->id], array_column($res->json(), 'id'));

        $this->actingAs($admin)
            ->getJson(route('admin.collections.products.search', ['ids' => (string) $q->id]))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $q->id);
    }

    public function test_galeria_lee_imagenes_de_la_config_en_categoria(): void
    {
        $c = $this->makeCategory('Calderas', 'calderas');
        $c->sections()->create(['type' => 'gallery_carousel', 'title' => 'Galería', 'config' => [
            'images' => [['url' => 'https://cdn.example.com/gal-1.jpg', 'alt' => 'GAL-ALT']],
        ], 'sort_order' => 0]);

        $this->get('/catalogo/calderas')->assertOk()
            ->assertSee('https://cdn.example.com/gal-1.jpg', false)
            ->assertSee('GAL-ALT', false)
            ->assertSee('service-page', false);
    }

    public function test_la_pantalla_del_editor_carga_en_los_tres_niveles(): void
    {
        if (!view()->exists('admin.categories.live-editor')) {
            $this->markTestSkipped('La vista admin.categories.live-editor aún no existe.');
        }

        $admin = $this->adminUser();
        $main = $this->makeCategory('Calderas', 'calderas');
        $sub = $this->makeCategory('Eléctricas', 'electricas', $main);
        $child = $this->makeCategory('Residenciales', 'residenciales', $sub);
        $main->sections()->create(['type' => 'product_grid', 'title' => 'Grid', 'config' => [], 'sort_order' => 0]);

        foreach ([$main, $sub, $child] as $cat) {
            $this->actingAs($admin)->get(route('admin.categories.live-editor', $cat->id))->assertOk();
        }
    }
}
