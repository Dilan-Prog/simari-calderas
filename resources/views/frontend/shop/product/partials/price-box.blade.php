@php
    // Comparación en unidades consistentes: base_price/compare_base_price ya
    // vienen convertidos a MXN y sin IVA, igual que lo que se muestra abajo.
    $hasDiscount = $product->compare_base_price && $product->compare_base_price > $product->base_price;
    $discountPct = $hasDiscount ? round((1 - ($product->base_price / $product->compare_base_price)) * 100) : null;
    // Fijo a MXN: base_price/compare_base_price ya vienen convertidos.
    $currency = 'MXN';
    $quoteMessage = "Hola, me interesa cotizar este producto: {$product->resolveVariables($product->name)} (SKU: {$product->sku}) - " . route('product.show', $product->slug);
    $quoteWhatsappUrl = 'https://wa.me/524494348018?text=' . urlencode($quoteMessage);
@endphp
<div class="product-price-box">
    <div class="product-price-box__eyebrow">{{ $product->availability === 'available' ? 'Disponible' : 'Sobre pedido' }} &nbsp;|&nbsp; SKU {{ $product->sku }}</div>
    <h1 class="product-price-box__title">{{ $product->resolveVariables($product->name) }}</h1>

    @if ($hasDiscount)
        <div class="product-price-box__discount-row">
            <span class="product-price-box__discount-badge">{{ $discountPct }}% OFF</span>
            <span class="product-price-box__original">${{ number_format($product->compare_base_price, 2) }}</span>
        </div>
    @endif
    <div class="product-price-box__price">${{ number_format($product->base_price, 2) }} {{ $currency }}</div>
    <div class="product-price-box__iva">Precio+ IVA</div>
    <div class="product-price-box__shipping">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
        Envío gratis a toda la República Mexicana
    </div>

    @if ($product->shipping_cost > 0)
        <div class="product-price-box__shipping-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Recíbelo por ${{ number_format($product->shipping_cost, 2) }} de envío
        </div>
    @endif

    <div class="product-price-box__actions">
        <button type="button" class="product-price-box__add-btn" data-product-id="{{ $product->id }}">Agregar al carrito</button>
        <a href="{{ $quoteWhatsappUrl }}" target="_blank" rel="noopener nofollow" class="product-price-box__quote-btn">Solicitar cotización</a>
    </div>

    @if ($product->short_description)
        <div class="product-price-box__highlights">
            <div class="product-price-box__highlights-title">Lo que debes saber de este producto</div>
            <p>{{ $product->resolveVariables($product->short_description) }}</p>
        </div>
    @endif
</div>
