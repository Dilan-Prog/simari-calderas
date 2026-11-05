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
    .sp-table .sp-desc { min-width:354px; max-width:354px; overflow:hidden; text-overflow:ellipsis; border-right:2px solid #c9ccd2; }
    .sp-table .sp-line { min-width:95px; }
    .sp-table .sp-format { min-width:136px; }
    .sp-table .sp-num { min-width:99px; text-align:right; font-variant-numeric:tabular-nums; }
    .sp-table thead tr.sp-head th.sp-num { color:var(--sp-orange); text-align:right; }
    .sp-table thead tr.sp-head th.sp-total, .sp-table td.sp-total { background:var(--sp-orange-soft); }
    .sp-table td.sp-total { font-weight:700; background:#f7f8f9; min-width:120px; }
    .sp-table td.sp-zero { color:#a3a8b1; }
    .sp-table tbody tr:hover td:not(.sp-idx) { background:#fafafa; }
    .sp-table tbody tr:hover td.sp-total { background:#f1f2f4; }
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
    .sp-pop .sp-link { border:0; background:none; color:var(--sp-orange); font:600 13px 'Inter',sans-serif; cursor:pointer; padding:4px; }
    .sp-pop .sp-list { max-height:220px; overflow:auto; }
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
                Columnas <span class="sp-badge" id="spColumnsCount">5</span>
            </button>
            <button type="button" class="sp-btn" id="spPeriodBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M3 10h18"/></svg>
                <span id="spPeriodLabel">Jul – dic {{ $year }}</span>
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

{{-- Popovers (filtro por columna, columnas visibles, periodo) --}}
<div class="sp-pop" id="spPop" hidden></div>
@endsection

@push('scripts')
<script>
(() => {
    const ROWS = @json($rows);
    const YEAR = {{ (int) $year }};
    const MONTHS = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    const MONTHS_LONG = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    const YY = String(YEAR).slice(-2);
    const PERIODS = [
        { label: `Ene – jun ${YEAR}`, from: 1, to: 6 },
        { label: `Jul – dic ${YEAR}`, from: 7, to: 12 },
        { label: `Ene – dic ${YEAR}`, from: 1, to: 12 },
        { label: `Trimestre 3 ${YEAR}`, from: 7, to: 9 },
        { label: `Trimestre 4 ${YEAR}`, from: 10, to: 12 },
    ];

    // Columnas informativas que se pueden ocultar (el badge "Columnas" cuenta las visibles).
    const INFO_COLS = [
        { key: 'sku', label: 'SKU', cls: 'sp-sku', type: 'text' },
        { key: 'description', label: 'Descripción', cls: 'sp-desc', type: 'text' },
        { key: 'line', label: 'Línea', cls: 'sp-line', type: 'list' },
        { key: 'format', label: 'Formato', cls: 'sp-format', type: 'list' },
    ];

    const state = {
        period: PERIODS[1],
        hidden: new Set(),        // claves de columnas ocultas (incluye 'total')
        filters: {},              // key -> {text} | {values:Set} | {min}
        sort: null,               // {key, dir}
        selected: null,           // {row, col}
    };

    const $ = (id) => document.getElementById(id);
    const table = $('spTable'), thead = table.tHead, tbody = table.tBodies[0], pop = $('spPop');
    const fmt = (n) => Number(n).toLocaleString('en-US');
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const monthKeys = () => { const k = []; for (let m = state.period.from; m <= state.period.to; m++) k.push('m' + m); return k; };
    const valueOf = (row, key) => {
        if (key.startsWith('m')) return row.months[key.slice(1)] ?? 0;
        if (key === 'total') return monthKeys().reduce((s, k) => s + valueOf(row, k), 0);
        return row[key];
    };
    const colLabel = (key) => key === 'total' ? 'Total'
        : key.startsWith('m') ? `${MONTHS[key.slice(1) - 1]}-${YY}`
        : INFO_COLS.find(c => c.key === key).label;
    const colType = (key) => key.startsWith('m') || key === 'total' ? 'num' : INFO_COLS.find(c => c.key === key).type;

    function visibleColumns() {
        const cols = INFO_COLS.filter(c => !state.hidden.has(c.key)).map(c => ({ key: c.key, cls: c.cls }));
        monthKeys().forEach(k => cols.push({ key: k, cls: 'sp-num' }));
        if (!state.hidden.has('total')) cols.push({ key: 'total', cls: 'sp-num sp-total' });
        return cols;
    }

    function filteredRows() {
        let rows = ROWS.filter(row => Object.entries(state.filters).every(([key, f]) => {
            const v = valueOf(row, key);
            if (f.text) return String(v).toLowerCase().includes(f.text.toLowerCase());
            if (f.values) return f.values.has(v);
            if (f.min !== undefined && f.min !== '') return Number(v) >= Number(f.min);
            return true;
        }));
        if (state.sort) {
            const { key, dir } = state.sort;
            rows = [...rows].sort((a, b) => {
                const x = valueOf(a, key), y = valueOf(b, key);
                const r = typeof x === 'number' ? x - y : String(x).localeCompare(String(y), 'es');
                return dir === 'asc' ? r : -r;
            });
        }
        return rows;
    }

    const isFiltered = (key) => {
        const f = state.filters[key];
        return !!(f && (f.text || f.values || (f.min !== undefined && f.min !== ''))) || state.sort?.key === key;
    };
    const filterIcon = (key) => `<button type="button" class="sp-filter-btn ${isFiltered(key) ? 'is-on' : ''}" data-filter="${key}" aria-label="Filtrar ${esc(colLabel(key))}">
        <svg viewBox="0 0 24 24" fill="${isFiltered(key) ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5z"/></svg></button>`;

    function render() {
        const cols = visibleColumns();
        const rows = filteredRows();

        // Encabezado: fila de totales + fila de títulos con filtro
        let sum = `<tr class="sp-sum"><th class="sp-idx">Σ</th>`;
        let head = `<tr class="sp-head"><th class="sp-idx"><span style="color:#9ca3af">${filterIconSvg()}</span></th>`;
        let labelPlaced = false;
        cols.forEach(c => {
            if (c.key.startsWith('m') || c.key === 'total') {
                sum += `<th class="${c.cls}">${fmt(rows.reduce((s, r) => s + valueOf(r, c.key), 0))}</th>`;
            } else if (!labelPlaced) {
                sum += `<th class="${c.cls} sp-sum-label">Total general</th>`; labelPlaced = true;
            } else {
                sum += `<th class="${c.cls}"></th>`;
            }
            head += `<th class="${c.cls}"><div class="sp-th"><span>${esc(colLabel(c.key))}</span>${filterIcon(c.key)}</div></th>`;
        });
        thead.innerHTML = sum + '</tr>' + head + '</tr>';

        // Cuerpo
        if (!rows.length) {
            tbody.innerHTML = `<tr class="sp-empty"><td colspan="${cols.length + 1}">Ningún producto coincide con los filtros.</td></tr>`;
        } else {
            tbody.innerHTML = rows.map((row, i) => {
                const active = state.selected?.row === i;
                return `<tr class="${active ? 'is-active' : ''}"><td class="sp-idx">${i + 1}</td>` + cols.map((c, j) => {
                    const v = valueOf(row, c.key);
                    const num = typeof v === 'number';
                    const sel = active && state.selected.col === j ? ' is-selected' : '';
                    const zero = num && v === 0 && c.key !== 'total' ? ' sp-zero' : '';
                    return `<td class="${c.cls}${zero}${sel}" data-r="${i}" data-c="${j}" ${c.key === 'description' ? `title="${esc(v)}"` : ''}>${num ? fmt(v) : esc(v)}</td>`;
                }).join('') + '</tr>';
            }).join('');
        }

        $('spFoot').innerHTML = `Mostrando <strong>${rows.length}</strong> de ${ROWS.length} productos`;
        $('spColumnsCount').textContent = INFO_COLS.filter(c => !state.hidden.has(c.key)).length + (state.hidden.has('total') ? 0 : 1);
        $('spPeriodLabel').textContent = state.period.label;
    }
    function filterIconSvg() {
        return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5z"/></svg>`;
    }

    // ---- Popovers ----
    function openPop(anchor, html, bind) {
        pop.innerHTML = html;
        pop.hidden = false;
        const r = anchor.getBoundingClientRect();
        const w = pop.offsetWidth;
        pop.style.top = (r.bottom + 6) + 'px';
        pop.style.left = Math.max(8, Math.min(r.right - w, window.innerWidth - w - 8)) + 'px';
        pop.dataset.anchor = anchor.id || anchor.dataset.filter || '';
        bind?.();
    }
    const closePop = () => { pop.hidden = true; pop.innerHTML = ''; };
    document.addEventListener('mousedown', (e) => {
        if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[data-filter],#spColumnsBtn,#spPeriodBtn')) closePop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });

    function openFilter(btn) {
        const key = btn.dataset.filter, type = colType(key), f = state.filters[key] || {};
        const sortOpt = (dir, txt) => `<button type="button" class="sp-opt ${state.sort?.key === key && state.sort.dir === dir ? 'is-on' : ''}" data-sort="${dir}">${txt}</button>`;
        let body = '';
        if (type === 'text') {
            body = `<h4>Buscar</h4><input type="text" id="spFText" placeholder="Contiene…" value="${esc(f.text || '')}">`;
        } else if (type === 'list') {
            const values = [...new Set(ROWS.map(r => r[key]))].sort((a, b) => a.localeCompare(b, 'es'));
            body = `<h4>Valores</h4><div class="sp-list">` + values.map(v =>
                `<label class="sp-opt"><input type="checkbox" value="${esc(v)}" ${!f.values || f.values.has(v) ? 'checked' : ''}> ${esc(v)}</label>`).join('') + `</div>`;
        } else {
            body = `<h4>Mayor o igual a</h4><input type="number" id="spFMin" min="0" placeholder="0" value="${f.min ?? ''}">`;
        }
        openPop(btn, `<h4>Ordenar</h4>${sortOpt('asc', type === 'num' ? 'Menor a mayor' : 'A → Z')}${sortOpt('desc', type === 'num' ? 'Mayor a menor' : 'Z → A')}<hr>${body}
            <div class="sp-pop-foot"><button type="button" class="sp-link" id="spFClear">Limpiar</button><button type="button" class="sp-link" id="spFApply">Aplicar</button></div>`, () => {
            pop.querySelectorAll('[data-sort]').forEach(b => b.onclick = () => {
                const dir = b.dataset.sort;
                state.sort = state.sort?.key === key && state.sort.dir === dir ? null : { key, dir };
                state.selected = null; render(); closePop();
            });
            const apply = () => {
                if (type === 'text') state.filters[key] = { text: $('spFText').value.trim() };
                else if (type === 'list') {
                    const boxes = [...pop.querySelectorAll('input[type=checkbox]')];
                    state.filters[key] = boxes.every(b => b.checked) ? {} : { values: new Set(boxes.filter(b => b.checked).map(b => b.value)) };
                } else state.filters[key] = { min: $('spFMin').value };
                state.selected = null; render(); closePop();
            };
            $('spFApply').onclick = apply;
            pop.querySelectorAll('input[type=text],input[type=number]').forEach(i => { i.focus(); i.onkeydown = (e) => { if (e.key === 'Enter') apply(); }; });
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

    $('spColumnsBtn').onclick = (e) => {
        const all = [...INFO_COLS.map(c => ({ key: c.key, label: c.label })), { key: 'total', label: 'Total' }];
        openPop(e.currentTarget, `<h4>Columnas visibles</h4>` + all.map(c =>
            `<label class="sp-opt"><input type="checkbox" data-col="${c.key}" ${state.hidden.has(c.key) ? '' : 'checked'}> ${c.label}</label>`).join(''), () => {
            pop.querySelectorAll('[data-col]').forEach(cb => cb.onchange = () => {
                cb.checked ? state.hidden.delete(cb.dataset.col) : state.hidden.add(cb.dataset.col);
                state.selected = null; render();
            });
        });
    };

    $('spPeriodBtn').onclick = (e) => {
        openPop(e.currentTarget, `<h4>Periodo</h4>` + PERIODS.map((p, i) =>
            `<button type="button" class="sp-opt ${p === state.period ? 'is-on' : ''}" data-period="${i}">${p.label}</button>`).join(''), () => {
            pop.querySelectorAll('[data-period]').forEach(b => b.onclick = () => {
                state.period = PERIODS[b.dataset.period];
                Object.keys(state.filters).forEach(k => { if (k.startsWith('m')) delete state.filters[k]; });
                if (state.sort?.key.startsWith('m')) state.sort = null;
                state.selected = null; render(); closePop();
            });
        });
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

    // ---- Exportar (CSV de lo que se ve: columnas visibles + filtros) ----
    $('spExport').onclick = () => {
        const cols = visibleColumns(), rows = filteredRows();
        const cell = (v) => `"${String(v).replace(/"/g, '""')}"`;
        const lines = [cols.map(c => cell(colLabel(c.key))).join(',')];
        rows.forEach(r => lines.push(cols.map(c => cell(valueOf(r, c.key))).join(',')));
        lines.push(cols.map((c, i) => c.key.startsWith('m') || c.key === 'total'
            ? cell(rows.reduce((s, r) => s + valueOf(r, c.key), 0)) : cell(i === 0 ? 'Total general' : '')).join(','));
        const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `productos-${MONTHS_LONG[state.period.from - 1]}-${MONTHS_LONG[state.period.to - 1]}-${YEAR}.csv`;
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
