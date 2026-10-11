@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    Ubicaciones de almacén - Admin
@endsection
@section('content')
@php
    $user = auth()->user();
    $canCreate = $user->hasPermission('inventory', 'create');
    $canEdit = $user->hasPermission('inventory', 'edit');
    $canDelete = $user->hasPermission('inventory', 'delete');
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.inventory.index') }}">Inventario</a> &gt; <strong>Ubicaciones</strong>
            </p>
            <h1 class="erp-title">Ubicaciones de almacén</h1>
            <p class="erp-subtitle">Pasillos, anaqueles o zonas, y el detalle de saldos por almacén</p>
        </div>
        <div class="wh-actions">
            <a href="{{ route('admin.inventory.warehouses.index') }}" class="wh-btn">Almacenes</a>
        </div>
    </div>

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    @if (! $warehouse)
        <div class="erp-empty">Aún no hay almacenes registrados.</div>
    @else
    <form method="GET" class="wh-filters">
        <select name="warehouse_id" class="users-manager-input" onchange="this.form.submit()">
            @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected($w->id === $warehouse->id)>{{ $w->name }}</option> @endforeach
        </select>
    </form>

    <div class="wh-card">
        <h2>Ubicaciones de {{ $warehouse->name }}</h2>
        @if ($canCreate)
            <form method="POST" action="{{ route('admin.warehouse-locations.store') }}" class="wh-inline-form" style="margin-bottom:14px;">
                @csrf
                <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                <div class="wh-field"><label>Código</label><input type="text" name="code" class="users-manager-input" maxlength="30" placeholder="A-01-02" required></div>
                <div class="wh-field"><label>Nombre (opcional)</label><input type="text" name="name" class="users-manager-input" maxlength="100" placeholder="Pasillo A, nivel 2"></div>
                <button type="submit" class="wh-btn wh-btn-primary">Agregar</button>
            </form>
        @endif
        <div class="wh-table-wrap">
            <table class="wh-table wh-stack">
                <thead><tr><th>Código</th><th>Nombre</th><th class="num">Productos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($locations as $loc)
                        <tr>
                            @if ($canEdit)
                                <td data-label="Código"><input form="loc-{{ $loc->id }}" type="text" name="code" value="{{ $loc->code }}" maxlength="30" class="users-manager-input" required></td>
                                <td data-label="Nombre"><input form="loc-{{ $loc->id }}" type="text" name="name" value="{{ $loc->name }}" maxlength="100" class="users-manager-input"></td>
                            @else
                                <td data-label="Código">{{ $loc->code }}</td>
                                <td data-label="Nombre">{{ $loc->name ?: '—' }}</td>
                            @endif
                            <td data-label="Productos" class="num">{{ $loc->stock_balances_count }}</td>
                            <td data-label="Estado">
                                @if ($canEdit)
                                    <input form="loc-{{ $loc->id }}" type="hidden" name="is_active" value="0">
                                    <label><input form="loc-{{ $loc->id }}" type="checkbox" name="is_active" value="1" @checked($loc->is_active)> Activa</label>
                                @else
                                    {{ $loc->is_active ? 'Activa' : 'Inactiva' }}
                                @endif
                            </td>
                            <td>
                                <div class="wh-actions">
                                    @if ($canEdit) <button form="loc-{{ $loc->id }}" type="submit" class="wh-btn">Guardar</button> @endif
                                    @if ($canDelete)
                                        <form method="POST" action="{{ route('admin.warehouse-locations.destroy', $loc) }}" data-confirm="¿Eliminar la ubicación? Sus productos quedarán sin ubicación.">
                                            @csrf @method('DELETE') <button type="submit" class="wh-btn wh-btn-danger">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="wh-empty">Este almacén no tiene ubicaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canEdit)
            @foreach ($locations as $loc)
                <form method="POST" action="{{ route('admin.warehouse-locations.update', $loc) }}" id="loc-{{ $loc->id }}">@csrf @method('PUT')</form>
            @endforeach
        @endif
    </div>

    <div class="wh-card" data-wh-locations data-assign-url="{{ route('admin.warehouse-locations.assign') }}">
        <h2>Saldos de {{ $warehouse->name }}</h2>
        <form method="GET" class="wh-filters">
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <input type="text" name="search" class="users-manager-input" placeholder="Buscar producto o SKU..." value="{{ request('search') }}">
            <select name="location_id" class="users-manager-input" onchange="this.form.submit()">
                <option value="">Todas las ubicaciones</option>
                <option value="none" @selected(request('location_id') === 'none')>Sin ubicación</option>
                @foreach ($locations as $loc) <option value="{{ $loc->id }}" @selected((string) request('location_id') === (string) $loc->id)>{{ $loc->code }}</option> @endforeach
            </select>
            <button type="submit" class="wh-btn">Filtrar</button>
        </form>
        <div class="wh-table-wrap">
            <table class="wh-table wh-stack">
                <thead><tr><th>Producto</th><th>SKU</th><th class="num">Cantidad</th><th>Ubicación</th></tr></thead>
                <tbody>
                    @forelse ($balances as $b)
                        <tr>
                            <td data-label="Producto">{{ $b->product->name }}</td>
                            <td data-label="SKU">{{ $b->product->sku }}</td>
                            <td data-label="Cantidad" class="num">{{ $b->quantity }}</td>
                            <td data-label="Ubicación">
                                @if ($canEdit)
                                    <select class="users-manager-input" data-assign-select data-balance="{{ $b->id }}">
                                        <option value="">Sin ubicación</option>
                                        @foreach ($locations->where('is_active', true)->merge($locations->where('id', $b->location_id))->unique('id') as $loc)
                                            <option value="{{ $loc->id }}" @selected($b->location_id === $loc->id)>{{ $loc->code }}{{ $loc->name ? ' · ' . $loc->name : '' }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $b->location?->code ?? '—' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="wh-empty">Sin saldos en este almacén.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>
</div>
@endsection
