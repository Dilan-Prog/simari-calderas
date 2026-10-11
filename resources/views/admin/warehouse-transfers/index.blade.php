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
    $tones = ['borrador' => 'neu', 'en_transito' => 'info', 'recibido' => 'ok', 'cancelado' => 'bad'];
    $without = fn ($key) => route('admin.warehouse-transfers.index', collect(request()->query())->except([$key, 'page'])->all());
    $withStatus = fn ($st) => route('admin.warehouse-transfers.index', collect(request()->query())->except(['page', 'status'])->when($st, fn ($c) => $c->put('status', $st))->all());
    $cur = request('status');
    $views = [
        ['label' => 'Todos', 'url' => $withStatus(null), 'active' => ! $cur],
        ['label' => 'Borradores ' . $stats['borrador'], 'url' => $withStatus('borrador'), 'active' => $cur === 'borrador'],
        ['label' => 'En tránsito ' . $stats['en_transito'], 'url' => $withStatus('en_transito'), 'active' => $cur === 'en_transito'],
        ['label' => 'Recibidos ' . $stats['recibido'], 'url' => $withStatus('recibido'), 'active' => $cur === 'recibido'],
    ];
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Folio: ' . request('search'), 'url' => $without('search')];
    if ($cur && isset($statusLabels[$cur])) $chips[] = ['label' => 'Estado: ' . $statusLabels[$cur], 'url' => $without('status')];
    if (request('from_warehouse_id')) $chips[] = ['label' => 'Origen: ' . ($warehouses->firstWhere('id', (int) request('from_warehouse_id'))?->name ?? '—'), 'url' => $without('from_warehouse_id')];
    if (request('to_warehouse_id')) $chips[] = ['label' => 'Destino: ' . ($warehouses->firstWhere('id', (int) request('to_warehouse_id'))?->name ?? '—'), 'url' => $without('to_warehouse_id')];
@endphp
<div class="erp-page">
    <x-erp.page-header title="Traspasos" code="ALM1"
        desc="Movimientos de material entre almacenes con confirmación de recepción."
        :crumbs="[['Almacén', null], ['Traspasos', null]]">
        @if (auth()->user()->hasPermission('warehouse-transfers', 'create'))
            <a href="{{ route('admin.warehouse-transfers.create') }}" class="erp-btn erp-btn--primary">+ Nuevo traspaso</a>
        @endif
    </x-erp.page-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="erp-toolbar" style="padding:0;border:0;flex:1;">
                @if ($cur) <input type="hidden" name="status" value="{{ $cur }}"> @endif
                <div class="erp-search"><input type="search" name="search" placeholder="Buscar por folio…" value="{{ request('search') }}" aria-label="Buscar por folio"></div>
                <select name="from_warehouse_id" class="erp-select" aria-label="Origen" onchange="this.form.submit()">
                    <option value="">Cualquier origen</option>
                    @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('from_warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                </select>
                <select name="to_warehouse_id" class="erp-select" aria-label="Destino" onchange="this.form.submit()">
                    <option value="">Cualquier destino</option>
                    @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('to_warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table wh-stack">
                <thead><tr><th>Folio</th><th>Origen → destino</th><th class="is-right">Partidas</th><th>Solicitó</th><th>Fecha</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($transfers as $t)
                        <tr>
                            <td data-label="Folio" class="erp-cell-folio"><a href="{{ route('admin.warehouse-transfers.show', $t) }}">{{ $t->number }}</a></td>
                            <td data-label="Ruta"><div class="erp-cell-main">{{ $t->fromWarehouse->name }} → {{ $t->toWarehouse->name }}</div></td>
                            <td data-label="Partidas" class="is-num">{{ $t->lines_count }}</td>
                            <td data-label="Solicitó">{{ trim(($t->requestedBy->first_name ?? '') . ' ' . ($t->requestedBy->last_name ?? '')) ?: '—' }}</td>
                            <td data-label="Fecha">{{ $t->created_at->format('d/m/Y') }}</td>
                            <td data-label="Estado"><x-erp.chip :tone="$tones[$t->status] ?? 'neu'" :label="\App\Models\WarehouseTransfer::statusLabel($t->status)" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-erp.empty title="Sin traspasos" text="No hay traspasos con estos filtros." style="border:0;" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <span>{{ $transfers->total() }} traspasos</span>
            <div>{{ $transfers->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
