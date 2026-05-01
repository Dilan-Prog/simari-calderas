<div id="supplierEditModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2>Editar Proveedor</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeSupplierEditModal">✕</button>
        </div>

        <div id="supplier-edit-errors" class="user-manager-errors" style="display:none;"></div>

        <form class="user-manager-modal-body" id="supplierEditForm" method="POST">
            @csrf
            @method('PUT')

            <h3>Información de la Empresa</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre de la Empresa *</label>
                    <input class="users-manager-input" type="text" name="company_name" placeholder="Ej: Proveedor S.A. de C.V.">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC</label>
                    <input class="users-manager-input" type="text" name="rfc" placeholder="XAXX010101000">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Contacto Principal</label>
                    <input class="users-manager-input" type="text" name="contact_name" placeholder="Ej: Juan Pérez">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono</label>
                    <input class="users-manager-input" type="text" name="phone" placeholder="(449) 123-4567">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email</label>
                    <input class="users-manager-input" type="email" name="email" placeholder="contacto@empresa.mx">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Sitio Web</label>
                    <input class="users-manager-input" type="text" name="website" placeholder="https://empresa.mx">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Dirección</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address"
                        placeholder="Calle, número, colonia, ciudad..."></textarea>
                </div>
            </div>

            <h3>Condiciones Comerciales</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Condiciones de Pago *</label>
                    <select class="users-manager-select" name="payment_terms">
                        <option value="">Seleccionar...</option>
                        <option value="contado">Contado</option>
                        <option value="15 días">15 días</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                        <option value="60 días">60 días</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado *</label>
                    <select class="users-manager-select" name="status">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>
            </div>

            <h3>Evaluación</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Rating de Calidad (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_quality" min="0" max="5">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Rating de Cumplimiento (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_compliance" min="0" max="5">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Notas de Evaluación</label>
                    <textarea class="users-manager-input client-modal-textarea" name="evaluation_notes"
                        placeholder="Comentarios sobre la evaluación del proveedor..."></textarea>
                </div>
            </div>

            <h3>Notas Internas</h3>
            <div class="show-user-divider"></div>
            <div class="client-modal-address-section">
                <div>
                    <textarea class="users-manager-input client-modal-textarea" name="notes"
                        placeholder="Notas privadas sobre el proveedor..."></textarea>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelSupplierEditModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
