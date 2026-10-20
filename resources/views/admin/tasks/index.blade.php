@extends('admin.layouts.master')

@section('title', 'Tareas - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
<style>
/* Modal "Nueva tarea" -- autocontenido en esta página; usa los tokens de erp-core.css. */
.task-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(23,25,28,.55); z-index: 1000; align-items: center; justify-content: center; padding: 16px; }
.task-modal-overlay.active { display: flex; }
.task-modal-box { background: var(--erp-surface); border-radius: 16px; width: 100%; max-width: 720px; max-height: 92vh; overflow-y: auto; padding: 24px 26px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.task-modal-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.task-modal-eyebrow { font-size: 11.5px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--erp-muted, #6b7280); margin: 0 0 2px; }
.task-modal-title { font-size: 24px; font-weight: 800; color: var(--erp-ink); margin: 0; line-height: 1.15; }
.task-modal-head-actions { display: flex; gap: 10px; flex-shrink: 0; }
.task-modal-error { padding: 10px 14px; margin-bottom: 14px; border-radius: 8px; color: var(--erp-bad); background: var(--erp-bad-bg); font-size: 13px; }
.task-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
.task-modal-field--full { grid-column: 1 / -1; }
.task-modal-field label, .task-modal-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--erp-text); margin-bottom: 5px; }
.task-modal-field label .req, .task-modal-label .req { color: var(--erp-bad); }
.task-modal-field input[type="text"], .task-modal-field input[type="date"], .task-modal-field select, .task-modal-field textarea {
    width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 10px; padding: 10px 12px; font-size: 14px; font-family: inherit; color: var(--erp-ink); background: #fff;
}
.task-modal-field input:focus, .task-modal-field select:focus, .task-modal-field textarea:focus { outline: none; border-color: var(--erp-accent); box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
.task-modal-field textarea { resize: vertical; min-height: 80px; }
.task-seg { display: inline-flex; border: 1px solid #d4d7dc; border-radius: 10px; overflow: hidden; background: #fff; }
.task-seg label { margin: 0; cursor: pointer; position: relative; }
.task-seg input { position: absolute; opacity: 0; pointer-events: none; }
.task-seg span { display: block; padding: 10px 18px; font-size: 13.5px; font-weight: 700; color: var(--erp-text); border-left: 1px solid #e5e7eb; }
.task-seg label:first-child span { border-left: 0; }
.task-seg input:checked + span { background: var(--erp-ink); color: #fff; }
.task-seg input:focus-visible + span { box-shadow: inset 0 0 0 2px var(--erp-accent); }
@media (max-width: 640px) {
    .task-modal-grid { grid-template-columns: 1fr; }
    .task-modal-head { flex-direction: column; align-items: stretch; }
}

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
        <button type="button" class="erp-btn erp-btn--primary" id="taskNewBtn">+ Nueva tarea</button>
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
                            <tr class="{{ $task->trashed() ? 'task-row-deleted task-row' : 'task-row' }}" data-href="{{ route('admin.tasks.show', $task) }}">
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

{{-- Modal para crear una tarea manual. El detalle de cada tarea es la página admin.tasks.show. --}}
<div class="task-modal-overlay {{ $errors->any() ? 'active' : '' }}" id="taskModalOverlay">
    <div class="task-modal-box" role="dialog" aria-modal="true" aria-labelledby="taskModalTitle">
        <form method="POST" action="{{ route('admin.tasks.store') }}">
            @csrf
            <div class="task-modal-head">
                <div>
                    <p class="task-modal-eyebrow">Seguimiento</p>
                    <h2 class="task-modal-title" id="taskModalTitle">Nueva tarea</h2>
                </div>
                <div class="task-modal-head-actions">
                    <button type="button" class="erp-btn" id="taskModalCancel">Cancelar</button>
                    <button type="submit" class="erp-btn erp-btn--primary">Guardar</button>
                </div>
            </div>
            @if ($errors->any()) <div class="task-modal-error" role="alert">{{ $errors->first() }}</div> @endif
            <div class="task-modal-grid">
                <div class="task-modal-field task-modal-field--full">
                    <label for="taskTitle">Tarea <span class="req">*</span></label>
                    <input type="text" id="taskTitle" name="title" required maxlength="255" value="{{ old('title') }}">
                </div>
                <div class="task-modal-field">
                    <label for="taskRelated">Relacionado con</label>
                    <input type="text" id="taskRelated" name="related_reference" maxlength="255" value="{{ old('related_reference') }}" placeholder="Folio de negocio, cotización o cliente">
                </div>
                <div class="task-modal-field">
                    <label for="taskType">Tipo</label>
                    <select id="taskType" name="type">
                        <option value="">Selecciona...</option>
                        @foreach (\App\Models\Task::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('type') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-modal-field">
                    <label for="taskAssignee">Responsable <span class="req">*</span></label>
                    <select id="taskAssignee" name="assigned_to" required>
                        <option value="">Selecciona...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to') === (string) $user->id)>{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-modal-field">
                    <label for="taskDue">Vence</label>
                    <input type="date" id="taskDue" name="due_at" value="{{ old('due_at') }}">
                </div>
                <div class="task-modal-field task-modal-field--full">
                    <span class="task-modal-label">Prioridad</span>
                    <div class="task-seg" role="radiogroup" aria-label="Prioridad">
                        @foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $value => $label)
                            <label><input type="radio" name="priority" value="{{ $value }}" @checked(old('priority', 'media') === $value)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="task-modal-field task-modal-field--full">
                    <label for="taskDesc">Descripción</label>
                    <textarea id="taskDesc" name="description">{{ old('description') }}</textarea>
                </div>
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
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') overlay.classList.remove('active'); });

    document.querySelectorAll('.task-row[data-href]').forEach(function (row) {
        row.addEventListener('click', function () { window.location.href = row.dataset.href; });
    });
})();
</script>
@endsection
