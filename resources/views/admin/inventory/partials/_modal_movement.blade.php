{{-- Registrar movimiento manual de inventario. Selector de producto
     buscable en cliente (mismo patrón que
     admin.supplier.partials._modal_assign_product, sin AJAX: el catálogo
     completo id/name/sku ya viene precargado por InventoryController::index()
     como $products). --}}
<div id="movementModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="5" x="2" y="3" rx="1" />
                    <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                    <path d="M10 12h4" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title">Registrar movimiento</h2>
                <p class="del-confirm-desc">Entrada, salida o ajuste manual de stock para un producto en un almacén.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeMovementModal">✕</button>
        </div>

        <div id="movement-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <div class="ap-modal-body">
            <div class="user-manager-form user-manager-form-3">
                <div>
                    <label class="supliers-manager-slider-label">Almacén *</label>
                    <select class="users-manager-select" id="mvWarehouseId">
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Tipo *</label>
                    <select class="users-manager-select" id="mvType">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                        <option value="ajuste">Ajuste</option>
                    </select>
                </div>
                <div id="mvDirectionWrap" style="display:none;">
                    <label class="supliers-manager-slider-label">Dirección del ajuste *</label>
                    <select class="users-manager-select" id="mvDirection">
                        <option value="increase">Aumentar stock</option>
                        <option value="decrease">Disminuir stock</option>
                    </select>
                </div>
            </div>

            <div class="ap-field-group" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">Producto *</label>
                <div class="ap-product-picker" id="mvProductPicker">
                    <input type="text" class="users-manager-input" id="mvProductSearch" autocomplete="off"
                        placeholder="Buscar producto por nombre o SKU...">
                    <div class="ap-product-dropdown" id="mvProductDropdown"></div>
                </div>
                <input type="hidden" id="mvProductId" value="">
            </div>

            <div class="user-manager-form" style="margin-top:12px;">
                <div>
                    <label class="supliers-manager-slider-label">Cantidad *</label>
                    <input class="users-manager-input" type="number" id="mvQuantity" min="1" step="1" placeholder="0">
                </div>
            </div>

            <div class="users-manager-email-camp" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">Notas</label>
                <textarea class="users-manager-input client-modal-textarea" id="mvNotes" rows="3" maxlength="500"
                    placeholder="Motivo del movimiento (opcional)..."></textarea>
            </div>
        </div>

        <div class="ap-modal-footer">
            <button type="button" id="cancelMovementModal" class="button-secondary size-adjustment">Cancelar</button>
            <button type="button" id="mvSave" class="button-primary size-adjustment">Registrar</button>
        </div>
    </div>
</div>
