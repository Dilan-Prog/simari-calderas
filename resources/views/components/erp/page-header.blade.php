@props(['title', 'code' => null, 'desc' => null, 'crumbs' => []])
{{--
    Encabezado de pantalla (listados y formularios).
      crumbs : [['Planeación', url|null], ['Proyectos', null]]  — el último es la página actual
      code   : código de transacción (PRJ2…) opcional
    Las acciones (Exportar, + Nuevo…) van en el slot por defecto.
--}}
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
@endpush
<div class="erp-page-head">
    <div>
        @if (count($crumbs))
            <nav class="erp-crumbs" aria-label="Ruta">
                @foreach ($crumbs as $i => $c)
                    @if ($i > 0)<span>›</span>@endif
                    @if (!empty($c[1]) && $i < count($crumbs) - 1)
                        <a href="{{ $c[1] }}">{{ $c[0] }}</a>
                    @else
                        <span class="{{ $i === count($crumbs) - 1 ? 'is-current' : '' }}">{{ $c[0] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif
        <h1 class="erp-title">{{ $title }} @if ($code)<span class="erp-code">{{ $code }}</span>@endif</h1>
        @if ($desc)<p class="erp-subtitle">{{ $desc }}</p>@endif
    </div>
    <div class="erp-head-actions">{{ $slot }}</div>
</div>
