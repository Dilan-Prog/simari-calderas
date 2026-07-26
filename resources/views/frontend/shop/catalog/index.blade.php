@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/catalog.css', 'resources/js/frontend/shop/catalog.js'];
@endphp

@section('title', ($category->name ?? 'Catálogo') . ' — Equiterm Industries')

@section('content')
<div class="eq-shop-catalog">
    <section class="catalog-hero"></section>

    @if ($category)
        @php
            $categoryChain = [];
            $chainCat = $category;
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
</div>
@endsection
