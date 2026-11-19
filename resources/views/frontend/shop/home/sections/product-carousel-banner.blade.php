@php
    // Contexto de la página ANTES de cualquier reasignación (ver
    // product-carousel.blade.php).
    $ctx = $product ?? $collection ?? $servicePage ?? $contextCategory ?? null;
    $currentProduct = isset($product) ? $product : null;

    $config = $section->config ?? [];
    $limit = $config['limit'] ?? 12;
    // Fuente de productos: App\Services\SectionProducts (misma lógica que
    // antes vivía aquí). En páginas de categoría, source=category sin
    // category_id usa la categoría de la página ($contextCategory).
    $resolvedProducts = \App\Services\SectionProducts::resolveDetailed($config, $contextCategory ?? null, (int) $limit, $currentProduct);
    $products = $resolvedProducts['products'];
    $sourceCollection = $resolvedProducts['source_collection'];

    // Destino del banner: `banner_link` (LinkTarget) tiene prioridad; si no
    // existe se usa el campo legado banner_link_url. Un destino roto deja el
    // banner sin enlace (nunca href vacío ni '#').
    $bannerLinkUrl = $config['banner_link_url'] ?? null;
    $bannerNewTab = false;
    if (!empty($config['banner_link']['type'])) {
        $bannerLinkUrl = \App\Support\LinkTarget::resolve($config['banner_link']);
        $bannerNewTab = !empty($config['banner_link']['new_tab']);
    }

    $banner = [
        'image_url' => $config['banner_image_url'] ?? null,
        'link_url'  => $bannerLinkUrl,
        'new_tab'   => $bannerNewTab,
        'alt'       => $config['banner_alt'] ?? '',
        'no_link'   => $config['banner_no_link'] ?? false,
    ];

    $viewAllUrl = ($sourceCollection && $sourceCollection->is_active)
        ? route('collection.show', $sourceCollection->slug)
        : null;

    $headingTitle = $section->resolveText($section->title, $ctx);
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp

@if (count($products) > 0)
<x-frontend.shop.product-carousel :title="$headingTitle" :products="$products" :banner="$banner" :view-all-url="$viewAllUrl" :heading-url="$headingUrl" :heading-new-tab="$headingNewTab" data-section-id="{{ $section->id ?? '' }}" />
@endif
