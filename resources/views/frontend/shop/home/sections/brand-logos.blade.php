@php
    // Solo URLs http(s) o rutas relativas del sitio: el config lo captura un
    // admin, pero un enlace tipo "javascript:" nunca debe llegar al HTML.
    $safeUrl = fn ($u) => is_string($u) && preg_match('#^(https?://|/)#i', trim($u)) ? trim($u) : null;

    $logos = collect($section->config['logos'] ?? [])
        ->map(fn ($l) => [
            'url'  => $safeUrl($l['url'] ?? null),
            'alt'  => trim((string) ($l['alt'] ?? '')),
            'link' => $safeUrl($l['link_url'] ?? null),
        ])
        ->filter(fn ($l) => $l['url'])
        ->values();
    $titleStyle = $section->config['title_style'] ?? null;
    $titleTag = \App\Support\TextStyle::tag($titleStyle, 'h2', ['h2', 'h3']);
@endphp
@if ($logos->isNotEmpty())
<section class="svc-brand-logos {{ !empty($section->config['grayscale']) ? 'svc-brand-logos--gray' : '' }}" aria-label="{{ $section->title ?: 'Marcas' }}" @if($previewMode) data-section-id="{{ $section->id }}" @endif>
    @if ($section->title)
        <{{ $titleTag }} class="svc-brand-logos__title"{!! \App\Support\TextStyle::attr($titleStyle) !!}>{{ $section->title }}</{{ $titleTag }}>
    @endif
    <div class="svc-brand-logos__grid">
        @foreach ($logos as $logo)
            @if ($logo['link'])
                <a class="svc-brand-logos__item" href="{{ $logo['link'] }}" @if(preg_match('#^https?://#i', $logo['link'])) target="_blank" rel="noopener" @endif title="{{ $logo['alt'] }}">
                    <img src="{{ $logo['url'] }}" alt="{{ $logo['alt'] }}" loading="lazy">
                </a>
            @else
                <div class="svc-brand-logos__item" title="{{ $logo['alt'] }}">
                    <img src="{{ $logo['url'] }}" alt="{{ $logo['alt'] }}" loading="lazy">
                </div>
            @endif
        @endforeach
    </div>
</section>
@endif
