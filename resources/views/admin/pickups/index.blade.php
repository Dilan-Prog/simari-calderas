@extends('admin.layouts.master')

@section('title', 'Recolecciones - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $statusMeta = \App\Support\PickupStatus::META;
    $active = $filters['status'] ?? null;
    $withFilters = fn (array $over) => route('admin.pickups.index', array_filter(array_merge($filters, $over), fn ($v) => $v !== null && $v !== ''));
    $views = [['label' => 'Todas ' . $counts->sum(), 'url' => $withFilters(['status' => null]), 'active' => ! $active]];
    foreach ($statusMeta as $key => $m) {
        $views[] = ['label' => $m['label'] . ' ' . ($counts[$key] ?? 0), 'url' => $withFilters(['status' => $key]), 'active' => $active === $key];
    }

    $chips = [];
    if (! empty($filters['q'])) $chips[] = ['label' => 'Búsqueda: ' . $filters['q'], 'url' => $withFilters(['q' => null])];
    if (! empty($filters['type'])) $chips[] = ['label' => 'Tipo: ' . (\App\Support\PickupStatus::TYPES[$filters['type']] ?? $filters['type']), 'url' => $withFilters(['type' => null])];
    if (! empty($filters['driver_user_id'])) {
        $drv = $drivers->firstWhere('id', (int) $filters['driver_user_id']);
        $chips[] = ['label' => 'Chofer: ' . ($drv ? trim($drv->first_name . ' ' . $drv->last_name) : '—'), 'url' => $withFilters(['driver_user_id' => null])];
    }
    if (! empty($filters['date'])) $chips[] = ['label' => 'Fecha: ' . $filters['date'], 'url' => $withFilters(['date' => null])];
@endphp
<div class="erp-page">
    <x-erp.page-header title="Recolecciones" code="LOG2"
        desc="Pedidos, material, equipos o documentos por recolectar, con evidencia de foto y firma."
        :crumbs="[['Logística', null], ['Recolecciones', null]]">
        <a href="{{ route('admin.pickups.route') }}" class="erp-btn">Ruta del día</a>
        <a href="{{ route('admin.pickups.create') }}" class="erp-btn erp-btn--primary">+ Nueva recolección</a>
    </x-erp.page-header>

    @if (session('success')) <div class="lg-alert lg-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="lg-alert" role="alert">{{ session('error') }}</div> @endif

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="erp-toolbar" style="padding:0;border:0;flex:1;">
                @if ($active) <input type="hidden" name="status" value="{{ $active }}"> @endif
                <div class="erp-search"><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Folio, contacto o dirección…" aria-label="Buscar recolecciones"></div>
                <select name="type" class="erp-select" aria-label="Tipo" onchange="this.form.submit()">
                    <option value="">Todos los tipos</option>
                    @foreach (\App\Support\PickupStatus::TYPES as $k => $label)
                        <option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="driver_user_id" class="erp-select" aria-label="Chofer" onchange="this.form.submit()">
                    <option value="">Todos los choferes</option>
                    @foreach ($drivers as $d)
                        <option value="{{ $d->id }}" @selected((string) ($filters['driver_user_id'] ?? '') === (string) $d->id)>{{ $d->first_name }} {{ $d->last_name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" class="erp-select" aria-label="Fecha" value="{{ $filters['date'] ?? '' }}" onchange="this.form.submit()">
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @if ($pickups->isEmpty())
            <div style="padding:16px;"><x-erp.empty title="Sin recolecciones" text="No hay recolecciones con estos filtros.">
                <a href="{{ route('admin.pickups.create') }}" class="erp-btn erp-btn--primary">+ Nueva recolección</a>
            </x-erp.empty></div>
        @else
            <div class="erp-table-wrap">
                <table class="erp-table lg-responsive">
                    <thead><tr><th>Folio</th><th>Tipo</th><th>Qué se recolecta</th><th>Programada</th><th>Chofer / paquetería</th><th>Evidencia</th><th>Estatus</th></tr></thead>
                    <tbody>
                    @foreach ($pickups as $p)
                        @php
                            $closed = ! \App\Support\PickupStatus::isOpen($p->status);
                            $hasSign = ! empty($p->signature_data);
                        @endphp
                        <tr data-href="{{ route('admin.pickups.show', $p) }}" style="cursor:pointer;">
                            <td class="erp-cell-folio"><a href="{{ route('admin.pickups.show', $p) }}">{{ $p->number }}</a></td>
                            <td>{{ \App\Support\PickupStatus::TYPES[$p->type] ?? $p->type }}</td>
                            <td>
                                <div class="erp-cell-main">{{ $p->counterpartLabel() }}</div>
                                <div class="erp-cell-sub">{{ \Illuminate\Support\Str::limit($p->address, 70) }}</div>
                            </td>
                            <td>
                                @if ($p->scheduled_date)
                                    <x-erp.due :date="$p->scheduled_date" :closed="$closed" />
                                    @if ($p->time_window)<div class="erp-cell-sub">{{ $p->time_window }}</div>@endif
                                @else
                                    <x-erp.chip tone="warn" label="Por programar" />
                                @endif
                            </td>
                            <td>{{ $p->driver ? trim($p->driver->first_name . ' ' . $p->driver->last_name) : ($p->carrier->name ?? 'Sin asignar') }}</td>
                            <td>
                                @if ($p->evidences_count && $hasSign)
                                    <x-erp.chip tone="ok" label="Foto + firma" />
                                @elseif ($p->evidences_count || $hasSign)
                                    <x-erp.chip tone="warn" :label="$p->evidences_count ? 'Solo foto' : 'Solo firma'" />
                                @else
                                    <span class="lg-muted">Pendiente</span>
                                @endif
                            </td>
                            <td><x-erp.chip :tone="\App\Support\PickupStatus::chipTone($p->status)" :label="$p->statusLabel()" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <x-slot:footer>
            <span>{{ $pickups->total() }} recolecciones</span>
            <div>{{ $pickups->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
