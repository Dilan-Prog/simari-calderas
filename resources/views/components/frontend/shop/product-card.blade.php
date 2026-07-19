@props(['product', 'compact' => false])

@php
    $hasDiscount = $product->compare_price && $product->compare_price > $product->price;
    $discountPct = $hasDiscount ? round((1 - ($product->price / $product->compare_price)) * 100) : null;
    $currency = $product->currency ?? 'MXN';
@endphp
<div class="product-card {{ $compact ? 'product-card--compact' : '' }}">
    @if ($product->is_new)
        <span class="product-card__badge">Nuevo</span>
    @elseif ($hasDiscount)
        <span class="product-card__badge">{{ $discountPct }}% OFF</span>
    @endif

    <a href="{{ route('product.show', $product->slug) }}" class="product-card__link">
        <div class="product-card__img-wrap">
            <img src="{{ $product->cover_image_url ?? asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="{{ $product->name }}" class="product-card__img" loading="lazy">
        </div>
        <div class="product-card__name">{{ $product->name }}</div>
        <div class="product-card__sku">{{ $product->sku }}</div>
        @if ($hasDiscount)
            <div class="product-card__original">${{ number_format($product->compare_price, 2) }} {{ $currency }}</div>
        @endif
        <div class="product-card__price-row">
            <span class="product-card__price">${{ number_format($product->price, 2) }} {{ $currency }}</span>
            @if ($hasDiscount)
                <span class="product-card__discount">{{ $discountPct }}% OFF</span>
            @endif
        </div>
        <div class="product-card__shipping">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Envío gratis
        </div>
    </a>
    <button type="button" class="product-card__add-btn" disabled title="Próximamente">Agregar al carrito</button>
</div>
