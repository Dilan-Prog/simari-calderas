{{-- Pestaña "Bloques": modales (fuera del <form>), estilos y JS.
     Estilos/JS inline a propósito (igual que el resto de _scripts de producto):
     no dependen de `npm run build`. Contrato con el editor de secciones:
     window.HomeSectionEditor.open({ page, zone, productId?, sectionId?, onSaved(section) }). --}}

{{-- Editor de secciones (otro módulo). Se incluye solo si ya existe. --}}
@includeIf('admin.home-sections.partials.editor')

{{-- Selector de plantillas --}}
<div id="pblocksPickerModal" class="del-confirm-overlay">
    <div class="del-confirm-box pblocks-modal-box">
        <h2 class="del-confirm-title" id="pblocksPickerTitle">Usar plantilla</h2>
        <p class="del-confirm-desc" id="pblocksPickerDesc">Elige una o varias plantillas para agregarlas al final de esta zona.</p>
        <input type="text" id="pblocksPickerSearch" class="pform-input" placeholder="Buscar plantilla por nombre o tipo..."
            autocomplete="off">
        <ul class="pblocks-picker-list" id="pblocksPickerList"></ul>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="pblocksPickerCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="pblocksPickerConfirm" disabled>Agregar</button>
        </div>
    </div>
</div>

