/* Picker de enlaces genérico del admin — panel flotante que deja elegir un
 * tipo de destino (producto, colección, categoría, marca, página estática o
 * una URL a mano) y buscar/seleccionar un resultado concreto.
 *
 * Generalización de la lógica que antes vivía embebida en
 * resources/js/admin/variable-picker.js (botón "Insertar enlace" de FAQ de
 * Productos, líneas ~327-437 previas a esta extracción) para que OTRAS
 * pantallas del admin (no solo el textarea de respuesta de FAQ) puedan
 * reutilizar el mismo panel. variable-picker.js ahora llama a
 * window.LinkPicker.open(...) en vez de mantener su propia copia de esta
 * lógica.
 *
 * API pública:
 *   window.LinkPicker.open({ anchorEl, onSelect })
 *     - anchorEl: elemento del DOM bajo el cual se posiciona el panel.
 *     - onSelect(url, label): callback al elegir un resultado (o al enviar
 *       una URL personalizada con el tipo "custom").
 *
 * CSS: .admin-link-picker* en resources/css/admin/components/ui-kit.css
 * (cargado admin-wide), reemplazando el antiguo prefijo .pform-link-picker*
 * (específico de Productos).
 */
(function () {
    const LINK_TYPES = [
        { type: 'product', label: 'Producto' },
        { type: 'collection', label: 'Colección' },
        { type: 'category', label: 'Categoría / Catálogo' },
        { type: 'brand', label: 'Marca' },
        { type: 'static_page', label: 'Páginas' },
        { type: 'custom', label: 'URL personalizada' },
    ];

    let panel = null;
    let onSelectCallback = null;
    let searchTimer = null;
    let requestId = 0;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
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
        panel.style.left = Math.round(rect.left) + 'px';
        panel.style.top = Math.round(rect.bottom + 4) + 'px';
    }

    function select(url, label) {
        const callback = onSelectCallback;
        close();
        if (callback) callback(url, label);
    }

    function renderTypeStep() {
        const el = ensurePanel();
        el.innerHTML =
            '<div class="admin-link-picker-title">Insertar enlace hacia…</div>' +
            LINK_TYPES.map((t) => `<button type="button" class="admin-link-picker-type" data-type="${t.type}">${t.label}</button>`).join('');

        el.querySelectorAll('.admin-link-picker-type').forEach((btn) => {
            btn.addEventListener('click', function () {
                if (this.dataset.type === 'custom') {
                    renderCustomStep();
                } else {
                    renderSearchStep(this.dataset.type);
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
            select(url, url);
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
        const typeLabel = LINK_TYPES.find((t) => t.type === type)?.label ?? '';
        el.innerHTML =
            '<div class="admin-link-picker-title">' +
            `<button type="button" class="admin-link-picker-back">←</button> ${typeLabel}` +
            '</div>' +
            '<input type="text" class="pform-input admin-link-picker-search" placeholder="Buscar...">' +
            '<ul class="admin-link-picker-results"></ul>';

        el.querySelector('.admin-link-picker-back').addEventListener('click', renderTypeStep);

        const input = el.querySelector('.admin-link-picker-search');
        const results = el.querySelector('.admin-link-picker-results');

        async function search(term) {
            const myRequestId = ++requestId;
            try {
                const res = await fetch(`/admin/enlaces/buscar?type=${type}&q=${encodeURIComponent(term)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok || myRequestId !== requestId) return;
                const items = await res.json();
                results.innerHTML = items
                    .map((it) => `<li class="admin-link-picker-item" data-label="${escapeHtml(it.label)}" data-url="${escapeHtml(it.url)}">${escapeHtml(it.label)}</li>`)
                    .join('') || '<li class="admin-link-picker-empty">Sin resultados</li>';

                results.querySelectorAll('.admin-link-picker-item').forEach((li) => {
                    // mousedown (no click): dispara antes del blur del input
                    // de búsqueda, que si no cerraría el panel primero.
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        select(this.dataset.url, this.dataset.label);
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

    document.addEventListener('mousedown', function (e) {
        if (panel && panel.classList.contains('is-open') && !e.target.closest('.admin-link-picker')) {
            close();
        }
    });

    window.LinkPicker = {
        open: function ({ anchorEl, onSelect }) {
            onSelectCallback = onSelect;
            ensurePanel().classList.add('is-open');
            position(anchorEl);
            renderTypeStep();
        },
    };
})();
