@extends('admin.layouts.master')

@section('title', ($order ? 'Editar ' . $order->number : 'Nueva orden de producción') . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@push('scripts')
    @vite(['resources/js/admin/erp-production.js'])
@endpush

@php
    $val = fn ($k, $default = null) => old($k, $order?->{$k} ?? $default);
    $dateVal = fn ($k) => old($k, $order?->{$k}?->format('Y-m-d'));
@endphp

@section('content')
<div class="erp-page prd-page" data-prd-form data-search-url="{{ route('admin.production-orders.search-products') }}">
    <x-erp.page-header
        :title="$order ? 'Editar ' . $order->number : 'Nueva orden de producción'"
        code="PRD2"
        desc="Se explota la lista de materiales (BOM) vigente del producto para calcular los componentes y el costo estándar."
        :crumbs="[['Producción', null], ['Órdenes de producción', route('admin.production-orders.index')], [$order ? $order->number : 'Nueva', null]]" />

    @if ($errors->any())
        <div class="prd-alert" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ $order ? route('admin.production-orders.update', $order) : route('admin.production-orders.store') }}" class="erp-card prd-form-card">
        @csrf
        @if ($order) @method('PUT') @endif

        <div class="erp-form-grid">
            @if ($order)
                <div class="erp-field prd-wide">
                    <label>Producto</label>
                    <input type="text" class="erp-input" value="{{ $order->product->name ?? '' }} × {{ rtrim(rtrim(number_format($order->quantity_planned, 4, '.', ''), '0'), '.') }}" disabled>
                </div>
            @else
                <div class="erp-field prd-wide">
                    <label>Producto a fabricar (con BOM vigente)</label>
                    <div class="prd-picker">
                        <input type="search" class="erp-input" placeholder="Buscar producto o SKU…" data-picker-input autocomplete="off" value="{{ $preProduct?->name }}">
                        <input type="hidden" name="product_id" value="{{ old('product_id', $preProduct?->id) }}" data-picker-id>
                        <div class="prd-picker-list" hidden></div>
                    </div>
                </div>
                <div class="erp-field">
                    <label>Cantidad a fabricar</label>
                    <input type="number" name="quantity" min="1" step="1" value="{{ old('quantity', 1) }}" class="erp-input" required>
                </div>
                <div class="erp-field">
                    <label>Almacén (consumo y producto terminado)</label>
                    <select name="warehouse_id" class="erp-input">
                        <option value="">Almacén por defecto</option>
                        @foreach ($warehouses as $w)
                            <option value="{{ $w->id }}" @selected((int) old('warehouse_id') === $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="erp-field">
                <label>Proyecto</label>
                <select name="project_id" class="erp-input">
                    <option value="">Sin proyecto</option>
                    @foreach ($projects as $p)
                        <option value="{{ $p->id }}" @selected((int) old('project_id', $order?->project_id ?? $preProject) === $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 40) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-field">
                <label>Pedido</label>
                <select name="sales_order_id" class="erp-input">
                    <option value="">Sin pedido</option>
                    @foreach ($orders as $so)
                        <option value="{{ $so->id }}" @selected((int) old('sales_order_id', $order?->sales_order_id ?? $preOrder) === $so->id)>{{ $so->order_number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="erp-field">
                <label>Inicio</label>
                <input type="date" name="start_date" value="{{ $dateVal('start_date') }}" class="erp-input">
            </div>
            <div class="erp-field">
                <label>Fecha límite</label>
                <input type="date" name="due_date" value="{{ $dateVal('due_date') }}" class="erp-input">
            </div>
            <div class="erp-field prd-wide">
                <label>Notas</label>
                <textarea name="notes" rows="3" class="erp-input">{{ $val('notes') }}</textarea>
            </div>
        </div>

        <div class="erp-form-actions">
            <a href="{{ $order ? route('admin.production-orders.show', $order) : route('admin.production-orders.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $order ? 'Guardar cambios' : 'Crear orden' }}</button>
        </div>
    </form>
</div>
@endsection
