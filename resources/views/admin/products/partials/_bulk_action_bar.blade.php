{{-- Barra de acciones masivas (Fase A, estilo Shopify) --}}
<div id="prodBulkBar" class="prod-bulk-bar">
    <span class="prod-bulk-count"><span id="prodBulkCount">0</span> seleccionado(s)</span>
    <button type="button" class="prod-bulk-btn" data-action="activate">Activar</button>
    <button type="button" class="prod-bulk-btn" data-action="deactivate">Desactivar</button>
    <button type="button" class="prod-bulk-btn" data-action="publish">Publicar en Web</button>
    <button type="button" class="prod-bulk-btn" data-action="unpublish">Despublicar</button>
    @permiso('products','delete')
    <button type="button" class="prod-bulk-btn danger" id="prodBulkDeleteBtn">Eliminar</button>
    @endpermiso
    <button type="button" class="prod-bulk-btn ghost" id="prodBulkCancelBtn">Cancelar selección</button>
</div>
