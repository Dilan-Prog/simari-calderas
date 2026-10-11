<?php

namespace Tests\Feature\ImageAlt;

use App\Models\ProductImage;
use App\Models\Products;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Editor en lote de Productos: la barra "Aplicar a seleccionados" ofrece
 * TODAS las columnas aplicables (SEO/Social, ALT, specs, FAQ...) y el
 * servidor acepta/valida esos cambios (las plantillas con {variables} se
 * guardan literales).
 */
class ProductBulkApplyAllTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private ?User $admin = null;

    private function adminUser(): User
    {
        if ($this->admin) {
            return $this->admin;
        }
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return $this->admin = User::create([
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

    private function save(array $changes)
    {
        return $this->actingAs($this->adminUser())->postJson(route('admin.products.bulk.save'), ['changes' => $changes]);
    }

    private function resultFor(array $json, int $id, string $field): array
    {
        foreach ($json['results'] as $r) {
            if ($r['id'] === $id && $r['field'] === $field) {
                return $r;
            }
        }
        $this->fail("Sin resultado para {$id}/{$field}");
    }

    private function image(Products $p, ?string $alt = null, int $order = 0): ProductImage
    {
        return ProductImage::create([
            'product_id' => $p->id,
            'image_url'  => 'https://example.com/img-' . uniqid() . '.jpg',
            'alt_text'   => $alt,
            'sort_order' => $order,
        ]);
    }

    public function test_el_selector_de_aplicar_columna_ofrece_todas_las_columnas_nuevas(): void
    {
        $this->makeProduct();

        $html = $this->withoutVite()
            ->actingAs($this->adminUser())
            ->get(route('admin.products.bulk-edit'))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, preg_match('/<select id="prodBulkApplyColumnSelect">(.*?)<\/select>/s', $html, $m));
        preg_match_all('/<option value="([^"]+)"/', $m[1], $opts);
        $options = $opts[1];

        $nuevas = ['model', 'short_description', 'description', 'redirect_old_slug', 'seo_title', 'seo_description',
            'seo_keywords', 'og_title', 'og_description', 'og_image', 'og_image_alt', 'cover_image_alt',
            'specifications', 'faqs', 'gallery_alts'];
        foreach ($nuevas as $key) {
            $this->assertContains($key, $options, "Falta {$key} en el selector");
        }
        // Las que ya existían siguen.
        foreach (['price', 'stock', 'category_id', 'brand_id', 'tags', 'canonical', 'supplier_id'] as $key) {
            $this->assertContains($key, $options);
        }
        // Slug (único por producto) y subniveles de categoría no se ofrecen.
        foreach (['slug', 'name', 'category_sub', 'category_child'] as $key) {
            $this->assertNotContains($key, $options);
        }

        // Datos para JS: tipo y maxlength de cada columna.
        $this->assertSame(1, preg_match('/window\.PROD_BULK_APPLY_COLUMNS = (\[.*?\]);/s', $html, $j));
        $cols = collect(json_decode($j[1], true))->keyBy('key');
        $this->assertSame('text', $cols['seo_title']['type']);
        $this->assertSame(60, $cols['seo_title']['maxlength']);
        $this->assertSame('textarea', $cols['seo_description']['type']);
        $this->assertSame(160, $cols['seo_description']['maxlength']);
        $this->assertSame('specs', $cols['specifications']['type']);
        $this->assertSame('faq', $cols['faqs']['type']);
        $this->assertSame('gallery-alts', $cols['gallery_alts']['type']);
    }

    public function test_plantillas_con_variables_se_guardan_literales_en_varios_productos(): void
    {
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $values = [
            'model'             => '{modelo} X',
            'short_description' => '{marca} {modelo} en {categoria}',
            'description'       => 'Descripción larga de {nombre_producto} ({anio_actual})',
            'seo_title'         => '{marca} {modelo} | Equiterm',
            'seo_description'   => 'Compra {nombre_producto} de {marca}',
            'seo_keywords'      => '{marca}, {modelo}, calderas',
            'og_title'          => '{nombre_producto} - {marca}',
            'og_description'    => 'OG de {nombre_producto}',
            'og_image_alt'      => '{nombre_producto} vista frontal',
            'cover_image_alt'   => '{marca} {modelo}',
        ];
        $changes = [];
        foreach ([$a, $b] as $p) {
            foreach ($values as $field => $value) {
                $changes[] = ['id' => $p->id, 'field' => $field, 'value' => $value];
            }
        }

        $json = $this->save($changes)->assertOk()->json();

        foreach ([$a, $b] as $p) {
            $fresh = $p->fresh();
            foreach ($values as $field => $value) {
                $this->assertTrue($this->resultFor($json, $p->id, $field)['ok'], "{$field} rechazado");
                $this->assertSame($value, $fresh->{$field}, "{$field} no quedó literal");
            }
        }
    }

    public function test_og_image_acepta_url_pero_no_plantillas(): void
    {
        $p = $this->makeProduct();

        $json = $this->save([
            ['id' => $p->id, 'field' => 'og_image', 'value' => 'https://example.com/og.jpg'],
        ])->assertOk()->json();
        $this->assertTrue($this->resultFor($json, $p->id, 'og_image')['ok']);
        $this->assertSame('https://example.com/og.jpg', $p->fresh()->og_image);

        $json = $this->save([
            ['id' => $p->id, 'field' => 'og_image', 'value' => '{marca}/og.jpg'],
        ])->assertOk()->json();
        $this->assertFalse($this->resultFor($json, $p->id, 'og_image')['ok']);
        $this->assertSame('https://example.com/og.jpg', $p->fresh()->og_image);
    }

    public function test_los_limites_de_longitud_se_rechazan(): void
    {
        $p = $this->makeProduct(['seo_title' => 'Original']);

        $json = $this->save([
            ['id' => $p->id, 'field' => 'seo_title', 'value' => str_repeat('a', 61)],
            ['id' => $p->id, 'field' => 'seo_description', 'value' => str_repeat('a', 161)],
            ['id' => $p->id, 'field' => 'seo_keywords', 'value' => str_repeat('a', 256)],
            ['id' => $p->id, 'field' => 'model', 'value' => str_repeat('a', 101)],
            ['id' => $p->id, 'field' => 'short_description', 'value' => str_repeat('a', 501)],
            ['id' => $p->id, 'field' => 'cover_image_alt', 'value' => str_repeat('a', 256)],
        ])->assertOk()->json();

        foreach (['seo_title', 'seo_description', 'seo_keywords', 'model', 'short_description', 'cover_image_alt'] as $field) {
            $this->assertFalse($this->resultFor($json, $p->id, $field)['ok'], "{$field} debió rechazarse");
        }
        $this->assertSame('Original', $p->fresh()->seo_title);

        // En el límite exacto sí pasa.
        $json = $this->save([
            ['id' => $p->id, 'field' => 'seo_title', 'value' => str_repeat('a', 60)],
        ])->assertOk()->json();
        $this->assertTrue($this->resultFor($json, $p->id, 'seo_title')['ok']);
    }

    public function test_specs_y_faqs_se_aplican_a_varios_productos_en_un_guardado(): void
    {
        $a = $this->makeProduct();
        $b = $this->makeProduct();

        $specs = json_encode([
            ['key' => 'Potencia', 'value' => '100 kW'],
            ['key' => 'Garantía', 'value' => '{anio_actual}'],
        ]);
        $faqs = json_encode([
            ['question' => '¿Incluye instalación de {marca}?', 'answer' => 'Sí, para {nombre_producto}.'],
        ]);

        $changes = [];
        foreach ([$a, $b] as $p) {
            $changes[] = ['id' => $p->id, 'field' => 'specifications', 'value' => $specs];
            $changes[] = ['id' => $p->id, 'field' => 'faqs', 'value' => $faqs];
        }
        $json = $this->save($changes)->assertOk()->json();

        foreach ([$a, $b] as $p) {
            $this->assertTrue($this->resultFor($json, $p->id, 'specifications')['ok']);
            $this->assertTrue($this->resultFor($json, $p->id, 'faqs')['ok']);
            $fresh = $p->fresh();
            $this->assertSame(
                [['key' => 'Potencia', 'value' => '100 kW'], ['key' => 'Garantía', 'value' => '{anio_actual}']],
                json_decode($fresh->specifications, true)
            );
            $this->assertSame('¿Incluye instalación de {marca}?', $fresh->faqs[0]['question']);
        }
    }

    public function test_gallery_alts_se_aplican_a_varios_productos_y_rechazan_imagenes_ajenas(): void
    {
        $a = $this->makeProduct();
        $b = $this->makeProduct();
        $a1 = $this->image($a, null, 0);
        $a2 = $this->image($a, 'ALT existente', 1);
        $b1 = $this->image($b, null, 0);

        $json = $this->save([
            ['id' => $a->id, 'field' => 'gallery_alts', 'value' => [['id' => $a1->id, 'alt_text' => '{marca} {modelo}']]],
            ['id' => $b->id, 'field' => 'gallery_alts', 'value' => [['id' => $b1->id, 'alt_text' => '{marca} {modelo}']]],
        ])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $a->id, 'gallery_alts')['ok']);
        $this->assertTrue($this->resultFor($json, $b->id, 'gallery_alts')['ok']);
        $this->assertSame('{marca} {modelo}', $a1->fresh()->alt_text);
        $this->assertSame('ALT existente', $a2->fresh()->alt_text, 'No tocar las imágenes no enviadas');
        $this->assertSame('{marca} {modelo}', $b1->fresh()->alt_text);

        // Una imagen de otro producto invalida el cambio.
        $json = $this->save([
            ['id' => $a->id, 'field' => 'gallery_alts', 'value' => [['id' => $b1->id, 'alt_text' => 'Intruso']]],
        ])->assertOk()->json();
        $this->assertFalse($this->resultFor($json, $a->id, 'gallery_alts')['ok']);
        $this->assertSame('{marca} {modelo}', $b1->fresh()->alt_text);
    }

    public function test_el_alt_publico_resuelve_las_variables_guardadas(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera Uno', 'model' => 'CU-9']);
        $p->cover_image_alt = '{nombre_producto} frontal';
        $p->og_image_alt = '{modelo} social';
        $p->save();
        $img = $this->image($p, 'Vista de {modelo}', 1);

        $fresh = $p->fresh();
        $this->assertSame('Caldera Uno frontal', $fresh->imageAlt(null, true));
        $this->assertSame('CU-9 social', $fresh->ogImageAlt());
        $this->assertSame('Vista de CU-9', $fresh->imageAlt($img));
    }
}
