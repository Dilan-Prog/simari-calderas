<style>
    .md-tabs { display:flex; gap:.25rem; border-bottom:1px solid var(--ts-border); margin-bottom:1.25rem; overflow-x:auto; }
    .md-tab {
        padding:.625rem 1rem; font-size:.875rem; font-weight:500; color:var(--ts-text-muted); text-decoration:none;
        border-bottom:2px solid transparent; margin-bottom:-1px; white-space:nowrap; display:inline-flex; align-items:center; gap:.5rem;
    }
    .md-tab:hover { color:var(--ts-text-title); }
    .md-tab--active { color:var(--ts-primary); border-bottom-color:var(--ts-primary); }
    .md-tab__count { background:var(--ts-border-light); color:var(--ts-text-muted); border-radius:999px; font-size:.6875rem; padding:.0625rem .5rem; font-weight:600; }

    .md-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; margin-bottom:1.25rem; }
    .md-stat { background:var(--ts-bg-white); border:1px solid var(--ts-border-light); border-radius:8px; padding:.875rem 1rem; box-shadow:var(--ts-shadow-sm); }
    .md-stat__label { font-size:.75rem; color:var(--ts-text-muted); margin:0 0 .25rem; }
    .md-stat__value { font-size:1.5rem; font-weight:700; color:var(--ts-text-title); margin:0; line-height:1.1; }

    .md-filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:1rem; }
    .md-input, .md-select {
        height:36px; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px; font-size:.8125rem;
        color:var(--ts-text-body); background:var(--ts-bg-white); outline:none; font-family:var(--ts-font);
    }
    .md-input { min-width:220px; flex:1 1 220px; max-width:360px; }
    .md-input:focus, .md-select:focus { border-color:var(--ts-primary); }
    .md-btn {
        height:36px; padding:0 1rem; border:none; border-radius:6px; cursor:pointer; background:var(--ts-primary); color:#fff;
        font-size:.8125rem; font-weight:500; display:inline-flex; align-items:center; gap:.375rem; text-decoration:none; font-family:var(--ts-font);
    }
    .md-btn:hover { background:var(--ts-primary-hover); color:#fff; }
    .md-btn--ghost { background:var(--ts-bg-white); color:var(--ts-text-body); border:1px solid var(--ts-border); }
    .md-btn--ghost:hover { background:#F9FAFB; color:var(--ts-text-body); }

    .md-card { background:var(--ts-bg-white); border:1px solid var(--ts-border-light); border-radius:8px; box-shadow:var(--ts-shadow-sm); margin-bottom:1rem; }
    .md-card__head { padding:.875rem 1.125rem; border-bottom:1px solid var(--ts-border-light); font-size:.9375rem; font-weight:600; color:var(--ts-text-title); }
    .md-card__body { padding:1.125rem; }

    .md-table-wrap { overflow-x:auto; }
    .md-table { width:100%; border-collapse:collapse; font-size:.8125rem; color:var(--ts-text-body); }
    .md-table th {
        text-align:left; font-size:.6875rem; letter-spacing:.04em; text-transform:uppercase; color:var(--ts-text-muted);
        padding:.625rem .875rem; background:#F9FAFB; border-bottom:1px solid var(--ts-border-light); white-space:nowrap;
    }
    .md-table td { padding:.75rem .875rem; border-bottom:1px solid var(--ts-border-light); vertical-align:middle; }
    .md-table tr:last-child td { border-bottom:none; }
    .md-num { text-align:right; font-variant-numeric:tabular-nums; }
    .md-folio { font-family:monospace; font-weight:600; color:var(--ts-primary); text-decoration:none; }
    .md-folio:hover { text-decoration:underline; }
    .md-muted { color:var(--ts-text-muted); }
    .md-sub { font-size:.75rem; color:var(--ts-text-light); }

    .md-badge { display:inline-block; font-size:.75rem; font-weight:600; padding:.1875rem .625rem; border-radius:999px; border:1px solid transparent; white-space:nowrap; }
    .md-badge--pendiente { background:var(--ts-cancel-bg); color:var(--ts-cancel-text); border-color:var(--ts-cancel-border); }
    .md-badge--en_preparacion { background:var(--ts-prog-bg); color:var(--ts-prog-text); border-color:var(--ts-prog-border); }
    .md-badge--parcialmente_entregado { background:var(--ts-sched-bg); color:var(--ts-sched-text); border-color:var(--ts-sched-border); }
    .md-badge--entregado, .md-badge--signed { background:var(--ts-done-bg); color:var(--ts-done-text); border-color:var(--ts-done-border); }
    .md-badge--cancelado { background:#FEF2F2; color:#DC2626; border-color:#FECACA; }

    .md-progress { min-width:150px; }
    .md-progress__bar { height:8px; background:var(--ts-border-light); border-radius:999px; overflow:hidden; }
    .md-progress__fill { height:100%; background:var(--ts-primary); border-radius:999px; }
    .md-progress__fill--done { background:var(--ts-done-text); }
    .md-progress__text { font-size:.75rem; color:var(--ts-text-muted); margin-top:.25rem; }

    .md-actions { display:flex; gap:.375rem; flex-wrap:wrap; }
    .md-act {
        display:inline-flex; align-items:center; gap:.25rem; height:30px; padding:0 .625rem; border-radius:6px; font-size:.75rem; font-weight:500;
        border:1px solid var(--ts-border); background:var(--ts-bg-white); color:var(--ts-text-body); text-decoration:none; white-space:nowrap;
    }
    .md-act:hover { background:#F9FAFB; color:var(--ts-text-title); }
    .md-act--primary { background:var(--ts-primary); border-color:var(--ts-primary); color:#fff; }
    .md-act--primary:hover { background:var(--ts-primary-hover); color:#fff; }

    .md-empty { text-align:center; padding:2.5rem 1rem; color:var(--ts-text-muted); }
    .md-empty svg { color:var(--ts-text-light); margin-bottom:.5rem; }
    .md-empty p { margin:.25rem 0; font-size:.875rem; }

    .md-pager { display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; padding:.75rem 1rem; border-top:1px solid var(--ts-border-light); font-size:.8125rem; color:var(--ts-text-muted); }
    .md-pager__nav { display:flex; gap:.25rem; }
    .md-page { min-width:32px; height:32px; padding:0 .5rem; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--ts-border); border-radius:6px; background:var(--ts-bg-white); color:var(--ts-text-body); text-decoration:none; font-size:.8125rem; }
    .md-page:hover { background:#F9FAFB; }
    .md-page--active { background:var(--ts-primary); border-color:var(--ts-primary); color:#fff; }
    .md-page--off { opacity:.45; pointer-events:none; }

    .md-head { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .md-head__left { display:flex; align-items:flex-start; gap:.75rem; }
    .md-back { width:36px; height:36px; border-radius:6px; border:1px solid var(--ts-border); background:var(--ts-bg-white); color:var(--ts-text-body); display:flex; align-items:center; justify-content:center; text-decoration:none; flex-shrink:0; }
    .md-back:hover { background:#F9FAFB; }
    .md-head__row { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .md-head__num { font-size:1.375rem; font-weight:700; color:var(--ts-text-title); margin:0; }
    .md-head__sub { font-size:.8125rem; color:var(--ts-text-muted); margin:.25rem 0 0; }

    .md-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; }
    .md-kv__k { font-size:.6875rem; text-transform:uppercase; letter-spacing:.04em; color:var(--ts-text-muted); margin:0 0 .125rem; }
    .md-kv__v { font-size:.875rem; color:var(--ts-text-title); margin:0; word-break:break-word; }

    .md-photos { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:.625rem; }
    .md-photo { display:block; border:1px solid var(--ts-border-light); border-radius:8px; overflow:hidden; background:#F3F4F6; aspect-ratio:4/3; }
    .md-photo img { width:100%; height:100%; object-fit:cover; display:block; }
    .md-sign { border:1px dashed var(--ts-border); border-radius:8px; background:#fff; padding:.5rem; display:inline-block; max-width:100%; }
    .md-sign img { display:block; max-width:100%; max-height:160px; }
    .md-obs { font-size:.875rem; color:var(--ts-text-body); line-height:1.55; margin:0; }

    @media (max-width: 900px) { .md-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 768px) {
        .md-input { max-width:none; flex-basis:100%; }
        .md-head { flex-direction:column; }
    }
</style>
