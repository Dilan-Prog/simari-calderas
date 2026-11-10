{{-- Edit client modal --}}
<div id="clientEditModal" class="user-manager-modal client-manage-modal clients-page-modal"
    role="dialog" aria-modal="true" aria-labelledby="clientEditModalTitle">
    <div class="user-manager-modal-content client-modal-content">

        {{-- Header --}}
        <div class="user-manager-modal-header">
            <h2 id="clientEditModalTitle">Editar Cliente</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeClientEditModal"
                aria-label="Cerrar" data-clients-modal-close>✕</button>
        </div>

        {{-- Form --}}
        <form class="user-manager-modal-body" id="clientEditForm" method="POST">
            @csrf
            @method('PUT')
            {{-- Zona con scroll: encabezado y botones quedan fijos (clientes-modals.css) --}}
            <div class="clients-modal-scroll">

            {{-- Errors container (dentro del scroll para que no tape el formulario en celular) --}}
            <div id="client-edit-errors" class="user-manager-errors" style="display:none;"></div>

            {{-- Section: Personal info --}}
            <h3>Información Personal</h3>
            <div class="user-manager-form">
                <div>
                    <label class="supliers-manager-slider-label">Nombre Completo *</label>
                    <input class="users-manager-input" type="text" name="full_name"
                        placeholder="Ej: Juan Pérez">
                </div>
                <div>
                    {{-- FIX QA-12: missing the * that Crear already has for this
                         field — validateFormFields() (shared by both forms in
                         _scripts.blade.php) already blocks saving with company
                         empty, so the label was silently understating what the
                         form actually enforces. --}}
                    <label class="supliers-manager-slider-label">Empresa *</label>
                    <input class="users-manager-input" type="text" name="company"
                        placeholder="Ej: Acme S.A. de C.V.">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Email</label>
                    {{-- FIX QA-4: Added text-transform: lowercase for immediate visual
                         feedback while typing. The actual value is already forced to
                         lowercase in JS (dynamicEmailLowercase), this is just cosmetic. --}}
                    <input class="users-manager-input" type="email" name="email" id="clientEditEmailInput"
                        style="text-transform: lowercase" placeholder="correo@empresa.mx">
                    <label class="client-modal-checkbox-inline">
                        <input type="checkbox" name="sin_correo" id="clientEditSinCorreo" value="1">
                        Sin Correo
                    </label>
                </div>
                <div>
                    {{-- FIX QA-12: same missing-asterisk issue as Empresa above. --}}
                    <label class="supliers-manager-slider-label">Teléfono *</label>
                    <input class="users-manager-input" type="text" name="phone"
                        placeholder="(555) 123-4567">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">WhatsApp</label>
                    <input class="users-manager-input" type="text" name="whatsapp"
                        placeholder="5551234567">
                </div>
                <div>
                    {{-- FIX QA-12: same missing-asterisk issue as Empresa above. --}}
                    <label class="supliers-manager-slider-label">RFC</label>
                    <input class="users-manager-input" type="text" name="rfc" id="clientEditRfcInput"
                        placeholder="XAXX010101000">
                    <label class="client-modal-checkbox-inline">
                        <input type="checkbox" name="rfc_generic" id="clientEditRfcGenerico" value="1">
                        RFC Genérico
                    </label>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Tipo de Persona</label>
                    <select class="users-manager-select" name="tipo_persona" id="clientEditTipoPersona">
                        <option value="">Seleccionar...</option>
                        <option value="fisica">Física</option>
                        <option value="moral">Moral</option>
                    </select>
                </div>
                <div>
                    {{-- FIX QA-12: this field is required by validateFormFields()
                         and by the server (required|in:...) in BOTH forms, but
                         neither Crear nor Editar showed a required asterisk. --}}
                    <label class="supliers-manager-slider-label">Tipo de documento</label>
                    {{-- FIX QA-8 Part A: Added missing 'cfdi' option so this select
                         has the exact same options as the create modal — without it,
                         a customer with document_type=cfdi couldn't be preselected. --}}
                    <select class="users-manager-select" name="document_type">
                        <option value="">Sin especificar</option>
                        <option value="ine">INE</option>
                        <option value="pasaporte">Pasaporte</option>
                        <option value="curp">CURP</option>
                        <option value="cfdi">CFDI</option>
                    </select>
                </div>
                <div>
                    {{-- FIX QA-12: same missing-required-asterisk issue as
                         "Tipo de documento" above. --}}
                    <label class="supliers-manager-slider-label">¿Cómo nos conoció? *</label>
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

            </div>{{-- /.clients-modal-scroll --}}

            {{-- Footer --}}
            <div class="user-manager-modal-footer">
                <button type="button" id="cancelClientEditModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Cambios</button>
            </div>

        </form>
    </div>
</div>

{{-- Bloqueo de scroll del fondo + Escape + foco (celular) para TODOS los
     modales de Clientes (.clients-page-modal). Vive aquí porque este parcial
     es el único que se incluye tanto en el índice (con _scripts.blade.php)
     como en el detalle del cliente (partials/show.blade.php, que abre este
     modal con su propio JS). Solo observa el estado de los modales —
     style.display en Nuevo/Editar, clase .active en Eliminar/Acceso— así que
     cualquier forma de abrir o cerrar actualiza el bloqueo sin tocar la
     lógica existente. Escape "pulsa" el botón de cerrar del modal de arriba,
     para que cada página use su propia animación/limpieza. --}}
@once
@push('scripts')
<script>
(function () {
    const init = () => {
        const modals = Array.from(document.querySelectorAll('.clients-page-modal'));
        if (!modals.length) return;

        const isOpen = (m) => !!m && (m.classList.contains('active') || m.style.display === 'flex');
        const byId = (id) => modals.find(m => m.id === id);
        // De arriba hacia abajo según z-index: acceso > eliminar > editar > nuevo
        const stack = ['accessModalOverlay', 'deleteClientModal', 'clientEditModal', 'clientCreateModal']
            .map(byId).filter(Boolean);

        const syncScrollLock = () => {
            document.body.classList.toggle('clients-scroll-lock', modals.some(isOpen));
        };

        const createModal = byId('clientCreateModal');
        let createWasOpen = isOpen(createModal);
        const observer = new MutationObserver(() => {
            syncScrollLock();
            const createOpen = isOpen(createModal);
            if (createOpen && !createWasOpen) {
                const first = createModal.querySelector(
                    'form input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not(:disabled)');
                if (first) setTimeout(() => {
                    try { first.focus({ preventScroll: true }); } catch (_) {}
                }, 50);
            }
            createWasOpen = createOpen;
        });
        modals.forEach(m => observer.observe(m, { attributes: true, attributeFilter: ['style', 'class'] }));

        // Errores del servidor al editar: llevar el scroll del cuerpo arriba para verlos
        const editErrors = document.getElementById('client-edit-errors');
        if (editErrors) {
            new MutationObserver(() => {
                if (editErrors.style.display === 'none') return;
                const scroller = editErrors.closest('.clients-modal-scroll');
                if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
            }).observe(editErrors, { attributes: true, attributeFilter: ['style'] });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const top = stack.find(isOpen);
            if (!top) return;
            const closeBtn = top.querySelector('[data-clients-modal-close]');
            if (closeBtn) closeBtn.click();
        });

        syncScrollLock();
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endpush
@endonce
