@php $config = $section->config ?? []; $left = $config['left'] ?? null; $right = $config['right'] ?? null; @endphp
@if ($left || $right)
<section class="home-dual-banner">
    @if ($left && !empty($left['image_url']))
        <a href="{{ $left['link_url'] ?? '#' }}" class="home-dual-banner__item">
            <img src="{{ \App\Support\UploadPath::url($left['image_url']) }}" alt="{{ $left['alt'] ?? '' }}" loading="lazy">
        </a>
    @endif
    @if ($right && !empty($right['image_url']))
        <a href="{{ $right['link_url'] ?? '#' }}" class="home-dual-banner__item">
            <img src="{{ \App\Support\UploadPath::url($right['image_url']) }}" alt="{{ $right['alt'] ?? '' }}" loading="lazy">
        </a>
    @endif
</section>
@endif
