@php
    $hasDiscount = $product->compare_price && $product->compare_price > $product->price;
    $discountPct = $hasDiscount ? round((1 - ($product->price / $product->compare_price)) * 100) : null;
    $currency = $product->currency ?? 'MXN';
@endphp
<div class="product-price-box">
    <div class="product-price-box__eyebrow">{{ $product->availability === 'available' ? 'Disponible' : 'Sobre pedido' }} &nbsp;|&nbsp; SKU {{ $product->sku }}</div>
    <h1 class="product-price-box__title">{{ $product->name }}</h1>

    @if ($hasDiscount)
        <div class="product-price-box__discount-row">
            <span class="product-price-box__discount-badge">{{ $discountPct }}% OFF</span>
            <span class="product-price-box__original">${{ number_format($product->compare_price, 2) }}</span>
        </div>
    @endif
    <div class="product-price-box__price">${{ number_format($product->price, 2) }} {{ $currency }}</div>
    <div class="product-price-box__iva">IVA incluido</div>
    <div class="product-price-box__shipping">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
        Envío gratis a toda la República Mexicana
    </div>

    <div class="product-price-box__actions">
        <button type="button" class="product-price-box__add-btn" disabled title="Próximamente">Agregar al carrito</button>
        <button type="button" class="product-price-box__quote-btn" @click="$store.shop.openQuote()">Solicitar cotización</button>
    </div>

    @if ($product->short_description)
        <div class="product-price-box__highlights">
            <div class="product-price-box__highlights-title">Lo que debes saber de este producto</div>
            <p>{{ $product->resolveVariables($product->short_description) }}</p>
        </div>
    @endif
</div>
