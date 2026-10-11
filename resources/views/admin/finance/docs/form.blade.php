@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    {{ $doc ? 'Editar ' . $doc->number : 'Nueva ' . $cfg['singular'] }} - Admin
@endsection
@section('content')
@php
    $r = $cfg['route'];
    $pc = $cfg['party_col'];
    $isRec = $cfg['kind'] === 'receivable';
    $v = fn ($key, $default = null) => old($key, $doc?->{$key} ?? $default);
    $money = fn ($key) => old($key, $doc ? number_format((float) $doc->{$key}, 2, '.', '') : null);
@endphp
<div class="container user-manager">
<section class="clients-manager-section fin-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; Finanzas &gt; <a href="{{ route($r . '.index') }}">{{ $cfg['title'] }}</a> &gt; <strong>{{ $doc ? $doc->number : 'Nueva' }}</strong></p>
            <h1 class="erp-title">{{ $doc ? 'Editar ' . $doc->number : 'Nueva ' . $cfg['singular'] }}</h1>
            <p class="erp-subtitle">El saldo y el estatus se recalculan solos con los pagos aplicados.</p>
        </div>
    </div>

    <form method="POST" action="{{ $doc ? route($r . '.update', $doc->id) : route($r . '.store') }}" class="fin-card">
        @csrf
        @if ($doc) @method('PUT') @endif

        <div class="fin-form">
            <label class="full">{{ $cfg['party_label'] }}
                <select name="{{ $pc }}" class="users-manager-input" required>
                    <option value="">Selecciona…</option>
                    @foreach ($parties as $id => $name)
                        <option value="{{ $id }}" @selected((int) old($pc, $doc?->{$pc} ?? ($prefill[$pc] ?? 0)) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Fecha de emisión
                <input type="date" name="issue_date" class="users-manager-input" value="{{ old('issue_date', $doc?->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            </label>
            <label>Días de crédito
                <input type="number" name="credit_days" class="users-manager-input" min="0" max="3650" value="{{ $v('credit_days', 0) }}">
                <span class="hint">El vencimiento = emisión + días.</span>
            </label>
            <label>Vencimiento (opcional)
                <input type="date" name="due_date" class="users-manager-input" value="{{ old('due_date') }}">
                <span class="hint">Si lo capturas, se usa en lugar de los días.{{ $doc ? ' Actual: ' . $doc->due_date->format('d/m/Y') : '' }}</span>
            </label>

            <label>Moneda
                <select name="currency" class="users-manager-input" id="finCurrency">
                    <option value="MXN" @selected($v('currency', 'MXN') === 'MXN')>MXN</option>
                    <option value="USD" @selected($v('currency', 'MXN') === 'USD')>USD</option>
                </select>
            </label>
            <label id="finRateWrap">Tipo de cambio (MXN por USD)
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="finRate" class="users-manager-input" value="{{ $v('exchange_rate', 1) }}">
            </label>

            <label>Subtotal
                <input type="number" step="0.01" min="0" name="subtotal" id="finSubtotal" class="users-manager-input" value="{{ $money('subtotal') }}" required>
            </label>
            <label>Impuestos (IVA)
                <input type="number" step="0.01" min="0" name="tax_total" id="finTax" class="users-manager-input" value="{{ $money('tax_total') ?? '0.00' }}">
            </label>
            <label>Total
                <input type="number" step="0.01" min="0" name="total" id="finTotal" class="users-manager-input" value="{{ $money('total') }}">
                <span class="hint">Vacío = subtotal + impuestos.</span>
            </label>

            @if ($isRec)
                <label>Origen
                    <select name="source_type" class="users-manager-input">
                        <option value="">Manual</option>
                        @foreach (['sales_order' => 'Pedido', 'quote' => 'Cotización', 'project' => 'Proyecto', 'store_order' => 'Orden de tienda'] as $k => $label)
                            <option value="{{ $k }}" @selected($v('source_type') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>ID del origen
                    <input type="number" min="1" name="source_id" class="users-manager-input" value="{{ $v('source_id') }}">
                </label>
                <label>ID de proyecto
                    <input type="number" min="1" name="project_id" class="users-manager-input" value="{{ $v('project_id') }}">
                </label>
            @else
                <label>Orden de compra (ID)
                    <input type="number" min="1" name="purchase_order_id" class="users-manager-input" value="{{ $v('purchase_order_id') }}">
                </label>
                <label>Folio de factura del proveedor
                    <input type="text" maxlength="60" name="supplier_invoice" class="users-manager-input" value="{{ $v('supplier_invoice') }}">
                </label>
            @endif

            <label class="full">Notas
                <textarea name="notes" rows="3" class="users-manager-input">{{ $v('notes') }}</textarea>
            </label>
        </div>

        <div class="fin-form-actions">
            <button type="submit" class="fin-btn fin-btn-primary">{{ $doc ? 'Guardar cambios' : 'Crear ' . $cfg['singular'] }}</button>
            <a href="{{ $doc ? route($r . '.show', $doc->id) : route($r . '.index') }}" class="fin-btn">Cancelar</a>
        </div>
    </form>

</section>
</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
