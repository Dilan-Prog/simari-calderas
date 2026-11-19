@php
    /*
     * Bloque "Productos en filas" (product_grid): N filas de per_row tarjetas
     * con banners opcionales entre filas.
     *
     * Config: source, category_id, brand_id, collection_id, product_ids, tag,
     *   per_row (3|4|5, def. 4), rows (1-10, def. 2), sort, title_style,
     *   position, banners: [{after_row, image_url, link (LinkTarget, tiene
     *   prioridad), link_url (legado), alt, no_link}].
     * Por defecto la fuente es la categoría de la página ($contextCategory)
     * con sus hijas (ver App\Services\SectionProducts).
     */
    $config = $section->config ?? [];
    $ctx = $product ?? $collection ?? $servicePage ?? $contextCategory ?? null;
    $currentProduct = isset($product) ? $product : null;

    $perRow = (int) ($config['per_row'] ?? 4);
    $perRow = in_array($perRow, [3, 4, 5], true) ? $perRow : 4;
    $rows = max(1, min(10, (int) ($config['rows'] ?? 2)));

    $gridConfig = $config;
    $gridConfig['source'] = $config['source'] ?? 'category';

    $gridProducts = \App\Services\SectionProducts::resolve($gridConfig, $contextCategory ?? null, $perRow * $rows, $currentProduct);
    $productRows = $gridProducts->chunk($perRow)->values();
    $rowCount = $productRows->count();

    // Banners normalizados: `link` (LinkTarget) tiene prioridad sobre el
    // legado link_url; un destino roto o "Sin enlace" deja la imagen sin <a>.
    $bannersByRow = [];
    foreach ((array) ($config['banners'] ?? []) as $banner) {
        if (!is_array($banner) || empty($banner['image_url'])) {
            continue;
        }

        $url = $banner['link_url'] ?? null;
        $newTab = false;
        if (!empty($banner['link']['type'])) {
            $url = \App\Support\LinkTarget::resolve($banner['link']);
            $newTab = !empty($banner['link']['new_tab']);
        }

        // Un banner después de una fila que no existe va tras la última fila
        // pintada (o solo, si no hay productos).
        $afterRow = max(1, (int) ($banner['after_row'] ?? 1));
        $afterRow = $rowCount > 0 ? min($afterRow, $rowCount) : 0;

        $bannersByRow[$afterRow][] = [
            'src'     => \App\Support\UploadPath::url($banner['image_url']),
            'alt'     => trim((string) ($banner['alt'] ?? '')) ?: \App\Support\LinkText::plain($section->title ?? ''),
            'url'     => empty($banner['no_link']) && !empty($url) ? $url : null,
            'new_tab' => $newTab,
        ];
    }

    $titleText = $section->resolveText($section->title, $ctx);
    $titleStyle = $config['title_style'] ?? null;
    $titleTag = \App\Support\TextStyle::tag($titleStyle, 'h2', ['h2', 'h3']);

    // Secuencia final: banners "fila 0" (sin productos), luego cada fila
    // seguida de sus banners.
    $gridSequence = [];
    foreach ($bannersByRow[0] ?? [] as $b) {
        $gridSequence[] = ['kind' => 'banner', 'banner' => $b];
    }
    foreach ($productRows as $i => $rowProducts) {
        $gridSequence[] = ['kind' => 'row', 'products' => $rowProducts];
        foreach ($bannersByRow[$i + 1] ?? [] as $b) {
            $gridSequence[] = ['kind' => 'banner', 'banner' => $b];
        }
    }
    $hasContent = count($gridSequence) > 0;
@endphp

@if ($hasContent)
<section class="product-grid-block" style="--per-row: {{ $perRow }};" data-section-id="{{ $section->id ?? '' }}">
    @if ($titleText)
        <{{ $titleTag }} class="product-grid-block__title"{!! \App\Support\TextStyle::attr($titleStyle) !!}>{!! \App\Support\LinkText::render($titleText) !!}</{{ $titleTag }}>
    @endif

    @foreach ($gridSequence as $gridItem)
        @if ($gridItem['kind'] === 'row')
            <div class="product-grid-block__row">
                @foreach ($gridItem['products'] as $gridProduct)
                    <div class="product-grid-block__item">
                        <x-frontend.shop.product-card :product="$gridProduct" />
                    </div>
                @endforeach
            </div>
        @else
            @php $gridBanner = $gridItem['banner']; @endphp
            <div class="product-grid-block__banner">
                @if ($gridBanner['url'])
                    <a href="{{ $gridBanner['url'] }}" class="product-grid-block__banner-link" @if ($gridBanner['new_tab']) target="_blank" rel="noopener" @endif>
                        <img src="{{ $gridBanner['src'] }}" alt="{{ $gridBanner['alt'] }}" loading="lazy">
                    </a>
                @else
                    <img src="{{ $gridBanner['src'] }}" alt="{{ $gridBanner['alt'] }}" loading="lazy">
                @endif
            </div>
        @endif
    @endforeach
</section>
@elseif ($previewMode ?? false)
<section class="product-grid-block product-grid-block--empty" data-section-id="{{ $section->id ?? '' }}">
    <p class="product-grid-block__empty">Sin productos para esta fuente</p>
</section>
@endif
