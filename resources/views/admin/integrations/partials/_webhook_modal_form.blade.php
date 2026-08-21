{{-- Crear/editar webhook — mismo shell .del-confirm-overlay/.ap-modal-box
     que resources/views/admin/credentials/partials/_modal_form.blade.php --}}
<div id="webhookModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13a3 3 0 1 0-2.83-4H15a4 4 0 0 0-4 4v1a4 4 0 0 1-4 4H6" />
                    <path d="M6 13a3 3 0 1 0 2.83 4H9a4 4 0 0 0 4-4v-1a4 4 0 0 1 4-4h1" />
                </svg>
            </div>
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title" id="webhookModalTitle">Nuevo Webhook</h2>
                <p class="del-confirm-desc">Envíos salientes configurables usados por las automatizaciones.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="closeWebhookModal">✕</button>
        </div>

        <div id="webhook-modal-errors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="webhookForm">
            @csrf

            <div class="user-manager-form user-manager-form-3 ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        Nombre <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="name" id="whName" maxlength="150"
                        placeholder="Ej: Notificación a CRM externo">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">
                        URL <span style="color:red">*</span>
                    </label>
                    <input type="text" class="users-manager-input" name="url" id="whUrl"
                        placeholder="https://ejemplo.com/webhook">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Método</label>
                    <select class="users-manager-input" name="method" id="whMethod">
                        <option value="GET">GET</option>
                        <option value="POST" selected>POST</option>
                        <option value="PUT">PUT</option>
                        <option value="PATCH">PATCH</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
            </div>

            <div class="ap-field-group">
                <label class="supliers-manager-slider-label">Encabezados (headers)</label>
                <div id="whHeadersRows"></div>
                <button type="button" class="button-secondary size-adjustment" id="whAddHeaderBtn"
                    style="align-self:flex-start; margin-top:6px;">
                    + Agregar campo
                </button>
            </div>

            <div class="ap-field-group">
                <label class="supliers-manager-slider-label">Credencial</label>
                <select class="users-manager-input" name="credential_id" id="whCredentialId">
                    <option value="">— ninguna —</option>
                </select>
                <p class="ap-readonly-note">Opcional. Solo se listan credenciales de tipo <code>webhook</code>.</p>
            </div>

            <div class="ap-field-group">
                <button type="button" class="button-secondary size-adjustment" id="whTestConnectionBtn"
                    style="align-self:flex-start;">
                    Probar conexión
                </button>
                <p id="whTestConnectionResult" class="ap-readonly-note" style="display:none;"></p>
            </div>

            <div class="ap-modal-footer">
                <button type="button" id="cancelWebhookModal"
                    class="button-secondary size-adjustment">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment" id="whSubmitBtn">Crear
                    Webhook</button>
            </div>
        </form>
    </div>
</div>

{{-- Template de una fila de header key/value — clonado por JS (ver
     _webhook_scripts.blade.php). Se mantiene fuera del form activo
     (display:none) para no interferir con FormData. --}}
<template id="whHeaderRowTemplate">
    <div class="wh-header-row">
        <div class="ap-field-group">
            <input type="text" class="users-manager-input wh-header-key" placeholder="Clave (ej: Authorization)">
        </div>
        <div class="ap-field-group">
            <input type="text" class="users-manager-input wh-header-value" placeholder="Valor">
        </div>
        <button type="button" class="action-btn wh-header-remove" title="Quitar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </button>
    </div>
</template>
