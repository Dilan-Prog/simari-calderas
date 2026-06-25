        {{-- Create/Edit Modal --}}
        <div id="brandModal" class="user-manager-modal client-manage-modal">
            <div class="user-manager-modal-content client-modal-content">
                <div class="user-manager-modal-header">
                    <h2 id="brandModalTitle">Nueva Marca</h2>
                    <button type="button" class="table-users-manager-action-btn cancel" id="closeBrandModal">✕</button>
                </div>

                <div id="brand-modal-errors" class="user-manager-errors" style="display:none;"></div>

                <form class="user-manager-modal-body" id="brandForm">
                    @csrf

                    <div class="user-manager-form">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Nombre <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="name" id="brandName"
                                placeholder="Ej: SIMARI">
                        </div>
                        <div>
                            <label class="supliers-manager-slider-label">
                                Slug <span style="color:red">*</span>
                            </label>
                            <input type="text" class="users-manager-input" name="slug" id="brandSlug"
                                placeholder="simari">
                        </div>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">Descripción</label>
                        <textarea class="users-manager-input client-modal-textarea" name="description" id="brandDescription" rows="3"
                            placeholder="Descripción de la marca..."></textarea>
                    </div>

                    <div class="users-manager-email-camp" style="margin-top:12px;">
                        <label class="supliers-manager-slider-label">URL del Logo</label>
                        <input type="text" class="users-manager-input" name="logo_url" id="brandLogoUrl"
                            placeholder="https://...">
                    </div>

                    <div class="user-manager-form" style="margin-top:12px;">
                        <div>
                            <label class="supliers-manager-slider-label">
                                Estado <span style="color:red">*</span>
                            </label>
                            <select class="users-manager-select" name="is_active" id="brandIsActive">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="user-manager-modal-footer">
                        <button type="button" id="cancelBrandModal"
                            class="button-secondary size-adjustment">Cancelar</button>
                        <button type="submit" class="button-primary size-adjustment" id="brandSubmitBtn">Crear
                            Marca</button>
                    </div>
                </form>
            </div>
        </div>
