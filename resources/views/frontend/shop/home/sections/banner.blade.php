@php $config = $section->config ?? []; @endphp
@if (!empty($config['image_url']))
<section class="home-banner">
    <a href="{{ $config['link_url'] ?? '#' }}" class="home-banner__link">
        <img src="{{ str_starts_with($config['image_url'], 'http') ? $config['image_url'] : asset($config['image_url']) }}" alt="{{ $config['alt'] ?? $section->title }}" loading="lazy">
    </a>
</section>
@endif
