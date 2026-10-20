@extends('admin.layouts.master')

@section('title', 'Tareas - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
<style>
/* Modal crear/ver/editar -- autocontenido en esta página; usa los tokens de erp-core.css. */
.task-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(23,25,28,.5); z-index: 1000; align-items: center; justify-content: center; padding: 16px; }
.task-modal-overlay.active { display: flex; }
.task-modal-box { background: var(--erp-surface); border-radius: 12px; width: 100%; max-width: 480px; max-height: 90vh; overflow-y: auto; padding: 24px; }
.task-modal-title { font-size: 18px; font-weight: 800; color: var(--erp-ink); margin: 0 0 18px; }
.task-modal-field { margin-bottom: 14px; }
.task-modal-field label { display: block; font-size: 12.5px; font-weight: 700; color: var(--erp-text); margin-bottom: 5px; }
.task-modal-field input[type="text"], .task-modal-field input[type="datetime-local"], .task-modal-field select, .task-modal-field textarea {
    width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 8px; padding: 9px 11px; font-size: 13.5px; font-family: inherit; color: var(--erp-ink);
}
.task-modal-field input:focus, .task-modal-field select:focus, .task-modal-field textarea:focus { outline: none; border-color: var(--erp-accent); box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
.task-modal-field textarea { resize: vertical; min-height: 70px; }
.task-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
.task-modal-btn { height: 38px; padding: 0 18px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid transparent; }
.task-modal-btn--cancel { background: #fff; color: var(--erp-text); border-color: #d4d7dc; }
.task-modal-btn--save { background: var(--erp-accent); color: #fff; }
.task-modal-btn--save:hover { background: #ea6a0c; }

/* Tabla de tareas: ocupa todo el ancho disponible (sin el tope de 1400px del contenedor ERP) */
.erp-page { max-width: none !important; margin: 0 !important; }
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
    $statusTone = ['open' => 'info', 'pending' => 'warn', 'in_progress' => 'acc', 'in_review' => 'info', 'closed' => 'ok'];
    $keep = fn (array $over) => route('admin.tasks.index', array_filter(
        array_merge(['view' => $view === 'open' ? null : $view, 'q' => $q, 'assigned_to' => $assignedTo], $over),
        fn ($v) => $v !== null && $v !== ''
    ));
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
        <button type="button" class="erp-btn erp-btn--primary" id="taskNewBtn">+ Nueva tarea</button>
    </x-erp.page-header>

    @if (session('success')) <div class="erp-card" role="status" style="padding:10px 14px;margin-bottom:12px;color:var(--erp-ok);background:var(--erp-ok-bg);">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="erp-card" role="alert" style="padding:10px 14px;margin-bottom:12px;color:var(--erp-bad);background:var(--erp-bad-bg);">{{ $errors->first() }}</div> @endif

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
                            <th>Responsable</th>
                            <th>Vence</th>
                            <th>Estado</th>
                            <th>Creada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="{{ $task->trashed() ? 'task-row-deleted task-row' : 'task-row' }}" data-href="{{ route('admin.tasks.show', $task) }}">
                                <td class="erp-cell-folio"><a href="{{ route('admin.tasks.show', $task) }}">TAR-{{ str_pad((string) $task->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                                <td>
                                    <div class="erp-cell-main">{{ $task->title }}</div>
                                    @if ($task->taskableUrl())
                                        <div class="erp-cell-sub"><a href="{{ $task->taskableUrl() }}" class="task-cell-link" onclick="event.stopPropagation()">{{ $task->taskableLabel() }}</a></div>
                                    @elseif ($task->taskableLabel())
                                        <div class="erp-cell-sub">{{ $task->taskableLabel() }}</div>
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
                                <td>
                                    @if ($task->assignee)
                                        {{ $task->assignee->full_name }}
                                    @else
                                        <span class="erp-cell-sub">Sin asignar</span>
                                    @endif
                                </td>
                                <td><x-erp.due :date="$task->due_at" :closed="$task->status === 'closed'" /></td>
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

{{-- Modal para crear una tarea manual. El detalle de cada tarea es la página admin.tasks.show. --}}
<div class="task-modal-overlay" id="taskModalOverlay">
    <div class="task-modal-box">
        <h2 class="task-modal-title">Nueva tarea</h2>
        <form method="POST" action="{{ route('admin.tasks.store') }}">
            @csrf
            <div class="task-modal-field">
                <label>Título *</label>
                <input type="text" name="title" required maxlength="255">
            </div>
            <div class="task-modal-field">
                <label>Descripción</label>
                <textarea name="description"></textarea>
            </div>
            <div class="task-modal-field">
                <label>Fecha límite</label>
                <input type="datetime-local" name="due_at">
            </div>
            <div class="task-modal-field">
                <label>Estado</label>
                <select name="status">
                    @foreach (\App\Models\Task::STATUSES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="task-modal-field">
                <label>Asignada a</label>
                <select name="assigned_to">
                    <option value="">Sin asignar</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="task-modal-actions">
                <button type="button" class="task-modal-btn task-modal-btn--cancel" id="taskModalCancel">Cancelar</button>
                <button type="submit" class="task-modal-btn task-modal-btn--save">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('taskModalOverlay');
    document.getElementById('taskNewBtn').addEventListener('click', function () { overlay.classList.add('active'); });
    document.getElementById('taskModalCancel').addEventListener('click', function () { overlay.classList.remove('active'); });
    overlay.addEventListener('click', function (e) { if (e.target === overlay) overlay.classList.remove('active'); });

    document.querySelectorAll('.task-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function () { window.location.href = row.dataset.href; });
    });
})();
</script>
@endsection
