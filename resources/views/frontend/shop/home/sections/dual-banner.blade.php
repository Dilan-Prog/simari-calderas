@php
    $config = $section->config ?? [];
    $ctx = $product ?? $collection ?? $servicePage ?? null;
    $left = $config['left'] ?? null;
    $right = $config['right'] ?? null;

    // Normaliza cada lado: `link` (LinkTarget) tiene prioridad; si no existe
    // se usa el campo legado link_url. Un destino roto deja el lado sin
    // enlace (nunca href vacío ni '#').
    $bannerSide = function (?array $side): ?array {
        if (!$side || empty($side['image_url'])) {
            return null;
        }

        $url = $side['link_url'] ?? null;
        $newTab = false;
        if (!empty($side['link']['type'])) {
            $url = \App\Support\LinkTarget::resolve($side['link']);
            $newTab = !empty($side['link']['new_tab']);
        }

        return [
            'src'     => \App\Support\UploadPath::url($side['image_url']),
            'alt'     => $side['alt'] ?? '',
            'url'     => empty($side['no_link']) && !empty($url) ? $url : null,
            'new_tab' => $newTab,
        ];
    };
    $sides = array_values(array_filter([$bannerSide($left), $bannerSide($right)]));

    // Encabezado opcional: solo en bloques dinámicos de producto (ver
    // banner.blade.php).
    $isProductBlock = in_array($section->page, [\App\Models\HomeSection::PAGE_PRODUCT_TEMPLATE, \App\Models\HomeSection::PAGE_PRODUCT_CUSTOM], true);
    $headingTitle = $isProductBlock ? $section->resolveText($section->title, $ctx) : null;
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp
@if (count($sides) > 0)
<section class="home-dual-banner-block">
    @if ($headingTitle)
        <h2 class="home-block-heading">
            @if ($headingUrl)
                <a href="{{ $headingUrl }}" rel="noopener" @if ($headingNewTab) target="_blank" @endif>{{ \App\Support\LinkText::plain($headingTitle) }}</a>
            @else
                {!! \App\Support\LinkText::render($headingTitle) !!}
            @endif
        </h2>
    @endif
    <div class="home-dual-banner">
        @foreach ($sides as $side)
            @if ($side['url'])
                <a href="{{ $side['url'] }}" class="home-dual-banner__item" @if ($side['new_tab']) target="_blank" rel="noopener" @endif>
                    <img src="{{ $side['src'] }}" alt="{{ $side['alt'] }}" loading="lazy">
                </a>
            @else
                <div class="home-dual-banner__item">
                    <img src="{{ $side['src'] }}" alt="{{ $side['alt'] }}" loading="lazy">
                </div>
            @endif
        @endforeach
    </div>
</section>
@endif
