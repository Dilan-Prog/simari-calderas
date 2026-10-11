@props(['steps' => [], 'current' => null, 'counts' => false])
{{--
    Tira de proceso (chevrons).
      steps   : ['Cotización', 'Pedido', ...] o [['key' => 'quote', 'label' => 'Cotización', 'count' => 12], ...]
      current : índice (0..n-1) o key del paso actual; los anteriores se marcan como hechos (✓).
      counts  : true → muestra el conteo grande arriba del texto (Launchpad)
--}}
@php
    $norm = collect($steps)->map(fn ($s, $i) => is_array($s) ? $s + ['key' => $i] : ['key' => $i, 'label' => $s])->values();
    $currentIdx = $current === null ? -1 : $norm->search(fn ($s) => $s['key'] === $current);
    if ($currentIdx === false) {
        $currentIdx = is_int($current) ? $current : -1;
    }
@endphp
<ol {{ $attributes->class(['erp-chevrons', 'erp-chevrons--count' => $counts]) }}>
    @foreach ($norm as $i => $s)
        <li class="erp-chevron {{ $i < $currentIdx ? 'is-done' : ($i === $currentIdx ? 'is-current' : '') }}">
            @if ($counts && isset($s['count']))<b>{{ $s['count'] }}</b>@endif
            @if ($i + 1 <= count($norm) && !$counts){{ ($i + 1) . ' ' }}@endif{{ $s['label'] }}
        </li>
    @endforeach
</ol>
