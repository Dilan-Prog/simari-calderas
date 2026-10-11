@extends('admin.layouts.master')

@section('title', 'MRP · Materiales - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-mrp.css'])
@endpush

@php
    $canCreate = auth()->user()->isAdmin() || auth()->user()->hasPermission('mrp', 'create');
@endphp

@section('content')
<div class="erp-page mrp-page">
    <div class="erp-page-head">
        <div>
            <h1 class="erp-title">MRP · Requerimientos de materiales</h1>
            <p class="erp-subtitle">Qué se necesita, qué hay en existencia y qué falta comprar para pedidos, proyectos y servicios.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.mrp.create') }}" class="mrp-btn mrp-btn-primary">+ Nuevo requerimiento</a>
        @endif
    </div>

    <form method="GET" class="mrp-filters">
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Buscar por folio…" class="mrp-input">
        <select name="status" class="mrp-input">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\MaterialRequirement::STATUSES as $s)
                <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ \App\Models\MaterialRequirement::statusLabel($s) }}</option>
            @endforeach
        </select>
        <select name="origin" class="mrp-input">
            <option value="">Todos los orígenes</option>
            @foreach (['sales_order' => 'Pedido', 'project' => 'Proyecto', 'technical_service' => 'Servicio técnico', 'production_order' => 'Orden de producción', 'manual' => 'Manual'] as $k => $l)
                <option value="{{ $k }}" @selected($filters['origin'] === $k)>{{ $l }}</option>
            @endforeach
        </select>
        @if ($projects->isNotEmpty())
            <select name="project_id" class="mrp-input">
                <option value="">Todos los proyectos</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" @selected($filters['project_id'] === (string) $p->id)>{{ $p->project_number }} · {{ \Illuminate\Support\Str::limit($p->name, 28) }}</option>
                @endforeach
            </select>
        @endif
        <input type="date" name="from" value="{{ $filters['from'] }}" class="mrp-input" title="Creado desde">
        <input type="date" name="to" value="{{ $filters['to'] }}" class="mrp-input" title="Creado hasta">
        <label class="mrp-check"><input type="checkbox" name="shortfall" value="1" @checked($filters['shortfall'] === '1')> Solo con faltante</label>
        <button class="mrp-btn" type="submit">Filtrar</button>
    </form>

    @if (count($chips))
        <div class="mrp-chips">
            @foreach ($chips as $c)
                <a href="{{ $c['url'] }}" class="mrp-chip" title="Quitar filtro">{{ $c['label'] }}: <strong>{{ $c['value'] }}</strong> ×</a>
            @endforeach
            <a href="{{ route('admin.mrp.index') }}" class="mrp-chip-clear">Limpiar todo</a>
        </div>
    @endif

    <div class="mrp-card">
        <div class="mrp-table-scroll">
            <table class="mrp-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Origen</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th class="num">Líneas</th>
                        <th class="num">Con faltante</th>
                        <th>Requerido para</th>
                        <th>Creado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reqs as $r)
                        <tr>
                            <td><a href="{{ route('admin.mrp.show', $r) }}" class="mrp-link">{{ $r->number }}</a></td>
                            <td>
                                {{ $r->originLabel() }}
                                @if ($r->originNumber())<div class="mrp-muted">{{ $r->originNumber() }}</div>@endif
                            </td>
                            <td>{{ $r->customer ? trim($r->customer->first_name . ' ' . $r->customer->last_name) : '—' }}</td>
                            <td><span class="erp-chip erp-tone-{{ \App\Models\MaterialRequirement::statusTone($r->status) }}">{{ \App\Models\MaterialRequirement::statusLabel($r->status) }}</span></td>
                            <td class="num">{{ $r->lines_count }}</td>
                            <td class="num">
                                @if ($r->short_lines_count > 0)
                                    <span class="erp-tone-bad"><strong>{{ $r->short_lines_count }}</strong></span>
                                @else
                                    <span class="erp-tone-ok">0</span>
                                @endif
                            </td>
                            <td>{{ $r->needed_by_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $r->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="mrp-empty">Aún no hay requerimientos. Crea uno con «Nuevo requerimiento» o desde la ficha de un pedido.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $reqs->links('admin.components.pagination') }}</div>
</div>
@endsection
