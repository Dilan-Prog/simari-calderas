@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@push('styles')
<style>
/* Pagos · registrar — teléfono (≤768px). Escritorio sin cambios: fuera del @media solo se oculta .pay-m-only
   (botón «Aplicar saldo» que el script de esta vista agrega a cada documento). */
.pay-create .pay-m-only { display: none; }
@media (max-width: 768px) {
    .pay-create { padding: 12px; overflow-x: clip; }
    .pay-create * { box-sizing: border-box; }
    .pay-create .erp-page-head { flex-direction: column; align-items: stretch; gap: 10px; margin-bottom: 14px; }
    .pay-create .erp-page-head > div:first-child { min-width: 0; }
    .pay-create .erp-title { font-size: 22px; overflow-wrap: anywhere; }
    .pay-create .erp-subtitle { max-width: none; overflow-wrap: anywhere; }
    .pay-create .erp-crumbs { flex-wrap: wrap; }

    /* Una columna, controles de 44px / 16px */
    .pay-create .erp-card { overflow: visible; margin-bottom: 0; }
    .pay-create .erp-card-body { padding: 14px; }
    .pay-create .erp-form-grid { grid-template-columns: minmax(0, 1fr); gap: 14px; }
    .pay-create .erp-field { min-width: 0; font-size: 13px; }
    .pay-create .erp-select, .pay-create .erp-input { width: 100%; max-width: 100%; min-height: 44px; font-size: 16px; }
    .pay-create input[type="file"].erp-input { padding: 9px 10px; font-size: 14px; }
    .pay-create textarea.erp-input { min-height: 88px; }
    .pay-create .fin-check-line { min-height: 44px; gap: 10px; font-size: 14px; line-height: 1.35; cursor: pointer; }
    .pay-create .fin-check-line input { flex: 0 0 auto; width: 20px; height: 20px; margin: 0; }

    /* Documentos abiertos → una tarjeta por documento */
    .pay-create .erp-table-wrap { overflow-x: visible; }
    .pay-create #finDocsTable, .pay-create #finDocsTable tbody { display: block; width: 100%; }
    .pay-create #finDocsTable thead { display: none; }
    .pay-create #finDocsTable tbody tr { display: block; }
    .pay-create #finDocsTable tbody tr > td { display: block; width: 100%; }
    .pay-create #finDocsTable tbody tr.pay-doc {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px 12px;
        grid-template-areas: "doc cur" "due bal" "apply apply";
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid var(--erp-line); border-radius: 10px; background: #fff;
    }
    .pay-create #finDocsTable tbody tr.pay-doc:hover { background: #fff; }
    .pay-create #finDocsTable tbody tr.pay-doc.is-filled { border-color: var(--erp-accent); box-shadow: 0 0 0 1px var(--erp-accent) inset; }
    .pay-create #finDocsTable tbody tr.pay-doc > td { display: block; width: auto; padding: 0; border: 0; min-width: 0; text-align: left; font-size: 13px; overflow-wrap: anywhere; }
    .pay-create #finDocsTable tr.pay-doc td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 3px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--erp-faint); }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(1) { grid-area: doc; font-size: 15px; }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(2) { grid-area: due; }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(2) .erp-due { white-space: normal; }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(3) { grid-area: cur; justify-self: end; text-align: right; }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(4) { grid-area: bal; justify-self: end; text-align: right; font-size: 15px; font-weight: 800; color: var(--erp-ink); }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(5) { grid-area: apply; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: end; padding-top: 10px; border-top: 1px solid var(--erp-line-soft); }
    .pay-create #finDocsTable tr.pay-doc > td:nth-child(5)::before { grid-column: 1 / -1; margin-bottom: 0; }
    .pay-create #finDocsTable .fin-apply-input { width: 100%; min-height: 44px; font-size: 16px; text-align: right; }
    .pay-create .pay-m-only.pay-fill { display: inline-flex; min-height: 44px; padding: 8px 12px; font-size: 14px; justify-content: center; white-space: nowrap; }
    .pay-create #finDocsTable .erp-empty { text-align: center; padding: 18px 8px; }

    /* Barra fija abajo: totales + FIFO + acciones */
    .pay-create .pay-create-bar {
        position: sticky; bottom: 0; z-index: 30;
        display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px;
        margin: 14px -14px -14px; padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));
        background: #fff; border-top: 1px solid var(--erp-line); border-radius: 0 0 12px 12px;
        box-shadow: 0 -6px 16px rgba(16, 24, 40, .08);
    }
    .pay-create .pay-create-bar .fin-apply-summary, .pay-create .pay-create-bar .erp-form-actions { display: contents; }
    .pay-create .pay-create-bar .fin-apply-summary > div { grid-column: span 2; min-width: 0; padding: 6px 8px; border-radius: 8px; background: #f6f7f9; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--erp-muted); line-height: 1.25; overflow-wrap: anywhere; }
    .pay-create .pay-create-bar .fin-apply-summary strong { display: block; margin-top: 2px; font-size: 14px; letter-spacing: 0; text-transform: none; color: var(--erp-ink); font-variant-numeric: tabular-nums; }
    .pay-create .pay-create-bar .fin-apply-summary strong.is-bad { color: var(--erp-bad); }
    .pay-create .pay-create-bar .fin-apply-summary strong.is-ok { color: var(--erp-ok); }
    .pay-create .pay-create-bar .erp-btn { width: 100%; min-height: 44px; justify-content: center; font-size: 14px; text-align: center; }
    .pay-create .pay-create-bar #finFifoBtn { grid-column: span 3; padding: 8px 10px; }
    .pay-create .pay-create-bar .erp-form-actions > a.erp-btn { grid-column: span 3; }
    .pay-create .pay-create-bar .erp-form-actions .erp-btn--primary { grid-column: 1 / -1; min-height: 48px; font-size: 15px; }
}
</style>
@endpush
@section('title')
    Registrar pago - Admin
