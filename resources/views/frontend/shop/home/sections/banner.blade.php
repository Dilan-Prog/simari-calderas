@php $config = $section->config ?? []; @endphp
@if (!empty($config['image_url']))
<section class="home-banner">
    <a href="{{ $config['link_url'] ?? '#' }}" class="home-banner__link">
        <img src="{{ \App\Support\UploadPath::url($config['image_url']) }}" alt="{{ $config['alt'] ?? $section->title }}" loading="lazy">
    </a>
</section>
@endif
