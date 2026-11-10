{{--
    Asistente de Reporte de Servicio — adaptación a teléfonos (≤768px).
    Se incluye desde create.blade.php y steps/step1…step6.blade.php (cada paso
    es una página propia, por eso el CSS vive en este parcial compartido).
    Todo va acotado a .sr-create-wrap.sr-wizard-m y dentro de
    @media (max-width: 768px): escritorio queda idéntico.
--}}
@push('styles')
<style>
@media (max-width: 768px) {
    /* ── Página: un solo scroll, sin scroll horizontal ── */
    .sr-create-wrap.sr-wizard-m {
        padding: 12px 12px 0;
        overflow-x: hidden;
        box-sizing: border-box;
        max-width: 100%;
    }
    .sr-create-wrap.sr-wizard-m input,
    .sr-create-wrap.sr-wizard-m select,
    .sr-create-wrap.sr-wizard-m textarea { max-width: 100%; min-width: 0; }
    .sr-create-wrap.sr-wizard-m > div:first-child { flex-wrap: wrap; row-gap: 2px; margin-bottom: 10px !important; }

    /* ── Stepper compacto: círculos + "Paso N de 6 · Nombre" ── */
    .sr-create-wrap.sr-wizard-m .sr-progress {
        position: relative;
        padding: 12px 14px 36px;
        margin-bottom: 12px;
        overflow: visible;
    }
    .sr-create-wrap.sr-wizard-m .sr-progress::before {
        content: '';
        position: absolute;
        z-index: 0;
        top: 25px;
        height: 2px;
        left: calc(14px + (100% - 28px) / 12);
        right: calc(14px + (100% - 28px) / 12);
        background: #E5E7EB;
    }
    .sr-create-wrap.sr-wizard-m.sr-page-step2 .sr-progress::before { background: linear-gradient(to right, #ff6213 20%, #E5E7EB 20%); }
    .sr-create-wrap.sr-wizard-m.sr-page-step3 .sr-progress::before { background: linear-gradient(to right, #ff6213 40%, #E5E7EB 40%); }
    .sr-create-wrap.sr-wizard-m.sr-page-step4 .sr-progress::before { background: linear-gradient(to right, #ff6213 60%, #E5E7EB 60%); }
    .sr-create-wrap.sr-wizard-m.sr-page-step5 .sr-progress::before { background: linear-gradient(to right, #ff6213 80%, #E5E7EB 80%); }
    .sr-create-wrap.sr-wizard-m.sr-page-step6 .sr-progress::before { background: #ff6213; }
    .sr-create-wrap.sr-wizard-m .sr-step-item { position: static; }
    .sr-create-wrap.sr-wizard-m .sr-step-item::after { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-step-circle { position: relative; z-index: 1; width: 28px; height: 28px; font-size: 12px; }
    .sr-create-wrap.sr-wizard-m .sr-step-label { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-step-item.active .sr-step-label {
        display: block;
        position: absolute;
        left: 14px; right: 14px; bottom: 10px;
        margin: 0;
        text-align: center;
        font-size: 13px; font-weight: 700; color: #111827;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sr-create-wrap.sr-wizard-m .sr-step-item.active .sr-step-label::before { font-weight: 500; color: #6B7280; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="1"].active .sr-step-label::before { content: "Paso 1 de 6 · "; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="2"].active .sr-step-label::before { content: "Paso 2 de 6 · "; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="3"].active .sr-step-label::before { content: "Paso 3 de 6 · "; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="4"].active .sr-step-label::before { content: "Paso 4 de 6 · "; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="5"].active .sr-step-label::before { content: "Paso 5 de 6 · "; }
    .sr-create-wrap.sr-wizard-m .sr-step-item[data-n="6"].active .sr-step-label::before { content: "Paso 6 de 6 · "; }

    /* ── Tarjeta del formulario ── */
    .sr-create-wrap.sr-wizard-m .sr-form-card {
        max-height: none; overflow: visible; margin-bottom: 0 !important;
        border-radius: 10px; box-sizing: border-box;
    }
    .sr-create-wrap.sr-wizard-m .sr-form-header { padding: 14px 16px; }
    .sr-create-wrap.sr-wizard-m .sr-form-header h2 { font-size: 15px; line-height: 1.3; }
    .sr-create-wrap.sr-wizard-m .sr-form-body { padding: 16px; overflow: visible; }

    /* ── Formularios: 1 columna, 16px (sin zoom en iOS), ≥44px ── */
    .sr-create-wrap.sr-wizard-m .sr-grid-2,
    .sr-create-wrap.sr-wizard-m .sr-grid-3 { display: flex; flex-direction: column; gap: 16px; }
    .sr-create-wrap.sr-wizard-m .sr-section-title { padding: 4px 0 8px; margin-bottom: 0; font-size: 12px; }
    .sr-create-wrap.sr-wizard-m:not(.sr-page-create):not(.sr-page-step1) .sr-label {
        display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: #374151;
    }
    .sr-create-wrap.sr-wizard-m .sr-input,
    .sr-create-wrap.sr-wizard-m .sr-select {
        height: 48px; font-size: 16px; padding: 0 12px;
    }
    .sr-create-wrap.sr-wizard-m .sr-select { padding-right: 36px; text-overflow: ellipsis; }
    .sr-create-wrap.sr-wizard-m .sr-textarea { font-size: 16px; min-height: 120px; padding: 12px; }
    .sr-create-wrap.sr-wizard-m .sr-hint,
    .sr-create-wrap.sr-wizard-m .sr-error { font-size: 12px; line-height: 1.4; }

    /* Buscador "Servicio programado de origen": lista dentro del flujo */
    .sr-create-wrap.sr-wizard-m .sr-client-dropdown {
        position: static; margin-top: 4px; max-height: 50vh; box-shadow: none; border-color: #D1D5DB;
    }
    .sr-create-wrap.sr-wizard-m .sr-client-dropdown__item {
        min-height: 52px; padding: 10px 14px; display: flex; flex-direction: column; justify-content: center;
    }
    .sr-create-wrap.sr-wizard-m .sr-client-dropdown__name { font-size: 15px; }
    .sr-create-wrap.sr-wizard-m .sr-client-dropdown__company { font-size: 13px; }

    /* Selector "Servicio (web)" (parcial compartido): solo dentro del asistente */
    .sr-create-wrap.sr-wizard-m .smp { min-width: 0; }
    .sr-create-wrap.sr-wizard-m .smp-input {
        height: 48px; font-size: 16px; padding: 0 44px 0 12px; border-radius: 6px; box-sizing: border-box;
    }
    .sr-create-wrap.sr-wizard-m .smp-clear { width: 40px; height: 40px; right: 4px; font-size: 22px; }
    .sr-create-wrap.sr-wizard-m .smp-list { position: static; min-width: 0; max-height: 50vh; margin-top: 4px; box-shadow: none; }
    .sr-create-wrap.sr-wizard-m .smp-list li { padding: 12px; font-size: 15px; }

    /* ── Barra inferior fija: Atrás | Siguiente / Guardar ── */
    .sr-create-wrap.sr-wizard-m .sr-form-footer {
        position: sticky; bottom: 0; z-index: 20;
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 3fr);
        align-items: stretch;
        gap: 8px;
        margin: 0;
        padding: 10px 12px calc(10px + env(safe-area-inset-bottom, 0px)) !important;
        background: #fff;
        border-top: 1px solid #E5E7EB !important;
        box-shadow: 0 -4px 12px rgba(0,0,0,.08);
    }
    .sr-create-wrap.sr-wizard-m .sr-form-footer .sr-btn-primary,
    .sr-create-wrap.sr-wizard-m .sr-form-footer .sr-btn-outline {
        width: 100%; height: auto; min-height: 48px;
        padding: 6px 10px;
        display: inline-flex; align-items: center; justify-content: center;
        text-align: center; white-space: normal; line-height: 1.2;
        font-size: 15px; box-sizing: border-box;
    }
    .sr-create-wrap.sr-wizard-m .sr-form-footer .sr-btn-primary { font-weight: 600; }
    /* Paso 4: [Guardar y continuar] arriba a todo lo ancho; [Anterior][Colocar después] abajo */
    .sr-create-wrap.sr-wizard-m.sr-page-step4 .sr-form-footer > div { display: contents; }
    .sr-create-wrap.sr-wizard-m.sr-page-step4 .sr-form-footer > div > .sr-btn-primary { grid-column: 1 / -1; order: -1; }
    .sr-create-wrap.sr-wizard-m.sr-page-step4 .sr-form-footer { margin: 16px -16px -16px; border-radius: 0 0 10px 10px; }
    /* Paso 5: el pie es hijo directo de la página → a todo lo ancho */
    .sr-create-wrap.sr-wizard-m.sr-page-step5 .sr-form-footer { margin: 4px -12px 0; }

    /* ── Paso 2A: mediciones → una tarjeta por parámetro ── */
    .sr-create-wrap.sr-wizard-m .sr-meas-scroll { overflow: visible; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table,
    .sr-create-wrap.sr-wizard-m .sr-meas-table tbody { display: block; width: 100%; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table thead { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table tbody tr {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 12px 10px;
        position: relative;
        padding: 12px;
        margin-bottom: 12px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-table tbody td {
        display: block; width: auto; min-width: 0 !important; padding: 0;
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label]::before {
        content: attr(data-label); display: block; margin-bottom: 4px;
        font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #6B7280;
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td.sr-meas-num {
        order: 1; position: static; align-self: center; width: auto; text-align: left;
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-num-short { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-meas-num-full { display: block; font-size: 14px; font-weight: 700; color: #111827; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td.sr-meas-del {
        order: 2; position: static; justify-self: end; align-self: center; width: auto;
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Parámetro"] { order: 3; grid-column: 1 / -1; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Unidad"]     { order: 4; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Resultado"]  { order: 5; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Límite Mín."] { order: 6; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Límite Máx."] { order: 7; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Rango"] {
        order: 8; grid-column: 1 / -1;
        padding: 10px; background: #F9FAFB; border-radius: 8px;
    }
    .sr-create-wrap.sr-wizard-m .sr-meas-table td[data-label="Rango"]::before { display: inline; margin-right: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table .sr-range-badge { font-size: 12px; padding: 3px 8px; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table .sr-range-bar-wrap { margin-top: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table .sr-input { height: 48px; font-size: 16px; }
    .sr-create-wrap.sr-wizard-m .sr-meas-table .sr-result { font-weight: 600; }
    .sr-create-wrap.sr-wizard-m .sr-btn-del-row {
        width: 44px; height: 44px; border-radius: 8px; font-size: 16px;
        border: 1px solid #E5E7EB; color: #6B7280; background: #fff;
    }
    .sr-create-wrap.sr-wizard-m .sr-btn-add-row {
        width: 100%; height: 48px; justify-content: center; font-size: 15px; margin-top: 4px;
    }

    /* ── Paso 2B: sistemas revisados → filas táctiles ≥48px ── */
    .sr-create-wrap.sr-wizard-m .sr-systems-grid { grid-template-columns: 1fr; gap: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-checkbox-item {
        min-height: 52px; padding: 10px 14px; gap: 12px; font-size: 15px; box-sizing: border-box;
    }
    .sr-create-wrap.sr-wizard-m .sr-checkbox-item input[type=checkbox] {
        width: 22px; height: 22px; flex-shrink: 0; margin: 0; accent-color: #ff6213;
    }
    .sr-create-wrap.sr-wizard-m .sr-checkbox-item:has(input:checked) { border-color: #ff6213; background: #FFF7ED; }
    .sr-create-wrap.sr-wizard-m.sr-page-step2 .sr-textarea { min-height: 180px; }

    /* ── Paso 2C: campos personalizados → tarjetas ── */
    .sr-create-wrap.sr-wizard-m .sr-custom-row {
        grid-template-columns: 1fr; gap: 10px; position: relative;
        padding: 12px; margin-bottom: 12px;
        border: 1px solid #E5E7EB; border-radius: 10px; background: #fff;
    }
    .sr-create-wrap.sr-wizard-m .sr-custom-row > div:last-child {
        padding-top: 0 !important; display: flex; justify-content: flex-end;
    }

    /* ── Paso 3: observaciones / muestreo ── */
    .sr-create-wrap.sr-wizard-m .sr-section-divider { margin: 16px 0; }
    .sr-create-wrap.sr-wizard-m .sr-toggle-row { min-height: 48px; margin-bottom: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-toggle-label { flex: 1; min-height: 44px; display: flex; align-items: center; font-size: 15px; cursor: pointer; }
    .sr-create-wrap.sr-wizard-m .sr-sampling-block { padding: 12px; }
    .sr-create-wrap.sr-wizard-m.sr-page-step3 .sr-grid-3 {
        display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px;
    }
    .sr-create-wrap.sr-wizard-m.sr-page-step3 .sr-grid-3 .sr-input { text-align: center; padding: 0 6px; }

    /* ── Paso 4: evidencia fotográfica ── */
    .sr-create-wrap.sr-wizard-m .sr-mobile-camera-section { display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
    .sr-create-wrap.sr-wizard-m .sr-camera-btn-label,
    .sr-create-wrap.sr-wizard-m .sr-gallery-btn-label {
        flex: none; width: 100%; box-sizing: border-box;
        flex-direction: row; gap: 10px;
        min-height: 56px; padding: 12px 16px;
        font-size: 16px; font-weight: 600;
    }
    .sr-create-wrap.sr-wizard-m .sr-camera-btn-label svg,
    .sr-create-wrap.sr-wizard-m .sr-gallery-btn-label svg { width: 24px; height: 24px; }
    .sr-create-wrap.sr-wizard-m .sr-img-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
    .sr-create-wrap.sr-wizard-m .sr-img-delete {
        top: 4px; right: 4px; width: 32px; height: 32px; font-size: 20px;
    }
    .sr-create-wrap.sr-wizard-m .sr-img-new-badge { font-size: 9px; padding: 2px 5px; left: 4px; bottom: 4px; }

    /* ── Paso 5: resumen ── */
    .sr-create-wrap.sr-wizard-m .sr-summary-card { margin-bottom: 12px; border-radius: 10px; }
    .sr-create-wrap.sr-wizard-m .sr-summary-card-header { min-height: 48px; padding: 2px 6px 2px 14px; box-sizing: border-box; }
    .sr-create-wrap.sr-wizard-m .sr-summary-card-header > a {
        display: inline-flex; align-items: center; min-height: 44px; padding: 0 10px; font-size: 14px !important; font-weight: 600 !important;
    }
    .sr-create-wrap.sr-wizard-m .sr-summary-body { padding: 12px 14px; }
    .sr-create-wrap.sr-wizard-m .sr-dl { grid-template-columns: 1fr; gap: 2px; font-size: 14px; }
    .sr-create-wrap.sr-wizard-m .sr-dt { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-dt:first-child { margin-top: 0; }
    .sr-create-wrap.sr-wizard-m .sr-dd { overflow-wrap: anywhere; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table,
    .sr-create-wrap.sr-wizard-m .sr-mini-table tbody { display: block; width: 100%; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table thead { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table tbody tr {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 10px;
        padding: 12px 14px; border-bottom: 1px solid #F3F4F6;
    }
    .sr-create-wrap.sr-wizard-m .sr-mini-table tbody tr:last-child { border-bottom: none; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table tbody td { padding: 0 !important; border: none; font-size: 14px; min-width: 0; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table tbody td:first-child { grid-column: 1 / -1; font-weight: 700; color: #111827; font-size: 15px; }
    .sr-create-wrap.sr-wizard-m .sr-mini-table td[data-label]:not(:first-child)::before {
        content: attr(data-label); display: block; margin-bottom: 2px;
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #6B7280;
    }
    .sr-create-wrap.sr-wizard-m .sr-step-check { min-height: 44px; font-size: 14px; padding: 8px 0; }

    /* ── Paso 6: firma ── */
    .sr-create-wrap.sr-wizard-m .sr-canvas-wrap { touch-action: none; }
    .sr-create-wrap.sr-wizard-m #signatureCanvas {
        width: 100%; height: 220px; min-height: 0; touch-action: none;
    }
    .sr-create-wrap.sr-wizard-m .sr-mobile-sign-hint { display: block; padding: 4px 0 8px; font-size: 14px; color: #6B7280; text-align: center; }
    .sr-create-wrap.sr-wizard-m .sr-mobile-canvas-bar { display: flex; justify-content: flex-end; margin-bottom: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-mobile-canvas-bar .sr-btn-clear { height: 44px; padding: 0 18px; font-size: 14px; border-radius: 8px; }
    .sr-create-wrap.sr-wizard-m .sr-canvas-toolbar .sr-btn-clear { display: none; }
    .sr-create-wrap.sr-wizard-m .sr-canvas-hint { font-size: 13px; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    // Móvil: al enfocar un buscador con lista dentro del flujo, súbelo al
    // inicio de la vista para que la lista quede visible sobre el teclado.
    var mq = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
    if (!mq) return;
    var wrap = document.querySelector('.sr-wizard-m');
    if (!wrap) return;
    wrap.addEventListener('focusin', function (e) {
        var t = e.target;
        if (!mq.matches || !t || !t.matches) return;
        if (!t.matches('#serviceSearchInput, .smp-input')) return;
        setTimeout(function () {
            try { t.scrollIntoView({ block: 'start', behavior: 'smooth' }); } catch (err) { t.scrollIntoView(true); }
        }, 300);
    });
})();
</script>
@endpush
