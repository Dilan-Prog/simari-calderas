<div id="supplierEditModal" class="user-manager-modal client-manage-modal suppliers-page-modal" role="dialog" aria-modal="true" aria-labelledby="supplierEditModalTitle">
    <div class="user-manager-modal-content client-modal-content">
        <div class="user-manager-modal-header">
            <h2 id="supplierEditModalTitle">Editar Proveedor</h2>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeSupplierEditModal" aria-label="Cerrar" data-suppliers-modal-close>✕</button>
        </div>

        <form class="user-manager-modal-body" id="supplierEditForm" method="POST">
            @csrf
            {{-- Zona con scroll: encabezado y botones quedan fijos (proveedores.css, .suppliers-page-modal) --}}
            <div class="suppliers-modal-scroll">

            <div id="supplier-edit-errors" class="user-manager-errors" style="display:none;"></div>

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
                    <label class="supliers-manager-slider-label">Tipo de Persona</label>
                    <select class="users-manager-select" name="tipo_persona">
                        <option value="">Seleccionar...</option>
                        <option value="fisica">Física</option>
                        <option value="moral">Moral</option>
                    </select>
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Contacto Principal</label>
                    <input class="users-manager-input" type="text" name="contact_name" placeholder="Ej: Juan Pérez">
                </div>
                <div>
                    <label class="supliers-manager-slider-label">Teléfono</label>
                    <input class="users-manager-input" type="text" name="phone" placeholder="(449) 123-4567" maxlength="20">
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

            </div>{{-- /suppliers-modal-scroll --}}

            <div class="user-manager-modal-footer">
                <button type="button" id="cancelSupplierEditModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

{{-- Bloqueo de scroll del fondo + Escape + foco (celular) para TODOS los
     modales de Proveedores (.suppliers-page-modal). Vive aquí porque este
     parcial es el único que se incluye tanto en el índice como en el detalle
     del proveedor (_modal_show.blade.php). Sólo observa el estado de los
     modales —style.display en Nuevo/Editar, clase .active en Eliminar /
     Asignar producto / Importar— así que cualquier forma de abrir o cerrar
     actualiza el bloqueo sin tocar la lógica existente. Escape "pulsa" el
     botón [data-suppliers-modal-close] del modal de arriba, para que cada
     página use su propia animación/limpieza. --}}
@once
@push('scripts')
<script>
(function () {
    const init = () => {
        const modals = Array.from(document.querySelectorAll('.suppliers-page-modal'));
        if (!modals.length) return;

        const isOpen = (m) => !!m && (m.classList.contains('active') || m.style.display === 'flex');
        const byId = (id) => modals.find(m => m.id === id);
        // De arriba hacia abajo según z-index
        const stack = ['deleteSupplierModal', 'importSupplierProductsModal', 'assignProductModal',
            'supplierEditModal', 'supplierCreateModal'].map(byId).filter(Boolean);
        // Selector de formato de descarga (componente compartido): puede abrirse
        // encima de Importar (plantilla); si está abierto, Escape no cierra los de abajo.
        const downloadModal = document.getElementById('downloadFormatModal');

        const syncScrollLock = () => {
            document.body.classList.toggle('suppliers-scroll-lock', modals.some(isOpen));
        };

        const createModal = byId('supplierCreateModal');
        let createWasOpen = isOpen(createModal);
        const observer = new MutationObserver(() => {
            syncScrollLock();
            const createOpen = isOpen(createModal);
            if (createOpen && !createWasOpen) {
                // Al abrir "Nuevo": volver arriba y enfocar el primer campo
                const scroller = createModal.querySelector('.suppliers-modal-scroll');
                if (scroller) scroller.scrollTop = 0;
                const first = createModal.querySelector(
                    'form input:not([type="hidden"]):not([type="file"]):not([type="checkbox"]):not(:disabled)');
                if (first && window.matchMedia('(min-width: 641px)').matches) setTimeout(() => {
                    try { first.focus({ preventScroll: true }); } catch (_) {}
                }, 50);
            }
            createWasOpen = createOpen;
        });
        modals.forEach(m => observer.observe(m, { attributes: true, attributeFilter: ['style', 'class'] }));

        // Errores del servidor: llevar el scroll del cuerpo arriba para verlos
        ['supplier-edit-errors', 'supplier-create-errors', 'assign-product-errors'].forEach(id => {
            const box = document.getElementById(id);
            if (!box) return;
            new MutationObserver(() => {
                if (box.style.display === 'none') return;
                const scroller = box.closest('.suppliers-modal-scroll, .ap-modal-scroll');
                if (scroller) scroller.scrollTo({ top: 0, behavior: 'smooth' });
            }).observe(box, { attributes: true, attributeFilter: ['style'] });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (downloadModal && downloadModal.classList.contains('active')) return;
            const top = stack.find(isOpen);
            if (!top) return;
            const closeBtn = top.querySelector('[data-suppliers-modal-close]');
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
