@props(['segments' => [], 'total' => null, 'cols' => null])
{{--
    Barra de antigüedad de saldos (segmentos proporcionales + leyenda).
      segments : [['label' => '0–30 días', 'value' => 1068800.0, 'amount' => '$1,068,800.00', 'color' => '#16a34a'], ...]
      total    : suma (si se omite se calcula). Los segmentos en cero se omiten de la barra pero siguen en la leyenda.
--}}
@php
    $sum = $total !== null ? (float) $total : (float) collect($segments)->sum('value');
    $aria = collect($segments)->map(fn ($s) => $s['label'] . ': ' . ($s['amount'] ?? $s['value']))->implode(', ');
@endphp
<div {{ $attributes->class(['erp-agebar']) }}>
    <div class="erp-agebar-track" role="img" aria-label="Antigüedad de saldos — {{ $aria }}">
        @foreach ($segments as $s)
            @php $w = $sum > 0 ? max(0, (float) $s['value']) / $sum * 100 : 0; @endphp
            @if ($w > 0)
                <span style="width: {{ round($w, 2) }}%; background: {{ $s['color'] }};" title="{{ $s['label'] }}: {{ $s['amount'] ?? $s['value'] }}"></span>
            @endif
        @endforeach
    </div>
    <dl class="erp-agebar-legend" @if ($cols) style="grid-template-columns: repeat({{ (int) $cols }}, minmax(0, 1fr));" @endif>
        @foreach ($segments as $s)
            <div>
                <dt><i style="background: {{ $s['color'] }};"></i>{{ $s['label'] }}</dt>
                <dd>{{ $s['amount'] ?? $s['value'] }}</dd>
                <dd class="erp-agebar-pct">{{ $sum > 0 ? round((float) $s['value'] / $sum * 100) : 0 }}%</dd>
            </div>
        @endforeach
    </dl>
</div>
