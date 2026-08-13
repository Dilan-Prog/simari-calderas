/* ============================================================
   deals-board.js — Rediseño de Negocios (Deals), Fase 14
   Vanilla JS (IIFE) + SortableJS. Sin React, sin jQuery.

   Arquitectura: el Blade (resources/views/admin/deals/index.blade.php)
   embebe TODO el dataset del pipeline activo en un <script type=
   "application/json" id="deals-boot-data">, generado por PHP en un
   solo request. Este módulo parsea ese JSON una vez y renderiza las
   4 vistas (Kanban/Lista/Tabla/Forecast) completamente en el cliente:
   cambiar de vista, buscar, filtrar y ordenar NUNCA dispara un nuevo
   request al servidor (mismo patrón que workflows-index.js).

   Los únicos roundtrips al servidor que este módulo hace son los
   explícitamente contratados con el backend paralelo de esta fase:
     - GET  {urls.dealDetailTemplate}   (Accept: application/json) → drawer
     - POST {urls.bulkAction}                                       → acción masiva
     - GET  {urls.export}                                           → exportar (descarga de archivo)
     - POST {urls.moveStageTemplate}                                → mover etapa (kanban / ganar-perder)
     - GET/POST/PUT/DELETE {urls.tagsBase}[/{id}]                   → CRUD de etiquetas
   ============================================================ */
import Sortable from 'sortablejs';

