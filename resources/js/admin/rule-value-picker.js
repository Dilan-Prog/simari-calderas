/* Picker de "escribe y busca" para el valor de las condiciones automáticas
 * de Colecciones (Etiqueta/Categoría/Marca). Mismo patrón de menú flotante
 * que spec-name-autocomplete.js (delegado sobre `document`, funciona en
 * filas clonadas por addRuleRow() sin tocar esa función), con dos modos
 * según de dónde salen los candidatos:
 *   - "ajax":  busca en el servidor (Etiqueta -- el universo de tags no
 *              está precargado en el DOM, viene de products.tags).
 *   - "local": filtra en memoria las <option> de un <select> ya renderizado
 *              server-side (Categoría/Marca -- la lista completa ya está
 *              en el HTML, no hace falta ida y vuelta al servidor).
 * En ambos modos, seleccionar un ítem termina escribiendo en el elemento
 * real que se envía como rule_value[] (el input de texto para Etiqueta, el
 * <select> oculto para Categoría/Marca) -- el picker nunca cambia qué se
 * envía, solo cómo se elige.
 */
(function () {
    const SELECTOR = '.rvp-input';

    let dropdown = null;
    let activeInput = null;
    let activeIndex = -1;
    let currentItems = [];
    let debounceTimer = null;
    let requestId = 0;

    function ensureDropdown() {
        if (!dropdown) {
            dropdown = document.createElement('ul');
            dropdown.className = 'rvp-float-menu';
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

    // items: array de string (modo ajax) o {value,label} (modo local)
    function renderItems(input, items) {
        const menu = ensureDropdown();
        currentItems = items;
        activeIndex = -1;

        if (!items.length) {
            closeDropdown();
            return;
        }

        menu.innerHTML = items
            .map((item) => `<li class="rvp-float-menu-item">${escapeHtml(typeof item === 'string' ? item : item.label)}</li>`)
            .join('');
        requestAnimationFrame(() => requestAnimationFrame(() => positionDropdown(input)));
        menu.classList.add('is-open');

        Array.from(menu.children).forEach((li, i) => {
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectItem(input, items[i]);
            });
        });
    }

    function selectItem(input, item) {
        if (typeof item === 'string') {
            // Modo ajax (Etiqueta): el input visible ES el que se envía.
            input.value = item;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            // Modo local (Categoría/Marca): el input visible es solo
            // búsqueda -- el valor real va al <select> oculto emparejado.
            const combo = input.closest('.rvp-combo');
            const source = combo ? combo.querySelector(input.dataset.rvpSourceSelector) : null;
            if (source) {
                source.value = item.value;
                source.dispatchEvent(new Event('change', { bubbles: true }));
            }
            input.value = item.label;
        }
        closeDropdown();
    }

    async function fetchAjaxSuggestions(input, term) {
        const myRequestId = ++requestId;
        try {
            const res = await fetch(`${input.dataset.rvpEndpoint}?q=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok || myRequestId !== requestId || activeInput !== input) return;
            renderItems(input, await res.json());
        } catch (err) {
            console.error('Error fetching rule value suggestions:', err);
        }
    }

    function filterLocalSuggestions(input, term) {
        const combo = input.closest('.rvp-combo');
        const source = combo ? combo.querySelector(input.dataset.rvpSourceSelector) : null;
        if (!source) return;

        const items = Array.from(source.options)
            .filter((opt) => opt.value !== '')
            .map((opt) => ({ value: opt.value, label: opt.textContent }));

        const termLower = term.trim().toLowerCase();
        const filtered = termLower === ''
            ? items
            : items.filter((item) => item.label.toLowerCase().includes(termLower));

        renderItems(input, filtered);
    }

    function fetchSuggestions(input, term) {
        if (input.dataset.rvpMode === 'local') {
            filterLocalSuggestions(input, term);
        } else {
            fetchAjaxSuggestions(input, term);
        }
    }

    document.addEventListener('focusin', function (e) {
        if (!e.target.matches(SELECTOR) || e.target.disabled) return;
        activeInput = e.target;
        fetchSuggestions(e.target, e.target.value.trim());
    });

    document.addEventListener('input', function (e) {
        if (!e.target.matches(SELECTOR) || e.target.disabled) return;
        activeInput = e.target;

        if (e.target.dataset.rvpMode === 'local') {
            filterLocalSuggestions(e.target, e.target.value.trim());
            return;
        }

        clearTimeout(debounceTimer);
        const term = e.target.value.trim();
        debounceTimer = setTimeout(() => fetchAjaxSuggestions(e.target, term), 250);
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
