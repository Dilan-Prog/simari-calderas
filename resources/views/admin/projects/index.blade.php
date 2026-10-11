@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-projects.css'])
@endpush
@section('title')
    Proyectos - Admin
@endsection
@section('content')
@php
    $statuses = \App\Models\Project::STATUSES;
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'remove' => 'search'];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estado: ' . $statuses[request('status')], 'remove' => 'status'];
    if ($filterCustomer) $chips[] = ['label' => 'Cliente: ' . trim($filterCustomer->first_name . ' ' . $filterCustomer->last_name), 'remove' => 'customer_id'];
    if (request('manager_id')) { $m = $managers->firstWhere('id', (int) request('manager_id')); $chips[] = ['label' => 'Responsable: ' . ($m ? $m->first_name . ' ' . $m->last_name : '—'), 'remove' => 'manager_id']; }
    if (request('due') === 'vencido') $chips[] = ['label' => 'Fecha límite vencida', 'remove' => 'due'];
    $without = fn ($key) => route('admin.projects.index', collect(request()->query())->except([$key, 'page'])->all());
    $dueLabel = function ($p) {
        $d = $p->daysToDue();
        if ($d === null || $p->status === 'cerrado') return null;
        return $d < 0 ? 'Vencido hace ' . abs($d) . ' d' : ($d === 0 ? 'Vence hoy' : 'Faltan ' . $d . ' d');
    };
@endphp
<div class="container user-manager">
<section class="clients-manager-section prj-page">

    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">Panel de Control &gt; <strong>Proyectos</strong></p>
            <h1 class="erp-title">Proyectos</h1>
            <p class="erp-subtitle">Planeación, fases y documentos asociados</p>
        </div>
        <div class="prj-head-actions">
            <div class="prj-viewtoggle" role="group" aria-label="Vista">
                <a href="{{ route('admin.projects.index', array_merge(request()->query(), ['view' => 'list'])) }}" class="{{ $view === 'list' ? 'is-active' : '' }}">Lista</a>
                <a href="{{ route('admin.projects.index', array_merge(request()->query(), ['view' => 'board'])) }}" class="{{ $view === 'board' ? 'is-active' : '' }}">Tablero</a>
            </div>
            @if (auth()->user()->hasPermission('projects', 'create'))
                <a href="{{ route('admin.projects.create') }}" class="prj-btn prj-btn-primary">+ Nuevo proyecto</a>
            @endif
        </div>
    </div>

    <div class="prj-stats">
        <div class="prj-stat"><strong>{{ $stats['total'] }}</strong><span>Proyectos</span></div>
        <div class="prj-stat"><strong>{{ $stats['running'] }}</strong><span>En ejecución</span></div>
        <a class="prj-stat {{ $stats['overdue'] ? 'is-bad' : '' }}" href="{{ route('admin.projects.index', ['due' => 'vencido']) }}"><strong>{{ $stats['overdue'] }}</strong><span>Con fecha vencida</span></a>
        <div class="prj-stat"><strong>{{ $stats['closed'] }}</strong><span>Cerrados</span></div>
    </div>

    <form method="GET" class="prj-filters">
        <input type="hidden" name="view" value="{{ $view }}">
        @if (request('customer_id')) <input type="hidden" name="customer_id" value="{{ request('customer_id') }}"> @endif
        <input type="text" name="search" class="users-manager-input" placeholder="Buscar por folio, nombre o cliente..." value="{{ request('search') }}">
        <select name="status" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach ($statuses as $k => $label)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="manager_id" class="users-manager-input" onchange="this.form.submit()">
            <option value="">Todos los responsables</option>
            @foreach ($managers as $m)
                <option value="{{ $m->id }}" @selected((int) request('manager_id') === $m->id)>{{ $m->first_name }} {{ $m->last_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="prj-btn">Filtrar</button>
    </form>

    @if (count($chips))
        <div class="prj-chips">
            @foreach ($chips as $c)
                <a href="{{ $without($c['remove']) }}" class="prj-chip">{{ $c['label'] }} <span aria-hidden="true">×</span></a>
            @endforeach
            <a href="{{ route('admin.projects.index', ['view' => $view]) }}" class="prj-chip-clear">Limpiar todo</a>
        </div>
    @endif

    @if ($view === 'board')
        <div class="prj-board">
            @foreach ($statuses as $key => $label)
                <div class="prj-col">
                    <div class="prj-col-head"><span>{{ $label }}</span><em>{{ $columns[$key]->count() }}</em></div>
                    @forelse ($columns[$key] as $p)
                        <a href="{{ route('admin.projects.show', $p) }}" class="prj-card">
                            <span class="prj-card-num">{{ $p->project_number }}</span>
                            <strong>{{ $p->name }}</strong>
                            <span class="prj-card-sub">{{ $p->display_customer }}</span>
                            @if ($p->due_date)
                                <span class="prj-due prj-due-{{ $p->dueTone() }}"><i></i>{{ $p->due_date->format('d/m/Y') }}@if ($dueLabel($p)) · {{ $dueLabel($p) }}@endif</span>
                            @endif
                        </a>
                    @empty
                        <p class="prj-col-empty">Sin proyectos</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    @else
        <main class="table-container-clients-manager">
            <div class="table-scroll">
                <table class="clients-manager-table prj-table">
                    <thead>
                        <tr>
                            <th>FOLIO</th><th>PROYECTO</th><th>CLIENTE</th><th>RESPONSABLE</th>
                            <th>ESTADO</th><th>FECHA LÍMITE</th><th>FASES</th><th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($projects as $p)
                        <tr>
                            <td><a href="{{ route('admin.projects.show', $p) }}"><strong>{{ $p->project_number }}</strong></a></td>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->display_customer }}</td>
                            <td>{{ $p->manager ? $p->manager->first_name . ' ' . $p->manager->last_name : '—' }}</td>
                            <td><span class="erp-chip erp-tone-{{ \App\Models\Project::statusTone($p->status) }}">{{ \App\Models\Project::statusLabel($p->status) }}</span></td>
                            <td>
                                @if ($p->due_date)
                                    <span class="prj-due prj-due-{{ $p->dueTone() }}"><i></i>{{ $p->due_date->format('d/m/Y') }}</span>
                                    @if ($dueLabel($p)) <small class="prj-muted">{{ $dueLabel($p) }}</small> @endif
                                @else — @endif
                            </td>
                            <td>{{ $p->phases_count }}</td>
                            <td><a href="{{ route('admin.projects.show', $p) }}" class="prj-link">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="prj-empty">No hay proyectos que coincidan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </main>
        <div style="margin-top:16px;">{{ $projects->links() }}</div>
    @endif
</section>
</div>
@endsection
