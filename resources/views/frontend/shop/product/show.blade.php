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

    // Elevado desde price-box.blade.php: gallery.blade.php también lo
    // necesita (badge de descuento sobre la imagen principal en móvil), y
    // @include comparte este scope con ambos partials.
    $hasDiscount = $product->compare_base_price && $product->compare_base_price > $product->base_price;
    $discountPct = $hasDiscount ? round((1 - ($product->base_price / $product->compare_base_price)) * 100) : null;
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

            {{-- Tablet/desktop: specs siempre expandida, sin acordeón (oculta en móvil por CSS) --}}
            <div class="product-main__specs-standalone">
                @include('frontend.shop.product.partials.specs-table')
            </div>

            {{-- Móvil: mismo contenido + envío/garantía/FAQ dentro de un acordeón
                 (oculto en tablet/desktop por CSS). Mismo patrón que
                 home/sections/faq.blade.php (.home-faq__*), con clases propias
                 (.product-accordion__*) para no compartir el x-data de ese
                 partial -- evita dos estados de acordeón independientes sobre
                 las mismas preguntas. --}}
            <div class="product-accordion" x-data="{ open: null }">
                <div class="product-accordion__item">
                    <button type="button" class="product-accordion__question" @click="open = open === 0 ? null : 0">
                        <span>Ficha técnica</span>
                        <span x-text="open === 0 ? '−' : '+'">+</span>
                    </button>
                    <div class="product-accordion__answer" x-show="open === 0" x-cloak>
                        @include('frontend.shop.product.partials.specs-table')
                    </div>
                </div>

                <div class="product-accordion__item">
                    <button type="button" class="product-accordion__question" @click="open = open === 1 ? null : 1">
                        <span>Envío e instalación</span>
                        <span x-text="open === 1 ? '−' : '+'">+</span>
                    </button>
                    <div class="product-accordion__answer" x-show="open === 1" x-cloak>
                        @if (!$product->shipping_cost || $product->shipping_cost <= 0)
                            <p>Envío gratis a toda la República Mexicana.</p>
                        @else
                            <p>Costo de envío: ${{ number_format($product->shipping_cost, 2) }} MXN.
                                @if ($product->free_shipping_threshold)
                                    Gratis en compras desde ${{ number_format($product->free_shipping_threshold, 2) }} MXN.
                                @endif
                            </p>
                        @endif
                        @if ($product->documents->where('type', 'manual')->isNotEmpty())
                            <p>Este producto incluye manual de instalación descargable más abajo, en la sección de documentos.</p>
                        @endif
                    </div>
                </div>

                <div class="product-accordion__item">
                    <button type="button" class="product-accordion__question" @click="open = open === 2 ? null : 2">
                        <span>Garantía y servicio</span>
                        <span x-text="open === 2 ? '−' : '+'">+</span>
                    </button>
                    <div class="product-accordion__answer" x-show="open === 2" x-cloak>
                        <p>Este equipo cuenta con garantía de fábrica. Nuestro equipo técnico está disponible para dar seguimiento a cualquier solicitud de servicio.</p>
                        @if ($product->documents->where('type', 'garantia')->isNotEmpty())
                            <p>Consulta el documento de garantía descargable más abajo, en la sección de documentos.</p>
                        @endif
                    </div>
                </div>

                @if ($schemaFaqs->isNotEmpty())
                    <div class="product-accordion__item">
                        <button type="button" class="product-accordion__question" @click="open = open === 3 ? null : 3">
                            <span>Preguntas y respuestas</span>
                            <span x-text="open === 3 ? '−' : '+'">+</span>
                        </button>
                        <div class="product-accordion__answer" x-show="open === 3" x-cloak>
                            @foreach ($schemaFaqs as $item)
                                <p>
                                    <strong>{{ $product->resolveVariables($item['question']) }}</strong><br>
                                    {!! \App\Support\TextLinks::render($product->resolveVariables($item['answer'])) !!}
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @include('frontend.shop.product.partials.documents-grid')
        </div>
        <div class="product-main__right" x-data="productQty({{ $product->stock ?? 0 }}, '{{ $product->availability }}')">
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
