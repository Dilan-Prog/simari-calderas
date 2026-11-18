{{-- Barra de acciones masivas (Fase A, estilo Shopify) --}}
<div id="prodBulkBar" class="prod-bulk-bar">
    <span class="prod-bulk-count"><span id="prodBulkCount">0</span> seleccionado(s)</span>
    {{-- Selecciona TODOS los productos del filtro actual (no solo la página
         visible). Se oculta cuando ya están todos seleccionados. --}}
    <button type="button" class="prod-bulk-btn ghost" id="prodBulkSelectFilteredBtn" hidden
        data-url="{{ route('admin.products.filtered-ids', request()->except(['page', 'per_page', 'view'])) }}"
        data-total="{{ $totalFiltered ?? 0 }}"
        data-export-url="{{ route('admin.products.export', request()->except(['page', 'per_page', 'view'])) }}">Seleccionar los {{ $totalFiltered ?? 0 }} del filtro</button>
    <button type="button" class="prod-bulk-btn" data-action="activate">Activar</button>
    <button type="button" class="prod-bulk-btn" data-action="deactivate">Desactivar</button>
    <button type="button" class="prod-bulk-btn" data-action="publish">Publicar en Web</button>
    <button type="button" class="prod-bulk-btn" data-action="unpublish">Despublicar</button>
    {{-- data-download-url se actualiza en _scripts.blade.php (syncBulkBar) con
         los ids seleccionados; el modal de formato (PDF/Excel) lo abre. --}}
    <button type="button" class="prod-bulk-btn" id="prodBulkExportBtn"
        data-download-url="{{ route('admin.products.export') }}">Exportar selección</button>
    @permiso('products','delete')
    <button type="button" class="prod-bulk-btn danger" id="prodBulkDeleteBtn">Eliminar</button>
    @endpermiso
    <button type="button" class="prod-bulk-btn ghost" id="prodBulkCancelBtn">Cancelar selección</button>
</div>
