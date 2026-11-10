{{-- Estilos del módulo Monitoreo de Clientes (prefijo mon-). Se apoya en erp-core.css (erp-*). --}}
<style>
    .mon-page { --mon-line: var(--erp-line, #e6e8eb); }
    .mon-flash { padding: 11px 16px; border-radius: 10px; margin-bottom: 14px; font-size: 13px; font-weight: 600; }
    .mon-flash--ok { background: #e6f4ea; color: #15803d; border: 1px solid #bfe3c9; }
    .mon-flash--err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .mon-flash ul { margin: 6px 0 0 18px; padding: 0; font-weight: 500; }
    .mon-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
    .mon-kpi { background: #fff; border: 1px solid var(--mon-line); border-radius: 12px; padding: 14px 16px; text-decoration: none; color: inherit; display: block; }
    .mon-kpi b { display: block; font-size: 26px; font-weight: 800; color: var(--erp-ink, #17191c); line-height: 1.1; margin-top: 4px; }
    .mon-kpi span { font-size: 12px; font-weight: 600; color: var(--erp-muted, #6b7280); display: flex; align-items: center; gap: 6px; }
    .mon-kpi small { display: block; margin-top: 2px; font-size: 11.5px; color: var(--erp-faint, #9ca3af); }
    a.mon-kpi:hover { border-color: var(--erp-accent, #f97316); }
    .mon-toolbar-form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; width: 100%; }
    .mon-toolbar-form .erp-search { flex: 1 1 260px; }
    .mon-toolbar-form .erp-search input { width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 8px; padding: 8px 10px; font-size: 13px; outline: none; }
    .mon-num { text-align: right; white-space: nowrap; }
    .mon-muted { color: var(--erp-muted, #6b7280); font-size: 12px; }
    .mon-link { color: var(--erp-ink, #17191c); font-weight: 700; text-decoration: none; }
    .mon-link:hover { color: var(--erp-accent-dark, #c2410c); }
    .mon-bad { color: #b91c1c; font-weight: 700; }
    .mon-badge { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 700; background: #f1f5f9; color: #475569; white-space: nowrap; }
    .mon-badge--cust { background: #fff4ec; color: #c2410c; }
    .mon-badge--param { background: #dbeafe; color: #1e40af; }
    .mon-badge--chem { background: #ccfbf1; color: #115e59; }

    /* Pestañas (enlaces con ?tab=) */
    .mon-tabs a.erp-tab { text-decoration: none; display: inline-block; }
    .mon-panel { padding: 16px 18px; }
    .mon-panel-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
    .mon-panel-toolbar form { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 0; }
    .mon-panel-toolbar .spacer { flex: 1; }
    .mon-lbl { font-size: 11px; font-weight: 700; color: var(--erp-muted, #6b7280); }

    /* Resumen: stats + tarjetas por semana */
    .mon-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-bottom: 18px; }
    .mon-stat { border: 1px solid var(--mon-line); border-radius: 10px; padding: 10px 12px; background: #fafbfc; }
    .mon-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--erp-ink, #17191c); }
    .mon-stat span { font-size: 11.5px; color: var(--erp-muted, #6b7280); font-weight: 600; }
    .mon-weeks { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 12px; }
    .mon-week { border: 1px solid var(--mon-line); border-radius: 12px; padding: 12px 14px; background: #fff; }
    .mon-week.is-current { border-color: var(--erp-accent, #f97316); box-shadow: 0 0 0 2px rgba(249, 115, 22, .12); }
    .mon-week.is-future { background: #fafbfc; opacity: .75; }
    .mon-week-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
    .mon-week-head strong { font-size: 14px; }
    .mon-week-line { display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; color: var(--erp-text, #374151); margin-bottom: 8px; }
    .mon-mini { width: 100%; border-collapse: collapse; font-size: 12px; }
    .mon-mini th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .03em; color: var(--erp-muted, #6b7280); padding: 3px 4px; border-bottom: 1px solid var(--mon-line); }
    .mon-mini td { padding: 4px; border-bottom: 1px solid var(--erp-line-soft, #eef0f2); vertical-align: top; }
    .mon-mini td.n { text-align: right; white-space: nowrap; }

    /* Formularios / modales */
    .mon-modal { position: fixed; inset: 0; z-index: 1200; display: none; align-items: center; justify-content: center; padding: 16px; background: rgba(17, 24, 39, .45); }
    .mon-modal.is-open { display: flex; }
    .mon-modal-box { background: #fff; border-radius: 14px; width: 100%; max-width: 520px; max-height: 90vh; overflow: auto; padding: 20px; box-shadow: 0 20px 50px rgba(0, 0, 0, .25); }
    .mon-modal-box--wide { max-width: 820px; }
    .mon-modal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .mon-modal-head h3 { margin: 0; font-size: 17px; font-weight: 800; }
    .mon-x { background: none; border: 0; font-size: 24px; line-height: 1; cursor: pointer; color: var(--erp-muted, #6b7280); }
    .mon-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .mon-grid2 .full { grid-column: 1 / -1; }
    .mon-eq-row { border: 1px solid var(--mon-line); border-radius: 10px; padding: 10px; margin-bottom: 8px; background: #fcfcfd; }
    .mon-eq-row form { display: grid; grid-template-columns: 1.4fr 1fr 1.2fr auto; gap: 8px; align-items: end; margin: 0; }
    .mon-eq-actions { display: flex; gap: 6px; align-items: center; }
    .mon-eq-meta { display: flex; gap: 10px; align-items: center; margin-top: 6px; font-size: 11.5px; color: var(--erp-muted, #6b7280); flex-wrap: wrap; }

    /* Editor de elementos del programa */
    .mon-presets { display: flex; gap: 8px; flex-wrap: wrap; }
    .mon-item { border: 1px solid var(--mon-line); border-radius: 10px; padding: 12px; margin-bottom: 10px; background: #fcfcfd; position: relative; }
    .mon-item.is-customer { border-color: #fed7aa; background: #fffaf5; }
    .mon-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
    .mon-item-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; }
    .mon-item-grid .c2 { grid-column: span 2; }
    .mon-item-grid .c3 { grid-column: span 3; }
    .mon-item[data-kind="parameter"] [data-only="chemical"] { display: none; }
    .mon-item[data-kind="chemical"] [data-only="parameter"] { display: none; }
    .mon-suggest { position: absolute; z-index: 30; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #d4d7dc; border-radius: 8px; box-shadow: 0 8px 20px rgba(0, 0, 0, .1); max-height: 220px; overflow: auto; }
    .mon-suggest[hidden] { display: none; }
    .mon-suggest button { display: block; width: 100%; text-align: left; background: none; border: 0; padding: 8px 12px; font-size: 13px; cursor: pointer; }
    .mon-suggest button:hover { background: var(--erp-accent-soft, #fff4ec); }
    .mon-rel { position: relative; }

    .mon-chart-wrap { max-width: 1100px; }

    /* Menú desplegable de exportación */
    details.mon-menu { position: relative; }
    details.mon-menu > summary { list-style: none; }
    details.mon-menu > summary::-webkit-details-marker { display: none; }
    .mon-menu-list { position: absolute; right: 0; top: calc(100% + 4px); z-index: 40; min-width: 210px; background: #fff; border: 1px solid #d4d7dc; border-radius: 10px; box-shadow: 0 10px 24px rgba(0, 0, 0, .12); padding: 6px; display: flex; flex-direction: column; }
    .mon-menu-list a { padding: 8px 10px; border-radius: 6px; font-size: 13px; font-weight: 600; color: var(--erp-text, #374151); text-decoration: none; }
    .mon-menu-list a:hover { background: var(--erp-accent-soft, #fff4ec); color: var(--erp-accent-dark, #c2410c); }
    @media (max-width: 900px) {
        .mon-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mon-item-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mon-item-grid .c2, .mon-item-grid .c3 { grid-column: span 2; }
        .mon-eq-row form { grid-template-columns: 1fr 1fr; }
        .mon-grid2 { grid-template-columns: 1fr; }
    }
    /* Resumen del Gantt para teléfono: oculto fuera del bloque móvil. */
    .mon-gsum { display: none; }

    /* ══════════ Teléfono (≤768px) ══════════
       Todo acotado a .mon-admin (páginas admin del módulo) y .mon-admin-modal
       (modales de show). Escritorio queda idéntico. */
    @media (max-width: 768px) {
        .mon-admin { max-width: 100%; overflow-x: clip; box-sizing: border-box; }

        /* Controles táctiles: 44px y 16px (evita zoom en iOS) */
        .mon-admin .erp-btn, .mon-admin-modal .erp-btn { min-height: 44px; justify-content: center; font-size: 14px; box-sizing: border-box; white-space: normal; text-align: center; }
        .mon-admin .erp-btn--sm, .mon-admin-modal .erp-btn--sm { padding: 8px 12px; }
        .mon-admin .erp-input, .mon-admin .erp-select, .mon-admin .erp-search input,
        .mon-admin-modal .erp-input, .mon-admin-modal .erp-select { font-size: 16px; min-height: 44px; box-sizing: border-box; max-width: 100%; }
        .mon-admin textarea.erp-input, .mon-admin-modal textarea.erp-input { min-height: 88px; }
        .mon-admin input[type="checkbox"], .mon-admin-modal input[type="checkbox"] { width: 20px; height: 20px; flex: 0 0 auto; }

        /* Cabeceras de página */
        .mon-admin .erp-page-head { flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 14px; }
        .mon-admin .erp-title { font-size: 22px; line-height: 1.25; }
        .mon-admin .erp-page-head > .erp-head-actions { width: 100%; }
        .mon-admin .erp-page-head > .erp-head-actions > .erp-btn { flex: 1 1 calc(50% - 8px); }

        /* KPIs */
        .mon-admin .mon-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .mon-admin .mon-kpis--3 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        .mon-admin .mon-kpis--3 > :last-child { grid-column: 1 / -1; }
        .mon-admin .mon-kpi { padding: 12px; }
        .mon-admin .mon-kpi b { font-size: 22px; }

        /* Worklist / filtros */
        .mon-admin .erp-views { padding: 12px; }
        .mon-admin .erp-pill { min-height: 40px; padding: 6px 14px; font-size: 13px; }
        .mon-admin .erp-toolbar { padding: 12px; }
        .mon-admin .mon-toolbar-form > * { flex: 1 1 100%; width: 100%; }
        .mon-admin .erp-wl-footer { flex-direction: column; align-items: stretch; padding: 12px; }
        .mon-admin .erp-wl-footer nav { overflow-x: auto; }

        /* Tablas → tarjetas (.mon-cards-table) */
        .mon-admin .erp-table-wrap:has(> .mon-cards-table) { overflow: visible; }
        .mon-admin .mon-cards-table, .mon-admin .mon-cards-table tbody { display: block; width: 100%; }
        .mon-admin .mon-cards-table thead { display: none; }
        .mon-admin .mon-cards-table tr.mon-card-row {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 12px;
            background: #fff; border: 1px solid var(--mon-line); border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05); padding: 14px; margin-bottom: 12px;
        }
        .mon-admin .mon-cards-table tr.mon-card-row:hover { background: #fff; }
        .mon-admin .mon-cards-table tr.mon-card-row > td {
            display: block; padding: 0; border: 0; text-align: left; font-size: 14px;
            overflow-wrap: anywhere; white-space: normal; max-width: none !important;
        }
        .mon-admin .mon-cards-table tr.mon-card-row > td::before {
            content: attr(data-label); display: block; margin-bottom: 2px;
            font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--erp-muted, #6b7280);
        }
        .mon-admin .mon-cards-table tr.mon-card-row > td.mon-c-head { grid-column: 1 / -1; padding-bottom: 10px; border-bottom: 1px solid var(--erp-line-soft, #eef0f2); font-size: 15px; }
        .mon-admin .mon-cards-table tr.mon-card-row > td.mon-c-head::before { display: none; }
        .mon-admin .mon-cards-table tr.mon-card-row > td.mon-c-full { grid-column: 1 / -1; }
        .mon-admin .mon-cards-table tr.mon-card-row > td.mon-c-actions { grid-column: 1 / -1; display: flex; gap: 8px; padding-top: 10px; border-top: 1px solid var(--erp-line-soft, #eef0f2); }
        .mon-admin .mon-cards-table tr.mon-card-row > td.mon-c-actions::before { display: none; }
        .mon-admin .mon-cards-table td.mon-c-actions > .erp-btn, .mon-admin .mon-cards-table td.mon-c-actions > form { flex: 1 1 0; display: flex !important; }
        .mon-admin .mon-cards-table td.mon-c-actions > form > .erp-btn { width: 100%; }
        .mon-admin .mon-cards-table tr.mon-empty-row, .mon-admin .mon-cards-table tr.mon-empty-row > td { display: block; padding: 0; border: 0; }
        .mon-index .erp-table-wrap { padding: 12px 12px 0; }
        .mon-index .mon-link { font-size: 15px; }

        /* ── Detalle del programa ── */
        .mon-show .erp-objhead { padding: 14px; }
        .mon-show .erp-objhead-top { flex-direction: column; gap: 12px; }
        .mon-show .erp-objhead-title { font-size: 20px; line-height: 1.25; overflow-wrap: anywhere; }
        .mon-show .erp-objhead-back { display: inline-flex; align-items: center; min-height: 36px; padding: 4px 12px; }
        .mon-show .erp-objhead-actions { width: 100%; gap: 8px; }
        .mon-show .erp-objhead-actions > * { flex: 1 1 calc(50% - 8px); margin: 0; }
        .mon-show .erp-objhead-actions > .erp-btn, .mon-show .erp-objhead-actions > form > .erp-btn, .mon-show details.mon-menu > summary { width: 100%; }
        .mon-show details.mon-menu { position: static; }
        .mon-show .erp-objhead-actions { position: relative; }
        .mon-show .mon-menu-list { left: 0; right: 0; min-width: 0; }
        .mon-show .mon-menu-list a { min-height: 44px; display: flex; align-items: center; font-size: 15px; }
        .mon-show .erp-objhead-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; }

        .mon-show .erp-tabnav { padding: 0 4px; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .mon-show .erp-tab { min-height: 44px; display: inline-flex; align-items: center; font-size: 14px; }
        .mon-show .mon-panel { padding: 12px; }
        .mon-show .mon-panel-toolbar { flex-direction: column; align-items: stretch; }
        .mon-show .mon-panel-toolbar form { flex-direction: column; align-items: stretch; width: 100%; }
        .mon-show .mon-panel-toolbar form > * { width: 100%; }
        .mon-show .mon-panel-toolbar form > label { min-height: 44px; font-size: 14px !important; }
        .mon-show .mon-panel-toolbar .spacer { display: none; }
        .mon-show .mon-panel-toolbar > .erp-btn { width: 100%; }

        /* Resumen: semana actual primero, tarjetas a una columna */
        .mon-show .mon-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mon-show .mon-weeks { grid-template-columns: 1fr; }
        .mon-show .mon-week.is-current { order: -1; }
        .mon-show .mon-mini th, .mon-show .mon-mini td { padding: 4px 3px; }
        .mon-show .mon-mini td.n { white-space: normal; }

        /* Gantt: resumen compacto + diagrama con scroll interno, 1ª columna y encabezado fijos */
        .mon-show .mon-gsum { display: block; margin-bottom: 14px; }
        .mon-gsum-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; margin-bottom: 10px; }
        .mon-gsum-head strong { font-size: 15px; }
        .mon-gsum-oor { flex-basis: 100%; font-size: 13px; font-weight: 600; }
        .mon-gsum-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
        .mon-gsum-item { border: 1px solid var(--mon-line); border-radius: 10px; padding: 10px 12px; background: #fff; }
        .mon-gsum-item.is-bad { border-color: #fecaca; background: #fef2f2; }
        .mon-gsum-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .mon-gsum-name { font-size: 14px; font-weight: 700; overflow-wrap: anywhere; }
        .mon-gsum-bar { height: 6px; border-radius: 3px; background: #dbeafe; margin: 8px 0 6px; overflow: hidden; }
        .mon-gsum-bar i { display: block; height: 100%; background: #2563eb; }
        .mon-gsum-bar.is-chem { background: #ccfbf1; }
        .mon-gsum-bar.is-chem i { background: #0f766e; }
        .mon-gsum-meta { display: flex; flex-wrap: wrap; gap: 2px 12px; font-size: 12.5px; color: var(--erp-text, #374151); }
        .mon-gsum-hint { margin: 10px 0 0; }
        .mon-show .mon-gantt-wrap .mg-root { --mg-label: 124px; --mg-col: 60px; }
        .mon-show .mon-gantt-wrap .mg-scroll { max-height: 70vh; max-height: 70dvh; overflow: auto; -webkit-overflow-scrolling: touch; overscroll-behavior: contain; }
        .mon-show .mon-gantt-wrap .mg-head { position: sticky; top: 0; z-index: 5; box-shadow: 0 1px 0 var(--mg-line); }
        .mon-show .mon-gantt-wrap .mg-head .mg-label { z-index: 6; }
        .mon-show .mon-gantt-wrap .mg-label { padding: 6px 8px; box-shadow: 1px 0 0 var(--mg-line); }
        .mon-show .mon-gantt-wrap .mg-label-name { font-size: 12.5px; }
        .mon-show .mon-gantt-wrap .mg-week { font-size: 12px; }
        .mon-show .mon-gantt-wrap .mg-bar-text { font-size: 11px; padding: 0 4px; }
        .mon-show .mon-gantt-wrap .mg-legend { font-size: 12.5px; }
        .mon-show .mon-chart-wrap { max-width: 100%; overflow-x: auto; }
        .mon-show .mon-chart-wrap svg { max-width: 100%; height: auto; }

        /* ── Modales: hoja inferior (registro) / pantalla completa (equipos) ── */
        .mon-admin-modal.is-open { align-items: flex-end; padding: 0; }
        .mon-admin-modal .mon-modal-box {
            width: 100%; max-width: 100%; max-height: 92vh; max-height: 92dvh; box-sizing: border-box;
            border-radius: 16px 16px 0 0; padding: 0 16px calc(16px + env(safe-area-inset-bottom, 0px));
            overscroll-behavior: contain; -webkit-overflow-scrolling: touch;
        }
        .mon-admin-modal .mon-modal-box--wide { height: 100vh; height: 100dvh; max-height: 100vh; max-height: 100dvh; border-radius: 0; }
        .mon-admin-modal .mon-modal-head { position: sticky; top: 0; z-index: 3; background: #fff; margin: 0 -16px 14px; padding: 8px 8px 8px 16px; border-bottom: 1px solid var(--mon-line); }
        .mon-admin-modal .mon-modal-head h3 { font-size: 16px; overflow-wrap: anywhere; }
        .mon-admin-modal .mon-x { min-width: 44px; min-height: 44px; font-size: 28px; }
        .mon-admin-modal .mon-grid2 { grid-template-columns: 1fr; }
        .mon-admin-modal .mon-grid2 .full[style] { text-align: left !important; }
        .mon-admin-modal .mon-grid2 .full > .erp-btn { width: 100%; }
        .mon-admin-modal .erp-form-actions {
            position: sticky; bottom: 0; z-index: 3; background: #fff;
            margin: 14px -16px calc(-16px - env(safe-area-inset-bottom, 0px)); padding: 12px 16px calc(12px + env(safe-area-inset-bottom, 0px));
        }
        .mon-admin-modal .erp-form-actions .erp-btn { flex: 1 1 0; }
        .mon-admin-modal .mon-eq-row form { grid-template-columns: 1fr; }
        .mon-admin-modal .mon-eq-actions { justify-content: space-between; }
        .mon-admin-modal .mon-eq-actions label { min-height: 44px; font-size: 14px !important; gap: 8px !important; }
        .mon-admin-modal .mon-eq-actions .erp-btn { flex: 1 1 auto; max-width: 60%; }
        .mon-admin-modal .mon-eq-meta { justify-content: space-between; }
        html.mon-modal-open, html.mon-modal-open body { overflow: hidden; }

        /* ── Formulario del programa ── */
        .mon-form-page .erp-card-head { flex-direction: column; align-items: stretch; padding: 14px 14px 0; }
        .mon-form-page .erp-card-body { padding: 14px; }
        .mon-form-page .erp-form-grid { grid-template-columns: 1fr; gap: 14px; }
        .mon-form-page .erp-form-grid > .erp-field { grid-column: 1 / -1 !important; font-size: 13px; }
        .mon-form-page .mon-presets { width: 100%; }
        .mon-form-page .mon-presets > .erp-btn { flex: 1 1 calc(50% - 8px); }
        .mon-form-page .mon-suggest { max-height: 50vh; }
        .mon-form-page .mon-suggest button { min-height: 44px; font-size: 15px; overflow-wrap: anywhere; }
        .mon-form-page .mon-item { padding: 12px; }
        .mon-form-page .mon-item-top { gap: 8px; }
        .mon-form-page .mon-item-top > .erp-select { flex: 1 1 100%; width: 100%; }
        .mon-form-page .mon-item-actions { margin-left: 0 !important; width: 100%; gap: 8px !important; }
        .mon-form-page .mon-item-actions .erp-btn { min-width: 44px; font-size: 16px; }
        .mon-form-page .mon-item-actions [data-item-remove] { flex: 1 1 auto; font-size: 14px; }
        .mon-form-page .mon-item-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .mon-form-page .mon-item-grid > .erp-field { grid-column: span 1; font-size: 13px; }
        .mon-form-page .mon-item-grid > .c3, .mon-form-page .mon-item-grid > :nth-child(2),
        .mon-form-page .mon-item-grid > [data-only="chemical"] { grid-column: 1 / -1; }
        .mon-form-page .mon-savebar {
            position: sticky; bottom: 0; z-index: 20; margin: 12px 0 0; padding: 12px 12px calc(12px + env(safe-area-inset-bottom, 0px));
            background: #fff; border: 1px solid var(--mon-line); border-radius: 12px 12px 0 0; box-shadow: 0 -6px 16px rgba(16, 24, 40, .08);
        }
        .mon-form-page .mon-savebar .erp-btn { flex: 1 1 0; font-size: 15px; }
    }
    @media print {
        .admin-sidebar, .admin-top-header, .sidebar-backdrop, .mon-noprint, .erp-tabnav, .erp-head-actions, .erp-objhead-actions, .erp-objhead-back, .mon-gsum { display: none !important; }
        .admin-main { margin: 0 !important; padding: 0 !important; }
        .erp-card { border: 0; }
    }
</style>
