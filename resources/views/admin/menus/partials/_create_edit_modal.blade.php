    {{-- Create/Edit Modal --}}
    <div id="menuModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="menuModalTitle">Nuevo Menú</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeMenuModal">✕</button>
            </div>

            <div id="menu-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="menuForm">
                @csrf
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Nombre <span style="color:red">*</span></label>
                    <input type="text" class="users-manager-input" name="name" id="menuName"
                        placeholder="Ej: header-main" value="{{ old('name') }}">
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Ubicación <span style="color:red">*</span></label>
                    <select class="users-manager-select" name="location" id="menuLocation">
                        <option value="header-main" {{ old('location') == 'header-main' ? 'selected' : '' }}>Encabezado — Enlace principal</option>
                        <option value="header-servicios" {{ old('location') == 'header-servicios' ? 'selected' : '' }}>Encabezado — Mega-menú Servicios</option>
                        <option value="footer" {{ old('location') == 'footer' ? 'selected' : '' }}>Pie de página — Columna</option>
                    </select>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Estado <span style="color:red">*</span></label>
                        <select class="users-manager-select" name="is_active" id="menuIsActive">
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Orden</label>
                        <input type="number" class="users-manager-input" name="sort_order" id="menuSortOrder"
                            min="0" value="{{ old('sort_order', 0) }}">
                    </div>
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelMenuModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="menuSubmitBtn">Crear Menú</button>
                </div>
            </form>
        </div>
    </div>
