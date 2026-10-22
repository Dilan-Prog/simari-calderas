{{-- Estilos compartidos del módulo Monitoreo (portal del cliente). Prefijo mn-. --}}
<style>
    .mn-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
    .mn-card { background: var(--ts-bg-white, #fff); border: 1px solid #E5E7EB; border-radius: 10px; padding: 1.125rem; box-shadow: var(--ts-shadow-sm, 0 1px 2px rgba(0,0,0,.05)); }
    .mn-card--link { display: flex; flex-direction: column; gap: .75rem; text-decoration: none; color: inherit; transition: box-shadow .15s, border-color .15s, transform .15s; }
    .mn-card--link:hover { border-color: var(--ts-primary, #ff6213); box-shadow: 0 6px 16px rgba(0,0,0,.08); transform: translateY(-1px); }
    .mn-card--link:focus-visible { outline: 2px solid var(--ts-primary, #ff6213); outline-offset: 2px; }
    .mn-card__head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .mn-card__title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
    .mn-muted { color: var(--ts-text-muted, #6B7280); font-size: .8125rem; }
    .mn-section-title { margin: 0 0 .75rem; font-size: 1rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    .mn-block { margin-bottom: 1rem; }

    .mn-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .2rem .6rem; border-radius: 9999px; font-size: .6875rem; font-weight: 600; border: 1px solid transparent; white-space: nowrap; }
    .mn-badge--active { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
    .mn-badge--paused { background: #FFFBEB; color: #D97706; border-color: #FDE68A; }
    .mn-badge--completed { background: #F3F4F6; color: #6B7280; border-color: #D1D5DB; }
    .mn-badge--ok { background: #F0FDF4; color: #15803D; border-color: #BBF7D0; }
    .mn-badge--bad { background: #FEF2F2; color: #B91C1C; border-color: #FECACA; }
    .mn-badge--neutral { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }
    .mn-badge--param { background: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }
    .mn-badge--chem { background: #F0FDFA; color: #0F766E; border-color: #99F6E4; }
    .mn-badge--orange { background: #FFF4EC; color: #C2410C; border-color: #FED7AA; }

    .mn-progress { height: 8px; border-radius: 9999px; background: #E5E7EB; overflow: hidden; }
    .mn-progress > span { display: block; height: 100%; background: var(--ts-primary, #ff6213); border-radius: 9999px; }
    .mn-progress-label { display: flex; justify-content: space-between; gap: .5rem; font-size: .75rem; color: var(--ts-text-muted, #6B7280); margin-bottom: .25rem; }

    .mn-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem .75rem; font-size: .8125rem; }
    .mn-meta dt { font-size: .6875rem; text-transform: uppercase; letter-spacing: .04em; color: var(--ts-text-muted, #6B7280); font-weight: 600; }
    .mn-meta dd { margin: 0; color: var(--ts-text-title, #111827); font-weight: 600; }
    .mn-bad-text { color: #B91C1C; }

    .mn-kpis { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .mn-kpi { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: .75rem .875rem; }
    .mn-kpi__label { font-size: .6875rem; text-transform: uppercase; letter-spacing: .04em; color: var(--ts-text-muted, #6B7280); font-weight: 600; }
    .mn-kpi__value { margin-top: .125rem; font-size: 1.25rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    .mn-kpi__value--bad { color: #B91C1C; }
    .mn-kpi__value--sm { font-size: 1rem; }

    .mn-tabs { display: flex; gap: .25rem; border-bottom: 1px solid #E5E7EB; margin-bottom: 1rem; overflow-x: auto; scrollbar-width: thin; }
    .mn-tab { padding: .625rem 1rem; font-size: .875rem; font-weight: 600; color: var(--ts-text-muted, #6B7280); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; }
    .mn-tab:hover { color: var(--ts-text-title, #111827); }
    .mn-tab[aria-current="page"] { color: var(--ts-primary, #ff6213); border-bottom-color: var(--ts-primary, #ff6213); }
    .mn-tab:focus-visible { outline: 2px solid var(--ts-primary, #ff6213); outline-offset: -2px; border-radius: 6px; }

    .mn-toolbar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; margin-bottom: 1rem; }
    .mn-toolbar .ts-field { min-width: 180px; }
    .mn-toolbar--end { margin-left: auto; }

    .mn-form-grid { display: grid; gap: 1rem; grid-template-columns: repeat(12, minmax(0, 1fr)); align-items: start; }
    .mn-col-2 { grid-column: span 2; }
    .mn-col-3 { grid-column: span 3; }
    .mn-col-4 { grid-column: span 4; }
    .mn-col-6 { grid-column: span 6; }
    .mn-col-12 { grid-column: 1 / -1; }
    .mn-unit-wrap { position: relative; }
    .mn-unit-wrap .ts-input { padding-right: 4.5rem; }
    .mn-unit-wrap__unit { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); font-size: .75rem; color: var(--ts-text-muted, #6B7280); pointer-events: none; max-width: 4rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mn-hint { font-size: .75rem; color: var(--ts-text-muted, #6B7280); min-height: 1rem; }
    .mn-error { font-size: .75rem; color: #DC2626; }
    .mn-input-error { border-color: #DC2626 !important; }
    .mn-alert { padding: .75rem 1rem; border-radius: 8px; font-size: .8125rem; margin-bottom: 1rem; }
    .mn-alert--warn { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; }
    .mn-alert--err { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
    .mn-alert--info { background: #EFF6FF; border: 1px solid #BFDBFE; color: #1E40AF; }

    .mn-table-wrap { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; overflow-x: auto; }
    .mn-table { width: 100%; border-collapse: collapse; font-size: .8125rem; min-width: 720px; }
    .mn-table thead th { background: var(--ts-table-header, #1A2535); color: #fff; text-align: left; padding: .625rem .875rem; font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
    .mn-table tbody td { padding: .625rem .875rem; border-top: 1px solid #F3F4F6; color: var(--ts-text-body, #374151); vertical-align: top; }
    .mn-table tbody tr:hover td { background: #FAFAFA; }
    .mn-table td.mn-num, .mn-table th.mn-num { text-align: right; font-variant-numeric: tabular-nums; }
    .mn-table .mn-actions { white-space: nowrap; text-align: right; }
    .mn-table .mn-actions form { display: inline; margin: 0; }
    .mn-btn-sm { height: 32px; padding: 0 .625rem; font-size: .75rem; }
    .mn-empty { text-align: center; padding: 2.5rem 1.25rem; color: var(--ts-text-muted, #6B7280); font-size: .875rem; }
    .mn-empty svg { color: var(--ts-text-light, #9CA3AF); margin-bottom: .75rem; }
    .mn-empty p { margin: 0 0 1rem; }

    .mn-weeks { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
    .mn-week { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 1rem; display: flex; flex-direction: column; gap: .75rem; }
    .mn-week--current { border-color: var(--ts-primary, #ff6213); box-shadow: 0 0 0 1px var(--ts-primary, #ff6213); }
    .mn-week--future { opacity: .55; background: #F9FAFB; }
    .mn-week__head { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .mn-week__title { margin: 0; font-size: 1rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    .mn-week__sub { font-size: .75rem; color: var(--ts-text-muted, #6B7280); }
    .mn-week__sect { margin: 0 0 .25rem; font-size: .6875rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; color: var(--ts-text-muted, #6B7280); }
    .mn-mini { width: 100%; border-collapse: collapse; font-size: .75rem; }
    .mn-mini th { text-align: left; font-weight: 600; color: var(--ts-text-muted, #6B7280); padding: .25rem .25rem .25rem 0; white-space: nowrap; }
    .mn-mini td { padding: .3rem .25rem .3rem 0; border-top: 1px solid #F3F4F6; color: var(--ts-text-body, #374151); }
    .mn-mini td.mn-num, .mn-mini th.mn-num { text-align: right; font-variant-numeric: tabular-nums; padding-right: 0; padding-left: .5rem; }
    .mn-mini-scroll { overflow-x: auto; }

    .mn-chart-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .75rem; margin-top: 1rem; }

    .mn-modal { position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(17, 24, 39, .55); }
    .mn-modal.is-open { display: flex; }
    .mn-modal__card { background: #fff; border-radius: 12px; width: 100%; max-width: 560px; max-height: calc(100dvh - 2rem); display: flex; flex-direction: column; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
    .mn-modal__card--sm { max-width: 440px; }
    .mn-modal__head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #F3F4F6; }
    .mn-modal__title { margin: 0; font-size: 1.0625rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    .mn-modal__close { border: 0; background: transparent; cursor: pointer; width: 36px; height: 36px; border-radius: 8px; color: var(--ts-text-muted, #6B7280); font-size: 1.125rem; line-height: 1; }
    .mn-modal__close:hover { background: #F3F4F6; }
    .mn-modal__body { padding: 1.25rem; overflow-y: auto; display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
    .mn-modal__body .mn-full { grid-column: 1 / -1; }
    .mn-modal__foot { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid #F3F4F6; }
    .mn-modal form { display: flex; flex-direction: column; min-height: 0; margin: 0; }
    .mn-confirm { padding: 1.5rem 1.25rem .5rem; text-align: center; font-size: .875rem; color: var(--ts-text-body, #374151); line-height: 1.5; }
    .mn-confirm strong { color: var(--ts-text-title, #111827); overflow-wrap: anywhere; }
    .mn-check { display: flex; align-items: center; gap: .5rem; font-size: .8125rem; color: var(--ts-text-body, #374151); cursor: pointer; min-height: 40px; }
    .mn-check input { width: 18px; height: 18px; accent-color: var(--ts-primary, #ff6213); }
    body.mn-modal-open { overflow: hidden; }

    .mn-details > summary { cursor: pointer; font-weight: 700; font-size: 1rem; color: var(--ts-text-title, #111827); list-style: none; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .mn-details > summary::-webkit-details-marker { display: none; }
    .mn-details > summary::after { content: '+'; font-size: 1.25rem; color: var(--ts-text-muted, #6B7280); }
    .mn-details[open] > summary::after { content: '\2212'; }
    .mn-details[open] > summary { margin-bottom: .75rem; }

    @media (max-width: 1100px) {
        .mn-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .mn-col-3, .mn-col-4 { grid-column: span 6; }
        .mn-col-2 { grid-column: 1 / -1; }
    }
    @media (max-width: 640px) {
        .mn-grid, .mn-weeks { grid-template-columns: 1fr; }
        .mn-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .mn-col-3, .mn-col-4, .mn-col-6 { grid-column: 1 / -1; }
        .mn-toolbar .ts-field { min-width: 0; flex: 1 1 100%; }
        .mn-toolbar--end { margin-left: 0; }
        .mn-modal { align-items: flex-end; padding: 0; }
        .mn-modal__card { max-width: none; border-radius: 14px 14px 0 0; max-height: 94dvh; }
        .mn-modal__body { grid-template-columns: 1fr; }
        .mn-modal__foot { flex-direction: column-reverse; }
        .mn-modal__foot .ts-btn { width: 100%; justify-content: center; height: 46px; }
        .mn-header-actions .ts-btn { flex: 1 1 auto; justify-content: center; }
    }

    @media print {
        .admin-sidebar, .admin-top-header, .sidebar-backdrop, .mn-no-print, .mn-tabs, .toast-notification { display: none !important; }
        .ts-page { padding: 0 !important; overflow: visible !important; }
        .admin-main { margin: 0 !important; }
    }
</style>
