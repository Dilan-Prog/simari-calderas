@push('scripts')
    <script>
        /* ============================================================
           Filtros técnicos por categoría (modal #categoryFiltersModal).
           GET/PUT /admin/categorias/{id}/filtros — ver CategoryFilterController.
           El estado vive en `state`; los inputs lo actualizan sin volver a
           pintar (para no perder el foco) y las acciones estructurales
           (agregar/quitar/mover) repintan la lista.
           ============================================================ */
        (function () {
            const modal = document.getElementById('categoryFiltersModal');
            if (!modal) return;

            const BASE_URL = '{{ url('/admin/categorias') }}';
            const TAGS_URL = '{{ url('/admin/productos/etiquetas/buscar') }}';
            const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            const MAX_GROUPS = 10;
            const MAX_OPTIONS = 15;

            const $ = (id) => document.getElementById(id);
            const el = {
                content: modal.querySelector('.cf-content'),
                inner: $('cfInner'),
                body: $('cfBody'),
                title: $('cfTitle'),
                path: $('cfPath'),
                alert: $('cfAlert'),
                warnings: $('cfWarnings'),
                loading: $('cfLoading'),
                main: $('cfContent'),
                inherited: $('cfInherited'),
                groups: $('cfGroups'),
                groupLimit: $('cfGroupLimit'),
                addGroup: $('cfAddGroup'),
                suggest: $('cfSuggest'),
                chips: $('cfSuggestChips'),
                status: $('cfStatus'),
                save: $('cfSave'),
                cancel: $('cfCancel'),
                close: $('cfClose'),
            };

            let seq = 0;
            const newKey = () => 'k' + (++seq);

            const state = {
                categoryId: null,
                categoryName: '',
                groups: [],
                inherited: [],
                suggestions: [],
                counts: {},          // etiqueta normalizada -> nº de productos conocido
                baseline: '',
                loading: false,
                loaded: false,
                saving: false,
                savedAt: null,
                activeGroup: null,   // key del último grupo con el que se interactuó
                opener: null,
            };

            /* ── Utilidades ── */
            function esc(s) {
                return String(s == null ? '' : s)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            }

            // Misma idea que App\Services\Catalog\TagNormalizer (minúsculas, sin acentos, espacios colapsados).
            function norm(s) {
                return String(s == null ? '' : s)
                    .normalize('NFD').replace(/[̀-ͯ]/g, '')
                    .toLowerCase().replace(/\s+/g, ' ').trim();
            }

            function plural(n, one, many) {
                return n + ' ' + (n === 1 ? one : many);
            }

            function findGroup(key) {
                return state.groups.find((g) => g.key === key);
            }

            function findOption(group, key) {
                return group ? group.options.find((o) => o.key === key) : null;
            }

            function makeOption(src) {
                src = src || {};
                const label = src.label || '';
                const tag = src.tag != null ? src.tag : label;
                return {
                    key: newKey(),
                    id: src.id || null,
                    label: label,
                    tag: tag,
                    is_active: src.is_active !== false,
                    savedTag: src.id ? tag : null,
                    savedCount: src.id ? (src.products_count || 0) : null,
                    // La etiqueta de producto se copia sola de la visible mientras no se edite aparte.
                    touched: src.id ? tag !== label : false,
                };
            }

            function makeGroup(src) {
                src = src || {};
                return {
                    key: newKey(),
                    id: src.id || null,
                    name: src.name || '',
                    is_active: src.is_active !== false,
                    options: (src.options || []).map(makeOption),
                };
            }

            function snapshot() {
                return JSON.stringify(state.groups.map((g) => [
                    g.id, g.name, g.is_active,
                    g.options.map((o) => [o.id, o.label, o.tag, o.is_active]),
                ]));
            }

            function isDirty() {
                return state.loaded && snapshot() !== state.baseline;
            }

            function rebuildCounts() {
                const counts = {};
                state.suggestions.forEach((s) => { counts[norm(s.tag)] = s.count; });
                const add = (groups) => groups.forEach((g) => (g.options || []).forEach((o) => {
                    counts[norm(o.tag)] = o.products_count;
                }));
                add(state.serverGroups || []);
                state.inherited.forEach((i) => add(i.groups));
                state.counts = counts;
            }

            function countFor(o) {
                const key = norm(o.tag);
                if (key === '') return null;
                if (Object.prototype.hasOwnProperty.call(state.counts, key)) return state.counts[key];
                // Etiqueta sin ningún producto detectado entre las usadas por la categoría.
                if (o.id && o.savedTag === o.tag && o.savedCount != null) return o.savedCount;
                // La lista de sugerencias trae TODAS las etiquetas en uso si no llegó al tope:
                // una etiqueta que no aparece ahí no la tiene ningún producto publicado.
                if (state.suggestions.length < 40) return 0;
                return null;
            }

            function countHtml(o) {
                const n = countFor(o);
                if (n === null) {
                    return '<span class="cf-count cf-count--unknown" title="Guarda para recalcular el conteo">sin conteo</span>';
                }
                if (n === 0) {
                    return '<span class="cf-count cf-count--zero" title="Ningún producto publicado de esta categoría tiene esta etiqueta">0 productos</span>';
                }
                return '<span class="cf-count">' + plural(n, 'producto', 'productos') + '</span>';
            }

            /* ── Pintado ── */
            function renderGroups(focusSel) {
                const total = state.groups.length;
                el.groups.innerHTML = state.groups.map((g, gi) => {
                    const optRows = g.options.map((o, oi) => `
                        <div class="cf-option" data-ok="${o.key}">
                            <div class="cf-cell">
                                <label class="cf-mobile-label" for="cfl-${o.key}">Etiqueta visible</label>
                                <input type="text" id="cfl-${o.key}" class="users-manager-input" data-f="label" data-err="groups.${gi}.options.${oi}.label"
                                    maxlength="80" value="${esc(o.label)}" placeholder="Ej: 1/4 DIN" autocomplete="off">
                            </div>
                            <div class="cf-cell">
                                <label class="cf-mobile-label" for="cft-${o.key}">Etiqueta de producto</label>
                                <input type="text" id="cft-${o.key}" class="users-manager-input cf-tag-input" data-f="tag" data-err="groups.${gi}.options.${oi}.tag"
                                    maxlength="80" value="${esc(o.tag)}" placeholder="Etiqueta que tienen los productos" autocomplete="off"
                                    role="combobox" aria-autocomplete="list" aria-expanded="false">
                            </div>
                            <div class="cf-cell cf-cell--count" data-count-for="${o.key}">${countHtml(o)}</div>
                            <div class="cf-cell cf-cell--check">
                                <label class="cf-check"><input type="checkbox" data-f="oactive" ${o.is_active ? 'checked' : ''}> Activa</label>
                            </div>
                            <div class="cf-cell cf-cell--actions">
                                <button type="button" class="cf-icon-btn" data-action="opt-up" aria-label="Subir opción ${oi + 1}" title="Subir" ${oi === 0 ? 'disabled' : ''}>▲</button>
                                <button type="button" class="cf-icon-btn" data-action="opt-down" aria-label="Bajar opción ${oi + 1}" title="Bajar" ${oi === g.options.length - 1 ? 'disabled' : ''}>▼</button>
                                <button type="button" class="cf-icon-btn cf-icon-btn--danger" data-action="opt-del" aria-label="Eliminar opción ${oi + 1}" title="Eliminar">✕</button>
                            </div>
                        </div>`).join('');

                    return `
                    <section class="cf-group ${g.is_active ? '' : 'is-inactive'}" data-gk="${g.key}" aria-label="Grupo ${gi + 1}">
                        <div class="cf-group__head">
                            <span class="cf-group__num">Grupo ${gi + 1}</span>
                            <div class="cf-group__name">
                                <input type="text" class="users-manager-input" data-f="gname" data-err="groups.${gi}.name"
                                    maxlength="60" value="${esc(g.name)}" placeholder="Nombre del grupo (ej: Tamaño DIN)"
                                    aria-label="Nombre del grupo ${gi + 1}" autocomplete="off">
                            </div>
                            <label class="cf-check"><input type="checkbox" data-f="gactive" ${g.is_active ? 'checked' : ''}> Activo</label>
                            <div class="cf-group__actions">
                                <button type="button" class="cf-icon-btn" data-action="group-up" aria-label="Subir grupo ${gi + 1}" title="Subir grupo" ${gi === 0 ? 'disabled' : ''}>▲</button>
                                <button type="button" class="cf-icon-btn" data-action="group-down" aria-label="Bajar grupo ${gi + 1}" title="Bajar grupo" ${gi === total - 1 ? 'disabled' : ''}>▼</button>
                                <button type="button" class="cf-icon-btn cf-icon-btn--danger" data-action="group-del" aria-label="Eliminar grupo ${gi + 1}" title="Eliminar grupo">✕</button>
                            </div>
                        </div>
                        <div class="cf-group__error" data-err-box="groups.${gi}.options" hidden></div>
                        <div class="cf-options-head" aria-hidden="true">
                            <span>Etiqueta visible</span><span>Etiqueta de producto</span><span>Productos</span><span>Estado</span><span></span>
                        </div>
                        <div class="cf-options">${optRows}</div>
                        <div class="cf-group__foot">
                            <button type="button" class="cf-btn cf-btn--add" data-action="add-option" ${g.options.length >= MAX_OPTIONS ? 'disabled' : ''}>+ Agregar opción</button>
                            <span class="cf-hint">${g.options.length} de ${MAX_OPTIONS} opciones</span>
                        </div>
                    </section>`;
                }).join('');

                if (!total) {
                    el.groups.innerHTML = '<p class="cf-empty">Esta categoría aún no tiene filtros técnicos propios. Agrega un grupo para empezar.</p>';
                }

                el.addGroup.disabled = total >= MAX_GROUPS;
                el.groupLimit.textContent = total + ' de ' + MAX_GROUPS + ' grupos';

                renderSuggestions();
                updateStatus();

                if (focusSel) {
                    const target = modal.querySelector(focusSel);
                    if (target) { target.focus(); }
                }
            }

            function usedTags() {
                const set = new Set();
                state.groups.forEach((g) => g.options.forEach((o) => { if (o.tag) set.add(norm(o.tag)); }));
                return set;
            }

            function renderSuggestions() {
                const used = usedTags();
                const list = state.suggestions.filter((s) => !used.has(norm(s.tag)));
                el.suggest.hidden = list.length === 0;
                el.chips.innerHTML = list.map((s) => `
                    <button type="button" class="cf-chip" data-action="chip" data-tag="${esc(s.tag)}"
                        title="Agregar «${esc(s.tag)}» como opción">
                        ${esc(s.tag)} <span class="cf-chip__n">${s.count}</span>
                    </button>`).join('');
            }

            function renderInherited() {
                if (!state.inherited.length) {
                    el.inherited.hidden = true;
                    el.inherited.innerHTML = '';
                    return;
                }
                el.inherited.hidden = false;
                el.inherited.innerHTML = '<summary>Filtros heredados de categorías superiores (solo lectura)</summary>' +
                    '<p class="cf-hint">Se muestran también en esta categoría. Para cambiarlos, edita los filtros de su categoría de origen.</p>' +
                    state.inherited.map((cat) => `
                        <div class="cf-inh-cat">
                            <h4>Desde «${esc(cat.category_name)}»</h4>
                            ${cat.groups.map((g) => `
                                <div class="cf-inh-group ${g.is_active ? '' : 'is-inactive'}">
                                    <strong>${esc(g.name)}</strong>${g.is_active ? '' : ' <em>(inactivo)</em>'}
                                    <ul>${g.options.map((o) => `
                                        <li class="${o.is_active ? '' : 'is-inactive'}">${esc(o.label)}
                                            <span class="cf-inh-tag">etiqueta: ${esc(o.tag)}</span>
                                            <span class="cf-count ${o.products_count === 0 ? 'cf-count--zero' : ''}">${plural(o.products_count, 'producto', 'productos')}</span>
                                        </li>`).join('')}
                                    </ul>
                                </div>`).join('')}
                        </div>`).join('');
            }

            function updateStatus() {
                const dirty = isDirty();
                el.save.disabled = !state.loaded || state.saving || !dirty;
                if (state.saving) {
                    el.status.textContent = 'Guardando…';
                    el.status.className = 'cf-status is-saving';
                } else if (!state.loaded) {
                    el.status.textContent = '';
                    el.status.className = 'cf-status';
                } else if (dirty) {
                    el.status.textContent = 'Cambios sin guardar';
                    el.status.className = 'cf-status is-dirty';
                } else if (state.savedAt) {
                    el.status.textContent = 'Guardado a las ' + state.savedAt;
                    el.status.className = 'cf-status is-saved';
                } else {
                    el.status.textContent = 'Sin cambios';
                    el.status.className = 'cf-status';
                }
            }

            function updateCount(optionKey) {
                const cell = el.groups.querySelector('[data-count-for="' + optionKey + '"]');
                if (!cell) return;
                for (const g of state.groups) {
                    const o = findOption(g, optionKey);
                    if (o) { cell.innerHTML = countHtml(o); return; }
                }
            }

            /* ── Errores / avisos ── */
            function clearErrors() {
                el.alert.hidden = true;
                el.alert.innerHTML = '';
                modal.querySelectorAll('.cf-field-error').forEach((n) => n.remove());
                modal.querySelectorAll('.is-invalid').forEach((n) => { n.classList.remove('is-invalid'); n.removeAttribute('aria-invalid'); });
                modal.querySelectorAll('[data-err-box]').forEach((n) => { n.hidden = true; n.textContent = ''; });
            }

            function clearFieldError(input) {
                if (!input.classList.contains('is-invalid')) return;
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
                const next = input.parentElement && input.parentElement.querySelector('.cf-field-error');
                if (next) next.remove();
            }

            function showAlert(html) {
                el.alert.innerHTML = html;
                el.alert.hidden = false;
            }

            function showErrors(message, errors) {
                clearErrors();
                const unmapped = [];
                let first = null;
                Object.keys(errors || {}).forEach((key) => {
                    const msg = (errors[key] || [])[0] || '';
                    const input = modal.querySelector('[data-err="' + key + '"]');
                    const box = modal.querySelector('[data-err-box="' + key + '"]');
                    if (input) {
                        input.classList.add('is-invalid');
                        input.setAttribute('aria-invalid', 'true');
                        const div = document.createElement('div');
                        div.className = 'cf-field-error';
                        div.textContent = msg;
                        input.insertAdjacentElement('afterend', div);
                        if (!first) first = input;
                    } else if (box) {
                        box.textContent = msg;
                        box.hidden = false;
                        if (!first) first = box;
                    } else {
                        unmapped.push(msg);
                    }
                });
                let html = '<strong>' + esc(message || 'Revisa los datos marcados.') + '</strong>';
                if (unmapped.length) {
                    html += '<ul>' + unmapped.map((m) => '<li>' + esc(m) + '</li>').join('') + '</ul>';
                }
                showAlert(html);
                if (first && first.scrollIntoView) {
                    first.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    if (first.focus && first.tagName === 'INPUT') first.focus({ preventScroll: true });
                }
            }

            function showWarnings(list) {
                if (!list || !list.length) {
                    el.warnings.hidden = true;
                    el.warnings.innerHTML = '';
                    return;
                }
                el.warnings.innerHTML = '<strong>Guardado. Revisa estos avisos (no impiden guardar):</strong><ul>' +
                    list.map((w) => '<li>' + esc(w.message) + '</li>').join('') + '</ul>';
                el.warnings.hidden = false;
            }

            /* ── API ── */
            async function api(method, body) {
                const res = await fetch(BASE_URL + '/' + state.categoryId + '/filtros', {
                    method: method,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: body ? JSON.stringify(body) : undefined,
                });
                let data = null;
                try { data = await res.json(); } catch (e) { /* respuesta sin JSON */ }
                return { ok: res.ok, status: res.status, data: data };
            }

            function applyServerData(data, opts) {
                opts = opts || {};
                state.serverGroups = data.groups || [];
                state.groups = state.serverGroups.map(makeGroup);
                if (data.inherited) state.inherited = data.inherited;
                if (data.tag_suggestions) state.suggestions = data.tag_suggestions;
                rebuildCounts();
                state.baseline = snapshot();
                state.activeGroup = state.groups.length ? state.groups[state.groups.length - 1].key : null;
                renderInherited();
                renderGroups();
            }

            async function load() {
                state.loading = true;
                state.loaded = false;
                el.loading.hidden = false;
                el.loading.textContent = 'Cargando filtros…';
                el.main.hidden = true;
                updateStatus();
                try {
                    const r = await api('GET');
                    if (!r.ok || !r.data) {
                        throw new Error(r.status === 403
                            ? 'No tienes permiso para ver los filtros de esta categoría.'
                            : 'No se pudieron cargar los filtros (error ' + r.status + ').');
                    }
                    el.title.textContent = 'Filtros técnicos — ' + r.data.category.name;
                    el.path.textContent = r.data.category.path;
                    state.loaded = true;
                    applyServerData(r.data);
                    el.loading.hidden = true;
                    el.main.hidden = false;
                } catch (err) {
                    el.loading.hidden = false;
                    el.loading.innerHTML = esc(err.message || 'Error de red al cargar los filtros.') +
                        ' <button type="button" class="cf-btn" id="cfRetry">Reintentar</button>';
                    $('cfRetry').addEventListener('click', load);
                } finally {
                    state.loading = false;
                    updateStatus();
                }
            }

            async function save() {
                if (state.saving || !isDirty()) return;
                state.saving = true;
                clearErrors();
                showWarnings(null);
                updateStatus();
                const payload = {
                    groups: state.groups.map((g) => ({
                        id: g.id,
                        name: g.name,
                        is_active: g.is_active,
                        options: g.options.map((o) => ({ id: o.id, label: o.label, tag: o.tag, is_active: o.is_active })),
                    })),
                };
                try {
                    const r = await api('PUT', payload);
                    if (r.status === 422 && r.data) {
                        showErrors(r.data.message, r.data.errors);
                    } else if (!r.ok || !r.data || !r.data.success) {
                        const msg = r.status === 403 ? 'No tienes permiso para editar los filtros de esta categoría.'
                            : (r.status === 419 ? 'Tu sesión expiró. Recarga la página e inicia sesión de nuevo.'
                            : 'No se pudo guardar (error ' + r.status + '). Intenta de nuevo.');
                        showAlert('<strong>' + esc(msg) + '</strong>');
                    } else {
                        state.savedAt = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
                        applyServerData(r.data);
                        showWarnings(r.data.warnings);
                    }
                } catch (err) {
                    showAlert('<strong>Error de red al guardar. Revisa tu conexión e intenta de nuevo.</strong>');
                } finally {
                    state.saving = false;
                    updateStatus();
                }
            }

            /* ── Abrir / cerrar ── */
            function open(categoryId, name, opener) {
                state.categoryId = categoryId;
                state.categoryName = name || '';
                state.opener = opener || null;
                state.groups = [];
                state.inherited = [];
                state.suggestions = [];
                state.savedAt = null;
                state.baseline = '';
                el.title.textContent = 'Filtros técnicos — ' + (name || '');
                el.path.textContent = '';
                clearErrors();
                showWarnings(null);
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.remove('is-closing');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                el.body.scrollTop = 0;
                el.close.focus();
                load();
            }

            function closeNow() {
                hideAc();
                modal.classList.add('is-closing');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.remove('is-closing');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                    state.loaded = false;
                    if (state.opener && document.contains(state.opener)) state.opener.focus();
                }, 160);
            }

            function requestClose() {
                if (isDirty() && !window.confirm('Tienes cambios sin guardar en los filtros. ¿Cerrar y descartarlos?')) {
                    return;
                }
                closeNow();
            }

            function isOpen() {
                return modal.style.display === 'flex' && !modal.classList.contains('is-closing');
            }

            /* ── Autocompletado de la etiqueta de producto ── */
            const ac = document.createElement('ul');
            ac.className = 'cf-ac';
            ac.setAttribute('role', 'listbox');
            ac.hidden = true;
            el.inner.appendChild(ac);
            let acInput = null;
            let acIndex = -1;
            let acTimer = null;
            let acReq = 0;

            function hideAc() {
                ac.hidden = true;
                ac.innerHTML = '';
                acIndex = -1;
                if (acInput) acInput.setAttribute('aria-expanded', 'false');
                acInput = null;
                clearTimeout(acTimer);
            }

            function positionAc(input) {
                const ir = input.getBoundingClientRect();
                const cr = el.inner.getBoundingClientRect();
                ac.style.left = (ir.left - cr.left) + 'px';
                ac.style.top = (ir.bottom - cr.top + 2) + 'px';
                ac.style.width = ir.width + 'px';
            }

            function showAc(input, items) {
                if (!items.length) { hideAc(); return; }
                acInput = input;
                acIndex = -1;
                ac.innerHTML = items.map((t, i) =>
                    '<li role="option" id="cfac-' + i + '" data-v="' + esc(t) + '">' + esc(t) + '</li>').join('');
                positionAc(input);
                ac.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            function highlightAc(idx) {
                const items = ac.querySelectorAll('li');
                items.forEach((li, i) => li.classList.toggle('is-active', i === idx));
                acIndex = idx;
            }

            function pickAc(value) {
                if (!acInput) return;
                const input = acInput;
                input.value = value;
                hideAc();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            }

            function queryTags(input) {
                const q = input.value.trim();
                if (!q) { hideAc(); return; }
                const reqId = ++acReq;
                const qn = norm(q);
                const local = state.suggestions.map((s) => s.tag).filter((t) => norm(t).includes(qn));
                const merge = (remote) => {
                    const seen = new Set();
                    const out = [];
                    remote.concat(local).forEach((t) => {
                        const k = norm(t);
                        if (typeof t === 'string' && t && !seen.has(k)) { seen.add(k); out.push(t); }
                    });
                    return out.slice(0, 10);
                };
                fetch(TAGS_URL + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                }).then((r) => (r.ok ? r.json() : []))
                    .catch(() => [])
                    .then((remote) => {
                        if (reqId !== acReq || document.activeElement !== input) return;
                        showAc(input, merge(Array.isArray(remote) ? remote : []));
                    });
            }

            ac.addEventListener('mousedown', (e) => {
                const li = e.target.closest('li');
                if (!li) return;
                e.preventDefault();
                pickAc(li.dataset.v);
            });

            /* ── Eventos: inputs ── */
            el.groups.addEventListener('input', (e) => {
                const input = e.target;
                const f = input.dataset.f;
                if (!f) return;
                const groupEl = input.closest('[data-gk]');
                const group = groupEl ? findGroup(groupEl.dataset.gk) : null;
                if (!group) return;
                state.activeGroup = group.key;
                clearFieldError(input);

                if (f === 'gname') {
                    group.name = input.value;
                } else {
                    const optEl = input.closest('[data-ok]');
                    const opt = optEl ? findOption(group, optEl.dataset.ok) : null;
                    if (!opt) return;
                    if (f === 'label') {
                        opt.label = input.value;
                        if (!opt.touched) {
                            opt.tag = input.value;
                            const tagInput = optEl.querySelector('[data-f="tag"]');
                            tagInput.value = input.value;
                            clearFieldError(tagInput);
                        }
                    } else if (f === 'tag') {
                        opt.tag = input.value;
                        opt.touched = true;
                        queryTags(input);
                    }
                    updateCount(opt.key);
                }
                renderSuggestions();
                updateStatus();
            });

            el.groups.addEventListener('change', (e) => {
                const input = e.target;
                const f = input.dataset.f;
                if (f !== 'gactive' && f !== 'oactive') return;
                const groupEl = input.closest('[data-gk]');
                const group = groupEl ? findGroup(groupEl.dataset.gk) : null;
                if (!group) return;
                state.activeGroup = group.key;
                if (f === 'gactive') {
                    group.is_active = input.checked;
                    groupEl.classList.toggle('is-inactive', !input.checked);
                } else {
                    const opt = findOption(group, input.closest('[data-ok]').dataset.ok);
                    if (opt) opt.is_active = input.checked;
                }
                updateStatus();
            });

            el.groups.addEventListener('focusin', (e) => {
                const groupEl = e.target.closest('[data-gk]');
                if (groupEl) state.activeGroup = groupEl.dataset.gk;
            });

            el.groups.addEventListener('focusout', (e) => {
                if (e.target.classList && e.target.classList.contains('cf-tag-input')) {
                    setTimeout(() => { if (acInput === e.target) hideAc(); }, 120);
                }
            });

            el.groups.addEventListener('keydown', (e) => {
                const input = e.target;
                if (!input.classList || !input.classList.contains('cf-tag-input') || ac.hidden || acInput !== input) return;
                const n = ac.querySelectorAll('li').length;
                if (e.key === 'ArrowDown') { e.preventDefault(); highlightAc((acIndex + 1) % n); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); highlightAc((acIndex - 1 + n) % n); }
                else if (e.key === 'Enter' && acIndex >= 0) {
                    e.preventDefault();
                    pickAc(ac.querySelectorAll('li')[acIndex].dataset.v);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    hideAc();
                }
            });

            /* ── Eventos: acciones estructurales ── */
            function move(arr, from, to) {
                if (to < 0 || to >= arr.length) return false;
                arr.splice(to, 0, arr.splice(from, 1)[0]);
                return true;
            }

            function handleAction(btn) {
                const action = btn.dataset.action;
                const groupEl = btn.closest('[data-gk]');
                const group = groupEl ? findGroup(groupEl.dataset.gk) : null;
                const optEl = btn.closest('[data-ok]');
                const gi = group ? state.groups.indexOf(group) : -1;
                const oi = group && optEl ? group.options.findIndex((o) => o.key === optEl.dataset.ok) : -1;
                clearErrors();
                hideAc();

                switch (action) {
                    case 'add-group': {
                        if (state.groups.length >= MAX_GROUPS) return;
                        const g = makeGroup({ options: [{ label: '' }] });
                        state.groups.push(g);
                        state.activeGroup = g.key;
                        renderGroups('[data-gk="' + g.key + '"] [data-f="gname"]');
                        break;
                    }
                    case 'group-up':
                    case 'group-down': {
                        const to = action === 'group-up' ? gi - 1 : gi + 1;
                        if (move(state.groups, gi, to)) {
                            renderGroups('[data-gk="' + group.key + '"] [data-action="' + action + '"]:not(:disabled)');
                            if (!document.activeElement || document.activeElement === document.body) {
                                const alt = el.groups.querySelector('[data-gk="' + group.key + '"] [data-action="' +
                                    (action === 'group-up' ? 'group-down' : 'group-up') + '"]:not(:disabled)');
                                if (alt) alt.focus();
                            }
                        }
                        break;
                    }
                    case 'group-del': {
                        state.groups.splice(gi, 1);
                        const next = state.groups[Math.min(gi, state.groups.length - 1)];
                        renderGroups(next ? '[data-gk="' + next.key + '"] [data-f="gname"]' : '#cfAddGroup');
                        break;
                    }
                    case 'add-option': {
                        if (group.options.length >= MAX_OPTIONS) return;
                        const o = makeOption({});
                        group.options.push(o);
                        state.activeGroup = group.key;
                        renderGroups('[data-ok="' + o.key + '"] [data-f="label"]');
                        break;
                    }
                    case 'opt-up':
                    case 'opt-down': {
                        const to = action === 'opt-up' ? oi - 1 : oi + 1;
                        if (move(group.options, oi, to)) {
                            const key = optEl.dataset.ok;
                            renderGroups('[data-ok="' + key + '"] [data-action="' + action + '"]:not(:disabled)');
                            if (!document.activeElement || document.activeElement === document.body) {
                                const alt = el.groups.querySelector('[data-ok="' + key + '"] [data-action="' +
                                    (action === 'opt-up' ? 'opt-down' : 'opt-up') + '"]:not(:disabled)');
                                if (alt) alt.focus();
                            }
                        }
                        break;
                    }
                    case 'opt-del': {
                        group.options.splice(oi, 1);
                        const nextOpt = group.options[Math.min(oi, group.options.length - 1)];
                        renderGroups(nextOpt ? '[data-ok="' + nextOpt.key + '"] [data-f="label"]'
                            : '[data-gk="' + group.key + '"] [data-action="add-option"]');
                        break;
                    }
                    case 'chip': {
                        addFromChip(btn.dataset.tag);
                        break;
                    }
                }
            }

            function addFromChip(tag) {
                let group = findGroup(state.activeGroup) || (state.groups.length === 1 ? state.groups[0] : null);
                if (!group && state.groups.length) group = state.groups[state.groups.length - 1];
                if (!group) {
                    group = makeGroup({ options: [] });
                    state.groups.push(group);
                }
                if (group.options.length >= MAX_OPTIONS) {
                    showAlert('<strong>El grupo «' + esc(group.name || 'sin nombre') + '» ya tiene ' + MAX_OPTIONS +
                        ' opciones. Selecciona otro grupo o elimina alguna opción.</strong>');
                    return;
                }
                const o = makeOption({ label: tag, tag: tag });
                group.options.push(o);
                state.activeGroup = group.key;
                renderGroups(group.name ? '[data-ok="' + o.key + '"] [data-f="label"]' : '[data-gk="' + group.key + '"] [data-f="gname"]');
            }

            modal.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-action]');
                if (btn && modal.contains(btn) && !btn.disabled) {
                    handleAction(btn);
                    return;
                }
                if (e.target === modal) requestClose();
            });

            el.close.addEventListener('click', requestClose);
            el.cancel.addEventListener('click', requestClose);
            el.save.addEventListener('click', save);

            /* ── Teclado: Esc y trampa de foco ── */
            document.addEventListener('keydown', (e) => {
                if (!isOpen()) return;
                if (e.key === 'Escape') {
                    if (!ac.hidden) { hideAc(); return; }
                    e.preventDefault();
                    requestClose();
                    return;
                }
                if (e.key === 'Tab') {
                    const focusables = Array.from(el.content.querySelectorAll(
                        'button:not(:disabled), input:not(:disabled), select, textarea, summary, [tabindex]:not([tabindex="-1"])'
                    )).filter((n) => n.offsetParent !== null);
                    if (!focusables.length) return;
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });

            window.addEventListener('beforeunload', (e) => {
                if (isOpen() && isDirty()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            /* ── Botón "Filtros" de cada fila de la tabla ── */
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-category-filters');
                if (!btn) return;
                open(btn.dataset.categoryId, btn.dataset.categoryName, btn);
            });
        })();
    </script>
@endpush
