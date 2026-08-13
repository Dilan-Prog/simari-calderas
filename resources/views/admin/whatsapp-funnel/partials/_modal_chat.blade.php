{{-- Modal de chat — se abre al hacer click en una tarjeta del kanban. El
     contenido (hilo de mensajes, datos del panel derecho) se carga vía AJAX
     a GET admin.whatsapp-funnel.messages en JS; este shell solo define la
     estructura estática. Mismo componente base .del-confirm-overlay que el
     resto del admin, con una caja más ancha (.wf-chat-modal-box). --}}
<div id="wfChatModal" class="del-confirm-overlay">
    <div class="del-confirm-box wf-chat-modal-box">
        <div class="wf-chat-modal-header">
            <div class="wf-chat-modal-header-left">
                <span class="wf-chat-avatar" id="wfChatModalAvatar">?</span>
                <div>
                    <h2 class="wf-chat-modal-title" id="wfChatModalName">—</h2>
                    <p class="wf-chat-modal-phone" id="wfChatModalPhone"></p>
                </div>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="wfCloseChatModal">✕</button>
        </div>

        <div class="wf-chat-modal-body">
            {{-- Columna izquierda: hilo de mensajes --}}
            <div class="wf-chat-thread-col">
                <div class="wf-chat-thread" id="wfChatThread">
                    <p class="wf-chat-loading">Cargando conversación...</p>
                </div>

                <div class="wf-chat-composer" id="wfChatComposer">
                    <div class="wf-chat-window-notice" id="wfChatWindowNotice" style="display:none;">
                        Ventana de 24h cerrada — solo puedes enviar una plantilla aprobada.
                    </div>

                    <div class="wf-chat-composer-row" id="wfChatTextRow">
                        <textarea id="wfChatTextInput" class="wf-chat-textarea" rows="2" placeholder="Escribe un mensaje..."></textarea>
                        <button type="button" class="button-primary size-adjustment" id="wfChatSendTextBtn">Enviar</button>
                    </div>

                    <div class="wf-chat-composer-row" id="wfChatTemplateRow" style="display:none;">
                        <select id="wfChatTemplateSelect" class="wf-filter-select"></select>
                        <button type="button" class="button-primary size-adjustment" id="wfChatSendTemplateBtn">Enviar plantilla</button>
                    </div>
                </div>
            </div>

            {{-- Columna derecha: panel de datos/acciones --}}
            <div class="wf-chat-panel-col">
                <div class="wf-chat-panel-section">
                    <h3 class="wf-chat-panel-title">Datos del chat</h3>
                    <p class="wf-chat-panel-row"><strong>Teléfono:</strong> <span id="wfPanelPhone">—</span></p>
                    <p class="wf-chat-panel-row"><strong>Cliente:</strong> <span id="wfPanelCustomer">Sin cliente vinculado</span></p>
                </div>

                <div class="wf-chat-panel-section">
                    <h3 class="wf-chat-panel-title">Agente asignado</h3>
                    <select id="wfPanelAgentSelect" class="wf-filter-select"></select>
                    <button type="button" class="button-secondary size-adjustment wf-panel-btn" id="wfPanelReassignBtn">
                        Reasignar agente
                    </button>
                </div>

                <div class="wf-chat-panel-section" id="wfPanelDealSection">
                    <h3 class="wf-chat-panel-title">Negocio</h3>
                    <p class="wf-chat-panel-row" id="wfPanelDealLinked" style="display:none;">
                        Vinculado a <a href="#" id="wfPanelDealLink" target="_blank">un negocio existente</a>.
                    </p>
                    <button type="button" class="button-primary size-adjustment wf-panel-btn" id="wfPanelCreateDealBtn">
                        Crear negocio
                    </button>
                </div>

                <div class="wf-chat-panel-section">
                    <button type="button" class="button-secondary size-adjustment wf-panel-btn" disabled
                        title="Próximamente">
                        Ver en la bandeja
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Mini-modal "Crear negocio" — se abre encima del modal de chat cuando se
     pulsa el botón del panel derecho. Reutiliza el mismo shell de modal. --}}
<div id="wfCreateDealModal" class="del-confirm-overlay">
    <div class="del-confirm-box ap-modal-box">
        <div class="ap-modal-header">
            <div class="ap-modal-header-text">
                <h2 class="del-confirm-title">Crear negocio</h2>
                <p class="del-confirm-desc">Se creará un negocio real en el pipeline de Negocios, prellenado con los datos de este chat.</p>
            </div>
            <button type="button" class="table-users-manager-action-btn cancel" id="wfCloseCreateDealModal">✕</button>
        </div>

        <div id="wfCreateDealErrors" class="ap-modal-errors" style="display:none;"></div>

        <form class="ap-modal-body" id="wfCreateDealForm">
            <div class="ap-field-group">
                <label class="supliers-manager-slider-label">Nombre del negocio <span style="color:red">*</span></label>
                <input type="text" class="users-manager-input" id="wfDealName" maxlength="255">
            </div>
            <div class="ap-field-grid" style="margin-top:12px;">
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Pipeline <span style="color:red">*</span></label>
                    <select id="wfDealPipelineSelect" class="wf-filter-select"></select>
                </div>
                <div class="ap-field-group">
                    <label class="supliers-manager-slider-label">Monto estimado</label>
                    <input type="number" step="0.01" min="0" class="users-manager-input" id="wfDealAmount">
                </div>
            </div>
            <div class="ap-modal-footer">
                <button type="button" class="button-secondary size-adjustment" id="wfCancelCreateDealModal">Cancelar</button>
                <button type="submit" class="button-primary size-adjustment">Crear negocio</button>
            </div>
        </form>
    </div>
</div>
