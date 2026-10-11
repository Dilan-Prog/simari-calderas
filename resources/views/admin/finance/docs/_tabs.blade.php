{{-- Secciones de CXC / CXP: pestañas de navegación (enlaces) con el estilo .erp-tabnav --}}
<nav class="erp-tabnav fin-noprint fin-nav-tabs" aria-label="Secciones">
    <a href="{{ route($cfg['route'] . '.index') }}" class="erp-tab {{ $active === 'index' ? 'is-active' : '' }}" @if ($active === 'index') aria-current="page" @endif>Documentos</a>
    <a href="{{ route($cfg['route'] . '.aging') }}" class="erp-tab {{ $active === 'aging' ? 'is-active' : '' }}" @if ($active === 'aging') aria-current="page" @endif>Antigüedad de saldos</a>
</nav>
