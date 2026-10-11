@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-projects.css'])
@endpush
@section('title')
    {{ $view === 'board' ? 'Tablero de proyectos' : 'Proyectos' }} - Admin
@endsection
@section('content')
@php
    use App\Models\Project;
    $statuses = Project::STATUSES;
    $q = request()->query();
    $url = fn (array $extra = [], array $except = []) => route('admin.projects.index', array_filter(
        array_merge(collect($q)->except(array_merge($except, ['page']))->all(), $extra), fn ($v) => $v !== null && $v !== ''));

    // Chips de filtros activos (cada uno quita su filtro).
    $chips = [];
    if (request('search')) $chips[] = ['label' => 'Búsqueda: ' . request('search'), 'url' => $url([], ['search'])];
    if (request('status') && isset($statuses[request('status')])) $chips[] = ['label' => 'Estado: ' . $statuses[request('status')], 'url' => $url([], ['status'])];
    if ($filterCustomer) $chips[] = ['label' => 'Cliente: ' . trim($filterCustomer->first_name . ' ' . $filterCustomer->last_name), 'url' => $url([], ['customer_id'])];
    if (request('manager_id')) {
        $m = $managers->firstWhere('id', (int) request('manager_id'));
        $chips[] = ['label' => 'Responsable: ' . ($m ? $m->first_name . ' ' . $m->last_name : '—'), 'url' => $url([], ['manager_id'])];
    }
    if (request('due') === 'vencido') $chips[] = ['label' => 'Fecha límite vencida', 'url' => $url([], ['due'])];

    // Vistas guardadas (píldoras).
    $mine = (string) auth()->id();
    $hasFilter = request()->hasAny(['status', 'manager_id', 'due']);
    $views = [
        ['label' => 'Todos', 'url' => $url([], ['status', 'manager_id', 'due']), 'active' => !$hasFilter],
        ['label' => 'En ejecución', 'url' => $url(['status' => 'en_ejecucion'], ['manager_id', 'due']),
            'active' => request('status') === 'en_ejecucion' && !request('manager_id') && !request('due')],
        ['label' => 'Mis proyectos', 'url' => $url(['manager_id' => $mine], ['status', 'due']),
            'active' => request('manager_id') === $mine && !request('status') && !request('due')],
        ['label' => 'Pausados', 'url' => $url(['status' => 'pausado'], ['manager_id', 'due']),
            'active' => request('status') === 'pausado' && !request('manager_id') && !request('due')],
        ['label' => 'Vencidos', 'url' => $url(['due' => 'vencido'], ['status', 'manager_id']),
            'active' => request('due') === 'vencido' && !request('status') && !request('manager_id')],
    ];

    $dueHint = function ($p) {
        $d = $p->daysToDue();
        if ($d === null || $p->status === 'cerrado') return null;
        return $d < 0 ? 'Vencido hace ' . abs($d) . ' d' : ($d === 0 ? 'Vence hoy' : 'Faltan ' . $d . ' d');
    };
    $fmtUser = fn ($u) => $u ? trim($u->first_name . ' ' . $u->last_name) : '—';
    $isBoard = $view === 'board';
