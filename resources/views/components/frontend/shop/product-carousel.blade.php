@props(['title' => null, 'products' => [], 'banner' => null])

<section class="product-carousel {{ $banner ? 'product-carousel--with-banner' : '' }}" x-data="carouselTrack()">
    @if ($title)
        <h2 class="product-carousel__title">{{ $title }}</h2>
    @endif
    <div class="product-carousel__wrap">
        @if ($banner && !empty($banner['image_url']))
            <a href="{{ $banner['link_url'] ?: '#' }}" class="product-carousel__banner-link">
                <img src="{{ $banner['image_url'] }}" alt="{{ $banner['alt'] ?: $title }}" class="product-carousel__banner-img">
            </a>
        @endif
        <div class="product-carousel__track-wrap">
            <div class="product-carousel__track" x-ref="track">
                @forelse ($products as $product)
                    <div class="product-carousel__item">
                        <x-frontend.shop.product-card :product="$product" />
                    </div>
                @empty
                    <p class="product-carousel__empty">Aún no hay productos en esta sección.</p>
                @endforelse
            </div>
            @if (count($products) > 0)
                <button type="button" class="product-carousel__nav product-carousel__nav--prev" @click="prev()" aria-label="Anterior">‹</button>
                <button type="button" class="product-carousel__nav product-carousel__nav--next" @click="next()" aria-label="Siguiente">›</button>
            @endif
        </div>
    </div>
</section>