(function () {
    'use strict';

    var root = document.getElementById('deals-hub-root');
    if (!root) return;

    var bootDataEl = document.getElementById('deals-boot-data');
    if (!bootDataEl) return;

    var BOOT;
    try {
        BOOT = JSON.parse(bootDataEl.textContent || '{}');
    } catch (e) {
        console.error('deals-board.js: JSON de arranque inválido', e);
        return;
    }

    var STAGES  = BOOT.stages || [];
    var OWNERS  = BOOT.owners || [];
    var TAGS    = BOOT.tags || [];
    var DEALS   = BOOT.deals || [];
    var URLS    = BOOT.urls || {};
    var CSRF    = BOOT.csrf || '';
    var STAGE_MAP = {};
    STAGES.forEach(function (s) { STAGE_MAP[s.id] = s; });

    // ── Estado mutable de UI (todo client-side) ─────────────
    var state = {
        view: 'kanban',
        search: '',
        ownerId: '',
        tagId: '',
        dateFrom: '',
        dateTo: '',
        minValue: '',
        maxValue: '',
        sort: 'recent',
        selected: {}, // { dealId: true }
    };

    // ============================================================
    // Tema (claro/oscuro) — mismo mecanismo que WorkflowCanvasApp.jsx
    // pero en vanilla JS: data-theme en el contenedor raíz + localStorage.
    // ============================================================
    var THEME_KEY = 'deals-hub-theme';
    (function initTheme() {
        var saved = 'light';
        try {
            saved = window.localStorage.getItem(THEME_KEY) || 'light';
        } catch (e) { /* localStorage no disponible, no-op */ }
        root.setAttribute('data-theme', saved);
    })();

    var themeToggleBtn = document.getElementById('dhThemeToggle');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { window.localStorage.setItem(THEME_KEY, next); } catch (e) { /* no-op */ }
        });
    }

    // ============================================================
    // Toasts
    // ============================================================
    var toastStack = document.getElementById('dhToastStack');
    function showToast(message, type) {
        if (!toastStack) { window.alert(message); return; }
        var el = document.createElement('div');
        el.className = 'dh-toast dh-toast-' + (type || 'info');
        el.textContent = message;
        toastStack.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .25s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 260);
        }, 3400);
    }

    // ============================================================
    // Helpers de formato
    // ============================================================
    function money(amount, currency) {
        if (amount === null || amount === undefined) return '—';
        var n = parseFloat(amount) || 0;
        return (currency || 'MXN') + ' $' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function shortDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso + (iso.length <= 10 ? 'T00:00:00' : ''));
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function initials(name) {
        if (!name) return '?';
        var parts = name.trim().split(/\s+/);
        var a = parts[0] ? parts[0][0] : '';
        var b = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (a + b).toUpperCase() || '?';
    }

    function daysSince(iso) {
        if (!iso) return null;
        var d = new Date(iso);
        if (isNaN(d.getTime())) return null;
        return Math.floor((Date.now() - d.getTime()) / 86400000);
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function csrfHeaders(extra) {
        var h = {
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };
        if (extra) Object.keys(extra).forEach(function (k) { h[k] = extra[k]; });
        return h;
    }

    // ============================================================
    // Salud / tendencia (heurística client-side)
    //
    // No hay un endpoint de velocidad por-deal en el contrato de esta
    // fase (DealReportService opera a nivel pipeline, no se serializó
    // deal-por-deal en el boot payload) — se aproxima "salud" con los
    // días transcurridos desde updated_at (proxy de "sin movimiento
    // reciente") y "tendencia" con la edad de creación del deal dentro
    // de la etapa. Es una señal indicativa, no el cálculo exacto de
    // DealReportService::pipelineVelocity().
    // ============================================================
    function dealHealth(deal) {
        if (deal.status !== 'open') return 'neutral';
        var d = daysSince(deal.updated_at);
        if (d === null) return 'good';
        if (d >= 21) return 'bad';
        if (d >= 10) return 'warn';
        return 'good';
    }

    function dealIsStalled(deal) {
        return dealHealth(deal) === 'bad';
    }

    // ============================================================
    // Filtrado (aplica a TODOS los deals; cada vista decide qué
    // subconjunto de status mostrar por default — kanban/lista muestran
    // abiertos, tabla/forecast pueden incluir cerrados).
    // ============================================================
    function matchesFilters(deal) {
        if (state.search) {
            var haystack = ((deal.name || '') + ' ' + (deal.folio || '') + ' ' + (deal.customer_name || '') + ' ' + (deal.company || '')).toLowerCase();
            if (haystack.indexOf(state.search) === -1) return false;
        }
        if (state.ownerId && String(deal.owner_id) !== String(state.ownerId)) return false;
        if (state.tagId) {
            var hasTag = (deal.tags || []).some(function (t) { return String(t.id) === String(state.tagId); });
            if (!hasTag) return false;
        }
        if (state.dateFrom && (!deal.expected_close_date || deal.expected_close_date < state.dateFrom)) return false;
        if (state.dateTo && (!deal.expected_close_date || deal.expected_close_date > state.dateTo)) return false;
        if (state.minValue && (parseFloat(deal.amount) || 0) < parseFloat(state.minValue)) return false;
        if (state.maxValue && (parseFloat(deal.amount) || 0) > parseFloat(state.maxValue)) return false;
        return true;
    }

    function filteredDeals(includeClosed) {
        return DEALS.filter(function (d) {
            if (!includeClosed && d.status !== 'open') return false;
            return matchesFilters(d);
        });
    }

    // ============================================================
    // Métricas resumen (barra superior)
    // ============================================================
    function renderMetrics() {
        var openDeals = filteredDeals(false);
        var allFiltered = filteredDeals(true);
        var won = allFiltered.filter(function (d) { return d.status === 'won'; });
        var lost = allFiltered.filter(function (d) { return d.status === 'lost'; });
        var closedCount = won.length + lost.length;

        var totalOpen = openDeals.reduce(function (s, d) { return s + (parseFloat(d.amount) || 0); }, 0);
        var weighted = openDeals.reduce(function (s, d) { return s + (parseFloat(d.amount) || 0) * ((d.probability || 0) / 100); }, 0);
        var avgSize = openDeals.length ? totalOpen / openDeals.length : 0;
        var winRate = closedCount ? (won.length / closedCount) * 100 : 0;
        var stalled = openDeals.filter(dealIsStalled).length;

        setText('dhMetricOpenCount', openDeals.length);
        setText('dhMetricOpenValue', money(totalOpen, 'MXN'));
        setText('dhMetricWeighted', money(weighted, 'MXN'));
        setText('dhMetricAvgSize', money(avgSize, 'MXN'));
        setText('dhMetricWinRate', winRate.toFixed(1) + '%');
        var stalledEl = document.getElementById('dhMetricStalled');
        if (stalledEl) {
            stalledEl.textContent = stalled;
            stalledEl.classList.toggle('dh-text-err', stalled > 0);
        }
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    // ============================================================
    // Selección múltiple + barra flotante de acciones masivas
    // ============================================================
    function toggleSelect(dealId, checked) {
        if (checked) state.selected[dealId] = true;
        else delete state.selected[dealId];
        syncSelectionUI();
    }

    function clearSelection() {
        state.selected = {};
        syncSelectionUI();
    }

    function selectedIds() {
        return Object.keys(state.selected).map(function (id) { return parseInt(id, 10); });
    }

    function syncSelectionUI() {
        var ids = selectedIds();
        var count = ids.length;

        document.querySelectorAll('[data-dh-select]').forEach(function (cb) {
            cb.checked = !!state.selected[cb.getAttribute('data-dh-select')];
        });
        document.querySelectorAll('[data-deal-id]').forEach(function (card) {
            var id = card.getAttribute('data-deal-id');
            card.classList.toggle('dh-card-selected', !!state.selected[id]);
        });

        var bar = document.getElementById('dhBulkBar');
        if (bar) bar.classList.toggle('dh-bulk-bar-visible', count > 0);
        setText('dhBulkCount', count + ' seleccionado' + (count === 1 ? '' : 's'));
    }

    document.addEventListener('change', function (e) {
        var cb = e.target;
        if (!cb.matches || !cb.matches('[data-dh-select]')) return;
        toggleSelect(cb.getAttribute('data-dh-select'), cb.checked);
    });

    document.addEventListener('click', function (e) {
        var card = e.target.closest ? e.target.closest('.dh-card-check-wrap') : null;
        if (card) { e.stopPropagation(); }
    });

    var bulkClear = document.getElementById('dhBulkClear');
    if (bulkClear) bulkClear.addEventListener('click', clearSelection);

    var bulkDelete = document.getElementById('dhBulkDelete');
    if (bulkDelete) {
        bulkDelete.addEventListener('click', function () {
            var ids = selectedIds();
            if (!ids.length) return;
            if (!window.confirm('¿Eliminar ' + ids.length + ' negocio(s)? Esta acción no se puede deshacer.')) return;
            runBulkAction('delete', {});
        });
    }

    var bulkStageSelect = document.getElementById('dhBulkStageSelect');
    var bulkStageBtn = document.getElementById('dhBulkStageApply');
    if (bulkStageBtn) {
        bulkStageBtn.addEventListener('click', function () {
            var ids = selectedIds();
            var stageId = bulkStageSelect ? bulkStageSelect.value : '';
            if (!ids.length || !stageId) return;
            runBulkAction('move_stage', { stage_id: stageId });
        });
    }

    var bulkOwnerSelect = document.getElementById('dhBulkOwnerSelect');
    var bulkOwnerBtn = document.getElementById('dhBulkOwnerApply');
    if (bulkOwnerBtn) {
        bulkOwnerBtn.addEventListener('click', function () {
            var ids = selectedIds();
            var ownerId = bulkOwnerSelect ? bulkOwnerSelect.value : '';
            if (!ids.length || !ownerId) return;
            runBulkAction('assign_owner', { owner_id: ownerId });
        });
    }

    function runBulkAction(action, extra) {
        var ids = selectedIds();
        if (!ids.length || !URLS.bulkAction) return;

        var payload = Object.assign({ ids: ids, action: action }, extra || {});

        fetch(URLS.bulkAction, {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(payload),
        })
            .then(function (response) {
                if (!response.ok) throw new Error('bulk action failed: ' + response.status);
                return response.json().catch(function () { return {}; });
            })
            .then(function () {
                showToast('Acción aplicada a ' + ids.length + ' negocio(s). Recargando…', 'success');
                setTimeout(function () { window.location.reload(); }, 700);
            })
            .catch(function (err) {
                console.error(err);
                showToast('No se pudo completar la acción masiva.', 'error');
            });
    }

    // ============================================================
    // Exportar
    // ============================================================
    var exportBtn = document.getElementById('dhExportBtn');
    if (exportBtn && URLS.export) {
        exportBtn.addEventListener('click', function () {
            var ids = selectedIds();
            var url = URLS.export;
            var params = [];
            if (BOOT.currentPipelineId) params.push('pipeline_id=' + encodeURIComponent(BOOT.currentPipelineId));
            if (ids.length) params.push('ids=' + ids.join(','));
            if (params.length) url += (url.indexOf('?') === -1 ? '?' : '&') + params.join('&');
            window.location.href = url;
        });
    }

    // ============================================================
    // RENDER — Kanban
    // ============================================================
    var kanbanEl = document.getElementById('dhKanban');

    function tagChipHtml(tag) {
        var color = tag.color || '#6b7280';
        return '<span class="dh-tag-chip" style="background:' + color + '22;color:' + color + ';">' + escapeHtml(tag.name) + '</span>';
    }

    function trendIcon(deal) {
        var age = daysSince(deal.created_at);
        if (age === null) return '<span class="dh-card-trend dh-trend-flat">–</span>';
        if (age <= 7) return '<span class="dh-card-trend dh-trend-up">&#9650; nuevo</span>';
        if (age >= 21) return '<span class="dh-card-trend dh-trend-down">&#9660; frío</span>';
        return '<span class="dh-card-trend dh-trend-flat">&#8226; activo</span>';
    }

    function cardHtml(deal) {
        var health = dealHealth(deal);
        var staleDot = health === 'bad' ? '<span class="dh-card-stale-dot" title="Sin movimiento reciente"></span>' : '';
        var tagsHtml = (deal.tags || []).map(tagChipHtml).join('');
        var checked = state.selected[deal.id] ? 'checked' : '';

        return '' +
            '<div class="dh-card" data-deal-id="' + deal.id + '" data-amount="' + (deal.amount || 0) + '">' +
                '<div class="dh-card-top">' +
                    '<div class="dh-card-check-wrap">' +
                        '<input type="checkbox" class="dh-card-check" data-dh-select="' + deal.id + '" ' + checked + '>' +
                    '</div>' +
                    '<span class="dh-card-folio">' + staleDot + escapeHtml(deal.folio) + '</span>' +
                    (deal.owner_name ? '<span class="dh-card-owner-avatar" title="' + escapeHtml(deal.owner_name) + '">' + initials(deal.owner_name) + '</span>' : '') +
                '</div>' +
                '<div class="dh-card-name">' + escapeHtml(deal.name) + '</div>' +
                '<div class="dh-card-customer">' + escapeHtml(deal.customer_name || deal.company || 'Sin cliente asignado') + '</div>' +
                (tagsHtml ? '<div class="dh-card-tags">' + tagsHtml + '</div>' : '') +
                '<div class="dh-card-bottom">' +
                    '<span class="dh-card-amount">' + money(deal.amount, deal.currency) + '</span>' +
                    trendIcon(deal) +
                '</div>' +
                '<div class="dh-card-quick-actions">' +
                    '<button type="button" class="dh-quick-btn" data-quick="call" title="Llamar">&#9742;</button>' +
                    '<button type="button" class="dh-quick-btn" data-quick="email" title="Correo">&#9993;</button>' +
                    '<button type="button" class="dh-quick-btn" data-quick="schedule" title="Agendar">&#128197;</button>' +
                    '<button type="button" class="dh-quick-btn" data-quick="whatsapp" title="WhatsApp">&#128172;</button>' +
                '</div>' +
            '</div>';
    }

    function stageHealthLabel(stageDeals) {
        var stalled = stageDeals.filter(dealIsStalled).length;
        if (stageDeals.length === 0) return { cls: 'dh-health-good', label: 'OK' };
        var ratio = stalled / stageDeals.length;
        if (ratio >= 0.5) return { cls: 'dh-health-bad', label: 'En riesgo' };
        if (ratio > 0) return { cls: 'dh-health-warn', label: 'Atención' };
        return { cls: 'dh-health-good', label: 'Saludable' };
    }

    function renderKanban() {
        if (!kanbanEl) return;
        var deals = filteredDeals(false);

        kanbanEl.innerHTML = STAGES.map(function (stage) {
            var stageDeals = deals.filter(function (d) { return d.stage_id === stage.id; });
            var total = stageDeals.reduce(function (s, d) { return s + (parseFloat(d.amount) || 0); }, 0);
            var health = stageHealthLabel(stageDeals);
            var wipLimit = stage.wip_limit;
            var wipExceeded = wipLimit && stageDeals.length > wipLimit;

            return '' +
                '<div class="dh-column" data-stage-id="' + stage.id + '">' +
                    '<div class="dh-column-header">' +
                        '<div class="dh-column-title-row">' +
                            '<span class="dh-column-title">' + escapeHtml(stage.name) + '</span>' +
                            '<span class="dh-column-probability" contenteditable="true" data-stage-probability="' + stage.id + '">' + (stage.probability || 0) + '%</span>' +
                        '</div>' +
                        '<div class="dh-column-stats">' +
                            '<span>' + stageDeals.length + ' negocio(s)</span>' +
                            '<span>' + money(total, 'MXN') + '</span>' +
                        '</div>' +
                        '<div class="dh-column-wip' + (wipExceeded ? ' dh-wip-exceeded' : '') + '">' +
                            (wipLimit ? ('WIP: ' + stageDeals.length + '/' + wipLimit + (wipExceeded ? ' — límite excedido' : '')) : 'Sin límite WIP') +
                            ' <span class="dh-column-health ' + health.cls + '">' + health.label + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dh-column-list" data-stage-id="' + stage.id + '">' +
                        (stageDeals.length
                            ? stageDeals.map(cardHtml).join('')
                            : '<div class="dh-column-empty">Sin negocios en esta etapa</div>') +
                    '</div>' +
                '</div>';
        }).join('');

        initSortable();
    }

    function initSortable() {
        var lists = kanbanEl ? kanbanEl.querySelectorAll('.dh-column-list') : [];
        lists.forEach(function (listEl) {
            new Sortable(listEl, {
                group: 'deals-hub-kanban',
                animation: 150,
                draggable: '.dh-card',
                ghostClass: 'sortable-ghost',
                dragClass: 'dragging',
                filter: '.dh-card-check, .dh-quick-btn',
                preventOnFilter: false,
                onEnd: handleCardDrop,
            });
        });
    }

    function handleCardDrop(evt) {
        var card = evt.item;
        var dealId = card.getAttribute('data-deal-id');
        var toStageId = evt.to.getAttribute('data-stage-id');
        if (!dealId || !toStageId) return;
        if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return;

        moveDealStage(dealId, toStageId, function (ok) {
            if (!ok) {
                // Revertir visualmente
                var fromList = evt.from;
                var ref = fromList.children[evt.oldIndex] || null;
                if (ref) fromList.insertBefore(card, ref);
                else fromList.appendChild(card);
            }
            renderKanban();
        });
    }

    function moveDealStage(dealId, toStageId, cb) {
        var url = (URLS.moveStageTemplate || '/admin/negocios/__DEAL_ID__/mover-etapa').replace('__DEAL_ID__', dealId);

        fetch(url, {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ to_stage_id: toStageId }),
        })
            .then(function (response) {
                if (response.status === 422) {
                    return response.json().then(function (data) {
                        var msg = 'No se pudo mover el negocio.';
                        if (data && data.errors) {
                            msg = Object.values(data.errors).reduce(function (a, b) { return a.concat(b); }, []).join('\n');
                        }
                        showToast(msg, 'error');
                        if (cb) cb(false);
                    });
                }
                if (!response.ok) throw new Error('move-stage failed: ' + response.status);
                return response.json().catch(function () { return {}; }).then(function () {
                    var deal = DEALS.filter(function (d) { return String(d.id) === String(dealId); })[0];
                    if (deal) {
                        deal.stage_id = parseInt(toStageId, 10);
                        var stage = STAGE_MAP[toStageId];
                        deal.stage_name = stage ? stage.name : deal.stage_name;
                        deal.probability = stage ? stage.probability : deal.probability;
                        if (stage && stage.is_won) deal.status = 'won';
                        else if (stage && stage.is_lost) deal.status = 'lost';
                    }
                    showToast('Negocio movido correctamente.', 'success');
                    renderMetrics();
                    if (cb) cb(true);
                });
            })
            .catch(function (err) {
                console.error(err);
                showToast('Error de red al mover el negocio.', 'error');
                if (cb) cb(false);
            });
    }

    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('[data-stage-probability]')) return;
        var stageId = el.getAttribute('data-stage-probability');
        var val = parseInt((el.textContent || '').replace(/[^0-9]/g, ''), 10);
        if (isNaN(val)) val = 0;
        val = Math.max(0, Math.min(100, val));
        el.textContent = val + '%';
        var stage = STAGE_MAP[stageId];
        if (stage) stage.probability = val;
        // Nota: sin un endpoint de actualización de etapa contratado para
        // esta fase, el cambio se aplica solo al cálculo en vivo (forecast/
        // tarjetas) de esta sesión. Persistirlo requiere editarlo desde
        // el módulo de Pipelines.
        showToast('Probabilidad actualizada (visual). Edítala en Pipelines para que persista.', 'info');
        renderForecast();
    }, true);

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-quick]') : null;
        if (!btn) return;
        e.stopPropagation();
        var kind = btn.getAttribute('data-quick');
        var cardEl = btn.closest('[data-deal-id]');
        var dealId = cardEl ? cardEl.getAttribute('data-deal-id') : null;
        var deal = DEALS.filter(function (d) { return String(d.id) === String(dealId); })[0];
        if (kind === 'call') {
            showToast(deal && deal.contact_phone ? ('Llamando a ' + deal.contact_phone + '…') : 'Este negocio no tiene teléfono registrado.', deal && deal.contact_phone ? 'info' : 'error');
        } else if (kind === 'email') {
            if (deal && deal.contact_email) window.location.href = 'mailto:' + deal.contact_email;
            else showToast('Este negocio no tiene correo registrado.', 'error');
        } else if (kind === 'schedule') {
            showToast('Agendar seguimiento — próximamente.', 'info');
        } else if (kind === 'whatsapp') {
            openWhatsappForDeal(dealId, btn);
        }
    });

    /**
     * Quick action "WhatsApp": busca/crea la WhatsappConversation ligada a
     * este deal (vía su customer_id) en el backend del Embudo de Venta
     * (Fase 13) y redirige ahí — esa página abre su propio modal de chat ya
     * existente sobre la conversación resultante (?open_conversation=ID),
     * sin duplicar ningún componente de chat en Negocios.
     */
    function openWhatsappForDeal(dealId, btn) {
        if (!dealId || !URLS.whatsappFromDealTemplate) return;
        if (btn) btn.disabled = true;

        var url = URLS.whatsappFromDealTemplate.replace('__DEAL_ID__', dealId);

        fetch(url, {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        })
            .then(function (response) {
                return response.json().then(function (body) { return { ok: response.ok, body: body }; });
            })
            .then(function (res) {
                if (!res.ok) {
                    showToast((res.body && res.body.message) || 'No se pudo abrir la conversación de WhatsApp.', 'error');
                    return;
                }
                window.location.href = res.body.redirect;
            })
            .catch(function (err) {
                console.error(err);
                showToast('Error de red al abrir WhatsApp.', 'error');
            })
            .finally(function () {
                if (btn) btn.disabled = false;
            });
    }

    // ============================================================
    // RENDER — Lista (agrupada por etapa)
    // ============================================================
    var listEl = document.getElementById('dhListGroups');

    function listRowHtml(deal) {
        var badge = statusBadge(deal.status);
        var checked = state.selected[deal.id] ? 'checked' : '';
        return '' +
            '<div class="dh-list-row" data-deal-id="' + deal.id + '" data-open-drawer="' + deal.id + '">' +
                '<input type="checkbox" class="dh-card-check" data-dh-select="' + deal.id + '" ' + checked + ' onclick="event.stopPropagation()">' +
                '<span class="dh-list-folio">' + escapeHtml(deal.folio) + '</span>' +
                '<span class="dh-list-name">' + escapeHtml(deal.name) + '</span>' +
                '<span>' + escapeHtml(deal.customer_name || deal.company || '—') + '</span>' +
                '<span>' + escapeHtml(deal.owner_name || '—') + '</span>' +
                '<span>' + shortDate(deal.expected_close_date) + '</span>' +
                '<span class="dh-list-amount">' + money(deal.amount, deal.currency) + '</span>' +
                '<span>' + badge + '</span>' +
                '<span></span>' +
            '</div>';
    }

    function statusBadge(status) {
        var map = { open: ['dh-badge-open', 'Abierto'], won: ['dh-badge-won', 'Ganado'], lost: ['dh-badge-lost', 'Perdido'] };
        var m = map[status] || map.open;
        return '<span class="dh-badge ' + m[0] + '">' + m[1] + '</span>';
    }

    function renderList() {
        if (!listEl) return;
        var deals = filteredDeals(true);

        if (!deals.length) {
            listEl.innerHTML = '<div class="dh-empty-state">No hay negocios que coincidan con los filtros actuales.</div>';
            return;
        }

        listEl.innerHTML = STAGES.map(function (stage) {
            var stageDeals = deals.filter(function (d) { return d.stage_id === stage.id; });
            if (!stageDeals.length) return '';
            var total = stageDeals.reduce(function (s, d) { return s + (parseFloat(d.amount) || 0); }, 0);

            return '' +
                '<div class="dh-list-group">' +
                    '<div class="dh-list-group-header">' +
                        '<span>' + escapeHtml(stage.name) + '</span>' +
                        '<span class="dh-list-group-meta">' + stageDeals.length + ' negocio(s) · ' + money(total, 'MXN') + '</span>' +
                    '</div>' +
                    '<div class="dh-list-header-row">' +
                        '<span></span><span>Folio</span><span>Nombre</span><span>Cliente</span><span>Responsable</span><span>Cierre</span><span>Monto</span><span>Estado</span><span></span>' +
                    '</div>' +
                    stageDeals.map(listRowHtml).join('') +
                '</div>';
        }).join('') || '<div class="dh-empty-state">No hay negocios que coincidan con los filtros actuales.</div>';
    }

    // ============================================================
    // RENDER — Tabla plana
    // ============================================================
    var tableBodyEl = document.getElementById('dhTableBody');
    var tableSortKey = 'created_at';
    var tableSortDir = -1;

    function tableRowHtml(deal) {
        var checked = state.selected[deal.id] ? 'checked' : '';
        return '' +
            '<tr data-deal-id="' + deal.id + '" data-open-drawer="' + deal.id + '">' +
                '<td><input type="checkbox" class="dh-card-check" data-dh-select="' + deal.id + '" ' + checked + ' onclick="event.stopPropagation()"></td>' +
                '<td class="dh-td-folio">' + escapeHtml(deal.folio) + '</td>' +
                '<td class="dh-td-name">' + escapeHtml(deal.name) + '</td>' +
                '<td><span class="dh-stage-pill">' + escapeHtml(deal.stage_name || '—') + '</span></td>' +
                '<td class="dh-td-amount">' + money(deal.amount, deal.currency) + '</td>' +
                '<td>' + escapeHtml(deal.customer_name || deal.company || '—') + '</td>' +
                '<td>' + escapeHtml(deal.owner_name || '—') + '</td>' +
                '<td>' + statusBadge(deal.status) + '</td>' +
                '<td>' + shortDate(deal.expected_close_date) + '</td>' +
                '<td>' + shortDate(deal.created_at) + '</td>' +
            '</tr>';
    }

    function renderTable() {
        if (!tableBodyEl) return;
        var deals = filteredDeals(true).slice();

        deals.sort(function (a, b) {
            var va = a[tableSortKey], vb = b[tableSortKey];
            if (tableSortKey === 'amount') { va = parseFloat(va) || 0; vb = parseFloat(vb) || 0; }
            va = va === null || va === undefined ? '' : va;
            vb = vb === null || vb === undefined ? '' : vb;
            if (va < vb) return -1 * tableSortDir;
            if (va > vb) return 1 * tableSortDir;
            return 0;
        });

        tableBodyEl.innerHTML = deals.length
            ? deals.map(tableRowHtml).join('')
            : '<tr><td colspan="10"><div class="dh-empty-state">No hay negocios que coincidan con los filtros actuales.</div></td></tr>';
    }

    document.querySelectorAll('[data-sort-key]').forEach(function (th) {
        th.addEventListener('click', function () {
            var key = th.getAttribute('data-sort-key');
            if (tableSortKey === key) tableSortDir *= -1;
            else { tableSortKey = key; tableSortDir = -1; }
            renderTable();
        });
    });

    // ============================================================
    // RENDER — Forecast (embudo + pronóstico por fecha + rendimiento)
    // ============================================================
    var funnelEl = document.getElementById('dhFunnel');
    var forecastMonthsEl = document.getElementById('dhForecastMonths');
    var repTableBodyEl = document.getElementById('dhRepTableBody');

    function renderForecast() {
        var openDeals = filteredDeals(false);

        // Embudo de conversión: negocios abiertos por etapa (no won/lost).
        if (funnelEl) {
            var openStages = STAGES.filter(function (s) { return !s.is_won && !s.is_lost; });
            var maxCount = Math.max.apply(null, openStages.map(function (s) {
                return openDeals.filter(function (d) { return d.stage_id === s.id; }).length;
            }).concat([1]));

            funnelEl.innerHTML = openStages.map(function (stage) {
                var count = openDeals.filter(function (d) { return d.stage_id === stage.id; }).length;
                var pct = maxCount ? Math.max(6, Math.round((count / maxCount) * 100)) : 0;
                return '' +
                    '<div class="dh-funnel-row">' +
                        '<span class="dh-funnel-label">' + escapeHtml(stage.name) + '</span>' +
                        '<div class="dh-funnel-bar-wrap"><div class="dh-funnel-bar" style="width:' + pct + '%">' + count + '</div></div>' +
                        '<span class="dh-funnel-value">' + (stage.probability || 0) + '%</span>' +
                    '</div>';
            }).join('') || '<div class="dh-empty-state">Sin etapas abiertas configuradas.</div>';
        }

        // Pronóstico por mes de cierre esperado (ponderado por probabilidad
        // de la etapa actual de cada deal).
        if (forecastMonthsEl) {
            var byMonth = {};
            openDeals.forEach(function (d) {
                var period = d.expected_close_date ? d.expected_close_date.slice(0, 7) : 'sin_fecha';
                if (!byMonth[period]) byMonth[period] = { raw: 0, weighted: 0, count: 0 };
                var amount = parseFloat(d.amount) || 0;
                byMonth[period].raw += amount;
                byMonth[period].weighted += amount * ((d.probability || 0) / 100);
                byMonth[period].count += 1;
            });
            var periods = Object.keys(byMonth).sort();
            forecastMonthsEl.innerHTML = periods.length
                ? periods.map(function (p) {
                    var row = byMonth[p];
                    var label = p === 'sin_fecha' ? 'Sin fecha de cierre' : shortDate(p + '-01').replace(/^\d{2}\s/, '');
                    return '' +
                        '<div class="dh-forecast-month-row">' +
                            '<span class="dh-forecast-month-label">' + label + ' <span style="color:var(--faint);font-weight:500;">(' + row.count + ')</span></span>' +
                            '<span class="dh-forecast-month-value">' + money(row.weighted, 'MXN') + '<span class="dh-forecast-month-raw">/ ' + money(row.raw, 'MXN') + '</span></span>' +
                        '</div>';
                }).join('')
                : '<div class="dh-empty-state">Sin negocios abiertos para pronosticar.</div>';
        }

        // Rendimiento por ejecutivo.
        if (repTableBodyEl) {
            var byOwner = {};
            filteredDeals(true).forEach(function (d) {
                if (!d.owner_id) return;
                if (!byOwner[d.owner_id]) byOwner[d.owner_id] = { name: d.owner_name, open: 0, openWeighted: 0, won: 0, lost: 0 };
                var amount = parseFloat(d.amount) || 0;
                if (d.status === 'open') {
                    byOwner[d.owner_id].open += 1;
                    byOwner[d.owner_id].openWeighted += amount * ((d.probability || 0) / 100);
                } else if (d.status === 'won') {
                    byOwner[d.owner_id].won += 1;
                } else if (d.status === 'lost') {
                    byOwner[d.owner_id].lost += 1;
                }
            });

            var rows = Object.keys(byOwner).map(function (id) { return byOwner[id]; })
                .sort(function (a, b) { return b.openWeighted - a.openWeighted; });

            repTableBodyEl.innerHTML = rows.length
                ? rows.map(function (r) {
                    var closed = r.won + r.lost;
                    var winRate = closed ? Math.round((r.won / closed) * 100) : 0;
                    return '' +
                        '<tr>' +
                            '<td class="dh-rep-name"><span class="dh-card-owner-avatar">' + initials(r.name) + '</span>' + escapeHtml(r.name || 'Sin nombre') + '</td>' +
                            '<td>' + r.open + '</td>' +
                            '<td>' + money(r.openWeighted, 'MXN') + '</td>' +
                            '<td>' + r.won + '</td>' +
                            '<td><span class="dh-rep-winrate-bar"><span class="dh-rep-winrate-fill" style="width:' + winRate + '%"></span></span>' + winRate + '%</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="5"><div class="dh-empty-state">Sin datos de ejecutivos todavía.</div></td></tr>';
        }
    }

    // ============================================================
    // Cambio de vista
    // ============================================================
    function setView(view) {
        state.view = view;
        document.querySelectorAll('.dh-view').forEach(function (el) {
            el.classList.toggle('dh-view-active', el.getAttribute('data-view') === view);
        });
        document.querySelectorAll('[data-view-btn]').forEach(function (btn) {
            btn.classList.toggle('dh-view-btn-active', btn.getAttribute('data-view-btn') === view);
        });
        renderAll();
    }

    document.querySelectorAll('[data-view-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () { setView(btn.getAttribute('data-view-btn')); });
    });

    // ============================================================
    // Filtros / búsqueda (todo client-side)
    // ============================================================
    function bindFilterInput(id, key, evt) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener(evt || 'input', function () {
            state[key] = el.value.trim().toLowerCase();
            renderAll();
        });
    }

    bindFilterInput('dhSearch', 'search');
    bindFilterInputRaw('dhFilterOwner', 'ownerId');
    bindFilterInputRaw('dhFilterTag', 'tagId');
    bindFilterInputRaw('dhFilterDateFrom', 'dateFrom');
    bindFilterInputRaw('dhFilterDateTo', 'dateTo');
    bindFilterInputRaw('dhFilterMinValue', 'minValue');
    bindFilterInputRaw('dhFilterMaxValue', 'maxValue');

    function bindFilterInputRaw(id, key) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', function () {
            state[key] = el.value;
            renderAll();
        });
    }

    var clearFiltersBtn = document.getElementById('dhClearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            state.search = state.ownerId = state.tagId = state.dateFrom = state.dateTo = state.minValue = state.maxValue = '';
            ['dhSearch', 'dhFilterOwner', 'dhFilterTag', 'dhFilterDateFrom', 'dhFilterDateTo', 'dhFilterMinValue', 'dhFilterMaxValue'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            renderAll();
        });
    }

    // ============================================================
    // Click para abrir drawer de detalle (delegado, evita re-bind
    // en cada render de lista/tabla/kanban).
    // ============================================================
    document.addEventListener('click', function (e) {
        if (e.target.closest && (e.target.closest('[data-dh-select]') || e.target.closest('.dh-quick-btn') || e.target.closest('[contenteditable]'))) return;
        var trigger = e.target.closest ? e.target.closest('[data-deal-id]') : null;
        if (!trigger) return;
        // En kanban el drag ya maneja el reordenamiento; un click simple
        // (sin arrastre) abre el drawer también.
        openDrawer(trigger.getAttribute('data-deal-id'));
    });

    // ============================================================
    // Drawer lateral de detalle
    // ============================================================
    var drawer = document.getElementById('dhDrawer');
    var drawerOverlay = document.getElementById('dhDrawerOverlay');
    var drawerBody = document.getElementById('dhDrawerBody');
    var drawerTitle = document.getElementById('dhDrawerTitle');
    var drawerFolio = document.getElementById('dhDrawerFolio');
    var drawerCloseBtn = document.getElementById('dhDrawerClose');
    var currentDrawerDealId = null;
    var currentDrawerTab = 'detalle';

    function openDrawer(dealId) {
        currentDrawerDealId = dealId;
        currentDrawerTab = 'detalle';
        if (drawer) drawer.classList.add('dh-drawer-open');
        if (drawerOverlay) drawerOverlay.classList.add('dh-drawer-open');

        var deal = DEALS.filter(function (d) { return String(d.id) === String(dealId); })[0];
        if (drawerTitle) drawerTitle.textContent = deal ? deal.name : 'Negocio';
        if (drawerFolio) drawerFolio.textContent = deal ? deal.folio : '';

        renderDrawerLoading();
        fetchDealDetail(dealId);
        setDrawerTab('detalle');
    }

    function closeDrawer() {
        if (drawer) drawer.classList.remove('dh-drawer-open');
        if (drawerOverlay) drawerOverlay.classList.remove('dh-drawer-open');
        currentDrawerDealId = null;
    }

    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);

    function renderDrawerLoading() {
        if (drawerBody) drawerBody.innerHTML = '<div class="dh-loading-inline">Cargando…</div>';
    }

    var lastDrawerPayload = null;

    function fetchDealDetail(dealId) {
        var url = (URLS.dealDetailTemplate || '/admin/negocios/__DEAL_ID__').replace('__DEAL_ID__', dealId);

        fetch(url, { headers: csrfHeaders() })
            .then(function (response) {
                if (!response.ok) throw new Error('detail fetch failed: ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (String(dealId) !== String(currentDrawerDealId)) return; // el usuario ya cambió de negocio
                lastDrawerPayload = data;
                renderDrawerPanes(data);
            })
            .catch(function (err) {
                console.error(err);
                if (drawerBody) drawerBody.innerHTML = '<div class="dh-empty-state">No se pudo cargar el detalle de este negocio.</div>';
            });
    }

    function renderDrawerPanes(data) {
        var deal = data.deal || data;
        var stageHistory = data.stage_history || data.stageHistory || [];
        var tasks = data.tasks || [];

        var detalleHtml = '' +
            drawerField('Nombre', escapeHtml(deal.name)) +
            drawerField('Folio', escapeHtml(deal.folio)) +
            drawerField('Monto', money(deal.amount, deal.currency)) +
            drawerField('Pipeline / Etapa', escapeHtml((deal.pipeline && deal.pipeline.name) || '') + ' — ' + escapeHtml((deal.stage && deal.stage.name) || '')) +
            drawerField('Responsable', escapeHtml((deal.owner && deal.owner.name) || '—')) +
            drawerField('Cliente', escapeHtml((deal.customer && (deal.customer.first_name + ' ' + deal.customer.last_name)) || deal.contact_snapshot_name || '—')) +
            drawerField('Fecha de cierre estimada', shortDate(deal.expected_close_date)) +
            drawerField('Estado', statusBadge(deal.status)) +
            (deal.lost_reason ? drawerField('Motivo de pérdida', escapeHtml(deal.lost_reason)) : '') +
            (deal.notes ? drawerField('Notas', escapeHtml(deal.notes)) : '');

        var actividadHtml = stageHistory.length
            ? stageHistory.map(function (h) {
                var from = (h.from_stage && h.from_stage.name) || 'Inicio';
                var to = (h.to_stage && h.to_stage.name) || '—';
                return '<div class="dh-timeline-item"><div><strong>' + escapeHtml(from) + ' → ' + escapeHtml(to) + '</strong><div class="dh-timeline-date">' + shortDate(h.moved_at) + '</div></div></div>';
            }).join('')
            : '<div class="dh-empty-state">Sin historial de movimientos todavía.</div>';

        var tareasHtml = tasks.length
            ? tasks.map(function (t) {
                return '<div class="dh-task-item"><input type="checkbox" ' + (t.status === 'completed' ? 'checked' : '') + ' disabled><span>' + escapeHtml(t.title || t.name || 'Tarea') + (t.due_at ? (' — <span style="color:var(--faint);">' + shortDate(t.due_at) + '</span>') : '') + '</span></div>';
            }).join('')
            : '<div class="dh-empty-state">Sin tareas asociadas a este negocio.</div>';

        if (drawerBody) {
            drawerBody.innerHTML = '' +
                '<div class="dh-drawer-pane" data-pane="detalle">' + detalleHtml + '</div>' +
                '<div class="dh-drawer-pane" data-pane="actividad">' + actividadHtml + '</div>' +
                '<div class="dh-drawer-pane" data-pane="tareas">' + tareasHtml + '</div>';
        }
        setDrawerTab(currentDrawerTab);
    }

    function drawerField(label, value) {
        return '<div class="dh-drawer-field"><span class="dh-drawer-field-label">' + label + '</span><span class="dh-drawer-field-value">' + value + '</span></div>';
    }

    function setDrawerTab(tab) {
        currentDrawerTab = tab;
        document.querySelectorAll('.dh-drawer-tab').forEach(function (btn) {
            btn.classList.toggle('dh-drawer-tab-active', btn.getAttribute('data-drawer-tab') === tab);
        });
        document.querySelectorAll('.dh-drawer-pane').forEach(function (pane) {
            pane.classList.toggle('dh-drawer-pane-active', pane.getAttribute('data-pane') === tab);
        });
    }

    document.querySelectorAll('.dh-drawer-tab').forEach(function (btn) {
        btn.addEventListener('click', function () { setDrawerTab(btn.getAttribute('data-drawer-tab')); });
    });

    // ── Acciones del drawer: editar / ganar / perder ────────
    var drawerEditBtn = document.getElementById('dhDrawerEdit');
    if (drawerEditBtn) {
        drawerEditBtn.addEventListener('click', function () {
            if (currentDrawerDealId && URLS.editTemplate) {
                window.location.href = URLS.editTemplate.replace('__DEAL_ID__', currentDrawerDealId);
            }
        });
    }

    var drawerWinBtn = document.getElementById('dhDrawerWin');
    var drawerLoseBtn = document.getElementById('dhDrawerLose');
    if (drawerWinBtn) drawerWinBtn.addEventListener('click', function () { openWinLoseModal('won'); });
    if (drawerLoseBtn) drawerLoseBtn.addEventListener('click', function () { openWinLoseModal('lost'); });

    // ============================================================
    // Modal ganar / perder
    // ============================================================
    var winLoseModal = document.getElementById('dhWinLoseModal');
    var winLoseTitle = document.getElementById('dhWinLoseTitle');
    var winLoseReasonWrap = document.getElementById('dhWinLoseReasonWrap');
    var winLoseNotes = document.getElementById('dhWinLoseNotes');
    var winLoseReasonSelect = document.getElementById('dhWinLoseReasonSelect');
    var winLoseConfirmBtn = document.getElementById('dhWinLoseConfirm');
    var winLoseCancelBtn = document.getElementById('dhWinLoseCancel');
    var pendingWinLoseKind = null;
    var pendingWinLoseDealId = null;

    function openWinLoseModal(kind) {
        var dealId = currentDrawerDealId;
        if (!dealId) return;

        var targetStage = STAGES.filter(function (s) { return kind === 'won' ? s.is_won : s.is_lost; })[0];
        if (!targetStage) {
            showToast('Este pipeline no tiene una etapa marcada como ' + (kind === 'won' ? 'ganada' : 'perdida') + '.', 'error');
            return;
        }

        pendingWinLoseKind = kind;
        pendingWinLoseDealId = dealId;

        if (winLoseTitle) winLoseTitle.textContent = kind === 'won' ? 'Marcar negocio como ganado' : 'Marcar negocio como perdido';
        if (winLoseReasonWrap) winLoseReasonWrap.style.display = kind === 'lost' ? 'block' : 'none';
        if (winLoseNotes) winLoseNotes.value = '';
        if (winLoseReasonSelect) winLoseReasonSelect.value = '';
        if (winLoseModal) winLoseModal.classList.add('dh-modal-open');
    }

    function closeWinLoseModal() {
        if (winLoseModal) winLoseModal.classList.remove('dh-modal-open');
        pendingWinLoseKind = null;
        pendingWinLoseDealId = null;
    }

    if (winLoseCancelBtn) winLoseCancelBtn.addEventListener('click', closeWinLoseModal);
    if (winLoseModal) {
        winLoseModal.addEventListener('click', function (e) {
            if (e.target === winLoseModal) closeWinLoseModal();
        });
    }

    if (winLoseConfirmBtn) {
        winLoseConfirmBtn.addEventListener('click', function () {
            if (!pendingWinLoseDealId || !pendingWinLoseKind) return;
            var targetStage = STAGES.filter(function (s) { return pendingWinLoseKind === 'won' ? s.is_won : s.is_lost; })[0];
            if (!targetStage) { closeWinLoseModal(); return; }

            var dealId = pendingWinLoseDealId;
            var url = (URLS.moveStageTemplate || '/admin/negocios/__DEAL_ID__/mover-etapa').replace('__DEAL_ID__', dealId);

            var payload = { to_stage_id: targetStage.id };
            if (pendingWinLoseKind === 'lost') {
                payload.lost_reason = winLoseReasonSelect ? winLoseReasonSelect.value : '';
                payload.notes = winLoseNotes ? winLoseNotes.value : '';
            }

            winLoseConfirmBtn.disabled = true;
            fetch(url, {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify(payload),
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('win/lose failed: ' + response.status);
                    return response.json().catch(function () { return {}; });
                })
                .then(function () {
                    var deal = DEALS.filter(function (d) { return String(d.id) === String(dealId); })[0];
                    if (deal) {
                        deal.status = pendingWinLoseKind;
                        deal.stage_id = targetStage.id;
                        deal.stage_name = targetStage.name;
                        if (pendingWinLoseKind === 'lost') deal.lost_reason = payload.lost_reason;
                    }
                    showToast(pendingWinLoseKind === 'won' ? 'Negocio marcado como ganado.' : 'Negocio marcado como perdido.', 'success');
                    closeWinLoseModal();
                    closeDrawer();
                    renderAll();
                })
                .catch(function (err) {
                    console.error(err);
                    showToast('No se pudo actualizar el negocio.', 'error');
                })
                .finally(function () {
                    winLoseConfirmBtn.disabled = false;
                });
        });
    }

    // ============================================================
    // Gestión de etiquetas (CRUD contra /admin/etiquetas-negocio)
    // ============================================================
    var tagManageBtn = document.getElementById('dhManageTagsBtn');
    var tagModal = document.getElementById('dhTagModal');
    var tagModalCloseBtn = document.getElementById('dhTagModalClose');
    var tagListEl = document.getElementById('dhTagManageList');
    var tagNewName = document.getElementById('dhTagNewName');
    var tagNewColor = document.getElementById('dhTagNewColor');
    var tagNewBtn = document.getElementById('dhTagNewBtn');

    function renderTagManageList() {
        if (!tagListEl) return;
        tagListEl.innerHTML = TAGS.length
            ? TAGS.map(function (t) {
                return '' +
                    '<div class="dh-tag-manage-row" data-tag-id="' + t.id + '">' +
                        '<span class="dh-tag-swatch" style="background:' + (t.color || '#6b7280') + '"></span>' +
                        '<input type="text" class="dh-input" value="' + escapeHtml(t.name) + '" data-tag-name-input>' +
                        '<button type="button" class="dh-btn dh-btn-icon" data-tag-save title="Guardar">&#10003;</button>' +
                        '<button type="button" class="dh-btn dh-btn-icon" data-tag-delete title="Eliminar">&times;</button>' +
                    '</div>';
            }).join('')
            : '<div class="dh-empty-state">Sin etiquetas todavía.</div>';
    }

    if (tagManageBtn && tagModal) {
        tagManageBtn.addEventListener('click', function () {
            renderTagManageList();
            tagModal.classList.add('dh-modal-open');
        });
    }
    if (tagModalCloseBtn) tagModalCloseBtn.addEventListener('click', function () { tagModal.classList.remove('dh-modal-open'); });
    if (tagModal) tagModal.addEventListener('click', function (e) { if (e.target === tagModal) tagModal.classList.remove('dh-modal-open'); });

    if (tagNewBtn) {
        tagNewBtn.addEventListener('click', function () {
            var name = tagNewName ? tagNewName.value.trim() : '';
            var color = tagNewColor ? tagNewColor.value : '#6b7280';
            if (!name || !URLS.tagsBase) return;

            fetch(URLS.tagsBase, {
                method: 'POST',
                headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ name: name, color: color }),
            })
                .then(function (r) { if (!r.ok) throw new Error('create tag failed'); return r.json(); })
                .then(function (data) {
                    TAGS.push(data.tag || data);
                    if (tagNewName) tagNewName.value = '';
                    renderTagManageList();
                    refreshTagFilterOptions();
                    showToast('Etiqueta creada.', 'success');
                })
                .catch(function () { showToast('No se pudo crear la etiqueta.', 'error'); });
        });
    }

    if (tagListEl) {
        tagListEl.addEventListener('click', function (e) {
            var row = e.target.closest ? e.target.closest('[data-tag-id]') : null;
            if (!row) return;
            var tagId = row.getAttribute('data-tag-id');
            var itemUrl = (URLS.tagsBase || '/admin/etiquetas-negocio') + '/' + tagId;

            if (e.target.closest('[data-tag-save]')) {
                var input = row.querySelector('[data-tag-name-input]');
                var name = input ? input.value.trim() : '';
                if (!name) return;
                fetch(itemUrl, {
                    method: 'PUT',
                    headers: csrfHeaders({ 'Content-Type': 'application/json' }),
                    body: JSON.stringify({ name: name }),
                })
                    .then(function (r) { if (!r.ok) throw new Error('update tag failed'); return r.json().catch(function () { return {}; }); })
                    .then(function () {
                        var t = TAGS.filter(function (x) { return String(x.id) === String(tagId); })[0];
                        if (t) t.name = name;
                        DEALS.forEach(function (d) {
                            (d.tags || []).forEach(function (dt) { if (String(dt.id) === String(tagId)) dt.name = name; });
                        });
                        renderAll();
                        showToast('Etiqueta actualizada.', 'success');
                    })
                    .catch(function () { showToast('No se pudo actualizar la etiqueta.', 'error'); });
            }

            if (e.target.closest('[data-tag-delete]')) {
                if (!window.confirm('¿Eliminar esta etiqueta?')) return;
                fetch(itemUrl, {
                    method: 'DELETE',
                    headers: csrfHeaders(),
                })
                    .then(function (r) { if (!r.ok) throw new Error('delete tag failed'); return r.json().catch(function () { return {}; }); })
                    .then(function () {
                        TAGS = TAGS.filter(function (x) { return String(x.id) !== String(tagId); });
                        DEALS.forEach(function (d) {
                            d.tags = (d.tags || []).filter(function (dt) { return String(dt.id) !== String(tagId); });
                        });
                        renderTagManageList();
                        refreshTagFilterOptions();
                        renderAll();
                        showToast('Etiqueta eliminada.', 'success');
                    })
                    .catch(function () { showToast('No se pudo eliminar la etiqueta.', 'error'); });
            }
        });
    }

    function refreshTagFilterOptions() {
        var select = document.getElementById('dhFilterTag');
        if (!select) return;
        var current = select.value;
        select.innerHTML = '<option value="">Todas las etiquetas</option>' + TAGS.map(function (t) {
            return '<option value="' + t.id + '">' + escapeHtml(t.name) + '</option>';
        }).join('');
        select.value = current;
    }

    // ============================================================
    // Render maestro
    // ============================================================
    function renderAll() {
        renderMetrics();
        if (state.view === 'kanban') renderKanban();
        else if (state.view === 'lista') renderList();
        else if (state.view === 'tabla') renderTable();
        else if (state.view === 'forecast') renderForecast();
    }

    // Estado inicial
    setView('kanban');
})();
