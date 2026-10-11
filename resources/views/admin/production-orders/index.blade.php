@extends('admin.layouts.master')

@section('title', 'Órdenes de producción - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-production.css'])
@endpush

@php
    $canCreate = auth()->user()->isAdmin() || auth()->user()->hasPermission('production-orders', 'create');
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
@endphp

@section('content')
<div class="erp-page prd-page">
    <div class="erp-page-head">
        <div>
            <h1 class="erp-title">Órdenes de producción</h1>
            <p class="erp-subtitle">Fabricación propia: reserva de componentes, consumo, producto terminado y costo real contra estándar.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.production-orders.create') }}" class="prd-btn prd-btn-primary">+ Nueva orden</a>
        @endif
    </div>

    @if (session('success'))<div class="prd-ok">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="prd-alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="GET" class="prd-filters">
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Folio…" class="prd-input">
        <input type="search" name="product" value="{{ $filters['product'] }}" placeholder="Producto o SKU…" class="prd-input">
        <select name="status" class="prd-input">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\ProductionOrder::STATUSES as $s)
                <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ \App\Models\ProductionOrder::statusLabel($s) }}</option>
            @endforeach
        </select>
        @if ($projects->isNotEmpty())
            <select name="project_id" class="prd-input">
                <option value="">Todos los proyectos</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($filters['project_id'] === (string) $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 28) }}</option>
                @endforeach
            </select>
        @endif
        <label class="prd-check"><input type="checkbox" name="overdue" value="1" @checked($filters['overdue'] === '1')> Solo vencidas</label>
        <button class="prd-btn" type="submit">Filtrar</button>
    </form>

    @if (count($chips))
        <div class="prd-chips">
            @foreach ($chips as $c)
                <a href="{{ $c['url'] }}" class="prd-chip" title="Quitar filtro">{{ $c['label'] }}: <strong>{{ $c['value'] }}</strong> ×</a>
            @endforeach
            <a href="{{ route('admin.production-orders.index') }}" class="prd-chip-clear">Limpiar todo</a>
        </div>
    @endif

    <div class="prd-card">
        <div class="prd-table-scroll">
            <table class="prd-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Producto</th>
                        <th class="num">Plan / producido</th>
                        <th>Estado</th>
                        <th>Avance</th>
                        <th>Fecha límite</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $o)
                        @php $days = $o->daysToDue(); @endphp
                        <tr>
                            <td><a href="{{ route('admin.production-orders.show', $o) }}" class="prd-link">{{ $o->number }}</a></td>
                            <td>
                                {{ $o->product->name ?? '—' }}
                                <div class="prd-muted">{{ $o->product->sku ?? '' }}</div>
                            </td>
                            <td class="num">{{ $n($o->quantity_planned) }} / {{ $n($o->quantity_produced) }}</td>
                            <td><span class="erp-chip erp-tone-{{ \App\Models\ProductionOrder::statusTone($o->status) }}">{{ \App\Models\ProductionOrder::statusLabel($o->status) }}</span></td>
                            <td>
                                <div class="prd-bar" title="{{ $o->progress }}%"><span style="width: {{ $o->progress }}%"></span></div>
                            </td>
                            <td>
                                @if ($o->due_date)
                                    <span class="prd-dot prd-dot-{{ $o->dueTone() }}"></span>
                                    {{ $o->due_date->format('d/m/Y') }}
                                    @if ($o->isOpen() && $days !== null)
                                        <div class="prd-muted">{{ $days < 0 ? 'Vencida hace ' . abs($days) . ' d' : ($days === 0 ? 'Vence hoy' : 'En ' . $days . ' d') }}</div>
                                    @endif
                                @else
                                    <span class="prd-muted">Sin fecha</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="prd-empty">Aún no hay órdenes de producción.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $orders->links('admin.components.pagination') }}</div>
</div>
@endsection
