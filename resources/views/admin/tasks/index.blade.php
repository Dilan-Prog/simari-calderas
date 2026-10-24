@extends('admin.layouts.master')

@section('title', 'Tareas - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/js/admin/hot-highlight.js'])
<style>
/* Modal "Nueva tarea" -- autocontenido en esta página; usa los tokens de erp-core.css. */
/* Tabla de tareas: ocupa todo el ancho disponible (sin el tope de 1400px del contenedor ERP) */
.erp-page { max-width: none !important; margin: 0 !important; flex: 1; min-height: 0; overflow-y: auto; }
.task-row { cursor: pointer; }
.task-row-deleted { opacity: .6; }
.task-row-deleted .erp-cell-main { text-decoration: line-through; }
.task-cell-link { color: var(--erp-accent-dark); font-weight: 700; text-decoration: none; }
.task-cell-link:hover { text-decoration: underline; }
.task-cell-desc { max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
@endpush

@section('content')
    @php
        $hotTaskIds = [];
        try { $hotTaskIds = method_exists(\App\Support\Notif::class, 'hotTaskIds') ? array_map('intval', (array) \App\Support\Notif::hotTaskIds(auth()->user())) : []; } catch (\Throwable $e) { $hotTaskIds = []; }
    @endphp
@php
    $statusTone = ['open' => 'info', 'pending' => 'warn', 'in_progress' => 'acc', 'in_review' => 'info', 'closed' => 'ok'];
    $keep = fn (array $over) => route('admin.tasks.index', array_filter(
        array_merge(['view' => $view === 'open' ? null : $view, 'q' => $q, 'assigned_to' => $assignedTo], $over),
        fn ($v) => $v !== null && $v !== ''
    ));
    $priorityTone = ['alta' => 'bad', 'media' => 'warn', 'baja' => 'neu'];
    $viewDefs = ['open' => 'Abiertas', 'mine' => 'Mis tareas', 'overdue' => 'Vencidas', 'closed' => 'Cerradas', 'all' => 'Todas'];
    $views = [];
    foreach ($viewDefs as $key => $label) {
        $views[] = [
            'label'  => $label . ' ' . ($counts[$key] ?? 0),
            'url'    => route('admin.tasks.index', array_filter(['view' => $key === 'open' ? null : $key, 'q' => $q, 'assigned_to' => $assignedTo], fn ($v) => $v !== null && $v !== '')),
            'active' => $view === $key,
        ];
    }
    $chips = [];
    if ($q !== '') $chips[] = ['label' => 'Búsqueda: ' . $q, 'url' => $keep(['q' => null])];
    if ($assignedTo !== '') {
        $who = $assignedTo === 'sin' ? 'Sin asignar' : ($users->firstWhere('id', (int) $assignedTo)?->full_name ?? '—');
        $chips[] = ['label' => 'Responsable: ' . $who, 'url' => $keep(['assigned_to' => null])];
    }
@endphp
<div class="erp-page">
    <x-erp.page-header title="Tareas" code="VTA6"
        desc="Pendientes generados por las automatizaciones y por los correos entrantes, más las tareas manuales."
        :crumbs="[['Ventas', null], ['Tareas', null]]">
        <button type="button" class="erp-btn erp-btn--primary" id="taskNewBtn" data-task-modal-open="create">+ Nueva tarea</button>
    </x-erp.page-header>

    @if (session('success')) <div class="erp-card" role="status" style="padding:10px 14px;margin-bottom:12px;color:var(--erp-ok);background:var(--erp-ok-bg);">{{ session('success') }}</div> @endif

    <x-erp.worklist :views="$views" :chips="$chips">
        <x-slot:toolbar>
            <form method="GET" class="erp-toolbar" style="padding:0;border:0;flex:1;" role="search">
                @if ($view !== 'open') <input type="hidden" name="view" value="{{ $view }}"> @endif
                <div class="erp-search"><input type="search" name="q" value="{{ $q }}" placeholder="Buscar por tarea o descripción…" aria-label="Buscar tareas"></div>
                <select name="assigned_to" class="erp-select" aria-label="Responsable" onchange="this.form.submit()">
                    <option value="">Responsable: todos</option>
                    <option value="sin" @selected($assignedTo === 'sin')>Sin asignar</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) $assignedTo === (string) $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn">Filtrar</button>
            </form>
        </x-slot:toolbar>

        @if ($tasks->isEmpty())
            <div style="padding:16px;"><x-erp.empty title="Sin tareas" text="No hay tareas con estos filtros." /></div>
        @else
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tarea / relacionado</th>
                            <th>Origen</th>
                            <th>Tipo</th>
                            <th>Responsable</th>
                            <th>Vence</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="{{ $task->trashed() ? 'task-row-deleted task-row' : 'task-row' }} {{ in_array($task->id, $hotTaskIds, true) ? 'is-hot' : '' }}" data-hot-task="{{ $task->id }}" data-href="{{ route('admin.tasks.show', $task) }}">
                                <td class="erp-cell-folio"><a href="{{ route('admin.tasks.show', $task) }}">TAR-{{ str_pad((string) $task->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                                <td>
                                    <div class="erp-cell-main">{{ $task->title }}</div>
                                    @if ($task->taskableUrl())
                                        <div class="erp-cell-sub"><a href="{{ $task->taskableUrl() }}" class="task-cell-link" onclick="event.stopPropagation()">{{ $task->taskableLabel() }}</a></div>
                                    @elseif ($task->taskableLabel())
                                        <div class="erp-cell-sub">{{ $task->taskableLabel() }}</div>
                                    @elseif ($task->related_reference)
                                        <div class="erp-cell-sub">{{ $task->related_reference }}</div>
                                    @elseif ($task->description)
                                        <div class="erp-cell-sub task-cell-desc">{{ $task->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->createdByWorkflow)
                                        <x-erp.chip tone="acc" :label="$task->createdByWorkflow->name" />
                                    @elseif (in_array($task->id, $emailTaskIds ?? [], true))
                                        <x-erp.chip tone="info" label="Correo N8N" />
                                    @else
                                        <span class="erp-cell-sub">Manual</span>
                                    @endif
                                </td>
                                <td>{{ \App\Models\Task::TYPES[$task->type] ?? '—' }}</td>
                                <td>
                                    @if ($task->assignee)
                                        {{ $task->assignee->full_name }}
                                    @else
                                        <span class="erp-cell-sub">Sin asignar</span>
                                    @endif
                                </td>
                                <td><x-erp.due :date="$task->due_at" :closed="$task->status === 'closed'" /></td>
                                <td>
                                    @if ($task->priority)
                                        <x-erp.chip :tone="$priorityTone[$task->priority] ?? 'neu'" :label="\App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority" />
                                    @else
                                        <span class="erp-cell-sub">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($task->trashed())
                                        <x-erp.chip tone="bad" :label="'Eliminada por ' . ($task->deletedBy?->full_name ?? 'usuario eliminado')" />
                                    @else
                                        <x-erp.chip :tone="$statusTone[$task->status] ?? 'neu'" :label="\App\Models\Task::statusLabel($task->status)" />
                                    @endif
                                </td>
                                <td class="erp-cell-sub">{{ $task->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <x-slot:footer>
            <span>{{ $tasks->total() }} {{ $tasks->total() === 1 ? 'tarea' : 'tareas' }}</span>
            <div>{{ $tasks->links('admin.components.pagination') }}</div>
        </x-slot:footer>
    </x-erp.worklist>
</div>

{{-- Modal de alta (el mismo componente sirve para editar en el detalle). --}}
@include('admin.tasks._form_modal', ['mode' => 'create', 'users' => $users])

<script>
(function () {
    document.querySelectorAll('.task-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function () { window.location.href = row.dataset.href; });
    });
})();
</script>
@endsection
