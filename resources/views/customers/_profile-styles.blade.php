<style>
    .cp-page { padding:32px; overflow-y:auto; height:calc(100vh - 64px); box-sizing:border-box; font-family:var(--font-family); }
    .cp-breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#9ca3af; margin-bottom:16px; }
    .cp-breadcrumb strong { color:#374151; font-weight:600; }

    .cp-header { display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; background:#fff; padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #f3f4f6; }
    .cp-header-left { display:flex; align-items:center; gap:18px; min-width:0; }
    .cp-avatar { width:80px; height:80px; flex-shrink:0; background:#22c55e; color:#fff; font-weight:700; font-size:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; }
    .cp-who { display:flex; flex-direction:column; gap:8px; align-items:flex-start; min-width:0; }
    .cp-who h2 { margin:0; font-size:22px; font-weight:700; color:#1f2937; }
    .cp-line { display:flex; align-items:center; gap:8px; margin:0; font-size:14px; color:#6b7280; }
    .cp-badge { display:inline-flex; align-items:center; padding:3px 12px; border-radius:999px; font-size:12px; font-weight:600; }
    .cp-badge--active { background:#d1fae5; color:#065f46; }
    .cp-badge--off { background:#f3f4f6; color:#6b7280; }
    .cp-readonly { font-size:12px; color:#9ca3af; max-width:280px; text-align:right; line-height:1.4; }

    .cp-stats { display:grid; grid-template-columns:repeat(5, 1fr); gap:16px; margin-bottom:20px; }
    .cp-stat { background:#fff; border-radius:12px; padding:16px 18px; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .cp-stat p { margin:0 0 4px; font-size:12px; font-weight:500; color:#6b7280; }
    .cp-stat h3 { margin:0; font-size:24px; font-weight:700; color:#1f2937; }

    .cp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
    .cp-card { background:#fff; padding:20px; border-radius:12px; display:flex; flex-direction:column; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #f3f4f6; min-width:0; }
    .cp-card h3 { margin:0; font-size:15px; font-weight:700; color:#1f2937; }
    .cp-card-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .cp-field { display:flex; flex-direction:column; gap:3px; }
    .cp-label { font-size:12px; font-weight:600; color:#6b7280; }
    .cp-value { display:flex; align-items:center; gap:8px; font-size:14px; color:#1f2937; margin:0; overflow-wrap:anywhere; }
    .cp-value svg { color:#9ca3af; flex-shrink:0; }
    .cp-tag { font-size:11px; font-weight:600; color:#2563eb; background:#eff6ff; border:1px solid #bfdbfe; border-radius:999px; padding:2px 10px; }
    .cp-section-title { font-size:15px; font-weight:700; color:#1f2937; margin:0 0 12px; }
    .cp-empty { color:#9ca3af; font-size:14px; margin:0; }

    .cp-card--form { gap:16px; align-self:start; }
    .cp-hint { display:block; margin:4px 0 0; font-size:12px; color:#6b7280; line-height:1.4; }
    .cp-req { color:#dc2626; }
    .cp-form { display:flex; flex-direction:column; gap:18px; }
    .cp-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; }
    .cp-form-grid--one { grid-template-columns:1fr; }
    .cp-full { grid-column:1 / -1; }
    .cp-fgroup { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .cp-flabel { font-size:13px; font-weight:600; color:#374151; }
    .cp-input { width:100%; box-sizing:border-box; height:42px; padding:0 12px; font:inherit; font-size:14px; color:#1f2937; background:#fff; border:1px solid #d1d5db; border-radius:8px; transition:border-color .15s, box-shadow .15s; }
    .cp-input--upper { text-transform:uppercase; }
    .cp-input:focus { outline:none; border-color:#ff6213; box-shadow:0 0 0 3px rgba(255,98,19,.18); }
    .cp-fgroup.has-error .cp-input { border-color:#dc2626; }
    .cp-fgroup.has-error .cp-input:focus { box-shadow:0 0 0 3px rgba(220,38,38,.18); }
    .cp-error { font-size:12px; font-weight:500; color:#dc2626; }
    .cp-actions { display:flex; justify-content:flex-end; padding-top:16px; border-top:1px solid #f3f4f6; }
    .cp-btn { display:inline-flex; align-items:center; justify-content:center; height:42px; padding:0 20px; font:inherit; font-size:14px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid transparent; transition:background .15s, border-color .15s; }
    .cp-btn:focus-visible { outline:none; box-shadow:0 0 0 3px rgba(255,98,19,.28); }
    .cp-btn--primary { background:#ff6213; color:#fff; }
    .cp-btn--primary:hover { background:#e5560f; }
    .cp-btn--outline { background:#fff; color:#374151; border-color:#d1d5db; }
    .cp-btn--outline:hover { border-color:#ff6213; color:#c2410c; }

    @media (max-width:1100px) { .cp-stats { grid-template-columns:repeat(3, 1fr); } }
    @media (max-width:900px) { .cp-grid { grid-template-columns:1fr; } .cp-readonly { text-align:left; max-width:none; } }
    @media (max-width:640px) {
        .cp-page { padding:16px; }
        .cp-stats { grid-template-columns:repeat(2, 1fr); }
        .cp-avatar { width:60px; height:60px; font-size:22px; }
        .cp-form-grid { grid-template-columns:1fr; }
        .cp-input { height:46px; font-size:16px; }
        .cp-actions .cp-btn { width:100%; height:48px; }
    }
</style>
