@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/collection.css', 'resources/js/frontend/shop/collection.js'];

    $metaTitle = $collection->seo_title ?: ($collection->name . ' — Equiterm Industries');
    $metaDescription = $collection->seo_description
        ?: \Illuminate\Support\Str::limit(strip_tags($collection->description ?? ''), 160)
        ?: ('Explora la colección ' . $collection->name . ' de Equiterm Industries.');
    $canonicalUrl = route('collection.show', $collection->slug);

    $ogImage = $collection->og_image_url ?: $collection->image_url;
    if ($ogImage && !str_starts_with($ogImage, 'http')) {
        $ogImage = \App\Support\UploadPath::url($ogImage);
    }
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)
@section('canonical', $canonicalUrl)
@section('og_title', $metaTitle)
@section('og_description', $metaDescription)
@section('og_url', $canonicalUrl)
@if ($ogImage)
    @section('og_image', $ogImage)
@endif

@php
    // JSON-LD: FAQPage (si la sección faq está activa y hay preguntas) +
    // CollectionPage/ItemList con los productos de la página actual.
    $schemaFaqs = collect($collection->faqs ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->values();
    $faqSectionActive = $sections->contains(fn ($s) => $s->type === 'faq');

    $itemList = $products->values()->map(fn ($p, $i) => [
        '@type'    => 'ListItem',
        'position' => $i + 1 + (($products->currentPage() - 1) * $products->perPage()),
        'url'      => route('product.show', $p->slug),
        'name'     => $p->resolveVariables($p->name),
    ])->all();
@endphp

@section('schema')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $collection->name,
            'url' => $canonicalUrl,
            'description' => $metaDescription,
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $products->total(),
                'itemListElement' => $itemList,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @if ($faqSectionActive && $schemaFaqs->isNotEmpty())
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schemaFaqs->map(fn ($item) => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ])->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
        </script>
    @endif
@endsection

@section('content')
<div class="eq-shop-collection">
    <div class="collection-breadcrumb">
        <a href="{{ route('home') }}">Inicio</a>
        &nbsp;›&nbsp; <span>{{ $collection->name }}</span>
    </div>

    <section class="collection-hero {{ $collection->image_url ? 'collection-hero--with-image' : '' }}">
        <div class="collection-hero__text">
            <h1>{{ $collection->name }}</h1>
            @if ($collection->description)
                <p>{{ $collection->description }}</p>
            @endif
            <span class="collection-hero__count">{{ $products->total() }} producto{{ $products->total() === 1 ? '' : 's' }}</span>
        </div>
        @if ($collection->image_url)
            <div class="collection-hero__image">
                <img src="{{ str_starts_with($collection->image_url, 'http') ? $collection->image_url : \App\Support\UploadPath::url($collection->image_url) }}"
                    alt="{{ $collection->name }}">
            </div>
        @endif
    </section>

    <section class="collection-grid-wrap">
        @if ($products->isNotEmpty())
            {{-- OJO: la variable del loop se llama $gridProduct a propósito —
                 en Blade la variable de un foreach sigue definida después del
                 loop, y si se llamara $product las secciones CMS de abajo
                 detectarían "contexto de producto" en vez de la colección. --}}
            <div class="collection-grid">
                @foreach ($products as $gridProduct)
                    <x-frontend.shop.product-card :product="$gridProduct" />
                @endforeach
            </div>
            <div class="collection-pagination">
                {{ $products->links() }}
            </div>
        @else
            <p class="collection-empty">Esta colección aún no tiene productos disponibles.</p>
        @endif
    </section>

    {{-- Secciones administrables desde Admin > Secciones del Sitio > Colecciones --}}
    @foreach ($sections as $section)
        @include('frontend.shop.home.sections.' . str_replace('_', '-', $section->type), ['section' => $section])
    @endforeach
</div>
@endsection
