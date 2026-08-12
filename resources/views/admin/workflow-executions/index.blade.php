@extends('admin.layouts.master')

@section('title', 'Ejecuciones de Automatizaciones - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.wfx-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.wfx-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.wfx-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wfx-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wfx-breadcrumb-current { color: #374151; }
.wfx-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wfx-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }

/* ── Filter card ─────────────────────────── */
.wfx-filters-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 16px; }
.wfx-filters-grid { display: grid; grid-template-columns: 3fr 3fr 1fr; gap: 12px; align-items: center; }
.wfx-select-wrap { position: relative; }
.wfx-filter-select { width: 100%; height: 40px; padding: 0 32px 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; appearance: none; cursor: pointer; transition: border-color .2s; box-sizing: border-box; }
.wfx-filter-select:focus { outline: none; border-color: var(--secondary-color); }
.wfx-select-chevron { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--text-description-color); pointer-events: none; display: flex; align-items: center; }
.wfx-btn-filter { height: 40px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--secondary-color); color: var(--secondary-color); background: transparent; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; white-space: nowrap; width: 100%; }
.wfx-btn-filter:hover { background: #FFF7ED; }

/* ── Table card ──────────────────────────── */
.wfx-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.wfx-table-scroll { overflow-x: auto; }
.wfx-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.wfx-table thead tr { background: var(--header-footer-color); height: 44px; }
.wfx-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.wfx-table tbody tr { height: 52px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.wfx-table tbody tr:last-child { border-bottom: none; }
.wfx-table tbody tr:hover { background: rgba(255,247,237,.4); }
.wfx-table td { padding: 0 20px; vertical-align: middle; }
.wfx-td-name { font-size: 13px; font-weight: 600; color: var(--secondary-color); text-decoration: none; }
.wfx-td-name:hover { text-decoration: underline; color: var(--button-primary-color-hover); }
.wfx-td-sub { font-size: 14px; color: #374151; }
.wfx-td-fecha { font-size: 13px; color: var(--text-description-color); }
.wfx-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.wfx-badge-active    { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }
.wfx-badge-waiting   { background: #FFFBEB; color: #D97706; border-color: #FDE68A; }
.wfx-badge-completed { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.wfx-badge-exited    { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }
.wfx-badge-fail-yes  { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.wfx-badge-fail-no   { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.wfx-empty-row td { padding: 48px 20px; text-align: center; }
.wfx-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.wfx-empty-inner svg { opacity: .4; }
.wfx-empty-inner p { font-size: 14px; margin: 0; }

/* ── Pagination ──────────────────────────── */
.wfx-pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F3F4F6; gap: 16px; flex-wrap: wrap; }
.wfx-pagination-info { font-size: 13px; color: var(--text-description-color); }
.wfx-pagination-info strong { font-weight: 600; color: #111827; }

/* ── Responsive ──────────────────────────── */
@media (max-width: 1024px) {
    .wfx-filters-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .wfx-page { padding: 16px; gap: 16px; }
    .wfx-filters-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="wfx-page">

    @include('admin.workflows.partials._tabs')

    {{-- Header --}}
    <div class="wfx-header">
        <div>
            <div class="wfx-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span>Automatizaciones</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="wfx-breadcrumb-current">Ejecuciones</span>
            </div>
            <h1 class="wfx-title">Ejecuciones</h1>
            <p class="wfx-subtitle">Historial de inscripciones y ejecuciones de las automatizaciones</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.workflow-executions.index') }}" class="wfx-filters-card">
        <div class="wfx-filters-grid">

            <div class="wfx-select-wrap">
                <select name="workflow_id" class="wfx-filter-select">
                    <option value="">Todas las automatizaciones</option>
                    @foreach($workflowsForFilter as $workflow)
                        <option value="{{ $workflow->id }}" {{ (string) request('workflow_id') === (string) $workflow->id ? 'selected' : '' }}>{{ $workflow->name }}</option>
                    @endforeach
                </select>
                <span class="wfx-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            <div class="wfx-select-wrap">
                <select name="status" class="wfx-filter-select">
                    <option value="">Todos los estados</option>
                    <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Activo</option>
                    <option value="waiting"   {{ request('status') === 'waiting'   ? 'selected' : '' }}>Esperando</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                    <option value="exited"    {{ request('status') === 'exited'    ? 'selected' : '' }}>Salido</option>
                </select>
                <span class="wfx-select-chevron">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </div>

            <button type="submit" class="wfx-btn-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
                Filtrar
            </button>
        </div>
    </form>

    {{-- Table card --}}
    <div class="wfx-table-card">
        <div class="wfx-table-scroll">
            <table class="wfx-table">
                <thead>
                    <tr>
                        <th>Workflow</th>
                        <th>Registro</th>
                        <th>Estado</th>
                        <th>Inscrito</th>
                        <th>Completado</th>
                        <th>¿Falló?</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                    <tr>
                        <td>
                            @if($enrollment->workflow)
                                <a href="{{ route('admin.workflows.show', $enrollment->workflow) }}" class="wfx-td-name">{{ $enrollment->workflow->name }}</a>
                            @else
                                <span class="wfx-td-sub">—</span>
                            @endif
                        </td>
                        <td class="wfx-td-sub">{{ class_basename($enrollment->enrollable_type) }} #{{ $enrollment->enrollable_id }}</td>
                        <td>
                            @php
                                $statusMap = [
                                    'active'    => ['class' => 'wfx-badge-active',    'label' => 'Activo'],
                                    'waiting'   => ['class' => 'wfx-badge-waiting',   'label' => 'Esperando'],
                                    'completed' => ['class' => 'wfx-badge-completed', 'label' => 'Completado'],
                                    'exited'    => ['class' => 'wfx-badge-exited',    'label' => 'Salido'],
                                ];
                                $statusBadge = $statusMap[$enrollment->status] ?? ['class' => 'wfx-badge-exited', 'label' => $enrollment->status];
                            @endphp
                            <span class="wfx-badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                        </td>
                        <td class="wfx-td-fecha">{{ $enrollment->enrolled_at?->diffForHumans() ?? '—' }}</td>
                        <td class="wfx-td-fecha">{{ $enrollment->completed_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            @if($enrollment->has_failure)
                                <span class="wfx-badge wfx-badge-fail-yes">Sí</span>
                            @else
                                <span class="wfx-badge wfx-badge-fail-no">No</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="wfx-empty-row">
                        <td colspan="6">
                            <div class="wfx-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                                <p>No se encontraron ejecuciones con los filtros actuales.</p>
                                @if(request()->hasAny(['workflow_id','status']))
                                <a href="{{ route('admin.workflow-executions.index') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Limpiar filtros</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enrollments->total() > 0)
        <div class="wfx-pagination-bar">
            <span class="wfx-pagination-info">
                Mostrando <strong>{{ $enrollments->firstItem() }}-{{ $enrollments->lastItem() }}</strong>
                de <strong>{{ $enrollments->total() }}</strong> ejecuciones
            </span>
            {{ $enrollments->links('admin.components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection
