{{-- Create client modal --}}
<div id="clientCreateModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">

        {{-- Header --}}
        <div class="user-manager-modal-header">
            <h2>Nuevo Cliente</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeClientModal">✕</button>
        </div>

        {{-- Form --}}
        <form class="user-manager-modal-body" id="clientCreateForm"
            action="{{ route('admin.clients.store') }}" method="POST">
            @csrf

            {{-- Section: Personal info --}}
            <h3>Información Personal</h3>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre Completo *</label>
                    <input class="users-manager-input" type="text" name="full_name"
                        placeholder="Ej: Juan Pérez" value="{{ old('full_name') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Empresa *</label>
                    <input class="users-manager-input" type="text" name="company"
                        placeholder="Ej: Acme S.A. de C.V." value="{{ old('company') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email *</label>
                    <input class="users-manager-input" type="email" name="email"
                        placeholder="correo@empresa.mx" value="{{ old('email') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono *</label>
                    <input class="users-manager-input" type="text" name="phone"
                        placeholder="(555) 123-4567" value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">WhatsApp</label>
                    <input class="users-manager-input" type="text" name="whatsapp"
                        placeholder="5551234567" value="{{ old('whatsapp') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC *</label>
                    <input class="users-manager-input" type="text" name="rfc"
                        placeholder="XAXX010101000" value="{{ old('rfc') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Tipo de documento</label>
                    <select class="users-manager-select" name="document_type">
                        <option value="">Seleccionar...</option>
                        <option value="ine" {{ old('document_type') == 'ine' ? 'selected' : '' }}>INE</option>
                        <option value="pasaporte" {{ old('document_type') == 'pasaporte' ? 'selected' : '' }}>Pasaporte</option>
                        <option value="curp" {{ old('document_type') == 'curp' ? 'selected' : '' }}>CURP</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Número de documento</label>
                    <input class="users-manager-input" type="text" name="document_numer"
                        placeholder="Ej: 1234567890123" value="{{ old('document_numer') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Fecha de nacimiento</label>
                    <input class="users-manager-input" type="date" name="birth_date"
                        value="{{ old('birth_date') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">¿Cómo nos conoció?</label>
                    <select class="users-manager-select" name="source">
                        <option value="">Seleccionar...</option>
                        <option value="web" {{ old('source') == 'web' ? 'selected' : '' }}>Web</option>
                        <option value="whatsapp" {{ old('source') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                        <option value="admin" {{ old('source') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="campaña" {{ old('source') == 'campaña' ? 'selected' : '' }}>Campaña</option>
                        <option value="referido" {{ old('source') == 'referido' ? 'selected' : '' }}>Referido</option>
                    </select>
                </div>
            </div>

            {{-- Section: Addresses --}}
            <h3>Direcciones</h3>
            <div class="client-modal-address-section">
                <div>
                    <label class="supliers-manager-slider-label">Dirección Fiscal *</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address_line1"
                        placeholder="Calle, número, colonia...">{{ old('address_line1') }}</textarea>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Dirección de Envío</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address_line2"
                        id="shippingAddress" placeholder="Calle, número, colonia...">{{ old('address_line2') }}</textarea>
                </div>
                <div class="client-modal-same-as-fiscal">
                    <input type="checkbox" id="sameAsFiscal" name="same_as_fiscal" value="1">
                    <label for="sameAsFiscal" class="supliers-manager-slider-label">Misma que fiscal</label>
                </div>
                <div class="user-manager-form client-modal-address-grid">
                    <div>
                        <label class="supliers-manager-slider-label">Ciudad *</label>
                        <input class="users-manager-input" type="text" name="city"
                            placeholder="Ej: CDMX" value="{{ old('city') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">Estado *</label>
                        <input class="users-manager-input" type="text" name="state"
                            placeholder="Ej: CDMX" value="{{ old('state') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">C.P. *</label>
                        <input class="users-manager-input" type="text" name="postal_code"
                            placeholder="12345" value="{{ old('postal_code') }}">
                    </div>
                    <div>
                        <label class="supliers-manager-slider-label">País *</label>
                        <input class="users-manager-input" type="text" name="country"
                            placeholder="México" value="{{ old('country', 'México') }}">
                    </div>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Referencia de ubicación</label>
                    <input class="users-manager-input" type="text" name="reference"
                        placeholder="Ej: Entre Av. Juárez y Reforma" value="{{ old('reference') }}">
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
                        placeholder="Información privada sobre el cliente...">{{ old('notes') }}</textarea>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado del Cliente *</label>
                    <select class="users-manager-select" name="status">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>Suspendido</option>
                    </select>
                </div>
            </div>

            {{-- Footer --}}
            <div class="user-manager-modal-footer">
                <button type="button" id="cancelClientModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Cliente</button>
            </div>

        </form>
    </div>
</div>
