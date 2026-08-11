{{-- Create/Edit Modal — mismo patrón que admin.brands.partials._create_and_edit_modal --}}
<div id="warehouseModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="warehouseModalTitle">Nuevo Almacén</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeWarehouseModal">✕</button>
        </div>

        <div id="warehouse-modal-errors" class="user-manager-errors" style="display:none;"></div>

        <form class="user-manager-modal-body" id="warehouseForm">
            @csrf

            <div class="users-manager-email-camp">
                <label class="supliers-manager-slider-label">
                    Nombre <span style="color:red">*</span>
                </label>
                <input type="text" class="users-manager-input" name="name" id="warehouseName" maxlength="100"
                    placeholder="Ej: Almacén Principal">
            </div>

            <div class="users-manager-email-camp" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">Ubicación</label>
                <input type="text" class="users-manager-input" name="location" id="warehouseLocation" maxlength="255"
                    placeholder="Ej: Planta Norte, Bodega 3">
            </div>

            <div class="user-manager-form" style="margin-top:12px;">
                <div>
                    <label class="supliers-manager-slider-label">
                        Estado <span style="color:red">*</span>
                    </label>
                    <select class="users-manager-select" name="is_active" id="warehouseIsActive">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelWarehouseModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="warehouseSubmitBtn">Crear
                    Almacén</button>
            </div>
        </form>
    </div>
</div>
