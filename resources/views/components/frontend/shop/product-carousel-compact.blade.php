@props(['title' => null, 'eyebrow' => null, 'products' => [], 'headingUrl' => null, 'headingNewTab' => false])

@php
    $quotePhone = \App\Models\Setting::get('footer.phone_link', '5214494577320');
@endphp

{{-- Versión compacta del carrusel de productos, para la zona "sidebar" de la
     página de producto (columna derecha, arriba de "Medios de pago"):
     etiqueta + título a la izquierda, flechas a la derecha, tarjetas chicas
     (imagen, SKU, nombre, precio, "Agregar") e indicador de páginas abajo.
     Los botones "Agregar al carrito" / "Solicitar cotización" reutilizan
     .product-card__add-btn (+ data-*) y .product-card__quote-btn (+
     data-ad-track) para que los tomen los mismos handlers de carrito y de
     tracking que las tarjetas normales. --}}
<section class="pc-compact" x-data="{
    page: 0, pages: 1,
    measure() {
        const t = this.$refs.track;
        if (!t || !t.clientWidth) return;
        this.pages = Math.max(1, Math.round(t.scrollWidth / t.clientWidth));
        this.page = Math.min(this.pages - 1, Math.round(t.scrollLeft / t.clientWidth));
    },
    go(dir) { const t = this.$refs.track; t.scrollBy({ left: dir * t.clientWidth, behavior: 'smooth' }); },
}" x-init="$nextTick(() => measure())" @resize.window.debounce.150ms="measure()">
    @if ($title || $eyebrow)
        <header class="pc-compact__header">
            <div class="pc-compact__heading">
                @if ($eyebrow)
                    <p class="pc-compact__eyebrow">{{ $eyebrow }}</p>
                @endif
                @if ($title)
                    <h2 class="pc-compact__title">
                        @if ($headingUrl)
                            <a href="{{ $headingUrl }}" rel="noopener" @if ($headingNewTab) target="_blank" @endif>{{ $title }}</a>
                        @else
                            {{ $title }}
                        @endif
                    </h2>
                @endif
            </div>
            <div class="pc-compact__nav" x-show="pages > 1" x-cloak>
                <button type="button" class="pc-compact__arrow" @click="go(-1)" :disabled="page === 0" aria-label="Anterior">‹</button>
                <button type="button" class="pc-compact__arrow" @click="go(1)" :disabled="page >= pages - 1" aria-label="Siguiente">›</button>
            </div>
        </header>
    @endif

    <div class="pc-compact__track" x-ref="track" @scroll.passive.debounce.80ms="measure()">
        @foreach ($products as $item)
            @php
                $itemName = $item->resolveVariables($item->name);
                $quoteMessage = "Hola, me interesa cotizar este producto: {$itemName} (SKU: {$item->sku}) - " . route('product.show', $item->slug);
                $quoteUrl = 'https://wa.me/' . $quotePhone . '?text=' . urlencode($quoteMessage);
                $itemImage = $item->cover_image_url
                    ?? $item->images->pluck('url')->filter()->first()
                    ?? asset('images/logo/equiterm-logo-blanco-color-3x.png');
            @endphp
            <article class="pc-compact__card">
                <a href="{{ route('product.show', $item->slug) }}" class="pc-compact__link">
                    <span class="pc-compact__img-wrap"><img src="{{ $itemImage }}" alt="{{ $itemName }}" loading="lazy"></span>
                    @if ($item->sku)
                        <span class="pc-compact__sku">{{ $item->sku }}</span>
                    @endif
                    <span class="pc-compact__name">{{ $itemName }}</span>
                    <span class="pc-compact__price-row">
                        <span class="pc-compact__price">${{ number_format($item->base_price, 2) }} MXN</span>
                        <span class="pc-compact__iva">Precio + IVA</span>
                    </span>
                    <x-frontend.shop.shipping-line :product="$item" />
                </a>
                <button type="button" class="product-card__add-btn pc-compact__add" data-product-id="{{ $item->id }}"
                    data-sku="{{ $item->sku }}" data-name="{{ $itemName }}" data-price="{{ $item->base_price }}">Agregar al carrito</button>
                <a href="{{ $quoteUrl }}" target="_blank" rel="noopener nofollow" class="product-card__quote-btn pc-compact__quote" data-ad-track="quote_start" data-product-id="{{ $item->id }}">Solicitar cotización<span class="product-card__sr"> (se abre en una pestaña nueva)</span></a>
            </article>
        @endforeach
    </div>

    <div class="pc-compact__dots" x-show="pages > 1" x-cloak aria-hidden="true">
        <template x-for="i in pages" :key="i">
            <span class="pc-compact__dot" :class="{ 'is-active': page === i - 1 }"></span>
        </template>
    </div>
</section>
