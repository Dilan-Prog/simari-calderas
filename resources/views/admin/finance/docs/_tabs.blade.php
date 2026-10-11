<nav class="fin-tabs fin-noprint" aria-label="Secciones">
    <a href="{{ route($cfg['route'] . '.index') }}" class="{{ $active === 'index' ? 'is-active' : '' }}">Documentos</a>
    <a href="{{ route($cfg['route'] . '.aging') }}" class="{{ $active === 'aging' ? 'is-active' : '' }}">Antigüedad de saldos</a>
</nav>
