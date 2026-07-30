{{-- Asignar/editar un producto para este proveedor (tabla suppliers_products).
     Compartido para "agregar" y "editar" — se rellena vía JS a partir de los
     data-* de la fila cuando se abre en modo edición. --}}
<div id="assignProductModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="assignProductModalTitle">Asignar Producto</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeAssignProductModal">✕</button>
        </div>

        <div id="assign-product-errors" class="user-manager-errors" style="display:none;"></div>

        <div class="user-manager-modal-body">
            <input type="hidden" id="apPivotId" value="">

            <div>
                <label class="supliers-manager-slider-label">Producto *</label>
                <select class="users-manager-select" id="apProductSelect">
                    <option value="">Seleccionar...</option>
                    @foreach ($allProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>

            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">SKU del proveedor</label>
                    <input class="users-manager-input" type="text" id="apSku" maxlength="100"
                        placeholder="Código con el que este proveedor identifica el producto">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Costo</label>
                    <input class="users-manager-input" type="number" id="apCost" step="0.01" min="0" placeholder="0.00">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Lead time (días)</label>
                    <input class="users-manager-input" type="number" id="apLeadTime" min="0" placeholder="0">
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:13px;color:#374151;cursor:pointer">
                <input type="checkbox" id="apIsPrimary" style="width:auto">
                Es el proveedor principal de este producto
            </label>
        </div>

        <div class="user-manager-modal-footer">
            <button type="button" id="cancelAssignProductModal" class="button-secondary size-adjustment">Cancelar</button>
            <button type="button" id="apSave" class="button-primary size-adjustment">Guardar</button>
        </div>
    </div>
</div>
