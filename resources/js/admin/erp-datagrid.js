/*
 * Tabla de datos estilo hoja de cálculo (misma vista que Ventas > Listado de Productos), reutilizable.
 *
 * Mejora una <table data-dg="clave"> que ya viene renderizada por el servidor: NO reconstruye las filas, así
 * que enlaces, chips, casillas de selección en lote y botones de cada fila siguen funcionando igual.
 * Agrega: columna de número de fila, fila Σ "Total general", encabezado fijo, ordenar/filtrar por columna
 * (texto, lista de valores, rango numérico o de fechas) y menú «Columnas» (se recuerda en el navegador).
 *
 * Contrato del HTML (ver docs/index.blade.php, payments/index.blade.php):
 *   <table class="erp-table" data-dg="finanzas-cxc" data-dg-noun="cuentas por cobrar">
 *   <th data-dg-skip>                          → columna fija (casilla, acciones): sin filtro ni ocultar
 *   <th data-dg-key="folio" data-dg-type="text|list|num|date" [data-dg-sum] [data-dg-hidden]>
 *   <td data-dg-v="valor" [data-dg-cur="MXN"]> → valor para ordenar/filtrar/sumar (si falta, se usa el texto)
 *   Las filas de datos llevan data-dg-row; la fila "sin resultados" no.
 */

const FILTER_SVG = (fill) => `<svg viewBox="0 0 24 24" fill="${fill}" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5z"/></svg>`;
const COLS_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="M15 4v16"/></svg>';

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const norm = (s) => String(s ?? '').replace(/\s+/g, ' ').trim();
const money = (n) => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

