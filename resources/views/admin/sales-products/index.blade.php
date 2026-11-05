@extends('admin.layouts.master')
@section('title')
    Productos - Ventas - Admin
@endsection

@push('styles')
<style>
    .sp { --sp-orange:#f26a1b; --sp-orange-soft:#fff6ee; --sp-line:#e6e7eb; --sp-head:#f4f5f7; --sp-text:#141516; --sp-muted:#6b7280;
          font-family:'Inter',system-ui,sans-serif; color:var(--sp-text); background:#fff; display:flex; flex-direction:column;
          height:calc(100vh - 70px); min-height:480px; border:1px solid var(--sp-line); border-radius:4px; overflow:hidden; }
    .sp-top { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 20px 16px 24px; flex-wrap:wrap; }
    .sp-top h1 { margin:0; font-size:28px; font-weight:800; letter-spacing:-.02em; }
    .sp-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .sp-btn { position:relative; display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 15px; background:#fff;
              border:1px solid var(--sp-line); border-radius:8px; font:600 14px 'Inter',sans-serif; color:var(--sp-text); cursor:pointer; }
    .sp-btn:hover { background:#f9fafb; }
    .sp-btn svg { width:16px; height:16px; }
    .sp-badge { min-width:20px; height:20px; padding:0 5px; border-radius:6px; background:#ffedd5; color:#c2410c; font-size:12px;
                font-weight:700; display:inline-flex; align-items:center; justify-content:center; }

    .sp-scroll { flex:1; overflow:auto; border-top:1px solid var(--sp-line); }
    .sp-table { border-collapse:separate; border-spacing:0; font-size:14px; white-space:nowrap; }
    .sp-table th, .sp-table td { border-right:1px solid var(--sp-line); border-bottom:1px solid var(--sp-line); padding:0 8px; height:33px; }
    .sp-table thead th { position:sticky; z-index:3; background:var(--sp-head); }
    .sp-table thead tr.sp-sum th { top:0; height:34px; background:var(--sp-orange-soft); font-weight:700; text-align:right; }
    .sp-table thead tr.sp-head th { top:34px; height:36px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:.02em;
                                    text-align:left; border-bottom:2px solid #2b2d31; }
    .sp-table thead tr.sp-sum th.sp-sum-label { text-align:left; }
    .sp-table .sp-idx { width:46px; min-width:46px; max-width:46px; padding:0; box-sizing:border-box; text-align:center; color:var(--sp-muted); font-size:12.5px; background:var(--sp-head);
                        position:sticky; left:0; z-index:2; }
    .sp-table thead .sp-idx { z-index:5; }
    .sp-table thead tr.sp-sum th.sp-idx { text-align:center; background:var(--sp-head); }
    .sp-table .sp-sku { position:sticky; left:46px; z-index:2; background:#fff; font-weight:700; min-width:114px; }
    .sp-table thead .sp-sku { z-index:5; background:var(--sp-head); }
    .sp-table thead tr.sp-sum .sp-sku { background:var(--sp-orange-soft); }
    .sp-table .sp-desc { min-width:354px; max-width:354px; overflow:hidden; text-overflow:ellipsis; }
    .sp-table .sp-text { min-width:140px; max-width:260px; overflow:hidden; text-overflow:ellipsis; }
    .sp-table .sp-num { min-width:110px; text-align:right; font-variant-numeric:tabular-nums; }
    .sp-table thead tr.sp-head th.sp-num { color:var(--sp-orange); text-align:right; }
    .sp-table td.sp-zero { color:#a3a8b1; }
    .sp-table tbody tr:hover td:not(.sp-idx) { background:#fafafa; }
    .sp-table tbody tr.is-active td.sp-idx { background:#fde7d6; color:var(--sp-orange); font-weight:700; }
    .sp-table td.is-selected { outline:2px solid var(--sp-orange); outline-offset:-2px; }
    .sp-table tbody td { cursor:cell; }

    .sp-th { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .sp-table th.sp-num .sp-th { justify-content:flex-end; }
    .sp-filter-btn { border:0; background:none; padding:2px; cursor:pointer; color:#8b9099; display:inline-flex; border-radius:4px; }
    .sp-filter-btn:hover { background:#e5e7eb; color:var(--sp-text); }
    .sp-filter-btn.is-on { color:var(--sp-orange); }
    .sp-filter-btn svg { width:12px; height:12px; }

    .sp-foot { border-top:1px solid var(--sp-line); background:var(--sp-head); padding:7px 16px; font-size:12.5px; color:var(--sp-muted); }
    .sp-foot strong { color:var(--sp-text); }
    .sp-empty td { text-align:center; color:var(--sp-muted); height:80px; }

    .sp-pop { position:fixed; z-index:1000; background:#fff; border:1px solid var(--sp-line); border-radius:10px;
              box-shadow:0 12px 32px rgba(0,0,0,.14); padding:10px; min-width:220px; font-family:'Inter',sans-serif; font-size:13.5px; }
    .sp-pop[hidden] { display:none; }
    .sp-pop h4 { margin:2px 4px 8px; font-size:11.5px; text-transform:uppercase; color:var(--sp-muted); letter-spacing:.04em; }
    .sp-pop button.sp-opt, .sp-pop label.sp-opt { display:flex; align-items:center; gap:8px; width:100%; border:0; background:none;
              padding:7px 8px; border-radius:6px; text-align:left; cursor:pointer; font:inherit; color:inherit; }
    .sp-pop .sp-opt:hover { background:#f3f4f6; }
    .sp-pop .sp-opt.is-on { color:var(--sp-orange); font-weight:600; }
    .sp-pop input[type=text], .sp-pop input[type=number] { width:100%; box-sizing:border-box; height:34px; border:1px solid var(--sp-line);
              border-radius:7px; padding:0 10px; font:inherit; margin:4px 0 6px; }
    .sp-pop hr { border:0; border-top:1px solid var(--sp-line); margin:8px 0; }
    .sp-pop .sp-pop-foot { display:flex; justify-content:space-between; gap:8px; margin-top:6px; }
    .sp-link { border:0; background:none; color:var(--sp-orange); font:600 13px 'Inter',sans-serif; cursor:pointer; padding:4px; }
    .sp-pop .sp-list { max-height:220px; overflow-y:auto; overflow-x:hidden; }
    .sp-pop .sp-list-tools { display:flex; justify-content:space-between; gap:8px; margin:0 0 4px; }
    .sp-pop .sp-list.is-tall { max-height:360px; }
    .sp-pop .sp-range { display:flex; gap:8px; }

    /* Menú "Columnas": mismo aspecto que el del listado de Productos (grupos en cuadrícula), pero se posiciona
       dentro de la pantalla: ancho acotado al viewport y scroll propio, para que no se corte en laptop. */
    .sp-pop.sp-pop--cols { width:min(640px, calc(100vw - 24px)); max-height:calc(100vh - 150px); overflow:auto; padding:16px; box-sizing:border-box; }
    .sp-cols-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:16px; }
    .sp-cols-group-title { margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#9ca3af; }
    .sp-cols-item { display:flex; align-items:center; gap:6px; padding:3px 0; font-size:13px; color:#374151; cursor:pointer; }
    .sp-cols-item input { flex:none; }
    .sp-cols-actions { display:flex; justify-content:space-between; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid var(--sp-line); }
    @media (max-width:1100px) { .sp-cols-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
</style>
@endpush

@section('content')
<div class="sp" id="salesProducts">
    <div class="sp-top">
        <h1>Productos</h1>
        <div class="sp-actions">
            <button type="button" class="sp-btn" id="spExport">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="m7 10 5 5 5-5"/><path d="M5 20h14"/></svg>
                Exportar
            </button>
            <button type="button" class="sp-btn" id="spImport">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15V4"/><path d="m7 9 5-5 5 5"/><path d="M5 20h14"/></svg>
                Importar productos
            </button>
            <input type="file" id="spImportFile" accept=".csv,.xlsx,.xls" hidden>
            <button type="button" class="sp-btn" id="spColumnsBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="M15 4v16"/></svg>
                Columnas <span class="sp-badge" id="spColumnsCount">0</span>
            </button>
        </div>
    </div>

    <div class="sp-scroll" id="spScroll">
        <table class="sp-table" id="spTable">
            <thead></thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="sp-foot" id="spFoot"></div>
</div>

{{-- Popovers (filtro por columna y columnas visibles) --}}
<div class="sp-pop" id="spPop" hidden></div>
@endsection

@push('scripts')
<script>
(() => {
    const ROWS = @json($rows);
    const COLS = @json($columns);

    const state = {
        hidden: new Set(COLS.filter(c => !c.default).map(c => c.key)),   // columnas ocultas
        filters: {},              // key -> {text} | {values:Set} | {min,max}  (varios a la vez)
        sort: null,               // {key, dir}
        selected: null,           // {row, col}
    };

    const $ = (id) => document.getElementById(id);
    const table = $('spTable'), thead = table.tHead, tbody = table.tBodies[0], pop = $('spPop');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const colOf = (key) => COLS.find(c => c.key === key);
    const fmtNum = (n, fmt) => n === null ? '' : Number(n).toLocaleString('en-US',
        fmt === 'money' ? { minimumFractionDigits: 2, maximumFractionDigits: 2 }
        : fmt === 'dec' ? { maximumFractionDigits: 2 } : { maximumFractionDigits: 0 });
    const cellClass = (c) => c.key === 'sku' ? 'sp-sku' : c.key === 'name' ? 'sp-desc' : c.type === 'num' ? 'sp-num' : 'sp-text';

    const visibleColumns = () => COLS.filter(c => !state.hidden.has(c.key));

    function matches(row, key, f) {
        const v = row[key];
        if (f.text) return String(v).toLowerCase().includes(f.text.toLowerCase());
        if (f.values) return f.values.has(v);
        const hasMin = f.min !== undefined && f.min !== '', hasMax = f.max !== undefined && f.max !== '';
        if (hasMin || hasMax) {
            if (v === null) return false;
            return (!hasMin || v >= Number(f.min)) && (!hasMax || v <= Number(f.max));
        }
        return true;
    }

    function filteredRows() {
        let rows = ROWS.filter(row => Object.entries(state.filters).every(([key, f]) => matches(row, key, f)));
        if (state.sort) {
            const { key, dir } = state.sort, sign = dir === 'asc' ? 1 : -1;
            rows = [...rows].sort((a, b) => {
                const x = a[key], y = b[key];
                if (x === null || y === null) return x === y ? 0 : x === null ? 1 : -1;   // vacíos siempre al final
                return sign * (typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es', { numeric: true }));
            });
        }
        return rows;
    }

    const hasFilter = (key) => {
        const f = state.filters[key];
        return !!(f && (f.text || f.values || (f.min !== undefined && f.min !== '') || (f.max !== undefined && f.max !== '')));
    };
    const isOn = (key) => hasFilter(key) || state.sort?.key === key;
    const filterIconSvg = (fill = 'none') => `<svg viewBox="0 0 24 24" fill="${fill}" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5z"/></svg>`;

    function render() {
        const cols = visibleColumns();
        const rows = filteredRows();

        let sum = `<tr class="sp-sum"><th class="sp-idx">Σ</th>`;
        let head = `<tr class="sp-head"><th class="sp-idx"><span style="color:#9ca3af">${filterIconSvg()}</span></th>`;
        const labelAt = cols.findIndex(c => !c.sum);
        cols.forEach((c, i) => {
            const cls = cellClass(c);
            if (c.sum) sum += `<th class="${cls}">${fmtNum(rows.reduce((s, r) => s + (r[c.key] ?? 0), 0), c.fmt)}</th>`;
            else if (i === labelAt) sum += `<th class="${cls} sp-sum-label">Total general</th>`;
            else sum += `<th class="${cls}"></th>`;
            head += `<th class="${cls}"><div class="sp-th"><span>${esc(c.label)}</span>
                <button type="button" class="sp-filter-btn ${isOn(c.key) ? 'is-on' : ''}" data-filter="${c.key}" aria-label="Ordenar y filtrar ${esc(c.label)}">${filterIconSvg(isOn(c.key) ? 'currentColor' : 'none')}</button></div></th>`;
        });
        thead.innerHTML = sum + '</tr>' + head + '</tr>';

        if (!cols.length) {
            tbody.innerHTML = `<tr class="sp-empty"><td>Activa al menos una columna en el botón «Columnas».</td></tr>`;
        } else if (!rows.length) {
            tbody.innerHTML = `<tr class="sp-empty"><td colspan="${cols.length + 1}">Ningún producto coincide con los filtros.</td></tr>`;
        } else {
            tbody.innerHTML = rows.map((row, i) => {
                const active = state.selected?.row === i;
                return `<tr class="${active ? 'is-active' : ''}"><td class="sp-idx">${i + 1}</td>` + cols.map((c, j) => {
                    const v = row[c.key];
                    const sel = active && state.selected.col === j ? ' is-selected' : '';
                    const zero = c.type === 'num' && v === 0 ? ' sp-zero' : '';
                    const text = c.type === 'num' ? fmtNum(v, c.fmt) : esc(v);
                    return `<td class="${cellClass(c)}${zero}${sel}" data-r="${i}" data-c="${j}" title="${c.type === 'num' ? text : esc(v)}">${text}</td>`;
                }).join('') + '</tr>';
            }).join('');
        }

        const nFilters = Object.keys(state.filters).filter(hasFilter).length;
        $('spFoot').innerHTML = `Mostrando <strong>${rows.length}</strong> de ${ROWS.length} productos`
            + (nFilters ? ` · ${nFilters} filtro${nFilters > 1 ? 's' : ''} activo${nFilters > 1 ? 's' : ''} <button type="button" class="sp-link" id="spClearAll">Limpiar todo</button>` : '');
        $('spColumnsCount').textContent = cols.length;
    }

    // ---- Popovers ----
    function openPop(anchor, html, bind, extraClass = '') {
        pop.className = 'sp-pop ' + extraClass;
        pop.innerHTML = html;
        pop.hidden = false;
        const r = anchor.getBoundingClientRect();
        const w = pop.offsetWidth;
        pop.style.top = (r.bottom + 6) + 'px';
        pop.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
        bind?.();
    }
    const closePop = () => { pop.hidden = true; pop.innerHTML = ''; };
    document.addEventListener('mousedown', (e) => {
        if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-filter],#spColumnsBtn')) closePop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });

    function openFilter(btn) {
        const key = btn.dataset.filter, col = colOf(key), f = state.filters[key] || {};
        const sortOpt = (dir, txt) => `<button type="button" class="sp-opt ${state.sort?.key === key && state.sort.dir === dir ? 'is-on' : ''}" data-sort="${dir}">${txt}</button>`;
        let body = '';
        if (col.type === 'text') {
            body = `<h4>Buscar</h4><input type="text" id="spFText" placeholder="Contiene…" value="${esc(f.text || '')}">`;
        } else if (col.type === 'list') {
            const values = [...new Set(ROWS.map(r => r[key]))].sort((a, b) => a.localeCompare(b, 'es'));
            body = `<h4>Valores</h4><div class="sp-list-tools"><button type="button" class="sp-link" id="spFAll">Marcar todo</button><button type="button" class="sp-link" id="spFNone">Desmarcar todo</button></div><div class="sp-list">` + values.map(v =>
                `<label class="sp-opt"><input type="checkbox" value="${esc(v)}" ${!f.values || f.values.has(v) ? 'checked' : ''}> ${esc(v)}</label>`).join('') + `</div>`;
        } else {
            body = `<h4>Rango</h4><div class="sp-range"><input type="number" id="spFMin" placeholder="Desde" value="${f.min ?? ''}"><input type="number" id="spFMax" placeholder="Hasta" value="${f.max ?? ''}"></div>`;
        }
        const num = col.type === 'num';
        openPop(btn, `<h4>Ordenar</h4>${sortOpt('asc', num ? 'Menor a mayor' : 'A → Z')}${sortOpt('desc', num ? 'Mayor a menor' : 'Z → A')}<hr>${body}
            <div class="sp-pop-foot"><button type="button" class="sp-link" id="spFClear">Limpiar</button><button type="button" class="sp-link" id="spFApply">Aplicar</button></div>`, () => {
            pop.querySelectorAll('[data-sort]').forEach(b => b.onclick = () => {
                const dir = b.dataset.sort;
                state.sort = state.sort?.key === key && state.sort.dir === dir ? null : { key, dir };
                state.selected = null; render(); closePop();
            });
            const apply = () => {
                if (col.type === 'text') state.filters[key] = { text: $('spFText').value.trim() };
                else if (col.type === 'list') {
                    const boxes = [...pop.querySelectorAll('input[type=checkbox]')];
                    state.filters[key] = boxes.every(b => b.checked) ? {} : { values: new Set(boxes.filter(b => b.checked).map(b => b.value)) };
                } else state.filters[key] = { min: $('spFMin').value, max: $('spFMax').value };
                state.selected = null; render(); closePop();
            };
            const setAll = (checked) => pop.querySelectorAll('.sp-list input[type=checkbox]').forEach(b => b.checked = checked);
            $('spFAll')?.addEventListener('click', () => setAll(true));
            $('spFNone')?.addEventListener('click', () => setAll(false));
            $('spFApply').onclick = apply;
            pop.querySelectorAll('input[type=text],input[type=number]').forEach(i => { i.onkeydown = (e) => { if (e.key === 'Enter') apply(); }; });
            pop.querySelector('input[type=text],input[type=number]')?.focus();
            $('spFClear').onclick = () => {
                delete state.filters[key];
                if (state.sort?.key === key) state.sort = null;
                state.selected = null; render(); closePop();
            };
        });
    }

    thead.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-filter]');
        if (btn) openFilter(btn);
    });
    $('spFoot').addEventListener('click', (e) => {
        if (!e.target.closest('#spClearAll')) return;
        state.filters = {}; state.sort = null; state.selected = null; render();
    });

    // ---- Columnas visibles: se recuerdan en este navegador (mismo criterio que el listado de Productos) ----
    const COLS_STORAGE_KEY = 'admin_sales_products_visible_columns';
    const saveHidden = () => {
        try { localStorage.setItem(COLS_STORAGE_KEY, JSON.stringify(COLS.filter(c => !state.hidden.has(c.key)).map(c => c.key))); } catch (e) { /* sin almacenamiento: no persiste, no es fatal */ }
    };
    try {
        const stored = JSON.parse(localStorage.getItem(COLS_STORAGE_KEY) || 'null');
        if (Array.isArray(stored)) state.hidden = new Set(COLS.filter(c => !stored.includes(c.key)).map(c => c.key));
    } catch (e) { /* valor corrupto: se queda con los predeterminados */ }

    $('spColumnsBtn').onclick = (e) => {
        const groups = {};
        COLS.forEach(c => (groups[c.group] ||= []).push(c));
        const html = `<div class="sp-cols-grid">` + Object.entries(groups).map(([name, cols]) =>
            `<div><p class="sp-cols-group-title">${esc(name)}</p>` + cols.map(c =>
                `<label class="sp-cols-item"><input type="checkbox" data-col="${c.key}" ${state.hidden.has(c.key) ? '' : 'checked'}> ${esc(c.label)}</label>`).join('') + `</div>`).join('') + `</div>
            <div class="sp-cols-actions"><button type="button" class="sp-link" id="spColsAll">Mostrar todas</button><button type="button" class="sp-link" id="spColsReset">Restablecer</button></div>`;
        openPop(e.currentTarget, html, () => {
            const sync = () => pop.querySelectorAll('[data-col]').forEach(cb => cb.checked = !state.hidden.has(cb.dataset.col));
            pop.querySelectorAll('[data-col]').forEach(cb => cb.onchange = () => {
                cb.checked ? state.hidden.delete(cb.dataset.col) : state.hidden.add(cb.dataset.col);
                saveHidden(); state.selected = null; render();
            });
            $('spColsAll').onclick = () => { state.hidden.clear(); saveHidden(); state.selected = null; render(); sync(); };
            $('spColsReset').onclick = () => { state.hidden = new Set(COLS.filter(c => !c.default).map(c => c.key)); saveHidden(); state.selected = null; render(); sync(); };
        }, 'sp-pop--cols');
    };

    // ---- Selección de celda (clic + flechas) ----
    tbody.addEventListener('click', (e) => {
        const td = e.target.closest('td[data-r]');
        if (!td) return;
        state.selected = { row: +td.dataset.r, col: +td.dataset.c };
        render();
    });
    document.addEventListener('keydown', (e) => {
        if (!state.selected || !pop.hidden || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
        const moves = { ArrowUp: [-1, 0], ArrowDown: [1, 0], ArrowLeft: [0, -1], ArrowRight: [0, 1] };
        if (!moves[e.key]) return;
        e.preventDefault();
        const maxR = filteredRows().length - 1, maxC = visibleColumns().length - 1;
        state.selected = {
            row: Math.max(0, Math.min(maxR, state.selected.row + moves[e.key][0])),
            col: Math.max(0, Math.min(maxC, state.selected.col + moves[e.key][1])),
        };
        render();
        tbody.querySelector('td.is-selected')?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });

    // ---- Exportar (CSV de lo que se ve: columnas visibles + filtros + orden) ----
    $('spExport').onclick = () => {
        const cols = visibleColumns(), rows = filteredRows();
        const cell = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
        const lines = [cols.map(c => cell(c.label)).join(',')];
        rows.forEach(r => lines.push(cols.map(c => cell(r[c.key])).join(',')));
        const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `productos-${new Date().toISOString().slice(0, 10)}.csv`;
        a.click();
        URL.revokeObjectURL(a.href);
    };

    // ---- Importar: pendiente de definir formato/origen de datos ----
    $('spImport').onclick = () => $('spImportFile').click();
    $('spImportFile').onchange = (e) => {
        if (e.target.files.length) alert('La importación de productos todavía no está conectada. Definiremos el formato del archivo en el siguiente paso.');
        e.target.value = '';
    };

    render();
})();
</script>
@endpush
