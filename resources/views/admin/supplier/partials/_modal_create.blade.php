<div id="supplierCreateModal" class="user-manager-modal client-manage-modal">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2>Nuevo Proveedor</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeSupplierCreateModal">✕</button>
        </div>

        <form class="user-manager-modal-body" id="supplierCreateForm" action="{{ route('admin.suppliers.store') }}"
            method="POST" novalidate>
            @csrf

            {{-- Constancia SAT reader --}}
            <div class="cfdi-reader-section">
                <h3 style="margin-top:0">Leer Constancia SAT</h3>
                <div class="cfdi-upload-row">
                    <input type="file" id="supplierCfdiFileInput" accept=".pdf" style="display:none">
                    <button type="button" id="supplierCfdiReadBtn" class="button-secondary size-adjustment">
                        📄 Seleccionar Constancia (PDF)
                    </button>
                    <span id="supplierCfdiFileName" class="cfdi-filename">Ningún archivo seleccionado</span>
                </div>
                <div id="supplierCfdiStatus" class="cfdi-status" style="display:none"></div>
            </div>

            <h3>Información de la Empresa</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre de la Empresa *</label>
                    <input class="users-manager-input" type="text" name="company_name"
                        placeholder="Ej: Proveedor S.A. de C.V." value="{{ old('company_name') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">RFC *</label>
                    <input class="users-manager-input" type="text" name="rfc" placeholder="XAXX010101000"
                        value="{{ old('rfc') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Contacto Principal *</label>
                    <input class="users-manager-input" type="text" name="contact_name" placeholder="Ej: Juan Pérez"
                        value="{{ old('contact_name') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono *</label>
                    <input class="users-manager-input" type="text" name="phone" placeholder="(449) 123-4567"
                        value="{{ old('phone') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email *</label>
                    <input class="users-manager-input" type="email" name="email" placeholder="contacto@empresa.mx"
                        value="{{ old('email') }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Sitio Web</label>
                    <input class="users-manager-input" type="text" name="website" placeholder="https://empresa.mx"
                        value="{{ old('website') }}">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Dirección</label>
                    <textarea class="users-manager-input client-modal-textarea" name="address"
                        placeholder="Calle, número, colonia, ciudad...">{{ old('address') }}</textarea>
                </div>
            </div>

            <h3>Condiciones Comerciales</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Condiciones de Pago *</label>
                    <select class="users-manager-select" name="payment_terms">
                        <option value="">Seleccionar...</option>
                        <option value="contado" {{ old('payment_terms') == 'contado' ? 'selected' : '' }}>Contado
                        </option>
                        <option value="15 días" {{ old('payment_terms') == '15 días' ? 'selected' : '' }}>15 días
                        </option>
                        <option value="30 días" {{ old('payment_terms') == '30 días' ? 'selected' : '' }}>30 días
                        </option>
                        <option value="45 días" {{ old('payment_terms') == '45 días' ? 'selected' : '' }}>45 días
                        </option>
                        <option value="60 días" {{ old('payment_terms') == '60 días' ? 'selected' : '' }}>60 días
                        </option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Estado *</label>
                    <select class="users-manager-select" name="status">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo
                        </option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                            Inactivo</option>
                        <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>
                            Suspendido</option>
                    </select>
                </div>
            </div>

            <h3>Evaluación</h3>
            <div class="show-user-divider"></div>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Rating de Calidad (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_quality" min="0"
                        max="5" value="{{ old('rating_quality', 0) }}">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Rating de Cumplimiento (1-5)</label>
                    <input class="users-manager-input" type="number" name="rating_compliance" min="0"
                        max="5" value="{{ old('rating_compliance', 0) }}">
                </div>
                <div class="users-manager-email-camp">
                    <label class="supliers-manager-slider-label">Notas de Evaluación</label>
                    <textarea class="users-manager-input client-modal-textarea" name="evaluation_notes"
                        placeholder="Comentarios sobre la evaluación del proveedor...">{{ old('evaluation_notes') }}</textarea>
                </div>
            </div>

            <h3>Notas Internas</h3>
            <div class="show-user-divider"></div>
            <div class="client-modal-address-section">
                <div>
                    <textarea class="users-manager-input client-modal-textarea" name="notes"
                        placeholder="Notas privadas sobre el proveedor...">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelSupplierCreateModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('supplierCreateForm');
    if (!form) return;

    const fields = [
        ['company_name',  'Nombre de la Empresa'],
        ['rfc',           'RFC'],
        ['contact_name',  'Contacto Principal'],
        ['phone',         'Teléfono'],
        ['email',         'Email'],
        ['payment_terms', 'Condiciones de Pago'],
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
