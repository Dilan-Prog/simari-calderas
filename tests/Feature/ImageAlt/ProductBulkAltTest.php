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
 * Editor en lote de Productos: ALT de portada, ALT/URL de imagen OG, ALT de
 * la galería (product_images) y acción "Rellenar ALT vacíos".
 */
class ProductBulkAltTest extends TestCase
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

    public function test_guarda_alt_de_portada_y_de_og_con_trim(): void
    {
        $p = $this->makeProduct();

        $json = $this->save([
            ['id' => $p->id, 'field' => 'cover_image_alt', 'value' => '  Caldera industrial  '],
            ['id' => $p->id, 'field' => 'og_image_alt', 'value' => 'Caldera en planta'],
        ])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $p->id, 'cover_image_alt')['ok']);
        $this->assertTrue($this->resultFor($json, $p->id, 'og_image_alt')['ok']);
        $fresh = $p->fresh();
        $this->assertSame('Caldera industrial', $fresh->cover_image_alt);
        $this->assertSame('Caldera en planta', $fresh->og_image_alt);
    }

    public function test_rechaza_alt_de_mas_de_255_caracteres_y_vacio_guarda_null(): void
    {
        $p = $this->makeProduct();
        $p->cover_image_alt = 'Previo';
        $p->save();

        $json = $this->save([
            ['id' => $p->id, 'field' => 'og_image_alt', 'value' => str_repeat('a', 256)],
            ['id' => $p->id, 'field' => 'cover_image_alt', 'value' => '   '],
        ])->assertOk()->json();

        $this->assertFalse($this->resultFor($json, $p->id, 'og_image_alt')['ok']);
        $this->assertTrue($this->resultFor($json, $p->id, 'cover_image_alt')['ok']);
        $fresh = $p->fresh();
        $this->assertNull($fresh->og_image_alt);
        $this->assertNull($fresh->cover_image_alt);
    }

    public function test_og_image_valida_url(): void
    {
        $p = $this->makeProduct();

        $json = $this->save([
            ['id' => $p->id, 'field' => 'og_image', 'value' => 'no-es-url'],
        ])->assertOk()->json();
        $this->assertFalse($this->resultFor($json, $p->id, 'og_image')['ok']);
        $this->assertNull($p->fresh()->og_image);

        $json = $this->save([
            ['id' => $p->id, 'field' => 'og_image', 'value' => 'https://example.com/og.jpg'],
        ])->assertOk()->json();
        $this->assertTrue($this->resultFor($json, $p->id, 'og_image')['ok']);
        $this->assertSame('https://example.com/og.jpg', $p->fresh()->og_image);
    }

    public function test_guarda_alt_de_galeria(): void
    {
        $p = $this->makeProduct();
        $a = $this->image($p, null, 0);
        $b = $this->image($p, 'Viejo', 1);

        $json = $this->save([[
            'id' => $p->id,
            'field' => 'gallery_alts',
            'value' => [
                ['id' => $a->id, 'alt_text' => '  Vista frontal '],
                ['id' => $b->id, 'alt_text' => ''],
            ],
        ]])->assertOk()->json();

        $this->assertTrue($this->resultFor($json, $p->id, 'gallery_alts')['ok']);
        $this->assertSame('Vista frontal', $a->fresh()->alt_text);
        $this->assertNull($b->fresh()->alt_text);
    }

    public function test_rechaza_imagenes_ajenas_y_alt_demasiado_largo_sin_tocar_nada(): void
    {
        $p = $this->makeProduct();
        $other = $this->makeProduct();
        $mine = $this->image($p, 'Original');
        $foreign = $this->image($other, 'Ajena');

        $json = $this->save([[
            'id' => $p->id,
            'field' => 'gallery_alts',
            'value' => [
                ['id' => $mine->id, 'alt_text' => 'Nuevo'],
                ['id' => $foreign->id, 'alt_text' => 'Hackeado'],
            ],
        ]])->assertOk()->json();

        $this->assertFalse($this->resultFor($json, $p->id, 'gallery_alts')['ok']);
        $this->assertSame('Original', $mine->fresh()->alt_text);
        $this->assertSame('Ajena', $foreign->fresh()->alt_text);

        $json = $this->save([[
            'id' => $p->id,
            'field' => 'gallery_alts',
            'value' => [['id' => $mine->id, 'alt_text' => str_repeat('x', 256)]],
        ]])->assertOk()->json();
        $this->assertFalse($this->resultFor($json, $p->id, 'gallery_alts')['ok']);
        $this->assertSame('Original', $mine->fresh()->alt_text);
    }

    public function test_formato_invalido_de_galeria_se_rechaza(): void
    {
        $p = $this->makeProduct();

        $json = $this->save([['id' => $p->id, 'field' => 'gallery_alts', 'value' => 'no-json']])->assertOk()->json();

        $this->assertFalse($this->resultFor($json, $p->id, 'gallery_alts')['ok']);
    }

    public function test_rellenar_alt_vacios_no_sobrescribe(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera 50 HP']);
        $withAlt = $this->makeProduct(['name' => 'Otro producto']);
        $withAlt->cover_image_alt = 'ALT existente';
        $withAlt->save();

        $empty = $this->image($p, null, 0);
        $blank = $this->image($p, '   ', 1);
        $kept = $this->image($p, 'Se queda', 2);
        $keptOther = $this->image($withAlt, 'Intacto', 0);
        $notSelected = $this->makeProduct(['name' => 'No seleccionado']);

        $this->actingAs($this->adminUser())
            ->postJson(route('admin.products.bulk.fill-alt'), ['ids' => [$p->id, $withAlt->id]])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('cover_filled', 1)
            ->assertJsonPath('gallery_filled', 2);

        $this->assertSame('Caldera 50 HP', $p->fresh()->cover_image_alt);
        $this->assertSame('Caldera 50 HP', $empty->fresh()->alt_text);
        $this->assertSame('Caldera 50 HP', $blank->fresh()->alt_text);
        $this->assertSame('Se queda', $kept->fresh()->alt_text);
        $this->assertSame('ALT existente', $withAlt->fresh()->cover_image_alt);
        $this->assertSame('Intacto', $keptOther->fresh()->alt_text);
        $this->assertNull($notSelected->fresh()->cover_image_alt);
    }

    public function test_rellenar_alt_exige_ids(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('admin.products.bulk.fill-alt'), ['ids' => []])
            ->assertStatus(422);
    }

    public function test_la_pantalla_renderiza_las_columnas_nuevas(): void
    {
        $p = $this->makeProduct(['name' => 'Caldera Vista']);
        $p->cover_image_alt = 'ALT portada visible';
        $p->og_image_alt = 'ALT og visible';
        $p->save();
        $this->image($p, 'ALT galeria', 0);

        $html = $this->withoutVite()
            ->actingAs($this->adminUser())
            ->get(route('admin.products.bulk-edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ALT de portada', $html);
        $this->assertStringContainsString('ALT de imagen OG', $html);
        $this->assertStringContainsString('ALT de imágenes', $html);
        $this->assertStringContainsString('data-field="cover_image_alt"', $html);
        $this->assertStringContainsString('data-field="og_image_alt"', $html);
        $this->assertStringContainsString('data-field="og_image"', $html);
        $this->assertStringContainsString('data-field="gallery_alts"', $html);
        $this->assertStringContainsString('ALT portada visible', $html);
        $this->assertStringContainsString('ALT og visible', $html);
        $this->assertStringContainsString('ALT galeria', $html);
        $this->assertStringContainsString('prodBulkFillAltBtn', $html);
    }

    public function test_las_columnas_nuevas_se_pueden_guardar_en_una_vista(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('admin.products.bulk-edit.views.store'), [
                'name'    => 'ALT',
                'columns' => ['og_image', 'og_image_alt', 'cover_image_alt', 'gallery_alts'],
                'widths'  => ['cover_image_alt' => 220, 'gallery_alts' => 180],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_la_pantalla_no_hace_n_mas_1_en_la_galeria(): void
    {
        foreach (range(1, 4) as $i) {
            $p = $this->makeProduct();
            $this->image($p, 'a' . $i);
            $this->image($p, 'b' . $i, 1);
        }

        $admin = $this->adminUser();
        \DB::enableQueryLog();
        $this->withoutVite()->actingAs($admin)->get(route('admin.products.bulk-edit'))->assertOk();
        $imageQueries = collect(\DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], 'product_images'))
            ->count();

        $this->assertSame(1, $imageQueries);
    }
}
