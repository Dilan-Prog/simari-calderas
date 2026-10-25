{{--
    Buscador del listado de Productos y del editor en lote (va DENTRO del
    <form class="prod-toolbar">). Lo conecta public/js/admin/list-search.js.
    $inputId: id del <input name="search">.
--}}
<div class="prod-search-wrap">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="8" />
        <path d="m21 21-4.3-4.3" />
    </svg>
    <input type="search" name="search" id="{{ $inputId }}" class="prod-search-input"
        placeholder="Buscar por nombre, SKU, modelo o marca…" autocomplete="off"
        aria-label="Buscar productos" value="{{ request('search') }}" />
    <button type="button" class="prod-search-clear" id="{{ $inputId }}Clear" aria-label="Limpiar búsqueda"
        title="Limpiar búsqueda (Esc)" hidden>&times;</button>
</div>
