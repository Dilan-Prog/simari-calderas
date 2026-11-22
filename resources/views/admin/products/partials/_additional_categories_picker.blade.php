{{-- Selector con búsqueda de "Categorías adicionales" (chips). Lo usan
     Crear/Editar producto y el modal de acción masiva del listado.

     Variables del caller:
       - $acpId (string): id único del contenedor.
       - $acpSelected (int[]): ids ya elegidos (Editar / old()).
       - $acpInputName (string|null): nombre de los <input hidden> por chip
         (ej. 'additional_category_ids[]'). null = no genera inputs (el modal
         masivo lee la selección con window.AdditionalCategoryPicker.get()).
       - $acpPrincipalInputId (string|null): id del <input> con la categoría
         principal; esa categoría aparece deshabilitada y se quita de los
         chips si el admin la elige como principal.
       - $acpOptions (array|null): [['id','label','active']]; si no se pasa se
         calcula con App\Support\CategoryPaths (una sola consulta).

     Estilos y JS propios e inline (@once): no dependen de `npm run build`. --}}
@php
    $acpOptions = $acpOptions ?? (new \App\Support\CategoryPaths())->options();
    $acpSelected = array_values(array_unique(array_map('intval', (array) ($acpSelected ?? []))));
@endphp

<div class="acp" id="{{ $acpId }}" data-acp
    data-name="{{ $acpInputName ?? '' }}"
    data-principal-input="{{ $acpPrincipalInputId ?? '' }}"
    data-selected='@json($acpSelected)'>
    <script type="application/json" class="acp-options">@json($acpOptions)</script>
    @if (!empty($acpInputName))
        {{-- Marca que el formulario SÍ trae este campo: sin ella, quitar todos
             los chips no enviaría nada y el servidor no sabría si debe vaciar. --}}
        <input type="hidden" name="additional_categories_present" value="1">
    @endif
    <div class="acp-chips" aria-live="polite"></div>
    <div class="acp-search">
        <input type="search" class="acp-input" placeholder="Buscar categoría para agregar..." autocomplete="off"
            aria-label="Buscar categoría adicional" enterkeyhint="done">
        <ul class="acp-list" role="listbox" hidden></ul>
    </div>
</div>

