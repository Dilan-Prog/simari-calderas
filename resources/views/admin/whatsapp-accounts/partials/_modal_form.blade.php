{{-- Crear/editar cuenta de WhatsApp — mismo shell .del-confirm-overlay que
     Credenciales/Métodos de pago. El token de acceso nunca se precarga al
     editar porque el servidor no lo devuelve (ver $hidden en el modelo). --}}
<div id="whatsappAccountModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.38 2 2 0 0 1 3.62 1.22l3 .01a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="whatsappAccountModalTitle">Nueva Cuenta de WhatsApp</h2>
                <p class="del-confirm-desc">Conecta un número de WhatsApp Business vía Meta Cloud API.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel"
                id="closeWhatsappAccountModal">✕</button>
        </div>

        <div id="whatsapp-account-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="whatsappAccountForm">
            @csrf

            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Nombre <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="name" id="waName" maxlength="100"
                        placeholder="Ej: Ventas MX">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Número visible <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="phone_number" id="waPhoneNumber"
                        maxlength="30" placeholder="+52 55 1234 5678">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Estado</label>
                    <label class="switch-label" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="is_active" id="waIsActive" checked>
                        <span>Activa</span>
                    </label>
                </div>
            </div>

            <div class="ap-field-grid" style="margin-top:12px;">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Phone Number ID (Meta)</label>
                    <input type="text" class="users-manager-input" name="phone_number_id" id="waPhoneNumberId"
                        maxlength="100" placeholder="ID que Meta usa para enviar, distinto al número visible">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">WhatsApp Business Account ID</label>
                    <input type="text" class="users-manager-input" name="whatsapp_business_account_id"
                        id="waBusinessAccountId" maxlength="100">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Token de verificación del webhook</label>
                    <input type="text" class="users-manager-input" name="webhook_verify_token"
                        id="waWebhookVerifyToken" maxlength="100"
                        placeholder="Debe coincidir con lo configurado en Meta for Developers">
                </div>
            </div>

            <div class="ap-field-group" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">Access Token</label>
                <textarea class="users-manager-input" name="access_token" id="waAccessToken" rows="4"
                    placeholder="Token permanente de la app de Meta"></textarea>
                <p class="ap-readonly-note">
                    Se cifra antes de guardarse. Al editar, déjalo vacío para conservar el valor actual.
                </p>
            </div>

            <div class="ap-field-group" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">App Secret</label>
                <textarea class="users-manager-input" name="app_secret" id="waAppSecret" rows="2"
                    placeholder="App Secret de la app de Meta, usado para verificar la firma del webhook"></textarea>
                <p class="ap-readonly-note">
                    Se cifra antes de guardarse. Al editar, déjalo vacío para conservar el valor actual.
                </p>
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelWhatsappAccountModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="waSubmitBtn">Crear
                    Cuenta</button>
            </div>
        </form>
    </div>
</div>
