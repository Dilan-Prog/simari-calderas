@props(['title', 'value', 'caption' => null, 'tone' => 'neu', 'code' => null, 'context' => null, 'href' => null])
{{-- Tile de Launchpad: título, KPI vivo con semáforo y una línea de contexto. Todo el tile es clicable si hay href. --}}
@php
    $tone = in_array($tone, \App\Support\ErpUi::TONES, true) ? $tone : 'neu';
    $ctx = trim($code . ($code && $context ? ' · ' : '') . $context);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['erp-tile']) }}>
@else
    <div {{ $attributes->class(['erp-tile']) }}>
@endif
    <span class="erp-tile-title">{{ $title }}</span>
    @if ($ctx !== '')<span class="erp-tile-ctx">{{ $ctx }}</span>@endif
    <span class="erp-tile-value erp-tone-{{ $tone }}"><i class="erp-dot erp-dot--{{ $tone }}"></i>{{ $value }}</span>
    @if ($caption)<span class="erp-tile-cap">{{ $caption }}</span>@endif
@if ($href)
    </a>
@else
    </div>
@endif
