@extends('admin.layouts.master')
@section('title')
    Listado de Productos - Ventas - Admin
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
    select.sp-btn { padding-right:10px; max-width:220px; }
    .sp-badge { min-width:20px; height:20px; padding:0 5px; border-radius:6px; background:#ffedd5; color:#c2410c; font-size:12px;
                font-weight:700; display:inline-flex; align-items:center; justify-content:center; }

    .sp-scroll { flex:1; overflow:auto; border-top:1px solid var(--sp-line); }
    .sp-table { --sp-row-h:33px; border-collapse:separate; border-spacing:0; font-size:14px; white-space:nowrap; }
    .sp-table th, .sp-table td { border-right:1px solid var(--sp-line); border-bottom:1px solid var(--sp-line); padding:0 8px; }
    .sp-table td { height:var(--sp-row-h); overflow:hidden; text-overflow:ellipsis; }
    .sp-table thead th { position:sticky; z-index:3; background:var(--sp-head); }
    .sp-table thead tr.sp-sum th { top:0; height:34px; background:var(--sp-orange-soft); font-weight:700; text-align:right; }
    .sp-table thead tr.sp-head th { top:34px; height:36px; font-size:12.5px; font-weight:700; text-transform:uppercase; letter-spacing:.02em;
                                    text-align:left; border-bottom:2px solid #2b2d31; }
    .sp-table thead tr.sp-sum th.sp-sum-label { text-align:left; }
    .sp-table thead tr.sp-head th[draggable=true] { cursor:grab; }
    .sp-table thead th.is-dragging { opacity:.45; }
    .sp-table thead th.is-drop-before { box-shadow:inset 3px 0 0 var(--sp-orange); }
    .sp-table thead th.is-drop-after { box-shadow:inset -3px 0 0 var(--sp-orange); }

    .sp-table .sp-idx { width:46px; min-width:46px; max-width:46px; padding:0; box-sizing:border-box; text-align:center; color:var(--sp-muted); font-size:12.5px; background:var(--sp-head);
                        position:sticky; left:0; z-index:2; }
    .sp-table thead .sp-idx { z-index:5; }
    .sp-table thead tr.sp-sum th.sp-idx { text-align:center; background:var(--sp-head); }
    /* Asa para cambiar el alto de las filas: borde inferior del número de fila */
    .sp-table tbody td.sp-idx::after { content:''; position:absolute; left:0; right:0; bottom:0; height:7px; cursor:row-resize; }

    /* La primera columna visible queda fija al desplazar a la derecha (sea cual sea) */
    .sp-table .sp-sticky { position:sticky; left:46px; z-index:2; background:#fff; border-right:2px solid #c9ccd2; }
    .sp-table thead .sp-sticky { z-index:5; background:var(--sp-head); }
    .sp-table thead tr.sp-sum .sp-sticky { background:var(--sp-orange-soft); }
    .sp-table .sp-sku-bold { font-weight:700; }
    .sp-table .sp-num { text-align:right; font-variant-numeric:tabular-nums; }
    .sp-table thead tr.sp-head th.sp-num { color:var(--sp-orange); text-align:right; }
    .sp-table td.sp-zero { color:#a3a8b1; }
    .sp-table tbody tr:hover td:not(.sp-idx) { background:#fafafa; }
    .sp-table tbody tr.is-active td.sp-idx { background:#fde7d6; color:var(--sp-orange); font-weight:700; }
    .sp-table td.is-selected { outline:2px solid var(--sp-orange); outline-offset:-2px; }
    .sp-table tbody td { cursor:cell; }
    /* Con filas altas el texto se ajusta en varias líneas en vez de cortarse */
    .sp-table.is-tall tbody td:not(.sp-idx):not(.sp-num) { white-space:normal; line-height:1.3; }

    .sp-th { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .sp-th-label { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .sp-table th.sp-num .sp-th { justify-content:flex-end; }
    .sp-filter-btn { flex:none; border:0; background:none; padding:2px; cursor:pointer; color:#8b9099; display:inline-flex; border-radius:4px; }
    .sp-filter-btn:hover { background:#e5e7eb; color:var(--sp-text); }
    .sp-filter-btn.is-on { color:var(--sp-orange); }
    .sp-filter-btn svg { width:12px; height:12px; }
    /* Asa para cambiar el ancho de la columna (doble clic = ancho original) */
    .sp-resize { position:absolute; top:0; right:-3px; bottom:0; width:7px; cursor:col-resize; z-index:6; }
    .sp-resize:hover, .sp-resize.is-active { background:var(--sp-orange); opacity:.55; }

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
    .sp-pop .sp-opt:disabled { opacity:.4; cursor:not-allowed; }
    .sp-pop input[type=text], .sp-pop input[type=number] { width:100%; box-sizing:border-box; height:34px; border:1px solid var(--sp-line);
              border-radius:7px; padding:0 10px; font:inherit; margin:4px 0 6px; }
    .sp-pop hr { border:0; border-top:1px solid var(--sp-line); margin:8px 0; }
    .sp-pop .sp-pop-foot { display:flex; justify-content:space-between; gap:8px; margin-top:6px; }
    .sp-link { border:0; background:none; color:var(--sp-orange); font:600 13px 'Inter',sans-serif; cursor:pointer; padding:4px; }
    .sp-link:disabled { opacity:.5; cursor:not-allowed; }
    .sp-link.is-danger { color:#c81e1e; }
    .sp-pop .sp-list { max-height:220px; overflow-y:auto; overflow-x:hidden; }
    .sp-pop .sp-list-tools { display:flex; justify-content:space-between; gap:8px; margin:0 0 4px; }
    .sp-pop .sp-range { display:flex; gap:8px; }

    /* Menú "Columnas": mismo aspecto que el del listado de Productos (grupos en cuadrícula), pero se posiciona
       dentro de la pantalla: ancho acotado al viewport y scroll propio, para que no se corte en laptop. */
    .sp-pop.sp-pop--cols { width:min(640px, calc(100vw - 24px)); max-height:calc(100vh - 150px); overflow:auto; padding:16px; box-sizing:border-box; }
    .sp-cols-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:16px; }
    .sp-cols-group-title { margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#9ca3af; }
    .sp-cols-item { display:flex; align-items:center; gap:6px; padding:3px 0; font-size:13px; color:#374151; cursor:pointer; }
    .sp-cols-item input { flex:none; }
    .sp-cols-actions { display:flex; justify-content:space-between; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid var(--sp-line); }
    .sp-cols-block { margin-top:12px; padding-top:12px; border-top:1px solid var(--sp-line); }
    .sp-cols-block h5 { margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:#9ca3af; }
    .sp-rowh { display:flex; align-items:center; gap:12px; }
    .sp-rowh input[type=range] { flex:1; accent-color:var(--sp-orange); }
    .sp-rowh output { min-width:48px; text-align:right; font-variant-numeric:tabular-nums; }
    .sp-hint { margin:6px 0 0; font-size:12px; color:var(--sp-muted); }
    .sp-view-save { display:flex; gap:8px; align-items:center; }
    .sp-view-save input[type=text] { margin:0; flex:1; }
    .sp-view-save .sp-btn { height:34px; padding:0 12px; font-size:13px; white-space:nowrap; background:var(--sp-orange); border-color:var(--sp-orange); color:#fff; }
    .sp-view-save .sp-btn:hover { background:#de5a10; }
    .sp-view-manage { display:flex; gap:12px; margin-top:8px; }
    .sp-move { display:grid; grid-template-columns:1fr 1fr; gap:4px; }
    .sp-move button { border:1px solid var(--sp-line); background:#fff; border-radius:6px; padding:6px 8px; font:600 12.5px 'Inter',sans-serif; cursor:pointer; text-align:left; }
    .sp-move button:hover:not(:disabled) { background:#f3f4f6; }
    .sp-move button:disabled { opacity:.4; cursor:not-allowed; }
    @media (max-width:1100px) { .sp-cols-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }

    .sp-toast { position:fixed; right:20px; bottom:20px; z-index:1100; background:#141516; color:#fff; padding:11px 16px; border-radius:8px;
                font:600 13.5px 'Inter',sans-serif; box-shadow:0 8px 24px rgba(0,0,0,.25); max-width:340px; }
    .sp-toast.is-error { background:#c81e1e; }
</style>
@endpush

@section('content')
<div class="sp" id="salesProducts">
    <div class="sp-top">
        <h1>Listado de Productos</h1>
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
            <select class="sp-btn" id="spViewSelect" aria-label="Vista personalizada"></select>
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
<style id="spColStyles"></style>
@endsection

@push('scripts')
<script>
(() => {
    const ROWS = @json($rows);
    const COLS = @json($columns);
    const VIEWS = @json($views);
    const VIEWS_URL = @json(url('/admin/ventas/productos/vistas'));
    const VIEWS_MAX = 10;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const LAYOUT_KEY = 'admin_sales_products_layout';
    const ROW_H = { min: 24, max: 160, def: 33, tall: 56 };
    const W = { min: 50, max: 800 };

    const clamp = (n, lo, hi) => Math.max(lo, Math.min(hi, n));
    const defaultWidth = (c) => ({ name: 354, sku: 114, short_description: 260 }[c.key] ?? (c.type === 'num' ? 110 : 140));
    const colOf = (key) => COLS.find(c => c.key === key);
    const ALL_KEYS = COLS.map(c => c.key);

    // ---- Distribución (columnas visibles, orden, anchos, alto de fila): es lo que guarda cada vista ----
    const defaultLayout = () => ({ visible: COLS.filter(c => c.default).map(c => c.key), order: [...ALL_KEYS], widths: {}, row_height: ROW_H.def });
    function normalize(cfg) {
        const widths = {};
        Object.entries(Array.isArray(cfg.widths) ? {} : (cfg.widths || {})).forEach(([k, w]) => {
            if (ALL_KEYS.includes(k) && Number(w) > 0) widths[k] = clamp(Math.round(Number(w)), W.min, W.max);
        });
        return {
            visible: (cfg.visible || []).filter(k => ALL_KEYS.includes(k)),
            order: [...new Set([...(cfg.order || []).filter(k => ALL_KEYS.includes(k)), ...ALL_KEYS])],
            widths,
            row_height: clamp(Math.round(Number(cfg.row_height) || ROW_H.def), ROW_H.min, ROW_H.max),
        };
    }

    const state = {
        hidden: new Set(), order: [], widths: {}, rowHeight: ROW_H.def,
        viewId: null,             // vista personalizada aplicada (null = predeterminada)
        filters: {},              // key -> {text} | {values:Set} | {min,max}  (varios a la vez)
        sort: null,               // {key, dir}
        selected: null,           // {row, col}
    };
    const visibleKeys = () => state.order.filter(k => !state.hidden.has(k));
    const currentLayout = () => ({ visible: visibleKeys(), order: [...state.order], widths: { ...state.widths }, row_height: state.rowHeight });
    function applyLayout(cfg) {
        const n = normalize(cfg);
        state.hidden = new Set(ALL_KEYS.filter(k => !n.visible.includes(k)));
        state.order = n.order; state.widths = n.widths; state.rowHeight = n.row_height;
        state.selected = null;
    }
    const saveLocal = () => { try { localStorage.setItem(LAYOUT_KEY, JSON.stringify({ viewId: state.viewId, ...currentLayout() })); } catch (e) { /* sin almacenamiento: no persiste, no es fatal */ } };

    // Al abrir: lo último que dejó este navegador (y la vista que tenía aplicada, si todavía existe)
    applyLayout(defaultLayout());
    try {
        const s = JSON.parse(localStorage.getItem(LAYOUT_KEY) || 'null');
        if (s && Array.isArray(s.visible)) {
            applyLayout(s);
            state.viewId = VIEWS.some(v => v.id === s.viewId) ? s.viewId : null;
        }
    } catch (e) { /* valor corrupto: se queda con los predeterminados */ }

    const $ = (id) => document.getElementById(id);
    const table = $('spTable'), thead = table.tHead, tbody = table.tBodies[0], pop = $('spPop');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmtNum = (n, fmt) => n === null ? '' : Number(n).toLocaleString('en-US',
        fmt === 'money' ? { minimumFractionDigits: 2, maximumFractionDigits: 2 }
        : fmt === 'dec' ? { maximumFractionDigits: 2 } : { maximumFractionDigits: 0 });
    const visibleColumns = () => visibleKeys().map(colOf);
    const cellClass = (c, i) => ['sp-c-' + c.key, c.type === 'num' ? 'sp-num' : 'sp-text', i === 0 ? 'sp-sticky' : '', c.key === 'sku' ? 'sp-sku-bold' : ''].filter(Boolean).join(' ');

    // Anchos por columna y alto de fila: reglas/variables CSS, así arrastrar no re-pinta toda la tabla.
    function applyColumnStyles() {
        $('spColStyles').textContent = COLS.map(c => {
            const w = (state.widths[c.key] ?? defaultWidth(c)) + 'px';
            return `#spTable .sp-c-${c.key}{width:${w};min-width:${w};max-width:${w};}`;
        }).join('');
    }
    function applyRowHeight() {
        table.style.setProperty('--sp-row-h', state.rowHeight + 'px');
        table.classList.toggle('is-tall', state.rowHeight >= ROW_H.tall);
    }

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
            const cls = cellClass(c, i);
            if (c.sum) sum += `<th class="${cls}">${fmtNum(rows.reduce((s, r) => s + (r[c.key] ?? 0), 0), c.fmt)}</th>`;
            else if (i === labelAt) sum += `<th class="${cls} sp-sum-label">Total general</th>`;
            else sum += `<th class="${cls}"></th>`;
            head += `<th class="${cls}" data-key="${c.key}" draggable="true" title="Arrastra para mover la columna"><div class="sp-th"><span class="sp-th-label">${esc(c.label)}</span>
                <button type="button" class="sp-filter-btn ${isOn(c.key) ? 'is-on' : ''}" data-filter="${c.key}" aria-label="Ordenar, mover y filtrar ${esc(c.label)}">${filterIconSvg(isOn(c.key) ? 'currentColor' : 'none')}</button></div>
                <span class="sp-resize" data-resize="${c.key}" title="Arrastra para cambiar el ancho (doble clic: ancho original)"></span></th>`;
        });
        thead.innerHTML = sum + '</tr>' + head + '</tr>';

        if (!cols.length) {
            tbody.innerHTML = `<tr class="sp-empty"><td>Activa al menos una columna en el botón «Columnas».</td></tr>`;
        } else if (!rows.length) {
            tbody.innerHTML = `<tr class="sp-empty"><td colspan="${cols.length + 1}">Ningún producto coincide con los filtros.</td></tr>`;
        } else {
            tbody.innerHTML = rows.map((row, i) => {
                const active = state.selected?.row === i;
                return `<tr class="${active ? 'is-active' : ''}"><td class="sp-idx" title="Arrastra el borde inferior para cambiar el alto de las filas">${i + 1}</td>` + cols.map((c, j) => {
                    const v = row[c.key];
                    const sel = active && state.selected.col === j ? ' is-selected' : '';
                    const zero = c.type === 'num' && v === 0 ? ' sp-zero' : '';
                    const text = c.type === 'num' ? fmtNum(v, c.fmt) : esc(v);
                    return `<td class="${cellClass(c, j)}${zero}${sel}" data-r="${i}" data-c="${j}" title="${c.type === 'num' ? text : esc(v)}">${text}</td>`;
                }).join('') + '</tr>';
            }).join('');
        }

        const nFilters = Object.keys(state.filters).filter(hasFilter).length;
        $('spFoot').innerHTML = `Mostrando <strong>${rows.length}</strong> de ${ROWS.length} productos`
            + (nFilters ? ` · ${nFilters} filtro${nFilters > 1 ? 's' : ''} activo${nFilters > 1 ? 's' : ''} <button type="button" class="sp-link" id="spClearAll">Limpiar todo</button>` : '');
        $('spColumnsCount').textContent = cols.length;
    }
    const refresh = () => { applyColumnStyles(); applyRowHeight(); render(); };

    // ---- Mensajes breves ----
    function toast(msg, isError = false) {
        const t = document.createElement('div');
        t.className = 'sp-toast' + (isError ? ' is-error' : '');
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
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

    // ---- Orden de columnas: mover una posición, al inicio/final, o arrastrando el encabezado ----
    function moveColumn(key, targetKey, after) {
        if (key === targetKey) return;
        const o = state.order.filter(k => k !== key);
        o.splice(o.indexOf(targetKey) + (after ? 1 : 0), 0, key);
        state.order = o;
    }
    function moveBy(key, mode) {
        const v = visibleKeys(), i = v.indexOf(key);
        if (i < 0) return;
        if (mode === 'left' && i > 0) moveColumn(key, v[i - 1], false);
        else if (mode === 'right' && i < v.length - 1) moveColumn(key, v[i + 1], true);
        else if (mode === 'start') moveColumn(key, v[0], false);
        else if (mode === 'end') moveColumn(key, v[v.length - 1], true);
        state.selected = null; saveLocal(); render();
    }

    const drag = { key: null };
    const clearDropMarks = () => thead.querySelectorAll('.is-drop-before,.is-drop-after,.is-dragging').forEach(el => el.classList.remove('is-drop-before', 'is-drop-after', 'is-dragging'));
    thead.addEventListener('dragstart', (e) => {
        const th = e.target.closest('th[data-key]');
        if (!th) return;
        drag.key = th.dataset.key;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', drag.key);
        th.classList.add('is-dragging');
    });
    thead.addEventListener('dragover', (e) => {
        const th = e.target.closest('th[data-key]');
        if (!drag.key || !th) return;
        e.preventDefault();
        thead.querySelectorAll('.is-drop-before,.is-drop-after').forEach(el => el.classList.remove('is-drop-before', 'is-drop-after'));
        if (th.dataset.key === drag.key) return;
        const r = th.getBoundingClientRect();
        th.classList.add(e.clientX < r.left + r.width / 2 ? 'is-drop-before' : 'is-drop-after');
    });
    thead.addEventListener('drop', (e) => {
        const th = e.target.closest('th[data-key]');
        if (!drag.key || !th) return;
        e.preventDefault();
        const r = th.getBoundingClientRect();
        moveColumn(drag.key, th.dataset.key, e.clientX >= r.left + r.width / 2);
        drag.key = null; state.selected = null; saveLocal(); render();
    });
    thead.addEventListener('dragend', () => { drag.key = null; clearDropMarks(); });

    // ---- Ancho de columna y alto de fila arrastrando ----
    function startDrag(e, cursor, onMove) {
        e.preventDefault();
        document.body.style.cursor = cursor;
        document.body.style.userSelect = 'none';
        const up = () => {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', up);
            document.body.style.cursor = ''; document.body.style.userSelect = '';
            thead.querySelectorAll('.sp-resize.is-active').forEach(h => h.classList.remove('is-active'));
            saveLocal();
        };
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', up);
    }
    thead.addEventListener('mousedown', (e) => {
        const h = e.target.closest('.sp-resize');
        if (!h) return;
        e.stopPropagation();
        const key = h.dataset.resize, startX = e.clientX, startW = h.closest('th').getBoundingClientRect().width;
        h.classList.add('is-active');
        startDrag(e, 'col-resize', (ev) => {
            state.widths[key] = clamp(Math.round(startW + ev.clientX - startX), W.min, W.max);
            applyColumnStyles();
        });
    });
    thead.addEventListener('dblclick', (e) => {
        const h = e.target.closest('.sp-resize');
        if (!h) return;
        delete state.widths[h.dataset.resize];
        applyColumnStyles(); saveLocal();
    });
    tbody.addEventListener('mousedown', (e) => {
        const td = e.target.closest('td.sp-idx');
        if (!td || e.clientY < td.getBoundingClientRect().bottom - 7) return;   // solo el borde inferior es el asa
        const startY = e.clientY, startH = state.rowHeight;
        startDrag(e, 'row-resize', (ev) => {
            state.rowHeight = clamp(Math.round(startH + ev.clientY - startY), ROW_H.min, ROW_H.max);
            applyRowHeight();
        });
    });

    // ---- Filtro / orden por columna ----
    function openFilter(btn) {
        const key = btn.dataset.filter, col = colOf(key), f = state.filters[key] || {};
        const sortOpt = (dir, txt) => `<button type="button" class="sp-opt ${state.sort?.key === key && state.sort.dir === dir ? 'is-on' : ''}" data-sort="${dir}">${txt}</button>`;
        const v = visibleKeys(), i = v.indexOf(key);
        let body = '';
        if (col.type === 'text') {
            body = `<h4>Buscar</h4><input type="text" id="spFText" placeholder="Contiene…" value="${esc(f.text || '')}">`;
        } else if (col.type === 'list') {
            const values = [...new Set(ROWS.map(r => r[key]))].sort((a, b) => a.localeCompare(b, 'es'));
            body = `<h4>Valores</h4><div class="sp-list-tools"><button type="button" class="sp-link" id="spFAll">Marcar todo</button><button type="button" class="sp-link" id="spFNone">Desmarcar todo</button></div><div class="sp-list">` + values.map(val =>
                `<label class="sp-opt"><input type="checkbox" value="${esc(val)}" ${!f.values || f.values.has(val) ? 'checked' : ''}> ${esc(val)}</label>`).join('') + `</div>`;
        } else {
            body = `<h4>Rango</h4><div class="sp-range"><input type="number" id="spFMin" placeholder="Desde" value="${f.min ?? ''}"><input type="number" id="spFMax" placeholder="Hasta" value="${f.max ?? ''}"></div>`;
        }
        const num = col.type === 'num';
        openPop(btn, `<h4>Ordenar</h4>${sortOpt('asc', num ? 'Menor a mayor' : 'A → Z')}${sortOpt('desc', num ? 'Mayor a menor' : 'Z → A')}<hr>
            <h4>Mover columna</h4><div class="sp-move">
                <button type="button" data-move="left" ${i <= 0 ? 'disabled' : ''}>← A la izquierda</button>
                <button type="button" data-move="right" ${i >= v.length - 1 ? 'disabled' : ''}>A la derecha →</button>
                <button type="button" data-move="start" ${i <= 0 ? 'disabled' : ''}>⇤ Al inicio</button>
                <button type="button" data-move="end" ${i >= v.length - 1 ? 'disabled' : ''}>Al final ⇥</button>
            </div><hr>${body}
            <div class="sp-pop-foot"><button type="button" class="sp-link" id="spFClear">Limpiar</button><button type="button" class="sp-link" id="spFApply">Aplicar</button></div>`, () => {
            pop.querySelectorAll('[data-move]').forEach(b => b.onclick = () => { closePop(); moveBy(key, b.dataset.move); });
            pop.querySelectorAll('[data-sort]').forEach(b => b.onclick = () => {
                const dir = b.dataset.sort;
                state.sort = state.sort?.key === key && state.sort.dir === dir ? null : { key, dir };
                state.selected = null; render(); closePop();
            });
            const apply = () => {
                if (col.type === 'text') state.filters[key] = { text: $('spFText').value.trim() };
                else if (col.type === 'list') {
                    const boxes = [...pop.querySelectorAll('.sp-list input[type=checkbox]')];
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

    // ---- Vistas personalizadas ----
    function renderViewSelect() {
        $('spViewSelect').innerHTML = `<option value="">Vista predeterminada</option>` +
            VIEWS.map(v => `<option value="${v.id}">${esc(v.name)}</option>`).join('');
        $('spViewSelect').value = state.viewId ?? '';
    }
    $('spViewSelect').onchange = (e) => {
        const view = VIEWS.find(v => String(v.id) === e.target.value);
        state.viewId = view ? view.id : null;
        applyLayout(view ? view.config : defaultLayout());
        saveLocal(); refresh();
    };
    async function api(method, url, body) {
        try {
            const r = await fetch(url, {
                method, body: body ? JSON.stringify(body) : undefined,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await r.json().catch(() => ({}));
            return { ok: r.ok && data.success !== false, data };
        } catch (err) {
            return { ok: false, data: { message: 'Error de conexión. Intenta de nuevo.' } };
        }
    }
    const currentView = () => VIEWS.find(v => v.id === state.viewId);

    async function saveNewView(name) {
        if (!name) return toast('Ponle un nombre a la vista antes de guardarla.', true);
        if (VIEWS.length >= VIEWS_MAX) return toast(`Ya tienes el máximo de ${VIEWS_MAX} vistas guardadas. Elimina una para poder guardar otra.`, true);
        const { ok, data } = await api('POST', VIEWS_URL, { name, config: currentLayout() });
        if (!ok) return toast(data.message ?? 'No se pudo guardar la vista.', true);
        VIEWS.push(data.view);
        state.viewId = data.view.id;
        renderViewSelect(); saveLocal(); closePop();
        toast(`Vista "${data.view.name}" guardada.`);
    }
    async function updateCurrentView() {
        const view = currentView();
        if (!view) return;
        const { ok, data } = await api('PUT', `${VIEWS_URL}/${view.id}`, { name: view.name, config: currentLayout() });
        if (!ok) return toast(data.message ?? 'No se pudo actualizar la vista.', true);
        view.config = data.view.config;
        closePop();
        toast(`Vista "${view.name}" actualizada.`);
    }
    async function deleteCurrentView() {
        const view = currentView();
        if (!view || !confirm(`¿Eliminar la vista "${view.name}"?`)) return;
        const { ok, data } = await api('DELETE', `${VIEWS_URL}/${view.id}`);
        if (!ok) return toast(data.message ?? 'No se pudo eliminar la vista.', true);
        VIEWS.splice(VIEWS.indexOf(view), 1);
        state.viewId = null;
        renderViewSelect(); saveLocal(); closePop();
        toast(`Vista "${view.name}" eliminada.`);
    }

    // ---- Menú Columnas: visibles + alto de fila + guardar vistas ----
    $('spColumnsBtn').onclick = (e) => {
        const groups = {};
        COLS.forEach(c => (groups[c.group] ||= []).push(c));
        const view = currentView();
        const html = `<div class="sp-cols-grid">` + Object.entries(groups).map(([name, cols]) =>
            `<div><p class="sp-cols-group-title">${esc(name)}</p>` + cols.map(c =>
                `<label class="sp-cols-item"><input type="checkbox" data-col="${c.key}" ${state.hidden.has(c.key) ? '' : 'checked'}> ${esc(c.label)}</label>`).join('') + `</div>`).join('') + `</div>
            <div class="sp-cols-actions"><button type="button" class="sp-link" id="spColsAll">Mostrar todas</button><button type="button" class="sp-link" id="spColsReset">Restablecer todo</button></div>
            <div class="sp-cols-block"><h5>Alto de las filas</h5>
                <div class="sp-rowh"><input type="range" id="spRowH" min="${ROW_H.min}" max="${ROW_H.max}" value="${state.rowHeight}"><output id="spRowHOut">${state.rowHeight} px</output></div>
                <p class="sp-hint">También puedes arrastrar el borde inferior del número de fila, y el borde derecho de cada encabezado para el ancho. Arrastra un encabezado para moverlo de lugar.</p></div>
            <div class="sp-cols-block"><h5>Vista personalizada</h5>
                <div class="sp-view-save"><input type="text" id="spViewName" maxlength="60" placeholder="Nombre de la nueva vista"><button type="button" class="sp-btn" id="spViewSave">Guardar como vista nueva</button></div>
                ${view ? `<div class="sp-view-manage"><button type="button" class="sp-link" id="spViewUpdate">Actualizar «${esc(view.name)}»</button><button type="button" class="sp-link is-danger" id="spViewDelete">Eliminar vista</button></div>` : ''}
                <p class="sp-hint">Una vista guarda qué columnas ves, su orden, sus anchos y el alto de las filas.</p></div>`;
        openPop(e.currentTarget, html, () => {
            const sync = () => {
                pop.querySelectorAll('[data-col]').forEach(cb => cb.checked = !state.hidden.has(cb.dataset.col));
                $('spRowH').value = state.rowHeight; $('spRowHOut').textContent = state.rowHeight + ' px';
            };
            pop.querySelectorAll('[data-col]').forEach(cb => cb.onchange = () => {
                cb.checked ? state.hidden.delete(cb.dataset.col) : state.hidden.add(cb.dataset.col);
                saveLocal(); state.selected = null; render();
            });
            $('spColsAll').onclick = () => { state.hidden.clear(); saveLocal(); state.selected = null; render(); sync(); };
            $('spColsReset').onclick = () => { applyLayout(defaultLayout()); state.viewId = null; renderViewSelect(); saveLocal(); refresh(); sync(); };
            $('spRowH').oninput = (ev) => {
                state.rowHeight = +ev.target.value; $('spRowHOut').textContent = state.rowHeight + ' px';
                applyRowHeight(); saveLocal();
            };
            $('spViewSave').onclick = () => saveNewView($('spViewName').value.trim());
            $('spViewName').onkeydown = (ev) => { if (ev.key === 'Enter') saveNewView(ev.target.value.trim()); };
            $('spViewUpdate')?.addEventListener('click', updateCurrentView);
            $('spViewDelete')?.addEventListener('click', deleteCurrentView);
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

    // ---- Exportar (CSV de lo que se ve: columnas visibles en su orden + filtros + orden) ----
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

    renderViewSelect();
    refresh();
})();
</script>
@endpush
