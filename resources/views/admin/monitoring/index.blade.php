@extends('admin.layouts.master')
@section('title', 'Monitoreo de Clientes - Admin')
@push('styles')
    @include('admin.monitoring._styles')
@endpush
@section('content')
@php
    use App\Http\Controllers\Backend\MonitoringController;
    use App\Models\MonitoringProgram;
    use Illuminate\Support\Carbon;

    $canCreate = auth()->user()->hasPermission('monitoring', 'create');
    $q = request()->query();
    $url = fn (array $extra = [], array $except = []) => route('admin.monitoring.index', array_filter(
        array_merge(collect($q)->except(array_merge($except, ['page']))->all(), $extra), fn ($v) => $v !== null && $v !== ''));

    $views = [['label' => 'Todos', 'url' => $url([], ['status']), 'active' => $filters['status'] === '']];
    foreach ($statuses as $key => $label) {
        $views[] = ['label' => $label . 's', 'url' => $url(['status' => $key]), 'active' => $filters['status'] === $key];
    }

    $chips = [];
    if ($filters['q'] !== '') $chips[] = ['label' => 'Búsqueda: ' . $filters['q'], 'url' => $url([], ['q'])];
    if ($filters['status'] !== '' && isset($statuses[$filters['status']])) $chips[] = ['label' => 'Estado: ' . $statuses[$filters['status']], 'url' => $url([], ['status'])];
@endphp
<div class="erp-page mon-page">

    <x-erp.page-header
        title="Monitoreo de Clientes"
        desc="Programas de seguimiento del agua y la dosificación de químicos por cliente y equipo."
        :crumbs="[['Producción', null], ['Monitoreo de Clientes', null]]">
        <a href="{{ route('admin.monitoring.alerts') }}" class="erp-btn">Alertas fuera de rango</a>
        @if ($canCreate)
            <a href="{{ route('admin.monitoring.create') }}" class="erp-btn erp-btn--primary">+ Nuevo programa</a>
        @endif
    </x-erp.page-header>

    <div class="mon-kpis">
        <a class="mon-kpi" href="{{ $url(['status' => 'active']) }}">
            <span><i class="erp-dot erp-dot--info"></i>Programas activos</span>
            <b>{{ $kpis['active_programs'] }}</b>
        </a>
        <div class="mon-kpi">
            <span><i class="erp-dot erp-dot--ok"></i>Registros esta semana</span>
            <b>{{ $kpis['entries_week'] }}</b>
            <small>{{ now()->startOfWeek()->format('d/m') }} al {{ now()->endOfWeek()->format('d/m') }}</small>
        </div>
        <a class="mon-kpi" href="{{ route('admin.monitoring.alerts') }}">
            <span><i class="erp-dot erp-dot--{{ $kpis['out_of_range'] > 0 ? 'bad' : 'ok' }}"></i>Lecturas fuera de rango</span>
            <b>{{ $kpis['out_of_range'] }}</b>
            <small>Últimos 30 días</small>
        </a>
        <div class="mon-kpi">
            <span><i class="erp-dot erp-dot--acc"></i>Clientes monitoreados</span>
            <b>{{ $kpis['customers'] }}</b>
            <small>Con un programa activo</small>
        </div>
    </div>

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="mon-toolbar-form">
                <div class="erp-search">
                    <input type="search" name="q" placeholder="Buscar por cliente, empresa o nombre del programa…" value="{{ $filters['q'] }}" aria-label="Buscar programas">
                </div>
                <select name="status" class="erp-select" onchange="this.form.submit()" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    @foreach ($statuses as $k => $label)
                        <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        <div class="erp-table-wrap">
            <table class="erp-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Programa</th>
                        <th class="mon-num">Equipos</th>
                        <th>Avance</th>
                        <th>Estado</th>
                        <th class="mon-num">Cumplimiento</th>
                        <th class="mon-num">Fuera de rango</th>
                        <th>Último registro</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($programs as $p)
                    @php
                        $s = $stats[$p->id];
                        $week = $p->currentWeek();
                        $today = now()->startOfDay();
                        if ($week) {
                            $weekText = 'Semana ' . $week . ' de ' . $p->weeks;
                        } elseif ($today->lt($p->start_date)) {
                            $weekText = 'Inicia el ' . $p->start_date->format('d/m/Y');
                        } else {
                            $weekText = 'Finalizó el ' . $p->endDate()->format('d/m/Y');
                        }
                        $tone = match ($p->status) { 'active' => 'ok', 'paused' => 'warn', default => 'neu' };
                        $comp = $s['compliance'];
                        $compTone = $comp === null ? 'neu' : ($comp >= 90 ? 'ok' : ($comp >= 70 ? 'warn' : 'bad'));
                    @endphp
                    <tr>
                        <td>
                            <div class="erp-cell-main">{{ MonitoringController::customerName($p->customer) }}</div>
                            @if ($p->customer?->company)<div class="erp-cell-sub">{{ $p->customer->company }}</div>@endif
                        </td>
                        <td>
                            <a href="{{ route('admin.monitoring.show', $p) }}" class="mon-link">{{ $p->name }}</a>
                            <div class="erp-cell-sub">{{ $p->start_date->format('d/m/Y') }} – {{ $p->endDate()->format('d/m/Y') }}</div>
                        </td>
                        <td class="mon-num">{{ $equipmentCounts[$p->customer_id] ?? 0 }}</td>
                        <td>{{ $weekText }}</td>
                        <td><x-erp.chip :tone="$tone" :label="$p->status_label" /></td>
                        <td class="mon-num">
                            @if ($comp === null) <span class="mon-muted">—</span>
                            @else <x-erp.chip :tone="$compTone" :label="$comp . '%'" /> @endif
                        </td>
                        <td class="mon-num">
                            @if ($s['out_of_range'] > 0) <span class="mon-bad">{{ $s['out_of_range'] }}</span> @else 0 @endif
                        </td>
                        <td>
                            @if ($s['last_entry_date'])
                                {{ Carbon::parse($s['last_entry_date'])->format('d/m/Y') }}
                                <div class="erp-cell-sub">{{ Carbon::parse($s['last_entry_date'])->diffForHumans() }}</div>
                            @else
                                <span class="mon-muted">Sin registros</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">
                        <x-erp.empty title="No hay programas de monitoreo" text="Crea un programa para un cliente, define los parámetros y químicos a seguir y el cliente podrá capturar sus lecturas desde su portal.">
                            @if ($canCreate)<a href="{{ route('admin.monitoring.create') }}" class="erp-btn erp-btn--primary">+ Nuevo programa</a>@endif
                        </x-erp.empty>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <span>{{ $programs->total() }} {{ $programs->total() === 1 ? 'programa' : 'programas' }}</span>
            @if ($programs->hasPages())<div>{{ $programs->links() }}</div>@endif
        </x-slot:footer>
    </x-erp.worklist>
</div>
@endsection
