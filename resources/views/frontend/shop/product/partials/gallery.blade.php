@php
    $galleryImages = $product->images;
    $coverFallback = $product->cover_image_url ?? asset('images/logo/equiterm-logo-blanco-color-3x.png');
    $imageUrls = $galleryImages->count() > 0 ? $galleryImages->pluck('url')->values() : collect([$coverFallback]);
@endphp
<div class="product-gallery" x-data="productGallery({{ $imageUrls->toJson() }})">
    <div class="product-gallery__main-row">
        <div class="product-gallery__thumbs">
            @forelse ($galleryImages as $i => $image)
                <button type="button" :class="{ 'is-active': active === {{ $i }} }" @click="select({{ $i }})">
                    <img src="{{ $image->url }}" alt="{{ $image->alt_text ?? $product->name }}">
                </button>
            @empty
                <button type="button" class="is-active">
                    <img src="{{ $coverFallback }}" alt="{{ $product->name }}">
                </button>
            @endforelse
        </div>
        <div class="product-gallery__main" @mousemove="onZoomMove($event)" @mouseenter="isZooming = true" @mouseleave="isZooming = false" @click="openLightbox()">
            <template x-if="images.length">
                <img :src="images[active]" :alt="'{{ addslashes($product->name) }}'" class="product-gallery__main-img">
            </template>
            <div class="product-gallery__zoom-lens" x-show="isZooming" x-cloak :style="lensStyle"></div>
        </div>
    </div>

    <div class="product-gallery__lightbox" x-show="lightboxOpen" x-cloak @click="closeLightbox()">
        <div class="product-gallery__lightbox-inner" @click.stop>
            <button type="button" class="product-gallery__lightbox-close" @click="closeLightbox()">×</button>
            <button type="button" class="product-gallery__lightbox-nav product-gallery__lightbox-nav--prev" @click="prev()">‹</button>
            <img :src="images[active]" alt="">
            <button type="button" class="product-gallery__lightbox-nav product-gallery__lightbox-nav--next" @click="next()">›</button>
        </div>
    </div>
</div>
