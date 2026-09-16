/* Selector de "Producto Canónico" para SEO — reemplaza el antiguo
 * <input type="url"> de texto libre por un buscador en vivo (nombre, SKU,
 * SKU proveedor, modelo, marca o slug) que deja elegir un producto ya
 * existente como destino de la señal canónica de Google, con una
 * alternativa para escribir una URL personalizada a mano cuando el destino
 * no es un producto del catálogo.
 *
 * Dos modos de uso:
 *
 *   - window.CanonicalPicker.mount(containerEl, options): monta el widget
 *     COMPLETO (buscador + chip de "seleccionado" + toggle a URL
 *     personalizada) y mantiene sincronizados dos <input> reales
 *     (canonical_product_id / canonical_url, o los nombres que se pasen en
 *     options) para que un submit normal del <form> los recoja tal cual.
 *     Usado en Crear/Editar Producto — ver
 *     resources/views/admin/products/partials/_canonical_picker.blade.php.
 *
 *   - window.CanonicalPicker.mountSearchOnly(containerEl, options): monta
 *     SOLO el buscador (mismo fetch/debounce/render que el modo anterior),
 *     disparando options.onSelect(product) al elegir uno en vez de escribir
 *     en inputs propios. Usado por el popover de "Producto/URL Canónica"
 *     de Editar en lote (_bulk_edit_scripts.blade.php), que maneja su
 *     propio resumen de "seleccionado" y su propio toggle de URL
 *     personalizada por fuera de este módulo (esa pantalla no tiene un
 *     <form> normal que recoja inputs con name=, todo viaja vía
 *     setPendingChange()).
 *
 * Referencia de arquitectura: link-picker.js (namespace global en window,
 * fetch + render de resultados). Referencia de UX de búsqueda en vivo:
 * resources/views/admin/quotes/edit.blade.php ("inline-product-search":
 * debounce, mínimo de caracteres antes de buscar, estado de carga/vacío,
 * manejo de sesión expirada).
 *
 * CSS: .cp-* en resources/css/admin/pages/product-form.css (cargado
 * admin-wide vía admin/app.css, así que también alcanza a bulk_edit.blade.php).
 */
