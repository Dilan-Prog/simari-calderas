{{-- "+ Nuevo chat" — inicia una conversación saliente. WhatsApp exige una
     plantilla aprobada para abrir conversación (no se puede con texto
     libre), por eso el formulario siempre incluye selector de plantilla. --}}
<div id="wfNewChatModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title">Nuevo chat</h2>
                <p class="del-confirm-desc">Inicia una conversación saliente enviando una plantilla aprobada.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="wfCloseNewChatModal">✕</button>
        </div>

        <div id="wfNewChatErrors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="wfNewChatForm">
            @csrf
            <div class="ap-field-grid">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Nombre</label>
                    <input type="text" class="users-manager-input" name="name" maxlength="180">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Empresa</label>
                    <input type="text" class="users-manager-input" name="company" maxlength="255">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Número de WhatsApp <span style="color:red">*</span></label>
                    <input type="text" class="users-manager-input" name="contact_phone" maxlength="30" placeholder="521555...">
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Cuenta de WhatsApp <span style="color:red">*</span></label>
                    <select class="wf-filter-select" name="account_id" id="wfNewChatAccount"></select>
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Etapa <span style="color:red">*</span></label>
                    <select class="wf-filter-select" name="pipeline_stage_id" id="wfNewChatStage"></select>
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Agente</label>
                    <select class="wf-filter-select" name="assigned_user_id" id="wfNewChatAgent">
                        <option value="">Sin asignar</option>
                    </select>
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Plantilla <span style="color:red">*</span></label>
                    <select class="wf-filter-select" name="template_name" id="wfNewChatTemplate"></select>
                </div>
            </div>

            <div id="wfNewChatTemplateParams" class="ap-field-grid" style="margin-top:12px;"></div>

            <div class="ap-modal-footer">
                <button type="button" class="button-secondary size-adjustment" id="wfCancelNewChatModal">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Iniciar chat</button>
            </div>
        </form>
    </div>
</div>
