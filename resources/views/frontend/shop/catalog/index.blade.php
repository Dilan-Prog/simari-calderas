@extends('frontend.shop.layouts.master')

@php
    // Variables: $result (App\Services\Catalog\CatalogResult) y $category (?Category).
    $shopVite = ['resources/css/frontend/shop/catalog.css', 'resources/js/frontend/shop/catalog.js'];
    $products = $result->paginator;

    $categoryChain = [];
    $chainCat = $category;
    while ($chainCat) {
        array_unshift($categoryChain, $chainCat);
        $chainCat = $chainCat->parent;
    }
@endphp

@php
    // Canonical: lo calcula el servicio (URL limpia de la categoría/catálogo,
    // o ?page=N autorreferenciada). Las variantes filtradas/ordenadas/con q
    // vienen con meta['noindex'] y se marcan noindex,follow más abajo.
    $catalogUrl = $category ? route('catalog.category', $category->slug) : route('catalog.index');
    $canonicalUrl = $result->meta['canonicalUrl'] ?? $catalogUrl;
    $catalogTitle = ($category->name ?? 'Catálogo') . ' — Equiterm Industries';
    $catalogDescription = $category?->seo_description
        ?: \Illuminate\Support\Str::limit(strip_tags($category?->description ?? ''), 160)
        ?: ('Explora el catálogo de ' . ($category->name ?? 'productos') . ' de Equiterm Industries.');
@endphp

@section('title', $catalogTitle)
@section('description', $catalogDescription)
@section('canonical', $canonicalUrl)
@section('og_title', $catalogTitle)
@section('og_description', $catalogDescription)
@section('og_url', $canonicalUrl)
@if ($category?->image_url)
    @section('og_image', $category->image_url)
@endif

@php
    // JSON-LD BreadcrumbList: reusa $categoryChain (misma jerarquía que el
    // breadcrumb visual de abajo).

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
            ])->all()
        ),
    ];

    // JSON-LD CollectionPage/ItemList con los productos de la página actual
    // (mismo patrón que frontend/shop/collection/show.blade.php).
    $itemList = $products->values()->map(fn ($p, $i) => [
        '@type'    => 'ListItem',
        'position' => $i + 1 + (($products->currentPage() - 1) * $products->perPage()),
        'url'      => route('product.show', $p->slug),
        'name'     => $p->resolveVariables($p->name),
    ])->all();

    $collectionSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $category->name ?? 'Catálogo',
        'url' => $canonicalUrl,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => $products->total(),
            'itemListElement' => $itemList,
        ],
    ];

    // JSON-LD FAQPage: solo si la categoría tiene FAQs propias — el mismo
    // $schemaFaqs alimenta el bloque visible más abajo, así el schema nunca
    // describe contenido que el usuario no ve en la página.
    $schemaFaqs = $category
        ? collect($category->faqs ?? [])
            ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
            ->values()
        : collect();
@endphp

@section('schema')
    @if (! empty($result->meta['noindex']))
        <meta name="robots" content="noindex,follow" data-catalog-robots>
    @endif
    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode($collectionSchema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT) !!}
    </script>
    @if ($schemaFaqs->isNotEmpty())
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
{{-- Marca temprana de JS (antes de pintar el sidebar): los estilos del drawer y de "Mostrar más" solo aplican con JS, así sin JS todo queda visible y usable. --}}
<script>document.documentElement.classList.add('catalog-js');</script>
<div class="eq-shop-catalog">
    <section class="catalog-hero">
        <h1 class="catalog-hero__title">{{ $category->name ?? 'Catálogo' }}</h1>
    </section>

    @if ($category)
        <div class="product-breadcrumb">
            <a href="{{ route('home') }}">Inicio</a>
            @foreach ($categoryChain as $chainCat)
                &nbsp;›&nbsp; <a href="{{ route('catalog.category', $chainCat->slug) }}">{{ $chainCat->name }}</a>
            @endforeach
        </div>
    @endif

    <div class="catalog-layout">
        <div class="catalog-overlay" data-catalog-overlay hidden></div>

        {{-- Sidebar: en escritorio es una columna sticky; en <900px es el drawer (JS le pone role=dialog/aria-modal al abrirlo). --}}
        <aside id="catalog-sidebar" class="catalog-sidebar" aria-labelledby="catalog-sidebar-title">
            <div class="catalog-sidebar__head">
                <h2 id="catalog-sidebar-title" class="catalog-sidebar__title">Filtros</h2>
                <button type="button" class="catalog-sidebar__close" data-catalog-drawer-close aria-label="Cerrar filtros">&#10005;</button>
            </div>
            <div class="catalog-sidebar__body" data-catalog-scroll>
                <div data-catalog-region="sidebar">
                    @include('frontend.shop.catalog.partials.sidebar')
                </div>
            </div>
            <div class="catalog-sidebar__foot">
                <button type="button" class="catalog-sidebar__apply" data-catalog-drawer-close>
                    <span data-catalog-view-count>Ver {{ number_format($result->total) }} {{ $result->total === 1 ? 'resultado' : 'resultados' }}</span>
                </button>
            </div>
        </aside>

        <div id="catalog-results" class="catalog-results" data-catalog-page="{{ (int) ($result->meta['page'] ?? 1) }}">
            <h2 id="catalog-results-heading" class="sr-only" tabindex="-1">Resultados{{ $category ? ' de ' . $category->name : '' }}</h2>
            <div id="catalog-live" role="status" aria-live="polite" class="sr-only">{{ $result->meta['liveMessage'] ?? '' }}</div>

            <div data-catalog-region="toolbar">
                @include('frontend.shop.catalog.partials.toolbar')
            </div>
            <div data-catalog-region="chips">
                @include('frontend.shop.catalog.partials.chips')
            </div>
            <div data-catalog-region="products">
                @include('frontend.shop.catalog.partials.grid')
            </div>
            <div class="catalog-results__pagination" data-catalog-region="pagination">
                {{ $result->paginator->links('frontend.shop.partials.pagination') }}
            </div>
        </div>
    </div>

    @if ($schemaFaqs->isNotEmpty())
        <section class="home-faq">
            <h2 class="home-faq__title">Preguntas frecuentes</h2>
            <div class="home-faq__list" x-data="{ open: null }">
                @foreach ($schemaFaqs as $i => $item)
                    <div class="home-faq__item">
                        <button type="button" class="home-faq__question"
                            @click="open = open === {{ $i }} ? null : {{ $i }}">
                            <span>{{ $item['question'] }}</span>
                            <span x-text="open === {{ $i }} ? '−' : '+'">+</span>
                        </button>
                        <div class="home-faq__answer" x-show="open === {{ $i }}" x-cloak>
                            <p>{!! \App\Support\TextLinks::render($item['answer']) !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
