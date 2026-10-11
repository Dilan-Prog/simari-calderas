@props(['href', 'name', 'source' => 'manual', 'time' => null, 'preview' => null, 'sub' => null, 'tags' => [], 'tone' => 'neu', 'status' => null, 'active' => false, 'unread' => false])
{{--
    Fila de bandeja (lista de conversaciones/leads).
      source : whatsapp | formulario | llamada | correo | manual (define el icono)
      tags   : [['t' => 'Google Ads', 'tone' => 'info'], ...]    status/tone: chip de estado a la derecha
    Todo el renglón es un enlace; en móvil la lista ocupa todo el ancho.
--}}
@php
    $icons = [
        'whatsapp' => 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.7A8 8 0 1 1 21 12z',
        'llamada' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
        'correo' => 'M3 6h18v12H3zM3 7l9 6 9-6',
        'formulario' => 'M6 3h9l4 4v14H6zM9 12h7M9 16h7M9 8h3',
        'manual' => 'M12 5v14M5 12h14',
    ];
    $path = $icons[$source] ?? $icons['manual'];
    $tone = $tone === 'neutral' ? 'neu' : $tone;
@endphp
<a href="{{ $href }}" {{ $attributes->class(['erp-inbox-row', 'is-active' => $active, 'is-unread' => $unread]) }}>
    <span class="erp-inbox-ic erp-inbox-ic--{{ $source }}" aria-hidden="true">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
    </span>
    <span class="erp-inbox-main">
        <span class="erp-inbox-top">
            <span class="erp-inbox-name">{{ $name }}</span>
            @if ($time)<span class="erp-inbox-time">{{ $time }}</span>@endif
        </span>
        @if ($sub)<span class="erp-inbox-sub">{{ $sub }}</span>@endif
        @if ($preview)<span class="erp-inbox-preview">{{ $preview }}</span>@endif
        @if (count($tags))
            <span class="erp-inbox-tags">
                @foreach ($tags as $tg)
                    <span class="erp-inbox-tag erp-inbox-tag--{{ $tg['tone'] ?? 'neu' }}">{{ $tg['t'] }}</span>
                @endforeach
            </span>
        @endif
    </span>
    @if ($status)
        <span class="erp-inbox-status"><x-erp.chip :tone="$tone" :label="$status" /></span>
    @endif
</a>
