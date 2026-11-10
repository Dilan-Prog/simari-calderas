{{--
    Modal de tarea: crear (mode=create) o editar (mode=edit, con $task).
      Variables: $mode, $users, $task (solo edit).
    Se abre con cualquier elemento que tenga data-task-modal-open="create|edit".
--}}
@php
    $isEdit = $mode === 'edit';
    $isLead = $mode === 'lead';
    $uid = 'taskModal' . ucfirst($mode);
    $reopen = $errors->any() && old('_modal') === $mode;
    $val = fn (string $field, $fallback = '') => old($field, $isEdit ? ($fallback) : '');
    $dueValue = old('due_at', $isEdit ? $task->due_at?->format('Y-m-d H:i') : '');
    $priorityValue = old('priority', $isEdit ? ($task->priority ?? '') : 'media');
@endphp
@once
    @push('styles')
    <style>
    /* Escritorio: caja centrada; encabezado (con acciones) fijo y solo el cuerpo con scroll. */
    .task-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(23,25,28,.55); z-index: 10000; align-items: center; justify-content: center; padding: 16px; }
    .task-modal-overlay.active { display: flex; }
    .task-modal-box { background: var(--erp-surface); border-radius: 16px; width: 100%; max-width: 720px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; padding: 0; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
    .task-modal-box > form { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; margin: 0; }
    .task-modal-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-shrink: 0; padding: 24px 26px 15px; }
    .task-modal-scroll { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; padding: 3px 26px 24px; }
    .task-modal-x { display: none; }
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
    .task-seg label { position: relative; margin: 0; cursor: pointer; }
    .task-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .task-seg span { display: block; padding: 8px 18px; font-size: 13px; font-weight: 700; color: var(--erp-text); background: #fff; border-right: 1px solid #d4d7dc; }
    .task-seg label:last-child span { border-right: 0; }
    .task-seg input:checked + span { background: var(--erp-ink); color: #fff; }
    .task-seg input:focus-visible + span { box-shadow: inset 0 0 0 2px var(--erp-accent); }

    /* Fondo bloqueado mientras el modal está abierto (clase puesta por el script de abajo). */
    body.task-modal-open { overflow: hidden; }

    /* Celular: pantalla completa; título + cerrar arriba, Cancelar/Guardar abajo, cuerpo con scroll en medio.
       .task-modal-head usa display: contents para que título y acciones se reordenen con `order`
       sin duplicar botones (el escritorio no cambia). */
    @media (max-width: 640px) {
        .task-modal-overlay { padding: 0; align-items: stretch; justify-content: stretch; }
        .task-modal-box { width: 100vw; max-width: none; height: 100vh; height: 100dvh; max-height: none; border-radius: 0; box-shadow: none; }
        .task-modal-head { display: contents; }
        .task-modal-headline { order: 0; flex-shrink: 0; display: flex; align-items: center; gap: 12px; padding: calc(12px + env(safe-area-inset-top)) calc(16px + env(safe-area-inset-right)) 12px calc(16px + env(safe-area-inset-left)); border-bottom: 1px solid var(--erp-line, #e6e8eb); }
        .task-modal-heading { flex: 1 1 auto; min-width: 0; }
        .task-modal-title { font-size: 20px; overflow-wrap: anywhere; }
        .task-modal-eyebrow { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .task-modal-x { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 44px; height: 44px; padding: 0; border: 1px solid var(--erp-line, #e6e8eb); border-radius: 10px; background: transparent; color: var(--erp-ink); font-size: 26px; line-height: 1; cursor: pointer; }
        .task-modal-scroll { order: 1; padding: 16px calc(16px + env(safe-area-inset-right)) 20px calc(16px + env(safe-area-inset-left)); }
        .task-modal-head-actions { order: 2; display: flex; gap: 10px; padding: 12px calc(16px + env(safe-area-inset-right)) calc(12px + env(safe-area-inset-bottom)) calc(16px + env(safe-area-inset-left)); border-top: 1px solid var(--erp-line, #e6e8eb); background: var(--erp-surface, #fff); }
        .task-modal-head-actions .erp-btn { flex: 1 1 0; min-height: 46px; justify-content: center; font-size: 15px; }
        .task-modal-grid { grid-template-columns: 1fr; gap: 14px; }
        .task-modal-field input[type="text"], .task-modal-field input[type="date"], .task-modal-field select, .task-modal-field textarea { font-size: 16px; min-height: 44px; }
        .task-seg { display: flex; width: 100%; box-sizing: border-box; }
        .task-seg label { flex: 1 1 0; min-width: 0; }
        .task-seg span { display: flex; align-items: center; justify-content: center; min-height: 44px; padding: 8px 6px; text-align: center; font-size: 14px; }
        .task-modal-box .task-due-quick button { min-height: 36px; padding: 6px 13px; font-size: 13px; }

        /* Calendario (flatpickr, se monta en <body>): con el modal abierto se fija abajo y centrado
           para no salirse de los 390px ni quedar recortado por el teclado/scroll. */
        body.task-modal-open .flatpickr-calendar.open { position: fixed !important; top: auto !important; right: auto !important; left: 50% !important; bottom: calc(12px + env(safe-area-inset-bottom)) !important; transform: translateX(-50%); width: min(320px, calc(100vw - 24px)); max-width: calc(100vw - 24px); max-height: calc(100dvh - 24px); overflow-y: auto; box-sizing: border-box; animation: none; }
        body.task-modal-open .flatpickr-calendar .flatpickr-innerContainer, body.task-modal-open .flatpickr-calendar .flatpickr-rContainer, body.task-modal-open .flatpickr-calendar .flatpickr-days { width: 100%; }
        body.task-modal-open .flatpickr-calendar .flatpickr-weekdays { width: 100%; }
        body.task-modal-open .flatpickr-calendar input.cur-year, body.task-modal-open .flatpickr-calendar .flatpickr-monthDropdown-months { font-size: 16px; }
    }
    </style>
    @endpush
@endonce

<div class="task-modal-overlay {{ $reopen ? 'active' : '' }}" id="{{ $uid }}" data-task-modal="{{ $mode }}">
    <div class="task-modal-box" role="dialog" aria-modal="true" aria-labelledby="{{ $uid }}Title">
        <form method="POST" action="{{ $isEdit ? route('admin.tasks.update', $task) : ($action ?? route('admin.tasks.store')) }}">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="_modal" value="{{ $mode }}">
            <div class="task-modal-head">
                <div class="task-modal-headline">
                    <div class="task-modal-heading">
                        <p class="task-modal-eyebrow">{{ $isEdit ? 'Editar · TAR-' . str_pad((string) $task->id, 4, '0', STR_PAD_LEFT) : ($eyebrow ?? 'Seguimiento') }}</p>
                        <h2 class="task-modal-title" id="{{ $uid }}Title">{{ $isEdit ? 'Editar tarea' : ($modalTitle ?? 'Nueva tarea') }}</h2>
                    </div>
                    <button type="button" class="task-modal-x" data-task-modal-close aria-label="Cerrar">&times;</button>
                </div>
                <div class="task-modal-head-actions">
                    <button type="button" class="erp-btn" data-task-modal-close>Cancelar</button>
                    <button type="submit" class="erp-btn erp-btn--primary">Guardar</button>
                </div>
            </div>
            <div class="task-modal-scroll">
                @if ($reopen) <div class="task-modal-error" role="alert">{{ $errors->first() }}</div> @endif
            <div class="task-modal-grid">
                <div class="task-modal-field task-modal-field--full">
                    <label for="{{ $uid }}Name">Tarea <span class="req">*</span></label>
                    <input type="text" id="{{ $uid }}Name" name="title" required maxlength="255" value="{{ old('title', $isEdit ? $task->title : '') }}">
                </div>
                <div class="task-modal-field">
                    <label for="{{ $uid }}Related">Relacionado con</label>
                    @if ($isLead)
                        <input type="text" id="{{ $uid }}Related" value="{{ $relatedLabel ?? '' }}" disabled>
                    @else
                        <input type="text" id="{{ $uid }}Related" name="related_reference" maxlength="255" value="{{ old('related_reference', $isEdit ? $task->related_reference : '') }}" placeholder="Folio de negocio, cotización o cliente">
                    @endif
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
                            <option value="{{ $user->id }}" @selected((string) old('assigned_to', $isEdit ? $task->assigned_to : ($defaultAssignee ?? '')) === (string) $user->id)>{{ $user->full_name }}</option>
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
        // Bloquea el scroll del fondo mientras haya un modal de tarea abierto.
        function syncLock() { document.body.classList.toggle('task-modal-open', !!document.querySelector('[data-task-modal].active')); }
        function closeModal(o) { o.classList.remove('active'); syncLock(); }
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-task-modal-open]');
            if (!btn) return;
            var o = overlayFor(btn.getAttribute('data-task-modal-open'));
            if (!o) return;
            o.classList.add('active');
            syncLock();
            var sc = o.querySelector('.task-modal-scroll'); if (sc) sc.scrollTop = 0;
            // En celular no se enfoca para no abrir el teclado encima del formulario.
            if (!window.matchMedia('(max-width: 640px)').matches) { var f = o.querySelector('input[type="text"]'); if (f) f.focus(); }
        });
        document.querySelectorAll('[data-task-modal]').forEach(function (o) {
            o.addEventListener('click', function (e) { if (e.target === o) closeModal(o); });
            o.querySelectorAll('[data-task-modal-close]').forEach(function (b) { b.addEventListener('click', function () { closeModal(o); }); });
            var form = o.querySelector('form');
            if (form) form.addEventListener('submit', function () { document.body.classList.remove('task-modal-open'); });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('[data-task-modal].active').forEach(closeModal);
        });
        // Reapertura automática tras error de validación ($reopen): aplica el bloqueo también.
        syncLock();
    })();
    </script>
    @endpush
@endonce
