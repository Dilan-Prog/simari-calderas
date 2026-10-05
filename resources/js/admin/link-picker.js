/* Picker de enlaces genérico del admin — panel flotante que deja elegir un
 * tipo de destino (producto, colección, categoría, subcategoría, categoría
 * hija, servicio, marca, página estática o una URL a mano) y buscar/
 * seleccionar un resultado concreto.
 *
 * Generalización de la lógica que antes vivía embebida en
 * resources/js/admin/variable-picker.js (botón "Insertar enlace" de FAQ de
 * Productos) para que OTRAS pantallas del admin (no solo el textarea de
 * respuesta de FAQ) puedan reutilizar el mismo panel. variable-picker.js
 * llama a window.LinkPicker.open(...) en vez de mantener su propia copia.
 *
 * API pública:
 *
 *   window.LinkPicker.open({ anchorEl, onSelect })           [compatibilidad]
 *     - anchorEl: elemento del DOM bajo el cual se posiciona el panel.
 *     - onSelect(url, label): callback al elegir un resultado (o al enviar
 *       una URL personalizada con el tipo "custom").
 *
 *   window.LinkPicker.openTarget({ anchorEl, onSelect, types? })
 *     - Igual que open() pero entrega un DESTINO de bloque
 *       { type, id, url, label } (los tipos de App\Support\LinkTarget:
 *       collection, service_page, home, product, category, subcategory,
 *       child_category, custom). `types` limita la lista de tipos.
 *
 *   window.LinkPicker.mountField(container, { value, onChange, types? })
 *     - Pinta dentro de `container` un botón que abre el picker, la etiqueta
 *       del destino elegido, un botón para quitarlo y la casilla "Abrir en
 *       pestaña nueva". `value` y lo que recibe onChange es el arreglo
 *       { type, id, url, new_tab } que guarda LinkTarget::normalize() en el
 *       servidor (o null si no hay destino).
 *     - Devuelve { getValue(), setValue(value), destroy() }.
 *     - Para enviarlo en un FormData: appendLink(fd, 'campo', value) →
 *       campo[type], campo[id], campo[url], campo[new_tab]
 *       (window.LinkPicker.appendToFormData).
 *
 * El destino guardado se resuelve a URL al renderizar (no al guardar); aquí
 * solo se repinta su etiqueta pidiendo /admin/enlaces/buscar?type=..&id=..
 * (el resultado trae `broken` si la entidad ya no existe o está inactiva).
 *
 * CSS: .admin-link-picker* y .admin-link-field* en
 * resources/css/admin/components/ui-kit.css (cargado admin-wide).
 */
