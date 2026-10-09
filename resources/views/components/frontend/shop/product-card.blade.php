@props(['product', 'compact' => false])

@php
    // Comparación en unidades consistentes: base_price/compare_base_price ya
    // vienen convertidos a MXN y sin IVA, igual que lo que se muestra abajo.
    $hasDiscount = $product->compare_base_price && $product->compare_base_price > $product->base_price;
    // Etiquetas de la esquina de la imagen (máx. 2): Más vendido, -X%, Últimas
    // piezas, Nuevo. El porcentaje de descuento vive SOLO en la etiqueta "-X%"
    // (BadgeResolver); la fila de precio ya no repite "% OFF".
    $badges = \App\Services\Catalog\BadgeResolver::for($product);
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
    @if (count($badges))
        <div class="product-card__badges">
            @foreach ($badges as $badge)
                <span class="product-card__badge product-card__badge--{{ $badge['key'] }}" style="--badge-bg: {{ $badge['bg'] }}; --badge-fg: {{ $badge['fg'] }};">{{ $badge['label'] }}</span>
            @endforeach
        </div>
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
        <div class="product-card__price-row">
            <span class="product-card__price">${{ number_format($product->base_price, 2) }} {{ $currency }}</span>
            @if ($hasDiscount)
                <s class="product-card__original"><span class="product-card__sr">Precio anterior: </span>${{ number_format($product->compare_base_price, 2) }} {{ $currency }}</s>
            @endif
            <span class="product-card__iva-note">Precio + IVA</span>
        </div>
        <x-frontend.shop.shipping-line :product="$product" />
    </a>
    <button type="button" class="product-card__add-btn" data-product-id="{{ $product->id }}"
        data-sku="{{ $product->sku }}" data-name="{{ $resolvedName }}" data-price="{{ $product->base_price }}">Agregar al carrito</button>
    {{-- Acción secundaria: enlace de texto discreto (conserva la clase
         .product-card__quote-btn, data-ad-track y wa.me que usan el tracking
         y el carrusel compacto). --}}
    <a href="{{ $quoteWhatsappUrl }}" target="_blank" rel="noopener nofollow" class="product-card__quote-btn" data-ad-track="quote_start" data-product-id="{{ $product->id }}">Solicitar cotización<span class="product-card__sr"> (se abre en una pestaña nueva)</span></a>
</div>
