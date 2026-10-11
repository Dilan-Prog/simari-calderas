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
<div class="erp-page">
    <x-erp.page-header title="Ubicaciones" code="ALM6"
        desc="Pasillo, rack y nivel por almacén, y el detalle de saldos por ubicación."
        :crumbs="[['Almacén', null], ['Inventario', route('admin.inventory.index')], ['Ubicaciones', null]]">
        <a href="{{ route('admin.stock-reservations.index') }}" class="erp-btn">Reservas por proyecto</a>
        <a href="{{ route('admin.inventory.warehouses.index') }}" class="erp-btn">Almacenes</a>
    </x-erp.page-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error" role="alert">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    @if (! $warehouse)
        <x-erp.empty title="Sin almacenes" text="Aún no hay almacenes registrados." />
    @else
    <x-erp.worklist :views="$warehouses->map(fn ($w) => ['label' => $w->name, 'url' => route('admin.warehouse-locations.index', ['warehouse_id' => $w->id]), 'active' => $w->id === $warehouse->id])->all()">
        @if ($canCreate)
            <div class="erp-toolbar">
                <form method="POST" action="{{ route('admin.warehouse-locations.store') }}" class="wh-inline-form" style="width:100%;">
                    @csrf
                    <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
                    <div class="erp-field"><label for="loc-new-code">Código</label><input id="loc-new-code" type="text" name="code" class="erp-input" maxlength="30" placeholder="A-01-02" required></div>
                    <div class="erp-field"><label for="loc-new-name">Nombre (opcional)</label><input id="loc-new-name" type="text" name="name" class="erp-input" maxlength="100" placeholder="Pasillo A · Rack 01 · Nivel 2"></div>
                    <button type="submit" class="erp-btn erp-btn--primary">+ Nueva ubicación</button>
                </form>
            </div>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table wh-stack">
                <thead><tr><th>Código</th><th>Ubicación</th><th class="is-right">Productos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($locations as $loc)
                        <tr>
                            @if ($canEdit)
                                <td data-label="Código"><input form="loc-{{ $loc->id }}" type="text" name="code" value="{{ $loc->code }}" maxlength="30" class="erp-input" required aria-label="Código"></td>
                                <td data-label="Ubicación"><input form="loc-{{ $loc->id }}" type="text" name="name" value="{{ $loc->name }}" maxlength="100" class="erp-input" aria-label="Nombre"></td>
                            @else
                                <td data-label="Código" class="erp-cell-folio">{{ $loc->code }}</td>
                                <td data-label="Ubicación"><div class="erp-cell-main">{{ $loc->name ?: '—' }}</div></td>
                            @endif
                            <td data-label="Productos" class="is-num">{{ $loc->stock_balances_count }}</td>
                            <td data-label="Estado">
                                @if ($canEdit)
                                    <input form="loc-{{ $loc->id }}" type="hidden" name="is_active" value="0">
                                    <label><input form="loc-{{ $loc->id }}" type="checkbox" name="is_active" value="1" @checked($loc->is_active)> Activa</label>
                                @else
                                    <x-erp.chip :tone="$loc->is_active ? 'ok' : 'neu'" :label="$loc->is_active ? 'Activa' : 'Inactiva'" />
                                @endif
                            </td>
                            <td>
                                <div class="wh-row-actions">
                                    @if ($canEdit) <button form="loc-{{ $loc->id }}" type="submit" class="erp-btn erp-btn--sm">Guardar</button> @endif
                                    @if ($canDelete)
                                        <form method="POST" action="{{ route('admin.warehouse-locations.destroy', $loc) }}" class="wh-inline" data-confirm="¿Eliminar la ubicación? Sus productos quedarán sin ubicación.">
                                            @csrf @method('DELETE') <button type="submit" class="erp-btn erp-btn--sm erp-btn--danger">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-erp.empty title="Sin ubicaciones" text="Este almacén no tiene ubicaciones." style="border:0;" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($canEdit)
            @foreach ($locations as $loc)
                <form method="POST" action="{{ route('admin.warehouse-locations.update', $loc) }}" id="loc-{{ $loc->id }}">@csrf @method('PUT')</form>
            @endforeach
        @endif
    </x-erp.worklist>

    <x-erp.card :title="'Saldos de ' . $warehouse->name" sub="Asigna la ubicación física de cada producto; no cambia las cantidades." data-wh-locations data-assign-url="{{ route('admin.warehouse-locations.assign') }}">
        <form method="GET" class="erp-toolbar" style="padding:0 0 12px;border:0;">
            <input type="hidden" name="warehouse_id" value="{{ $warehouse->id }}">
            <div class="erp-search"><input type="search" name="search" placeholder="Buscar producto o SKU..." value="{{ request('search') }}" aria-label="Buscar producto o SKU"></div>
            <select name="location_id" class="erp-select" aria-label="Ubicación" onchange="this.form.submit()">
                <option value="">Todas las ubicaciones</option>
                <option value="none" @selected(request('location_id') === 'none')>Sin ubicación</option>
                @foreach ($locations as $loc) <option value="{{ $loc->id }}" @selected((string) request('location_id') === (string) $loc->id)>{{ $loc->code }}</option> @endforeach
            </select>
            <button type="submit" class="erp-btn">Filtrar</button>
        </form>
        <div class="erp-table-wrap">
            <table class="erp-table wh-stack">
                <thead><tr><th>Producto</th><th>SKU</th><th class="is-right">Cantidad</th><th>Ubicación</th></tr></thead>
                <tbody>
                    @forelse ($balances as $b)
                        <tr>
                            <td data-label="Producto" class="erp-cell-main">{{ $b->product->name }}</td>
                            <td data-label="SKU">{{ $b->product->sku }}</td>
                            <td data-label="Cantidad" class="is-num">{{ $b->quantity }}</td>
                            <td data-label="Ubicación">
                                @if ($canEdit)
                                    <select class="erp-select" data-assign-select data-balance="{{ $b->id }}" aria-label="Ubicación de {{ $b->product->name }}">
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
                        <tr><td colspan="4"><x-erp.empty title="Sin saldos" text="Sin saldos en este almacén." style="border:0;" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-erp.card>
    @endif
</div>
@endsection
