{{-- Tarjeta "Vínculos" de la cotización: Proyecto + Servicio ID. Vars: $quote --}}
@push('styles')
<style>
    .ql-card { margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 14px; max-width: 900px; }
    .ql-field { min-width: 0; padding: 14px 16px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
    .ql-label { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
    .ql-label svg { color: var(--aq-orange, #ff6213); flex: none; }
    .ql-label-sub { font-weight: 500; letter-spacing: 0; text-transform: none; color: #9ca3af; }
    .ql-current { min-height: 28px; margin-bottom: 10px; display: flex; align-items: center; }
    .ql-empty { font-size: 13px; color: #9ca3af; }
    .ql-chip { display: inline-flex; align-items: center; gap: 8px; max-width: 100%; padding: 4px 12px; border: 1px solid #fed7aa; border-radius: 999px; background: #fff7ed; color: #9a3412; font-size: 13px; text-decoration: none; }
    .ql-chip strong { font-weight: 700; white-space: nowrap; }
    .ql-chip span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ql-chip:hover { background: #ffedd5; border-color: #fdba74; }
    .ql-row { display: flex; gap: 8px; align-items: stretch; margin: 0; }
    .ql-control { flex: 1; min-width: 0; height: 38px; padding: 0 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; font-size: 13px; }
    .ql-control:focus, .ql-field .smp-input:focus { outline: none; border-color: var(--aq-orange, #ff6213); box-shadow: 0 0 0 3px rgba(255, 98, 19, .18); }
    .ql-btn { flex: none; height: 38px; padding: 0 18px; border: 1px solid var(--aq-orange, #ff6213); border-radius: 8px; background: var(--aq-orange, #ff6213); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
    .ql-btn:hover { background: #de4a00; border-color: #de4a00; }
    .ql-linkbtn { display: inline-flex; margin-top: 10px; font-size: 13px; font-weight: 600; color: #c2410c; text-decoration: none; }
    .ql-linkbtn:hover { text-decoration: underline; }
    .ql-hint { margin: 8px 0 0; font-size: 12px; color: #6b7280; }
    /* El buscador compartido trae tema oscuro por prefers-color-scheme; el panel admin es claro. */
    .ql-field .smp { min-width: 0; }
    .ql-control, .ql-field .smp-input { box-sizing: border-box; }
    .ql-field .smp-input { width: 100%; height: 38px; padding: 0 30px 0 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #fff; color: #111827; }
    .ql-field .smp-list { min-width: 0; background: #fff; border-color: #d1d5db; border-radius: 8px; }
    .ql-field .smp-list li { color: #111827; }
    .ql-field .smp-list li:hover, .ql-field .smp-list li.is-active { background: #fff7ed; }
    .ql-field .smp-status { min-height: 0; margin-top: 6px; }
    @media (max-width: 640px) {
        .ql-card { grid-template-columns: 1fr; }
        .ql-row { flex-wrap: wrap; }
        .ql-btn { flex: 1 0 100%; }
    }
</style>
@endpush

<section class="ql-card" aria-label="Vínculos de la cotización">
    @include('admin.projects._doc_picker', ['doc' => $quote, 'type' => 'quote', 'layout' => 'field'])
    @include('admin.quotes.partials._service_id', ['quote' => $quote])
</section>
