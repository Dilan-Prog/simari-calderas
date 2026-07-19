@php $config = $section->config ?? []; $left = $config['left'] ?? null; $right = $config['right'] ?? null; @endphp
@if ($left || $right)
<section class="home-dual-banner">
    @if ($left && !empty($left['image_url']))
        <a href="{{ $left['link_url'] ?? '#' }}" class="home-dual-banner__item">
            <img src="{{ str_starts_with($left['image_url'], 'http') ? $left['image_url'] : asset($left['image_url']) }}" alt="{{ $left['alt'] ?? '' }}" loading="lazy">
        </a>
    @endif
    @if ($right && !empty($right['image_url']))
        <a href="{{ $right['link_url'] ?? '#' }}" class="home-dual-banner__item">
            <img src="{{ str_starts_with($right['image_url'], 'http') ? $right['image_url'] : asset($right['image_url']) }}" alt="{{ $right['alt'] ?? '' }}" loading="lazy">
        </a>
    @endif
</section>
@endif
