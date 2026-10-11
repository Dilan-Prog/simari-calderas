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
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <a href="{{ route('admin.payments.index') }}">Pagos</a> &gt; <strong>Nuevo</strong></p>
            <h1 class="erp-title">Registrar pago</h1>
            <p class="erp-subtitle">Captura el pago y aplícalo a uno o varios documentos abiertos.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.payments.store') }}" enctype="multipart/form-data" class="fin-card" id="finPaymentForm"
          data-open-url="{{ route('admin.payments.open-documents') }}" data-prefill-doc="{{ $prefill['doc'] }}">
        @csrf
        <div class="fin-form">
            <label>Tipo
                <select name="direction" id="finDirection" class="users-manager-input">
                    <option value="in" @selected($dir === 'in')>Cobro de cliente</option>
                    <option value="out" @selected($dir === 'out')>Pago a proveedor</option>
                </select>
            </label>
            <label id="finCustomerWrap">Cliente
                <select name="customer_id" id="finCustomer" class="users-manager-input">
                    <option value="">Selecciona…</option>
                    @foreach ($customers as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('customer_id', $prefill['customer_id']) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label id="finSupplierWrap">Proveedor
                <select name="supplier_id" id="finSupplier" class="users-manager-input">
                    <option value="">Selecciona…</option>
                    @foreach ($suppliers as $id => $name)
                        <option value="{{ $id }}" @selected((int) old('supplier_id', $prefill['supplier_id']) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Fecha de pago
                <input type="date" name="payment_date" class="users-manager-input" value="{{ old('payment_date', now()->format('Y-m-d')) }}" required>
            </label>
            <label>Monto
                <input type="number" step="0.01" min="0.01" name="amount" id="finAmount" class="users-manager-input" value="{{ old('amount') }}" required>
            </label>
            <label>Moneda
                <select name="currency" id="finPayCurrency" class="users-manager-input">
                    <option value="MXN" @selected(old('currency', 'MXN') === 'MXN')>MXN</option>
                    <option value="USD" @selected(old('currency') === 'USD')>USD</option>
                </select>
            </label>
            <label id="finPayRateWrap">Tipo de cambio (MXN por USD)
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="finPayRate" class="users-manager-input" value="{{ old('exchange_rate', 1) }}">
            </label>

            <label>Método de pago
                <select name="payment_method_id" class="users-manager-input">
                    <option value="">—</option>
                    @foreach ($methods as $m)
                        <option value="{{ $m->id }}" @selected((int) old('payment_method_id') === $m->id)>{{ $m->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Referencia
                <input type="text" name="reference" maxlength="120" class="users-manager-input" value="{{ old('reference') }}" placeholder="Folio de transferencia, cheque…">
            </label>
            <label>Comprobante
                <input type="file" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="users-manager-input">
                <span class="hint">JPG, PNG, WEBP o PDF, máx. 5 MB.</span>
            </label>
            <label class="full">Notas
                <textarea name="notes" rows="2" class="users-manager-input">{{ old('notes') }}</textarea>
            </label>
        </div>

        <h2 style="margin:22px 0 8px;font-size:15px;">Aplicar a documentos abiertos</h2>
        <p class="fin-muted" style="margin:0 0 10px;">Captura el monto a aplicar en la moneda de cada documento. Puedes dejar todo en blanco y aplicar después.</p>
        <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:10px;">
            <input type="checkbox" name="fifo" value="1" id="finFifo" @checked(old('fifo'))>
            Distribuir automáticamente (FIFO: primero el vencimiento más antiguo) al guardar
        </label>

        <div class="fin-scroll">
            <table class="fin-table" id="finDocsTable">
                <thead>
                    <tr><th>DOCUMENTO</th><th>VENCE</th><th>MONEDA</th><th class="num">SALDO</th><th class="num">APLICAR</th></tr>
                </thead>
                <tbody id="finDocsBody">
                    <tr><td colspan="5" class="fin-empty">Selecciona un cliente o proveedor para ver sus documentos abiertos.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="fin-apply-summary">
            <div>Monto del pago: <strong id="finSumAmount">$0.00</strong></div>
            <div>Aplicado: <strong id="finSumApplied">$0.00</strong></div>
            <div>Sin aplicar: <strong id="finSumFree">$0.00</strong></div>
            <button type="button" class="fin-btn fin-btn-sm" id="finFifoBtn">Distribuir FIFO ahora</button>
        </div>

        <div class="fin-form-actions">
            <button type="submit" class="fin-btn fin-btn-primary">Registrar pago</button>
            <a href="{{ route('admin.payments.index') }}" class="fin-btn">Cancelar</a>
        </div>
    </form>

</section>
</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
