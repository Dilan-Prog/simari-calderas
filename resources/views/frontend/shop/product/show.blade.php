@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/product-detail.css', 'resources/js/frontend/shop/product-detail.js'];

    $resolvedName = $product->resolveVariables($product->name);
    $metaTitle = $product->resolveVariables($product->seo_title) ?: ($resolvedName . ' — Equiterm Industries');
    $metaDescription = $product->resolveVariables($product->seo_description ?: $product->short_description);
    $canonicalUrl = route('product.show', $product->slug);
    // URL canónica manual (admin > Productos > SEO): para cuando este
    // producto es muy parecido a otro y se quiere que Google indexe el otro
    // como el "original". Solo afecta el <link rel="canonical"> — el resto
    // de la página (og:url, JSON-LD) sigue describiendo este producto tal
    // como es, en su propia URL.
    $canonicalTagUrl = $product->canonical_url ?: $canonicalUrl;
    $ogTitle = $product->resolveVariables($product->og_title) ?: $metaTitle;
    $ogDescription = $product->resolveVariables($product->og_description) ?: $metaDescription;
    $ogImage = $product->og_image ?: $product->cover_image_url;

    $categoryChain = [];
    $chainCat = $product->category;
    while ($chainCat) {
        array_unshift($categoryChain, $chainCat);
        $chainCat = $chainCat->parent;
    }
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('canonical', $canonicalTagUrl)
@section('og_title', $ogTitle)
@section('og_description', $ogDescription)
@section('og_url', $canonicalUrl)
@if ($ogImage)
    @section('og_image', $ogImage)
@endif

@php
    // JSON-LD BreadcrumbList: reusa $categoryChain (misma jerarquía que el
    // breadcrumb visual de abajo), para que el schema nunca se desincronice
    // de lo que el usuario realmente ve.
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_merge(
            [[
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Inicio',
                'item' => route('home'),
            ]],
            collect($categoryChain)->values()->map(fn ($cat, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 2,
                'name' => $cat->name,
                'item' => route('catalog.category', $cat->slug),
            ])->all(),
            [[
                '@type' => 'ListItem',
                'position' => count($categoryChain) + 2,
                'name' => $resolvedName,
                'item' => $canonicalUrl,
            ]]
        ),
    ];

    // JSON-LD Product: solo si el producto tiene un precio público real
    // (price > 0 — "cotización bajo pedido" no tiene un estado propio en la
    // BD todavía, así que se omite el schema completo en vez de anunciar
    // un precio de $0.00).
    $productSchema = null;
    if ($product->price > 0) {
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $resolvedName,
            'sku' => $product->sku,
            'url' => $canonicalUrl,
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($product->base_price, 2, '.', ''),
                'priceCurrency' => 'MXN',
                'availability' => $product->schema_availability,
                'url' => $canonicalUrl,
                // El precio mostrado en la página (base_price) no incluye
                // IVA -- se señala explícitamente para que Merchant Center
                // no lo interprete como precio final.
                'priceSpecification' => [
                    '@type' => 'PriceSpecification',
                    'price' => number_format($product->base_price, 2, '.', ''),
                    'priceCurrency' => 'MXN',
                    'valueAddedTaxIncluded' => false,
                ],
            ],
        ];

        if ($metaDescription) {
            $productSchema['description'] = $metaDescription;
        }
        $schemaImages = $product->images->pluck('url')->values()->all();
        if (empty($schemaImages) && $product->cover_image_url) {
            $schemaImages = [$product->cover_image_url];
        }
        if (!empty($schemaImages)) {
            $productSchema['image'] = $schemaImages;
        }
        if ($product->brand) {
            $productSchema['brand'] = ['@type' => 'Brand', 'name' => $product->brand->name];
        }
    }

    // JSON-LD FAQPage: solo si el producto tiene FAQs Y la sección faq está
    // activa en el CMS (coherencia con lo que realmente se ve en la página).
    $schemaFaqs = collect($product->faqs ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->values();
    $faqSectionActive = $sections->contains(fn ($s) => $s->type === 'faq');
    $faqSchema = null;
    if ($faqSectionActive && $schemaFaqs->isNotEmpty()) {
        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $schemaFaqs->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $product->resolveVariables($item['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $product->resolveVariables($item['answer']),
                ],
            ])->all(),
        ];
    }
@endphp

@section('schema')
    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @if ($productSchema)
        <script type="application/ld+json">
            {!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
    @if ($faqSchema)
        <script type="application/ld+json">
            {!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
@endsection

@section('content')
<div class="eq-shop-product">
    <div class="product-breadcrumb">
        <a href="{{ route('home') }}">Inicio</a>
        @foreach ($categoryChain as $chainCat)
            &nbsp;›&nbsp; <a href="{{ route('catalog.category', $chainCat->slug) }}">{{ $chainCat->name }}</a>
        @endforeach
        &nbsp;›&nbsp; <span>{{ $resolvedName }}</span>
    </div>

    <section class="product-main">
        <div class="product-main__left">
            @include('frontend.shop.product.partials.gallery')
            <div class="product-description">
                @if ($product->description)
                    <h2 class="product-description__title">Descripción</h2>
                    <p>{{ $product->resolveVariables($product->description) }}</p>
                @endif
            </div>
            @include('frontend.shop.product.partials.specs-table')
            @include('frontend.shop.product.partials.documents-grid')
        </div>
        <div class="product-main__right" x-data>
            @include('frontend.shop.product.partials.price-box')
        </div>
    </section>

    {{-- Secciones administrables desde Admin > Secciones del Sitio > Página de Producto --}}
    @foreach ($sections as $section)
        @php $sectionView = 'frontend.shop.home.sections.' . str_replace('_', '-', $section->type); @endphp
        @unless (\Illuminate\Support\Facades\View::exists($sectionView))
            @php \Illuminate\Support\Facades\Log::warning("HomeSection #{$section->id} referencia una vista inexistente: {$sectionView}"); @endphp
        @endunless
        @includeIf($sectionView, ['section' => $section])
    @endforeach
</div>
@endsection
