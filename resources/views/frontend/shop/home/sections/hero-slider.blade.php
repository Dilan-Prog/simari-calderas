@php $slides = $section->slides; @endphp
@if ($slides->count() > 0)
<section class="hero-slider" x-data="heroCarousel({{ $slides->count() }})">
    <div class="hero-slider__track" :style="`transform: translateX(-${active * 100}%)`">
        @foreach ($slides as $slide)
            @if ($slide->link_url)
                <a href="{{ $slide->link_url }}" class="hero-slider__slide" style="background-image:url('{{ $slide->image_url }}')" aria-label="{{ $slide->title }}"></a>
            @else
                <div class="hero-slider__slide" style="background-image:url('{{ $slide->image_url }}')"></div>
            @endif
        @endforeach
    </div>

    <button type="button" class="hero-slider__nav hero-slider__nav--prev" @click="prev()" aria-label="Anterior">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <button type="button" class="hero-slider__nav hero-slider__nav--next" @click="next()" aria-label="Siguiente">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div class="hero-slider__dots">
        @foreach ($slides as $i => $slide)
            <button type="button" :class="{ 'is-active': active === {{ $i }} }" @click="goTo({{ $i }})"></button>
        @endforeach
    </div>
</section>
@endif
