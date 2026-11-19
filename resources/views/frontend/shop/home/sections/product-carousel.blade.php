@php
    // Contexto de la página ANTES de cualquier reasignación: en páginas de
    // colección $collection es la colección de la PÁGINA; la colección
    // fuente del carrusel se maneja aparte como $sourceCollection.
    $ctx = $product ?? $collection ?? $servicePage ?? $contextCategory ?? null;
    // Producto de la página (solo existe en la página de producto): el
    // @foreach del componente reasigna $product en su propio scope, no aquí.
    $currentProduct = isset($product) ? $product : null;

    $config = $section->config ?? [];
    $limit = $config['limit'] ?? 12;
    // Fuente de productos: App\Services\SectionProducts (misma lógica que
    // antes vivía aquí). En páginas de categoría, source=category sin
    // category_id usa la categoría de la página ($contextCategory).
    $resolvedProducts = \App\Services\SectionProducts::resolveDetailed($config, $contextCategory ?? null, (int) $limit, $currentProduct);
    $products = $resolvedProducts['products'];
    $sourceCollection = $resolvedProducts['source_collection'];

    $viewAllUrl = ($sourceCollection && $sourceCollection->is_active)
        ? route('collection.show', $sourceCollection->slug)
        : null;

    // Encabezado: el título puede enlazar (heading_link); un enlace roto o
    // inexistente deja el título sin <a> (nunca href vacío ni '#').
    $headingTitle = $section->resolveText($section->title, $ctx);
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp

@if (count($products) > 0)
    @if (($section->zone ?? 'stack') === 'sidebar')
        {{-- Columna lateral (arriba de "Medios de pago"): versión compacta. --}}
        <x-frontend.shop.product-carousel-compact :title="$headingTitle" :eyebrow="$section->resolveText($config['eyebrow'] ?? null, $ctx)" :products="$products" :heading-url="$headingUrl" :heading-new-tab="$headingNewTab" data-section-id="{{ $section->id ?? '' }}" />
    @else
        <x-frontend.shop.product-carousel :title="$headingTitle" :products="$products" :view-all-url="$viewAllUrl" :heading-url="$headingUrl" :heading-new-tab="$headingNewTab" data-section-id="{{ $section->id ?? '' }}" />
    @endif
@endif
