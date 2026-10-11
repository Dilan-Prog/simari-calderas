@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@section('title')
    Conteos físicos - Admin
@endsection
@section('content')
@php
    $statusLabels = ['abierto' => 'Abierto', 'en_conteo' => 'En conteo', 'cerrado' => 'Por ajustar', 'ajustado' => 'Ajustado'];
    $tones = ['abierto' => 'neu', 'en_conteo' => 'info', 'cerrado' => 'warn', 'ajustado' => 'ok'];
    $without = fn ($key) => route('admin.stock-counts.index', collect(request()->query())->except([$key, 'page'])->all());
    $withStatus = fn ($st) => route('admin.stock-counts.index', collect(request()->query())->except(['page', 'status'])->when($st, fn ($c) => $c->put('status', $st))->all());
    $cur = request('status');
    $views = [
        ['label' => 'Todos', 'url' => $withStatus(null), 'active' => ! $cur],
        ['label' => 'En conteo ' . $stats['abiertos'], 'url' => $withStatus('en_conteo'), 'active' => $cur === 'en_conteo'],
        ['label' => 'Por ajustar ' . $stats['por_ajustar'], 'url' => $withStatus('cerrado'), 'active' => $cur === 'cerrado'],
        ['label' => 'Ajustados ' . $stats['ajustados'], 'url' => $withStatus('ajustado'), 'active' => $cur === 'ajustado'],
    ];
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Folio: ' . request('search'), 'url' => $without('search')];
    if ($cur && isset($statusLabels[$cur])) $chips[] = ['label' => 'Estado: ' . $statusLabels[$cur], 'url' => $without('status')];
    if (request('warehouse_id')) $chips[] = ['label' => 'Almacén: ' . ($warehouses->firstWhere('id', (int) request('warehouse_id'))?->name ?? '—'), 'url' => $without('warehouse_id')];
@endphp
<div class="erp-page">
    <x-erp.page-header title="Conteo físico" code="ALM2"
        desc="Conteos cíclicos con diferencias contra sistema y ajuste."
        :crumbs="[['Almacén', null], ['Conteo físico', null]]">
        @if (auth()->user()->hasPermission('stock-counts', 'create'))
            <a href="{{ route('admin.stock-counts.create') }}" class="erp-btn erp-btn--primary">+ Nuevo conteo</a>
        @endif
    </x-erp.page-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="erp-toolbar" style="padding:0;border:0;flex:1;">
                @if ($cur) <input type="hidden" name="status" value="{{ $cur }}"> @endif
                <div class="erp-search"><input type="search" name="search" placeholder="Buscar por folio…" value="{{ request('search') }}" aria-label="Buscar por folio"></div>
                <select name="warehouse_id" class="erp-select" aria-label="Almacén" onchange="this.form.submit()">
                    <option value="">Todos los almacenes</option>
                    @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) request('warehouse_id') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table wh-stack">
                <thead><tr><th>Folio</th><th>Almacén</th><th>Inicio</th><th>Avance</th><th class="is-right">Diferencias</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($counts as $c)
                        @php $pct = $c->lines_count ? round($c->counted_lines_count / $c->lines_count * 100) : 0; @endphp
                        <tr>
                            <td data-label="Folio" class="erp-cell-folio"><a href="{{ route('admin.stock-counts.show', $c) }}">{{ $c->number }}</a></td>
                            <td data-label="Almacén"><div class="erp-cell-main">{{ $c->warehouse->name }}</div></td>
                            <td data-label="Inicio">{{ $c->started_at?->format('d/m/Y') }}</td>
                            <td data-label="Avance">
                                <x-erp.progress :value="$pct" />
                                <div class="erp-cell-sub">{{ $c->counted_lines_count }} / {{ $c->lines_count }} partidas</div>
                            </td>
                            <td data-label="Diferencias" class="is-num">
                                @if ($c->counted_lines_count)
                                    <span class="{{ $c->diff_lines_count ? 'wh-neg' : 'wh-muted' }}">{{ $c->diff_lines_count }}</span>
                                @else <span class="wh-muted">—</span> @endif
                            </td>
                            <td data-label="Estado"><x-erp.chip :tone="$tones[$c->status] ?? 'neu'" :label="$statusLabels[$c->status] ?? $c->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-erp.empty title="Sin conteos" text="No hay conteos con estos filtros." style="border:0;" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <span>{{ $counts->total() }} conteos</span>
            <div>{{ $counts->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
