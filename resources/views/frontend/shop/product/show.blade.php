@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/product-detail.css', 'resources/js/frontend/shop/product-detail.js'];
@endphp

@section('title', $product->seo_title ?: ($product->name . ' — Equiterm Industries'))
@section('description', $product->seo_description ?: $product->short_description)

@section('content')
<div class="eq-shop-product">
    <div class="product-breadcrumb">
        <a href="{{ route('home') }}">Inicio</a>
        @if ($product->category?->parent)
            &nbsp;›&nbsp; <a href="{{ route('catalog.category', $product->category->parent->slug) }}">{{ $product->category->parent->name }}</a>
        @endif
        @if ($product->category)
            &nbsp;›&nbsp; <a href="{{ route('catalog.category', $product->category->slug) }}">{{ $product->category->name }}</a>
        @endif
        &nbsp;›&nbsp; <span>{{ $product->name }}</span>
    </div>

    <section class="product-main">
        <div class="product-main__left">
            @include('frontend.shop.product.partials.gallery')
            <div class="product-description">
                @if ($product->description)
                    <div class="product-description__title">Descripción</div>
                    <p>{{ $product->description }}</p>
                @endif
            </div>
            @include('frontend.shop.product.partials.specs-table')
            @include('frontend.shop.product.partials.documents-grid')
        </div>
        <div class="product-main__right" x-data>
            @include('frontend.shop.product.partials.price-box')
        </div>
    </section>

    <x-frontend.shop.product-carousel title="También te puede interesar" :products="$related" />

    <section class="product-qna-reviews">
        @include('frontend.shop.product.partials.qna-section')
        @include('frontend.shop.product.partials.reviews-section')
    </section>

    <x-frontend.shop.product-carousel title="Clientes que compraron esto también compraron" :products="$related" />
    <x-frontend.shop.product-carousel title="Más vendidos en {{ $product->category->name ?? 'esta categoría' }}" :products="$related" />
</div>
@endsection
