{{-- Wizard de Órdenes de Compra (create / edit) — adaptación a teléfonos.
     Incluido por create/create.blade.php y edit/edit.blade.php.
     Todo va acotado a .po-page--wizard y, salvo ocultar en escritorio los
     dos elementos nuevos de la barra inferior (total + guardar, que solo
     existen en móvil), vive dentro de @media (max-width:768px): el
     escritorio queda idéntico. Las filas de productos las genera
     create/_scripts y edit/_scripts con <td class="po-td-*" data-label>;
     se convierten en tarjetas solo con CSS (etiquetas vía ::before). --}}
@push('styles')
<style>
.po-page--wizard .powiz-footer__total,
.po-page--wizard .powiz-footer__submit { display: none; }

@media (max-width: 768px) {
    /* ── Página: deja de ser contenedor de scroll para que la barra
       inferior sea sticky respecto a .admin-main (que ya recorta el
       desborde horizontal) ── */
    .po-page.po-page--wizard {
        padding: 16px 16px 0;
        overflow: visible;
        flex-shrink: 0;
        min-width: 0;
    }
    .po-page--wizard .po-header { margin-bottom: 16px; }
    .po-page--wizard .po-breadcrumb { flex-wrap: wrap; row-gap: 2px; }
    .po-page--wizard .po-header__title { font-size: 20px; }
    .po-page--wizard .po-back-btn { min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }
    .po-page--wizard .po-card { padding: 16px; min-width: 0; }
    .po-page--wizard .po-card__header { margin-bottom: 16px; }
    .po-page--wizard .po-layout,
    .po-page--wizard .po-main,
    .po-page--wizard .po-sidebar { gap: 16px; min-width: 0; }
    .po-page--wizard .po-layout { grid-template-columns: minmax(0, 1fr); }
    .po-page--wizard .po-sidebar { position: static; }

    /* ── Barra de progreso compacta: círculos + "Paso N de 3 · Nombre" ── */
    .po-page--wizard .powiz-bar {
        position: relative;
        flex-wrap: nowrap;
        justify-content: space-between;
        padding: 12px 16px 38px;
        margin-bottom: 16px;
    }
    .po-page--wizard .powiz-step { gap: 0; flex-shrink: 0; }
    .po-page--wizard .powiz-step__circle { width: 28px; height: 28px; font-size: 12px; }
    .po-page--wizard .powiz-step-connector {
        flex: 1 1 auto;
        width: auto;
        min-width: 8px;
        margin: 0 6px;
    }
    .po-page--wizard .powiz-step-connector.done { background: #ff6213; }
    .po-page--wizard .powiz-step__label { display: none; }
    .po-page--wizard .powiz-step.active .powiz-step__label {
        display: block;
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 11px;
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .po-page--wizard .powiz-step.active .powiz-step__label::before { font-weight: 500; color: #6b7280; }
    .po-page--wizard .powiz-step[data-powiz-step-item="1"].active .powiz-step__label::before { content: "Paso 1 de 3 · "; }
    .po-page--wizard .powiz-step[data-powiz-step-item="2"].active .powiz-step__label::before { content: "Paso 2 de 3 · "; }
    .po-page--wizard .powiz-step[data-powiz-step-item="3"].active .powiz-step__label::before { content: "Paso 3 de 3 · "; }

    /* ── Formularios: 1 columna, 16px (evita zoom en iOS), ≥44px ── */
    .po-page--wizard .po-form-grid { grid-template-columns: minmax(0, 1fr); gap: 0; margin-bottom: 0; }
    .po-page--wizard .po-form-grid > .po-form-group,
    .po-page--wizard .po-form-group { margin-bottom: 16px; min-width: 0; }
    .po-page--wizard .po-label { font-size: 12px; }
    .po-page--wizard .po-input,
    .po-page--wizard .po-select,
    .po-page--wizard .po-textarea {
        font-size: 16px;
        min-height: 44px;
        min-width: 0;
        max-width: 100%;
    }
    .po-page--wizard .po-input,
    .po-page--wizard .po-select { height: 44px; padding-top: 8px; padding-bottom: 8px; }
    .po-page--wizard .po-textarea { min-height: 96px; }
    .po-page--wizard .po-help-note { margin-top: -4px; }

    /* Tarjeta del proveedor elegido */
    .po-page--wizard .po-supplier-card { padding: 12px 14px; margin-bottom: 16px; }
    .po-page--wizard .po-supplier-card__grid { grid-template-columns: 80px minmax(0, 1fr); font-size: 14px; }
    .po-page--wizard .po-supplier-card__grid > span { min-width: 0; overflow-wrap: anywhere; }

    /* ── Paso 3: buscador y "Agregar línea libre" a todo lo ancho ── */
    .po-page--wizard .po-products-toolbar { flex-direction: column; align-items: stretch; gap: 12px; }
    .po-page--wizard .po-inline-search { width: 100%; min-width: 0; }
    .po-page--wizard .po-inline-search__input {
        font-size: 16px;
        min-height: 44px;
        min-width: 0;
        padding-right: 46px;
    }
    .po-page--wizard .po-inline-search__clear {
        right: 2px;
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    .po-page--wizard .po-btn-add-row {
        width: 100%;
        height: 46px;
        justify-content: center;
        font-size: 15px;
    }
    /* Resultados dentro del flujo de la página (no flotan sobre el
       teclado ni quedan tapados por la barra inferior) */
    .po-page--wizard .po-inline-search__dropdown {
        position: static;
        margin-top: 6px;
        max-height: 55vh;
        box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
    }
    .po-page--wizard .po-inline-search__item { gap: 10px; padding: 8px 10px 8px 12px; min-height: 56px; }
    .po-page--wizard .po-inline-search__item-name { font-size: 15px; white-space: normal; overflow-wrap: anywhere; }
    .po-page--wizard .po-inline-search__item-sku { font-size: 12px; }
    .po-page--wizard .po-inline-search__item-price { font-size: 14px; }
    .po-page--wizard .po-inline-search__add-btn { width: 44px; height: 44px; border-radius: 8px; }

    /* ── Líneas: la tabla se convierte en tarjetas apiladas ── */
    .po-page--wizard .po-table-wrap { overflow: visible; }
    .po-page--wizard .po-table { display: block; min-width: 0; width: 100%; }
    .po-page--wizard .po-table thead { display: none; }
    .po-page--wizard .po-table tbody { display: flex; flex-direction: column; gap: 12px; }
    .po-page--wizard .po-table tbody tr.po-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 12px;
        padding: 12px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }
    .po-page--wizard .po-table tbody tr.po-item-row > td {
        display: block;
        width: auto;
        min-width: 0;
        padding: 0;
        text-align: left;
    }
    .po-page--wizard .po-item-row > td.po-td-num {
        order: 1;
        align-self: center;
        font-size: 12px !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .po-page--wizard .po-item-row > td.po-td-num::before { content: "Línea "; }
    .po-page--wizard .po-item-row > td.po-td-del   { order: 2; justify-self: end; align-self: center; }
    .po-page--wizard .po-item-row > td.po-td-name  { order: 3; grid-column: 1 / -1; }
    .po-page--wizard .po-item-row > td.po-td-sku   { order: 4; grid-column: 1 / -1; }
    .po-page--wizard .po-item-row > td.po-td-qty   { order: 5; }
    .po-page--wizard .po-item-row > td.po-td-price { order: 6; }
    .po-page--wizard .po-item-row > td.po-td-disc  { order: 7; }
    .po-page--wizard .po-item-row > td.po-td-total { order: 8; }
    .po-page--wizard .po-item-row > td[data-label]::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 4px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
        letter-spacing: .4px;
        text-transform: uppercase;
        color: #6b7280;
    }
    .po-page--wizard .po-item-name { font-size: 15px; overflow-wrap: anywhere; }
    .po-page--wizard .po-item-input,
    .po-page--wizard .po-item-comment {
        font-size: 16px;
        min-height: 44px;
        padding: 10px 12px;
        min-width: 0;
        max-width: 100%;
    }
    .po-page--wizard .po-item-comment { margin-top: 8px; color: #6b7280; }
    .po-page--wizard .po-item-total {
        padding-top: 0;
        font-size: 16px;
        line-height: 44px;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .po-page--wizard .po-item-remove {
        width: 44px;
        height: 44px;
        padding: 0;
        border: 1.5px solid #e5e7eb;
        border-radius: 8px;
        color: #9ca3af;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Fila vacía */
    .po-page--wizard .po-table tbody tr.po-table__empty {
        display: block;
        border: 1.5px dashed #e5e7eb;
        border-radius: 12px;
    }
    .po-page--wizard .po-table tr.po-table__empty td { display: block; padding: 28px 16px; }

    /* ── Resumen ── */
    .po-page--wizard .po-summary { gap: 4px; }
    .po-page--wizard .po-summary__row { font-size: 14px; padding: 6px 0; gap: 8px; flex-wrap: wrap; }
    .po-page--wizard .po-summary__input-group { gap: 6px; margin-left: auto; }
    .po-page--wizard .po-summary__input { width: 76px; height: 44px; font-size: 16px; padding: 8px; border-radius: 8px; }
    .po-page--wizard .po-summary__total-val { font-size: 22px; min-width: 0; overflow-wrap: anywhere; }
    .po-page--wizard .po-info-row { font-size: 14px; gap: 12px; }
    .po-page--wizard .po-info-row__val { text-align: right; min-width: 0; overflow-wrap: anywhere; }
    .po-page--wizard .po-btn-draft { min-height: 46px; font-size: 15px; }
    /* "Guardar" principal vive en la barra inferior fija */
    .po-page--wizard .po-sidebar .po-btn-save { display: none; }

    /* ── Barra inferior fija: Atrás · (Total) · Siguiente/Guardar ── */
    .po-page--wizard .powiz-footer {
        position: sticky;
        bottom: 0;
        z-index: 50;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: 10px;
        margin: 16px -16px 0;
        padding: 10px 16px calc(10px + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, .06);
    }
    .po-page--wizard .powiz-footer .powiz-btn--primary,
    .po-page--wizard .powiz-footer .powiz-btn--ghost {
        width: auto;
        height: 46px;
        min-width: 46px;
        justify-content: center;
        font-size: 15px;
        white-space: nowrap;
    }
    .po-page--wizard #powizBackBtn { order: 1; flex: 0 0 auto; padding: 0 14px; }
    .po-page--wizard .powiz-footer__total { order: 2; }
    .po-page--wizard #powizNextBtn,
    .po-page--wizard .powiz-footer__submit { order: 3; flex: 1 1 auto; }

    /* Paso 3 (último): aparecen total + guardar; "Atrás" solo flecha
       (el texto sigue disponible para lectores de pantalla) */
    .po-page--wizard .powiz-step-panel[data-powiz-step="3"].active ~ .powiz-footer .powiz-footer__total {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-width: 0;
        line-height: 1.15;
    }
    .po-page--wizard .powiz-step-panel[data-powiz-step="3"].active ~ .powiz-footer .powiz-footer__submit {
        display: inline-flex;
        flex: 0 0 auto;
        padding: 0 18px;
    }
    .po-page--wizard .powiz-step-panel[data-powiz-step="3"].active ~ .powiz-footer #powizBackBtn {
        width: 46px;
        padding: 0;
        gap: 0;
        font-size: 0;
    }
    .po-page--wizard .powiz-step-panel[data-powiz-step="3"].active ~ .powiz-footer #powizBackBtn::before {
        content: "←";
        font-size: 20px;
        line-height: 1;
    }
    .po-page--wizard .powiz-footer__total-label {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #6b7280;
    }
    .po-page--wizard .powiz-footer__total-amount {
        font-size: 18px;
        font-weight: 800;
        color: #ff6213;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── Modal "¿Regresar?": hoja inferior + scroll de fondo bloqueado ── */
    .po-page--wizard .powiz-confirm-backdrop { align-items: flex-end; padding: 0; }
    .po-page--wizard .powiz-confirm-modal {
        width: 100%;
        max-width: none;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 16px 16px 0 0;
        padding: 20px 16px calc(16px + env(safe-area-inset-bottom, 0px));
    }
    .po-page--wizard .powiz-confirm-modal p { font-size: 15px; }
    .po-page--wizard .powiz-confirm-modal__actions { flex-direction: column-reverse; gap: 8px; }
    .po-page--wizard .powiz-confirm-modal__actions .powiz-btn--primary,
    .po-page--wizard .powiz-confirm-modal__actions .powiz-btn--ghost {
        width: 100%;
        height: 46px;
        justify-content: center;
    }
    body:has(.po-page--wizard .powiz-confirm-backdrop.active),
    body:has(.po-page--wizard .powiz-confirm-backdrop.active) .admin-main {
        overflow: hidden;
        overscroll-behavior: contain;
    }
}
</style>
@endpush
