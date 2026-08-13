import Sortable from 'sortablejs';

/* ============================================================
   whatsapp-funnel-board.js — Embudo de Venta (kanban de conversaciones
   de WhatsApp). Mismo patrón que pipeline-board.js: SortableJS puro,
   sin jQuery, URLs con placeholder sustituido en JS para no hardcodear
   el prefijo de ruta. IDs/atributos exactos: ver
   resources/views/admin/whatsapp-funnel/index.blade.php
   ============================================================ */
(function () {
    'use strict';

    var board = document.getElementById('wfBoard');
    if (!board) return;

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var data = window.__wfFunnelData || { stages: [], accounts: [], agents: [], templates: [], dealPipelines: [] };

    var moveStageUrlTemplate    = board.dataset.moveStageUrlTemplate || '';
    var messagesUrlTemplate     = board.dataset.messagesUrlTemplate || '';
    var sendMessageUrlTemplate  = board.dataset.sendMessageUrlTemplate || '';
    var createDealUrlTemplate   = board.dataset.createDealUrlTemplate || '';
    var reassignAgentUrlTemplate = board.dataset.reassignAgentUrlTemplate || '';
    var newChatUrl              = board.dataset.newChatUrl || '';

    function buildUrl(template, id) {
        return template.replace('__CONV_ID__', id);
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    function showErrors(container, errors, fallback) {
        var message = fallback || 'Ocurrió un error.';
        if (errors && typeof errors === 'object') {
            var messages = [];
            Object.keys(errors).forEach(function (key) {
                var val = errors[key];
                if (Array.isArray(val)) {
                    messages = messages.concat(val);
                } else {
                    messages.push(val);
                }
            });
            if (messages.length > 0) {
                message = messages.join('\n');
            }
        }
        if (container) {
            container.textContent = message;
            container.style.display = 'block';
        } else {
            alert(message);
        }
    }

    // ── Drag & drop entre columnas ───────────────────────────────

    var lists = board.querySelectorAll('.wf-column-list');

    lists.forEach(function (listEl) {
        new Sortable(listEl, {
            group: 'wa-funnel-kanban',
            animation: 150,
            draggable: '.wf-chat-card',
            ghostClass: 'sortable-ghost',
            dragClass: 'dragging',
            onEnd: function (evt) {
                var card = evt.item;
                var convId = card.getAttribute('data-conv-id');
                var toStageId = evt.to.getAttribute('data-stage-id');

                if (!convId || !toStageId) return;
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                fetch(buildUrl(moveStageUrlTemplate, convId), {
                    method: 'POST',
                    headers: jsonHeaders(),
                    body: JSON.stringify({ to_stage_id: toStageId }),
                })
                .then(function (response) {
                    if (response.status === 422) {
                        return response.json().then(function (data) {
                            var fromList = evt.from;
                            var referenceNode = fromList.children[evt.oldIndex] || null;
                            if (referenceNode) {
                                fromList.insertBefore(card, referenceNode);
                            } else {
                                fromList.appendChild(card);
                            }
                            showErrors(null, data && data.errors, 'No se pudo mover la conversación.');
                        });
                    }
                    if (!response.ok) {
                        throw new Error('Error inesperado al mover la conversación (status ' + response.status + ').');
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    alert('Ocurrió un error de red al mover la conversación.');
                });
            },
        });
    });

    // ── Filtros client-side ──────────────────────────────────────

    var searchInput = document.getElementById('wfSearchInput');
    var agentFilter  = document.getElementById('wfAgentFilter');
    var unreadFilter = document.getElementById('wfUnreadFilter');
    var staleFilter  = document.getElementById('wfStaleFilter');
    var applyBtn     = document.getElementById('wfApplyFiltersBtn');

    function applyFilters() {
        var search = (searchInput && searchInput.value || '').toLowerCase().trim();
        var agentId = agentFilter ? agentFilter.value : '';
        var unreadOnly = unreadFilter ? unreadFilter.checked : false;
        var staleOnly = staleFilter ? staleFilter.checked : false;
        var nowSec = Math.floor(Date.now() / 1000);

        board.querySelectorAll('.wf-chat-card').forEach(function (card) {
            var name = (card.getAttribute('data-name') || '').toLowerCase();
            var phone = (card.getAttribute('data-phone') || '').toLowerCase();
            var cardAgentId = card.getAttribute('data-agent-id') || '';
            var unread = parseInt(card.getAttribute('data-unread'), 10) || 0;
            var lastActivity = parseInt(card.getAttribute('data-last-activity'), 10) || 0;

            var visible = true;

            if (search && name.indexOf(search) === -1 && phone.indexOf(search) === -1) {
                visible = false;
            }
            if (agentId && cardAgentId !== agentId) {
                visible = false;
            }
            if (unreadOnly && unread <= 0) {
                visible = false;
            }
            if (staleOnly) {
                var hoursSince = lastActivity ? (nowSec - lastActivity) / 3600 : Infinity;
                if (hoursSince < 24) visible = false;
            }

            card.style.display = visible ? '' : 'none';
        });
    }

    if (applyBtn) applyBtn.addEventListener('click', applyFilters);
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (agentFilter) agentFilter.addEventListener('change', applyFilters);
    if (unreadFilter) unreadFilter.addEventListener('change', applyFilters);
    if (staleFilter) staleFilter.addEventListener('change', applyFilters);

    // ── Modal de chat ─────────────────────────────────────────────

    var chatModal = document.getElementById('wfChatModal');
    var chatThread = document.getElementById('wfChatThread');
    var chatModalName = document.getElementById('wfChatModalName');
    var chatModalPhone = document.getElementById('wfChatModalPhone');
    var chatModalAvatar = document.getElementById('wfChatModalAvatar');
    var chatWindowNotice = document.getElementById('wfChatWindowNotice');
    var chatTextRow = document.getElementById('wfChatTextRow');
    var chatTemplateRow = document.getElementById('wfChatTemplateRow');
    var chatTextInput = document.getElementById('wfChatTextInput');
    var chatTemplateSelect = document.getElementById('wfChatTemplateSelect');
    var sendTextBtn = document.getElementById('wfChatSendTextBtn');
    var sendTemplateBtn = document.getElementById('wfChatSendTemplateBtn');
    var panelPhone = document.getElementById('wfPanelPhone');
    var panelCustomer = document.getElementById('wfPanelCustomer');
    var panelAgentSelect = document.getElementById('wfPanelAgentSelect');
    var panelReassignBtn = document.getElementById('wfPanelReassignBtn');
    var panelDealLinked = document.getElementById('wfPanelDealLinked');
    var panelDealLink = document.getElementById('wfPanelDealLink');
    var panelCreateDealBtn = document.getElementById('wfPanelCreateDealBtn');
    var closeChatModalBtn = document.getElementById('wfCloseChatModal');

    var currentConversationId = null;

    function populateSelect(select, items, placeholder) {
        if (!select) return;
        select.innerHTML = '';
        if (placeholder) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            select.appendChild(opt);
        }
        items.forEach(function (item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
    }

    function renderThread(messages) {
        chatThread.innerHTML = '';
        if (!messages || messages.length === 0) {
            chatThread.innerHTML = '<p class="wf-chat-loading">Sin mensajes todavía.</p>';
            return;
        }
        messages.forEach(function (msg) {
            var bubble = document.createElement('div');
            var isOut = msg.sender_type !== 'contact';
            bubble.className = 'wf-bubble ' + (isOut ? 'wf-bubble-out' : 'wf-bubble-in') + (msg.is_template ? ' wf-bubble-template' : '');
            var text = document.createElement('span');
            text.textContent = msg.content || (msg.is_template ? ('Plantilla: ' + msg.template_name) : '(sin texto)');
            bubble.appendChild(text);
            var meta = document.createElement('span');
            meta.className = 'wf-bubble-meta';
            meta.textContent = msg.sent_at ? new Date(msg.sent_at).toLocaleString() : '';
            bubble.appendChild(meta);
            chatThread.appendChild(bubble);
        });
        chatThread.scrollTop = chatThread.scrollHeight;
    }

    function openChat(convId, cardEl) {
        // cardEl es opcional: cuando el modal se abre vía ?open_conversation=
        // (quick action "WhatsApp" de una tarjeta de Deal, Fase 14) puede no
        // haber una tarjeta renderizada en este tablero todavía si la
        // conversación es nueva — el nombre/teléfono se rellenan entonces
        // desde la respuesta del servidor más abajo.
        currentConversationId = convId;
        chatModal.classList.add('active');
        chatModal.style.display = 'flex';
        if (cardEl) {
            chatModalName.textContent = cardEl.getAttribute('data-name') || '—';
            chatModalPhone.textContent = cardEl.getAttribute('data-phone') || '';
            chatModalAvatar.textContent = (cardEl.getAttribute('data-name') || '?').substring(0, 2).toUpperCase();
        } else {
            chatModalName.textContent = 'Cargando…';
            chatModalPhone.textContent = '';
            chatModalAvatar.textContent = '?';
        }
        chatThread.innerHTML = '<p class="wf-chat-loading">Cargando conversación...</p>';

        // Limpiar el badge de no leídos en la tarjeta (el servidor lo
        // resetea al cargar los mensajes; se refleja aquí de inmediato).
        if (cardEl) {
            var badge = cardEl.querySelector('.wf-unread-badge');
            if (badge) badge.remove();
            cardEl.setAttribute('data-unread', '0');
        }

        fetch(buildUrl(messagesUrlTemplate, convId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(function (r) { return r.json(); })
        .then(function (payload) {
            renderThread(payload.messages);

            if (!cardEl) {
                var conv = payload.conversation || {};
                var displayName = conv.customer
                    ? (conv.customer.first_name + ' ' + conv.customer.last_name).trim()
                    : (conv.contact_phone || 'Contacto');
                chatModalName.textContent = displayName || '—';
                chatModalPhone.textContent = conv.contact_phone || '';
                chatModalAvatar.textContent = (displayName || '?').substring(0, 2).toUpperCase();
            }

            panelPhone.textContent = payload.conversation.contact_phone || '—';
            panelCustomer.textContent = payload.conversation.customer
                ? (payload.conversation.customer.first_name + ' ' + payload.conversation.customer.last_name).trim()
                : 'Sin cliente vinculado';

            populateSelect(panelAgentSelect, data.agents, 'Sin asignar');
            panelAgentSelect.value = payload.conversation.assigned_user_id || '';

            if (payload.conversation.deal_id) {
                panelDealLinked.style.display = 'block';
                panelDealLink.href = '/admin/negocios/' + payload.conversation.deal_id;
                panelCreateDealBtn.disabled = true;
                panelCreateDealBtn.textContent = 'Negocio ya vinculado';
            } else {
                panelDealLinked.style.display = 'none';
                panelCreateDealBtn.disabled = false;
                panelCreateDealBtn.textContent = 'Crear negocio';
            }

            var templates = payload.templates || data.templates || [];
            populateSelect(chatTemplateSelect, templates.map(function (t) {
                return { id: t.name, name: t.label };
            }), null);

            if (payload.within_window) {
                chatWindowNotice.style.display = 'none';
                chatTextRow.style.display = 'flex';
                chatTemplateRow.style.display = 'none';
            } else {
                chatWindowNotice.style.display = 'block';
                chatTextRow.style.display = 'none';
                chatTemplateRow.style.display = 'flex';
            }
        })
        .catch(function (err) {
            console.error(err);
            chatThread.innerHTML = '<p class="wf-chat-loading">No se pudo cargar la conversación.</p>';
        });
    }

    function closeChat() {
        chatModal.classList.remove('active');
        chatModal.style.display = 'none';
        currentConversationId = null;
    }

    board.querySelectorAll('.wf-chat-card').forEach(function (card) {
        card.addEventListener('click', function (evt) {
            // No abrir el modal si el click vino de un drag (SortableJS
            // agrega la clase temporalmente durante el arrastre).
            if (card.classList.contains('sortable-ghost') || card.classList.contains('dragging')) return;
            openChat(card.getAttribute('data-conv-id'), card);
        });
    });

    if (closeChatModalBtn) closeChatModalBtn.addEventListener('click', closeChat);
    if (chatModal) {
        chatModal.addEventListener('click', function (evt) {
            if (evt.target === chatModal) closeChat();
        });
    }

    if (sendTextBtn) {
        sendTextBtn.addEventListener('click', function () {
            var text = (chatTextInput.value || '').trim();
            if (!text || !currentConversationId) return;

            sendTextBtn.disabled = true;
            fetch(buildUrl(sendMessageUrlTemplate, currentConversationId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ type: 'text', text: text }),
            })
            .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
            .then(function (res) {
                if (!res.ok) {
                    alert(res.body.message || 'No se pudo enviar el mensaje.');
                    return;
                }
                chatTextInput.value = '';
                openChat(currentConversationId, board.querySelector('.wf-chat-card[data-conv-id="' + currentConversationId + '"]'));
            })
            .catch(function (err) { console.error(err); alert('Error de red al enviar el mensaje.'); })
            .finally(function () { sendTextBtn.disabled = false; });
        });
    }

    if (sendTemplateBtn) {
        sendTemplateBtn.addEventListener('click', function () {
            var templateName = chatTemplateSelect.value;
            if (!templateName || !currentConversationId) return;

            sendTemplateBtn.disabled = true;
            fetch(buildUrl(sendMessageUrlTemplate, currentConversationId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ type: 'template', template_name: templateName }),
            })
            .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
            .then(function (res) {
                if (!res.ok) {
                    alert(res.body.message || 'No se pudo enviar la plantilla.');
                    return;
                }
                openChat(currentConversationId, board.querySelector('.wf-chat-card[data-conv-id="' + currentConversationId + '"]'));
            })
            .catch(function (err) { console.error(err); alert('Error de red al enviar la plantilla.'); })
            .finally(function () { sendTemplateBtn.disabled = false; });
        });
    }

    if (panelReassignBtn) {
        panelReassignBtn.addEventListener('click', function () {
            if (!currentConversationId) return;
            var agentId = panelAgentSelect.value || null;

            fetch(buildUrl(reassignAgentUrlTemplate, currentConversationId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ assigned_user_id: agentId }),
            })
            .then(function (r) { return r.json(); })
            .then(function (payload) {
                var card = board.querySelector('.wf-chat-card[data-conv-id="' + currentConversationId + '"]');
                if (card) card.setAttribute('data-agent-id', agentId || '');
                alert('Agente actualizado.');
            })
            .catch(function (err) { console.error(err); alert('Error de red al reasignar agente.'); });
        });
    }

    // ── Mini-modal "Crear negocio" ──────────────────────────────

    var createDealModal = document.getElementById('wfCreateDealModal');
    var createDealForm = document.getElementById('wfCreateDealForm');
    var createDealErrors = document.getElementById('wfCreateDealErrors');
    var dealName = document.getElementById('wfDealName');
    var dealPipelineSelect = document.getElementById('wfDealPipelineSelect');
    var dealAmount = document.getElementById('wfDealAmount');
    var closeCreateDealBtn = document.getElementById('wfCloseCreateDealModal');
    var cancelCreateDealBtn = document.getElementById('wfCancelCreateDealModal');

    function openCreateDeal() {
        if (!currentConversationId) return;
        createDealErrors.style.display = 'none';
        dealName.value = chatModalName.textContent || '';
        populateSelect(dealPipelineSelect, data.dealPipelines, null);
        createDealModal.classList.add('active');
        createDealModal.style.display = 'flex';
    }

    function closeCreateDeal() {
        createDealModal.classList.remove('active');
        createDealModal.style.display = 'none';
    }

    if (panelCreateDealBtn) panelCreateDealBtn.addEventListener('click', openCreateDeal);
    if (closeCreateDealBtn) closeCreateDealBtn.addEventListener('click', closeCreateDeal);
    if (cancelCreateDealBtn) cancelCreateDealBtn.addEventListener('click', closeCreateDeal);

    if (createDealForm) {
        createDealForm.addEventListener('submit', function (evt) {
            evt.preventDefault();
            if (!currentConversationId) return;

            fetch(buildUrl(createDealUrlTemplate, currentConversationId), {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    name: dealName.value,
                    pipeline_id: dealPipelineSelect.value,
                    amount: dealAmount.value || null,
                }),
            })
            .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, status: r.status, body: body }; }); })
            .then(function (res) {
                if (!res.ok) {
                    showErrors(createDealErrors, res.body.errors, res.body.message);
                    return;
                }
                if (res.body.redirect) {
                    window.location.href = res.body.redirect;
                }
            })
            .catch(function (err) { console.error(err); alert('Error de red al crear el negocio.'); });
        });
    }

    // ── "+ Nuevo chat" ────────────────────────────────────────────

    var newChatBtn = document.getElementById('wfNewChatBtn');
    var newChatModal = document.getElementById('wfNewChatModal');
    var newChatForm = document.getElementById('wfNewChatForm');
    var newChatErrors = document.getElementById('wfNewChatErrors');
    var newChatAccount = document.getElementById('wfNewChatAccount');
    var newChatStage = document.getElementById('wfNewChatStage');
    var newChatAgent = document.getElementById('wfNewChatAgent');
    var newChatTemplate = document.getElementById('wfNewChatTemplate');
    var newChatTemplateParams = document.getElementById('wfNewChatTemplateParams');
    var closeNewChatBtn = document.getElementById('wfCloseNewChatModal');
    var cancelNewChatBtn = document.getElementById('wfCancelNewChatModal');

    function renderTemplateParams() {
        newChatTemplateParams.innerHTML = '';
        var tpl = data.templates.find(function (t) { return t.name === newChatTemplate.value; });
        if (!tpl || !tpl.params) return;
        tpl.params.forEach(function (label, idx) {
            var group = document.createElement('div');
            group.className = 'ap-field-group';
            var lbl = document.createElement('label');
            lbl.className = 'supliers-manager-slider-label';
            lbl.textContent = label;
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'users-manager-input wf-template-param';
            input.dataset.paramIndex = idx;
            group.appendChild(lbl);
            group.appendChild(input);
            newChatTemplateParams.appendChild(group);
        });
    }

    function openNewChat() {
        newChatErrors.style.display = 'none';
        newChatForm.reset();
        populateSelect(newChatAccount, data.accounts, null);
        populateSelect(newChatStage, data.stages, null);
        populateSelect(newChatAgent, data.agents, 'Sin asignar');
        populateSelect(newChatTemplate, data.templates.map(function (t) {
            return { id: t.name, name: t.label };
        }), null);
        renderTemplateParams();
        newChatModal.classList.add('active');
        newChatModal.style.display = 'flex';
    }

    function closeNewChat() {
        newChatModal.classList.remove('active');
        newChatModal.style.display = 'none';
    }

    if (newChatBtn) newChatBtn.addEventListener('click', openNewChat);
    if (closeNewChatBtn) closeNewChatBtn.addEventListener('click', closeNewChat);
    if (cancelNewChatBtn) cancelNewChatBtn.addEventListener('click', closeNewChat);
    if (newChatTemplate) newChatTemplate.addEventListener('change', renderTemplateParams);
    if (newChatModal) {
        newChatModal.addEventListener('click', function (evt) {
            if (evt.target === newChatModal) closeNewChat();
        });
    }

    if (newChatForm) {
        newChatForm.addEventListener('submit', function (evt) {
            evt.preventDefault();

            var formData = new FormData(newChatForm);
            var params = Array.prototype.slice.call(document.querySelectorAll('.wf-template-param'))
                .sort(function (a, b) { return a.dataset.paramIndex - b.dataset.paramIndex; })
                .map(function (input) { return input.value; });

            fetch(newChatUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({
                    name: formData.get('name'),
                    company: formData.get('company'),
                    contact_phone: formData.get('contact_phone'),
                    account_id: formData.get('account_id'),
                    pipeline_stage_id: formData.get('pipeline_stage_id'),
                    assigned_user_id: formData.get('assigned_user_id') || null,
                    template_name: formData.get('template_name'),
                    params: params,
                }),
            })
            .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
            .then(function (res) {
                if (!res.ok) {
                    showErrors(newChatErrors, res.body.errors, res.body.message);
                    return;
                }
                window.location.reload();
            })
            .catch(function (err) { console.error(err); alert('Error de red al iniciar el chat.'); });
        });
    }

    // ── Auto-abrir el modal de chat vía ?open_conversation=ID ──────
    // Usado por el quick action "WhatsApp" de la tarjeta de Deal en
    // Negocios (Fase 14): ese botón busca/crea la conversación en el
    // backend y redirige aquí con este parámetro (más ?pipeline_id= para
    // que el kanban cargue el embudo correcto) — este mismo modal ya
    // existente se abre sobre ella, sin duplicar ningún componente.
    (function autoOpenFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var openConvId = params.get('open_conversation');
        if (!openConvId || !chatModal) return;

        var existingCard = board.querySelector('.wf-chat-card[data-conv-id="' + openConvId + '"]');
        openChat(openConvId, existingCard);

        // Limpiar el parámetro de la URL para que un refresh posterior no
        // vuelva a abrir el modal automáticamente.
        if (window.history && window.history.replaceState) {
            params.delete('open_conversation');
            var qs = params.toString();
            var newUrl = window.location.pathname + (qs ? '?' + qs : '');
            window.history.replaceState({}, '', newUrl);
        }
    })();
})();
