<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Services\Catalog\CatalogParams;
use App\Services\Catalog\CatalogResult;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Arma a mano un CatalogResult (el contrato congelado) para probar las vistas
 * del catálogo sin pasar por CatalogQuery. Requiere CreatesProductBlockFixtures
 * en la clase que lo usa (makeCategory / makeProduct).
 */
trait CreatesCatalogViewFixtures
{
    /** Opciones de faceta con la forma del contrato. */
    protected function facetOptions(string $prefix, int $n, string $inputName, array $selectedIdx = []): array
    {
        $rows = [];
        for ($i = 1; $i <= $n; $i++) {
            $rows[] = [
                'id'        => $i,
                'name'      => "{$prefix} {$i}",
                'label'     => "{$prefix} {$i}",
                'count'     => 100 - $i,
                'selected'  => in_array($i, $selectedIdx, true),
                'href'      => "/catalogo?{$prefix}={$i}",
                'inputName' => $inputName,
                'value'     => $i,
            ];
        }

        return $rows;
    }

    /** Facetas completas de ejemplo; $over reemplaza claves de primer nivel. */
    protected function sampleFacets(array $over = []): array
    {
        return array_merge([
            'categories' => [
                'current' => ['name' => 'Controles', 'count' => 40],
                'back'    => ['label' => '‹ Todas las categorías', 'url' => '/catalogo'],
                'items'   => [
                    ['id' => 11, 'name' => 'Termostatos', 'url' => '/catalogo/termostatos', 'count' => 25],
                    ['id' => 12, 'name' => 'Sensores', 'url' => '/catalogo/sensores', 'count' => 15],
                ],
            ],
            'brands' => $this->facetOptions('Marca', 3, 'marca[]'),
            'price'  => [
                'min' => 100, 'max' => 9000,
                'ranges' => [
                    ['label' => 'Hasta $500', 'min' => null, 'max' => 500, 'count' => 12, 'active' => false, 'href' => '/catalogo?precio_max=500'],
                    ['label' => '$500 a $2,000', 'min' => 500, 'max' => 2000, 'count' => 20, 'active' => true, 'href' => '/catalogo'],
                ],
                'selected'   => ['min' => 500, 'max' => 2000],
                'formAction' => '/catalogo',
                'hidden'     => ['orden' => 'precio_asc', 'marca' => [2, 3]],
            ],
            'availability' => [
                ['key' => 'stock', 'label' => 'En stock', 'count' => 30, 'selected' => false, 'href' => '/catalogo?disp[]=stock', 'inputName' => 'disp[]', 'value' => 'stock'],
                ['key' => '24_48h', 'label' => 'Envío en 24-48 h', 'count' => 28, 'selected' => true, 'href' => '/catalogo', 'inputName' => 'disp[]', 'value' => '24_48h'],
            ],
            'free_shipping' => ['label' => 'Envío gratis', 'count' => 35, 'selected' => false, 'href' => '/catalogo?envio_gratis=1', 'inputName' => 'envio_gratis', 'value' => '1'],
            'tech' => [
                ['id' => 7, 'name' => 'Tamaño DIN', 'options' => [
                    ['id' => 1, 'label' => '1/4 DIN', 'count' => 5, 'selected' => false, 'href' => '/catalogo?f[7][]=1', 'inputName' => 'f[7][]', 'value' => 1],
                    ['id' => 2, 'label' => '1/8 DIN', 'count' => 3, 'selected' => true, 'href' => '/catalogo', 'inputName' => 'f[7][]', 'value' => 2],
                ]],
            ],
            'maxVisible' => 6,
        ], $over);
    }

    protected function sampleSortOptions(string $selected = 'relevancia'): array
    {
        $labels = [
            'relevancia' => 'Relevancia', 'descuento' => 'Mayor descuento', 'precio_asc' => 'Precio: menor a mayor',
            'precio_desc' => 'Precio: mayor a menor', 'vendidos' => 'Más vendidos', 'nuevos' => 'Más nuevos',
        ];

        return collect($labels)->map(fn ($label, $key) => [
            'key' => $key, 'label' => $label, 'selected' => $key === $selected,
            'href' => $key === 'relevancia' ? '/catalogo' : "/catalogo?orden={$key}",
        ])->values()->all();
    }

    /**
     * @param  int  $productCount  productos reales en la página actual
     * @param  int|null  $total    total (por omisión = $productCount)
     * @param  array  $over        facets, chips, clearHref, sortOptions, meta, page, q, order
     */
    protected function makeCatalogResult(int $productCount = 3, ?int $total = null, array $over = []): CatalogResult
    {
        $category = $this->makeCategory(['name' => 'Controles']);
        $products = collect();
        for ($i = 1; $i <= $productCount; $i++) {
            $products->push($this->makeProduct(['category_id' => $category->id, 'name' => "Producto {$i}"]));
        }

        $total = $total ?? $productCount;
        $page = $over['page'] ?? 1;
        $paginator = new LengthAwarePaginator($products, $total, 24, $page, ['path' => '/catalogo']);

        $params = new CatalogParams();
        $params->q = $over['q'] ?? '';
        $params->order = $over['order'] ?? 'relevancia';

        $meta = array_merge([
            'page' => $page, 'pages' => max(1, (int) ceil($total / 24)), 'activeCount' => 0, 'noindex' => false,
            'canonicalUrl' => 'https://example.test/catalogo', 'stateUrl' => '/catalogo',
            'liveMessage' => $total === 1 ? '1 resultado' : "{$total} resultados",
        ], $over['meta'] ?? []);

        return new CatalogResult(
            $paginator,
            $total,
            $over['facets'] ?? $this->sampleFacets(),
            $over['chips'] ?? [],
            $over['clearHref'] ?? '/catalogo',
            $over['sortOptions'] ?? $this->sampleSortOptions(),
            $meta,
            $params,
        );
    }

    protected function renderCatalogPartial(string $partial, CatalogResult $result, ?Category $category = null): string
    {
        return view('frontend.shop.catalog.partials.' . $partial, ['result' => $result, 'category' => $category])->render();
    }
}
