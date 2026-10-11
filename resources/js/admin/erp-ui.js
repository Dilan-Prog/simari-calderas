// ERP UI: pestañas de Object Page, selección en lote y árboles expandibles (sin dependencias).
(function () {
    // ── Pestañas: <div data-erp-tabs> con .erp-tab[data-tab] y .erp-tabpanel[data-panel] ──
    document.querySelectorAll('[data-erp-tabs]').forEach((root) => {
        const tabs = [...root.querySelectorAll('.erp-tab[data-tab]')];
        const panels = [...root.querySelectorAll('.erp-tabpanel[data-panel]')];
        if (!tabs.length) return;
        const show = (key) => {
            tabs.forEach((t) => t.classList.toggle('is-active', t.dataset.tab === key));
            panels.forEach((p) => p.classList.toggle('is-active', p.dataset.panel === key));
        };
        tabs.forEach((t) => t.addEventListener('click', () => {
            show(t.dataset.tab);
            try { history.replaceState(null, '', '#' + t.dataset.tab); } catch (e) { /* ignore */ }
        }));
        const fromHash = location.hash.replace('#', '');
        show(tabs.some((t) => t.dataset.tab === fromHash) ? fromHash : (tabs.find((t) => t.classList.contains('is-active')) || tabs[0]).dataset.tab);
    });

    // ── Selección en lote ──
    const bar = document.querySelector('[data-erp-batch]');
    const checks = () => [...document.querySelectorAll('.erp-row-check')];
    const sync = () => {
        const n = checks().filter((c) => c.checked).length;
        if (bar) {
            bar.classList.toggle('is-open', n > 0);
            const c = bar.querySelector('[data-erp-batch-count]');
            if (c) c.textContent = String(n);
        }
        const all = document.querySelector('[data-erp-select-all]');
        if (all) {
            const total = checks().length;
            all.checked = total > 0 && n === total;
            all.indeterminate = n > 0 && n < total;
        }
    };
    document.addEventListener('change', (e) => {
        if (e.target.matches('[data-erp-select-all]')) {
            checks().forEach((c) => { c.checked = e.target.checked; });
            sync();
        } else if (e.target.matches('.erp-row-check')) {
            sync();
        }
    });
    window.erpSelectedIds = () => checks().filter((c) => c.checked).map((c) => c.value);

    // ── Árbol: botón [data-erp-tree-toggle] alterna las filas hijas con data-parent="<id>" ──
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-erp-tree-toggle]');
        if (!btn) return;
        const id = btn.dataset.erpTreeToggle;
        const open = btn.getAttribute('aria-expanded') !== 'true';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.textContent = open ? '▾' : '▸';
        document.querySelectorAll('[data-parent="' + id + '"]').forEach((row) => { row.hidden = !open; });
    });
})();
