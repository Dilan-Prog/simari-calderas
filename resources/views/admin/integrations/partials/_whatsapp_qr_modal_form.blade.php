{{-- Crear cuenta "WhatsApp Web" (conexión por QR, Baileys) -- un solo campo
     (Nombre), a diferencia del modal de API que captura phone_number_id/
     tokens/etc. Movido desde
     resources/views/admin/whatsapp-accounts/partials/_modal_qr_form.blade.php.
     Postea al mismo endpoint store() de siempre con connection_type=baileys_qr
     oculto; al terminar, se abre el modal de escaneo para la cuenta recién
     creada. --}}
<div id="whatsappQrFormModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="5" height="5" x="3" y="3" rx="1" />
                    <rect width="5" height="5" x="16" y="3" rx="1" />
                    <rect width="5" height="5" x="3" y="16" rx="1" />
                    <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
                    <path d="M21 21v.01" />
                    <path d="M12 7v3a2 2 0 0 1-2 2H7" />
                    <path d="M3 12h.01" />
                    <path d="M12 3h.01" />
                    <path d="M12 16v.01" />
                    <path d="M16 12h1" />
                    <path d="M21 12v.01" />
                    <path d="M12 21v-1" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title">Nueva Cuenta de WhatsApp Web</h2>
                <p class="del-confirm-desc">
                    Solo necesitas un nombre para identificarla -- el número se detecta al escanear el QR desde
                    WhatsApp.
                </p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel"
                id="closeWhatsappQrFormModal">✕</button>
        </div>

        <div id="whatsapp-qr-form-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="whatsappQrForm">
            @csrf
            <input type="hidden" name="connection_type" value="baileys_qr">

            <div class="ap-field-group">
                <label class="supliers-manager-slider-label">
                    Nombre <span style="color:red">*</span>
                </label>
                <input type="text" class="users-manager-input" name="name" id="waQrName" maxlength="100"
                    placeholder="Ej: Ventas MX">
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelWhatsappQrForm"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="waQrSubmitBtn">Crear y generar
                    QR</button>
            </div>
        </form>
    </div>
</div>
