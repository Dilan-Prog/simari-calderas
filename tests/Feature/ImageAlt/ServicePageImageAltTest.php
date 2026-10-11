<?php

namespace Tests\Feature\ImageAlt;

use App\Models\Role;
use App\Models\ServicePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ALT de imágenes en Páginas de Servicio: portada, imagen OG, galería y
 * bloques; guardado desde el editor en vivo y render en el sitio público.
 */
class ServicePageImageAltTest extends TestCase
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

    private function makePage(array $over = []): ServicePage
    {
        return ServicePage::create(array_merge([
            'name' => 'Mantenimiento de calderas', 'slug' => 'mantenimiento-calderas',
            'page_type' => ServicePage::TYPE_SERVICE, 'currency' => 'MXN', 'is_active' => true,
        ], $over));
    }

    private function generalPayload(ServicePage $page, array $over = []): array
    {
        return array_merge([
            'name' => $page->name,
            'slug' => $page->slug,
            'page_type' => ServicePage::TYPE_SERVICE,
            'sort_order' => 0, 'short_description' => '', 'price' => '', 'currency' => 'MXN',
            'background_color' => '', 'seo_title' => '', 'seo_description' => '',
            'is_canonical' => true,
        ], $over);
    }

    public function test_guarda_alt_de_portada_y_og_desde_informacion_general(): void
    {
        $page = $this->makePage();

        $res = $this->actingAs($this->adminUser())
            ->putJson(route('admin.service-pages.update-general', $page), $this->generalPayload($page, [
                'cover_image_alt' => '  Técnico revisando caldera  ',
                'og_image_url' => 'uploads/og.jpg',
                'og_image_alt' => ' Caldera industrial ',
            ]))
            ->assertOk()
            ->assertJsonPath('servicePage.cover_image_alt', 'Técnico revisando caldera')
            ->assertJsonPath('servicePage.og_image_url', 'uploads/og.jpg')
            ->assertJsonPath('servicePage.og_image_alt', 'Caldera industrial');

        $fresh = $page->fresh();
        $this->assertSame('Técnico revisando caldera', $fresh->cover_image_alt);
        $this->assertSame('Caldera industrial', $fresh->og_image_alt);
    }

    public function test_og_alt_se_anula_si_no_hay_imagen_og(): void
    {
        $page = $this->makePage(['og_image_url' => 'uploads/og.jpg', 'og_image_alt' => 'Viejo']);

        $this->actingAs($this->adminUser())
            ->putJson(route('admin.service-pages.update-general', $page), $this->generalPayload($page, [
                'og_image_url' => '',
                'og_image_alt' => 'Huérfano',
            ]))
            ->assertOk()
            ->assertJsonPath('servicePage.og_image_alt', null);

        $this->assertNull($page->fresh()->og_image_alt);
        $this->assertNull($page->fresh()->og_image_url);
    }

    public function test_alts_largos_se_rechazan(): void
    {
        $page = $this->makePage();

        $this->actingAs($this->adminUser())
            ->putJson(route('admin.service-pages.update-general', $page), $this->generalPayload($page, [
                'cover_image_alt' => str_repeat('a', 256),
                'og_image_url' => 'uploads/og.jpg',
                'og_image_alt' => str_repeat('b', 256),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cover_image_alt', 'og_image_alt']);
    }

    public function test_alt_de_galeria_se_guarda_con_trim_y_validacion(): void
    {
        $page = $this->makePage();
        $admin = $this->adminUser();
        $image = $page->images()->create(['image_url' => 'uploads/a.jpg', 'alt_text' => null, 'sort_order' => 0]);

        $this->actingAs($admin)
            ->putJson(route('admin.service-pages.images.update', [$page, $image]), ['alt_text' => '  Vista frontal  '])
            ->assertOk()
            ->assertJsonPath('image.alt_text', 'Vista frontal');
        $this->assertSame('Vista frontal', $image->fresh()->alt_text);

        $this->actingAs($admin)
            ->putJson(route('admin.service-pages.images.update', [$page, $image]), ['alt_text' => str_repeat('x', 256)])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->putJson(route('admin.service-pages.images.update', [$page, $image]), ['alt_text' => ''])
            ->assertOk();
        $this->assertNull($image->fresh()->alt_text);
    }

    public function test_alt_de_galeria_requiere_permiso_de_edicion(): void
    {
        $page = $this->makePage();
        $image = $page->images()->create(['image_url' => 'uploads/a.jpg', 'sort_order' => 0]);

        $this->putJson(route('admin.service-pages.images.update', [$page, $image]), ['alt_text' => 'x'])
            ->assertStatus(401);
    }

    public function test_publico_renderiza_alt_de_portada_og_y_galeria(): void
    {
        $page = $this->makePage([
            'cover_image_alt' => 'Portada con ALT propio',
            'og_image_url' => 'https://example.com/og.jpg',
            'og_image_alt' => 'ALT de la imagen OG',
            'cover_image_url' => 'https://example.com/cover.jpg',
        ]);
        $img = $page->images()->create(['image_url' => 'https://example.com/g1.jpg', 'alt_text' => 'Alt galería uno', 'sort_order' => 0]);
        $page->sections()->create([
            'type' => 'gallery_carousel', 'title' => 'Galería', 'config' => ['image_ids' => [$img->id]],
            'sort_order' => 0, 'short_description' => '', 'price' => '', 'currency' => 'MXN',
            'background_color' => '', 'seo_title' => '', 'seo_description' => '', 'is_active' => true,
        ]);
        $page->sections()->create([
            'type' => 'content_tabs', 'title' => 'Tabs',
            'config' => ['tabs' => [
                ['label' => 'Uno', 'body' => 'x', 'image_id' => $img->id, 'image_alt' => 'ALT de pestaña'],
                ['label' => 'Dos', 'body' => 'y', 'image_id' => $img->id],
            ]],
            'sort_order' => 1, 'is_active' => true,
        ]);
        $page->sections()->create([
            'type' => 'brand_logos', 'title' => 'Marcas',
            'config' => ['logos' => [
                ['url' => 'https://example.com/l1.png', 'alt' => 'Marca Uno'],
                ['url' => 'https://example.com/l2.png', 'alt' => ''],
            ]],
            'sort_order' => 2, 'is_active' => true,
        ]);

        $res = $this->get('/servicio/' . $page->slug)->assertOk();

        $res->assertSee('<img src="https://example.com/cover.jpg" alt="Portada con ALT propio">', false);
        $res->assertSee('property="og:image:alt"   content="ALT de la imagen OG"', false);
        $res->assertSee('alt="Alt galería uno"', false);
        $res->assertSee('alt="ALT de pestaña"', false);
        $res->assertSee('alt="Marca Uno"', false);
        // Sin ALT propio: pestaña -> ALT de la galería; logo -> nombre del servicio.
        $res->assertSee('<img src="https://example.com/g1.jpg" alt="Alt galería uno">', false);
        $res->assertSee('<img src="https://example.com/l2.png" alt="Mantenimiento de calderas"', false);
        // JSON-LD intacto.
        $res->assertSee('application/ld+json', false);
    }

    public function test_publico_usa_fallbacks_sin_alts(): void
    {
        $page = $this->makePage(['cover_image_url' => 'https://example.com/cover.jpg']);

        $res = $this->get('/servicio/' . $page->slug)->assertOk();

        $res->assertSee('<img src="https://example.com/cover.jpg" alt="Mantenimiento de calderas">', false);
        $res->assertSee('content="Mantenimiento de calderas — Equiterm Industries"', false);
    }

    public function test_og_alt_cae_al_alt_de_portada_si_no_hay_imagen_og_propia(): void
    {
        $page = $this->makePage([
            'cover_image_url' => 'https://example.com/cover.jpg',
            'cover_image_alt' => 'ALT portada para OG',
        ]);

        $this->get('/servicio/' . $page->slug)
            ->assertOk()
            ->assertSee('property="og:image:alt"   content="ALT portada para OG"', false);
    }
}
