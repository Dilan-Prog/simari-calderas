// ERP boms: JS del módulo (entrada de Vite).

const isPhone = () => window.matchMedia('(max-width: 768px)').matches;

function debounce(fn, ms) {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

/** Autocompletado de productos sobre un contenedor .bom-picker. */
function attachPicker(root, searchUrl, onPick, getExclude) {
    const input = root.querySelector('[data-picker-input]');
    const list = root.querySelector('.bom-picker-list');
    if (!input || !list) return;

    const run = debounce(async () => {
        const q = input.value.trim();
        if (q.length < 2) { list.hidden = true; return; }
        const params = new URLSearchParams({ q });
        const ex = getExclude ? getExclude() : null;
        if (ex) params.set('exclude', ex);
        try {
            const res = await fetch(`${searchUrl}?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const items = await res.json();
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<div class="bom-picker-item"><small>Sin resultados</small></div>';
            }
            items.forEach((p) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'bom-picker-item';
                const name = document.createElement('span');
                name.textContent = p.name + (p.has_bom ? '  ⚙ subensamble' : '');
                const sku = document.createElement('small');
                sku.textContent = p.sku || '';
                b.append(name, sku);
                b.addEventListener('click', () => { list.hidden = true; onPick(p); });
                list.appendChild(b);
            });
            list.hidden = false;
        } catch (e) { list.hidden = true; }
    }, 250);

    input.addEventListener('input', run);
    // En teléfono, al enfocar el buscador se sube al inicio de la pantalla para que la lista
    // de resultados (en flujo, ver erp-boms.css) quede visible sobre el teclado.
    input.addEventListener('focus', () => {
        if (isPhone()) setTimeout(() => root.scrollIntoView({ block: 'start', behavior: 'smooth' }), 250);
    });
    document.addEventListener('click', (e) => { if (!root.contains(e.target)) list.hidden = true; });
}

function initForm(form) {
    const searchUrl = form.dataset.searchUrl;
    const container = form.querySelector('[data-lines]');
    const emptyMsg = form.querySelector('[data-lines-empty]');
    let initial = [];
    try { initial = JSON.parse(form.dataset.lines || '[]'); } catch (e) { /* vacío */ }

    // Producto fabricado (solo al crear)
    const mainPicker = form.querySelector('[data-product-picker]');
    if (mainPicker) {
        attachPicker(mainPicker, searchUrl, (p) => {
            mainPicker.querySelector('[data-picker-id]').value = p.id;
            mainPicker.querySelector('[data-picker-input]').value = p.name + (p.sku ? ` (${p.sku})` : '');
        });
    }

    const productId = () => form.querySelector('[data-picker-id]')?.value || null;

    function reindex() {
        const rows = container.querySelectorAll('.bom-line');
        rows.forEach((row, i) => {
            row.querySelectorAll('[data-field]').forEach((el) => {
                el.name = `lines[${i}][${el.dataset.field}]`;
            });
        });
        emptyMsg.hidden = rows.length > 0;
    }

    function addLine(data = {}) {
        const row = document.createElement('div');
        row.className = 'bom-line';
        row.innerHTML = `
            <div class="bom-picker bom-line-cell" data-label="Componente">
                <input type="hidden" data-field="component_product_id">
                <input type="hidden" data-field="component_name">
                <input type="text" class="erp-input" autocomplete="off" placeholder="Buscar componente…" aria-label="Componente" data-picker-input>
                <div class="bom-picker-list" hidden></div>
            </div>
            <div class="bom-line-cell bom-line-qty" data-label="Cantidad"><input type="number" class="erp-input" data-field="quantity" step="0.0001" min="0.0001" inputmode="decimal" aria-label="Cantidad" required></div>
            <div class="bom-line-cell bom-line-unit" data-label="Unidad"><input type="text" class="erp-input" data-field="unit" maxlength="30" placeholder="pieza" aria-label="Unidad"></div>
            <div class="bom-line-cell bom-line-scrap" data-label="Merma %"><input type="number" class="erp-input" data-field="scrap_percent" step="0.01" min="0" max="999.99" inputmode="decimal" aria-label="Merma %"></div>
            <div class="bom-line-cell bom-line-notes" data-label="Notas"><input type="text" class="erp-input" data-field="notes" maxlength="255" aria-label="Notas"></div>
            <div class="bom-line-actions">
                <button type="button" class="erp-btn erp-btn--sm bom-icon-btn" data-up title="Subir" aria-label="Subir renglón">↑</button>
                <button type="button" class="erp-btn erp-btn--sm bom-icon-btn" data-down title="Bajar" aria-label="Bajar renglón">↓</button>
                <button type="button" class="erp-btn erp-btn--sm bom-icon-btn" data-remove title="Quitar" aria-label="Quitar renglón">✕</button>
            </div>`;
        const set = (f, v) => { row.querySelector(`[data-field="${f}"]`).value = v ?? ''; };
        set('component_product_id', data.component_product_id);
        set('component_name', data.name);
        set('quantity', data.quantity ?? 1);
        set('unit', data.unit);
        set('scrap_percent', data.scrap_percent ?? 0);
        set('notes', data.notes);
        row.querySelector('[data-picker-input]').value = data.name || '';

        attachPicker(row.querySelector('.bom-picker'), searchUrl, (p) => {
            set('component_product_id', p.id);
            set('component_name', p.name + (p.sku ? ` (${p.sku})` : ''));
            row.querySelector('[data-picker-input]').value = p.name + (p.sku ? ` (${p.sku})` : '');
            const unit = row.querySelector('[data-field="unit"]');
            if (!unit.value && p.unit) unit.value = p.unit;
        }, productId);

        row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); reindex(); });
        row.querySelector('[data-up]').addEventListener('click', () => {
            if (row.previousElementSibling) { container.insertBefore(row, row.previousElementSibling); reindex(); }
        });
        row.querySelector('[data-down]').addEventListener('click', () => {
            if (row.nextElementSibling) { container.insertBefore(row.nextElementSibling, row); reindex(); }
        });

        container.appendChild(row);
        reindex();
        return row;
    }

    // Hay dos botones «Agregar componente» (cabecera y, en móvil, al pie de la lista).
    form.querySelectorAll('[data-add-line]').forEach((btn) => btn.addEventListener('click', () => {
        const row = addLine();
        row.querySelector('[data-picker-input]').focus({ preventScroll: isPhone() });
    }));
    initial.forEach(addLine);
    if (!initial.length) addLine();

    form.addEventListener('submit', (e) => {
        const bad = [...container.querySelectorAll('.bom-line')].find((r) => !r.querySelector('[data-field="component_product_id"]').value);
        if (bad) {
            e.preventDefault();
            bad.querySelector('[data-picker-input]').focus();
            alert('Selecciona un componente de la lista en cada renglón (o quita el renglón vacío).');
        }
    });
}

function initTree(root) {
    const setAll = (collapsed) => root.querySelectorAll('.bom-node.is-assembly').forEach((n) => {
        n.classList.toggle('is-collapsed', collapsed);
        const b = n.querySelector(':scope > .erp-tree-row [data-toggle]');
        if (b) { b.textContent = collapsed ? '▸' : '▾'; b.setAttribute('aria-expanded', String(!collapsed)); }
    });
    root.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle]');
        if (!btn) return;
        const node = btn.closest('.bom-node');
        const collapsed = node.classList.toggle('is-collapsed');
        btn.textContent = collapsed ? '▸' : '▾';
        btn.setAttribute('aria-expanded', String(!collapsed));
    });
    root.querySelector('[data-expand-all]')?.addEventListener('click', () => setAll(false));
    root.querySelector('[data-collapse-all]')?.addEventListener('click', () => setAll(true));
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bom-form]').forEach(initForm);
    document.querySelectorAll('[data-bom-tree]').forEach(initTree);
});
