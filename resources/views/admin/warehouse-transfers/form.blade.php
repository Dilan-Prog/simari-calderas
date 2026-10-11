@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    {{ $transfer ? 'Editar ' . $transfer->number : 'Nuevo traspaso' }} - Admin
@endsection
@section('content')
@php
    $initial = old('lines') ? collect(old('lines'))->map(function ($l) {
        $p = \App\Models\Products::find($l['product_id'] ?? 0);
        return $p ? ['product_id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'quantity' => (int) ($l['quantity'] ?? 1), 'available' => null] : null;
    })->filter()->values()->all() : $initialLines;
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.warehouse-transfers.index') }}">Traspasos</a> &gt; <strong>{{ $transfer ? $transfer->number : 'Nuevo' }}</strong>
            </p>
            <h1 class="erp-title">{{ $transfer ? 'Editar ' . $transfer->number : 'Nuevo traspaso' }}</h1>
            <p class="erp-subtitle">El traspaso se guarda como borrador; el inventario se mueve hasta enviarlo.</p>
        </div>
    </div>

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    <form method="POST" action="{{ $transfer ? route('admin.warehouse-transfers.update', $transfer) : route('admin.warehouse-transfers.store') }}"
          data-wh-transfer-form data-products-url="{{ route('admin.warehouse-transfers.products') }}" data-initial="{{ json_encode($initial) }}">
        @csrf
        @if ($transfer) @method('PUT') @endif

        <div class="wh-card">
            <div class="wh-form-grid">
                <div>
                    <label for="from_warehouse_id">Almacén origen</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="users-manager-input" required>
                        <option value="">Selecciona...</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((string) old('from_warehouse_id', $transfer?->from_warehouse_id) === (string) $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="to_warehouse_id">Almacén destino</label>
                    <select name="to_warehouse_id" id="to_warehouse_id" class="users-manager-input" required>
                        <option value="">Selecciona...</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((string) old('to_warehouse_id', $transfer?->to_warehouse_id) === (string) $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="wh-field">
                <label for="notes">Notas</label>
                <textarea name="notes" id="notes" rows="2" class="users-manager-input" maxlength="1000">{{ old('notes', $transfer?->notes) }}</textarea>
            </div>
        </div>

        <div class="wh-card">
            <h2>Productos</h2>
            <div class="wh-search">
                <input type="text" class="users-manager-input" data-product-search placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                <div class="wh-search-results" data-product-results></div>
            </div>
            <div class="wh-table-wrap">
                <table class="wh-table wh-stack">
                    <thead><tr><th>Producto</th><th class="num">Disponible en origen</th><th class="num">Cantidad</th><th></th></tr></thead>
                    <tbody data-lines-body></tbody>
                </table>
                <div class="wh-empty" data-lines-empty>Aún no agregas productos.</div>
            </div>
        </div>

        <div class="wh-actions">
            <button type="submit" class="wh-btn wh-btn-primary">{{ $transfer ? 'Guardar cambios' : 'Crear borrador' }}</button>
            <a href="{{ $transfer ? route('admin.warehouse-transfers.show', $transfer) : route('admin.warehouse-transfers.index') }}" class="wh-btn">Cancelar</a>
        </div>
    </form>
</section>
</div>
@endsection
