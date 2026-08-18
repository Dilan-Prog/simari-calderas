@php
    // $resolvedName ya viene calculado por show.blade.php (@include comparte
    // su scope) -- no se recalcula aquí para no pagar resolveVariables() de
    // nuevo por cada vista que incluya este partial.
    $galleryImages = $product->images;
    $coverFallback = $product->cover_image_url ?? asset('images/logo/equiterm-logo-blanco-color-3x.png');
    $imageUrls = $galleryImages->count() > 0 ? $galleryImages->pluck('url')->values() : collect([$coverFallback]);
    $imageLabels = $galleryImages->count() > 0
        ? $galleryImages->map(fn ($img) => (string) ($img->alt_text ?? ''))->values()
        : collect(['']);
@endphp
<div class="product-gallery" x-data="productGallery({{ $imageUrls->toJson() }}, {{ $imageLabels->toJson() }})">
    <div class="product-gallery__main-row">
        <div class="product-gallery__thumbs">
            @forelse ($galleryImages as $i => $image)
                <button type="button" :class="{ 'is-active': active === {{ $i }} }" @click="select({{ $i }})">
                    <img src="{{ $image->url }}" alt="{{ $image->alt_text ?? $resolvedName }}">
                </button>
            @empty
                <button type="button" class="is-active">
                    <img src="{{ $coverFallback }}" alt="{{ $resolvedName }}">
                </button>
            @endforelse
        </div>
        <div class="product-gallery__main" @mousemove="onZoomMove($event)" @mouseenter="isZooming = true" @mouseleave="isZooming = false" @click="openLightbox()">
            @if ($hasDiscount)
                <div class="product-gallery__discount-badge">{{ $discountPct }}% OFF</div>
            @endif
            <template x-if="images.length">
                <img :src="images[active]" :alt="'{{ addslashes($resolvedName) }}'" class="product-gallery__main-img">
            </template>
            <div class="product-gallery__zoom-lens" x-show="isZooming" x-cloak :style="lensStyle"></div>
        </div>
    </div>

    <div class="product-gallery__dots" x-show="images.length > 1">
        <template x-for="(img, i) in images" :key="i">
            <button type="button" class="product-gallery__dot" :class="{ 'is-active': active === i }" @click="select(i)" :aria-label="'Ver imagen ' + (i+1)"></button>
        </template>
    </div>

    <div class="product-gallery__lightbox" x-show="lightboxOpen" x-cloak @click.self="closeLightbox()" @keydown.escape.window="closeLightbox()" @keydown.arrow-left.window="prev()" @keydown.arrow-right.window="next()">
        <div class="product-gallery__lightbox-panel">
            <div class="product-gallery__lightbox-header">
                <h3>{{ $resolvedName }}</h3>
                <button type="button" class="product-gallery__lightbox-close" @click="closeLightbox()" aria-label="Cerrar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="product-gallery__lightbox-body">
                <div class="product-gallery__lightbox-thumbs" x-show="images.length > 1">
                    <template x-for="(img, i) in images" :key="i">
                        <button type="button" :class="{ 'is-active': active === i }" @click="select(i)">
                            <span class="product-gallery__lightbox-thumb-img"><img :src="img" :alt="labels[i] || ''" loading="lazy"></span>
                            <span class="product-gallery__lightbox-thumb-label" x-show="labels[i]" x-text="labels[i]"></span>
                        </button>
                    </template>
                </div>
                <div class="product-gallery__lightbox-stage">
                    <button type="button" class="product-gallery__lightbox-nav product-gallery__lightbox-nav--prev" @click="prev()" x-show="images.length > 1" aria-label="Anterior">‹</button>
                    <img :src="images[active]" :alt="labels[active] || '{{ addslashes($resolvedName) }}'" loading="lazy">
                    <button type="button" class="product-gallery__lightbox-nav product-gallery__lightbox-nav--next" @click="next()" x-show="images.length > 1" aria-label="Siguiente">›</button>
                </div>
            </div>
        </div>
    </div>
</div>
