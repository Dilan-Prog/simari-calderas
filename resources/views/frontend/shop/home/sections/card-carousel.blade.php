@php
    $config = $section->config ?? [];
    $ctx = $product ?? $collection ?? $servicePage ?? null;

    // Fichas válidas: con imagen y texto, y cuyo enlace (si lo tienen) aún
    // resuelve. Una ficha con enlace roto se omite por completo; sin enlace
    // configurado se pinta como ficha estática.
    $cards = collect($config['items'] ?? [])
        ->map(function ($item) use ($ctx, $section) {
            $image = trim((string) ($item['image_url'] ?? ''));
            $text = trim((string) $section->resolveText($item['text'] ?? '', $ctx));
            if ($image === '' || $text === '') {
                return null;
            }

            $link = $item['link'] ?? null;
            $hasLink = !empty($link['type']);
            $url = $hasLink ? \App\Support\LinkTarget::resolve($link) : null;
            if ($hasLink && !$url) {
                return null; // enlace roto
            }

            return [
                'src'     => \App\Support\UploadPath::url($image),
                'text'    => $text,
                'url'     => $url,
                'new_tab' => $hasLink && !empty($link['new_tab']),
            ];
        })
        ->filter()
        ->values();

    $headingTitle = $section->resolveText($section->title, $ctx);
    $headingUrl = $headingTitle ? \App\Support\LinkTarget::resolve($section->heading_link) : null;
    $headingNewTab = !empty($section->heading_link['new_tab']);
@endphp
@if ($cards->isNotEmpty())
<section class="card-carousel" x-data="{}">
    @if ($headingTitle)
        <h2 class="card-carousel__title">
            @if ($headingUrl)
                <a href="{{ $headingUrl }}" rel="noopener" @if ($headingNewTab) target="_blank" @endif>{{ \App\Support\LinkText::plain($headingTitle) }}</a>
            @else
                {!! \App\Support\LinkText::render($headingTitle) !!}
            @endif
        </h2>
    @endif
    <div class="card-carousel__wrap">
        <div class="card-carousel__track" x-ref="track">
            @foreach ($cards as $card)
                @if ($card['url'])
                    <a href="{{ $card['url'] }}" class="card-carousel__card" @if ($card['new_tab']) target="_blank" rel="noopener" @endif>
                        <span class="card-carousel__img-wrap"><img src="{{ $card['src'] }}" alt="{{ \App\Support\LinkText::plain($card['text']) }}" loading="lazy"></span>
                        <span class="card-carousel__text">{{ \App\Support\LinkText::plain($card['text']) }}</span>
                    </a>
                @else
                    <div class="card-carousel__card">
                        <span class="card-carousel__img-wrap"><img src="{{ $card['src'] }}" alt="{{ \App\Support\LinkText::plain($card['text']) }}" loading="lazy"></span>
                        <span class="card-carousel__text">{{ \App\Support\LinkText::plain($card['text']) }}</span>
                    </div>
                @endif
            @endforeach
        </div>
        @if ($cards->count() > 2)
            <button type="button" class="card-carousel__nav card-carousel__nav--prev" @click="$refs.track.scrollBy({ left: -$refs.track.clientWidth * 0.8, behavior: 'smooth' })" aria-label="Anterior">‹</button>
            <button type="button" class="card-carousel__nav card-carousel__nav--next" @click="$refs.track.scrollBy({ left: $refs.track.clientWidth * 0.8, behavior: 'smooth' })" aria-label="Siguiente">›</button>
        @endif
    </div>
</section>
@endif
