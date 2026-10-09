<?php

namespace Tests\Feature\Catalog;

use App\Models\Products;
use App\Services\Catalog\BadgeResolver;
use App\Services\Catalog\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesBadgeFixtures;
use Tests\TestCase;

class BadgeResolverTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBadgeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetCatalogState();
    }

    private function keys(Products $product): array
    {
        return array_column(BadgeResolver::for($product), 'key');
    }

    // ── Máximo 2 y prioridad ───────────────────────────────────────────────

    public function test_maximo_dos_etiquetas_y_prioridad_best_seller_descuento_ultimas_nuevo(): void
    {
        ['winner' => $winner] = $this->seedBestSellerCategory();
        $winner->forceFill(['compare_price' => 125, 'stock' => 2, 'is_new' => true])->save();
        $winner->refresh();

        // Cumple las 4 condiciones: solo las 2 primeras por prioridad.
        $this->assertSame(['best_seller', 'discount'], $this->keys($winner));
    }

    public function test_sin_best_seller_el_orden_es_descuento_ultimas_piezas_y_corta_en_dos(): void
    {
        $product = $this->makeProduct(['compare_price' => 125, 'stock' => 2, 'is_new' => true]);

        $this->assertSame(['discount', 'last_units'], $this->keys($product));
    }

    public function test_ultimas_piezas_pasa_a_nuevo_si_no_hay_descuento(): void
    {
        $product = $this->makeProduct(['stock' => 2, 'is_new' => true]);

        $this->assertSame(['last_units', 'new'], $this->keys($product));
    }

    public function test_producto_sin_condiciones_no_tiene_etiquetas(): void
    {
        $this->assertSame([], BadgeResolver::for($this->makeProduct()));
    }

    public function test_estructura_de_cada_etiqueta(): void
    {
        $badge = BadgeResolver::for($this->makeProduct(['is_new' => true]))[0];

        $this->assertSame(['key', 'label', 'bg', 'fg'], array_keys($badge));
        $this->assertSame('new', $badge['key']);
        $this->assertSame('Nuevo', $badge['label']);
        $this->assertMatchesRegularExpression('/^#[0-9A-F]{6}$/', $badge['bg']);
        $this->assertContains($badge['fg'], ['#FFFFFF', '#000000']);
    }

    // ── Descuento ──────────────────────────────────────────────────────────

    public function test_descuento_muestra_menos_x_por_ciento_solo_con_precio_anterior_real(): void
    {
        $this->assertSame('-20%', BadgeResolver::for($this->makeProduct(['compare_price' => 125]))[0]['label']);

        $this->assertSame([], $this->keys($this->makeProduct(['compare_price' => null])), 'sin precio anterior');
        $this->assertSame([], $this->keys($this->makeProduct(['compare_price' => 100])), 'precio anterior igual');
        $this->assertSame([], $this->keys($this->makeProduct(['compare_price' => 80])), 'precio anterior menor (datos invertidos)');
    }

    public function test_descuento_respeta_el_minimo_configurado(): void
    {
        $product = $this->makeProduct(['compare_price' => 125]); // 20 %

        $this->setCatalog('discount_min_percent', 20);
        $this->assertSame(['discount'], $this->keys($product), 'X == minimo califica');

        $this->setCatalog('discount_min_percent', 21);
        $this->assertSame([], $this->keys($product), 'X < minimo no califica');
    }

    public function test_descuento_que_redondea_a_cero_no_genera_menos_cero_por_ciento(): void
    {
        $this->setCatalog('discount_min_percent', 0);

        // 100 vs 100.2 -> 0.2 % => redondea a 0 => sin etiqueta.
        $this->assertSame([], $this->keys($this->makeProduct(['compare_price' => 100.2])));
    }

    public function test_descuento_compara_en_mxn_sin_iva(): void
    {
        // Ambos en USD: la proporción se conserva tras la conversión.
        $product = $this->makeProduct(['currency' => 'USD', 'price' => 10, 'compare_price' => 12.5]);

        $this->assertSame('-20%', BadgeResolver::for($product)[0]['label']);
    }

    // ── Últimas piezas ─────────────────────────────────────────────────────

    public function test_ultimas_piezas_por_umbral_de_stock_y_disponibilidad(): void
    {
        $this->assertSame(['last_units'], $this->keys($this->makeProduct(['stock' => 1])));
        $this->assertSame(['last_units'], $this->keys($this->makeProduct(['stock' => 3])), 'stock == umbral');
        $this->assertSame([], $this->keys($this->makeProduct(['stock' => 4])), 'stock > umbral');
        $this->assertSame([], $this->keys($this->makeProduct(['stock' => 0])), 'sin stock');
        $this->assertSame([], $this->keys($this->makeProduct(['stock' => 2, 'availability' => 'on_order'])));
        $this->assertSame([], $this->keys($this->makeProduct(['stock' => 2, 'availability' => 'out_of_stock'])));
    }

    public function test_ultimas_piezas_respeta_el_umbral_configurado(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $this->assertSame([], $this->keys($product));

        $this->setCatalog('last_units_threshold', 5);
        $this->assertSame(['last_units'], $this->keys($product));
    }

    // ── Nuevo ──────────────────────────────────────────────────────────────

    public function test_nuevo_por_bandera_is_new(): void
    {
        $this->assertSame(['new'], $this->keys($this->makeProduct(['is_new' => true])));
    }

    public function test_nuevo_por_dias_solo_si_new_days_mayor_a_cero(): void
    {
        $recent = $this->makeProduct(['created_at' => now()->subDays(5)]);
        $old = $this->makeProduct(['created_at' => now()->subDays(20)]);

        $this->assertSame([], $this->keys($recent), 'new_days = 0 solo usa is_new');

        $this->setCatalog('new_days', 10);
        $this->assertSame(['new'], $this->keys($recent));
        $this->assertSame([], $this->keys($old));
    }

    // ── Etiquetas apagadas ─────────────────────────────────────────────────

    public function test_badge_enabled_apagado_oculta_esa_etiqueta_y_deja_pasar_la_siguiente(): void
    {
        ['winner' => $winner] = $this->seedBestSellerCategory();
        $winner->forceFill(['compare_price' => 125, 'stock' => 2, 'is_new' => true])->save();
        $winner->refresh();

        $this->setCatalog('badge_enabled_best_seller', false);
        $this->assertSame(['discount', 'last_units'], $this->keys($winner));

        $this->setCatalog('badge_enabled_discount', false);
        $this->assertSame(['last_units', 'new'], $this->keys($winner));

        $this->setCatalog('badge_enabled_last_units', false);
        $this->assertSame(['new'], $this->keys($winner));

        $this->setCatalog('badge_enabled_new', false);
        $this->assertSame([], $this->keys($winner));
    }

    // ── Más vendido ────────────────────────────────────────────────────────

    public function test_mas_vendido_solo_el_top_de_la_categoria(): void
    {
        ['winner' => $winner, 'others' => $others] = $this->seedBestSellerCategory(10);

        $this->assertSame(['best_seller'], $this->keys($winner));
        $this->assertSame([], $this->keys($others[0]), 'segundo lugar no entra en el top 10 % de 10');
        $this->assertSame([$winner->id], BadgeResolver::bestSellerIds());
    }

    public function test_mas_vendido_k_crece_con_el_tamano_de_la_categoria(): void
    {
        // N = 20 => k = ceil(20 * 10 / 100) = 2.
        ['winner' => $winner, 'others' => $others] = $this->seedBestSellerCategory(20);

        $this->assertEqualsCanonicalizing([$winner->id, $others[0]->id], BadgeResolver::bestSellerIds());
    }

    public function test_mas_vendido_k_minimo_es_uno(): void
    {
        // N = 3 => ceil(0.3) = 1.
        ['winner' => $winner] = $this->seedBestSellerCategory(3);

        $this->assertSame([$winner->id], BadgeResolver::bestSellerIds());
    }

    public function test_una_sola_unidad_vendida_no_genera_etiqueta(): void
    {
        $category = $this->makeCategory();
        $products = [];
        for ($i = 0; $i < 5; $i++) {
            $products[] = $this->makeProduct(['category_id' => $category->id]);
        }
        foreach (array_slice($products, 0, 3) as $p) {
            $this->setSales($p, 1);
        }
        CatalogCache::bump();

        $this->assertSame([], BadgeResolver::bestSellerIds(), 'units < best_seller_min_units');
        $this->assertSame([], $this->keys($products[0]));
    }

    public function test_minimo_de_productos_con_ventas_en_la_categoria(): void
    {
        $category = $this->makeCategory();
        $a = $this->makeProduct(['category_id' => $category->id]);
        $b = $this->makeProduct(['category_id' => $category->id]);
        $this->makeProduct(['category_id' => $category->id]);
        $this->setSales($a, 20);
        $this->setSales($b, 10);
        CatalogCache::bump();

        $this->assertSame([], BadgeResolver::bestSellerIds(), 'solo 2 productos con ventas (< 3)');

        $this->setCatalog('best_seller_min_products', 2);
        $this->assertSame([$a->id], BadgeResolver::bestSellerIds());
    }

    public function test_el_ranking_es_por_categoria_directa(): void
    {
        $first = $this->seedBestSellerCategory(10);
        $second = $this->seedBestSellerCategory(10);

        $this->assertEqualsCanonicalizing(
            [$first['winner']->id, $second['winner']->id],
            BadgeResolver::bestSellerIds()
        );
    }

    public function test_productos_no_publicados_no_cuentan_en_n_ni_en_el_ranking(): void
    {
        $category = $this->makeCategory();
        $winner = $this->makeProduct(['category_id' => $category->id]);
        $b = $this->makeProduct(['category_id' => $category->id]);
        $c = $this->makeProduct(['category_id' => $category->id]);
        // Inactivo con muchas ventas: no debe ganar ni contar como "con ventas".
        $hidden = $this->makeProduct(['category_id' => $category->id, 'is_active' => false]);
        $this->setSales($winner, 6);
        $this->setSales($b, 5);
        $this->setSales($hidden, 99);
        CatalogCache::bump();

        $this->assertSame([], BadgeResolver::bestSellerIds(), 'con el oculto fuera solo hay 2 productos con ventas');

        $this->setSales($c, 4);
        CatalogCache::bump();
        $this->assertSame([$winner->id], BadgeResolver::bestSellerIds());
    }

    public function test_mas_vendido_respeta_el_porcentaje_configurado(): void
    {
        ['winner' => $winner, 'others' => $others] = $this->seedBestSellerCategory(10);

        $this->setCatalog('best_seller_top_percent', 20); // k = 2
        $this->assertEqualsCanonicalizing([$winner->id, $others[0]->id], BadgeResolver::bestSellerIds());
    }

    public function test_best_seller_ids_se_cachea_y_se_invalida_con_bump(): void
    {
        ['winner' => $winner, 'others' => $others] = $this->seedBestSellerCategory(10);

        $this->assertSame([$winner->id], BadgeResolver::bestSellerIds());
        $this->assertTrue(Cache::has('catalog.badges.' . CatalogCache::version()));

        // Cambio de datos SIN bump: se sirve lo cacheado (memo + Cache).
        $this->setSales($others[0], 500);
        BadgeResolver::flushMemo();
        $this->assertSame([$winner->id], BadgeResolver::bestSellerIds());

        // Con bump se recalcula.
        CatalogCache::bump();
        $this->assertSame([$others[0]->id], BadgeResolver::bestSellerIds());
    }

    public function test_resolver_muchas_tarjetas_no_hace_consultas_adicionales(): void
    {
        ['winner' => $winner] = $this->seedBestSellerCategory(10);
        $products = Products::all();

        // Calienta ajustes y el set de más vendidos.
        BadgeResolver::for($winner);

        DB::enableQueryLog();
        foreach ($products as $product) {
            BadgeResolver::for($product);
        }
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries, 'sin N+1: ' . json_encode(array_column($queries, 'query')));
    }

    // ── Colores y contraste ────────────────────────────────────────────────

    public function test_colores_por_defecto_y_contraste_automatico(): void
    {
        ['winner' => $winner] = $this->seedBestSellerCategory();
        $winner->forceFill(['compare_price' => 125, 'stock' => 2, 'is_new' => true])->save();
        $winner->refresh();

        $best = BadgeResolver::for($winner)[0];
        $this->assertSame(['#FF6213', '#000000'], [$best['bg'], $best['fg']], 'naranja de marca: negro da mayor contraste');

        $badges = [];
        foreach ([
            $this->makeProduct(['compare_price' => 125]),
            $this->makeProduct(['stock' => 2]),
            $this->makeProduct(['is_new' => true]),
        ] as $p) {
            $b = BadgeResolver::for($p)[0];
            $badges[$b['key']] = [$b['bg'], $b['fg']];
        }

        $this->assertSame(['#C62828', '#FFFFFF'], $badges['discount']);
        $this->assertSame(['#FFC107', '#000000'], $badges['last_units']);
        $this->assertSame(['#2E7D32', '#FFFFFF'], $badges['new']);
    }

    public function test_colores_desde_settings_normalizados_y_con_fg_por_contraste(): void
    {
        $product = $this->makeProduct(['is_new' => true]);

        $this->setCatalog('badge_color_new', '#000000');
        $this->assertSame(['#000000', '#FFFFFF'], $this->colors($product));

        $this->setCatalog('badge_color_new', '#ffffff');
        $this->assertSame(['#FFFFFF', '#000000'], $this->colors($product));

        $this->setCatalog('badge_color_new', '#0af'); // forma corta
        $this->assertSame('#00AAFF', $this->colors($product)[0]);

        $this->setCatalog('badge_color_new', '1976d2'); // sin '#'
        $this->assertSame(['#1976D2', '#FFFFFF'], $this->colors($product));
    }

    public function test_color_invalido_cae_al_default(): void
    {
        $product = $this->makeProduct(['is_new' => true]);

        foreach (['rojo', '#12345', '#GGGGGG', '', 'url(javascript:1)', '#fff; background:red'] as $invalid) {
            $this->setCatalog('badge_color_new', $invalid);
            $this->assertSame(['#2E7D32', '#FFFFFF'], $this->colors($product), "valor: {$invalid}");
        }
    }

    private function colors(Products $product): array
    {
        $badge = BadgeResolver::for($product)[0];

        return [$badge['bg'], $badge['fg']];
    }
}
