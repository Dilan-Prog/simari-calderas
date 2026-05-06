{{-- Edit client modal --}}
<div id="clientEditModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">

        {{-- Header --}}
        <div class="user-manager-modal-header">
            <h2>Editar Cliente</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeClientEditModal">✕</button>
        </div>

        {{-- Errors container --}}
        <div id="client-edit-errors" class="user-manager-errors" style="display:none;"></div>

        {{-- Form --}}
        <form class="user-manager-modal-body" id="clientEditForm" method="POST">
            @csrf
            @method('PUT')

            {{-- Section: Personal info --}}
            <h3>Información Personal</h3>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre Completo *</label>
                    <input class="users-manager-input" type="text" name="full_name"
                        placeholder="Ej: Juan Pérez">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Empresa</label>
                    <input class="users-manager-input" type="text" name="company"
                        placeholder="Ej: Acme S.A. de C.V.">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email *</label>
                    <input class="users-manager-input" type="email" name="email"
                        placeholder="correo@empresa.mx">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono</label>
                    <input class="users-manager-input" type="text" name="phone"
                        placeholder="(555) 123-4567">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">WhatsApp</label>
                    <input class="users-manager-input" type="text" name="whatsapp"
                        placeholder="5551234567">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC</label>
                    <input class="users-manager-input" type="text" name="rfc"
                        placeholder="XAXX010101000">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Tipo de documento</label>
                    <select class="users-manager-select" name="document_type">
                        <option value="">Seleccionar...</option>
                        <option value="ine">INE</option>
                        <option value="pasaporte">Pasaporte</option>
                        <option value="curp">CURP</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Número de documento</label>
                    <input class="users-manager-input" type="text" name="document_numer"
                        placeholder="Ej: 1234567890123">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Fecha de nacimiento</label>
                    <input class="users-manager-input" type="date" name="birth_date">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">¿Cómo nos conoció?</label>
                    <select class="users-manager-select" name="source">
                        <option value="">Seleccionar...</option>
                        <option value="web">Web</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="admin">Admin</option>
                        <option value="campaña">Campaña</option>
                        <option value="referido">Referido</option>
                    </select>
                </div>
            </div>

            {{-- Section: Addresses --}}
            <h3>Direcciones</h3>
            <div class="client-modal-address-section">
                <div>
                    <label class="supliers-manager-slider-label">Dirección Fiscal</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address_line1"
                        id="editFiscalAddress" placeholder="Calle, número, colonia..."></textarea>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Dirección de Envío</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address_line2"
                        id="editShippingAddress" placeholder="Calle, número, colonia..."></textarea>
                </div>
                <div class="client-modal-same-as-fiscal">
                    <input type="checkbox" id="editSameAsFiscal" name="same_as_fiscal" value="1">
                    <label for="editSameAsFiscal" class="supliers-manager-slider-label">Misma que fiscal</label>
                </div>
                <div class="user-manager-form client-modal-address-grid">
                    <div>
                        <label class="supliers-manager-slider-label">Ciudad</label>
                        <input class="users-manager-input" type="text" name="city"
                            placeholder="Ej: CDMX">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado</label>
                        <input class="users-manager-input" type="text" name="state"
                            placeholder="Ej: CDMX">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">C.P.</label>
                        <input class="users-manager-input" type="text" name="postal_code"
                            placeholder="12345">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">País</label>
                        <input class="users-manager-input" type="text" name="country"
                            placeholder="México">
                    </div>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Referencia de ubicación</label>
                    <input class="users-manager-input" type="text" name="reference"
                        placeholder="Ej: Entre Av. Juárez y Reforma">
                </div>
                <div class="client-modal-default-toggle">
                    <label class="supliers-manager-slider-label">Dirección predeterminada</label>
                    <label class="client-modal-toggle">
                        <input type="checkbox" name="is_default" value="1" checked>
                        <span class="client-modal-toggle-slider"></span>
                    </label>
                </div>
            </div>

            {{-- Section: Additional info --}}
            <h3>Información Adicional</h3>
            <div class="client-modal-address-section">
                <div>
                    <label class="supliers-manager-slider-label">Notas Internas</label>
                    <textarea class="users-manager-input client-modal-textarea" name="notes"
                        placeholder="Información privada sobre el cliente..."></textarea>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado del Cliente *</label>
                    <select class="users-manager-select" name="status">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </div>
            </div>

            {{-- Footer --}}
            <div class="user-manager-modal-footer">
                <button type="button" id="cancelClientEditModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Cambios</button>
            </div>

        </form>
    </div>
</div>
