<?php

namespace Tests\Feature\ImageAlt;

use App\Models\Collection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ALT de la imagen principal / OG de Colecciones y de las imágenes de sus bloques. */
class CollectionImageAltTest extends TestCase
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

    public function test_modal_store_y_update_guardan_alts(): void
    {
        $admin = $this->adminUser();

        $res = $this->actingAs($admin)->postJson(route('admin.collections.store'), [
            'name' => 'Nueva', 'slug' => 'nueva', 'type' => 'manual',
            'image_url' => 'https://x.test/a.jpg', 'image_alt' => '  Caldera industrial  ',
            'og_image_url' => 'https://x.test/og.jpg', 'og_image_alt' => ' Vista OG ',
        ])->assertOk();

        $c = Collection::findOrFail($res->json('collection.id'));
        $this->assertSame('Caldera industrial', $c->image_alt);
        $this->assertSame('Vista OG', $c->og_image_alt);

        $this->actingAs($admin)->putJson(route('admin.collections.update', $c->id), [
            'name' => 'Nueva', 'slug' => 'nueva', 'type' => 'manual',
            'image_url' => 'https://x.test/a.jpg', 'image_alt' => 'Otro alt',
            'og_image_url' => '', 'og_image_alt' => 'Sin imagen OG',
        ])->assertOk();

        $c->refresh();
        $this->assertSame('Otro alt', $c->image_alt);
        $this->assertNull($c->og_image_alt, 'Sin og_image_url el ALT OG se descarta.');

        $this->actingAs($admin)->putJson(route('admin.collections.update', $c->id), [
            'name' => 'Nueva', 'slug' => 'nueva', 'type' => 'manual',
            'image_alt' => str_repeat('a', 256),
        ])->assertStatus(422)->assertJsonValidationErrors('image_alt');
    }

    public function test_live_editor_general_guarda_y_devuelve_alts(): void
    {
        $admin = $this->adminUser();
        $c = $this->makeCollection();

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.general', $c->id), [
            'name' => 'Calderas', 'slug' => 'calderas',
            'image_url' => 'https://x.test/a.jpg', 'image_alt' => ' Alt principal ',
            'og_image_url' => 'https://x.test/og.jpg', 'og_image_alt' => ' Alt OG ',
        ])->assertOk()
            ->assertJsonPath('collection.image_alt', 'Alt principal')
            ->assertJsonPath('collection.og_image_alt', 'Alt OG')
            ->assertJsonPath('collection.og_image_url', 'https://x.test/og.jpg');

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.general', $c->id), [
            'name' => 'Calderas', 'slug' => 'calderas',
            'image_url' => 'https://x.test/a.jpg', 'image_alt' => 'Alt principal',
            'og_image_alt' => 'huérfano',
        ])->assertOk()->assertJsonPath('collection.og_image_alt', null);

        $this->actingAs($admin)->putJson(route('admin.collections.live-editor.general', $c->id), [
            'name' => 'Calderas', 'slug' => 'calderas', 'og_image_alt' => str_repeat('a', 256),
            'og_image_url' => 'https://x.test/og.jpg',
        ])->assertStatus(422)->assertJsonValidationErrors('og_image_alt');

        $this->actingAs($admin)->get(route('admin.collections.live-editor', $c->id))
            ->assertOk()->assertSee('og_image_alt', false);
    }

    public function test_publico_renderiza_alt_hero_y_og(): void
    {
        $this->makeCollection([
            'image_url' => 'https://x.test/a.jpg', 'image_alt' => 'Caldera pirotubular',
            'og_image_url' => 'https://x.test/og.jpg', 'og_image_alt' => 'Planta con caldera',
        ]);

        $this->get('/coleccion/calderas')->assertOk()
            ->assertSee('alt="Caldera pirotubular"', false)
            ->assertSee('<meta property="og:image:alt"   content="Planta con caldera"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_publico_usa_fallbacks_de_alt(): void
    {
        $this->makeCollection(['image_url' => 'https://x.test/a.jpg', 'og_image_url' => 'https://x.test/og.jpg']);

        $this->get('/coleccion/calderas')->assertOk()
            ->assertSee('alt="Calderas"', false)
            ->assertSee('<meta property="og:image:alt"   content="Calderas"', false);
    }

    public function test_og_alt_cae_al_alt_principal_si_no_hay_alt_og(): void
    {
        $this->makeCollection(['image_url' => 'https://x.test/a.jpg', 'image_alt' => 'Alt principal']);

        $this->get('/coleccion/calderas')->assertOk()
            ->assertSee('<meta property="og:image:alt"   content="Alt principal"', false);
    }

    public function test_bloques_con_imagen_renderizan_su_alt(): void
    {
        $c = $this->makeCollection();
        $c->sections()->create([
            'type' => 'banner', 'title' => 'Titulo banner', 'sort_order' => 0, 'is_active' => true,
            'config' => ['image_url' => 'https://x.test/b.jpg', 'alt' => 'ALT-BANNER-X'],
        ]);
        $c->sections()->create([
            'type' => 'gallery_carousel', 'title' => 'Galeria', 'sort_order' => 1, 'is_active' => true,
            'config' => ['images' => [['url' => 'https://x.test/g.jpg', 'alt' => 'ALT-GALERIA-X'], ['url' => 'https://x.test/h.jpg', 'alt' => '']]],
        ]);
        $c->sections()->create([
            'type' => 'brand_logos', 'title' => 'Marcas', 'sort_order' => 2, 'is_active' => true,
            'config' => ['logos' => [['url' => 'https://x.test/l.png', 'alt' => 'ALT-LOGO-X', 'link_url' => '']]],
        ]);
        $c->sections()->create([
            'type' => 'dual_banner', 'title' => 'Dual', 'sort_order' => 3, 'is_active' => true,
            'config' => ['left' => ['image_url' => 'https://x.test/d1.jpg', 'alt' => 'ALT-DUAL-L'], 'right' => ['image_url' => 'https://x.test/d2.jpg']],
        ]);

        $this->get('/coleccion/calderas')->assertOk()
            ->assertSee('alt="ALT-BANNER-X"', false)
            ->assertSee('alt="ALT-GALERIA-X"', false)
            ->assertSee('alt="ALT-LOGO-X"', false)
            ->assertSee('alt="ALT-DUAL-L"', false)
            ->assertSee('src="https://x.test/d2.jpg" alt="Dual"', false)
            ->assertSee('src="https://x.test/h.jpg" alt="Galeria"', false);
    }
}
