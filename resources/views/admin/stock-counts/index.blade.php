@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@section('title')
    Conteos físicos - Admin
@endsection
@section('content')
@php
    $statusLabels = ['abierto' => 'Abierto', 'en_conteo' => 'En conteo', 'cerrado' => 'Cerrado', 'ajustado' => 'Ajustado'];
    $chips = [];
    if (request('search')) $chips[] = ['Folio: ' . request('search'), 'search'];
    if (request('status') && isset($statusLabels[request('status')])) $chips[] = ['Estado: ' . $statusLabels[request('status')], 'status'];
    if (request('warehouse_id')) $chips[] = ['Almacén: ' . ($warehouses->firstWhere('id', (int) request('warehouse_id'))?->name ?? '—'), 'warehouse_id'];
    $without = fn ($key) => route('admin.stock-counts.index', collect(request()->query())->except([$key, 'page'])->all());
@endphp
<div class="container user-manager">
<section class="clients-manager-section wh-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; <strong>Conteos físicos</strong></p>
            <h1 class="erp-title">Conteos físicos</h1>
            <p class="erp-subtitle">Compara el inventario del sistema contra el conteo real y ajusta las diferencias</p>
        </div>
        <div class="wh-actions">
            @if (auth()->user()->hasPermission('stock-counts', 'create'))
                <a href="{{ route('admin.stock-counts.create') }}" class="wh-btn wh-btn-primary">+ Nuevo conteo</a>
            @endif
        </div>
    </div>

    @if (session('error')) <div class="wh-alert wh-alert-error">{{ session('error') }}</div> @endif

    <div class="wh-stats">
        <a class="wh-stat" href="{{ route('admin.stock-counts.index', ['status' => 'en_conteo']) }}"><strong>{{ $stats['abiertos'] }}</strong><span>Abiertos / en conteo</span></a>
        <a class="wh-stat" href="{{ route('admin.stock-counts.index', ['status' => 'cerrado']) }}"><strong>{{ $stats['por_ajustar'] }}</strong><span>Cerrados sin ajustar</span></a>
        <a class="wh-stat" href="{{ route('admin.stock-counts.index', ['status' => 'ajustado']) }}"><strong>{{ $stats['ajustados'] }}</strong><span>Ajustados</span></a>
    </div>

    <form method="GET" class="wh-filters">
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio..." value="{{ request('search') }}">
        <select name="status" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach ($statusLabels as $k => $label) <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option> @endforeach
        </select>
        <select name="warehouse_id" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los almacenes</option>
            @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
        </select>
        <button type="submit" class="wh-btn">Filtrar</button>
    </form>

    @if ($chips)
        <div class="wh-chips">
            @foreach ($chips as [$label, $key]) <a class="wh-chip-filter" href="{{ $without($key) }}">{{ $label }} ✕</a> @endforeach
        </div>
    @endif

    <div class="wh-card wh-table-wrap">
        <table class="wh-table wh-stack">
            <thead><tr><th>Folio</th><th>Almacén</th><th>Avance</th><th>Estado</th><th>Inicio</th></tr></thead>
            <tbody>
                @forelse ($counts as $c)
                    @php $pct = $c->lines_count ? round($c->counted_lines_count / $c->lines_count * 100) : 0; @endphp
                    <tr>
                        <td data-label="Folio"><a href="{{ route('admin.stock-counts.show', $c) }}">{{ $c->number }}</a></td>
                        <td data-label="Almacén">{{ $c->warehouse->name }}</td>
                        <td data-label="Avance">
                            <div style="display:flex; gap:8px; align-items:center;">
                                <div class="wh-progress"><span style="width: {{ $pct }}%"></span></div>
                                <small>{{ $c->counted_lines_count }}/{{ $c->lines_count }}</small>
                            </div>
                        </td>
                        <td data-label="Estado"><span class="erp-chip erp-tone-{{ \App\Models\StockCount::statusTone($c->status) }}">{{ \App\Models\StockCount::statusLabel($c->status) }}</span></td>
                        <td data-label="Inicio">{{ $c->started_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="wh-empty">No hay conteos con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $counts->links() }}
</section>
</div>
@endsection
