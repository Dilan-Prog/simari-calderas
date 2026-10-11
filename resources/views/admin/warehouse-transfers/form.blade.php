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
<div class="erp-page">
    <x-erp.page-header :title="$transfer ? 'Editar ' . $transfer->number : 'Nuevo traspaso'" code="ALM1"
        desc="El traspaso se guarda como borrador; el inventario se mueve hasta enviarlo."
        :crumbs="[['Almacén', null], ['Traspasos', route('admin.warehouse-transfers.index')], [$transfer ? $transfer->number : 'Nuevo', null]]" />

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error" role="alert">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    <form method="POST" action="{{ $transfer ? route('admin.warehouse-transfers.update', $transfer) : route('admin.warehouse-transfers.store') }}"
          data-wh-transfer-form data-products-url="{{ route('admin.warehouse-transfers.products') }}" data-initial="{{ json_encode($initial) }}">
        @csrf
        @if ($transfer) @method('PUT') @endif

        <x-erp.card title="Origen y destino">
            <div class="erp-form-grid">
                <div class="erp-field">
                    <label for="from_warehouse_id">Almacén origen</label>
                    <select name="from_warehouse_id" id="from_warehouse_id" class="erp-select" required>
                        <option value="">Selecciona...</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((string) old('from_warehouse_id', $transfer?->from_warehouse_id) === (string) $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="erp-field">
                    <label for="to_warehouse_id">Almacén destino</label>
                    <select name="to_warehouse_id" id="to_warehouse_id" class="erp-select" required>
                        <option value="">Selecciona...</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((string) old('to_warehouse_id', $transfer?->to_warehouse_id) === (string) $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="erp-field" style="margin-top:14px;">
                <label for="notes">Notas</label>
                <textarea name="notes" id="notes" rows="2" class="erp-input" maxlength="1000">{{ old('notes', $transfer?->notes) }}</textarea>
            </div>
        </x-erp.card>

        <x-erp.card title="Material a traspasar" sub="Busca por nombre o SKU; el disponible se calcula contra el almacén origen.">
            <div class="wh-search">
                <input type="search" class="erp-input" data-product-search placeholder="Buscar producto por nombre o SKU..." autocomplete="off" aria-label="Buscar producto">
                <div class="wh-search-results" data-product-results></div>
            </div>
            <div class="erp-table-wrap">
                <table class="erp-table wh-stack">
                    <thead><tr><th>Producto</th><th class="is-right">Disponible en origen</th><th class="is-right">Cantidad</th><th></th></tr></thead>
                    <tbody data-lines-body></tbody>
                </table>
                <div class="wh-muted" data-lines-empty style="padding:20px;text-align:center;">Aún no agregas productos.</div>
            </div>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ $transfer ? route('admin.warehouse-transfers.show', $transfer) : route('admin.warehouse-transfers.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $transfer ? 'Guardar cambios' : 'Crear borrador' }}</button>
        </div>
    </form>
</div>
@endsection
