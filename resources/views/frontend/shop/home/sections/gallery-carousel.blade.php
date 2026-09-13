@php
    $imgIds = $section->config['image_ids'] ?? [];
    $images = empty($imgIds)
        ? $servicePage->images
        : $servicePage->images->whereIn('id', $imgIds)->values();
@endphp
@if ($images->isNotEmpty())
<section class="svc-gallery" x-data="{ active: 0 }" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <h2 class="svc-gallery__title">{{ $section->title }}</h2>
    @endif
    <div class="svc-gallery__main">
        @foreach ($images as $i => $img)
            <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? '' }}" x-show="active === {{ $i }}" @if($i > 0) x-cloak @endif>
        @endforeach
    </div>
    @if ($images->count() > 1)
        <div class="svc-gallery__thumbs">
            @foreach ($images as $i => $img)
                <button type="button" class="svc-gallery__thumb" :class="{ 'is-active': active === {{ $i }} }" @click="active = {{ $i }}">
                    <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? '' }}">
                </button>
            @endforeach
        </div>
    @endif
</section>
@endif
