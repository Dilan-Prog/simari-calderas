@php
    $config = $section->config ?? [];
    $align = in_array($config['align'] ?? 'center', ['left', 'center', 'right'], true) ? $config['align'] : 'center';
    // 'buttons' (array) es el shape actual — soporta más de un botón en la
    // misma fila (ej. CTA + teléfono de contacto). Un config viejo guardado
    // como un solo botón plano (text/url/style/color en la raíz) se sigue
    // leyendo tal cual, para no romper secciones ya guardadas antes de este
    // cambio.
    $buttons = !empty($config['buttons']) && is_array($config['buttons'])
        ? $config['buttons']
        : (!empty($config['text']) && !empty($config['url']) ? [$config] : []);
@endphp
@if (!empty($buttons))
<section class="svc-button svc-button--{{ $align }}" @if ($previewMode) data-section-id="{{ $section->id }}" @endif>
    <div class="svc-button__row">
        @foreach ($buttons as $btn)
            @continue(empty($btn['text']) || empty($btn['url']))
            @php
                $style = ($btn['style'] ?? 'solid') === 'outline' ? 'outline' : 'solid';
                $color = $btn['color'] ?? '#ff6213';
            @endphp
            <a href="{{ $btn['url'] }}" class="svc-btn svc-btn--{{ $style }}"
               style="{{ $style === 'solid' ? 'background:'.e($color).';border-color:'.e($color) : 'color:'.e($color).';border-color:'.e($color) }}"
               @if (!str_starts_with($btn['url'], '/')) target="_blank" rel="noopener" @endif>
                {{ $btn['text'] }}
            </a>
        @endforeach
    </div>
</section>
@endif
