// ERP Almacén: formulario de traspaso, captura de conteo físico, ubicaciones.
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const debounce = (fn, ms = 250) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; };

/** Buscador genérico: input + contenedor de resultados. */
function attachSearch({ input, results, url, params = () => ({}), render, onPick }) {
    const run = debounce(async () => {
        const q = input.value.trim();
        const qs = new URLSearchParams({ q, ...params() });
        try {
            const res = await fetch(`${url}?${qs}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            results.innerHTML = data.length
                ? data.map((p, i) => `<div class="wh-search-item" data-i="${i}">${render(p)}</div>`).join('')
                : '<div class="wh-search-item"><span class="wh-muted">Sin resultados</span></div>';
            results.classList.add('is-open');
            results.querySelectorAll('[data-i]').forEach((el) => el.addEventListener('click', () => {
                onPick(data[+el.dataset.i]);
                input.value = '';
                results.classList.remove('is-open');
            }));
        } catch (e) { /* silencioso */ }
    });
    input.addEventListener('input', run);
    input.addEventListener('focus', run);
    document.addEventListener('click', (e) => {
        if (!results.contains(e.target) && e.target !== input) results.classList.remove('is-open');
    });
}

/* ── Formulario de traspaso ── */
function initTransferForm(root) {
    const body = root.querySelector('[data-lines-body]');
    const empty = root.querySelector('[data-lines-empty]');
    const fromSel = root.querySelector('[name="from_warehouse_id"]');
    const lines = new Map(JSON.parse(root.dataset.initial || '[]').map((l) => [l.product_id, l]));

    function redraw() {
        body.innerHTML = '';
        let i = 0;
        lines.forEach((l) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td data-label="Producto">${esc(l.name)}<br><small class="wh-muted">${esc(l.sku || '')}</small>
                    <input type="hidden" name="lines[${i}][product_id]" value="${l.product_id}"></td>
                <td data-label="Disponible en origen" class="num">${l.available ?? '—'}</td>
                <td data-label="Cantidad" class="num"><input type="number" min="1" step="1" class="wh-input-qty" name="lines[${i}][quantity]" value="${l.quantity || 1}"></td>
                <td><button type="button" class="wh-btn wh-btn-danger" data-remove="${l.product_id}">Quitar</button></td>`;
            body.appendChild(tr);
            i++;
        });
        empty.style.display = lines.size ? 'none' : '';
        body.querySelectorAll('[data-remove]').forEach((b) => b.addEventListener('click', () => { lines.delete(+b.dataset.remove); redraw(); }));
    }

    attachSearch({
        input: root.querySelector('[data-product-search]'),
        results: root.querySelector('[data-product-results]'),
        url: root.dataset.productsUrl,
        params: () => ({ warehouse_id: fromSel.value }),
        render: (p) => `<span>${esc(p.name)} <small>${esc(p.sku || '')}</small></span><small>Disp.: ${p.available}</small>`,
        onPick: (p) => {
            const cur = lines.get(p.id);
            lines.set(p.id, { product_id: p.id, name: p.name, sku: p.sku, available: p.available, quantity: (cur?.quantity || 0) + 1 });
            redraw();
        },
    });

    // Al cambiar el origen, los saldos mostrados ya no aplican.
    fromSel.addEventListener('change', () => { lines.forEach((l) => { l.available = null; }); redraw(); });
    redraw();
}

/* ── Formulario de conteo (selección de productos) ── */
function initCountForm(root) {
    const pills = root.querySelector('[data-pills]');
    const picked = new Map();
    const modeInputs = root.querySelectorAll('[name="mode"]');
    const toggle = () => {
        const mode = root.querySelector('[name="mode"]:checked')?.value;
        root.querySelector('[data-mode-category]').style.display = mode === 'category' ? '' : 'none';
        root.querySelector('[data-mode-products]').style.display = mode === 'products' ? '' : 'none';
    };
    modeInputs.forEach((r) => r.addEventListener('change', toggle));
    toggle();

    const redraw = () => {
        pills.innerHTML = '';
        picked.forEach((p) => {
            const span = document.createElement('span');
            span.className = 'wh-pill';
            span.innerHTML = `${esc(p.name)}<input type="hidden" name="product_ids[]" value="${p.id}"><button type="button" aria-label="Quitar">×</button>`;
            span.querySelector('button').addEventListener('click', () => { picked.delete(p.id); redraw(); });
            pills.appendChild(span);
        });
    };
    attachSearch({
        input: root.querySelector('[data-product-search]'),
        results: root.querySelector('[data-product-results]'),
        url: root.dataset.productsUrl,
        render: (p) => `<span>${esc(p.name)}</span><small>${esc(p.sku || '')}</small>`,
        onPick: (p) => { picked.set(p.id, p); redraw(); },
    });
}

/* ── Hoja de captura de conteo (guardado AJAX por línea) ── */
function initCountSheet(root) {
    const bar = root.querySelector('[data-progress-bar]');
    const label = root.querySelector('[data-progress-label]');

    root.querySelectorAll('[data-count-input]').forEach((input) => {
        const save = async () => {
            if (input.dataset.last === input.value) return;
            input.classList.remove('is-saved', 'is-error');
            input.classList.add('is-saving');
            try {
                const res = await fetch(input.dataset.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ counted_quantity: input.value === '' ? null : input.value }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Error');
                input.dataset.last = input.value;
                input.classList.remove('is-saving');
                input.classList.add('is-saved');
                const diffEl = root.querySelector(`[data-diff-for="${input.dataset.line}"]`);
                if (diffEl) {
                    const d = data.difference;
                    diffEl.textContent = d === null ? '—' : (d > 0 ? `+${d}` : `${d}`);
                    diffEl.className = d === null || d === 0 ? 'wh-muted' : (d > 0 ? 'wh-pos' : 'wh-neg');
                }
                if (bar) bar.style.width = `${data.total ? Math.round((data.counted / data.total) * 100) : 0}%`;
                if (label) label.textContent = `${data.counted} de ${data.total} contados`;
            } catch (e) {
                input.classList.remove('is-saving');
                input.classList.add('is-error');
                input.title = e.message;
            }
        };
        input.addEventListener('change', save);
        input.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            save();
            const all = [...root.querySelectorAll('[data-count-input]')];
            all[all.indexOf(input) + 1]?.focus();
        });
    });
}

/* ── Asignación de ubicación a un saldo ── */
function initLocationAssign(root) {
    root.querySelectorAll('[data-assign-select]').forEach((sel) => {
        sel.addEventListener('change', async () => {
            sel.disabled = true;
            try {
                const res = await fetch(root.dataset.assignUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ balance_id: sel.dataset.balance, location_id: sel.value || null }),
                });
                if (!res.ok) throw new Error();
                sel.style.borderColor = '#22c55e';
            } catch (e) {
                sel.style.borderColor = '#ef4444';
            }
            sel.disabled = false;
        });
    });
}

/* ── Confirmaciones ── */
function initConfirms() {
    document.querySelectorAll('form[data-confirm]').forEach((f) => {
        f.addEventListener('submit', (e) => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[data-wh-transfer-form]') && initTransferForm(document.querySelector('[data-wh-transfer-form]'));
    document.querySelector('[data-wh-count-form]') && initCountForm(document.querySelector('[data-wh-count-form]'));
    document.querySelector('[data-wh-count-sheet]') && initCountSheet(document.querySelector('[data-wh-count-sheet]'));
    document.querySelector('[data-wh-locations]') && initLocationAssign(document.querySelector('[data-wh-locations]'));
    initConfirms();
});
