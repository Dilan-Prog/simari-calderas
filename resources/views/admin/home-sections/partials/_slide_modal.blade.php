    {{-- Create/Edit Slide Modal --}}
    <div id="slideModal" class="user-manager-modal client-manage-modal">
        <div class="user-manager-modal-content client-modal-content">
            <div class="user-manager-modal-header">
                <h2 id="slideModalTitle">Nuevo Slide</h2>
                <button type="button" class="table-users-manager-action-btn cancel"
                    id="closeSlideModal">✕</button>
            </div>

            <div id="slide-modal-errors" class="user-manager-errors" style="display:none;"></div>

            <form class="user-manager-modal-body" id="slideForm">
                @csrf
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">URL de Imagen <span style="color:red">*</span></label>
                    <input type="text" class="users-manager-input" name="image_url" id="slideImageUrl" placeholder="https://...">
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">Texto de Badge</label>
                        <input type="text" class="users-manager-input" name="badge_text" id="slideBadgeText" placeholder="Ej: Nuevo">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Título Resaltado</label>
                        <input type="text" class="users-manager-input" name="title_highlight" id="slideTitleHighlight" placeholder="Palabra resaltada">
                    </div>
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Título <span style="color:red">*</span></label>
                    <input type="text" class="users-manager-input" name="title" id="slideTitle" placeholder="Título del slide">
                </div>

                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Descripción</label>
                    <textarea class="users-manager-input client-modal-textarea" name="description" id="slideDescription" rows="3" placeholder="Descripción del slide..."></textarea>
                </div>

                <div class="user-manager-form">
                    <div>
                        <label class="supliers-manager-slider-label">URL de Enlace</label>
                        <input type="text" class="users-manager-input" name="link_url" id="slideLinkUrl" placeholder="/catalogo">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado</label>
                        <select class="users-manager-select" name="is_active" id="slideIsActive">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="user-manager-modal-footer">
                    <button type="button" id="cancelSlideModal"
                        class="button-secondary size-adjustment">Cancelar</button>
                    <button type="submit" class="button-primary size-adjustment"
                        id="slideSubmitBtn">Crear Slide</button>
                </div>
            </form>
        </div>
    </div>