@endphp
<div class="erp-page prj-page">

    <x-erp.page-header
        :title="$isBoard ? 'Tablero de proyectos' : 'Proyectos'"
        :code="$isBoard ? 'PRJ1' : 'PRJ2'"
        :desc="$isBoard ? 'Estado de cada proyecto con semáforo de fecha límite.' : 'Levantamiento, fases, responsables y avance de proyectos de instalación y servicio.'"
        :crumbs="[['Planeación', null], [$isBoard ? 'Tablero de proyectos' : 'Proyectos', null]]">
        <div class="prj-viewtoggle" role="group" aria-label="Vista">
            <a href="{{ $url(['view' => 'board']) }}" class="{{ $isBoard ? 'is-active' : '' }}">Tarjetas</a>
            <a href="{{ $url(['view' => 'list']) }}" class="{{ !$isBoard ? 'is-active' : '' }}">Lista</a>
        </div>
        @if (auth()->user()->hasPermission('projects', 'create'))
            <a href="{{ route('admin.projects.create') }}" class="erp-btn erp-btn--primary">+ Nuevo proyecto</a>
        @endif
    </x-erp.page-header>

    <div class="prj-kpis">
        <a class="prj-kpi" href="{{ $url(['status' => 'en_ejecucion'], ['due', 'manager_id']) }}"><i class="erp-dot erp-dot--info"></i><b>{{ $stats['active'] }}</b><span>Activos</span></a>
        <a class="prj-kpi" href="{{ $url(['due' => 'vencido'], ['status', 'manager_id']) }}"><i class="erp-dot erp-dot--bad"></i><b>{{ $stats['overdue'] }}</b><span>Con fecha vencida</span></a>
        <div class="prj-kpi"><i class="erp-dot erp-dot--warn"></i><b>{{ $stats['soon'] }}</b><span>Por vencer (≤ 3 sem.)</span></div>
        <a class="prj-kpi" href="{{ $url(['status' => 'cerrado'], ['due', 'manager_id']) }}"><i class="erp-dot erp-dot--ok"></i><b>{{ $stats['closed'] }}</b><span>Cerrados</span></a>
        <div class="prj-kpi prj-kpi--plain"><b>{{ $stats['total'] }}</b><span>Proyectos en total ({{ $stats['running'] }} en ejecución)</span></div>
    </div>

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="prj-toolbar-form">
                <input type="hidden" name="view" value="{{ $view }}">
                @if (request('customer_id')) <input type="hidden" name="customer_id" value="{{ request('customer_id') }}"> @endif
                <div class="erp-search">
                    <input type="search" name="search" placeholder="Buscar por folio, nombre o cliente…" value="{{ request('search') }}" aria-label="Buscar proyectos">
                </div>
                <select name="status" class="erp-select" onchange="this.form.submit()" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach ($statuses as $k => $label)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="manager_id" class="erp-select" onchange="this.form.submit()" aria-label="Responsable">
                    <option value="">Todos los responsables</option>
                    @foreach ($managers as $m)
                        <option value="{{ $m->id }}" @selected((int) request('manager_id') === $m->id)>{{ $m->first_name }} {{ $m->last_name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @unless ($isBoard)
            <div class="erp-table-wrap">
                <table class="erp-table prj-table">
                    <thead>
                        <tr>
                            <th>Folio</th><th>Proyecto / cliente</th><th>Persona a cargo</th><th>Fase actual</th>
                            <th>Avance</th><th>Fecha límite</th><th>Estado</th><th class="is-num">Monto (MXN)</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($projects as $p)
                        <tr>
                            <td class="erp-cell-folio"><a href="{{ route('admin.projects.show', $p) }}">{{ $p->project_number }}</a></td>
                            <td>
                                <div class="erp-cell-main"><a href="{{ route('admin.projects.show', $p) }}" class="prj-plain-link">{{ $p->name }}</a></div>
                                <div class="erp-cell-sub">{{ $p->display_customer }}</div>
                            </td>
                            <td>{{ $fmtUser($p->manager) }}</td>
                            <td>{{ $p->currentPhaseLabel() ?? '—' }}</td>
                            <td><x-erp.progress :value="$progress[$p->id] ?? 0" /></td>
                            <td>
                                @if ($p->due_date)
                                    <x-erp.due :date="$p->due_date" :closed="$p->status === 'cerrado'" />
                                    @if ($dueHint($p))<div class="erp-cell-sub">{{ $dueHint($p) }}</div>@endif
                                @else — @endif
                            </td>
                            <td><x-erp.status :value="$p->status" :label="Project::statusLabel($p->status)" /></td>
                            <td class="is-num">{{ ($amt = $p->quotesTotalMxn()) > 0 ? '$' . number_format($amt, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-erp.empty title="No hay proyectos que coincidan" text="Ajusta los filtros o crea un proyecto nuevo." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <x-slot:footer>
                <span>{{ $projects->total() }} {{ $projects->total() === 1 ? 'proyecto' : 'proyectos' }}</span>
                @if ($projects->hasPages())<div>{{ $projects->links() }}</div>@endif
            </x-slot:footer>
        @endunless
    </x-erp.worklist>

    @if ($isBoard)
        <div class="erp-board prj-board" aria-label="Tablero de proyectos por estado">
            @foreach ($statuses as $key => $label)
                @php $tone = \App\Support\ErpUi::tone($key); @endphp
                <section class="erp-col">
                    <div class="erp-col-head">
                        <span class="prj-col-title"><i class="erp-dot erp-dot--{{ $tone }}"></i>{{ $label }}</span>
                        <span class="erp-col-count">{{ $columns[$key]->count() }}</span>
                    </div>
                    @forelse ($columns[$key] as $p)
                        @php $pct = $progress[$p->id] ?? 0; $tag = $p->dueTone() === 'bad' ? 'Vencido' : ($p->dueTone() === 'warn' ? 'Por vencer' : 'En tiempo'); @endphp
                        <a href="{{ route('admin.projects.show', $p) }}" class="erp-kcard">
                            <div class="erp-kcard-top"><span>{{ $p->project_number }}</span><span>{{ $p->currentPhaseLabel() }}</span></div>
                            <div class="erp-kcard-title">{{ $p->name }}</div>
                            <div class="erp-cell-sub">{{ $p->display_customer }}</div>
                            <x-erp.progress :value="$pct" class="prj-kcard-progress" />
                            <div class="erp-kcard-foot">
                                <span class="prj-kcard-resp">{{ $fmtUser($p->manager) }}</span>
                                @if ($p->due_date)
                                    <x-erp.due :date="$p->due_date" :closed="$p->status === 'cerrado'" :title="$p->status === 'cerrado' ? 'Cerrado' : $tag" />
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="prj-col-empty">Sin proyectos</p>
                    @endforelse
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
