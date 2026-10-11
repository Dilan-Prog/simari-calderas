@props(['views' => [], 'chips' => []])
{{--
    Cascarón de listado (worklist):  vistas guardadas · barra de búsqueda/filtros · chips activos · tabla · pie.
      views : [['label' => 'Todos', 'url' => '...', 'active' => true], ...]   (píldoras "Vistas")
      chips : [['label' => 'Estado: En ejecución', 'url' => '<url que quita el filtro>'], ...]
    Slots con nombre:  toolbar (dentro de un <form> propio), viewsExtra (p. ej. "+ Guardar vista"), footer.
    Slot por defecto: la tabla (usar <div class="erp-table-wrap"><table class="erp-table">…).
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
<div {{ $attributes->class(['erp-card', 'erp-worklist']) }}>
    @if (count($views) || isset($viewsExtra))
        <div class="erp-views">
            <span class="erp-views-label">Vistas</span>
            @foreach ($views as $v)
                <a href="{{ $v['url'] ?? '#' }}" class="erp-pill {{ !empty($v['active']) ? 'is-active' : '' }}">{{ $v['label'] }}</a>
            @endforeach
            @isset($viewsExtra){{ $viewsExtra }}@endisset
        </div>
    @endif
    @isset($toolbar)
        <div class="erp-toolbar">{{ $toolbar }}</div>
    @endisset
    @if (count($chips))
        <x-erp.filter-chips :chips="$chips" />
    @endif
    {{ $slot }}
    @isset($footer)
        <div class="erp-wl-footer">{{ $footer }}</div>
    @endisset
</div>
