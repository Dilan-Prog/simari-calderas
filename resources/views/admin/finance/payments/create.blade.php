@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    Registrar pago - Admin
@endsection
@section('content')
@php
    $dir = old('direction', $direction);
@endphp
<div class="erp-page fin-page">

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
                <input type="number" step="0.01" min="0.01" name="amount" id="finAmount" class="erp-input" value="{{ old('amount') }}" required>
            </label>
            <label class="erp-field">Moneda
                <select name="currency" id="finPayCurrency" class="erp-select">
                    <option value="MXN" @selected(old('currency', 'MXN') === 'MXN')>MXN</option>
                    <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                </select>
            </label>
            <label id="finPayRateWrap" class="erp-field">Tipo de cambio (MXN por USD)
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="finPayRate" class="erp-input" value="{{ old('exchange_rate', 1) }}">
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
    </form>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
