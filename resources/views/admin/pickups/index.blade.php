@extends('admin.layouts.master')

@section('title', 'Recolecciones - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-logistics.css'])
@endpush

@section('content')
@php
    $statusMeta = \App\Support\PickupStatus::META;
    $active = $filters['status'] ?? null;
    $chipUrl = fn ($s) => route('admin.pickups.index', array_filter(array_merge($filters, ['status' => $s])));
@endphp
<div class="lg-page">
    <div class="lg-head">
        <div>
            <h1>Recolecciones</h1>
            <p>Programación de recolecciones de pedidos, material u otros, con chofer o paquetería y evidencia.</p>
        </div>
        <div class="lg-actions">
            <a href="{{ route('admin.pickups.route') }}" class="lg-btn">Ruta del día</a>
            <a href="{{ route('admin.pickups.create') }}" class="lg-btn lg-btn-primary">Nueva recolección</a>
        </div>
    </div>

    <div class="lg-chips">
        <a href="{{ $chipUrl(null) }}" class="lg-chip {{ $active ? '' : 'is-active' }}">Todas <b>{{ $counts->sum() }}</b></a>
        @foreach ($statusMeta as $key => $m)
            <a href="{{ $chipUrl($key) }}" class="lg-chip {{ $active === $key ? 'is-active' : '' }}">{{ $m['label'] }} <b>{{ $counts[$key] ?? 0 }}</b></a>
        @endforeach
    </div>

    <form method="GET" class="lg-filters">
        @if ($active) <input type="hidden" name="status" value="{{ $active }}"> @endif
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Folio, contacto o dirección…">
        <select name="type">
            <option value="">Todos los tipos</option>
            @foreach (\App\Support\PickupStatus::TYPES as $k => $label)
                <option value="{{ $k }}" @selected(($filters['type'] ?? '') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="driver_user_id">
            <option value="">Todos los choferes</option>
            @foreach ($drivers as $d)
                <option value="{{ $d->id }}" @selected((string) ($filters['driver_user_id'] ?? '') === (string) $d->id)>{{ $d->first_name }} {{ $d->last_name }}</option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ $filters['date'] ?? '' }}">
        <button type="submit" class="lg-btn">Filtrar</button>
    </form>

    @if ($pickups->isEmpty())
        <div class="erp-empty">No hay recolecciones con estos filtros.</div>
    @else
        <table class="lg-table lg-responsive">
            <thead><tr><th>Folio</th><th>Tipo</th><th>Cliente / contacto</th><th>Programada</th><th>Asignado a</th><th>Estatus</th></tr></thead>
            <tbody>
            @foreach ($pickups as $p)
                <tr class="is-link" data-href="{{ route('admin.pickups.show', $p) }}">
                    <td><strong>{{ $p->number }}</strong></td>
                    <td>{{ \App\Support\PickupStatus::TYPES[$p->type] ?? $p->type }}</td>
                    <td>{{ $p->counterpartLabel() }}<div class="lg-muted">{{ \Illuminate\Support\Str::limit($p->address, 60) }}</div></td>
                    <td>{{ $p->scheduled_date?->format('d/m/Y') ?? '—' }}<div class="lg-muted">{{ $p->time_window }}</div></td>
                    <td>{{ $p->driver ? trim($p->driver->first_name . ' ' . $p->driver->last_name) : ($p->carrier->name ?? 'Sin asignar') }}</td>
                    <td><span class="erp-chip erp-tone-{{ \App\Support\PickupStatus::tone($p->status) }}">{{ $p->statusLabel() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:14px;">{{ $pickups->links('admin.components.pagination') }}</div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/admin/erp-logistics.js'])
@endpush
