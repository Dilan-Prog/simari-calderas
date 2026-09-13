@php
    // Config nuevo (picker sitewide): {images:[{url,alt}]}. Config viejo
    // (galería propia del servicio, antes de la expansión del editor en
    // vivo): {image_ids:[...]} o vacío = toda la galería propia -- se
    // sigue leyendo tal cual para no romper páginas ya guardadas, sin
    // migración de datos.
    if (!empty($section->config['images'])) {
        $images = collect($section->config['images'])->map(fn ($i) => (object) [
            'url' => $i['url'] ?? '',
            'alt_text' => $i['alt'] ?? '',
        ])->filter(fn ($i) => $i->url !== '')->values();
    } else {
        $imgIds = $section->config['image_ids'] ?? [];
        $images = empty($imgIds)
            ? $servicePage->images
            : $servicePage->images->whereIn('id', $imgIds)->values();
    }
    $titleStyle = $section->config['title_style'] ?? null;
    $titleTag = \App\Support\TextStyle::tag($titleStyle, 'h2', ['h2', 'h3']);
@endphp
@if ($images->isNotEmpty())
<section class="svc-gallery" x-data="{ active: 0 }" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <{{ $titleTag }} class="svc-gallery__title"{!! \App\Support\TextStyle::attr($titleStyle) !!}>{{ $section->title }}</{{ $titleTag }}>
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
