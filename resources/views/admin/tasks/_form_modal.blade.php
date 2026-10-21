{{--
    Modal de tarea: crear (mode=create) o editar (mode=edit, con $task).
      Variables: $mode, $users, $task (solo edit).
    Se abre con cualquier elemento que tenga data-task-modal-open="create|edit".
--}}
@php
    $isEdit = $mode === 'edit';
    $uid = 'taskModal' . ucfirst($mode);
    $reopen = $errors->any() && old('_modal') === $mode;
    $val = fn (string $field, $fallback = '') => old($field, $isEdit ? ($fallback) : '');
    $dueValue = old('due_at', $isEdit ? $task->due_at?->format('Y-m-d H:i') : '');
    $priorityValue = old('priority', $isEdit ? ($task->priority ?? '') : 'media');
@endphp
@once
    @push('styles')
    <style>
    .task-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(23,25,28,.55); z-index: 10000; align-items: center; justify-content: center; padding: 16px; }
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
        width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 8px; padding: 9px 11px; font-size: 13.5px; font-family: inherit; color: var(--erp-ink); background: #fff;
    }
    .task-modal-field input:focus, .task-modal-field select:focus, .task-modal-field textarea:focus { outline: none; border-color: var(--erp-accent); box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
    .task-modal-field textarea { resize: vertical; min-height: 80px; }
    .task-seg { display: inline-flex; border: 1px solid #d4d7dc; border-radius: 8px; overflow: hidden; }
    .task-seg label { margin: 0; cursor: pointer; }
    .task-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .task-seg span { display: block; padding: 8px 18px; font-size: 13px; font-weight: 700; color: var(--erp-text); background: #fff; border-right: 1px solid #d4d7dc; }
    .task-seg label:last-child span { border-right: 0; }
    .task-seg input:checked + span { background: var(--erp-ink); color: #fff; }
    .task-seg input:focus-visible + span { box-shadow: inset 0 0 0 2px var(--erp-accent); }
    @media (max-width: 640px) { .task-modal-grid { grid-template-columns: 1fr; } .task-modal-head { flex-direction: column; align-items: stretch; } }
    </style>
    @endpush
@endonce

<div class="task-modal-overlay {{ $reopen ? 'active' : '' }}" id="{{ $uid }}" data-task-modal="{{ $mode }}">
    <div class="task-modal-box" role="dialog" aria-modal="true" aria-labelledby="{{ $uid }}Title">
        <form method="POST" action="{{ $isEdit ? route('admin.tasks.update', $task) : route('admin.tasks.store') }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $mode }}">
            <div class="task-modal-head">
                <div>
                    <p class="task-modal-eyebrow">{{ $isEdit ? 'Editar · TAR-' . str_pad((string) $task->id, 4, '0', STR_PAD_LEFT) : 'Seguimiento' }}</p>
                    <h2 class="task-modal-title" id="{{ $uid }}Title">{{ $isEdit ? 'Editar tarea' : 'Nueva tarea' }}</h2>
                </div>
                <div class="task-modal-head-actions">
                    <button type="button" class="erp-btn" data-task-modal-close>Cancelar</button>
                    <button type="submit" class="erp-btn erp-btn--primary">Guardar</button>
                </div>
            </div>
            @if ($reopen) <div class="task-modal-error" role="alert">{{ $errors->first() }}</div> @endif
            <div class="task-modal-grid">
                <div class="task-modal-field task-modal-field--full">
                    <label for="{{ $uid }}Name">Tarea <span class="req">*</span></label>
                    <input type="text" id="{{ $uid }}Name" name="title" required maxlength="255" value="{{ old('title', $isEdit ? $task->title : '') }}">
                </div>
                <div class="task-modal-field">
                    <label for="{{ $uid }}Related">Relacionado con</label>
                    <input type="text" id="{{ $uid }}Related" name="related_reference" maxlength="255" value="{{ old('related_reference', $isEdit ? $task->related_reference : '') }}" placeholder="Folio de negocio, cotización o cliente">
                </div>
                <div class="task-modal-field">
                    <label for="{{ $uid }}Type">Tipo</label>
                    <select id="{{ $uid }}Type" name="type">
                        <option value="">Selecciona...</option>
                        @foreach (\App\Models\Task::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('type', $isEdit ? $task->type : '') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-modal-field">
                    <label for="{{ $uid }}Assignee">Responsable @unless ($isEdit)<span class="req">*</span>@endunless</label>
                    <select id="{{ $uid }}Assignee" name="assigned_to" @required(! $isEdit)>
                        <option value="">{{ $isEdit ? 'Sin asignar' : 'Selecciona...' }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to', $isEdit ? $task->assigned_to : '') === (string) $user->id)>{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-modal-field">
                    <label for="{{ $uid }}Due">Vence (fecha y hora, 24 h)</label>
                    <input type="text" id="{{ $uid }}Due" name="due_at" value="{{ $dueValue }}" data-due-picker>
                </div>
                @if ($isEdit)
                    <div class="task-modal-field">
                        <label for="{{ $uid }}Status">Estado</label>
                        <select id="{{ $uid }}Status" name="status">
                            @foreach (\App\Models\Task::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="task-modal-field task-modal-field--full">
                    <span class="task-modal-label">Prioridad</span>
                    <div class="task-seg" role="radiogroup" aria-label="Prioridad">
                        @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                            <label><input type="radio" name="priority" value="{{ $value }}" @checked($priorityValue === $value)><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="task-modal-field task-modal-field--full">
                    <label for="{{ $uid }}Desc">Descripción</label>
                    <textarea id="{{ $uid }}Desc" name="description">{{ old('description', $isEdit ? $task->description : '') }}</textarea>
                </div>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
    @vite(['resources/js/admin/task-date-picker.js'])
    <script>
    (function () {
        function overlayFor(mode) { return document.querySelector('[data-task-modal="' + mode + '"]'); }
        document.querySelectorAll('[data-task-modal-open]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var o = overlayFor(btn.getAttribute('data-task-modal-open'));
                if (o) { o.classList.add('active'); var f = o.querySelector('input[type="text"]'); if (f) f.focus(); }
            });
        });
        document.querySelectorAll('[data-task-modal]').forEach(function (o) {
            o.addEventListener('click', function (e) { if (e.target === o) o.classList.remove('active'); });
            o.querySelectorAll('[data-task-modal-close]').forEach(function (b) { b.addEventListener('click', function () { o.classList.remove('active'); }); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('[data-task-modal].active').forEach(function (o) { o.classList.remove('active'); });
        });
    })();
    </script>
    @endpush
@endonce
