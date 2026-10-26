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
    var moveLeadUrlTemplate     = board.dataset.moveLeadUrlTemplate || '';
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
            draggable: '.wf2-card',
            filter: '[data-wf-quick]',
            preventOnFilter: false,
            ghostClass: 'sortable-ghost',
            dragClass: 'dragging',
            onEnd: function (evt) {
                var card = evt.item;
                var leadId = card.getAttribute('data-lead-id');
                var convId = leadId || card.getAttribute('data-conv-id');
                var toStageId = evt.to.getAttribute('data-stage-id');

                if (!convId || !toStageId) return;
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

                // Tarjeta de lead (correo, formulario, llamada): se mueve con su propia ruta.
                fetch(buildUrl(leadId ? moveLeadUrlTemplate : moveStageUrlTemplate, convId), {
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

    var searchInput   = document.getElementById('wfSearchInput');
    var agentFilter   = document.getElementById('wfAgentFilter');
    var channelFilter = document.getElementById('wfChannelFilter');
    var sortSelect    = document.getElementById('wfSort');
    var chipBtns      = document.querySelectorAll('[data-chip]');
    var activeChip    = 'todos';

    function norm(str) {
        return String(str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }

    // Chips rapidos: Todos / Sin leer / Sin responder / Ventana abierta / Tareas vencidas / Prioridad alta.
    function chipMatches(item) {
        switch (activeChip) {
            case 'unread': return (parseInt(item.getAttribute('data-unread'), 10) || 0) > 0;
            case 'stale':  return item.getAttribute('data-stale') === '1';
            case 'window': return item.getAttribute('data-win') === '1';
            case 'due':    return item.getAttribute('data-due') === '1';
            case 'prio':   return item.getAttribute('data-prio') === '1';
            default:       return true;
        }
    }

    function applyFilters() {
        var words = norm(searchInput && searchInput.value).split(/\s+/).filter(Boolean);
        var agentId = agentFilter ? agentFilter.value : '';
        var channel = channelFilter ? channelFilter.value : '';

        var rowsShown = 0;
        document.querySelectorAll('[data-wf-item]').forEach(function (item) {
            var hay = norm((item.getAttribute('data-name') || '') + ' ' + (item.getAttribute('data-phone') || ''));
            var visible = words.every(function (w) { return hay.indexOf(w) !== -1; });
            if (agentId && (item.getAttribute('data-agent-id') || '') !== agentId) visible = false;
            if (channel && item.getAttribute('data-channel') !== channel) visible = false;
            if (!chipMatches(item)) visible = false;

            item.style.display = visible ? '' : 'none';
            if (visible && item.classList.contains('wf2-row')) rowsShown++;
        });

        // Contador y mensaje vacio de cada columna segun lo que queda visible.
        board.querySelectorAll('.wf2-col').forEach(function (col) {
            var shown = [].filter.call(col.querySelectorAll('.wf2-card'), function (c) { return c.style.display !== 'none'; }).length;
            var counter = col.querySelector('[data-column-count]');
            if (counter) counter.textContent = shown;
            var empty = col.querySelector('[data-wf-empty]');
            if (empty) empty.hidden = shown > 0;
        });

        var none = document.querySelector('[data-wf-none]');
        if (none) none.hidden = rowsShown > 0;
    }

    // Orden de las tarjetas dentro de cada columna: recientes, mayor valor o mas tiempo sin respuesta.
    function sortCards() {
        var mode = sortSelect ? sortSelect.value : 'reciente';
        board.querySelectorAll('.wf2-col-list').forEach(function (list) {
            var cards = [].slice.call(list.querySelectorAll('.wf2-card'));
            cards.sort(function (a, b) {
                var la = parseInt(a.getAttribute('data-last-activity'), 10) || 0;
                var lb = parseInt(b.getAttribute('data-last-activity'), 10) || 0;
                if (mode === 'valor') return (parseFloat(b.getAttribute('data-value')) || 0) - (parseFloat(a.getAttribute('data-value')) || 0) || lb - la;
                if (mode === 'espera') return la - lb;
                return lb - la;
            });
            var empty = list.querySelector('[data-wf-empty]');
            cards.forEach(function (c) { list.insertBefore(c, empty); });
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    [agentFilter, channelFilter].forEach(function (el) { if (el) el.addEventListener('change', applyFilters); });
    if (sortSelect) sortSelect.addEventListener('change', sortCards);
    chipBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeChip = btn.getAttribute('data-chip');
            chipBtns.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-on', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            applyFilters();
        });
    });

    // ── Vistas (Tablero / Lista) ──────────────────────────────────

    var VIEW_KEY = 'wf-view';
    var viewBtns = document.querySelectorAll('[data-view-btn]');
    var setView = function (name) {
        viewBtns.forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-view-btn') === name); });
        document.querySelectorAll('.wf2-viewbox').forEach(function (v) { v.classList.toggle('is-on', v.getAttribute('data-view') === name); });
        try { window.localStorage.setItem(VIEW_KEY, name); } catch (e) { /* sin localStorage */ }
    };
    viewBtns.forEach(function (b) { b.addEventListener('click', function () { setView(b.getAttribute('data-view-btn')); }); });
    try {
        var savedView = window.localStorage.getItem(VIEW_KEY);
        if (savedView && document.querySelector('.wf2-viewbox[data-view="' + savedView + '"]')) setView(savedView);
    } catch (e) { /* sin localStorage */ }

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
            var badge = cardEl.querySelector('[data-wf-unread]');
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

    // ── Ficha del lead en modal (la Bandeja de leads incrustada) ──

    var leadModal = document.getElementById('wfLeadModal');
    var leadFrame = document.getElementById('wfLeadFrame');
    var leadFull = document.getElementById('wfLeadFull');
    var leadLoads = 0;

    function openLeadModal(url) {
        if (!leadModal || !leadFrame) { window.location.href = url; return; }
        leadLoads = 0;
        leadFrame.src = url + (url.indexOf('?') === -1 ? '?' : '&') + 'embed=1';
        if (leadFull) leadFull.href = url;
        leadModal.hidden = false;
        document.body.style.overflow = 'hidden';
        var closeBtn = document.getElementById('wfLeadClose');
        if (closeBtn) closeBtn.focus();
    }

    function closeLeadModal() {
        if (!leadModal || leadModal.hidden) return;
        leadModal.hidden = true;
        document.body.style.overflow = '';
        var changed = leadLoads > 1; // si dentro se guardó algo (la página recargó), el tablero se actualiza
        leadFrame.src = 'about:blank';
        if (changed) window.location.reload();
    }

    if (leadFrame) leadFrame.addEventListener('load', function () { if (leadFrame.getAttribute('src') !== 'about:blank') leadLoads++; });
    var leadCloseBtn = document.getElementById('wfLeadClose');
    if (leadCloseBtn) leadCloseBtn.addEventListener('click', closeLeadModal);
    if (leadModal) leadModal.addEventListener('mousedown', function (e) { if (e.target === leadModal) closeLeadModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeLeadModal(); });

    document.querySelectorAll('[data-wf-item]').forEach(function (card) {
        card.addEventListener('click', function (evt) {
            // No abrir nada si el click vino de un drag (SortableJS agrega la clase durante el arrastre).
            if (card.classList.contains('sortable-ghost') || card.classList.contains('dragging')) return;
            var openUrl = card.getAttribute('data-open-url');
            if (openUrl) {
                openLeadModal(openUrl);
                return;
            }
            // Conversación sin lead ligado: modal de chat.
            if (card.getAttribute('data-conv-id')) openChat(card.getAttribute('data-conv-id'), card);
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
                openChat(currentConversationId, board.querySelector('.wf2-card[data-conv-id="' + currentConversationId + '"]'));
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
                openChat(currentConversationId, board.querySelector('.wf2-card[data-conv-id="' + currentConversationId + '"]'));
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
                var card = board.querySelector('.wf2-card[data-conv-id="' + currentConversationId + '"]');
                if (card) {
                    card.setAttribute('data-agent-id', agentId || '');
                    var label = card.querySelector('[data-wf-agent]');
                    var agent = (data.agents || []).filter(function (x) { return String(x.id) === String(agentId); })[0];
                    if (label) {
                        label.title = agent ? agent.name : 'Sin asignar';
                        label.textContent = agent ? agent.name.split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0); }).join('').toUpperCase() : '–';
                    }
                }
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

        var existingCard = board.querySelector('.wf2-card[data-conv-id="' + openConvId + '"]');
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
