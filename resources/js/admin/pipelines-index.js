/* ============================================================
   pipelines-index.js — Admin > ERP > Pipelines
   Vanilla JS (IIFE), sin dependencias nuevas. Mismo patrón de
   fetch+CSRF y filtrado client-side sobre data-* que
   resources/js/admin/workflows-index.js.

   CONTRATO DE DATOS con el backend (ver CLAUDE.md de la tarea):
     POST   /admin/pipelines                  crear   {name, channel, is_default, is_active, stages}
     PUT    /admin/pipelines/{id}              editar  {name, is_default, is_active, stages}
     DELETE /admin/pipelines/{id}              borrar  { reassign_to_pipeline_id? }
     POST   /admin/pipelines/{id}/duplicar     duplicar -> admin.pipelines.duplicate
     POST   /admin/pipelines/{id}/reorder-stages  { stage_ids: [...] }
   ============================================================ */
(function () {
    'use strict';

    var root = document.getElementById('pipelinesPage');
    if (!root) return;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    var baseUrl = '/admin/pipelines';

    function fetchJson(url, options) {
        options = options || {};
        options.headers = Object.assign({
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }, options.headers || {});
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                return { ok: response.ok, status: response.status, data: data };
            });
        });
    }

    /* ============================================================
       Constantes / plantillas (100% client-side, sin backend)
       ============================================================ */

    var COLORS = ['#6b7280', '#b45309', '#0f6fbd', '#6d28d9', '#0f7a4f', '#c81e1e', '#0e7490', '#8a6d1f'];

    var TIPO_META = {
        normal: { label: 'Normal' },
        ganada: { label: 'Ganada' },
        perdida: { label: 'Perdida' },
    };

    var KIND = {
        deals: { label: 'Negocios' },
        whatsapp: { label: 'Chats' },
    };

    var TEMPLATES = [
        {
            name: 'Ventas B2B clásico', channel: 'deals',
            desc: 'Cuatro etapas de avance más cierre ganado y perdido. El punto de partida seguro.',
            stages: [
                ['Prospecto', 0, 'normal'], ['Calificado', 25, 'normal'], ['Cotizado', 50, 'normal'],
                ['Negociación', 75, 'normal'], ['Venta cerrada', 100, 'ganada'], ['Venta perdida', 0, 'perdida'],
            ],
        },
        {
            name: 'Proyecto con visita técnica', channel: 'deals',
            desc: 'Para ticket alto: levantamiento en sitio, dictamen y propuesta formal.',
            stages: [
                ['Levantamiento', 5, 'normal'], ['Visita técnica', 20, 'normal'], ['Propuesta técnica', 45, 'normal'],
                ['Negociación', 70, 'normal'], ['Adjudicado', 100, 'ganada'], ['Declinado', 0, 'perdida'],
            ],
        },
        {
            name: 'Chats entrantes', channel: 'whatsapp',
            desc: 'Embudo corto para WhatsApp: calificar rápido y cerrar o descartar.',
            stages: [
                ['Prospectos', 0, 'normal'], ['Conversando', 20, 'normal'], ['Cotización enviada', 50, 'normal'],
                ['Ventas cerradas', 100, 'ganada'], ['Sin interés', 0, 'perdida'],
            ],
        },
    ];

    function money(n) {
        return '$' + Math.round(n || 0).toLocaleString('es-MX');
    }

    function stageColor(i) {
        return COLORS[i % COLORS.length];
    }

    var uid = -1;
    function nextUid() { return uid--; }

    function makeStage(name, prob, tipo, wip) {
        return {
            id: null,
            uid: nextUid(),
            name: name || '',
            probability: typeof prob === 'number' ? prob : 0,
            is_won: tipo === 'ganada',
            is_lost: tipo === 'perdida',
            wip_limit: (wip === undefined || wip === null || wip === '') ? null : Number(wip),
        };
    }

    function stageTipo(s) {
        if (s.is_won) return 'ganada';
        if (s.is_lost) return 'perdida';
        return 'normal';
    }

    /* ============================================================
       Filtro / búsqueda / tabs / expandir (100% client-side)
       ============================================================ */

    var tabsWrap = document.getElementById('ppTabs');
    var searchInput = document.getElementById('ppSearch');
    var estadoSelect = document.getElementById('ppEstado');
    var toggleAllBtn = document.getElementById('ppToggleAll');
    var rowsWrap = document.getElementById('ppRows');
    var emptyFiltered = document.getElementById('ppEmptyFiltered');
    var countLabel = document.getElementById('ppCountLabel');
    var clearFiltersBtn = document.getElementById('ppClearFilters');

    var currentTab = 'todos';
    var currentSearch = '';
    var currentEstado = 'todos';

    function rows() {
        return rowsWrap ? Array.prototype.slice.call(rowsWrap.querySelectorAll('[data-pipeline-row]')) : [];
    }

    function rowMatches(row) {
        var channel = row.getAttribute('data-channel');
        var active = row.getAttribute('data-active') === '1';
        var search = row.getAttribute('data-search') || '';

        if (currentTab !== 'todos' && channel !== currentTab) return false;
        if (currentEstado === 'activo' && !active) return false;
        if (currentEstado === 'inactivo' && active) return false;
        if (currentSearch && search.indexOf(currentSearch) === -1) return false;
        return true;
    }

    function applyFilters() {
        var all = rows();
        var visible = 0;
        all.forEach(function (row) {
            var show = rowMatches(row);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (emptyFiltered) {
            emptyFiltered.style.display = (visible === 0 && all.length > 0) ? 'flex' : 'none';
        }
        if (countLabel) {
            countLabel.textContent = 'Mostrando ' + visible + ' de ' + all.length + ' pipelines';
        }
    }

    if (tabsWrap) {
        tabsWrap.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('.pp-tab') : null;
            if (!btn) return;
            Array.prototype.slice.call(tabsWrap.querySelectorAll('.pp-tab')).forEach(function (b) {
                b.classList.remove('pp-tab-active');
            });
            btn.classList.add('pp-tab-active');
            currentTab = btn.getAttribute('data-tab') || 'todos';
            applyFilters();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearch = searchInput.value.trim().toLowerCase();
            applyFilters();
        });
    }

    if (estadoSelect) {
        estadoSelect.addEventListener('change', function () {
            currentEstado = estadoSelect.value;
            applyFilters();
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function () {
            currentTab = 'todos';
            currentSearch = '';
            currentEstado = 'todos';
            if (searchInput) searchInput.value = '';
            if (estadoSelect) estadoSelect.value = 'todos';
            if (tabsWrap) {
                Array.prototype.slice.call(tabsWrap.querySelectorAll('.pp-tab')).forEach(function (b, i) {
                    b.classList.toggle('pp-tab-active', i === 0);
                });
            }
            applyFilters();
        });
    }

    function panelFor(id) {
        return document.getElementById('ppPanel-' + id);
    }

    function setPanelOpen(id, open) {
        var panel = panelFor(id);
        if (!panel) return;
        panel.style.display = open ? 'block' : 'none';
        var btn = rowsWrap.querySelector('.pp-btn-toggle[data-id="' + id + '"]');
        if (btn) btn.classList.toggle('pp-caret-open', open);
    }

    function isPanelOpen(id) {
        var panel = panelFor(id);
        return !!panel && panel.style.display !== 'none';
    }

    if (rowsWrap) {
        rowsWrap.addEventListener('click', function (e) {
            var toggleBtn = e.target.closest ? e.target.closest('.pp-btn-toggle') : null;
            if (toggleBtn) {
                var id = toggleBtn.getAttribute('data-id');
                setPanelOpen(id, !isPanelOpen(id));
                return;
            }
            var editLink = e.target.closest ? e.target.closest('.pp-btn-edit-link') : null;
            if (editLink) {
                openEditorFromButtonId(editLink.getAttribute('data-id'));
            }
        });
    }

    if (toggleAllBtn) {
        toggleAllBtn.addEventListener('click', function () {
            var anyOpen = rows().some(function (row) {
                return isPanelOpen(row.getAttribute('data-pipeline-row'));
            });
            rows().forEach(function (row) {
                setPanelOpen(row.getAttribute('data-pipeline-row'), !anyOpen);
            });
            toggleAllBtn.textContent = anyOpen ? 'Expandir todo' : 'Colapsar todo';
        });
    }

    /* ============================================================
       Editor (crear / editar) — estado "draft" en memoria
       ============================================================ */

    var editorOverlay = document.getElementById('ppEditorOverlay');
    var editorTitle = document.getElementById('ppEditorTitle');
    var fieldName = document.getElementById('ppFieldName');
    var fieldNameHint = document.getElementById('ppFieldNameHint');
    var fieldChannel = document.getElementById('ppFieldChannel');
    var fieldChannelHint = document.getElementById('ppFieldChannelHint');
    var togglePred = document.getElementById('ppTogglePred');
    var toggleActive = document.getElementById('ppToggleActive');
    var stagesCountLabel = document.getElementById('ppStagesCountLabel');
    var templatesInline = document.getElementById('ppTemplatesInline');
    var stagesEditorBody = document.getElementById('ppStagesEditorBody');
    var addStageBtn = document.getElementById('ppAddStage');
    var previewName = document.getElementById('ppPreviewName');
    var previewMeta = document.getElementById('ppPreviewMeta');
    var previewBars = document.getElementById('ppPreviewBars');
    var checklistWrap = document.getElementById('ppChecklist');
    var impactWrap = document.getElementById('ppImpact');
    var impactNote = document.getElementById('ppImpactNote');
    var footerMsg = document.getElementById('ppFooterMsg');
    var saveBtn = document.getElementById('ppEditorSave');
    var cancelBtn = document.getElementById('ppEditorCancel');
    var closeBtn = document.getElementById('ppEditorClose');

    var draft = null;
    var isEditMode = false;
    var editingId = null;
    var editingDealsCount = 0;
    var dragUid = null;

    function newDraft() {
        return {
            name: '',
            channel: 'deals',
            is_default: false,
            is_active: true,
            stages: [makeStage('', 0, 'normal')],
        };
    }

    function openEditor(mode, opts) {
        opts = opts || {};
        isEditMode = mode === 'edit';
        editingId = opts.id || null;
        editingDealsCount = opts.dealsCount || 0;
        draft = opts.draft || newDraft();

        editorTitle.textContent = isEditMode ? 'Editar pipeline' : 'Nuevo pipeline';
        saveBtn.textContent = isEditMode ? 'Guardar cambios' : 'Crear pipeline';

        fieldChannel.disabled = isEditMode;
        fieldChannelHint.textContent = isEditMode
            ? 'El tipo no se puede cambiar después de creado.'
            : 'Define dónde aparece el embudo: negocios o chats.';

        templatesInline.style.display = isEditMode ? 'none' : 'flex';

        renderEditor();
        editorOverlay.classList.add('pp-modal-open');
    }

    function closeEditor() {
        editorOverlay.classList.remove('pp-modal-open');
        draft = null;
        isEditMode = false;
        editingId = null;
        dragUid = null;
    }

    function openEditorFromButtonId(id) {
        var btn = rowsWrap.querySelector('.pp-btn-edit[data-id="' + id + '"]');
        if (btn) openEditorFromButton(btn);
    }

    function openEditorFromButton(btn) {
        var stagesRaw = [];
        try { stagesRaw = JSON.parse(btn.getAttribute('data-stages') || '[]'); } catch (e) { stagesRaw = []; }
        var d = {
            name: btn.getAttribute('data-name') || '',
            channel: btn.getAttribute('data-channel') || 'deals',
            is_default: btn.getAttribute('data-is-default') === '1',
            is_active: btn.getAttribute('data-is-active') === '1',
            stages: stagesRaw.map(function (s) {
                return {
                    id: s.id,
                    uid: nextUid(),
                    name: s.name || '',
                    probability: Number(s.probability) || 0,
                    is_won: !!s.is_won,
                    is_lost: !!s.is_lost,
                    wip_limit: (s.wip_limit === undefined || s.wip_limit === null) ? null : Number(s.wip_limit),
                };
            }),
        };
        if (d.stages.length === 0) d.stages.push(makeStage('', 0, 'normal'));

        openEditor('edit', {
            id: btn.getAttribute('data-id'),
            dealsCount: Number(btn.getAttribute('data-deals-count')) || 0,
            draft: d,
        });
    }

    function draftStat(fn) {
        return draft.stages.reduce(fn, 0);
    }

    function weightedValue(stages) {
        // Solo un cálculo de referencia para el preview del editor — el valor
        // real (con montos de negocios existentes) lo calcula el backend.
        return 0;
    }

    function validations() {
        var name = draft.name.trim();
        var pipelines = pipelinesData();
        var nameDup = pipelines.some(function (p) {
            return String(p.id) !== String(editingId) && p.name.trim().toLowerCase() === name.toLowerCase() && name;
        });
        var namesFilled = draft.stages.every(function (s) { return s.name.trim(); });
        var hasGanada = draft.stages.some(function (s) { return s.is_won; });
        var hasPerdida = draft.stages.some(function (s) { return s.is_lost; });
        var normales = draft.stages.filter(function (s) { return !s.is_won && !s.is_lost; });
        var ascending = normales.every(function (s, i) { return i === 0 || normales[i - 1].probability <= s.probability; });

        var okName = !!name && !nameDup;
        var okStagesCount = draft.stages.length >= 2 && namesFilled;
        var okAll = okName && okStagesCount && hasGanada;

        return {
            okName: okName, nameDup: nameDup, namesFilled: namesFilled,
            hasGanada: hasGanada, hasPerdida: hasPerdida, ascending: ascending,
            okStagesCount: okStagesCount, okAll: okAll,
        };
    }

    function pipelinesData() {
        return rows().map(function (row) {
            var btn = row.querySelector('.pp-btn-edit');
            return {
                id: row.getAttribute('data-pipeline-row'),
                name: btn ? (btn.getAttribute('data-name') || '') : '',
            };
        });
    }

    function renderStageRow(stage, index) {
        var row = document.createElement('div');
        row.className = 'pp-stage-editor-row';
        row.draggable = true;
        row.setAttribute('data-uid', stage.uid);

        var color = stageColor(index);

        var orderCol = document.createElement('div');
        orderCol.className = 'pp-stage-order-col';
        orderCol.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#c9ccd1" stroke-width="2"><path d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01" stroke-linecap="round"/></svg><span>' + index + '</span>';
        row.appendChild(orderCol);

        var nameCol = document.createElement('div');
        nameCol.className = 'pp-stage-name-col';
        var dot = document.createElement('span');
        dot.className = 'pp-stage-dot';
        dot.style.background = color;
        var nameInput = document.createElement('input');
        nameInput.type = 'text';
        nameInput.className = 'pp-input pp-input-sm';
        nameInput.placeholder = 'Ej. Contactado';
        nameInput.value = stage.name;
        nameInput.addEventListener('input', function () {
            stage.name = nameInput.value;
            renderPreview();
            renderChecklist();
            renderFooter();
        });
        nameCol.appendChild(dot);
        nameCol.appendChild(nameInput);
        row.appendChild(nameCol);

        var probCol = document.createElement('div');
        probCol.className = 'pp-stage-prob-col';
        var probInput = document.createElement('input');
        probInput.type = 'number';
        probInput.min = '0';
        probInput.max = '100';
        probInput.className = 'pp-input pp-input-sm pp-input-num';
        probInput.value = String(stage.probability);
        probInput.addEventListener('input', function () {
            var v = Math.max(0, Math.min(100, Number(probInput.value) || 0));
            stage.probability = v;
            renderPreview();
            renderChecklist();
        });
        var pct = document.createElement('span');
        pct.className = 'pp-prob-suffix';
        pct.textContent = '%';
        probCol.appendChild(probInput);
        probCol.appendChild(pct);
        row.appendChild(probCol);

        var tipoCol = document.createElement('div');
        tipoCol.className = 'pp-stage-tipo-col';
        ['normal', 'ganada', 'perdida'].forEach(function (k) {
            var sel = stageTipo(stage) === k;
            var pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'pp-tipo-pill pp-tipo-pill-' + k + (sel ? ' pp-tipo-pill-active' : '');
            pill.textContent = TIPO_META[k].label;
            pill.addEventListener('click', function () {
                stage.is_won = (k === 'ganada');
                stage.is_lost = (k === 'perdida');
                if (k === 'ganada') stage.probability = 100;
                if (k === 'perdida') stage.probability = 0;
                renderEditor();
            });
            tipoCol.appendChild(pill);
        });
        row.appendChild(tipoCol);

        var wipCol = document.createElement('div');
        wipCol.className = 'pp-stage-wip-col';
        var wipInput = document.createElement('input');
        wipInput.type = 'number';
        wipInput.min = '0';
        wipInput.className = 'pp-input pp-input-sm pp-input-num';
        wipInput.placeholder = '—';
        wipInput.value = stage.wip_limit === null ? '' : String(stage.wip_limit);
        wipInput.addEventListener('input', function () {
            var raw = wipInput.value;
            stage.wip_limit = raw === '' ? null : Math.max(0, Number(raw) || 0);
        });
        wipCol.appendChild(wipInput);
        row.appendChild(wipCol);

        var removeCol = document.createElement('div');
        removeCol.className = 'pp-stage-remove-col';
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'pp-stage-remove-btn';
        removeBtn.title = 'Quitar etapa';
        removeBtn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>';
        removeBtn.addEventListener('click', function () {
            if (draft.stages.length <= 1) return;
            draft.stages = draft.stages.filter(function (s) { return s.uid !== stage.uid; });
            renderEditor();
        });
        removeCol.appendChild(removeBtn);
        row.appendChild(removeCol);

        // Drag & drop nativo — solo reordena el array en memoria.
        row.addEventListener('dragstart', function () {
            dragUid = stage.uid;
            row.classList.add('pp-stage-row-dragging');
        });
        row.addEventListener('dragend', function () {
            row.classList.remove('pp-stage-row-dragging');
        });
        row.addEventListener('dragover', function (e) {
            e.preventDefault();
        });
        row.addEventListener('drop', function (e) {
            e.preventDefault();
            if (dragUid === null || dragUid === stage.uid) return;
            var fromIdx = draft.stages.findIndex(function (s) { return s.uid === dragUid; });
            var toIdx = draft.stages.findIndex(function (s) { return s.uid === stage.uid; });
            if (fromIdx === -1 || toIdx === -1) return;
            var moved = draft.stages.splice(fromIdx, 1)[0];
            draft.stages.splice(toIdx, 0, moved);
            dragUid = null;
            renderEditor();
        });

        return row;
    }

    function renderStagesEditor() {
        stagesEditorBody.innerHTML = '';
        draft.stages.forEach(function (stage, index) {
            stagesEditorBody.appendChild(renderStageRow(stage, index));
        });
        stagesCountLabel.textContent = 'Etapas · ' + draft.stages.length;
    }

    function renderTemplatesInline() {
        templatesInline.innerHTML = '<span class="pp-templates-inline-label">Aplicar plantilla:</span>';
        TEMPLATES.forEach(function (tpl) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'pp-template-chip';
            chip.textContent = tpl.name;
            chip.addEventListener('click', function () {
                draft.channel = tpl.channel;
                if (!draft.name.trim()) draft.name = tpl.name;
                draft.stages = tpl.stages.map(function (s) { return makeStage(s[0], s[1], s[2]); });
                fieldChannel.value = draft.channel;
                renderEditor();
            });
            templatesInline.appendChild(chip);
        });
    }

    function renderPreview() {
        previewName.textContent = draft.name.trim() || 'Nuevo pipeline';
        previewMeta.textContent = (KIND[draft.channel] ? KIND[draft.channel].label : draft.channel) + ' · ' + draft.stages.length + ' etapas';

        var maxProb = Math.max.apply(null, draft.stages.map(function (s) { return s.probability; }).concat([1]));
        previewBars.innerHTML = '';
        draft.stages.forEach(function (stage, i) {
            var color = stageColor(i);
            var w = Math.max(8, Math.round(100 * ((stage.probability || 2) / maxProb)));

            var item = document.createElement('div');
            item.className = 'pp-preview-item';

            var head = document.createElement('div');
            head.className = 'pp-preview-item-head';
            var label = document.createElement('div');
            label.className = 'pp-preview-item-label';
            label.innerHTML = '<span class="pp-preview-dot" style="background:' + color + ';"></span>' + escapeHtml(stage.name.trim() || ('Etapa ' + (i + 1)));
            var pct = document.createElement('div');
            pct.className = 'pp-preview-item-pct';
            pct.textContent = stage.probability + '%';
            head.appendChild(label);
            head.appendChild(pct);

            var track = document.createElement('div');
            track.className = 'pp-preview-track';
            var fill = document.createElement('div');
            fill.className = 'pp-preview-fill';
            fill.style.width = w + '%';
            fill.style.background = color + '2a';
            fill.style.borderRightColor = color;
            track.appendChild(fill);

            item.appendChild(head);
            item.appendChild(track);
            previewBars.appendChild(item);
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function checkRow(state, text) {
        // state: 'ok' | 'warn' | 'err'
        return { state: state, text: text };
    }

    function renderChecklist() {
        var v = validations();
        var checks = [];

        checks.push(checkRow(
            v.okName ? 'ok' : 'err',
            v.okName ? 'Nombre válido y sin duplicado' : (v.nameDup ? 'El nombre ya está en uso' : 'Falta el nombre del pipeline')
        ));

        checks.push(checkRow(
            v.okStagesCount ? 'ok' : 'err',
            v.namesFilled
                ? (draft.stages.length >= 2 ? draft.stages.length + ' etapas con nombre' : 'Necesitas al menos 2 etapas')
                : 'Hay etapas sin nombre'
        ));

        checks.push(checkRow(
            v.hasGanada ? 'ok' : 'err',
            v.hasGanada ? 'Existe una etapa de cierre Ganada' : 'Falta marcar la etapa de cierre Ganada'
        ));

        checks.push(checkRow(
            v.hasPerdida ? 'ok' : 'warn',
            v.hasPerdida ? 'Existe una etapa de cierre Perdida' : 'Sin etapa Perdida no podrás registrar motivos de pérdida'
        ));

        checks.push(checkRow(
            v.ascending ? 'ok' : 'warn',
            v.ascending ? 'Probabilidades en orden ascendente' : 'Las probabilidades no suben de forma consistente'
        ));

        checklistWrap.innerHTML = '';
        checks.forEach(function (c) {
            var row = document.createElement('div');
            row.className = 'pp-check-row';
            var icon = document.createElement('span');
            icon.className = 'pp-check-icon pp-check-icon-' + c.state;
            icon.textContent = c.state === 'ok' ? '✓' : (c.state === 'warn' ? '!' : '×');
            var text = document.createElement('div');
            text.className = 'pp-check-text pp-check-text-' + c.state;
            text.textContent = c.text;
            row.appendChild(icon);
            row.appendChild(text);
            checklistWrap.appendChild(row);
        });
    }

    function renderImpact() {
        impactWrap.innerHTML = '';
        var rowsData = [
            { label: 'Negocios afectados', value: isEditMode ? String(editingDealsCount) : '0' },
            { label: 'Etapas nuevas', value: String(draft.stages.length) },
            { label: 'Predeterminado', value: draft.is_default ? 'Sí' : 'No', cls: draft.is_default ? 'pp-impact-ok' : '' },
            { label: 'Disponible al crear', value: draft.is_active ? 'Sí' : 'No', cls: draft.is_active ? 'pp-impact-ok' : 'pp-impact-warn' },
        ];
        rowsData.forEach(function (r) {
            var row = document.createElement('div');
            row.className = 'pp-impact-row';
            var label = document.createElement('div');
            label.className = 'pp-impact-label';
            label.textContent = r.label;
            var value = document.createElement('div');
            value.className = 'pp-impact-value ' + (r.cls || '');
            value.textContent = r.value;
            row.appendChild(label);
            row.appendChild(value);
            impactWrap.appendChild(row);
        });
        impactNote.textContent = isEditMode
            ? 'Al quitar una etapa con negocios se te pedirá moverlos a otra etapa antes de guardar.'
            : 'Marcar este pipeline como predeterminado quitará esa marca al pipeline que la tenga hoy.';
    }

    function renderFooter() {
        var v = validations();
        footerMsg.textContent = v.okAll ? 'Todo listo para guardar.' : 'Revisa las validaciones pendientes del panel derecho.';
        footerMsg.className = 'pp-footer-msg ' + (v.okAll ? 'pp-footer-msg-ok' : 'pp-footer-msg-warn');
        saveBtn.disabled = !v.okAll;
    }

    function renderEditor() {
        fieldName.value = draft.name;
        fieldChannel.value = draft.channel;

        var v = validations();
        fieldName.classList.toggle('pp-input-error', !!draft.name && !v.okName);
        fieldNameHint.textContent = v.nameDup ? 'Ya existe un pipeline con ese nombre' : 'Como lo verá el equipo en los selectores de negocio';
        fieldNameHint.classList.toggle('pp-field-hint-error', v.nameDup);

        togglePred.setAttribute('data-active', draft.is_default ? '1' : '0');
        toggleActive.setAttribute('data-active', draft.is_active ? '1' : '0');

        if (!isEditMode) renderTemplatesInline();
        renderStagesEditor();
        renderPreview();
        renderChecklist();
        renderImpact();
        renderFooter();
    }

    if (fieldName) {
        fieldName.addEventListener('input', function () {
            draft.name = fieldName.value;
            renderPreview();
            renderChecklist();
            renderFooter();
            var v = validations();
            fieldName.classList.toggle('pp-input-error', !!draft.name && !v.okName);
            fieldNameHint.textContent = v.nameDup ? 'Ya existe un pipeline con ese nombre' : 'Como lo verá el equipo en los selectores de negocio';
            fieldNameHint.classList.toggle('pp-field-hint-error', v.nameDup);
        });
    }

    if (fieldChannel) {
        fieldChannel.addEventListener('change', function () {
            if (isEditMode) return;
            draft.channel = fieldChannel.value;
            renderPreview();
        });
    }

    if (togglePred) {
        togglePred.addEventListener('click', function () {
            draft.is_default = !draft.is_default;
            togglePred.setAttribute('data-active', draft.is_default ? '1' : '0');
            renderImpact();
        });
    }

    if (toggleActive) {
        toggleActive.addEventListener('click', function () {
            draft.is_active = !draft.is_active;
            toggleActive.setAttribute('data-active', draft.is_active ? '1' : '0');
            renderImpact();
        });
    }

    if (addStageBtn) {
        addStageBtn.addEventListener('click', function () {
            draft.stages.push(makeStage('', 0, 'normal'));
            renderEditor();
        });
    }

    var openCreateBtn = document.getElementById('ppOpenCreate');
    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', function () {
            openEditor('create', { draft: newDraft() });
        });
    }

    if (rowsWrap) {
        rowsWrap.addEventListener('click', function (e) {
            var editBtn = e.target.closest ? e.target.closest('.pp-btn-edit') : null;
            if (editBtn) {
                openEditorFromButton(editBtn);
            }
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeEditor);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEditor);
    if (editorOverlay) {
        editorOverlay.addEventListener('click', function (e) {
            if (e.target === editorOverlay) closeEditor();
        });
    }

    function showFieldError(inputId, message) {
        var el = document.getElementById(inputId);
        if (!el) return;
        el.classList.add('pp-input-error');
        var hint = document.createElement('div');
        hint.className = 'pp-field-hint pp-field-hint-error pp-server-error';
        hint.textContent = message;
        el.insertAdjacentElement('afterend', hint);
    }

    function clearServerErrors() {
        document.querySelectorAll('#ppEditorModal .pp-server-error').forEach(function (el) { el.remove(); });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            var v = validations();
            if (!v.okAll) return;

            clearServerErrors();
            saveBtn.disabled = true;

            var payload = {
                name: draft.name.trim(),
                is_default: draft.is_default,
                is_active: draft.is_active,
                stages: draft.stages.map(function (s) {
                    return {
                        id: s.id || null,
                        name: s.name.trim(),
                        probability: s.probability,
                        is_won: s.is_won,
                        is_lost: s.is_lost,
                        wip_limit: s.wip_limit,
                    };
                }),
            };
            if (!isEditMode) payload.channel = draft.channel;

            var url = isEditMode ? (baseUrl + '/' + editingId) : baseUrl;
            var method = isEditMode ? 'PUT' : 'POST';

            fetchJson(url, { method: method, body: JSON.stringify(payload) }).then(function (res) {
                if (res.ok) {
                    window.location.reload();
                    return;
                }
                if (res.status === 422) {
                    var errors = (res.data && res.data.errors) || {};
                    var idByField = { name: 'ppFieldName' };
                    Object.keys(errors).forEach(function (field) {
                        if (idByField[field]) showFieldError(idByField[field], errors[field][0]);
                    });
                    footerMsg.textContent = 'Corrige los errores señalados para guardar.';
                    footerMsg.className = 'pp-footer-msg pp-footer-msg-warn';
                } else {
                    footerMsg.textContent = (res.data && res.data.message) || 'No se pudo guardar el pipeline.';
                    footerMsg.className = 'pp-footer-msg pp-footer-msg-warn';
                }
                saveBtn.disabled = false;
            }).catch(function () {
                footerMsg.textContent = 'Error de conexión. Intenta de nuevo.';
                footerMsg.className = 'pp-footer-msg pp-footer-msg-warn';
                saveBtn.disabled = false;
            });
        });
    }

    /* ============================================================
       Duplicar
       ============================================================ */

    if (rowsWrap) {
        rowsWrap.addEventListener('click', function (e) {
            var dupBtn = e.target.closest ? e.target.closest('.pp-btn-duplicate') : null;
            if (!dupBtn) return;
            var id = dupBtn.getAttribute('data-id');
            dupBtn.disabled = true;
            fetchJson(baseUrl + '/' + id + '/duplicar', { method: 'POST' }).then(function (res) {
                if (res.ok) {
                    window.location.reload();
                } else {
                    window.alert((res.data && res.data.message) || 'No se pudo duplicar el pipeline.');
                    dupBtn.disabled = false;
                }
            }).catch(function () {
                window.alert('Error de conexión. Intenta de nuevo.');
                dupBtn.disabled = false;
            });
        });
    }

    /* ============================================================
       Plantillas (modal)
       ============================================================ */

    var templatesOverlay = document.getElementById('ppTemplatesOverlay');
    var templatesGrid = document.getElementById('ppTemplatesGrid');
    var openTemplatesBtn = document.getElementById('ppOpenTemplates');
    var templatesCloseBtn = document.getElementById('ppTemplatesClose');
    var emptyTemplateBtn = document.getElementById('ppEmptyTemplateBtn');
    var emptyTemplateBtn2 = document.getElementById('ppEmptyTemplateBtn2');

    function renderTemplatesGrid() {
        if (!templatesGrid) return;
        templatesGrid.innerHTML = '';
        TEMPLATES.forEach(function (tpl) {
            var card = document.createElement('div');
            card.className = 'pp-template-card';

            var head = document.createElement('div');
            head.className = 'pp-template-card-head';
            var name = document.createElement('div');
            name.className = 'pp-template-card-name';
            name.textContent = tpl.name;
            var badge = document.createElement('span');
            badge.className = 'pp-badge pp-kind-' + tpl.channel;
            badge.textContent = KIND[tpl.channel].label;
            head.appendChild(name);
            head.appendChild(badge);

            var desc = document.createElement('div');
            desc.className = 'pp-template-card-desc';
            desc.textContent = tpl.desc;

            var stagesList = document.createElement('div');
            stagesList.className = 'pp-template-card-stages';
            tpl.stages.forEach(function (s, i) {
                var line = document.createElement('div');
                line.className = 'pp-template-stage-line';
                line.innerHTML = '<span class="pp-preview-dot" style="background:' + stageColor(i) + ';"></span>'
                    + '<span class="pp-template-stage-name">' + escapeHtml(s[0]) + '</span>'
                    + '<span class="pp-template-stage-prob">' + s[1] + '%</span>';
                stagesList.appendChild(line);
            });

            var cta = document.createElement('div');
            cta.className = 'pp-template-card-cta';
            cta.textContent = 'Usar esta plantilla →';

            card.appendChild(head);
            card.appendChild(desc);
            card.appendChild(stagesList);
            card.appendChild(cta);

            card.addEventListener('click', function () {
                var d = newDraft();
                d.channel = tpl.channel;
                d.name = tpl.name;
                d.stages = tpl.stages.map(function (s) { return makeStage(s[0], s[1], s[2]); });
                closeTemplates();
                openEditor('create', { draft: d });
            });

            templatesGrid.appendChild(card);
        });
    }

    function openTemplates() {
        renderTemplatesGrid();
        if (templatesOverlay) templatesOverlay.classList.add('pp-modal-open');
    }

    function closeTemplates() {
        if (templatesOverlay) templatesOverlay.classList.remove('pp-modal-open');
    }

    if (openTemplatesBtn) openTemplatesBtn.addEventListener('click', openTemplates);
    if (emptyTemplateBtn) emptyTemplateBtn.addEventListener('click', openTemplates);
    if (emptyTemplateBtn2) emptyTemplateBtn2.addEventListener('click', openTemplates);
    if (templatesCloseBtn) templatesCloseBtn.addEventListener('click', closeTemplates);
    if (templatesOverlay) {
        templatesOverlay.addEventListener('click', function (e) {
            if (e.target === templatesOverlay) closeTemplates();
        });
    }

    /* ============================================================
       Eliminar
       ============================================================ */

    var deleteOverlay = document.getElementById('ppDeleteOverlay');
    var deleteName = document.getElementById('ppDeleteName');
    var deleteDesc = document.getElementById('ppDeleteDesc');
    var deleteMoveWrap = document.getElementById('ppDeleteMoveWrap');
    var deleteTargetSelect = document.getElementById('ppDeleteTarget');
    var deleteCancelBtn = document.getElementById('ppDeleteCancel');
    var deleteConfirmBtn = document.getElementById('ppDeleteConfirm');

    var deletingId = null;
    var deletingNeedsMove = false;

    function openDeleteModal(btn) {
        deletingId = btn.getAttribute('data-id');
        var name = btn.getAttribute('data-name') || '';
        var dealsCount = Number(btn.getAttribute('data-deals-count')) || 0;
        var etapasCount = Number(btn.getAttribute('data-etapas-count')) || 0;
        deletingNeedsMove = btn.getAttribute('data-has-deals') === '1' || dealsCount > 0;

        var targets = [];
        try { targets = JSON.parse(btn.getAttribute('data-targets') || '[]'); } catch (e) { targets = []; }

        deleteName.textContent = name;

        if (deletingNeedsMove) {
            deleteDesc.textContent = 'Tiene ' + dealsCount + ' negocios asignados. Para eliminarlo debes moverlos a otro pipeline; se colocarán en la primera etapa del destino.';
            deleteMoveWrap.style.display = 'block';
            deleteTargetSelect.innerHTML = '<option value="">Selecciona un pipeline…</option>';
            targets.forEach(function (t) {
                var opt = document.createElement('option');
                opt.value = String(t.id);
                opt.textContent = t.name;
                deleteTargetSelect.appendChild(opt);
            });
            deleteTargetSelect.value = '';
            deleteConfirmBtn.textContent = 'Elige el destino';
            deleteConfirmBtn.disabled = true;
        } else {
            deleteDesc.textContent = 'Este pipeline no tiene negocios asignados. Se eliminarán sus ' + etapasCount + ' etapas y la acción no se puede deshacer.';
            deleteMoveWrap.style.display = 'none';
            deleteConfirmBtn.textContent = 'Eliminar pipeline';
            deleteConfirmBtn.disabled = false;
        }

        deleteOverlay.classList.add('pp-modal-open');
    }

    function closeDeleteModal() {
        deleteOverlay.classList.remove('pp-modal-open');
        deletingId = null;
    }

    if (rowsWrap) {
        rowsWrap.addEventListener('click', function (e) {
            var delBtn = e.target.closest ? e.target.closest('.pp-btn-delete') : null;
            if (!delBtn) return;
            openDeleteModal(delBtn);
        });
    }

    if (deleteTargetSelect) {
        deleteTargetSelect.addEventListener('change', function () {
            var has = !!deleteTargetSelect.value;
            deleteConfirmBtn.disabled = !has;
            deleteConfirmBtn.textContent = has ? 'Mover y eliminar' : 'Elige el destino';
        });
    }

    if (deleteCancelBtn) deleteCancelBtn.addEventListener('click', closeDeleteModal);
    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', function (e) {
            if (e.target === deleteOverlay) closeDeleteModal();
        });
    }

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function () {
            if (deletingNeedsMove && !deleteTargetSelect.value) return;
            deleteConfirmBtn.disabled = true;

            var body = {};
            if (deletingNeedsMove) body.reassign_to_pipeline_id = Number(deleteTargetSelect.value);

            fetchJson(baseUrl + '/' + deletingId, { method: 'DELETE', body: JSON.stringify(body) }).then(function (res) {
                if (res.ok) {
                    window.location.reload();
                } else {
                    window.alert((res.data && res.data.message) || 'No se pudo eliminar el pipeline.');
                    deleteConfirmBtn.disabled = false;
                }
            }).catch(function () {
                window.alert('Error de conexión. Intenta de nuevo.');
                deleteConfirmBtn.disabled = false;
            });
        });
    }

    /* ============================================================
       Subir/bajar etapa (panel expandido del índice, fuera del editor)
       ============================================================ */

    if (rowsWrap) {
        rowsWrap.addEventListener('click', function (e) {
            var moveBtn = e.target.closest ? e.target.closest('.pp-btn-stage-up, .pp-btn-stage-down') : null;
            if (!moveBtn) return;

            var pipelineId = moveBtn.getAttribute('data-pipeline-id');
            var stageId = moveBtn.getAttribute('data-stage-id');
            var tbody = rowsWrap.querySelector('tbody[data-pipeline-id="' + pipelineId + '"]');
            if (!tbody) return;

            var stageRows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-stage-id]'));
            var idx = stageRows.findIndex(function (r) { return r.getAttribute('data-stage-id') === stageId; });
            if (idx === -1) return;

            var targetIdx = moveBtn.classList.contains('pp-btn-stage-up') ? idx - 1 : idx + 1;
            if (targetIdx < 0 || targetIdx >= stageRows.length) return;

            if (targetIdx < idx) {
                tbody.insertBefore(stageRows[idx], stageRows[targetIdx]);
            } else {
                tbody.insertBefore(stageRows[targetIdx], stageRows[idx]);
            }

            var stageIds = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-stage-id]')).map(function (r) {
                return r.getAttribute('data-stage-id');
            });

            // Actualiza la columna "Orden" visualmente de inmediato.
            Array.prototype.slice.call(tbody.querySelectorAll('tr[data-stage-id]')).forEach(function (r, i) {
                var orderCell = r.querySelector('.pp-col-order');
                if (orderCell) orderCell.textContent = String(i);
            });

            fetchJson(baseUrl + '/' + pipelineId + '/reorder-stages', {
                method: 'POST',
                body: JSON.stringify({ stage_ids: stageIds }),
            }).then(function (res) {
                if (!res.ok) window.location.reload();
            }).catch(function () {
                window.location.reload();
            });
        });
    }

    /* ============================================================
       Estado inicial
       ============================================================ */

    applyFilters();
})();
