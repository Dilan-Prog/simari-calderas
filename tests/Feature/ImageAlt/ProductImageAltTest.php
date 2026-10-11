<?php

namespace Tests\Feature\ImageAlt;

use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * ALT de las imágenes de un producto: galería (product_images.alt_text),
 * portada (cover_image_alt) e imagen Open Graph (og_image_alt).
 *
 * Regla portada <-> galería (ProductController::syncCoverAlt): la portada es la
 * primera imagen de la galería; si llega cover_image_alt con texto gana y se
 * copia al alt_text de la primera imagen; si llega vacío y la primera imagen
 * tiene alt_text, cover_image_alt se rellena con él.
 */
class ProductImageAltTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function brand(): Brand
    {
        return Brand::create(['name' => 'Marca ' . uniqid(), 'slug' => 'marca-' . uniqid(), 'is_active' => true]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'name'        => 'Caldera Industrial',
            'sku'         => 'SKU-' . uniqid(),
            'category_id' => $this->makeCategory()->id,
            'brand_id'    => $this->brand()->id,
            'price'       => 1000,
        ], $over);
    }

    private function addImage(Products $p, string $url, ?string $alt, int $sort): ProductImage
    {
        return ProductImage::create([
            'product_id' => $p->id, 'image_url' => $url, 'alt_text' => $alt, 'sort_order' => $sort,
        ]);
    }

    // ── Admin: store ────────────────────────────────────────────────────

    public function test_store_guarda_alt_de_galeria_portada_y_og(): void
    {
        $admin = $this->adminUser();
        $seed = $this->makeProduct();
        $a = $this->addImage($seed, 'https://cdn.test/a.jpg', null, 0);
        $b = $this->addImage($seed, 'https://cdn.test/b.jpg', null, 1);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'existing_image_ids'  => [$a->id, $b->id],
            'image_source_order'  => ['existing', 'existing'],
            'image_alts'          => ['  Vista frontal  ', 'Vista lateral'],
            'cover_image_alt'     => '',
            'og_image'            => 'https://cdn.test/og.jpg',
            'og_image_alt'        => '  Caldera en planta  ',
        ]))->assertSessionHasNoErrors();

        $p = Products::where('name', 'Caldera Industrial')->firstOrFail();
        $imgs = $p->images()->get();

        $this->assertSame(['Vista frontal', 'Vista lateral'], $imgs->pluck('alt_text')->all());
        // Portada vacía + primera imagen con ALT -> la portada lo hereda.
        $this->assertSame('Vista frontal', $p->cover_image_alt);
        $this->assertSame('Caldera en planta', $p->og_image_alt);
    }

    public function test_store_portada_con_texto_gana_sobre_la_primera_imagen(): void
    {
        $admin = $this->adminUser();
        $seed = $this->makeProduct();
        $a = $this->addImage($seed, 'https://cdn.test/a.jpg', null, 0);
        $b = $this->addImage($seed, 'https://cdn.test/b.jpg', null, 1);

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'existing_image_ids' => [$a->id, $b->id],
            'image_source_order' => ['existing', 'existing'],
            'image_alts'         => ['otro texto', 'segunda'],
            'cover_image_alt'    => 'Portada oficial',
        ]))->assertSessionHasNoErrors();

        $p = Products::where('name', 'Caldera Industrial')->firstOrFail();
        $this->assertSame('Portada oficial', $p->cover_image_alt);
        $this->assertSame(['Portada oficial', 'segunda'], $p->images()->pluck('alt_text')->all());
    }

    public function test_store_og_alt_se_guarda_null_si_no_hay_imagen_og(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'og_image'     => '',
            'og_image_alt' => 'Texto huérfano',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Products::where('name', 'Caldera Industrial')->firstOrFail()->og_image_alt);
    }

    public function test_store_valida_longitud_maxima_de_los_alt(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'og_image'        => 'https://cdn.test/og.jpg',
            'og_image_alt'    => str_repeat('a', 256),
            'cover_image_alt' => str_repeat('b', 256),
            'image_alts'      => [str_repeat('c', 256)],
        ]))->assertSessionHasErrors(['og_image_alt', 'cover_image_alt', 'image_alts.0']);
    }

    // ── Admin: update ───────────────────────────────────────────────────

    private function updatePayload(Products $p, array $over = []): array
    {
        return array_merge([
            'name'        => $p->name,
            'sku'         => $p->sku,
            'category_id' => $p->category_id,
            'brand_id'    => $p->brand_id ?: $this->brand()->id,
            'price'       => 100,
        ], $over);
    }

    public function test_update_edita_alt_de_imagenes_existentes_y_sincroniza_portada(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct();
        $first = $this->addImage($p, 'https://cdn.test/a.jpg', 'viejo', 0);
        $second = $this->addImage($p, 'https://cdn.test/b.jpg', null, 1);

        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $this->updatePayload($p, [
            'existing_alts'   => [$first->id => 'Nuevo ALT portada', $second->id => '  Segunda  '],
            'cover_image_alt' => 'Nuevo ALT portada',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Nuevo ALT portada', $first->fresh()->alt_text);
        $this->assertSame('Segunda', $second->fresh()->alt_text);
        $this->assertSame('Nuevo ALT portada', $p->fresh()->cover_image_alt);
    }

    public function test_update_cambiar_alt_de_la_portada_actualiza_la_primera_imagen(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct();
        $first = $this->addImage($p, 'https://cdn.test/a.jpg', 'viejo', 0);

        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $this->updatePayload($p, [
            'cover_image_alt' => 'Solo editó la portada',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Solo editó la portada', $first->fresh()->alt_text);
    }

    public function test_update_cambiar_alt_de_la_primera_imagen_sin_portada_actualiza_la_portada(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct();
        $first = $this->addImage($p, 'https://cdn.test/a.jpg', 'viejo', 0);

        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $this->updatePayload($p, [
            'existing_alts' => [$first->id => 'Editado en galería'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Editado en galería', $p->fresh()->cover_image_alt);
    }

    public function test_update_og_alt_se_borra_al_quitar_la_imagen_og(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct(['og_image' => 'https://cdn.test/og.jpg', 'og_image_alt' => 'Antes']);

        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $this->updatePayload($p, [
            'og_image' => '', 'og_image_alt' => 'Ya no aplica',
        ]))->assertSessionHasNoErrors();

        $fresh = $p->fresh();
        $this->assertNull($fresh->og_image);
        $this->assertNull($fresh->og_image_alt);
    }

    public function test_update_no_toca_alt_de_imagenes_de_otro_producto(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct();
        $other = $this->makeProduct();
        $foreign = $this->addImage($other, 'https://cdn.test/x.jpg', 'intacto', 0);

        $this->actingAs($admin)->put(route('admin.products.update', $p->id), $this->updatePayload($p, [
            'existing_alts' => [$foreign->id => 'hackeado'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('intacto', $foreign->fresh()->alt_text);
    }

    public function test_reordenar_imagenes_mueve_el_alt_de_la_portada_a_la_nueva_primera(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct(['cover_image_alt' => 'Alt A']);
        $a = $this->addImage($p, 'https://cdn.test/a.jpg', 'Alt A', 0);
        $b = $this->addImage($p, 'https://cdn.test/b.jpg', 'Alt B', 1);

        $this->actingAs($admin)->postJson(route('admin.products.images.reorder', $p->id), [
            'order' => [$b->id, $a->id],
        ])->assertOk()->assertJson(['success' => true, 'cover_image_alt' => 'Alt B']);

        $this->assertSame('Alt B', $p->fresh()->cover_image_alt);
    }

    public function test_formulario_de_edicion_muestra_los_campos_alt(): void
    {
        $admin = $this->adminUser();
        $p = $this->makeProduct(['og_image' => 'https://cdn.test/og.jpg', 'og_image_alt' => 'ALT OG guardado']);
        $img = $this->addImage($p, 'https://cdn.test/a.jpg', 'ALT galería guardado', 0);

        $html = $this->withoutVite()->actingAs($admin)
            ->get(route('admin.products.edit', $p->id))->assertOk()->getContent();

        $this->assertStringContainsString('name="existing_alts[' . $img->id . ']"', $html);
        $this->assertStringContainsString('ALT galería guardado', $html);
        $this->assertStringContainsString('name="cover_image_alt"', $html);
        $this->assertStringContainsString('name="og_image_alt"', $html);
        $this->assertStringContainsString('ALT OG guardado', $html);
    }

    public function test_formulario_de_alta_muestra_los_campos_alt(): void
    {
        $html = $this->withoutVite()->actingAs($this->adminUser())
            ->get(route('admin.products.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="cover_image_alt"', $html);
        $this->assertStringContainsString('name="og_image_alt"', $html);
        $this->assertStringContainsString('pformOgPreview', $html);
    }

    // ── Sitio público ───────────────────────────────────────────────────

    public function test_ficha_publica_usa_alt_guardado_y_og_alt(): void
    {
        $p = $this->makeProduct([
            'name' => 'Caldera Pública', 'og_image' => 'https://cdn.test/og.jpg',
            'og_image_alt' => 'ALT de la imagen OG', 'cover_image_alt' => 'ALT portada',
        ]);
        $this->addImage($p, 'https://cdn.test/a.jpg', 'ALT portada', 0);
        $this->addImage($p, 'https://cdn.test/b.jpg', 'ALT segunda', 1);

        $html = $this->get(route('product.show', $p->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<img src="https://cdn.test/a.jpg" alt="ALT portada">', $html);
        $this->assertStringContainsString('<img src="https://cdn.test/b.jpg" alt="ALT segunda">', $html);
        $this->assertMatchesRegularExpression('/<meta property="og:image:alt"\s+content="ALT de la imagen OG"/', $html);
        $this->assertMatchesRegularExpression('/<meta property="og:image"\s+content="https:\/\/cdn.test\/og.jpg"/', $html);
        // JSON-LD intacto
        $this->assertStringContainsString('"@type":"Product"', str_replace(' ', '', $html));
    }

    public function test_ficha_publica_cae_al_nombre_cuando_no_hay_alt(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera Sin Alt', 'og_image' => 'https://cdn.test/og.jpg']);
        $this->addImage($p, 'https://cdn.test/a.jpg', null, 0);
        $this->addImage($p, 'https://cdn.test/b.jpg', '   ', 1);

        $html = $this->get(route('product.show', $p->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<img src="https://cdn.test/a.jpg" alt="Caldera Sin Alt">', $html);
        $this->assertStringContainsString('<img src="https://cdn.test/b.jpg" alt="Caldera Sin Alt">', $html);
        $this->assertMatchesRegularExpression('/<meta property="og:image:alt"\s+content="Caldera Sin Alt"/', $html);
    }

    public function test_tarjeta_de_producto_usa_alt_de_portada_y_de_galeria(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera Tarjeta', 'cover_image_alt' => 'ALT tarjeta']);
        $this->addImage($p, 'https://cdn.test/a.jpg', 'ALT tarjeta', 0);
        $this->addImage($p, 'https://cdn.test/b.jpg', null, 1);

        $html = Blade::render('<x-frontend.shop.product-card :product="$product" />', ['product' => $p->fresh()]);

        $this->assertStringContainsString('src="https://cdn.test/a.jpg" alt="ALT tarjeta"', $html);
        $this->assertStringContainsString('src="https://cdn.test/b.jpg" alt="Caldera Tarjeta"', $html);
    }

    public function test_tarjeta_de_producto_con_una_imagen_cae_al_nombre(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera Una']);
        $this->addImage($p, 'https://cdn.test/a.jpg', null, 0);

        $html = Blade::render('<x-frontend.shop.product-card :product="$product" />', ['product' => $p->fresh()]);

        $this->assertStringContainsString('src="https://cdn.test/a.jpg" alt="Caldera Una"', $html);
    }
}
