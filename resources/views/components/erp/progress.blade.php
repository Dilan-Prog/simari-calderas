@props(['value' => 0, 'width' => null])
{{-- Barra de avance 0-100 con porcentaje. Verde al llegar a 100. --}}
@php $v = max(0, min(100, (int) round((float) $value))); @endphp
<span {{ $attributes->class(['erp-progress', 'is-done' => $v >= 100]) }}>
    <span class="erp-progress-bar" @if ($width) style="width: {{ $width }}" @endif><span style="width: {{ $v }}%"></span></span>
    <span class="erp-progress-val">{{ $v }}%</span>
</span>
