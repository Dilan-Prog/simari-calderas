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

/* ── Teléfono (≤768px): todo acotado a .tasks-index; escritorio sin cambios ── */
@media (max-width: 768px) {
    .tasks-index { padding: 12px; overflow-x: hidden; }
    .tasks-index * { box-sizing: border-box; }

    /* Encabezado */
    .tasks-index .erp-page-head { flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 14px; }
    .tasks-index .erp-page-head > div:first-child { min-width: 0; }
    .tasks-index .erp-title { font-size: 22px; }
    .tasks-index .erp-subtitle { max-width: none; overflow-wrap: anywhere; }
    .tasks-index .erp-head-actions { width: 100%; }
    .tasks-index .erp-head-actions .erp-btn { flex: 1 1 100%; justify-content: center; min-height: 44px; font-size: 14px; }

    /* Vistas: una sola fila desplazable */
    .tasks-index .erp-views { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding: 10px 12px; gap: 6px; scroll-padding: 0 12px; }
    .tasks-index .erp-views::-webkit-scrollbar { display: none; }
    .tasks-index .erp-views-label { display: none; }
    .tasks-index .erp-views .erp-pill { flex: 0 0 auto; white-space: nowrap; min-height: 36px; padding: 6px 14px; font-size: 13px; }

    /* Barra de búsqueda / filtros */
    .tasks-index .erp-worklist > .erp-toolbar { padding: 12px; }
    .tasks-index .erp-worklist > .erp-toolbar > form { flex-wrap: wrap; gap: 8px; min-width: 0; }
    .tasks-index .erp-search { flex: 1 1 100%; min-width: 0; }
    .tasks-index .erp-search input { font-size: 16px; min-height: 44px; }
    .tasks-index .erp-toolbar .erp-select { flex: 1 1 0; min-width: 0; font-size: 16px; min-height: 44px; }
    .tasks-index .erp-toolbar .erp-btn { flex: 0 0 auto; min-height: 44px; justify-content: center; padding: 8px 18px; font-size: 14px; }
    .tasks-index .erp-chips-row { padding: 10px 12px 0; }
    .tasks-index .erp-chips-row .erp-pill { max-width: 100%; white-space: normal; overflow-wrap: anywhere; min-height: 32px; }

    /* Tabla → tarjetas */
    .tasks-index .erp-table-wrap { overflow-x: visible; }
    .tasks-index .erp-table, .tasks-index .erp-table tbody { display: block; width: 100%; }
    .tasks-index .erp-table thead { display: none; }
    .tasks-index .erp-table tbody { padding: 12px; }
    .tasks-index .erp-table tbody tr.task-row {
        display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 10px 12px;
        grid-template-areas: "folio status" "title title" "origin type" "assignee due" "priority created";
        padding: 12px 14px; margin-bottom: 10px; border: 1px solid var(--erp-line); border-radius: 10px;
        background: var(--erp-surface, #fff); box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .tasks-index .erp-table tbody tr.task-row:last-child { margin-bottom: 0; }
    .tasks-index .erp-table tbody tr.task-row:active { background: #fafbfc; }
    .tasks-index .erp-table tbody tr.task-row > td { display: block; padding: 0; border: 0; min-width: 0; background: transparent; box-shadow: none; font-size: 13px; overflow-wrap: anywhere; }
    .tasks-index .task-c-folio { grid-area: folio; align-self: center; font-size: 12.5px; }
    .tasks-index .task-c-status { grid-area: status; justify-self: end; align-self: center; text-align: right; max-width: 100%; }
    .tasks-index .task-c-title { grid-area: title; }
    .tasks-index .task-c-origin { grid-area: origin; }
    .tasks-index .task-c-type { grid-area: type; }
    .tasks-index .task-c-assignee { grid-area: assignee; }
    .tasks-index .task-c-due { grid-area: due; }
    .tasks-index .task-c-priority { grid-area: priority; }
    .tasks-index .task-c-created { grid-area: created; font-size: 12.5px !important; color: var(--erp-text) !important; }
    .tasks-index .task-c-title .erp-cell-main { font-size: 15px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tasks-index .task-c-title .erp-cell-sub { margin-top: 3px; font-size: 12.5px; }
    .tasks-index .task-c-title .task-cell-link { display: inline-block; padding: 4px 0; }
    .tasks-index .task-cell-desc { max-width: 100%; }
    .tasks-index .task-row td[data-label]::before {
        content: attr(data-label); display: block; margin-bottom: 3px;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--erp-faint);
    }
    .tasks-index .task-row .erp-chip { white-space: normal; max-width: 100%; line-height: 1.35; }
    .tasks-index .task-row .erp-due { white-space: normal; }
    /* La fila de datos se separa del título con una línea fina */
    .tasks-index .task-c-origin, .tasks-index .task-c-type { padding-top: 10px !important; border-top: 1px solid var(--erp-line-soft) !important; }
    /* Vencida: borde rojo a la izquierda */
    .tasks-index .task-row:has(.erp-due.is-overdue) { border-left: 3px solid var(--erp-bad-dot); }
    /* Eliminada: conserva la atenuación y el tachado */
    .tasks-index .task-row.task-row-deleted { opacity: .6; border-style: dashed; }
    /* Resalte "respondió" (hot.css pinta los td; en tarjeta se pinta la tarjeta) */
    .tasks-index .erp-table tbody tr.task-row.is-hot { background: var(--hot-soft, #fff4ec) !important; box-shadow: inset 3px 0 0 var(--hot-accent, #f97316); border-color: var(--hot-accent, #f97316); }
    .tasks-index .erp-table tbody tr.task-row.is-hot > td { background: transparent; box-shadow: none; }

    /* Estado vacío */
    .tasks-index .erp-empty { text-align: center; }

    /* Pie: conteo + paginación apilados y centrados */
    .tasks-index .erp-wl-footer { flex-direction: column; align-items: center; text-align: center; gap: 10px; padding: 12px; font-size: 13px; }
    .tasks-index .erp-wl-footer > div { width: 100%; }
    .tasks-index .admin-pagination { justify-content: center; margin-top: 0; }
    .tasks-index .admin-pagination-list { justify-content: center; gap: 4px; }
    .tasks-index .admin-pagination-btn { min-width: 40px; min-height: 40px; padding: 0 8px; }
    .tasks-index .admin-pagination-dots { min-width: auto; }
}
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
<div class="erp-page tasks-index">
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
                                <td class="erp-cell-folio task-c-folio"><a href="{{ route('admin.tasks.show', $task) }}">TAR-{{ str_pad((string) $task->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                                <td class="task-c-title">
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
                                <td class="task-c-origin" data-label="Origen">
                                    @if ($task->createdByWorkflow)
                                        <x-erp.chip tone="acc" :label="$task->createdByWorkflow->name" />
                                    @elseif (in_array($task->id, $emailTaskIds ?? [], true))
                                        <x-erp.chip tone="info" label="Correo N8N" />
                                    @else
                                        <span class="erp-cell-sub">Manual</span>
                                    @endif
                                </td>
                                <td class="task-c-type" data-label="Tipo">{{ \App\Models\Task::TYPES[$task->type] ?? '—' }}</td>
                                <td class="task-c-assignee" data-label="Responsable">
                                    @if ($task->assignee)
                                        {{ $task->assignee->full_name }}
                                    @else
                                        <span class="erp-cell-sub">Sin asignar</span>
                                    @endif
                                </td>
                                <td class="task-c-due" data-label="Vence"><x-erp.due :date="$task->due_at" :closed="$task->status === 'closed'" /></td>
                                <td class="task-c-priority" data-label="Prioridad">
                                    @if ($task->priority)
                                        <x-erp.chip :tone="$priorityTone[$task->priority] ?? 'neu'" :label="\App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority" />
                                    @else
                                        <span class="erp-cell-sub">—</span>
                                    @endif
                                </td>
                                <td class="task-c-status">
                                    @if ($task->trashed())
                                        <x-erp.chip tone="bad" :label="'Eliminada por ' . ($task->deletedBy?->full_name ?? 'usuario eliminado')" />
                                    @else
                                        <x-erp.chip :tone="$statusTone[$task->status] ?? 'neu'" :label="\App\Models\Task::statusLabel($task->status)" />
                                    @endif
                                </td>
                                <td class="erp-cell-sub task-c-created" data-label="Creada">{{ $task->created_at->format('d/m/Y H:i') }}</td>
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
    // Teléfono: la fila de vistas se desplaza horizontalmente; centra la vista activa.
    var views = document.querySelector('.tasks-index .erp-views');
    var active = views && views.querySelector('.erp-pill.is-active');
    if (views && active && views.scrollWidth > views.clientWidth) {
        views.scrollLeft = Math.max(0, active.offsetLeft - views.offsetLeft - (views.clientWidth - active.offsetWidth) / 2);
    }
})();
</script>
@endsection
