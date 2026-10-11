@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@section('title')
    Traspasos de almacén - Admin
@endsection
@section('content')
@php
    $statusLabels = collect(\App\Models\WarehouseTransfer::STATUSES)->mapWithKeys(fn ($s) => [$s => \App\Models\WarehouseTransfer::statusLabel($s)]);
    $chips = [];
    if (request('search')) $chips[] = ['Folio: ' . request('search'), 'search'];
    if (request('status') && isset($statusLabels[request('status')])) $chips[] = ['Estado: ' . $statusLabels[request('status')], 'status'];
    if (request('from_warehouse_id')) $chips[] = ['Origen: ' . ($warehouses->firstWhere('id', (int) request('from_warehouse_id'))?->name ?? '—'), 'from_warehouse_id'];
    if (request('to_warehouse_id')) $chips[] = ['Destino: ' . ($warehouses->firstWhere('id', (int) request('to_warehouse_id'))?->name ?? '—'), 'to_warehouse_id'];
    $without = fn ($key) => route('admin.warehouse-transfers.index', collect(request()->query())->except([$key, 'page'])->all());
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; <strong>Traspasos de almacén</strong></p>
            <h1 class="erp-title">Traspasos de almacén</h1>
            <p class="erp-subtitle">Mueve mercancía entre almacenes con envío y recepción</p>
        </div>
        <div class="wh-actions">
            @if (auth()->user()->hasPermission('warehouse-transfers', 'create'))
                <a href="{{ route('admin.warehouse-transfers.create') }}" class="wh-btn wh-btn-primary">+ Nuevo traspaso</a>
            @endif
        </div>
    </div>

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif

    <div class="wh-stats">
        <a class="wh-stat" href="{{ route('admin.warehouse-transfers.index', ['status' => 'borrador']) }}"><strong>{{ $stats['borrador'] }}</strong><span>Borradores</span></a>
        <a class="wh-stat" href="{{ route('admin.warehouse-transfers.index', ['status' => 'en_transito']) }}"><strong>{{ $stats['en_transito'] }}</strong><span>En tránsito</span></a>
        <a class="wh-stat" href="{{ route('admin.warehouse-transfers.index', ['status' => 'recibido']) }}"><strong>{{ $stats['recibido'] }}</strong><span>Recibidos</span></a>
    </div>

    <form method="GET" class="wh-filters">
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio..." value="{{ request('search') }}">
        <select name="status" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach ($statusLabels as $k => $label)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="from_warehouse_id" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Cualquier origen</option>
            @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('from_warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
        </select>
        <select name="to_warehouse_id" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Cualquier destino</option>
            @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('to_warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
        </select>
        <button type="submit" class="wh-btn">Filtrar</button>
    </form>

    @if ($chips)
        <div class="wh-chips">
            @foreach ($chips as [$label, $key])
                <a class="wh-chip-filter" href="{{ $without($key) }}">{{ $label }} ✕</a>
            @endforeach
        </div>
    @endif

    <div class="wh-card wh-table-wrap">
        <table class="wh-table wh-stack">
            <thead><tr><th>Folio</th><th>Origen</th><th>Destino</th><th>Productos</th><th>Estado</th><th>Creado</th></tr></thead>
            <tbody>
                @forelse ($transfers as $t)
                    <tr>
                        <td data-label="Folio"><a href="{{ route('admin.warehouse-transfers.show', $t) }}">{{ $t->number }}</a></td>
                        <td data-label="Origen">{{ $t->fromWarehouse->name }}</td>
                        <td data-label="Destino">{{ $t->toWarehouse->name }}</td>
                        <td data-label="Productos" class="num">{{ $t->lines_count }}</td>
                        <td data-label="Estado"><span class="erp-chip erp-tone-{{ \App\Models\WarehouseTransfer::statusTone($t->status) }}">{{ \App\Models\WarehouseTransfer::statusLabel($t->status) }}</span></td>
                        <td data-label="Creado">{{ $t->created_at->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="wh-empty">No hay traspasos con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $transfers->links() }}
</section>
</div>
@endsection
