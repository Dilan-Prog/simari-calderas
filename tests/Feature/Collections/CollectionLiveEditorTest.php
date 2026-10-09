<?php

namespace Tests\Feature\Collections;

use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Editor en vivo de Colecciones: bloques propios, respaldo global,
 * información general y ajustes (tipo/reglas). Auth: mismo patrón que
 * BrandRedirectUrlTest.
 */
class CollectionLiveEditorTest extends TestCase
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

    private function makeCollection(array $over = []): Collection
    {
        return Collection::create(array_merge([
            'name' => 'Calderas', 'slug' => 'calderas', 'type' => 'manual', 'is_active' => true,
        ], $over));
    }

    public function test_guarda_y_lee_bloques_propios_sin_borrar_por_omision(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCollection();

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.save', $c->id), [
            'sections' => [
                ['type' => 'html_block', 'title' => 'Uno', 'config' => ['html' => '<p>a</p>']],
                ['type' => 'faq', 'title' => 'Dos', 'is_active' => false],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(['Uno', 'Dos'], $c->sections()->pluck('title')->all());

        $first = $c->sections()->first();
        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.save', $c->id), [
            'sections' => [['id' => $first->id, 'type' => 'html_block', 'title' => 'Uno editado']],
        ])->assertOk();

        $this->assertSame(2, $c->sections()->count());
        $this->assertSame('Uno editado', $first->fresh()->title);
    }

    public function test_tipo_no_permitido_se_rechaza(): void
    {
        $c = $this->makeCollection();

        $this->actingAs($this->adminUser())->putJson(route('admin.collections.live-editor.save', $c->id), [
            'sections' => [['type' => 'rich_header']],
        ])->assertStatus(422)->assertJsonValidationErrors('sections.0.type');

        $this->assertSame(0, $c->sections()->count());
    }

    public function test_publico_usa_bloques_propios_o_respaldo_global(): void
    {
        $c = $this->makeCollection();
        HomeSection::create([
            'type' => 'html_block', 'page' => 'collection', 'name' => 'g', 'title' => 'GLOBAL-TITLE',
            'config' => ['html' => '<p>GLOBAL-MARK</p>'], 'sort_order' => 0, 'is_active' => true,
        ]);

        $this->get('/coleccion/calderas')->assertOk()->assertSee('GLOBAL-MARK', false);

        $c->sections()->create([
            'type' => 'html_block', 'title' => 'Propio', 'config' => ['html' => '<p>OWN-MARK</p>'],
            'sort_order' => 0, 'is_active' => true,
        ]);

        $this->get('/coleccion/calderas')->assertOk()
            ->assertSee('OWN-MARK', false)
            ->assertDontSee('GLOBAL-MARK', false);
    }

    public function test_general_actualiza_campos_seo_y_faqs(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCollection();
        $other = $this->makeCollection(['name' => 'Otra', 'slug' => 'otra']);

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.general', $c->id), [
            'name' => 'Calderas Pro', 'slug' => 'Calderas Pro', 'description' => 'desc',
            'seo_title' => 'SEO T', 'seo_description' => 'SEO D', 'is_active' => 0,
            'faq_items' => [
                ['question' => ' ¿Q? ', 'answer' => 'R'],
                ['question' => 'sin respuesta', 'answer' => ''],
            ],
        ])->assertOk()
            ->assertJsonPath('collection.slug', 'calderas-pro')
            ->assertJsonPath('collection.public_path', '/coleccion/calderas-pro')
            ->assertJsonPath('collection.faqs.0.question', '¿Q?');

        $c->refresh();
        $this->assertSame('SEO T', $c->seo_title);
        $this->assertFalse($c->is_active);
        $this->assertCount(1, $c->faqs);

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.general', $c->id), [
            'name' => 'X', 'slug' => $other->slug,
        ])->assertStatus(422)->assertJsonValidationErrors('slug');
    }

    public function test_ajustes_actualiza_tipo_match_y_reglas(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCollection();

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.settings', $c->id), [
            'type' => 'automatic', 'match_type' => 'any',
            'rules' => [
                ['field' => 'tag', 'operator' => 'equals', 'value' => 'oferta'],
                ['field' => 'price', 'operator' => 'greater_than', 'value' => '100'],
                ['field' => 'brand_id', 'operator' => 'equals', 'value' => ''],
            ],
        ])->assertOk()
            ->assertJsonPath('type', 'automatic')
            ->assertJsonPath('match_type', 'any')
            ->assertJsonCount(2, 'rules');

        $this->assertSame(2, $c->rules()->count());

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.settings', $c->id), [
            'type' => 'automatic', 'match_type' => 'all',
            'rule_field' => ['tag'], 'rule_operator' => ['equals'], 'rule_value' => ['nuevo'],
        ])->assertOk()->assertJsonCount(1, 'rules');

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.settings', $c->id), [
            'type' => 'automatic',
        ])->assertStatus(422)->assertJsonValidationErrors('match_type');

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.settings', $c->id), [
            'type' => 'manual',
        ])->assertOk()->assertJsonPath('match_type', null);
    }

    public function test_preview_devuelve_html_con_borrador_en_memoria(): void
    {
        $c = $this->makeCollection();

        $res = $this->actingAs($this->adminUser())
            ->postJson(route('admin.collections.live-editor.preview', $c->id), [
                'sections' => [
                    ['type' => 'html_block', 'config' => ['html' => '<p>DRAFT-MARK</p>']],
                    ['type' => 'html_block', 'config' => ['html' => '<p>HIDDEN-MARK</p>'], 'is_active' => false],
                ],
            ])->assertOk();

        $html = $res->json('html');
        $this->assertStringContainsString('DRAFT-MARK', $html);
        $this->assertStringNotContainsString('HIDDEN-MARK', $html);
        $this->assertSame(0, $c->sections()->count());
    }

    public function test_la_pantalla_del_editor_en_vivo_carga_con_datos_de_coleccion(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCollection();
        $c->sections()->create(['type' => 'benefits_grid', 'title' => 'Beneficios', 'config' => ['items' => []], 'sort_order' => 0]);

        $res = $this->actingAs($admin)->get(route('admin.collections.live-editor', $c->id));

        $res->assertOk();
        $res->assertSee('leProductsBtn', false);
        $res->assertSee('brand_logos', false);
        // Js::from escapa las diagonales dentro del JSON de arranque.
        $this->assertStringContainsString('destinos', $res->getContent());
        $this->assertStringContainsString('enlaces', $res->getContent());
        $res->assertDontSee('leGalleryBtn', false);
        $res->assertDontSee('rating_reviews', false);
    }

    public function test_el_editor_en_vivo_de_servicios_sigue_cargando_con_galeria_y_resenas(): void
    {
        $admin = $this->adminUser();
        $service = \App\Models\ServicePage::create([
            'name' => 'Mantenimiento de Chillers', 'slug' => 'mantenimiento-chillers',
            'page_type' => \App\Models\ServicePage::TYPE_SERVICE, 'is_active' => true,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.service-pages.live-editor', $service->id));

        $res->assertOk();
        $res->assertSee('leGalleryBtn', false);
        $res->assertSee('leReviewsBtn', false);
        $res->assertDontSee('leProductsBtn', false);
        $res->assertSee('Bloque de Marcas', false);
    }
}
