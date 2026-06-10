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
            action="{{ route('admin.clients.store') }}" method="POST" novalidate>
            @csrf

            {{-- CFDI / Constancia SAT reader --}}
            <div class="cfdi-reader-section">
                <h3 style="margin-top:0">Leer Constancia SAT</h3>
                <div class="cfdi-upload-row">
                    <input type="file" id="cfdiFileInput" accept=".pdf" style="display:none">
                    <button type="button" id="cfdiReadBtn" class="button-secondary size-adjustment">
                        📄 Seleccionar Constancia (PDF)
                    </button>
                    <span id="cfdiFileName" class="cfdi-filename">Ningún archivo seleccionado</span>
                </div>
                <div id="cfdiStatus" class="cfdi-status" style="display:none"></div>
            </div>

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
                        <option value="cfdi" {{ old('document_type') == 'cfdi' ? 'selected' : '' }}>CFDI</option>
                    </select>
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

@push('scripts')
<script>
(function () {
    const form = document.getElementById('clientCreateForm');
    if (!form) return;

    const fields = [
        ['full_name',     'Nombre Completo'],
        ['company',       'Empresa'],
        ['email',         'Email'],
        ['phone',         'Teléfono'],
        ['rfc',           'RFC'],
        ['address_line1', 'Dirección Fiscal'],
        ['city',          'Ciudad'],
        ['state',         'Estado'],
        ['postal_code',   'C.P.'],
        ['country',       'País'],
    ];

    function clr(name) {
        const f = form.querySelector(`[name="${name}"]`);
        if (!f) return;
        f.classList.remove('val-error-input', 'val-error-select');
        form.querySelectorAll(`.val-error-msg[data-for="${name}"]`).forEach(e => e.remove());
    }
    function mark(name, label) {
        const f = form.querySelector(`[name="${name}"]`);
        if (!f) return;
        f.classList.add(f.tagName === 'SELECT' ? 'val-error-select' : 'val-error-input');
        if (!form.querySelector(`.val-error-msg[data-for="${name}"]`)) {
            const msg = document.createElement('span');
            msg.className = 'val-error-msg';
            msg.dataset.for = name;
            msg.textContent = `El campo "${label}" es requerido`;
            f.insertAdjacentElement('afterend', msg);
        }
    }

    fields.forEach(([name]) => {
        const f = form.querySelector(`[name="${name}"]`);
        if (!f) return;
        f.addEventListener(f.tagName === 'SELECT' ? 'change' : 'input', () => clr(name));
    });

    form.addEventListener('submit', (e) => {
        let first = null;
        fields.forEach(([name, label]) => {
            const f = form.querySelector(`[name="${name}"]`);
            if (!f) return;
            const empty = f.tagName === 'SELECT' ? f.value === '' : f.value.trim() === '';
            if (empty) { mark(name, label); if (!first) first = f; }
            else clr(name);
        });
        if (first) {
            e.preventDefault();
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            try { first.focus(); } catch (_) {}
        }
    });
})();
</script>
@endpush