@endsection
@section('content')
@php
    $dir = old('direction', $direction);
@endphp
<div class="erp-page fin-page pay-create">

    <x-erp.page-header
        title="Registrar pago" code="FIN4"
        desc="Captura el pago y aplícalo a uno o varios documentos abiertos."
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], ['Pagos', route('admin.payments.index')], ['Nuevo', null]]" />

    <form method="POST" action="{{ route('admin.payments.store') }}" enctype="multipart/form-data" class="erp-card" id="finPaymentForm"
          data-open-url="{{ route('admin.payments.open-documents') }}" data-prefill-doc="{{ $prefill['doc'] }}">
        @csrf
        <div class="erp-card-body">
        <div class="erp-form-grid">
            <label class="erp-field">Tipo
                <select name="direction" id="finDirection" class="erp-select">
                    <option value="in" @selected($dir === 'in')>Cobro de cliente</option>
                    <option value="out" @selected($dir === 'out')>Pago a proveedor</option>
                </select>
            </label>
            <label id="finCustomerWrap" class="erp-field">Cliente
                <select name="customer_id" id="finCustomer" class="erp-select">
                    <option value="">Selecciona…</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('customer_id', $prefill['customer_id']) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label id="finSupplierWrap" class="erp-field">Proveedor
                <select name="supplier_id" id="finSupplier" class="erp-select">
                    <option value="">Selecciona…</option>
                    @foreach ($suppliers as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('supplier_id', $prefill['supplier_id']) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="erp-field">Fecha de pago
                <input type="date" name="payment_date" class="erp-input" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
            </label>
            <label class="erp-field">Monto
                <input type="number" step="0.01" min="0.01" name="amount" id="finAmount" inputmode="decimal" class="erp-input" value="{{ old('amount') }}" required>
            </label>
            <label class="erp-field">Moneda
                <select name="currency" id="finPayCurrency" class="erp-select">
                    <option value="MXN" @selected(old('currency', 'MXN') === 'MXN')>MXN</option>
                    <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                </select>
            </label>
            <label id="finPayRateWrap" class="erp-field">Tipo de cambio (MXN por USD)
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="finPayRate" inputmode="decimal" class="erp-input" value="{{ old('exchange_rate', 1) }}">
            </label>

            <label class="erp-field">Método de pago
                <select name="payment_method_id" class="erp-select">
                    <option value="">—</option>
                    @foreach ($methods as $m)
                        <option value="{{ $m->id }}" @selected((int) old('payment_method_id') === $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="erp-field">Referencia
                <input type="text" name="reference" maxlength="120" class="erp-input" value="{{ old('reference') }}" placeholder="Folio de transferencia, cheque…">
            </label>
            <label class="erp-field">Comprobante
                <input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="erp-input">
                <small>JPG, PNG, WEBP o PDF, máx. 5 MB.</small>
            </label>
            <label class="erp-field fin-full">Notas
                <textarea name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea>
            </label>
        </div>

        <h2 class="erp-card-title" style="margin:22px 0 4px;">Aplicar a documentos abiertos</h2>
        <p class="fin-muted" style="margin:0 0 10px;">Captura el monto a aplicar en la moneda de cada documento. Puedes dejar todo en blanco y aplicar después.</p>
        <label class="fin-check-line">
            <input type="checkbox" name="fifo" value="1" id="finFifo" @checked(old('fifo'))>
            Distribuir automáticamente (FIFO: primero el vencimiento más antiguo) al guardar
        </label>

        <div class="erp-table-wrap">
            <table class="erp-table" id="finDocsTable">
                <thead>
                    <tr><th>Documento</th><th>Vence</th><th>Moneda</th><th class="is-num">Saldo</th><th class="is-num">Aplicar</th></tr>
                </thead>
                <tbody id="finDocsBody">
                    <tr><td colspan="5" class="erp-empty" style="border:0;">Selecciona un cliente o proveedor para ver sus documentos abiertos.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="pay-create-bar">
        <div class="fin-apply-summary">
            <div>Monto del pago: <strong id="finSumAmount">$0.00</strong></div>
            <div>Aplicado: <strong id="finSumApplied">$0.00</strong></div>
            <div>Sin aplicar: <strong id="finSumFree">$0.00</strong></div>
            <button type="button" class="erp-btn erp-btn--sm" id="finFifoBtn">Distribuir FIFO ahora</button>
        </div>

        <div class="erp-form-actions">
            <a href="{{ route('admin.payments.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">Registrar pago</button>
        </div>
        </div>
        </div>
    </form>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
@push('scripts')
<script>
/* Móvil: los documentos abiertos los dibuja erp-finance.js en #finDocsBody; aquí se añaden etiquetas
   para las tarjetas, teclado decimal y el botón «Aplicar saldo» (oculto en escritorio). */
(function () {
    var body = document.getElementById('finDocsBody');
    if (!body) return;
    var labels = ['Documento', 'Vence', 'Moneda', 'Saldo', 'Aplicar'];
    var mark = function (input) {
        var tr = input.closest('tr');
        if (tr) tr.classList.toggle('is-filled', (parseFloat(input.value) || 0) > 0);
    };
    var decorate = function () {
        body.querySelectorAll('tr').forEach(function (tr) {
            var input = tr.querySelector('input[data-doc]');
            if (!input || tr.dataset.payM) return;
            tr.dataset.payM = '1';
            tr.classList.add('pay-doc');
            Array.prototype.forEach.call(tr.children, function (td, i) { if (labels[i]) td.setAttribute('data-label', labels[i]); });
            input.setAttribute('inputmode', 'decimal');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'erp-btn pay-m-only pay-fill';
            btn.textContent = 'Aplicar saldo';
            btn.setAttribute('aria-label', 'Aplicar saldo completo a ' + (tr.children[0] ? tr.children[0].textContent : 'documento'));
            input.parentNode.appendChild(btn);
            input.addEventListener('input', function () { mark(input); });
            mark(input);
        });
    };
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('.pay-fill');
        if (!btn) return;
        var input = btn.parentNode.querySelector('input[data-doc]');
        if (!input || input.disabled) return;
        input.value = (parseFloat(input.dataset.balance) || 0).toFixed(2);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });
    new MutationObserver(decorate).observe(body, { childList: true });
    decorate();
    // FIFO rellena los montos sin evento «input»: se re-marca cuando cambia el total aplicado.
    var applied = document.getElementById('finSumApplied');
    if (applied) {
        new MutationObserver(function () { body.querySelectorAll('input[data-doc]').forEach(mark); })
            .observe(applied, { childList: true, characterData: true, subtree: true });
    }
})();
</script>
@endpush
