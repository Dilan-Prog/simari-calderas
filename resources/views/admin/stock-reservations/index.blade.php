@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-warehouse.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-warehouse.js'])
@endpush
@section('title')
    Reservas por proyecto - Admin
@endsection
@section('content')
@php
    $canEdit = auth()->user()->isAdmin() || auth()->user()->hasPermission('inventory', 'edit');
    $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $without = fn ($key) => route('admin.stock-reservations.index', collect(request()->query())->except([$key])->all());
    $withProject = fn ($pid) => route('admin.stock-reservations.index', collect(request()->query())->except(['project_id'])->when($pid, fn ($c) => $c->put('project_id', $pid))->all());
    $curProject = $filters['project_id'] ?? null;
    $views = [['label' => 'Todas', 'url' => $withProject(null), 'active' => ! $curProject]];
    foreach ($projects as $p) {
        $views[] = ['label' => $p->project_number, 'url' => $withProject($p->id), 'active' => (string) $curProject === (string) $p->id];
    }
    $chips = [];
    if (! empty($filters['search'])) $chips[] = ['label' => 'Material: ' . $filters['search'], 'url' => $without('search')];
    if (! empty($filters['warehouse_id'])) $chips[] = ['label' => 'Almacén: ' . ($warehouses->firstWhere('id', (int) $filters['warehouse_id'])?->name ?? '—'), 'url' => $without('warehouse_id')];
@endphp
<div class="erp-page">
    <x-erp.page-header title="Reservas por proyecto" code="ALM3"
        desc="Material apartado para proyectos; no disponible para venta ni otros proyectos."
        :crumbs="[['Almacén', null], ['Reservas por proyecto', null]]">
        <a href="{{ route('admin.warehouse-locations.index') }}" class="erp-btn">Ubicaciones</a>
        <a href="{{ route('admin.mrp.index') }}" class="erp-btn">Planeación de materiales</a>
    </x-erp.page-header>

    @if (session('error')) <div class="wh-alert wh-alert-error" role="alert">{{ session('error') }}</div> @endif
    @if (session('success')) <div class="wh-alert wh-alert-ok" role="status">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="wh-alert wh-alert-error" role="alert">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div> @endif

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="erp-toolbar" style="padding:0;border:0;flex:1;">
                @if ($curProject) <input type="hidden" name="project_id" value="{{ $curProject }}"> @endif
                <div class="erp-search"><input type="search" name="search" placeholder="Buscar material o SKU…" value="{{ $filters['search'] ?? '' }}" aria-label="Buscar material o SKU"></div>
                <select name="warehouse_id" class="erp-select" aria-label="Almacén" onchange="this.form.submit()">
                    <option value="">Todos los almacenes</option>
                    @foreach ($warehouses as $w) <option value="{{ $w->id }}" @selected((string) ($filters['warehouse_id'] ?? '') === (string) $w->id)>{{ $w->name }}</option> @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @if ($canEdit)
            <x-erp.batch-bar noun="reservas">
                <button type="button" class="erp-btn" data-release-selected>Liberar reserva</button>
            </x-erp.batch-bar>
            <form method="POST" action="{{ route('admin.stock-reservations.release') }}" id="release-form" data-confirm="¿Liberar las reservas seleccionadas? El material volverá a estar disponible.">@csrf</form>
        @endif

        <div class="erp-table-wrap">
            <table class="erp-table wh-stack">
                <thead><tr>
                    @if ($canEdit)<th style="width:34px;"><input type="checkbox" data-erp-select-all aria-label="Seleccionar todas"></th>@endif
                    <th>Material</th><th class="is-right">Cantidad</th><th>Almacén</th><th>Requerimiento</th><th>Requerida</th><th>Estado</th>@if ($canEdit)<th></th>@endif
                </tr></thead>
                <tbody>
                    @forelse ($groups as $g)
                        <tr class="wh-group-row">
                            <td colspan="{{ $canEdit ? 8 : 6 }}">
                                {{ $g['label'] }}
                                <small>{{ $g['products']->count() }} material(es) · {{ $qty($g['total']) }} uds reservadas</small>
                            </td>
                        </tr>
                        @foreach ($g['products'] as $rows)
                            @foreach ($rows as $r)
                                <tr>
                                    @if ($canEdit)<td data-label=""><input type="checkbox" class="erp-row-check" value="{{ $r->id }}" aria-label="Seleccionar reserva de {{ $r->product->name ?? 'material' }}"></td>@endif
                                    <td data-label="Material">
                                        <div class="erp-cell-main">{{ $r->product->name ?? '—' }}</div>
                                        <div class="erp-cell-sub">{{ $r->product->sku ?? '' }}</div>
                                    </td>
                                    <td data-label="Cantidad" class="is-num">{{ $qty($r->quantity) }}</td>
                                    <td data-label="Almacén">{{ $r->warehouse->name ?? 'Cualquiera' }}</td>
                                    <td data-label="Requerimiento">
                                        @if ($r->requirement)
                                            <a href="{{ route('admin.mrp.show', $r->requirement) }}">{{ $r->requirement->number }}</a>
                                        @else <span class="wh-muted">{{ $r->source_type ? \Illuminate\Support\Str::headline($r->source_type) : '—' }}</span> @endif
                                    </td>
                                    <td data-label="Requerida">
                                        @if ($r->requirement?->needed_by_date)<x-erp.due :date="$r->requirement->needed_by_date" />@else <span class="wh-muted">—</span> @endif
                                    </td>
                                    <td data-label="Estado"><x-erp.chip tone="ok" label="Reservado" /></td>
                                    @if ($canEdit)
                                        <td>
                                            <form method="POST" action="{{ route('admin.stock-reservations.release') }}" class="wh-inline" data-confirm="¿Liberar esta reserva? El material volverá a estar disponible.">
                                                @csrf <input type="hidden" name="ids[]" value="{{ $r->id }}">
                                                <button type="submit" class="erp-btn erp-btn--sm">Liberar</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr><td colspan="{{ $canEdit ? 8 : 6 }}"><x-erp.empty title="Sin reservas activas" text="Las reservas se generan desde la Planeación de materiales (MRP) al reservar un requerimiento." style="border:0;" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <span>{{ $total }} reservas activas</span>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