(function () {
    const SEARCH_URL = '/admin/enlaces/buscar';

    // Tipos del picker "insertar enlace" (FAQ de Productos, editor en vivo).
    const LINK_TYPES = [
        { type: 'product', label: 'Producto' },
        { type: 'collection', label: 'Colección' },
        { type: 'category', label: 'Categoría' },
        { type: 'subcategory', label: 'Subcategoría' },
        { type: 'child_category', label: 'Categoría hija' },
        { type: 'service_page', label: 'Servicio' },
        { type: 'brand', label: 'Marca' },
        { type: 'static_page', label: 'Páginas' },
        { type: 'custom', label: 'URL personalizada' },
    ];

    // Tipos de destino de bloque (espejo de App\Support\LinkTarget::TYPES).
    const TARGET_TYPES = [
        { type: 'collection', label: 'Colección' },
        { type: 'service_page', label: 'Servicio' },
        { type: 'home', label: 'Inicio' },
        { type: 'product', label: 'Producto específico' },
        { type: 'category', label: 'Categoría' },
        { type: 'subcategory', label: 'Subcategoría' },
        { type: 'child_category', label: 'Categoría hija' },
        { type: 'custom', label: 'URL personalizada' },
    ];

    const TYPE_LABELS = {};
    TARGET_TYPES.concat(LINK_TYPES).forEach((t) => { TYPE_LABELS[t.type] = t.label; });

    let panel = null;
    let onSelectCallback = null;
    let panelMode = 'url'; // 'url' | 'target'
    let panelTypes = LINK_TYPES;
    let currentItems = [];
    let searchTimer = null;
    let requestId = 0;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function ensurePanel() {
        if (!panel) {
            panel = document.createElement('div');
            panel.className = 'admin-link-picker';
            document.body.appendChild(panel);
        }
        return panel;
    }

    function close() {
        if (panel) {
            panel.classList.remove('is-open');
            panel.innerHTML = '';
        }
        onSelectCallback = null;
    }

    function position(anchorEl) {
        const rect = anchorEl.getBoundingClientRect();
        const width = 260;
        const estimatedHeight = 320;
        let left = Math.round(rect.left);
        left = Math.max(8, Math.min(left, window.innerWidth - width - 12));
        let top = Math.round(rect.bottom + 4);
        if (top + estimatedHeight > window.innerHeight) {
            top = Math.max(8, Math.round(rect.top - estimatedHeight - 4));
        }
        panel.style.left = left + 'px';
        panel.style.top = top + 'px';
    }

    // item: { type, id, url, label }
    function select(item) {
        const callback = onSelectCallback;
        const mode = panelMode;
        close();
        if (!callback) return;
        if (mode === 'target') {
            callback(item);
        } else {
            callback(item.url, item.label);
        }
    }

    function renderTypeStep() {
        const el = ensurePanel();
        el.innerHTML =
            '<div class="admin-link-picker-title">' +
            (panelMode === 'target' ? 'Destino del enlace…' : 'Insertar enlace hacia…') +
            '</div>' +
            panelTypes.map((t) => `<button type="button" class="admin-link-picker-type" data-type="${t.type}">${escapeHtml(t.label)}</button>`).join('');

        el.querySelectorAll('.admin-link-picker-type').forEach((btn) => {
            btn.addEventListener('click', function () {
                const type = this.dataset.type;
                if (type === 'custom') {
                    renderCustomStep();
                } else if (type === 'home' && panelMode === 'target') {
                    select({ type: 'home', id: null, url: null, label: 'Inicio' });
                } else {
                    renderSearchStep(type);
                }
            });
        });
    }

    function renderCustomStep() {
        const el = ensurePanel();
        el.innerHTML =
            '<div class="admin-link-picker-title">' +
            '<button type="button" class="admin-link-picker-back">←</button> URL personalizada' +
            '</div>' +
            '<input type="text" class="pform-input admin-link-picker-custom-input" placeholder="https://...">' +
            '<button type="button" class="admin-link-picker-custom-submit">Usar</button>';

        el.querySelector('.admin-link-picker-back').addEventListener('click', renderTypeStep);

        const input = el.querySelector('.admin-link-picker-custom-input');
        const submit = el.querySelector('.admin-link-picker-custom-submit');

        function submitCustomUrl() {
            const url = input.value.trim();
            if (!url) return;
            select({ type: 'custom', id: null, url, label: url });
        }

        // mousedown (no click): dispara ANTES del blur que cerraría el panel
        // por el listener global de mousedown-fuera-del-panel.
        submit.addEventListener('mousedown', function (e) {
            e.preventDefault();
            submitCustomUrl();
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitCustomUrl();
            }
        });
        input.focus();
    }

    function renderSearchStep(type) {
        const el = ensurePanel();
        const typeLabel = TYPE_LABELS[type] ?? '';
        el.innerHTML =
            '<div class="admin-link-picker-title">' +
            `<button type="button" class="admin-link-picker-back">←</button> ${escapeHtml(typeLabel)}` +
            '</div>' +
            '<input type="text" class="pform-input admin-link-picker-search" placeholder="Buscar...">' +
            '<ul class="admin-link-picker-results"></ul>';

        el.querySelector('.admin-link-picker-back').addEventListener('click', renderTypeStep);

        const input = el.querySelector('.admin-link-picker-search');
        const results = el.querySelector('.admin-link-picker-results');

        async function search(term) {
            const myRequestId = ++requestId;
            try {
                const res = await fetch(`${SEARCH_URL}?type=${encodeURIComponent(type)}&q=${encodeURIComponent(term)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                if (!res.ok || myRequestId !== requestId) return;
                currentItems = await res.json();
                results.innerHTML = currentItems
                    .map((it, i) => `<li class="admin-link-picker-item" data-idx="${i}">${escapeHtml(it.label)}</li>`)
                    .join('') || '<li class="admin-link-picker-empty">Sin resultados</li>';

                results.querySelectorAll('.admin-link-picker-item').forEach((li) => {
                    // mousedown (no click): dispara antes del blur del input
                    // de búsqueda, que si no cerraría el panel primero.
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        const it = currentItems[Number(this.dataset.idx)];
                        if (it) select({ type, id: it.id ?? null, url: it.url, label: it.label });
                    });
                });
            } catch (err) {
                console.error('Error buscando destino de enlace:', err);
            }
        }

        input.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => search(this.value.trim()), 250);
        });
        input.focus();
        search('');
    }

    function openPanel({ anchorEl, onSelect, mode, types }) {
        onSelectCallback = onSelect;
        panelMode = mode;
        panelTypes = types && types.length ? types : (mode === 'target' ? TARGET_TYPES : LINK_TYPES);
        ensurePanel().classList.add('is-open');
        position(anchorEl);
        renderTypeStep();
    }

    document.addEventListener('mousedown', function (e) {
        if (panel && panel.classList.contains('is-open') && !e.target.closest('.admin-link-picker')) {
            close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel && panel.classList.contains('is-open')) {
            close();
        }
    });

    /* ── Campo reutilizable de destino ─────────────────────────────── */

    const labelCache = {};

    // Etiqueta legible de un destino guardado (consulta el servidor para los
    // que apuntan a una entidad). Devuelve { text, broken }.
    async function describeTarget(value) {
        if (!value || !value.type) return null;
        const typeLabel = TYPE_LABELS[value.type] ?? value.type;

        if (value.type === 'home') return { text: 'Inicio', broken: false };
        if (value.type === 'custom') return { text: value.url || '(sin URL)', broken: !value.url };
        if (!value.id) return { text: typeLabel + ' (sin elegir)', broken: true };

        const key = value.type + ':' + value.id;
        if (!labelCache[key]) {
            labelCache[key] = fetch(`${SEARCH_URL}?type=${encodeURIComponent(value.type)}&id=${encodeURIComponent(value.id)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            })
                .then((res) => (res.ok ? res.json() : []))
                .then((rows) => (rows[0] ? { text: rows[0].label, broken: !!rows[0].broken } : { text: '(no encontrado)', broken: true }))
                .catch(() => ({ text: '(sin conexión)', broken: false }));
        }
        const info = await labelCache[key];

        return { text: `${typeLabel}: ${info.text}`, broken: info.broken };
    }

    function normalizeValue(v) {
        if (!v || !v.type) return null;
        return {
            type: v.type,
            id: v.id == null || v.id === '' ? null : Number(v.id),
            url: v.url || null,
            new_tab: !!v.new_tab,
        };
    }

    function mountField(container, options) {
        options = options || {};
        let value = normalizeValue(options.value);
        const onChange = typeof options.onChange === 'function' ? options.onChange : function () {};
        let renderToken = 0;

        container.classList.add('admin-link-field');
        container.innerHTML =
            '<div class="admin-link-field__row">' +
            '<button type="button" class="admin-link-field__btn">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>' +
            '<span class="admin-link-field__label"></span>' +
            '</button>' +
            '<button type="button" class="admin-link-field__clear" title="Quitar destino" aria-label="Quitar destino">&times;</button>' +
            '</div>' +
            '<label class="admin-link-field__newtab"><input type="checkbox"> Abrir en pestaña nueva</label>';

        const btn = container.querySelector('.admin-link-field__btn');
        const labelEl = container.querySelector('.admin-link-field__label');
        const clearBtn = container.querySelector('.admin-link-field__clear');
        const newTabWrap = container.querySelector('.admin-link-field__newtab');
        const newTabInput = newTabWrap.querySelector('input');

        async function render() {
            const token = ++renderToken;
            clearBtn.style.display = value ? '' : 'none';
            newTabWrap.style.display = value ? '' : 'none';
            newTabInput.checked = !!(value && value.new_tab);
            btn.classList.remove('is-broken', 'has-value');

            if (!value) {
                labelEl.textContent = 'Elegir destino…';
                return;
            }

            btn.classList.add('has-value');
            labelEl.textContent = 'Cargando…';
            const info = await describeTarget(value);
            if (token !== renderToken) return;
            labelEl.textContent = info.text + (info.broken ? ' — enlace roto' : '');
            btn.classList.toggle('is-broken', info.broken);
            btn.title = labelEl.textContent;
        }

        function emit() {
            onChange(value ? Object.assign({}, value) : null);
        }

        btn.addEventListener('click', function () {
            openPanel({
                anchorEl: btn,
                mode: 'target',
                types: options.types
                    ? TARGET_TYPES.filter((t) => options.types.includes(t.type))
                    : null,
                onSelect: function (item) {
                    const previousNewTab = value ? value.new_tab : false;
                    value = normalizeValue({
                        type: item.type,
                        id: item.id,
                        url: item.type === 'custom' ? item.url : null,
                        new_tab: previousNewTab,
                    });
                    if (value && item.label) {
                        // Evita volver a consultar el servidor por la etiqueta recién elegida.
                        labelCache[value.type + ':' + value.id] = Promise.resolve({ text: item.label, broken: false });
                    }
                    render();
                    emit();
                },
            });
        });

        clearBtn.addEventListener('click', function () {
            value = null;
            render();
            emit();
        });

        newTabInput.addEventListener('change', function () {
            if (!value) return;
            value.new_tab = newTabInput.checked;
            emit();
        });

        render();

        return {
            getValue: () => (value ? Object.assign({}, value) : null),
            setValue: function (v) {
                value = normalizeValue(v);
                render();
            },
            destroy: function () {
                container.innerHTML = '';
                container.classList.remove('admin-link-field');
            },
        };
    }

    // Serializa un destino como campos de formulario campo[type]/[id]/[url]/[new_tab].
    // Sin destino manda campo[type]='' para que el servidor lo trate como "sin enlace"
    // (y limpie el link_url legado).
    function appendToFormData(formData, name, value) {
        const v = normalizeValue(value);
        formData.append(name + '[type]', v ? v.type : '');
        if (!v) return;
        if (v.id != null) formData.append(name + '[id]', String(v.id));
        if (v.url) formData.append(name + '[url]', v.url);
        formData.append(name + '[new_tab]', v.new_tab ? '1' : '0');
    }

    window.LinkPicker = {
        open: function ({ anchorEl, onSelect }) {
            openPanel({ anchorEl, onSelect, mode: 'url' });
        },
        openTarget: function ({ anchorEl, onSelect, types }) {
            openPanel({ anchorEl, onSelect, mode: 'target', types });
        },
        mountField: mountField,
        appendToFormData: appendToFormData,
        describeTarget: describeTarget,
        TARGET_TYPES: TARGET_TYPES,
    };
})();
