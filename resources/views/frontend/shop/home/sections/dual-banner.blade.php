@php $config = $section->config ?? []; $left = $config['left'] ?? null; $right = $config['right'] ?? null; @endphp
@if ($left || $right)
<section class="home-dual-banner">
    @if ($left && !empty($left['image_url']))
        @if (empty($left['no_link']) && !empty($left['link_url']))
            <a href="{{ $left['link_url'] }}" class="home-dual-banner__item">
                <img src="{{ \App\Support\UploadPath::url($left['image_url']) }}" alt="{{ $left['alt'] ?? '' }}" loading="lazy">
            </a>
        @else
            <div class="home-dual-banner__item">
                <img src="{{ \App\Support\UploadPath::url($left['image_url']) }}" alt="{{ $left['alt'] ?? '' }}" loading="lazy">
            </div>
        @endif
    @endif
    @if ($right && !empty($right['image_url']))
        @if (empty($right['no_link']) && !empty($right['link_url']))
            <a href="{{ $right['link_url'] }}" class="home-dual-banner__item">
                <img src="{{ \App\Support\UploadPath::url($right['image_url']) }}" alt="{{ $right['alt'] ?? '' }}" loading="lazy">
            </a>
        @else
            <div class="home-dual-banner__item">
                <img src="{{ \App\Support\UploadPath::url($right['image_url']) }}" alt="{{ $right['alt'] ?? '' }}" loading="lazy">
            </div>
        @endif
    @endif
</section>
@endif
