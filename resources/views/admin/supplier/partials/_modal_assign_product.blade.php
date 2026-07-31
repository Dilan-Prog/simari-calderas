{{-- Asignar/editar un producto para este proveedor (tabla suppliers_products).
     Compartido para "agregar" y "editar" — se rellena vía JS a partir de los
     data-* de la fila cuando se abre en modo edición. Modal centrado (mismo
     patrón que .del-confirm-overlay / la importación) con selector de
     producto buscable en vez de un <select> plano de cientos de opciones. --}}
<div id="assignProductModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m7.5 4.27 9 5.15"></path>
                    <path
                        d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z">
                    </path>
                    <path d="m3.3 7 8.7 5 8.7-5"></path>
                    <path d="M12 22V12"></path>
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="assignProductModalTitle">Asignar Producto</h2>
                <p class="del-confirm-desc" id="assignProductModalSubtitle">
                    Vincula un producto del catálogo a este proveedor con su SKU, costo y tiempo de entrega.
                </p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeAssignProductModal">✕</button>
        </div>

        <div id="assign-product-errors" class="ap-modal-errors" style="display:none;"></div>

        <div class="ap-modal-body">
            <input type="hidden" id="apPivotId" value="">
            <input type="hidden" id="apProductId" value="">

            <div class="ap-field-group">
                <label class="supliers-manager-slider-label">Producto *</label>
                <div class="ap-product-picker" id="apProductPicker">
                    <input type="text" class="users-manager-input" id="apProductSearch" autocomplete="off"
                        placeholder="Buscar producto por nombre o SKU...">
                    <div class="ap-product-dropdown" id="apProductDropdown"></div>
                </div>
                <p class="ap-readonly-note" id="apProductLockedNote" style="display:none;">
                    No se puede cambiar el producto de un vínculo ya existente.
                </p>
            </div>

            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">SKU del proveedor</label>
                    <input class="users-manager-input" type="text" id="apSku" maxlength="100"
                        placeholder="Código con el que este proveedor identifica el producto">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Costo</label>
                    <div class="ap-input-prefix">
                        <span>$</span>
                        <input class="users-manager-input" type="number" id="apCost" step="0.01" min="0"
                            placeholder="0.00">
                    </div>
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Lead time (días)</label>
                    <input class="users-manager-input" type="number" id="apLeadTime" min="0" placeholder="0">
                </div>
            </div>

            <label class="ap-primary-toggle" for="apIsPrimary">
                <input type="checkbox" id="apIsPrimary">
                <div>
                    <strong>Proveedor principal</strong>
                    <span>Se usará como la fuente preferida de este producto. Solo un proveedor puede ser
                        principal por producto — marcar esta opción reemplaza al principal actual.</span>
                </div>
            </label>
        </div>

        <div class="ap-modal-footer">
            <button type="button" id="cancelAssignProductModal" class="button-secondary size-adjustment">Cancelar</button>
            <button type="button" id="apSave" class="button-primary size-adjustment">Guardar</button>
        </div>
    </div>
</div>
