@props(['product', 'compact' => false])

@php
    // Comparación en unidades consistentes: base_price/compare_base_price ya
    // vienen convertidos a MXN y sin IVA, igual que lo que se muestra abajo.
    $hasDiscount = $product->compare_base_price && $product->compare_base_price > $product->base_price;
    $discountPct = $hasDiscount ? round((1 - ($product->base_price / $product->compare_base_price)) * 100) : null;
    // Fijo a MXN: base_price/compare_base_price ya vienen convertidos.
    $currency = 'MXN';
    $galleryUrls = $product->images->pluck('url')->filter()->values();
    $hasGallery = $galleryUrls->count() > 1;
    $imageUrl = $product->cover_image_url
        ?? $galleryUrls->first()
        ?? asset('images/logo/equiterm-logo-blanco-color-3x.png');
    $resolvedName = $product->resolveVariables($product->name);
    $quoteMessage = "Hola, me interesa cotizar este producto: {$resolvedName} (SKU: {$product->sku}) - " . route('product.show', $product->slug);
    $quoteWhatsappUrl = 'https://wa.me/' . \App\Models\Setting::get('footer.phone_link', '5214494577320') . '?text=' . urlencode($quoteMessage);
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
            <div class="product-card__original">${{ number_format($product->compare_base_price, 2) }} {{ $currency }}</div>
        @endif
        <div class="product-card__price-row">
            <span class="product-card__price">${{ number_format($product->base_price, 2) }} {{ $currency }}</span>
            <span class="product-card__iva-note">Precio + IVA</span>
            @if ($hasDiscount)
                <span class="product-card__discount">{{ $discountPct }}% OFF</span>
            @endif
        </div>
        @if (!$product->shipping_cost || $product->shipping_cost <= 0)
            <div class="product-card__shipping">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                Envío gratis
            </div>
        @elseif (!$product->free_shipping_threshold)
            <div class="product-card__shipping product-card__shipping--paid">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                Envío: ${{ number_format($product->shipping_cost, 2) }} MXN
            </div>
        @else
            <div class="product-card__shipping product-card__shipping--paid">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                Envío gratis desde ${{ number_format($product->free_shipping_threshold, 2) }} MXN
            </div>
        @endif
    </a>
    <button type="button" class="product-card__add-btn" data-product-id="{{ $product->id }}"
        data-sku="{{ $product->sku }}" data-name="{{ $resolvedName }}" data-price="{{ $product->base_price }}">Agregar al carrito</button>
    <a href="{{ $quoteWhatsappUrl }}" target="_blank" rel="noopener nofollow" class="product-card__quote-btn" data-ad-track="quote_start" data-product-id="{{ $product->id }}">Solicitar Cotización</a>
</div>
