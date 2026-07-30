@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/catalog.css', 'resources/js/frontend/shop/catalog.js'];

    $categoryChain = [];
    $chainCat = $category;
    while ($chainCat) {
        array_unshift($categoryChain, $chainCat);
        $chainCat = $chainCat->parent;
    }
@endphp

@section('title', ($category->name ?? 'Catálogo') . ' — Equiterm Industries')
@section('description', $category?->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags($category?->description ?? ''), 160)
    ?: ('Explora el catálogo de ' . ($category->name ?? 'productos') . ' de Equiterm Industries.'))

@php
    // JSON-LD BreadcrumbList: reusa $categoryChain (misma jerarquía que el
    // breadcrumb visual de abajo). No se toca el canonical/OG de esta
    // vista (bug conocido, se corrige aparte); esto solo agrega structured
    // data adicional.
    $catalogUrl = $category ? route('catalog.category', $category->slug) : route('catalog.index');

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
        'url' => $catalogUrl,
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
        @include('frontend.shop.catalog.partials.filters-sidebar')

        <div class="catalog-results">
            <div class="catalog-results__toolbar">
                <form method="GET" action="{{ $category ? route('catalog.category', $category->slug) : route('catalog.index') }}" class="catalog-results__sort">
                    @foreach (request()->except('orden') as $key => $value)
                        @foreach ((array) $value as $v)
                            <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">
                        @endforeach
                    @endforeach
                    <label>Ordenar:</label>
                    <select name="orden" onchange="this.form.submit()">
                        <option value="relevancia" @selected(request('orden', 'relevancia') === 'relevancia')>Relevancia</option>
                        <option value="descuento" @selected(request('orden') === 'descuento')>Mayor descuento</option>
                        <option value="precio_asc" @selected(request('orden') === 'precio_asc')>Precio: menor a mayor</option>
                        <option value="precio_desc" @selected(request('orden') === 'precio_desc')>Precio: mayor a menor</option>
                    </select>
                </form>
            </div>

            @if ($products->count() > 0)
                @foreach ($productsByCategory as $categoryName => $categoryProducts)
                    <x-frontend.shop.product-carousel :title="$categoryName" :products="$categoryProducts" />
                @endforeach

                <div class="catalog-results__pagination">
                    {{ $products->links() }}
                </div>
            @else
                <div class="catalog-results__empty">No hay productos que coincidan con estos filtros.</div>
            @endif
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
