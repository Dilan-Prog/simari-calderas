@props(['steps' => [], 'current' => null])
{{--
    Tira de proceso (chevrons) estilo SAP.
      steps   : ['Cotización', 'Pedido', ...] o [['key' => 'quote', 'label' => 'Cotización'], ...]
      current : índice (0..n-1) o key del paso actual; los anteriores se marcan como hechos.
--}}
@php
    $norm = collect($steps)->map(fn ($s, $i) => is_array($s) ? $s + ['key' => $i] : ['key' => $i, 'label' => $s])->values();
    $currentIdx = $norm->search(fn ($s) => $s['key'] === $current);
    if ($currentIdx === false) {
        $currentIdx = is_int($current) ? $current : -1;
    }
@endphp
<ol class="erp-chevrons">
    @foreach ($norm as $i => $s)
        <li class="erp-chevron {{ $i < $currentIdx ? 'is-done' : ($i === $currentIdx ? 'is-current' : '') }}">{{ $s['label'] }}</li>
    @endforeach
</ol>
