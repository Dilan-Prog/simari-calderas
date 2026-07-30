{{-- Asignar/editar un proveedor para este producto (tabla suppliers_products).
     Compartido para "agregar" y "editar" — se rellena vía JS a partir de los
     data-* de la fila cuando se abre en modo edición. --}}
<div id="supplierLinkModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <h2 class="del-confirm-title" id="supplierLinkModalTitle">Asignar Proveedor</h2>

        <input type="hidden" id="slPivotId" value="">

        <div class="pform-field">
            <label class="pform-label" for="slSupplierSelect">Proveedor <span class="pform-required">*</span></label>
            <select id="slSupplierSelect" class="pform-select">
                <option value="">Seleccionar...</option>
                @foreach ($allSuppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="pform-field">
            <label class="pform-label" for="slSku">SKU del proveedor</label>
            <input type="text" id="slSku" class="pform-input" maxlength="100"
                placeholder="Código con el que el proveedor identifica este producto">
        </div>

        <div class="pform-grid-2">
            <div class="pform-field">
                <label class="pform-label" for="slCost">Costo</label>
                <div class="pform-price-wrap">
                    <span class="pform-price-prefix">$</span>
                    <input type="number" id="slCost" class="pform-input pform-input-prefixed" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
            <div class="pform-field">
                <label class="pform-label" for="slLeadTime">Lead time (días)</label>
                <input type="number" id="slLeadTime" class="pform-input" min="0" placeholder="0">
            </div>
        </div>

        <div class="pform-field" style="margin-bottom:8px">
            <label class="pform-label" style="display:flex;align-items:center;gap:8px;cursor:pointer">
                <input type="checkbox" id="slIsPrimary" style="width:auto">
                Es el proveedor principal de este producto
            </label>
        </div>

        <p id="slError" class="pform-hint" style="color:#dc2626;display:none"></p>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="slCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="slSave">Guardar</button>
        </div>
    </div>
</div>
