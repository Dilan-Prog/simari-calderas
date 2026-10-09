<?php

namespace Tests\Feature\Catalog;

use App\Models\Products;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

/**
 * Renderiza <x-frontend.shop.product-card> y verifica el contrato del rediseño:
 * un solo botón de agregar, cotización como enlace con tracking, badges (máx. 2)
 * y precio anterior tachado sin "% OFF" duplicado.
 */
class ProductCardTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBadgeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function render(Products $product, bool $compact = false): string
    {
        return Blade::render(
            $compact
                ? '<x-frontend.shop.product-card :product="$product" compact="true" />'
                : '<x-frontend.shop.product-card :product="$product" />',
            ['product' => $product]
        );
    }

    private function xpath(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>');
        libxml_clear_errors();

        return new \DOMXPath($dom);
    }

    private function byClass(\DOMXPath $xp, string $class, string $tag = '*'): \DOMNodeList
    {
        return $xp->query("//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]");
    }

    public function test_tiene_un_solo_boton_agregar_con_sus_data_attributes(): void
    {
        $product = $this->makeProduct(['name' => 'Caldera Test', 'sku' => 'CAL-1', 'price' => 100]);
        $xp = $this->xpath($this->render($product));

        $buttons = $this->byClass($xp, 'product-card__add-btn');
        $this->assertSame(1, $buttons->length);
        $this->assertSame(1, $xp->query('//button')->length, 'ningún otro botón (sin galería)');

        $btn = $buttons->item(0);
        $this->assertSame('button', $btn->nodeName);
        $this->assertSame((string) $product->id, $btn->getAttribute('data-product-id'));
        $this->assertSame('CAL-1', $btn->getAttribute('data-sku'));
        $this->assertSame('Caldera Test', $btn->getAttribute('data-name'));
        $this->assertEquals(100, (float) $btn->getAttribute('data-price'));
        $this->assertSame('Agregar al carrito', trim($btn->textContent));
    }

    public function test_cotizacion_es_enlace_con_tracking_y_whatsapp(): void
    {
        $product = $this->makeProduct();
        $xp = $this->xpath($this->render($product));

        $links = $this->byClass($xp, 'product-card__quote-btn', 'a');
        $this->assertSame(1, $links->length);

        $a = $links->item(0);
        $this->assertStringStartsWith('https://wa.me/', $a->getAttribute('href'));
        $this->assertStringContainsString('text=', $a->getAttribute('href'));
        $this->assertSame('quote_start', $a->getAttribute('data-ad-track'));
        $this->assertSame((string) $product->id, $a->getAttribute('data-product-id'));
        $this->assertSame('_blank', $a->getAttribute('target'));
        $this->assertStringContainsString('noopener', $a->getAttribute('rel'));
        $this->assertStringContainsString('Solicitar cotización', $a->textContent);
    }

    public function test_muestra_maximo_dos_badges_con_colores_en_variables_css(): void
    {
        $product = $this->makeProduct(['compare_price' => 125, 'stock' => 2, 'is_new' => true]);
        $html = $this->render($product);
        $xp = $this->xpath($html);

        $this->assertSame(1, $this->byClass($xp, 'product-card__badges')->length);
        $badges = $this->byClass($xp, 'product-card__badge', 'span');
        $this->assertSame(2, $badges->length);

        $this->assertSame('-20%', trim($badges->item(0)->textContent));
        $this->assertSame('Últimas piezas', trim($badges->item(1)->textContent));
        $this->assertStringContainsString('--badge-bg: #C62828', $badges->item(0)->getAttribute('style'));
        $this->assertStringContainsString('--badge-fg: #FFFFFF', $badges->item(0)->getAttribute('style'));
        $this->assertStringNotContainsString('Nuevo', $html, 'la tercera etiqueta queda fuera');
    }

    public function test_sin_condiciones_no_renderiza_contenedor_de_badges(): void
    {
        $xp = $this->xpath($this->render($this->makeProduct()));

        $this->assertSame(0, $this->byClass($xp, 'product-card__badges')->length);
        $this->assertSame(0, $this->byClass($xp, 'product-card__badge')->length);
    }

    public function test_con_descuento_muestra_precio_anterior_tachado_junto_al_actual_sin_off_duplicado(): void
    {
        $product = $this->makeProduct(['price' => 100, 'compare_price' => 125]);
        $html = $this->render($product);
        $xp = $this->xpath($html);

        $original = $xp->query("//s[contains(concat(' ', normalize-space(@class), ' '), ' product-card__original ')]");
        $this->assertSame(1, $original->length);
        $this->assertStringContainsString('$125.00 MXN', $original->item(0)->textContent);

        // Junto al precio actual: mismo contenedor de la fila de precio.
        $row = $this->byClass($xp, 'product-card__price-row')->item(0);
        $this->assertStringContainsString('$100.00 MXN', $row->textContent);
        $this->assertStringContainsString('$125.00 MXN', $row->textContent);
        $this->assertStringContainsString('Precio + IVA', $row->textContent);

        $this->assertStringNotContainsString('% OFF', $html);
        $this->assertSame(0, $this->byClass($xp, 'product-card__discount')->length);
        $this->assertSame(1, substr_count($html, '-20%'), 'el porcentaje aparece solo en la etiqueta');
    }

    public function test_sin_descuento_real_no_hay_precio_tachado(): void
    {
        foreach ([null, 100, 80] as $compare) {
            $html = $this->render($this->makeProduct(['price' => 100, 'compare_price' => $compare]));

            $this->assertStringNotContainsString('<s ', $html, "compare_price = " . var_export($compare, true));
            $this->assertStringNotContainsString('% OFF', $html);
        }
    }

    public function test_conserva_precio_iva_envio_nombre_y_variante_compact(): void
    {
        $product = $this->makeProduct(['name' => 'Bomba Test', 'price' => 1234.5]);
        $html = $this->render($product, compact: true);
        $xp = $this->xpath($html);

        $this->assertSame(1, $this->byClass($xp, 'product-card--compact')->length);
        $this->assertStringContainsString('$1,234.50 MXN', $html);
        $this->assertStringContainsString('Precio + IVA', $html);
        $this->assertStringContainsString('Bomba Test', $html);
        $this->assertSame(1, $this->byClass($xp, 'product-card__shipping')->length);
        $this->assertSame(1, $this->byClass($xp, 'product-card__add-btn')->length);
    }
}
