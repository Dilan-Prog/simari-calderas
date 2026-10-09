<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogViewFixtures;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * Vistas del catálogo público (WP-2): sidebar, chips, toolbar, grid e index.
 * Se alimentan con un CatalogResult armado a mano (contrato congelado), sin
 * pasar por CatalogQuery.
 */
class CatalogViewsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;
    use CreatesCatalogViewFixtures;

    public function test_sidebar_renders_fieldsets_with_labelled_checkboxes_and_hrefs(): void
    {
        $html = $this->renderCatalogPartial('sidebar', $this->makeCatalogResult());

        // Formulario GET funcional sin JS, con "Aplicar" dentro de <noscript>.
        $this->assertMatchesRegularExpression('/<form[^>]+id="catalog-filters-form"[^>]+method="GET"|<form[^>]+method="GET"[^>]+id="catalog-filters-form"/i', $html);
        $this->assertMatchesRegularExpression('/<noscript>\s*<button[^>]+type="submit"[^>]*>\s*Aplicar\s*<\/button>\s*<\/noscript>/s', $html);

        // Un fieldset/legend por grupo.
        foreach (['Categorías', 'Precio', 'Marcas', 'Disponibilidad', 'Envío', 'Tamaño DIN'] as $legend) {
            $this->assertMatchesRegularExpression('/<legend[^>]*>\s*' . preg_quote($legend, '/') . '\s*<\/legend>/u', $html, "Falta el grupo {$legend}");
        }
        $this->assertSame(6, substr_count($html, '<fieldset'));

        // Cada checkbox tiene name real, data-href, label asociado y conteo accesible.
        preg_match_all('/<input type="checkbox"[^>]*>/', $html, $inputs);
        $this->assertCount(3 + 2 + 1 + 2, $inputs[0]); // marcas + disp + envío + técnicos
        foreach ($inputs[0] as $input) {
            $this->assertStringContainsString('data-href="', $input);
            $this->assertMatchesRegularExpression('/name="[^"]+"/', $input);
            $this->assertStringContainsString('form="catalog-filters-form"', $input);
            preg_match('/id="([^"]+)"/', $input, $id);
            $this->assertMatchesRegularExpression('/<label for="' . preg_quote($id[1], '/') . '"/', $html);
        }
        $this->assertStringContainsString('name="marca[]"', $html);
        $this->assertStringContainsString('name="disp[]"', $html);
        $this->assertStringContainsString('name="envio_gratis"', $html);
        $this->assertStringContainsString('name="f[7][]"', $html);
        $this->assertStringContainsString('data-href="/catalogo?f[7][]=1"', $html);
        $this->assertStringContainsString('<span class="catalog-facet__count">99</span><span class="sr-only"> productos</span>', $html);
        $this->assertMatchesRegularExpression('/data-href="\/catalogo"\s+checked/', $html, 'La opción seleccionada debe ir marcada');
    }

    public function test_sidebar_price_group_has_clickable_ranges_and_from_to_form(): void
    {
        $html = $this->renderCatalogPartial('sidebar', $this->makeCatalogResult());

        $this->assertStringContainsString('href="/catalogo?precio_max=500"', $html);
        $this->assertMatchesRegularExpression('/<a[^>]+href="\/catalogo\?precio_max=500"\s+data-catalog-link/', $html);
        $this->assertStringContainsString('aria-current="true"', $html);

        $this->assertStringContainsString('data-catalog-price-form', $html);
        $this->assertStringContainsString('name="precio_min"', $html);
        $this->assertStringContainsString('name="precio_max"', $html);
        $this->assertStringContainsString('value="500"', $html);
        // Ocultos del formulario Desde/Hasta (incluye arreglos aplanados).
        $this->assertStringContainsString('<input type="hidden" name="orden" value="precio_asc">', $html);
        $this->assertStringContainsString('<input type="hidden" name="marca[]" value="2">', $html);
        $this->assertStringContainsString('<input type="hidden" name="marca[]" value="3">', $html);
    }

    public function test_sidebar_categories_are_plain_route_links_with_back_link(): void
    {
        $html = $this->renderCatalogPartial('sidebar', $this->makeCatalogResult());

        $this->assertStringContainsString('class="catalog-cat__back" href="/catalogo"', $html);
        $this->assertStringContainsString('href="/catalogo/termostatos"', $html);
        // Cambiar de categoría cambia h1/SEO: navegación completa, nunca AJAX.
        $this->assertDoesNotMatchRegularExpression('/href="\/catalogo\/termostatos"[^>]*data-catalog-link/', $html);
    }

    public function test_sidebar_shows_more_button_only_when_group_exceeds_max_visible(): void
    {
        $many = $this->makeCatalogResult(1, null, ['facets' => $this->sampleFacets([
            'brands' => $this->facetOptions('Marca', 9, 'marca[]'),
        ])]);
        $html = $this->renderCatalogPartial('sidebar', $many);

        $this->assertStringContainsString('data-catalog-more', $html);
        $this->assertStringContainsString('>Mostrar más</button>', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-controls="catalog-facet-more-marca"', $html);
        $this->assertStringContainsString('<ul class="catalog-facet__list catalog-facet__extra is-collapsed" id="catalog-facet-more-marca">', $html);
        // 6 visibles + 3 en el contenedor oculto.
        preg_match('/<ul class="catalog-facet__list catalog-facet__extra[^>]*>(.*?)<\/ul>/s', $html, $extra);
        $this->assertSame(3, substr_count($extra[1], 'type="checkbox"'));

        $few = $this->renderCatalogPartial('sidebar', $this->makeCatalogResult(1));
        $this->assertStringNotContainsString('data-catalog-more', $few);
        $this->assertStringNotContainsString('Mostrar más', $few);
    }

    public function test_sidebar_group_starts_expanded_when_a_selected_option_is_behind_show_more(): void
    {
        $result = $this->makeCatalogResult(1, null, ['facets' => $this->sampleFacets([
            'brands' => $this->facetOptions('Marca', 8, 'marca[]', [8]),
        ])]);
        $html = $this->renderCatalogPartial('sidebar', $result);

        $this->assertStringContainsString('>Mostrar menos</button>', $html);
        $this->assertStringNotContainsString('catalog-facet__extra is-collapsed', $html);
    }

    public function test_sidebar_hides_groups_without_options_and_empty_free_shipping(): void
    {
        $result = $this->makeCatalogResult(1, null, ['facets' => $this->sampleFacets([
            'brands' => [],
            'tech' => [],
            'free_shipping' => ['label' => 'Envío gratis', 'count' => 0, 'selected' => false, 'href' => '/catalogo', 'inputName' => 'envio_gratis', 'value' => '1'],
        ])]);
        $html = $this->renderCatalogPartial('sidebar', $result);

        $this->assertStringNotContainsString('Marcas', $html);
        $this->assertStringNotContainsString('Tamaño DIN', $html);
        $this->assertStringNotContainsString('type="checkbox" class="catalog-facet__input" id="catalog-f-envio', $html);
    }

    public function test_chips_render_remove_links_with_aria_label_and_clear_link(): void
    {
        $result = $this->makeCatalogResult(1, null, [
            'chips' => [
                ['label' => 'Marca: Honeywell', 'removeHref' => '/catalogo?x=1'],
                ['label' => 'Envío gratis', 'removeHref' => '/catalogo?x=2'],
            ],
            'clearHref' => '/catalogo?orden=precio_asc',
        ]);
        $html = $this->renderCatalogPartial('chips', $result);

        $this->assertStringContainsString('aria-label="Quitar filtro Marca: Honeywell"', $html);
        $this->assertStringContainsString('aria-label="Quitar filtro Envío gratis"', $html);
        $this->assertStringContainsString('href="/catalogo?x=1"', $html);
        $this->assertSame(3, substr_count($html, 'data-catalog-link'));
        $this->assertMatchesRegularExpression('/<a[^>]+href="\/catalogo\?orden=precio_asc"[^>]*>Limpiar filtros<\/a>/', $html);
    }

    public function test_chips_render_nothing_without_active_filters(): void
    {
        $html = $this->renderCatalogPartial('chips', $this->makeCatalogResult(1));

        $this->assertSame('', trim($html));
    }

    public function test_toolbar_shows_total_filters_button_and_sort_select(): void
    {
        $result = $this->makeCatalogResult(3, 167, [
            'sortOptions' => $this->sampleSortOptions('precio_asc'),
            'meta' => ['activeCount' => 2],
        ]);
        $html = $this->renderCatalogPartial('toolbar', $result);

        $this->assertStringContainsString('<strong>167</strong> resultados', $html);
        $this->assertStringContainsString('data-catalog-drawer-open', $html);
        $this->assertStringContainsString('aria-controls="catalog-sidebar"', $html);
        $this->assertStringContainsString('Filtros (2)', $html);

        $this->assertMatchesRegularExpression('/<select[^>]+id="catalog-sort"[^>]+data-catalog-sort|<select[^>]+data-catalog-sort[^>]+id="catalog-sort"/', $html);
        $this->assertStringContainsString('<label for="catalog-sort"', $html);
        $this->assertSame(6, substr_count($html, '<option '));
        $this->assertMatchesRegularExpression('/<option value="precio_asc" data-href="\/catalogo\?orden=precio_asc" selected>/', $html);
        // Alternativa sin JS: enlaces dentro de <noscript>.
        $this->assertMatchesRegularExpression('/<noscript>.*<a href="\/catalogo\?orden=nuevos"/s', $html);
    }

    public function test_toolbar_uses_singular_and_plain_filters_label(): void
    {
        $html = $this->renderCatalogPartial('toolbar', $this->makeCatalogResult(1));

        $this->assertStringContainsString('<strong>1</strong> resultado<', $html);
        $this->assertStringContainsString('<span>Filtros</span>', $html);
    }

    public function test_grid_renders_one_product_card_per_product(): void
    {
        $html = $this->renderCatalogPartial('grid', $this->makeCatalogResult(4));

        $this->assertSame(4, substr_count($html, 'class="catalog-grid__item"'));
        $this->assertSame(4, preg_match_all('/class="product-card[ "]/', $html));
        $this->assertStringContainsString('Producto 1', $html);
        $this->assertStringNotContainsString('product-card--compact', $html);
        $this->assertStringNotContainsString('catalog-empty', $html);
    }

    public function test_grid_shows_empty_state_with_clear_link_when_filters_are_active(): void
    {
        $result = $this->makeCatalogResult(0, 0, [
            'chips' => [['label' => 'Marca: X', 'removeHref' => '/catalogo']],
            'clearHref' => '/catalogo?orden=nuevos',
            'meta' => ['activeCount' => 1],
        ]);
        $html = $this->renderCatalogPartial('grid', $result);

        $this->assertStringContainsString('No hay productos que coincidan con estos filtros.', $html);
        $this->assertMatchesRegularExpression('/<a[^>]+href="\/catalogo\?orden=nuevos"[^>]+data-catalog-link[^>]*>Limpiar filtros<\/a>/', $html);
        $this->assertStringNotContainsString('product-card', $html);
    }

    public function test_pagination_links_are_ajax_enabled_and_labelled(): void
    {
        $result = $this->makeCatalogResult(24, 60);
        $html = $result->paginator->links('frontend.shop.partials.pagination')->toHtml();

        $this->assertStringContainsString('data-catalog-link', $html);
        $this->assertStringContainsString('aria-label="Ir a la página 2"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('aria-label="Página siguiente"', $html);
    }

    public function test_index_page_keeps_seo_and_new_layout_without_carousels(): void
    {
        $result = $this->makeCatalogResult(3, 60, [
            'page' => 2,
            'meta' => ['canonicalUrl' => 'https://example.test/catalogo/controles?page=2', 'liveMessage' => '60 resultados'],
        ]);
        $category = $this->makeCategory(['name' => 'Controles', 'slug' => 'controles']);

        $html = view('frontend.shop.catalog.index', ['result' => $result, 'category' => $category])->render();

        // SEO.
        $this->assertStringContainsString('Controles — Equiterm Industries</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://example.test/catalogo/controles?page=2"', $html);
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type": "CollectionPage"', $html);
        $this->assertStringContainsString('"numberOfItems": 60', $html);
        $this->assertStringContainsString('"position": 25,', $html); // offset de página: (2-1)*24 + 1
        $this->assertStringContainsString('<h1 class="catalog-hero__title">Controles</h1>', $html);
        $this->assertStringNotContainsString('data-catalog-robots', $html);

        // Layout.
        $this->assertStringContainsString('<aside id="catalog-sidebar"', $html);
        foreach (['sidebar', 'toolbar', 'chips', 'products', 'pagination'] as $name) {
            $this->assertStringContainsString('data-catalog-region="' . $name . '"', $html, "Falta la región {$name}");
        }
        $this->assertMatchesRegularExpression('/<h2 id="catalog-results-heading"[^>]+tabindex="-1"/', $html);
        $this->assertStringContainsString('<div id="catalog-live" role="status" aria-live="polite" class="sr-only">60 resultados</div>', $html);
        $this->assertStringContainsString('id="catalog-results"', $html);

        // Sin carruseles por subcategoría.
        $this->assertStringNotContainsString('product-carousel', $html);
        $this->assertStringContainsString('catalog-grid', $html);
    }

    public function test_index_page_adds_noindex_only_for_noindex_variants(): void
    {
        $category = $this->makeCategory(['name' => 'Controles', 'slug' => 'controles']);

        $noindex = view('frontend.shop.catalog.index', [
            'result' => $this->makeCatalogResult(1, null, ['meta' => ['noindex' => true]]),
            'category' => $category,
        ])->render();
        $this->assertStringContainsString('<meta name="robots" content="noindex,follow" data-catalog-robots>', $noindex);

        $indexable = view('frontend.shop.catalog.index', [
            'result' => $this->makeCatalogResult(1),
            'category' => $category,
        ])->render();
        $this->assertStringNotContainsString('data-catalog-robots', $indexable);
        $this->assertStringNotContainsString('noindex', $indexable);
    }

    public function test_index_page_renders_catalog_root_without_category_and_keeps_faq(): void
    {
        $withFaq = $this->makeCategory([
            'name' => 'Con FAQ', 'slug' => 'con-faq',
            'faqs' => [['question' => '¿Envían?', 'answer' => 'Sí, a todo el país.']],
        ]);
        $html = view('frontend.shop.catalog.index', ['result' => $this->makeCatalogResult(1), 'category' => $withFaq])->render();
        $this->assertStringContainsString('"@type": "FAQPage"', $html);
        $this->assertStringContainsString('¿Envían?', $html);

        $root = view('frontend.shop.catalog.index', ['result' => $this->makeCatalogResult(1), 'category' => null])->render();
        $this->assertStringContainsString('<h1 class="catalog-hero__title">Catálogo</h1>', $root);
        $this->assertStringNotContainsString('FAQPage', $root);
    }

    public function test_legacy_sidebar_and_price_slider_are_gone(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/frontend/shop/catalog/partials/filters-sidebar.blade.php'));

        $roots = [resource_path('views'), resource_path('js'), resource_path('css'), app_path()];
        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (! $file->isFile() || ! preg_match('/\.(php|js|css)$/', $file->getFilename())) {
                    continue;
                }
                $contents = file_get_contents($file->getPathname());
                $this->assertStringNotContainsString('filters-sidebar', $contents, $file->getPathname());
                $this->assertStringNotContainsString('priceRangeSlider', $contents, $file->getPathname());
            }
        }
    }
}
