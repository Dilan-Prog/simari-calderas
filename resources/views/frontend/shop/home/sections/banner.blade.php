@php
    $config = $section->config ?? [];
    $ctx = $product ?? $collection ?? $servicePage ?? null;

    // Destino: `link` (LinkTarget) tiene prioridad; si no existe se usa el
    // campo legado link_url. Un destino roto deja el banner sin enlace.
    $linkUrl = $config['link_url'] ?? null;
    $linkNewTab = false;
    if (!empty($config['link']['type'])) {
        $linkUrl = \App\Support\LinkTarget::resolve($config['link']);
        $linkNewTab = !empty($config['link']['new_tab']);
    }
    $hasLink = empty($config['no_link']) && !empty($linkUrl);

    $bannerAlt = $config['alt'] ?? $section->title ?? '';

    // Encabezado opcional sobre el banner: solo en bloques dinámicos de
    // producto (los banners del Home usan `title` solo como alt de reserva y
    // no deben empezar a mostrarlo).
    $isProductBlock = in_array($section->page, [\App\Models\HomeSection::PAGE_PRODUCT_TEMPLATE, \App\Models\HomeSection::PAGE_PRODUCT_CUSTOM], true);
    $headingTitle = $isProductBlock ? $section->resolveText($section->title, $ctx) : null;
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp
@if (!empty($config['image_url']))
<section class="home-banner{{ $headingTitle ? ' home-banner--with-heading' : '' }}">
    @if ($headingTitle)
        <h2 class="home-block-heading">
            @if ($headingUrl)
                <a href="{{ $headingUrl }}" rel="noopener" @if ($headingNewTab) target="_blank" @endif>{{ \App\Support\LinkText::plain($headingTitle) }}</a>
            @else
                {!! \App\Support\LinkText::render($headingTitle) !!}
            @endif
        </h2>
    @endif
    @if ($hasLink)
        <a href="{{ $linkUrl }}" class="home-banner__link" @if ($linkNewTab) target="_blank" rel="noopener" @endif>
            <img src="{{ \App\Support\UploadPath::url($config['image_url']) }}" alt="{{ $bannerAlt }}" loading="lazy">
        </a>
    @else
        <img src="{{ \App\Support\UploadPath::url($config['image_url']) }}" alt="{{ $bannerAlt }}" loading="lazy">
    @endif
</section>
@endif
