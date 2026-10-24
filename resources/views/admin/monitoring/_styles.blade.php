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
    @media print {
        .admin-sidebar, .admin-top-header, .sidebar-backdrop, .mon-noprint, .erp-tabnav, .erp-head-actions, .erp-objhead-actions, .erp-objhead-back { display: none !important; }
        .admin-main { margin: 0 !important; padding: 0 !important; }
        .erp-card { border: 0; }
    }
</style>
