@extends('admin.layouts.master')

@section('title', 'Tareas - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
}

.tasks-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.tasks-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.tasks-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.tasks-breadcrumb-current { color: #374151; }
.tasks-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.tasks-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }

.tasks-filters { display: flex; gap: 8px; }
.tasks-filter-link { display: inline-flex; align-items: center; height: 36px; padding: 0 14px; border-radius: 7px; font-size: 13px; font-weight: 600; text-decoration: none; color: #4b5563; background: #f2f3f5; }
.tasks-filter-link.active { background: var(--secondary-color); color: #fff; }

.tasks-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.tasks-table-scroll { overflow-x: auto; }
.tasks-table { width: 100%; border-collapse: collapse; min-width: 760px; }
.tasks-table thead tr { background: var(--header-footer-color); height: 44px; }
.tasks-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.tasks-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; }
.tasks-table tbody tr:last-child { border-bottom: none; }
.tasks-table tbody tr:hover { background: rgba(255,247,237,.4); }
.tasks-table td { padding: 10px 20px; vertical-align: middle; font-size: 13px; color: #111827; }
.tasks-td-title { font-weight: 600; }
.tasks-td-desc { color: var(--text-description-color); font-size: 12.5px; max-width: 360px; }
.tasks-pill { display: inline-flex; align-items: center; height: 22px; padding: 0 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; background: #eef2ff; color: #4338ca; }
.tasks-link { color: var(--secondary-color); font-weight: 600; text-decoration: none; }
.tasks-link:hover { text-decoration: underline; }
.tasks-muted { color: #9CA3AF; }
.tasks-empty { padding: 60px 20px; text-align: center; color: var(--text-description-color); font-size: 14px; }
</style>
@endpush

@section('content')
<div class="tasks-page">
    <div class="tasks-header">
        <div>
            <div class="tasks-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Panel de Control</a>
                <span>/</span>
                <span class="tasks-breadcrumb-current">Tareas</span>
            </div>
            <h1 class="tasks-title">Tareas</h1>
            <p class="tasks-subtitle">Pendientes generados automáticamente por las automatizaciones (ej. "sin correo", "sin WhatsApp") y tareas manuales.</p>
        </div>
        <div class="tasks-filters">
            <a href="{{ route('admin.tasks.index', ['status' => 'open']) }}" class="tasks-filter-link {{ $status === 'open' ? 'active' : '' }}">Abiertas</a>
            <a href="{{ route('admin.tasks.index', ['status' => 'all']) }}" class="tasks-filter-link {{ $status === 'all' ? 'active' : '' }}">Todas</a>
        </div>
    </div>

    <div class="tasks-table-card">
        @if ($tasks->isEmpty())
            <div class="tasks-empty">No hay tareas {{ $status === 'open' ? 'abiertas' : '' }} por ahora.</div>
        @else
            <div class="tasks-table-scroll">
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th>Relacionado con</th>
                            <th>Origen</th>
                            <th>Asignada a</th>
                            <th>Estado</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr>
                                <td>
                                    <div class="tasks-td-title">{{ $task->title }}</div>
                                    @if ($task->description)
                                        <div class="tasks-td-desc">{{ $task->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->taskableUrl())
                                        <a href="{{ $task->taskableUrl() }}" class="tasks-link">{{ $task->taskableLabel() }}</a>
                                    @elseif ($task->taskableLabel())
                                        {{ $task->taskableLabel() }}
                                    @else
                                        <span class="tasks-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->createdByWorkflow)
                                        <span class="tasks-pill">{{ $task->createdByWorkflow->name }}</span>
                                    @else
                                        <span class="tasks-muted">Manual</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->assignee)
                                        {{ $task->assignee->full_name }}
                                    @else
                                        <span class="tasks-muted">Sin asignar</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tasks-pill" style="{{ $task->status === 'open' ? '' : 'background:#f2f3f5;color:#6b7280;' }}">{{ ucfirst($task->status) }}</span>
                                </td>
                                <td class="tasks-muted">{{ $task->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{ $tasks->links() }}
</div>
@endsection
