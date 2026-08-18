@php
    // Comparación en unidades consistentes: base_price/compare_base_price ya
    // vienen convertidos a MXN y sin IVA, igual que lo que se muestra abajo.
    $hasDiscount = $product->compare_base_price && $product->compare_base_price > $product->base_price;
    $discountPct = $hasDiscount ? round((1 - ($product->base_price / $product->compare_base_price)) * 100) : null;
    // Fijo a MXN: base_price/compare_base_price ya vienen convertidos.
    $currency = 'MXN';
    // $resolvedName ya viene calculado por show.blade.php (@include comparte
    // su scope) -- no se recalcula aquí.
    $quoteMessage = "Hola, me interesa cotizar este producto: {$resolvedName} (SKU: {$product->sku}) - " . route('product.show', $product->slug);
    $quoteWhatsappUrl = 'https://wa.me/' . \App\Models\Setting::get('footer.phone_link', '5214494577320') . '?text=' . urlencode($quoteMessage);
@endphp
<div class="product-price-box">
    <div class="product-price-box__eyebrow">{{ $product->availability_label }} &nbsp;|&nbsp; SKU {{ $product->sku }}</div>
    <h1 class="product-price-box__title">{{ $resolvedName }}</h1>

    @if ($hasDiscount)
        <div class="product-price-box__discount-row">
            <span class="product-price-box__discount-badge">{{ $discountPct }}% OFF</span>
            <span class="product-price-box__original">${{ number_format($product->compare_base_price, 2) }}</span>
        </div>
    @endif
    <div class="product-price-box__price">${{ number_format($product->base_price, 2) }} {{ $currency }}</div>
    <div class="product-price-box__iva">Precio+ IVA</div>
    @if (!$product->shipping_cost || $product->shipping_cost <= 0)
        <div class="product-price-box__shipping">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Envío gratis a toda la República Mexicana
        </div>
    @elseif (!$product->free_shipping_threshold)
        <div class="product-price-box__shipping-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Recíbelo por ${{ number_format($product->shipping_cost, 2) }} de envío
        </div>
    @else
        <div class="product-price-box__shipping-note">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
            Envío gratis en compras desde ${{ number_format($product->free_shipping_threshold, 2) }} MXN. Antes de eso, el envío tiene un costo de ${{ number_format($product->shipping_cost, 2) }}.
        </div>
    @endif

    <div class="product-price-box__actions">
        <button type="button" class="product-price-box__add-btn" data-product-id="{{ $product->id }}" @if(!$product->is_purchasable) disabled @endif>
            {{ $product->is_purchasable ? 'Agregar al carrito' : 'No disponible' }}
        </button>
        <a href="{{ $quoteWhatsappUrl }}" target="_blank" rel="noopener nofollow" class="product-price-box__quote-btn">Solicitar cotización</a>
    </div>

    @if ($product->short_description)
        <div class="product-price-box__highlights">
            <div class="product-price-box__highlights-title">Lo que debes saber de este producto</div>
            <p>{{ $product->resolveVariables($product->short_description) }}</p>
        </div>
    @endif

    @php
        $quickFacts = collect([
            ['label' => 'Marca', 'value' => $product->brand?->name],
            ['label' => 'Modelo', 'value' => $product->model],
            ['label' => 'SKU', 'value' => $product->sku],
            ['label' => 'Categoría', 'value' => $product->category?->name],
            ['label' => 'Disponibilidad', 'value' => $product->availability_label],
            ['label' => 'Unidad de medida', 'value' => $product->stock_unit],
        ])->filter(fn ($fact) => filled($fact['value']));
    @endphp
    @if ($quickFacts->isNotEmpty())
        <dl class="product-price-box__facts">
            @foreach ($quickFacts as $fact)
                <div class="product-price-box__facts-row">
                    <dt>{{ $fact['label'] }}</dt>
                    <dd>{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>
