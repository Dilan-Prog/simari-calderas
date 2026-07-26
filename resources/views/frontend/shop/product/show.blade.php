@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/product-detail.css', 'resources/js/frontend/shop/product-detail.js'];
@endphp

@section('title', $product->seo_title ?: ($product->name . ' — Equiterm Industries'))
@section('description', $product->resolveVariables($product->seo_description ?: $product->short_description))

@php
    // JSON-LD FAQPage: solo si el producto tiene FAQs Y la sección faq está
    // activa en el CMS (coherencia con lo que realmente se ve en la página).
    $schemaFaqs = collect($product->faqs ?? [])
        ->filter(fn ($item) => !empty($item['question']) && !empty($item['answer']))
        ->values();
    $faqSectionActive = $sections->contains(fn ($s) => $s->type === 'faq');
@endphp

@if ($faqSectionActive && $schemaFaqs->isNotEmpty())
    @section('schema')
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
    @endsection
@endif

@section('content')
<div class="eq-shop-product">
    @php
        $categoryChain = [];
        $chainCat = $product->category;
        while ($chainCat) {
            array_unshift($categoryChain, $chainCat);
            $chainCat = $chainCat->parent;
        }
    @endphp
    <div class="product-breadcrumb">
        <a href="{{ route('home') }}">Inicio</a>
        @foreach ($categoryChain as $chainCat)
            &nbsp;›&nbsp; <a href="{{ route('catalog.category', $chainCat->slug) }}">{{ $chainCat->name }}</a>
        @endforeach
        &nbsp;›&nbsp; <span>{{ $product->name }}</span>
    </div>

    <section class="product-main">
        <div class="product-main__left">
            @include('frontend.shop.product.partials.gallery')
            <div class="product-description">
                @if ($product->description)
                    <div class="product-description__title">Descripción</div>
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
        @include('frontend.shop.home.sections.' . str_replace('_', '-', $section->type), ['section' => $section])
    @endforeach
</div>
@endsection