@once
    <style>
        .acp { position: relative; text-align: left; }
        .acp-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .acp-chips:empty { display: none; }
        .acp-chip {
            display: inline-flex; align-items: center; gap: 6px; max-width: 100%;
            padding: 4px 6px 4px 10px; background: #e0e7ff; color: #3730a3;
            border-radius: 16px; font-size: 13px; line-height: 1.3;
        }
        .acp-chip-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
        .acp-chip-remove {
            flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px; border: 0; border-radius: 50%; background: transparent;
            color: inherit; font-size: 16px; line-height: 1; cursor: pointer; padding: 0;
        }
        .acp-chip-remove:hover, .acp-chip-remove:focus-visible { background: #c7d2fe; outline: none; }
        .acp-input {
            width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 14px; background: #fff; color: #111827; font-family: inherit;
        }
        .acp-input:focus { outline: none; border-color: var(--secondary-color, #0054ff); box-shadow: 0 0 0 3px rgba(0, 84, 255, .12); }
        .acp-list {
            list-style: none; margin: 4px 0 0; padding: 4px; max-height: 240px; overflow-y: auto;
            border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .08); -webkit-overflow-scrolling: touch;
        }
        .acp-option {
            padding: 8px 10px; border-radius: 6px; font-size: 13.5px; color: #374151; cursor: pointer;
            display: flex; justify-content: space-between; gap: 8px;
        }
        .acp-option.is-active, .acp-option:hover { background: #eef2ff; color: #3730a3; }
        .acp-option[aria-disabled="true"] { color: #9ca3af; cursor: not-allowed; background: transparent; }
        .acp-option-note { flex: 0 0 auto; font-size: 12px; color: #9ca3af; }
        .acp-empty { padding: 8px 10px; font-size: 13px; color: #6b7280; }
        @media (max-width: 768px) {
            /* 16px evita el zoom automático de iOS al enfocar el campo. */
            .acp-input { font-size: 16px; padding: 11px 12px; }
            .acp-option { padding: 11px 10px; font-size: 14.5px; }
            .acp-chip { font-size: 14px; padding: 6px 6px 6px 12px; }
            .acp-chip-remove { width: 28px; height: 28px; }
            .acp-list { max-height: 50vh; }
        }
    </style>
    <script>
        (function () {
            if (window.AdditionalCategoryPicker) return;

            const MAX_RESULTS = 60;
            const norm = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
            const states = new WeakMap();

            function principalId(state) {
                const input = state.principalInputId ? document.getElementById(state.principalInputId) : null;
                return input && input.value ? String(input.value) : '';
            }

            function renderChips(state) {
                const box = state.el.querySelector('.acp-chips');
                box.innerHTML = '';
                state.selected.forEach((id) => {
                    const opt = state.byId.get(id);
                    if (!opt) return;
                    const chip = document.createElement('span');
                    chip.className = 'acp-chip';
                    chip.title = opt.label;
                    const label = document.createElement('span');
                    label.className = 'acp-chip-label';
                    label.textContent = opt.label + (opt.active ? '' : ' (inactiva)');
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'acp-chip-remove';
                    btn.setAttribute('aria-label', 'Quitar ' + opt.label);
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', () => remove(state, id));
                    chip.append(label, btn);
                    if (state.name) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = state.name;
                        hidden.value = id;
                        chip.appendChild(hidden);
                    }
                    box.appendChild(chip);
                });
            }

            function renderList(state) {
                const list = state.el.querySelector('.acp-list');
                const words = norm(state.input.value).split(/\s+/).filter(Boolean);
                const principal = principalId(state);
                const matches = state.options.filter((o) => {
                    if (state.selected.includes(String(o.id))) return false;
                    return words.every((w) => o._n.includes(w));
                });
                list.innerHTML = '';
                state.visible = [];
                matches.slice(0, MAX_RESULTS).forEach((o) => {
                    const li = document.createElement('li');
                    li.className = 'acp-option';
                    li.setAttribute('role', 'option');
                    li.dataset.id = o.id;
                    const text = document.createElement('span');
                    text.textContent = o.label;
                    li.appendChild(text);
                    const isPrincipal = String(o.id) === principal;
                    if (isPrincipal || !o.active) {
                        const note = document.createElement('span');
                        note.className = 'acp-option-note';
                        note.textContent = isPrincipal ? 'principal' : 'inactiva';
                        li.appendChild(note);
                    }
                    if (isPrincipal) {
                        li.setAttribute('aria-disabled', 'true');
                    } else {
                        state.visible.push(li);
                    }
                    li.addEventListener('mousedown', (e) => e.preventDefault());
                    li.addEventListener('click', () => {
                        if (isPrincipal) return;
                        add(state, String(o.id));
                        state.input.value = '';
                        renderList(state);
                        state.input.focus();
                    });
                    list.appendChild(li);
                });
                if (!matches.length) {
                    const li = document.createElement('li');
                    li.className = 'acp-empty';
                    li.textContent = 'Sin coincidencias.';
                    list.appendChild(li);
                } else if (matches.length > MAX_RESULTS) {
                    const li = document.createElement('li');
                    li.className = 'acp-empty';
                    li.textContent = 'Escribe para ver más (' + matches.length + ' coincidencias).';
                    list.appendChild(li);
                }
                state.activeIndex = -1;
            }

            function setActive(state, idx) {
                state.visible.forEach((li) => li.classList.remove('is-active'));
                if (!state.visible.length) return;
                state.activeIndex = (idx + state.visible.length) % state.visible.length;
                const li = state.visible[state.activeIndex];
                li.classList.add('is-active');
                li.scrollIntoView({ block: 'nearest' });
            }

            function open(state) {
                renderList(state);
                state.el.querySelector('.acp-list').hidden = false;
            }

            function close(state) {
                state.el.querySelector('.acp-list').hidden = true;
            }

            function emit(state) {
                state.el.dispatchEvent(new CustomEvent('acp:change', { detail: { ids: get(state.el) } }));
            }

            function add(state, id) {
                if (!state.byId.has(id) || state.selected.includes(id) || id === principalId(state)) return;
                state.selected.push(id);
                renderChips(state);
                emit(state);
            }

            function remove(state, id) {
                state.selected = state.selected.filter((s) => s !== id);
                renderChips(state);
                if (!state.el.querySelector('.acp-list').hidden) renderList(state);
                emit(state);
            }

            /** La categoría principal nunca puede ser adicional: si se elige como principal, sale de los chips. */
            function syncPrincipal(state) {
                const p = principalId(state);
                if (p && state.selected.includes(p)) remove(state, p);
                else if (!state.el.querySelector('.acp-list').hidden) renderList(state);
            }

            function mount(el) {
                if (!el || states.has(el)) return;
                let options = [];
                try { options = JSON.parse(el.querySelector('.acp-options').textContent || '[]'); } catch (e) {}
                options.forEach((o) => { o._n = norm(o.label); });
                let selected = [];
                try { selected = JSON.parse(el.dataset.selected || '[]').map(String); } catch (e) {}

                const state = {
                    el,
                    options,
                    byId: new Map(options.map((o) => [String(o.id), o])),
                    selected: [],
                    name: el.dataset.name || '',
                    principalInputId: el.dataset.principalInput || '',
                    input: el.querySelector('.acp-input'),
                    visible: [],
                    activeIndex: -1,
                };
                states.set(el, state);
                selected.forEach((id) => {
                    if (state.byId.has(id) && !state.selected.includes(id)) state.selected.push(id);
                });
                renderChips(state);

                state.input.addEventListener('focus', () => open(state));
                state.input.addEventListener('input', () => open(state));
                state.input.addEventListener('blur', () => setTimeout(() => close(state), 120));
                state.input.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowDown') { e.preventDefault(); open(state); setActive(state, state.activeIndex + 1); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(state, state.activeIndex - 1); }
                    else if (e.key === 'Enter') {
                        // Nunca envía el formulario: Enter elige la opción resaltada (o la única).
                        e.preventDefault();
                        const li = state.visible[state.activeIndex] || (state.visible.length === 1 ? state.visible[0] : null);
                        if (li) li.click();
                    } else if (e.key === 'Escape') { close(state); }
                    else if (e.key === 'Backspace' && state.input.value === '' && state.selected.length) {
                        remove(state, state.selected[state.selected.length - 1]);
                    }
                });

                if (state.principalInputId) {
                    // El cascade de categoría actualiza el hidden en el 'change' de
                    // sus <select>; este listener (en document) corre después.
                    document.addEventListener('change', (e) => {
                        if (e.target && /^pformCategory(Main|Sub|Child)$/.test(e.target.id || '')) syncPrincipal(state);
                    });
                    setTimeout(() => syncPrincipal(state), 0);
                }
            }

            function get(el) {
                const state = states.get(el);
                return state ? state.selected.map(Number) : [];
            }

            function set(el, ids) {
                const state = states.get(el);
                if (!state) return;
                state.selected = [];
                (ids || []).map(String).forEach((id) => {
                    if (state.byId.has(id) && !state.selected.includes(id)) state.selected.push(id);
                });
                renderChips(state);
                emit(state);
            }

            window.AdditionalCategoryPicker = { mount, get, set };

            function mountAll() { document.querySelectorAll('[data-acp]').forEach(mount); }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountAll);
            else mountAll();
        })();
    </script>
@endonce
