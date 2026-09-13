@php
    $config = $section->config ?? [];
    $align = in_array($config['align'] ?? 'center', ['left', 'center', 'right'], true) ? $config['align'] : 'center';
    $style = ($config['style'] ?? 'solid') === 'outline' ? 'outline' : 'solid';
    $color = $config['color'] ?? '#ff6213';
@endphp
@if (!empty($config['text']) && !empty($config['url']))
<section class="svc-button svc-button--{{ $align }}" @if ($previewMode) data-section-id="{{ $section->id }}" @endif>
    <a href="{{ $config['url'] }}" class="svc-btn svc-btn--{{ $style }}"
       style="{{ $style === 'solid' ? 'background:'.e($color).';border-color:'.e($color) : 'color:'.e($color).';border-color:'.e($color) }}"
       @if (!str_starts_with($config['url'], '/')) target="_blank" rel="noopener" @endif>
        {{ $config['text'] }}
    </a>
</section>
@endif
