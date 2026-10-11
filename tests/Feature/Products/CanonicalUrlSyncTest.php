<?php

namespace Tests\Feature\Products;

use App\Models\Products;
use App\Models\Redirect;
use App\Support\CanonicalUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Al cambiar el slug de un producto que es el CANÓNICO de otros, la
 * canonical_url guardada en esos otros se actualiza a la URL nueva (antes
 * quedaba apuntando al slug viejo). Caso real: se le quitó "-nde5gk" al slug.
 */
class CanonicalUrlSyncTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private const HOST = 'https://equitermindustries.com.mx';

    private function canonicalTarget(string $slug): Products
    {
        return $this->makeProduct(['slug' => $slug]);
    }

    /** Instancia cargada de BD, como en el editor real (la creada en la prueba conserva wasRecentlyCreated). */
    private function reload(Products $product): Products
    {
        return Products::findOrFail($product->id);
    }

    private function dependent(Products $target, array $over = []): Products
    {
        return $this->makeProduct(array_merge([
            'canonical_product_id' => $target->id,
            'canonical_url'        => self::HOST . '/producto/' . $target->slug,
        ], $over));
    }

    public function test_cambiar_el_slug_del_canonico_actualiza_a_los_ligados_conservando_el_host(): void
    {
        $target = $this->canonicalTarget('control-dc1010ct-101000-e-honeywell-nde5gk');
        $a = $this->dependent($target);
        $b = $this->dependent($target);
        $other = $this->makeProduct(['slug' => 'otro-producto']);

        $this->reload($target)->update(['slug' => 'control-dc1010ct-101000-e-honeywell']);

        $expected = self::HOST . '/producto/control-dc1010ct-101000-e-honeywell';
        $this->assertSame($expected, $a->fresh()->canonical_url);
        $this->assertSame($expected, $b->fresh()->canonical_url);
        $this->assertNull($other->fresh()->canonical_url);
        $this->assertSame($target->id, $a->fresh()->canonical_product_id);
    }

    public function test_actualiza_tambien_las_canonicas_de_texto_libre_que_apuntaban_a_la_url_vieja(): void
    {
        $target = $this->canonicalTarget('viejo-slug');
        $free = $this->makeProduct(['canonical_product_id' => null, 'canonical_url' => self::HOST . '/producto/viejo-slug']);
        $elsewhere = $this->makeProduct(['canonical_product_id' => null, 'canonical_url' => 'https://otro-sitio.com/producto/viejo-slug-2']);

        $this->reload($target)->update(['slug' => 'slug-nuevo']);

        $this->assertSame(self::HOST . '/producto/slug-nuevo', $free->fresh()->canonical_url);
        // Mismo prefijo de path pero OTRA URL: no se toca.
        $this->assertSame('https://otro-sitio.com/producto/viejo-slug-2', $elsewhere->fresh()->canonical_url);
    }

    public function test_se_actualizan_aunque_se_pida_no_redirigir_la_url_anterior(): void
    {
        $target = $this->canonicalTarget('slug-a');
        $dep = $this->dependent($target);

        $fresh = $this->reload($target);
        $fresh->redirectOldSlug = false;
        $fresh->update(['slug' => 'slug-b']);

        $this->assertSame(self::HOST . '/producto/slug-b', $dep->fresh()->canonical_url);
        $this->assertNull(Redirect::where('old_path', '/producto/slug-a')->first());
    }

    public function test_el_redirect_301_de_la_url_anterior_sigue_registrandose(): void
    {
        $target = $this->canonicalTarget('slug-viejo');
        $this->dependent($target);

        $this->reload($target)->update(['slug' => 'slug-nuevo']);

        $this->assertSame('/producto/slug-nuevo', Redirect::where('old_path', '/producto/slug-viejo')->value('new_path'));
    }

    public function test_guardar_sin_cambiar_el_slug_no_toca_a_los_dependientes(): void
    {
        $target = $this->canonicalTarget('slug-fijo');
        $dep = $this->dependent($target, ['canonical_url' => self::HOST . '/producto/loquesea']);

        $this->reload($target)->update(['name' => 'Otro nombre']);

        $this->assertSame(self::HOST . '/producto/loquesea', $dep->fresh()->canonical_url);
    }

    public function test_repair_all_corrige_los_ligados_ya_desfasados_y_respeta_dry_run(): void
    {
        $target = $this->canonicalTarget('slug-actual');
        // Desfasado: el destino cambió antes de existir este arreglo.
        $stale = $this->dependent($target, ['canonical_url' => self::HOST . '/producto/slug-actual-nde5gk']);
        $fine = $this->dependent($target);

        $dry = CanonicalUrls::repairAll(true);
        $this->assertSame(1, $dry['linked']);
        $this->assertSame(self::HOST . '/producto/slug-actual-nde5gk', $stale->fresh()->canonical_url);

        $real = CanonicalUrls::repairAll(false);
        $this->assertSame(1, $real['linked']);
        $this->assertSame(self::HOST . '/producto/slug-actual', $stale->fresh()->canonical_url);
        $this->assertSame(self::HOST . '/producto/slug-actual', $fine->fresh()->canonical_url);

        // Idempotente
        $this->assertSame(0, CanonicalUrls::repairAll(false)['linked']);
    }

    public function test_repair_all_sigue_las_redirecciones_para_canonicas_de_texto_libre(): void
    {
        $this->canonicalTarget('slug-final');
        Redirect::record('/producto/slug-final-xyz', '/producto/slug-final');
        $free = $this->makeProduct(['canonical_product_id' => null, 'canonical_url' => self::HOST . '/producto/slug-final-xyz']);

        $result = CanonicalUrls::repairAll(false);

        $this->assertSame(1, $result['redirected']);
        $this->assertSame(self::HOST . '/producto/slug-final', $free->fresh()->canonical_url);
    }

    public function test_el_comando_funciona_con_dry_run(): void
    {
        $target = $this->canonicalTarget('slug-cmd');
        $stale = $this->dependent($target, ['canonical_url' => self::HOST . '/producto/slug-cmd-old']);

        $this->artisan('products:sync-canonicals', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame(self::HOST . '/producto/slug-cmd-old', $stale->fresh()->canonical_url);

        $this->artisan('products:sync-canonicals')->assertExitCode(0);
        $this->assertSame(self::HOST . '/producto/slug-cmd', $stale->fresh()->canonical_url);
    }
}
