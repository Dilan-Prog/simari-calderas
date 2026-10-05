@props(['title' => null, 'products' => [], 'banner' => null, 'viewAllUrl' => null, 'headingUrl' => null, 'headingNewTab' => false])

<section class="product-carousel {{ $banner ? 'product-carousel--with-banner' : '' }}" x-data="carouselTrack()">
    @if ($title || $viewAllUrl)
        <div class="product-carousel__header">
            @if ($title)
                <h2 class="product-carousel__title">
                    @if ($headingUrl)
                        {{-- Texto ancla = título (SEO). Solo se pinta <a> si el destino resuelve. --}}
                        <a href="{{ $headingUrl }}" rel="noopener" @if ($headingNewTab) target="_blank" @endif>{{ $title }}</a>
                    @else
                        {{ $title }}
                    @endif
                </h2>
            @endif
            @if ($viewAllUrl)
                <a href="{{ $viewAllUrl }}" class="product-carousel__view-all">Ver todo ›</a>
            @endif
        </div>
    @endif
    <div class="product-carousel__wrap">
        @if ($banner && !empty($banner['image_url']))
            @php
                $bannerAlt = ($banner['alt'] ?? '') ?: ($title ?? '');
                $bannerNewTab = !empty($banner['new_tab']);
            @endphp
            @if (empty($banner['no_link']) && !empty($banner['link_url']))
                <a href="{{ $banner['link_url'] }}" class="product-carousel__banner-link" @if ($bannerNewTab) target="_blank" rel="noopener" @endif>
                    <img src="{{ $banner['image_url'] }}" alt="{{ $bannerAlt }}" class="product-carousel__banner-img">
                </a>
            @else
                <div class="product-carousel__banner-link">
                    <img src="{{ $banner['image_url'] }}" alt="{{ $bannerAlt }}" class="product-carousel__banner-img">
                </div>
            @endif
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
