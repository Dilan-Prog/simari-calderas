/* Autocompletado de nombres de especificación (catálogo permanente en
 * product_spec_names). Se adjunta por delegación de eventos sobre
 * `document`, así que funciona igual en filas creadas en el momento por
 * addSpecRow()/specsAddRow() (crear/editar producto y el popover de
 * edición en lote) sin tener que tocar esas funciones.
 */
(function () {
    const SELECTOR = 'input[name="spec_key[]"], .bulk-spec-key';
    const SUGGESTIONS_URL = '/admin/productos/especificaciones/buscar';

    let dropdown = null;
    let activeInput = null;
    let activeIndex = -1;
    let currentItems = [];
    let debounceTimer = null;
    let requestId = 0;

    function ensureDropdown() {
        if (!dropdown) {
            dropdown = document.createElement('ul');
            dropdown.className = 'pform-float-menu';
            document.body.appendChild(dropdown);
        }
        return dropdown;
    }

    function closeDropdown() {
        if (dropdown) {
            dropdown.classList.remove('is-open');
            dropdown.innerHTML = '';
        }
        activeIndex = -1;
        currentItems = [];
    }

    function positionDropdown(input) {
        const rect = input.getBoundingClientRect();
        dropdown.style.left = rect.left + 'px';
        dropdown.style.top = (rect.bottom + 4) + 'px';
        dropdown.style.width = Math.max(rect.width, 180) + 'px';
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderItems(input, items) {
        const menu = ensureDropdown();
        currentItems = items;
        activeIndex = -1;

        if (!items.length) {
            closeDropdown();
            return;
        }

        menu.innerHTML = items
            .map((name) => `<li class="pform-float-menu-item">${escapeHtml(name)}</li>`)
            .join('');
        positionDropdown(input);
        menu.classList.add('is-open');

        Array.from(menu.children).forEach((li, i) => {
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectItem(input, items[i]);
            });
        });
    }

    function selectItem(input, name) {
        input.value = name;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        closeDropdown();
    }

    async function fetchSuggestions(input, term) {
        const myRequestId = ++requestId;
        try {
            const res = await fetch(`${SUGGESTIONS_URL}?q=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok || myRequestId !== requestId || activeInput !== input) return;
            renderItems(input, await res.json());
        } catch (err) {
            console.error('Error fetching spec name suggestions:', err);
        }
    }

    document.addEventListener('focusin', function (e) {
        if (!e.target.matches(SELECTOR)) return;
        activeInput = e.target;
        fetchSuggestions(e.target, e.target.value.trim());
    });

    document.addEventListener('input', function (e) {
        if (!e.target.matches(SELECTOR)) return;
        activeInput = e.target;
        clearTimeout(debounceTimer);
        const term = e.target.value.trim();
        debounceTimer = setTimeout(() => fetchSuggestions(e.target, term), 250);
    });

    document.addEventListener('keydown', function (e) {
        if (!dropdown || !dropdown.classList.contains('is-open') || activeInput !== e.target) return;
        const items = Array.from(dropdown.children);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            items.forEach((it, i) => it.classList.toggle('is-active', i === activeIndex));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            items.forEach((it, i) => it.classList.toggle('is-active', i === activeIndex));
        } else if (e.key === 'Escape') {
            closeDropdown();
        } else if (e.key === 'Enter' && activeIndex >= 0 && currentItems[activeIndex]) {
            e.preventDefault();
            selectItem(e.target, currentItems[activeIndex]);
        }
    });

    document.addEventListener('mousedown', function (e) {
        if (dropdown && !e.target.matches(SELECTOR) && !dropdown.contains(e.target)) {
            closeDropdown();
        }
    });

    window.addEventListener(
        'scroll',
        function () {
            if (activeInput && dropdown && dropdown.classList.contains('is-open')) {
                positionDropdown(activeInput);
            }
        },
        true
    );
})();
