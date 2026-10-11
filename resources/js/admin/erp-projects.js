// ERP projects: JS del módulo (entrada de Vite).
(function () {
    'use strict';

    const debounce = (fn, ms = 250) => {
        let t;
        return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
    };

    // ── Pestañas del show ────────────────────────────────────────────────
    document.querySelectorAll('[data-prj-tabs]').forEach((nav) => {
        const show = (key) => {
            nav.querySelectorAll('[data-tab]').forEach((b) => b.classList.toggle('is-active', b.dataset.tab === key));
            document.querySelectorAll('[data-panel]').forEach((p) => {
                const on = p.dataset.panel === key;
                p.hidden = !on;
                p.classList.toggle('is-active', on);
            });
            try { history.replaceState(null, '', '#' + key); } catch (e) { /* noop */ }
        };
        nav.addEventListener('click', (e) => {
            const b = e.target.closest('[data-tab]');
            if (b) show(b.dataset.tab);
        });
        const initial = (location.hash || '').replace('#', '');
        if (initial && nav.querySelector('[data-tab="' + initial + '"]')) show(initial);
    });

    // ── Autocompletado de cliente ────────────────────────────────────────
    document.querySelectorAll('[data-customer-picker]').forEach((box) => {
        const input = box.querySelector('[data-customer-input]');
        const hidden = box.querySelector('[data-customer-id]');
        const list = box.querySelector('[data-customer-list]');
        const clear = box.querySelector('[data-customer-clear]');
        const search = debounce(async () => {
            const q = input.value.trim();
            if (q.length < 2) { list.hidden = true; return; }
            try {
                const res = await fetch(box.dataset.url + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
                const rows = await res.json();
                list.innerHTML = '';
                rows.forEach((c) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = c.name + (c.company ? ' · ' + c.company : '');
                    b.addEventListener('click', () => {
                        hidden.value = c.id;
                        input.value = c.name;
                        list.hidden = true;
                    });
                    list.appendChild(b);
                });
                list.hidden = rows.length === 0;
            } catch (e) { list.hidden = true; }
        });
        input.addEventListener('input', () => { hidden.value = ''; search(); });
        clear && clear.addEventListener('click', (e) => { e.preventDefault(); hidden.value = ''; input.value = ''; list.hidden = true; });
    });

    // ── Asociar documentos existentes ────────────────────────────────────
    document.querySelectorAll('[data-attach-box]').forEach((form) => {
        const input = form.querySelector('[data-attach-input]');
        const idField = form.querySelector('[data-attach-id]');
        const list = form.querySelector('[data-attach-list]');
        const submit = form.querySelector('[data-attach-submit]');
        const run = debounce(async () => {
            const url = form.dataset.url + '?type=' + encodeURIComponent(form.dataset.type)
                + '&exclude_project=' + encodeURIComponent(form.dataset.project || '')
                + '&q=' + encodeURIComponent(input.value.trim());
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const rows = await res.json();
                list.innerHTML = '';
                rows.forEach((d) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.textContent = d.number + (d.project ? '  (ya en ' + d.project + ')' : '');
                    b.addEventListener('click', () => {
                        idField.value = d.id;
                        input.value = d.number;
                        submit.disabled = false;
                        list.hidden = true;
                    });
                    list.appendChild(b);
                });
                list.hidden = rows.length === 0;
            } catch (e) { list.hidden = true; }
        });
        input.addEventListener('focus', run);
        input.addEventListener('input', () => { idField.value = ''; submit.disabled = true; run(); });
    });

    // ── Editor de fases ──────────────────────────────────────────────────
    const form = document.querySelector('[data-project-form]');
    if (form) {
        const list = form.querySelector('[data-phase-list]');
        const tpl = form.querySelector('[data-phase-template]');
        const empty = form.querySelector('[data-phase-empty]');
        let counter = list.querySelectorAll('[data-phase]').length + 1000;

        const renumber = () => {
            const rows = list.querySelectorAll('[data-phase]');
            rows.forEach((row, i) => {
                const idx = row.querySelector('[data-phase-index]');
                if (idx) idx.textContent = i + 1;
                // El orden del arreglo define sort_order: reindexa los nombres.
                row.querySelectorAll('[name^="phases["]').forEach((el) => {
                    el.name = el.name.replace(/^phases\[[^\]]+\]/, 'phases[' + i + ']');
                });
            });
            if (empty) empty.hidden = rows.length > 0;
        };

        form.querySelector('[data-phase-add]').addEventListener('click', () => {
            const html = tpl.innerHTML.replace(/__IDX__/g, String(counter++));
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const row = wrap.firstElementChild;
            list.appendChild(row);
            renumber();
            const name = row.querySelector('.prj-phase-name');
            name && name.focus();
        });

        list.addEventListener('click', (e) => {
            const row = e.target.closest('[data-phase]');
            if (!row) return;
            if (e.target.closest('[data-phase-remove]')) {
                if (confirm('¿Quitar esta fase?')) { row.remove(); renumber(); }
            } else if (e.target.closest('[data-phase-up]') && row.previousElementSibling) {
                list.insertBefore(row, row.previousElementSibling); renumber();
            } else if (e.target.closest('[data-phase-down]') && row.nextElementSibling) {
                list.insertBefore(row.nextElementSibling, row); renumber();
            }
        });

        // Completada => 100 %
        list.addEventListener('change', (e) => {
            const sel = e.target.closest('select[name$="[status]"]');
            if (sel && sel.value === 'completada') {
                const p = sel.closest('[data-phase]').querySelector('input[name$="[progress]"]');
                if (p) p.value = 100;
            }
        });

        renumber();
    }
})();