(function () {
    // Contrato acordado con el backend (ver plan): GET a esta ruta con
    // ?q=&exclude_id=, responde un ARRAY (no envuelto) de hasta 15
    // resultados {id, name, sku, model, slug, brand}.
    const SEARCH_URL = '/admin/productos/canonica/buscar';
    const MIN_QUERY_LENGTH = 2;
    const DEBOUNCE_MS = 300;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    // Línea de meta-datos mostrada bajo el nombre: modelo · SKU · marca.
    function formatMeta(product) {
        return [product.model, product.sku, product.brand]
            .filter(Boolean)
            .map(escapeHtml)
            .join(' &middot; ');
    }

    async function fetchResults(query, excludeId) {
        const url = new URL(SEARCH_URL, window.location.origin);
        url.searchParams.set('q', query);
        if (excludeId) url.searchParams.set('exclude_id', excludeId);

        const res = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });

        // FIX (mismo patrón que inline-product-search de Cotizaciones):
        // /admin/* envuelve toda ruta con el middleware 'auth' además del
        // 'permission' propio — si la sesión expiró, responde 401 real (no
        // 419) porque fetch() cuenta como petición AJAX.
        if (res.status === 401) return 'AUTH_EXPIRED';
        if (!res.ok) throw new Error('Server error searching canonical products');

        const data = await res.json();
        return Array.isArray(data) ? data : [];
    }

    /* ── Buscador reutilizable: input + dropdown de resultados ──
       Construye el DOM y el ciclo fetch/debounce/render una sola vez;
       mount() y mountSearchOnly() lo insertan donde corresponda. Devuelve
       un controller con focus()/setExcludeId()/destroy(). */
    function buildSearchBox({ excludeId, placeholder, onSelect }) {
        let debounceTimer = null;
        let requestSeq = 0;
        let currentExcludeId = excludeId || null;

        const root = document.createElement('div');
        root.className = 'cp-search';
        root.innerHTML =
            '<input type="text" class="pform-input cp-search-input" autocomplete="off" placeholder="' +
            escapeHtml(placeholder || 'Buscar por SKU, nombre, modelo o marca...') + '">' +
            '<ul class="cp-search-results"></ul>';

        const input = root.querySelector('.cp-search-input');
        const list = root.querySelector('.cp-search-results');

        function hideList() {
            list.classList.remove('is-open');
            list.innerHTML = '';
        }

        function renderItems(items) {
            if (items === 'AUTH_EXPIRED') {
                list.innerHTML = '<li class="cp-search-empty">Tu sesión expiró. Recarga la página para seguir buscando.</li>';
                list.classList.add('is-open');
                return;
            }

            if (!items.length) {
                list.innerHTML = '<li class="cp-search-empty">Sin resultados.</li>';
                list.classList.add('is-open');
                return;
            }

            list.innerHTML = items.map((p) => (
                '<li class="cp-search-item" data-id="' + escapeHtml(p.id) + '">' +
                    '<div class="cp-search-item-main">' +
                        '<span class="cp-search-item-name">' + escapeHtml(p.name) + '</span>' +
                        '<span class="cp-search-item-meta">' + formatMeta(p) + '</span>' +
                    '</div>' +
                    (p.slug ? '<span class="cp-search-item-slug">' + escapeHtml(p.slug) + '</span>' : '') +
                '</li>'
            )).join('');
            list.classList.add('is-open');

            list.querySelectorAll('.cp-search-item').forEach((li, i) => {
                // mousedown (no click): dispara ANTES del blur del input de
                // búsqueda, que si no cerraría el dropdown primero — mismo
                // truco que link-picker.js.
                li.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    hideList();
                    input.value = '';
                    onSelect(items[i]);
                });
            });
        }

        async function search(term) {
            const mySeq = ++requestSeq;
            try {
                const items = await fetchResults(term, currentExcludeId);
                if (mySeq !== requestSeq) return;
                renderItems(items);
            } catch (err) {
                console.error('Error buscando producto canónico:', err);
                if (mySeq !== requestSeq) return;
                list.innerHTML = '<li class="cp-search-empty">Error de conexión al buscar.</li>';
                list.classList.add('is-open');
            }
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const term = this.value.trim();
            if (term.length < MIN_QUERY_LENGTH) { hideList(); return; }
            debounceTimer = setTimeout(() => search(term), DEBOUNCE_MS);
        });

        input.addEventListener('focus', function () {
            const term = this.value.trim();
            if (term.length >= MIN_QUERY_LENGTH) search(term);
        });

        function outsideClick(e) {
            if (!root.contains(e.target)) hideList();
        }
        document.addEventListener('mousedown', outsideClick);

        return {
            root,
            focus() { input.focus(); },
            setExcludeId(id) { currentExcludeId = id || null; },
            destroy() {
                clearTimeout(debounceTimer);
                document.removeEventListener('mousedown', outsideClick);
            },
        };
    }

    // Si containerEl ya tenía un widget montado (p. ej. el modal de Editar
    // en lote reabriéndose para otra fila), límpialo primero para no
    // acumular listeners de "clic afuera" en document.
    function destroyPrevious(containerEl) {
        if (containerEl._cpDestroy) {
            try { containerEl._cpDestroy(); } catch (e) { /* noop */ }
            containerEl._cpDestroy = null;
        }
    }

    /* ── Modo 1: montaje completo para Crear/Editar Producto ── */
    function mount(containerEl, options) {
        destroyPrevious(containerEl);

        const opts = Object.assign({
            excludeId: null,
            initialProduct: null,
            initialCustomUrl: '',
            productIdInputName: 'canonical_product_id',
            urlInputName: 'canonical_url',
            productIdInputId: null,
            urlInputId: null,
            formId: null,
        }, options || {});

        containerEl.innerHTML = '';
        containerEl.classList.add('cp-picker');

        let mode = opts.initialProduct ? 'product' : (opts.initialCustomUrl ? 'custom' : 'none');
        let selectedProduct = opts.initialProduct || null;

        // Los dos inputs realmente enviados con el <form> — viven fuera del
        // <form> físico (igual que el resto de campos SEO), por eso llevan
        // form="..." cuando se pasa formId, mismo patrón que el resto de
        // #pform* de esta pantalla.
        const productIdInput = document.createElement('input');
        productIdInput.type = 'hidden';
        productIdInput.name = opts.productIdInputName;
        if (opts.productIdInputId) productIdInput.id = opts.productIdInputId;
        if (opts.formId) productIdInput.setAttribute('form', opts.formId);

        const urlInput = document.createElement('input');
        urlInput.name = opts.urlInputName;
        urlInput.className = 'pform-input cp-picker-custom-input';
        urlInput.placeholder = 'https://equitermindustries.com.mx/producto/otro-producto-similar';
        urlInput.maxLength = 255;
        if (opts.urlInputId) urlInput.id = opts.urlInputId;
        if (opts.formId) urlInput.setAttribute('form', opts.formId);

        const searchWrap = document.createElement('div');
        searchWrap.className = 'cp-picker-search-wrap';

        const selectedWrap = document.createElement('div');
        selectedWrap.className = 'cp-picker-selected';
        selectedWrap.style.display = 'none';
        selectedWrap.innerHTML =
            '<div class="cp-picker-selected-info">' +
                '<strong class="cp-picker-selected-name"></strong>' +
                '<span class="cp-picker-selected-meta"></span>' +
            '</div>' +
            '<button type="button" class="pform-btn outline cp-picker-change-btn">Cambiar</button>';

        const toggleRow = document.createElement('p');
        toggleRow.className = 'cp-picker-toggle-row';
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'cp-picker-toggle-link';
        toggleRow.appendChild(toggleBtn);

        containerEl.appendChild(selectedWrap);
        containerEl.appendChild(searchWrap);
        containerEl.appendChild(urlInput);
        containerEl.appendChild(toggleRow);
        containerEl.appendChild(productIdInput);

        const searchBox = buildSearchBox({
            excludeId: opts.excludeId,
            onSelect(product) {
                selectedProduct = product;
                mode = 'product';
                render();
            },
        });
        searchWrap.appendChild(searchBox.root);

        selectedWrap.querySelector('.cp-picker-change-btn').addEventListener('click', function () {
            selectedProduct = null;
            mode = 'none';
            render();
            searchBox.focus();
        });

        toggleBtn.addEventListener('click', function () {
            if (mode === 'custom') {
                mode = selectedProduct ? 'product' : 'none';
                urlInput.value = '';
            } else {
                mode = 'custom';
                selectedProduct = null;
            }
            render();
        });

        function render() {
            searchWrap.style.display = (mode === 'none') ? '' : 'none';
            selectedWrap.style.display = (mode === 'product') ? '' : 'none';
            urlInput.style.display = (mode === 'custom') ? '' : 'none';
            urlInput.type = (mode === 'custom') ? 'url' : 'hidden';

            if (mode === 'product' && selectedProduct) {
                selectedWrap.querySelector('.cp-picker-selected-name').textContent = selectedProduct.name || '';
                selectedWrap.querySelector('.cp-picker-selected-meta').innerHTML =
                    formatMeta(selectedProduct) +
                    (selectedProduct.slug ? ' &middot; <em>' + escapeHtml(selectedProduct.slug) + '</em>' : '');
            }

            productIdInput.value = (mode === 'product' && selectedProduct) ? selectedProduct.id : '';
            if (mode !== 'custom') urlInput.value = '';

            toggleBtn.textContent = (mode === 'custom')
                ? 'Buscar un producto en su lugar'
                : 'Usar una URL personalizada en su lugar';
        }

        if (mode === 'custom') urlInput.value = opts.initialCustomUrl || '';
        render();

        containerEl._cpDestroy = function () {
            searchBox.destroy();
        };

        return { destroy: containerEl._cpDestroy };
    }

    /* ── Modo 2: solo el buscador, para el popover de Editar en lote ── */
    function mountSearchOnly(containerEl, options) {
        destroyPrevious(containerEl);

        const opts = Object.assign({ excludeId: null, onSelect: function () {} }, options || {});

        containerEl.innerHTML = '';
        containerEl.classList.add('cp-picker-search-only');

        const searchBox = buildSearchBox({
            excludeId: opts.excludeId,
            onSelect: opts.onSelect,
        });
        containerEl.appendChild(searchBox.root);

        containerEl._cpDestroy = function () {
            searchBox.destroy();
        };

        return {
            focus: searchBox.focus,
            setExcludeId: searchBox.setExcludeId,
            destroy: containerEl._cpDestroy,
        };
    }

    window.CanonicalPicker = { mount, mountSearchOnly };
})();
