<?php

namespace Tests\Feature\Products;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Ficha de producto: junto al estado/SKU se muestran las etiquetas manuales
 * (Destacado, Nuevo, Recomendado) y "Más de 100 vendidos" queda sin mostrarse.
 */
class ProductFlagBadgesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private function topbar(string $html): string
    {
        preg_match('/<div class="product-price-box__eyebrow.*?<\/div>/s', $html, $m);
        $this->assertNotEmpty($m, 'No se encontró la barra de estado/SKU');

        return $m[0];
    }

    public function test_solo_se_muestra_nuevo_y_destacado_y_recomendado_quedan_ocultos(): void
    {
        $p = $this->makeProduct(['is_featured' => true, 'is_new' => true, 'is_recommended' => true]);

        $bar = $this->topbar($this->get(route('product.show', $p->slug))->assertOk()->getContent());

        $this->assertStringContainsString('SKU ' . $p->sku, $bar);
        $this->assertStringContainsString('Nuevo', $bar);
        $this->assertStringNotContainsString('Destacado', $bar);
        $this->assertStringNotContainsString('Recomendado', $bar);
    }

    public function test_un_producto_destacado_sin_nuevo_no_muestra_etiquetas(): void
    {
        $p = $this->makeProduct(['is_featured' => true, 'is_new' => false, 'is_recommended' => true]);

        $bar = $this->topbar($this->get(route('product.show', $p->slug))->assertOk()->getContent());

        $this->assertStringNotContainsString('product-price-box__flag', $bar);
    }

    public function test_sin_etiquetas_no_agrega_nada(): void
    {
        $p = $this->makeProduct(['is_featured' => false, 'is_new' => false, 'is_recommended' => false]);

        $bar = $this->topbar($this->get(route('product.show', $p->slug))->assertOk()->getContent());

        $this->assertStringNotContainsString('product-price-box__flag', $bar);
    }

    public function test_mas_de_100_vendidos_no_se_muestra(): void
    {
        $p = $this->makeProduct(['is_featured' => true, 'is_new' => true, 'is_recommended' => true]);

        $html = $this->get(route('product.show', $p->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('Más de 100 vendidos', $html);
        $this->assertStringNotContainsString('flag--sold', $html);
    }
}