let pop = null;
function ensurePop() {
    if (pop) return pop;
    pop = document.createElement('div');
    pop.className = 'dg-pop';
    pop.hidden = true;
    document.body.appendChild(pop);
    document.addEventListener('mousedown', (e) => {
        if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-dg-filter],[data-dg-cols]')) closePop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
    window.addEventListener('resize', closePop);
    return pop;
}
function closePop() { if (pop) { pop.hidden = true; pop.innerHTML = ''; } }
function openPop(anchor, html, bind, extra = '') {
    const p = ensurePop();
    p.className = 'dg-pop ' + extra;
    p.innerHTML = html;
    p.hidden = false;
    const r = anchor.getBoundingClientRect();
    const w = p.offsetWidth;
    p.style.top = (r.bottom + 6) + 'px';
    p.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
    bind?.(p);
}

function init(table) {
    const key = table.dataset.dg;
    const noun = table.dataset.dgNoun || 'registros';
    const thead = table.tHead;
    const tbody = table.tBodies[0];
    const headRow = thead?.rows[thead.rows.length - 1];
    if (!thead || !tbody || !headRow || table.dataset.dgReady) return;
    table.dataset.dgReady = '1';

    const wrap = table.closest('.erp-table-wrap') || table.parentElement;
    wrap.classList.add('dg');

    // ── Columnas (a partir del encabezado original) ──
    const heads = Array.from(headRow.cells);
    const cols = heads.map((th, i) => ({
        i,
        th,
        key: th.dataset.dgKey || null,
        type: th.dataset.dgType || 'text',
        sum: th.hasAttribute('data-dg-sum'),
        fixed: th.hasAttribute('data-dg-skip') || !th.dataset.dgKey,
        label: norm(th.textContent),
        defaultHidden: th.hasAttribute('data-dg-hidden'),
    }));
    const dataCols = cols.filter((c) => !c.fixed);

    // ── Filas de datos ──
    const rows = Array.from(tbody.rows).filter((r) => r.hasAttribute('data-dg-row')).map((el, n) => {
        const vals = {};
        dataCols.forEach((c) => {
            const td = el.cells[c.i];
            let v = td?.dataset.dgV;
            if (v === undefined) v = norm(td?.textContent);
            if (c.type === 'num') { const x = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); v = Number.isFinite(x) ? x : null; }
            vals[c.key] = v;
        });
        return { el, n, vals, checks: Array.from(el.querySelectorAll('.erp-row-check')) };
    });
    const emptyRows = Array.from(tbody.rows).filter((r) => !r.hasAttribute('data-dg-row'));
    if (!rows.length) return; // sin datos no hay nada que mejorar

    // Buscador externo opcional (data-dg-search="#id"): filtra por data-search de la fila o por su texto.
    const searchEl = table.dataset.dgSearch ? document.querySelector(table.dataset.dgSearch) : null;
    rows.forEach((r) => { r.search = (r.el.dataset.search || norm(r.el.textContent)).toLowerCase(); });

    // ── Estado ──
    const STORE = 'erp-dg:' + key + ':hidden';
    const state = { filters: {}, sort: null, hidden: new Set(cols.filter((c) => c.defaultHidden).map((c) => c.key)) };
    try {
        const stored = JSON.parse(localStorage.getItem(STORE) || 'null');
        if (Array.isArray(stored)) state.hidden = new Set(stored);
    } catch (e) { /* sin almacenamiento: no persiste, no es fatal */ }
    const saveHidden = () => { try { localStorage.setItem(STORE, JSON.stringify([...state.hidden])); } catch (e) { /* idem */ } };

    // ── Estructura: columna de número + fila Σ + botones de filtro ──
    const idxTh = document.createElement('th');
    idxTh.className = 'dg-idx';
    idxTh.innerHTML = `<span style="color:#9ca3af">${FILTER_SVG('none')}</span>`;
    headRow.insertBefore(idxTh, headRow.firstChild);
    rows.forEach((r) => {
        const td = document.createElement('td');
        td.className = 'dg-idx';
        r.el.insertBefore(td, r.el.firstChild);
        r.idx = td;
    });
    emptyRows.forEach((r) => { const c = r.cells[0]; if (c && c.hasAttribute('colspan')) c.colSpan = c.colSpan + 1; });

    const sumRow = document.createElement('tr');
    sumRow.className = 'dg-sum';
    sumRow.innerHTML = '<th class="dg-idx">Σ</th>' + cols.map(() => '<th></th>').join('');
    thead.insertBefore(sumRow, headRow);
    const sumCells = Array.from(sumRow.cells).slice(1);
    const labelCol = cols.find((c) => !c.fixed && !c.sum);

    dataCols.forEach((c) => {
        const th = c.th;
        th.classList.add('dg-col-' + c.type);
        const inner = th.innerHTML;
        th.innerHTML = `<div class="dg-th"><span>${inner}</span><button type="button" class="dg-filter-btn" data-dg-filter="${c.key}" aria-label="Ordenar y filtrar ${esc(c.label)}">${FILTER_SVG('none')}</button></div>`;
    });

    // ── Barra superior (Columnas) y pie (filtros activos) ──
    const bar = document.createElement('div');
    bar.className = 'dg-bar';
    bar.innerHTML = `<span class="dg-hint">Usa el embudo de cada columna para ordenar y filtrar.</span>
        <button type="button" class="dg-btn" data-dg-cols>${COLS_SVG} Columnas <span class="dg-badge" data-dg-colcount>0</span></button>`;
    wrap.parentNode.insertBefore(bar, wrap);
    const foot = document.createElement('div');
    foot.className = 'dg-foot';
    foot.hidden = true;
    wrap.parentNode.insertBefore(foot, wrap.nextSibling);

    // ── Lógica ──
    const hasFilter = (k) => {
        const f = state.filters[k];
        return !!(f && (f.text || f.values || (f.min !== undefined && f.min !== '') || (f.max !== undefined && f.max !== '')));
    };
    const isOn = (k) => hasFilter(k) || state.sort?.key === k;
    const colOf = (k) => cols.find((c) => c.key === k);

    function matches(row, k, f) {
        const v = row.vals[k];
        const col = colOf(k);
        if (f.text) return String(v ?? '').toLowerCase().includes(f.text.toLowerCase());
        if (f.values) return f.values.has(String(v ?? ''));
        const hasMin = f.min !== undefined && f.min !== '';
        const hasMax = f.max !== undefined && f.max !== '';
        if (hasMin || hasMax) {
            if (v === null || v === '' || v === undefined) return false;
            if (col.type === 'date') return (!hasMin || v >= f.min) && (!hasMax || v <= f.max);
            return (!hasMin || v >= Number(f.min)) && (!hasMax || v <= Number(f.max));
        }
        return true;
    }

    function resetSelection() {
        // Al cambiar el filtro se limpia la selección en lote: nunca queda marcada una fila que ya no se ve.
        const checks = rows.flatMap((r) => r.checks);
        const any = checks.some((c) => c.checked);
        checks.forEach((c) => { c.checked = false; });
        const all = document.querySelector('[data-erp-select-all]');
        if (all) { all.checked = false; all.indeterminate = false; }
        if (any) (all || checks[0])?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function render(resetChecks = false) {
        const q = (searchEl?.value || '').trim().toLowerCase();
        let list = rows.filter((r) => (!q || r.search.includes(q)) && Object.entries(state.filters).every(([k, f]) => matches(r, k, f)));
        if (state.sort) {
            const { key: k, dir } = state.sort;
            const sign = dir === 'asc' ? 1 : -1;
            list = [...list].sort((a, b) => {
                const x = a.vals[k], y = b.vals[k];
                const ex = x === null || x === '' || x === undefined, ey = y === null || y === '' || y === undefined;
                if (ex || ey) return ex === ey ? a.n - b.n : ex ? 1 : -1; // vacíos al final
                const c = typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es', { numeric: true });
                return c === 0 ? a.n - b.n : sign * c;
            });
        } else {
            list = [...list].sort((a, b) => a.n - b.n);
        }

        const visible = new Set(list);
        rows.forEach((r) => {
            r.el.hidden = !visible.has(r);
            // La selección en lote (erp-ui.js) opera sobre .erp-row-check: una fila filtrada deja de contar,
            // así «Seleccionar todos» y las acciones masivas solo alcanzan lo que se ve.
            r.checks.forEach((c) => c.classList.toggle('erp-row-check', visible.has(r)));
        });
        list.forEach((r, i) => { r.idx.textContent = String(i + 1); tbody.appendChild(r.el); });
        emptyRows.forEach((r) => tbody.appendChild(r)); // la fila "sin resultados" siempre al final

        // Σ (por moneda si la columna trae data-dg-cur)
        cols.forEach((c, j) => {
            const cell = sumCells[j];
            if (c.sum) {
                const byCur = {};
                list.forEach((r) => {
                    const td = r.el.cells[c.i + 1];
                    const v = r.vals[c.key];
                    if (typeof v !== 'number') return;
                    const cur = td?.dataset.dgCur || '';
                    byCur[cur] = (byCur[cur] || 0) + v;
                });
                const keys = Object.keys(byCur);
                cell.className = 'dg-num';
                cell.innerHTML = keys.length ? keys.map((cur) => `<div>${money(byCur[cur])}${cur ? `<small>${esc(cur)}</small>` : ''}</div>`).join('') : '';
            } else if (c === labelCol) {
                cell.className = 'dg-sum-label';
                cell.textContent = table.dataset.dgSumLabel || 'Total general';
            } else {
                cell.className = '';
                cell.textContent = '';
            }
        });

        // Botones de filtro activos
        dataCols.forEach((c) => {
            const btn = c.th.querySelector('[data-dg-filter]');
            btn.classList.toggle('is-on', isOn(c.key));
            btn.innerHTML = FILTER_SVG(isOn(c.key) ? 'currentColor' : 'none');
        });

        applyHidden();

        const nFilters = Object.keys(state.filters).filter(hasFilter).length;
        foot.hidden = !(nFilters || state.sort || list.length !== rows.length);
        foot.innerHTML = `Mostrando <strong>${list.length}</strong> de ${rows.length} ${esc(noun)} de esta página`
            + (nFilters ? ` · ${nFilters} filtro${nFilters > 1 ? 's' : ''} activo${nFilters > 1 ? 's' : ''}` : '')
            + ((nFilters || state.sort) ? ' <button type="button" class="dg-link" data-dg-clear>Limpiar todo</button>' : '');

        emptyRows.forEach((r) => { r.hidden = list.length > 0 && rows.length > 0; });
        if (!list.length && rows.length) {
            // Todo quedó filtrado: mensaje visible dentro de la tabla.
            let none = tbody.querySelector('tr.dg-none');
            if (!none) {
                none = document.createElement('tr');
                none.className = 'dg-none';
                none.innerHTML = `<td colspan="${cols.length + 1}">Ningún registro coincide con los filtros.</td>`;
                tbody.appendChild(none);
            }
            none.hidden = false;
        } else {
            tbody.querySelector('tr.dg-none')?.setAttribute('hidden', '');
        }

        if (resetChecks) resetSelection();
    }

    function applyHidden() {
        cols.forEach((c) => {
            if (c.fixed) return;
            const off = state.hidden.has(c.key);
            const set = (el) => { if (el) el.style.display = off ? 'none' : ''; };
            set(c.th);
            set(sumCells[c.i]);
            rows.forEach((r) => set(r.el.cells[c.i + 1]));
        });
        bar.querySelector('[data-dg-colcount]').textContent = String(dataCols.filter((c) => !state.hidden.has(c.key)).length);
    }

    // ── Popover de filtro por columna ──
    function openFilter(btn) {
        const k = btn.dataset.dgFilter, col = colOf(k), f = state.filters[k] || {};
        const sortOpt = (dir, txt) => `<button type="button" class="dg-opt ${state.sort?.key === k && state.sort.dir === dir ? 'is-on' : ''}" data-sort="${dir}">${txt}</button>`;
        const asNum = col.type === 'num' || col.type === 'date';
        let body;
        if (col.type === 'text') {
            body = `<h4>Buscar</h4><input type="text" data-f="text" placeholder="Contiene…" value="${esc(f.text || '')}">`;
        } else if (col.type === 'list') {
            const values = [...new Set(rows.map((r) => String(r.vals[k] ?? '')))].sort((a, b) => a.localeCompare(b, 'es'));
            body = `<h4>Valores</h4><div class="dg-list-tools"><button type="button" class="dg-link" data-all>Marcar todo</button><button type="button" class="dg-link" data-none>Desmarcar todo</button></div><div class="dg-list">`
                + values.map((v) => `<label class="dg-opt"><input type="checkbox" value="${esc(v)}" ${!f.values || f.values.has(v) ? 'checked' : ''}> ${esc(v || '(vacío)')}</label>`).join('') + '</div>';
        } else if (col.type === 'date') {
            body = `<h4>Rango de fechas</h4><div class="dg-range"><input type="date" data-f="min" value="${esc(f.min ?? '')}"><input type="date" data-f="max" value="${esc(f.max ?? '')}"></div>`;
        } else {
            body = `<h4>Rango</h4><div class="dg-range"><input type="number" step="any" data-f="min" placeholder="Desde" value="${esc(f.min ?? '')}"><input type="number" step="any" data-f="max" placeholder="Hasta" value="${esc(f.max ?? '')}"></div>`;
        }
        openPop(btn, `<h4>Ordenar</h4>${sortOpt('asc', asNum ? 'Menor a mayor' : 'A → Z')}${sortOpt('desc', asNum ? 'Mayor a menor' : 'Z → A')}<hr>${body}
            <div class="dg-pop-foot"><button type="button" class="dg-link" data-clear>Limpiar</button><button type="button" class="dg-link" data-apply>Aplicar</button></div>`, (p) => {
            p.querySelectorAll('[data-sort]').forEach((b) => {
                b.onclick = () => {
                    state.sort = state.sort?.key === k && state.sort.dir === b.dataset.sort ? null : { key: k, dir: b.dataset.sort };
                    render(); closePop();
                };
            });
            const apply = () => {
                if (col.type === 'text') state.filters[k] = { text: p.querySelector('[data-f="text"]').value.trim() };
                else if (col.type === 'list') {
                    const boxes = [...p.querySelectorAll('input[type=checkbox]')];
                    state.filters[k] = boxes.every((b) => b.checked) ? {} : { values: new Set(boxes.filter((b) => b.checked).map((b) => b.value)) };
                } else state.filters[k] = { min: p.querySelector('[data-f="min"]').value, max: p.querySelector('[data-f="max"]').value };
                render(true); closePop();
            };
            const setAll = (on) => p.querySelectorAll('.dg-list input[type=checkbox]').forEach((b) => { b.checked = on; });
            p.querySelector('[data-all]')?.addEventListener('click', () => setAll(true));
            p.querySelector('[data-none]')?.addEventListener('click', () => setAll(false));
            p.querySelector('[data-apply]').onclick = apply;
            p.querySelectorAll('input[type=text],input[type=number],input[type=date]').forEach((i) => { i.onkeydown = (e) => { if (e.key === 'Enter') apply(); }; });
            p.querySelector('input[type=text],input[type=number]')?.focus();
            p.querySelector('[data-clear]').onclick = () => {
                delete state.filters[k];
                if (state.sort?.key === k) state.sort = null;
                render(true); closePop();
            };
        });
    }

    thead.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-dg-filter]');
        if (btn) { e.stopPropagation(); openFilter(btn); }
    });
    foot.addEventListener('click', (e) => {
        if (!e.target.closest('[data-dg-clear]')) return;
        state.filters = {}; state.sort = null; render(true);
    });

    // ── Menú «Columnas» ──
    bar.querySelector('[data-dg-cols]').addEventListener('click', (e) => {
        const html = `<div class="dg-cols-grid">${dataCols.map((c) =>
            `<label class="dg-cols-item"><input type="checkbox" data-col="${c.key}" ${state.hidden.has(c.key) ? '' : 'checked'}> ${esc(c.label)}</label>`).join('')}</div>
            <div class="dg-cols-actions"><button type="button" class="dg-link" data-showall>Mostrar todas</button><button type="button" class="dg-link" data-reset>Restablecer</button></div>`;
        openPop(e.currentTarget, html, (p) => {
            const sync = () => p.querySelectorAll('[data-col]').forEach((cb) => { cb.checked = !state.hidden.has(cb.dataset.col); });
            p.querySelectorAll('[data-col]').forEach((cb) => {
                cb.onchange = () => {
                    // Siempre queda al menos una columna visible.
                    if (!cb.checked && dataCols.filter((c) => !state.hidden.has(c.key)).length <= 1) { cb.checked = true; return; }
                    cb.checked ? state.hidden.delete(cb.dataset.col) : state.hidden.add(cb.dataset.col);
                    saveHidden(); applyHidden();
                };
            });
            p.querySelector('[data-showall]').onclick = () => { state.hidden.clear(); saveHidden(); applyHidden(); sync(); };
            p.querySelector('[data-reset]').onclick = () => { state.hidden = new Set(cols.filter((c) => c.defaultHidden).map((c) => c.key)); saveHidden(); applyHidden(); sync(); };
        }, 'dg-pop--cols');
    });

    searchEl?.addEventListener('input', () => render(true));
    render();
}

function boot() { document.querySelectorAll('table[data-dg]').forEach(init); }
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
