@props(['product'])

@php
    // Línea de envío de las tarjetas de producto -- fuente única de la
    // lógica: Products::shippingInfo() (producto > regla de marca > regla de
    // categoría > gratis). Usada por product-card y por el carrusel compacto.
    $ship = $product->shippingInfo();
@endphp
@if ($ship['cost'] <= 0)
    <div class="product-card__shipping">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
        Envío gratis
    </div>
@elseif (!$ship['threshold'])
    <div class="product-card__shipping product-card__shipping--paid">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
        Envío: ${{ number_format($ship['cost'], 2) }} MXN
    </div>
@else
    <div class="product-card__shipping product-card__shipping--paid">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
        Envío gratis desde ${{ number_format($ship['threshold'], 2) }} MXN
    </div>
@endif
