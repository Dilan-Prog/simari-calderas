@props(['chips' => [], 'clearUrl' => null])
{{-- Chips de filtros activos. Cada chip: ['label' => 'Estado: En ejecución', 'url' => url que lo quita]. --}}
@if (count($chips))
    <div class="erp-chips-row">
        @foreach ($chips as $c)
            <a href="{{ $c['url'] ?? '#' }}" class="erp-pill erp-pill--filter" title="Quitar filtro">{{ $c['label'] }} ×</a>
        @endforeach
        @if ($clearUrl)
            <a href="{{ $clearUrl }}" class="erp-pill erp-pill--ghost">Limpiar todo</a>
        @endif
    </div>
@endif
