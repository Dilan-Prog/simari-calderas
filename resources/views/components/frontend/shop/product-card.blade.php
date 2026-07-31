@props(['product', 'compact' => false])

@php
    // NOTA: compare_price no tiene su propia bandera de IVA — el % de
    // descuento aquí sigue comparando contra el precio crudo, no contra
    // final_price. Fuera de alcance por ahora (no pedido por el usuario).
    $hasDiscount = $product->compare_price && $product->compare_price > $product->price;
    $discountPct = $hasDiscount ? round((1 - ($product->price / $product->compare_price)) * 100) : null;
    $currency = $product->currency ?? 'MXN';
    $galleryUrls = $product->images->pluck('url')->filter()->values();
    $hasGallery = $galleryUrls->count() > 1;
    $imageUrl = $product->cover_image_url
        ?? $galleryUrls->first()
        ?? asset('images/logo/equiterm-logo-blanco-color-3x.png');
    $resolvedName = $product->resolveVariables($product->name);
@endphp
<div class="product-card {{ $compact ? 'product-card--compact' : '' }}">
    @if ($product->is_new)
        <span class="product-card__badge">Nuevo</span>
    @elseif ($hasDiscount)
        <span class="product-card__badge">{{ $discountPct }}% OFF</span>
    @endif

    <a href="{{ route('product.show', $product->slug) }}" class="product-card__link">
        @if ($hasGallery)
            <div class="product-card__img-wrap" x-data="productCardGallery({{ $galleryUrls->count() }})">
                @foreach ($galleryUrls as $i => $url)
                    <img src="{{ $url }}" alt="{{ $resolvedName }}" class="product-card__img product-card__img--slide" :class="{ 'is-active': active === {{ $i }} }" loading="lazy">
                @endforeach
                <button type="button" class="product-card__img-nav product-card__img-nav--prev" @click.stop.prevent="prev()" aria-label="Imagen anterior">‹</button>
                <button type="button" class="product-card__img-nav product-card__img-nav--next" @click.stop.prevent="next()" aria-label="Imagen siguiente">›</button>
                <div class="product-card__img-dots">
                    @foreach ($galleryUrls as $i => $url)
                        <span class="product-card__img-dot" :class="{ 'is-active': active === {{ $i }} }"></span>
                    @endforeach
                </div>
            </div>
        @else
            <div class="product-card__img-wrap">
                <img src="{{ $imageUrl }}" alt="{{ $resolvedName }}" class="product-card__img" loading="lazy">
            </div>
        @endif
        <div class="product-card__name">{{ $resolvedName }}</div>
        <div class="product-card__sku">{{ $product->sku }}</div>
        @if ($hasDiscount)
            <div class="product-card__original">${{ number_format($product->compare_price, 2) }} {{ $currency }}</div>
        @endif
        <div class="product-card__price-row">
            <span class="product-card__price">${{ number_format($product->final_price, 2) }} {{ $currency }}</span>
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