{{-- Confirmación genérica (quitar / convertir en copia propia) --}}
<div id="pblocksConfirmModal" class="del-confirm-overlay">
    <div class="del-confirm-box">
        <h2 class="del-confirm-title" id="pblocksConfirmTitle">Confirmar</h2>
        <p class="del-confirm-desc" id="pblocksConfirmDesc"></p>
        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="pblocksConfirmCancel">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="pblocksConfirmOk">Confirmar</button>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .pblocks-status { font-size: 12.5px; color: #6b7280; white-space: nowrap; min-height: 18px; }
        .pblocks-status.ok { color: #166534; }
        .pblocks-status.err { color: #b91c1c; }

        .pblocks-zone { margin-top: 24px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #fcfcfd; }
        .pblocks-zone-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        .pblocks-zone-title { margin: 0 0 2px; font-size: 15px; font-weight: 700; color: #111827; }
        .pblocks-zone-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .pblocks-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
        .pblocks-list:empty { display: none; }
        .pblocks-empty { display: none; padding: 18px; text-align: center; font-size: 13px; color: #9ca3af;
            border: 1px dashed #d1d5db; border-radius: 10px; background: #fff; }
        .pblocks-empty.show { display: block; }

        .pblocks-row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: #fff;
            border: 1px solid #e5e7eb; border-radius: 10px; }
        .pblocks-row.is-hidden { opacity: .62; background: #f9fafb; }
        .pblocks-ghost { opacity: .4; }
        .pblocks-handle { cursor: grab; color: #9ca3af; font-size: 16px; line-height: 1; user-select: none; padding: 4px 2px; flex-shrink: 0; }
        .pblocks-moves { display: flex; flex-direction: column; flex-shrink: 0; }
        .pblocks-moves button { border: none; background: none; cursor: pointer; color: #9ca3af; font-size: 10px; line-height: 1; padding: 2px 4px; }
        .pblocks-moves button:hover { color: #111827; }
        .pblocks-info { flex: 1; min-width: 0; }
        .pblocks-name { font-size: 14px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .pblocks-type { font-size: 12px; font-weight: 500; color: #6b7280; }
        .pblocks-meta { margin-top: 3px; font-size: 12.5px; color: #6b7280; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .pblocks-badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .pblocks-badge.template { background: #dbeafe; color: #1e40af; }
        .pblocks-badge.custom { background: #d1fae5; color: #065f46; }
        .pblocks-badge.warn { background: #fee2e2; color: #991b1b; }
        .pblocks-badge.muted { background: #f3f4f6; color: #4b5563; }

        .pblocks-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end; flex-shrink: 0; }
        .pblocks-actions .pblocks-btn { border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 8px;
            padding: 5px 10px; font-size: 12.5px; cursor: pointer; white-space: nowrap; }
        .pblocks-actions .pblocks-btn:hover { background: #f9fafb; }
        .pblocks-actions .pblocks-btn.danger:hover { background: #fef2f2; border-color: #fca5a5; color: #991b1b; }
        .pblocks-vis-label { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; color: #374151; cursor: pointer; margin-right: 4px; }

        .pblocks-modal-box { max-width: 560px; text-align: left; }
        .pblocks-modal-box .del-confirm-title, .pblocks-modal-box .del-confirm-desc { text-align: left; }
        .pblocks-picker-list { list-style: none; margin: 12px 0 0; padding: 0; max-height: 300px; overflow-y: auto;
            border: 1px solid #e5e7eb; border-radius: 10px; }
        .pblocks-picker-list li { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
        .pblocks-picker-list li:last-child { border-bottom: none; }
        .pblocks-picker-list label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .pblocks-picker-list .pblocks-picker-note { padding: 18px; text-align: center; color: #9ca3af; font-size: 13px; }
        .pblocks-picker-name { font-size: 14px; font-weight: 600; color: #111827; }
        .pblocks-picker-sub { font-size: 12px; color: #6b7280; }

        .pform-tab:disabled { opacity: .5; cursor: not-allowed; }

        @media (max-width: 760px) {
            .pblocks-row { flex-wrap: wrap; }
            .pblocks-actions { justify-content: flex-start; width: 100%; }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (function () {
            const root = document.getElementById('pblocksRoot');
            if (!root) return;

            const BASE = root.dataset.baseUrl;               // .../productos/{id}/bloques
            const TEMPLATES_URL = root.dataset.templatesUrl;
            const PRODUCT_ID = Number(root.dataset.productId);
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const ZONE_LABELS = { stack: 'Pila principal', sidebar: 'Columna lateral' };

            let blocks = [];

            /* ── Utilidades ── */
            function el(tag, cls, text) {
                const n = document.createElement(tag);
                if (cls) n.className = cls;
                if (text !== undefined && text !== null) n.textContent = text;
                return n;
            }

            function toast(message, type) {
                const t = el('div', '', message);
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;padding:14px 18px;border-radius:10px;font-size:14px;'
                    + 'box-shadow:0 4px 12px rgba(0,0,0,.12);max-width:380px;border:1px solid;'
                    + (type === 'error' ? 'background:#fef2f2;color:#991b1b;border-color:#fecaca;' : 'background:#f0fdf4;color:#166534;border-color:#bbf7d0;');
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 4000);
            }

            async function api(url, method, body) {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
                    },
                    body: body !== undefined ? JSON.stringify(body) : undefined,
                });
                let data = {};
                try { data = await res.json(); } catch (e) { /* respuesta no JSON */ }
                if (!res.ok || data.success === false) {
                    const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                    const err = new Error(data.message || firstError || 'No se pudo completar la acción.');
                    err.data = data;
                    throw err;
                }
                return data;
            }

            const statusEl = document.getElementById('pblocksStatus');
            let statusTimer = null;
            function setStatus(text, cls) {
                statusEl.textContent = text;
                statusEl.className = 'pblocks-status ' + (cls || '');
                clearTimeout(statusTimer);
                if (cls === 'ok') statusTimer = setTimeout(() => { statusEl.textContent = ''; }, 2500);
            }

            /* ── Render ── */
            const lists = {};
            root.querySelectorAll('.pblocks-list').forEach(ul => { lists[ul.dataset.zone] = ul; });

            function renderRow(b) {
                const li = el('li', 'pblocks-row' + (b.is_visible ? '' : ' is-hidden'));
                li.dataset.id = b.id;
                li.dataset.sectionId = b.section_id;
                li.dataset.kind = b.kind;

                li.appendChild(el('span', 'pblocks-handle', '⋮⋮')).title = 'Arrastra para reordenar';

                const moves = el('div', 'pblocks-moves');
                const up = el('button', '', '▲'); up.type = 'button'; up.title = 'Subir'; up.dataset.move = 'up';
                const down = el('button', '', '▼'); down.type = 'button'; down.title = 'Bajar'; down.dataset.move = 'down';
                moves.append(up, down);
                li.appendChild(moves);

                const info = el('div', 'pblocks-info');
                const name = el('div', 'pblocks-name');
                name.appendChild(el('span', '', b.name));
                name.appendChild(el('span', 'pblocks-type', b.type_label));
                if (b.kind === 'template') {
                    name.appendChild(el('span', 'pblocks-badge template',
                        'Plantilla (usada en ' + b.usage + (b.usage === 1 ? ' producto)' : ' productos)')));
                } else {
                    name.appendChild(el('span', 'pblocks-badge custom', 'Propia'));
                }
                if (!b.is_active) name.appendChild(el('span', 'pblocks-badge muted', 'Sección inactiva'));
                info.appendChild(name);

                const meta = el('div', 'pblocks-meta');
                if (b.link_label) {
                    meta.appendChild(el('span', '', 'Encabezado → ' + b.link_label));
                    if (b.link_broken) meta.appendChild(el('span', 'pblocks-badge warn', 'Enlace roto'));
                } else {
                    meta.appendChild(el('span', '', 'Encabezado sin enlace'));
                }
                info.appendChild(meta);
                li.appendChild(info);

                const actions = el('div', 'pblocks-actions');
                const visLabel = el('label', 'pblocks-vis-label');
                const vis = document.createElement('input');
                vis.type = 'checkbox'; vis.className = 'pblocks-vis'; vis.checked = !!b.is_visible;
                visLabel.append(vis, el('span', '', 'Visible'));
                actions.appendChild(visLabel);

                const edit = el('button', 'pblocks-btn', b.kind === 'template' ? 'Editar plantilla' : 'Editar');
                edit.type = 'button'; edit.dataset.act = 'edit';
                if (b.kind === 'template') edit.title = 'Los cambios afectan a todos los productos que usan esta plantilla';
                actions.appendChild(edit);

                if (b.kind === 'template') {
                    const copy = el('button', 'pblocks-btn', 'Convertir en copia propia');
                    copy.type = 'button'; copy.dataset.act = 'copy';
                    actions.appendChild(copy);
                }

                const del = el('button', 'pblocks-btn danger', 'Quitar');
                del.type = 'button'; del.dataset.act = 'remove';
                actions.appendChild(del);

                li.appendChild(actions);
                return li;
            }

            function render() {
                Object.entries(lists).forEach(([zone, ul]) => {
                    ul.innerHTML = '';
                    const rows = blocks.filter(b => b.zone === zone);
                    rows.forEach(b => ul.appendChild(renderRow(b)));
                    root.querySelector('.pblocks-empty[data-zone="' + zone + '"]').classList.toggle('show', rows.length === 0);
                });
            }

            async function refresh() {
                try {
                    const data = await api(BASE, 'GET');
                    blocks = data.blocks;
                    render();
                } catch (e) {
                    toast(e.message, 'error');
                }
            }

            /* ── Guardar orden / visibilidad ── */
            function collectRows() {
                const rows = [];
                Object.values(lists).forEach(ul => {
                    ul.querySelectorAll('.pblocks-row').forEach(li => {
                        rows.push({ id: Number(li.dataset.id), is_visible: li.querySelector('.pblocks-vis').checked });
                    });
                });
                return rows;
            }

            function updateEmptyStates() {
                Object.entries(lists).forEach(([zone, ul]) => {
                    root.querySelector('.pblocks-empty[data-zone="' + zone + '"]')
                        .classList.toggle('show', ul.children.length === 0);
                });
            }

            let saveTimer = null;
            function scheduleSave() {
                setStatus('Guardando…');
                clearTimeout(saveTimer);
                saveTimer = setTimeout(async () => {
                    try {
                        await api(BASE + '/reordenar', 'POST', { rows: collectRows() });
                        setStatus('✓ Guardado', 'ok');
                    } catch (e) {
                        setStatus('No se pudo guardar', 'err');
                        toast(e.message, 'error');
                        refresh();
                    }
                }, 300);
            }

            /* Arrastrar y soltar con la API nativa de HTML5 (la CSP del admin no permite
               cargar librerías desde CDN). Solo se reordena dentro de la misma zona;
               los botones ▲▼ cubren pantallas táctiles. */
            let dragEl = null;
            root.addEventListener('mousedown', e => {
                const handle = e.target.closest('.pblocks-handle');
                if (handle) handle.closest('.pblocks-row').draggable = true;
            });
            root.addEventListener('dragstart', e => {
                const li = e.target.closest && e.target.closest('.pblocks-row');
                if (!li) return;
                dragEl = li;
                li.classList.add('pblocks-ghost');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', li.dataset.id);
            });
            root.addEventListener('dragover', e => {
                if (!dragEl) return;
                const ul = e.target.closest('.pblocks-list');
                if (!ul || ul !== dragEl.parentNode) return;
                e.preventDefault();
                const after = Array.from(ul.querySelectorAll('.pblocks-row:not(.pblocks-ghost)')).find(r => {
                    const box = r.getBoundingClientRect();
                    return e.clientY < box.top + box.height / 2;
                });
                if (after) ul.insertBefore(dragEl, after); else ul.appendChild(dragEl);
            });
            root.addEventListener('dragend', () => {
                if (!dragEl) return;
                dragEl.classList.remove('pblocks-ghost');
                dragEl.draggable = false;
                dragEl = null;
                scheduleSave();
            });

            /* ── Acciones por fila (delegado) ── */
            root.addEventListener('change', e => {
                if (!e.target.classList.contains('pblocks-vis')) return;
                e.target.closest('.pblocks-row').classList.toggle('is-hidden', !e.target.checked);
                scheduleSave();
            });

            root.addEventListener('click', e => {
                const mv = e.target.closest('[data-move]');
                if (mv) {
                    const li = mv.closest('.pblocks-row');
                    if (mv.dataset.move === 'up' && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
                    if (mv.dataset.move === 'down' && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
                    scheduleSave();
                    return;
                }

                const btn = e.target.closest('[data-act]');
                if (btn) {
                    const li = btn.closest('.pblocks-row');
                    const b = blocks.find(x => x.id === Number(li.dataset.id));
                    if (!b) return;
                    if (btn.dataset.act === 'edit') return editBlock(b);
                    if (btn.dataset.act === 'remove') return removeBlock(b);
                    if (btn.dataset.act === 'copy') return copyBlock(b);
                    return;
                }

                const use = e.target.closest('.pblocks-use-template');
                if (use) return openPicker(use.dataset.zone);

                const nw = e.target.closest('.pblocks-new-custom');
                if (nw) return newCustom(nw.dataset.zone);
            });

            /* ── Confirmación genérica ── */
            const confirmModal = document.getElementById('pblocksConfirmModal');
            let confirmCb = null;
            function askConfirm(title, desc, okLabel, cb) {
                document.getElementById('pblocksConfirmTitle').textContent = title;
                document.getElementById('pblocksConfirmDesc').textContent = desc;
                document.getElementById('pblocksConfirmOk').textContent = okLabel;
                confirmCb = cb;
                confirmModal.classList.add('active');
            }
            document.getElementById('pblocksConfirmCancel').addEventListener('click', () => confirmModal.classList.remove('active'));
            document.getElementById('pblocksConfirmOk').addEventListener('click', async () => {
                confirmModal.classList.remove('active');
                if (confirmCb) await confirmCb();
            });

            function removeBlock(b) {
                const isTemplate = b.kind === 'template';
                askConfirm(
                    'Quitar bloque',
                    isTemplate
                        ? '«' + b.name + '» se quitará de este producto. La plantilla seguirá existiendo y los demás productos que la usan no cambian.'
                        : '«' + b.name + '» es una sección propia de este producto: se eliminará definitivamente.',
                    'Quitar',
                    async () => {
                        try {
                            const data = await api(BASE + '/' + b.id, 'DELETE');
                            blocks = data.blocks; render();
                            toast('Bloque quitado.');
                        } catch (e) { toast(e.message, 'error'); }
                    }
                );
            }

            function copyBlock(b) {
                askConfirm(
                    'Convertir en copia propia',
                    'Se creará una copia de «' + b.name + '» solo para este producto. Dejará de actualizarse cuando cambie la plantilla original (que sigue igual para los demás productos).',
                    'Convertir',
                    async () => {
                        try {
                            const data = await api(BASE + '/' + b.id + '/copia-propia', 'POST');
                            blocks = data.blocks; render();
                            toast('Ahora es una sección propia de este producto.');
                        } catch (e) { toast(e.message, 'error'); }
                    }
                );
            }

            /* ── Editor de secciones (otro módulo) ── */
            function editorOrWarn() {
                if (window.HomeSectionEditor && typeof window.HomeSectionEditor.open === 'function') return window.HomeSectionEditor;
                toast('El editor de secciones no está disponible en esta página. Recarga e inténtalo de nuevo.', 'error');
                return null;
            }

            async function adoptAndRefresh(section) {
                // Red de seguridad: si el editor creó la sección propia pero no la
                // asignó a este producto, se asigna aquí (idempotente en servidor).
                try {
                    if (section && section.id && (!section.page || section.page === 'product_custom')) {
                        await api(BASE + '/propia', 'POST', { section_id: section.id });
                    }
                } catch (e) { /* si es una plantilla o ya estaba, basta con refrescar */ }
                await refresh();
            }

            function newCustom(zone) {
                const editor = editorOrWarn();
                if (!editor) return;
                editor.open({ page: 'product_custom', zone, productId: PRODUCT_ID, onSaved: adoptAndRefresh });
            }

            function editBlock(b) {
                const editor = editorOrWarn();
                if (!editor) return;
                editor.open({
                    page: b.kind === 'template' ? 'product_template' : 'product_custom',
                    zone: b.zone,
                    sectionId: b.section_id,
                    ...(b.kind === 'custom' ? { productId: PRODUCT_ID } : {}),
                    onSaved: () => refresh(),
                });
            }

            /* ── Selector de plantillas ── */
            const picker = document.getElementById('pblocksPickerModal');
            const pickerList = document.getElementById('pblocksPickerList');
            const pickerSearch = document.getElementById('pblocksPickerSearch');
            const pickerConfirm = document.getElementById('pblocksPickerConfirm');
            let pickerZone = 'stack';
            let pickerSelected = new Set();
            let pickerTimer = null;
            let pickerSeq = 0;

            function pickerNote(text) {
                pickerList.innerHTML = '';
                const li = el('li', ''); li.appendChild(el('div', 'pblocks-picker-note', text));
                pickerList.appendChild(li);
            }

            function syncPickerButton() {
                const n = pickerSelected.size;
                pickerConfirm.disabled = n === 0;
                pickerConfirm.textContent = n > 1 ? 'Agregar (' + n + ')' : 'Agregar';
            }

            async function loadPicker() {
                const seq = ++pickerSeq;
                pickerNote('Buscando…');
                const params = new URLSearchParams({ zone: pickerZone, product_id: PRODUCT_ID, q: pickerSearch.value.trim() });
                try {
                    const data = await api(TEMPLATES_URL + '?' + params.toString(), 'GET');
                    if (seq !== pickerSeq) return;
                    pickerList.innerHTML = '';
                    if (!data.templates.length) {
                        pickerNote('No hay plantillas disponibles en esta zona' + (pickerSearch.value.trim() ? ' con ese texto.' : '.'));
                        return;
                    }
                    data.templates.forEach(t => {
                        const li = el('li', '');
                        const label = el('label', '');
                        const cb = document.createElement('input');
                        cb.type = 'checkbox'; cb.value = t.id; cb.checked = pickerSelected.has(t.id);
                        cb.addEventListener('change', () => {
                            cb.checked ? pickerSelected.add(t.id) : pickerSelected.delete(t.id);
                            syncPickerButton();
                        });
                        const text = el('div', '');
                        text.appendChild(el('div', 'pblocks-picker-name', t.name));
                        text.appendChild(el('div', 'pblocks-picker-sub',
                            t.type_label + ' · usada en ' + t.usage + (t.usage === 1 ? ' producto' : ' productos')));
                        label.append(cb, text);
                        li.appendChild(label);
                        pickerList.appendChild(li);
                    });
                } catch (e) {
                    if (seq === pickerSeq) pickerNote(e.message);
                }
            }

            function openPicker(zone) {
                pickerZone = zone;
                pickerSelected = new Set();
                pickerSearch.value = '';
                document.getElementById('pblocksPickerTitle').textContent = 'Usar plantilla — ' + ZONE_LABELS[zone];
                syncPickerButton();
                picker.classList.add('active');
                loadPicker();
                setTimeout(() => pickerSearch.focus(), 50);
            }

            pickerSearch.addEventListener('input', () => {
                clearTimeout(pickerTimer);
                pickerTimer = setTimeout(loadPicker, 250);
            });
            // Enter dentro del buscador no debe hacer nada raro (el modal vive fuera del <form>, pero por si acaso).
            pickerSearch.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
            document.getElementById('pblocksPickerCancel').addEventListener('click', () => picker.classList.remove('active'));
            pickerConfirm.addEventListener('click', async () => {
                if (!pickerSelected.size) return;
                pickerConfirm.disabled = true;
                try {
                    const data = await api(BASE + '/plantillas', 'POST', { section_ids: Array.from(pickerSelected) });
                    blocks = data.blocks; render();
                    picker.classList.remove('active');
                    toast(data.result.added === 1 ? 'Plantilla agregada.' : data.result.added + ' plantillas agregadas.');
                } catch (e) {
                    toast(e.message, 'error');
                    syncPickerButton();
                }
            });

            [picker, confirmModal].forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); }));

            refresh();
        })();
    </script>
@endpush
