<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Collection;
use App\Models\HomeSection;
use App\Models\Products;
use App\Models\ProductSectionAssignment;
use App\Models\ServicePage;

/**
 * Helpers de datos para las pruebas de bloques dinámicos de producto.
 * Se asume RefreshDatabase en la clase que lo usa (BD laravel_test).
 */
trait CreatesProductBlockFixtures
{
    protected function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name' => 'Categoria',
            'slug' => 'cat-' . uniqid(),
            'is_active' => true,
        ], $overrides));
    }

    protected function makeProduct(array $overrides = []): Products
    {
        return Products::create(array_merge([
            'category_id' => $this->makeCategory()->id,
            'name'        => 'Producto',
            'slug'        => 'prod-' . uniqid(),
            'sku'         => 'SKU-' . uniqid(),
            'price'       => 100,
            'stock'       => 5,
            'is_active'          => true,
            'publish_on_website' => true,
        ], $overrides));
    }

    protected function makeCollection(array $overrides = []): Collection
    {
        return Collection::create(array_merge([
            'name' => 'Coleccion',
            'slug' => 'col-' . uniqid(),
            'type' => 'manual',
            'is_active' => true,
        ], $overrides));
    }

    protected function makeServicePage(array $overrides = []): ServicePage
    {
        return ServicePage::create(array_merge([
            'name' => 'Servicio',
            'slug' => 'srv-' . uniqid(),
            'page_type' => ServicePage::TYPE_SERVICE,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeSection(array $overrides = []): HomeSection
    {
        return HomeSection::create(array_merge([
            'type'       => 'banner',
            'page'       => HomeSection::PAGE_PRODUCT_TEMPLATE,
            'zone'       => HomeSection::ZONE_STACK,
            'name'       => 'Plantilla ' . uniqid(),
            'title'      => 'Titulo',
            'config'     => ['foo' => 'bar'],
            'sort_order' => 0,
            'is_active'  => true,
        ], $overrides));
    }

    protected function makeTemplate(array $overrides = []): HomeSection
    {
        return $this->makeSection(array_merge(['page' => HomeSection::PAGE_PRODUCT_TEMPLATE], $overrides));
    }

    protected function makeCustom(array $overrides = []): HomeSection
    {
        return $this->makeSection(array_merge(['page' => HomeSection::PAGE_PRODUCT_CUSTOM], $overrides));
    }

    protected function assign(Products $product, HomeSection $section, int $sort = 0, bool $visible = true): ProductSectionAssignment
    {
        return ProductSectionAssignment::create([
            'product_id'      => $product->id,
            'home_section_id' => $section->id,
            'sort_order'      => $sort,
            'is_visible'      => $visible,
        ]);
    }

    /** home_section_id => sort_order del producto, ordenado por sort_order. */
    protected function layoutOf(Products $product): array
    {
        return ProductSectionAssignment::where('product_id', $product->id)
            ->orderBy('sort_order')->orderBy('id')
            ->pluck('sort_order', 'home_section_id')->all();
    }

    /** ids de sección del producto en orden. */
    protected function orderOf(Products $product): array
    {
        return array_keys($this->layoutOf($product));
    }
}
