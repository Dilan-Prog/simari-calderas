{{-- Crear/editar credencial — un solo modal reutilizado para ambos modos
     (el JS de _scripts.blade.php rellena name/type si es edición; payload
     nunca se precarga porque el servidor no lo devuelve), mismo shell
     .del-confirm-overlay/.del-confirm-box que payment-methods (ver
     resources/css/admin/components/ui-kit.css). --}}
<div id="credentialModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="credentialModalTitle">Nueva Credencial</h2>
                <p class="del-confirm-desc">Guarda claves y accesos usados por las automatizaciones.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeCredentialModal">✕</button>
        </div>

        <div id="credential-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="credentialForm">
            @csrf

            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Nombre <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="name" id="credName" maxlength="150"
                        placeholder="Ej: SMTP producción">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Tipo <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="type" id="credType" maxlength="100"
                        placeholder="Ej: smtp, slack, generic">
                </div>
            </div>

            {{-- Sub-formulario específico para type=mysql (Fase 8): campos
                 separados en vez del textarea JSON genérico. Se ensamblan a
                 JSON en el cliente antes de enviar (ver _scripts.blade.php).
                 Oculto por defecto; el JS lo muestra/oculta según el valor
                 escrito en "Tipo". --}}
            <div id="credMysqlFields" class="ap-field-grid" style="display:none; margin-top:12px;">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Host</label>
                    <input type="text" class="users-manager-input" id="credMysqlHost" placeholder="127.0.0.1">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Puerto</label>
                    <input type="text" class="users-manager-input" id="credMysqlPort" placeholder="3306">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Base de datos</label>
                    <input type="text" class="users-manager-input" id="credMysqlDatabase">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Usuario</label>
                    <input type="text" class="users-manager-input" id="credMysqlUsername">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Contraseña</label>
                    <input type="password" class="users-manager-input" id="credMysqlPassword"
                        placeholder="Al editar, déjalo vacío para conservar el valor actual">
                </div>
                <div class="ap-field-group" style="display:flex; align-items:flex-end; gap:8px;">
                    <button type="button" class="button-secondary size-adjustment" id="credTestConnectionBtn">
                        Probar conexión
                    </button>
                </div>
            </div>
            <p id="credTestConnectionResult" class="ap-readonly-note" style="display:none; margin-top:4px;"></p>

            <div class="ap-field-group" id="credPayloadGroup" style="margin-top:12px;">
                <label class="supliers-manager-slider-label">Payload</label>
                <textarea class="users-manager-input" name="payload" id="credPayload" rows="8"
                    placeholder='{"api_key": "...", "otro_campo": "..."}'></textarea>
                <p class="ap-readonly-note">
                    JSON con los datos a cifrar. Al editar, déjalo vacío para conservar el valor actual.
                </p>
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelCredentialModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="credSubmitBtn">Crear
                    Credencial</button>
            </div>
        </form>
    </div>
</div>
