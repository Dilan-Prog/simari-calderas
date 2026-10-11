// ERP mrp: JS del módulo (entrada de Vite).

function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

/** Pantalla de creación: alterna el origen y gestiona las líneas manuales. */
function initCreate(root) {
    const searchUrl = root.dataset.searchUrl;
    const radios = root.querySelectorAll('[data-origin-radio]');
    const panes = root.querySelectorAll('[data-origin-pane]');

    function syncOrigin() {
        const sel = (root.querySelector('[data-origin-radio]:checked') || {}).value;
        panes.forEach((p) => {
            const on = p.dataset.originPane === sel;
            p.hidden = !on;
            // Solo el <select> del origen activo envía origin_id.
            p.querySelectorAll('select[data-origin-select]').forEach((s) => { s.disabled = !on; });
            p.querySelectorAll('[data-manual-lines] input').forEach((i) => { i.disabled = !on; });
        });
    }
    radios.forEach((r) => r.addEventListener('change', syncOrigin));

    // Líneas manuales
    const box = root.querySelector('[data-manual-lines]');
    const addBtn = root.querySelector('[data-add-line]');
    let idx = 0;

    function addLine() {
        const i = idx++;
        const row = document.createElement('div');
        row.className = 'mrp-line';
        row.innerHTML = `
            <div class="mrp-picker">
                <input type="search" class="mrp-input" placeholder="Buscar producto o SKU…" data-picker-input autocomplete="off">
                <input type="hidden" name="lines[${i}][product_id]">
                <div class="mrp-picker-list" hidden></div>
            </div>
            <input type="number" class="mrp-input" name="lines[${i}][quantity]" min="0.0001" step="any" placeholder="Cantidad">
            <input type="text" class="mrp-input mrp-unit" name="lines[${i}][unit]" maxlength="30" placeholder="Unidad">
            <button type="button" class="mrp-icon-btn" title="Quitar" data-remove>✕</button>`;
        box.appendChild(row);

        const input = row.querySelector('[data-picker-input]');
        const hidden = row.querySelector('input[type=hidden]');
        const list = row.querySelector('.mrp-picker-list');
        const unit = row.querySelector('.mrp-unit');

        const run = debounce(async () => {
            const q = input.value.trim();
            hidden.value = '';
            if (q.length < 2) { list.hidden = true; return; }
            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const items = await res.json();
                list.innerHTML = '';
                if (!items.length) {
                    const none = document.createElement('div');
                    none.className = 'mrp-picker-item';
                    none.innerHTML = '<small>Sin resultados</small>';
                    list.appendChild(none);
                }
                items.forEach((p) => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'mrp-picker-item';
                    const name = document.createElement('span');
                    name.textContent = p.name;
                    const sku = document.createElement('small');
                    sku.textContent = (p.sku || '') + ' · existencia ' + (p.stock ?? 0);
                    b.append(name, sku);
                    b.addEventListener('click', () => {
                        hidden.value = p.id;
                        input.value = p.name;
                        if (!unit.value && p.unit) unit.value = p.unit;
                        list.hidden = true;
                    });
                    list.appendChild(b);
                });
                list.hidden = false;
            } catch (e) { list.hidden = true; }
        }, 250);

        input.addEventListener('input', run);
        document.addEventListener('click', (e) => { if (!row.contains(e.target)) list.hidden = true; });
        row.querySelector('[data-remove]').addEventListener('click', () => row.remove());
    }

    if (addBtn) addBtn.addEventListener('click', addLine);
    addLine();
    syncOrigin();
}

/** Pantalla de detalle: al elegir proveedor en una línea sin proveedor, se marca para comprar. */
function initShow() {
    document.querySelectorAll('select[name^="suppliers["]').forEach((sel) => {
        sel.addEventListener('change', () => {
            const cb = sel.closest('tr')?.querySelector('[data-po-check]');
            if (cb) cb.checked = sel.value !== '';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const create = document.querySelector('[data-mrp-create]');
    if (create) initCreate(create);
    initShow();
});
