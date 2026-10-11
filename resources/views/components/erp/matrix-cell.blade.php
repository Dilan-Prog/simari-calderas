@props(['value' => null, 'unit' => null, 'tone' => null, 'strong' => false, 'dash' => true])
{{--
    Celda numérica de matriz (cobertura de materiales): alineada a la derecha por la columna `.is-num`,
    con tono opcional (bad | warn | ok | info). Si el valor es 0/vacío y $dash, muestra «—» atenuado.
--}}
@php
    $empty = $dash && ($value === null || $value === '' || (is_numeric($value) && (float) $value == 0.0));
    $tone = in_array($tone, ['ok', 'warn', 'bad', 'info'], true) ? $tone : null;
@endphp
<span {{ $attributes->class(['erp-mcell', 'is-strong' => $strong, 'is-zero' => $empty, 'is-' . $tone => $tone && !$empty]) }}>{{ $empty ? '—' : $value }}@if (!$empty && $unit)<small>{{ $unit }}</small>@endif</span>
