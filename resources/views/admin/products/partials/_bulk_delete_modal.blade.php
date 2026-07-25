{{-- Bulk delete confirmation modal --}}
<div id="bulkDeleteProductModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <div class="del-confirm-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                <path d="M12 9v4"></path>
                <path d="M12 17h.01"></path>
            </svg>
        </div>
        <h2 class="del-confirm-title">¿Eliminar <span id="bulkDeleteCount">0</span> producto(s)?</h2>
        <p class="del-confirm-desc">
            Esta acción no se puede deshacer. Los productos sin referencias en órdenes, servicios o inventario se
            eliminarán permanentemente; los que sí tengan referencias se omitirán y se te informará por qué.
        </p>
        <div id="bulkDeleteResult" class="prod-import-result" style="display:none"></div>

        <div class="del-confirm-actions" id="bulkDeleteActions">
            <button type="button" class="button-secondary size-adjustment" id="bulkDeleteCancelBtn">Cancelar</button>
            <button type="button" class="button-primary size-adjustment delete-confirmation-modal-button"
                id="bulkDeleteConfirmBtn">
                Eliminar
            </button>
        </div>
    </div>
</div>
