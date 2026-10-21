@extends('admin.layouts.master')

@section('title', 'Tarea ' . $task->title . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
<style>
.task-page { flex: 1; min-height: 0; overflow-y: auto; }
.task-page .erp-page { max-width: none !important; margin: 0 !important; }
.task-inline-form { display: inline; margin: 0; }
.task-desc { white-space: pre-line; margin: 0; color: var(--erp-text); font-size: 13.5px; line-height: 1.55; }
.task-form label { display: block; font-size: 12px; font-weight: 700; color: var(--erp-muted); margin: 0 0 4px; }
.task-form .erp-select, .task-form textarea { width: 100%; box-sizing: border-box; border: 1px solid #d4d7dc; border-radius: 8px; padding: 9px 11px; font-size: 13.5px; font-family: inherit; color: var(--erp-ink); background: #fff; }
.task-form textarea { min-height: 120px; resize: vertical; }
.task-form textarea:focus { outline: none; border-color: var(--erp-accent); box-shadow: 0 0 0 3px rgba(249,115,22,.12); }
.task-form .task-field { margin-bottom: 14px; }
.task-items { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
.task-item { display: flex; align-items: center; gap: 10px; }
.task-item img, .task-item-ph { width: 34px; height: 34px; object-fit: cover; border-radius: 6px; border: 1px solid var(--erp-line); background: var(--erp-bg); flex-shrink: 0; }
.task-tl { list-style: none; margin: 0; padding: 0; }
.task-tl-item { position: relative; padding: 0 0 22px 28px; }
.task-tl-item::before { content: ''; position: absolute; left: 4px; top: 14px; bottom: -4px; width: 1px; background: var(--erp-line); }
.task-tl-item:last-child { padding-bottom: 0; }
.task-tl-item:last-child::before { display: none; }
.task-tl-dot { position: absolute; left: 0; top: 5px; width: 9px; height: 9px; border-radius: 50%; background: var(--erp-neu-dot); }
.task-tl-title { font-weight: 700; font-size: 13.5px; color: var(--erp-ink); }
.task-tl-meta { font-size: 12.5px; color: var(--erp-muted); margin-top: 2px; }
.task-mail { margin-top: 8px; border: 1px solid var(--erp-line); border-radius: 10px; background: #fff; }
.task-mail > summary { cursor: pointer; list-style: none; padding: 8px 12px; font-size: 12.5px; font-weight: 700; color: var(--erp-accent-dark); }
.task-mail > summary::-webkit-details-marker { display: none; }
.task-mail > summary::before { content: '\25B8'; display: inline-block; margin-right: 6px; transition: transform .15s; }
.task-mail[open] > summary::before { transform: rotate(90deg); }
.task-mail-head { padding: 0 12px 8px; display: grid; grid-template-columns: auto 1fr; gap: 2px 12px; font-size: 12.5px; border-bottom: 1px solid var(--erp-line-soft); }
.task-mail-head dt { color: var(--erp-muted); font-weight: 700; }
.task-mail-head dd { margin: 0; color: var(--erp-ink); word-break: break-word; }
.task-mail-body { white-space: pre-wrap; word-break: break-word; padding: 12px; font-size: 13px; line-height: 1.55; color: var(--erp-text); max-height: 420px; overflow-y: auto; }
.task-tl-body { white-space: pre-line; margin-top: 6px; padding: 8px 12px; border-radius: 8px; background: var(--erp-bg); font-size: 13px; color: var(--erp-text); }
.task-notes-list { display: flex; flex-direction: column; gap: 10px; margin-top: 18px; }
.task-note { background: var(--erp-bg); border-radius: 10px; padding: 12px 14px; }
.task-note-text { white-space: pre-line; font-size: 13.5px; color: var(--erp-text); line-height: 1.5; }
.task-note-meta { font-size: 12px; color: var(--erp-muted); margin-top: 6px; }
.task-modal { position: fixed; inset: 0; z-index: 10000; display: none; align-items: center; justify-content: center; background: rgba(15,23,42,.45); padding: 16px; }
.task-modal.is-open { display: flex; }
.task-modal-card { background: #fff; border-radius: 14px; padding: 20px; width: 100%; max-width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.task-modal-card h3 { margin: 0 0 14px; font-size: 16px; }
.task-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.task-alert { padding: 10px 14px; margin-bottom: 12px; border-radius: 10px; font-size: 13px; }
.task-alert--ok { color: var(--erp-ok); background: var(--erp-ok-bg); }
.task-alert--bad { color: var(--erp-bad); background: var(--erp-bad-bg); }
</style>
@endpush

@section('content')
@php
    $statusTone = ['open' => 'info', 'pending' => 'warn', 'in_progress' => 'acc', 'in_review' => 'info', 'closed' => 'ok'];
    $closed = $task->status === 'closed';
    $folio = 'TAR-' . str_pad((string) $task->id, 4, '0', STR_PAD_LEFT);
    $customer = $task->taskableCustomerInfo();
    $quick = $task->taskableQuickView();
    $items = $task->taskableQuoteItems() ?? [];
    $relatedLabel = $task->taskableLabel();
    $relatedUrl = $task->taskableUrl();
    $subtitle = $relatedLabel ?: ($task->related_reference ?: ($email ? 'Correo de ' . $email->from_email : null));
    $typeLabel = \App\Models\Task::TYPES[$task->type] ?? '—';
    $priorityLabel = \App\Models\Task::PRIORITIES[$task->priority] ?? '—';
    $notesCount = count($notes ?? []);
    $due = $task->due_at ? $task->due_at->format('d/m/Y H:i') : '—';
@endphp
<div class="task-page">
<div class="erp-page">
    <x-erp.object-header
        :title="$task->title"
        :eyebrow="'TAREA · ' . $folio"
        :subtitle="$subtitle"
        :status="$task->trashed() ? 'Eliminada' : \App\Models\Task::statusLabel($task->status)"
        :tone="$task->trashed() ? 'bad' : ($statusTone[$task->status] ?? 'neu')"
        :back="route('admin.tasks.index')"
        :facts="[
            ['label' => 'Origen', 'value' => $origin],
            ['label' => 'Tipo', 'value' => $typeLabel],
            ['label' => 'Responsable', 'value' => $task->assignee?->full_name ?? 'Sin asignar'],
            ['label' => 'Vence', 'value' => $due],
            ['label' => 'Prioridad', 'value' => $priorityLabel],
        ]">
        @unless ($task->trashed())
            <button type="button" class="erp-btn" data-task-modal-open="edit">Editar</button>
            <button type="button" class="erp-btn" onclick="document.getElementById('taskReassignModal').classList.add('is-open')">Reasignar</button>
            <form method="POST" action="{{ route('admin.tasks.update', $task) }}" class="task-inline-form">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="{{ $closed ? 'open' : 'closed' }}">
                <button type="submit" class="erp-btn {{ $closed ? '' : 'erp-btn--primary' }}">{{ $closed ? 'Reabrir' : 'Completar' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" class="task-inline-form" onsubmit="return confirm('¿Eliminar esta tarea? Esto no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endunless
    </x-erp.object-header>

    @unless ($task->trashed())
        <div class="task-modal" id="taskReassignModal" onclick="if (event.target === this) this.classList.remove('is-open')">
            <form method="POST" action="{{ route('admin.tasks.update', $task) }}" class="task-modal-card task-form">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="{{ $task->status }}">
                <h3>Reasignar tarea</h3>
                <div class="task-field">
                    <label for="taskReassignTo">Responsable</label>
                    <select id="taskReassignTo" name="assigned_to" class="erp-select">
                        <option value="">Sin asignar</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int) $task->assigned_to === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="task-modal-actions">
                    <button type="button" class="erp-btn" onclick="document.getElementById('taskReassignModal').classList.remove('is-open')">Cancelar</button>
                    <button type="submit" class="erp-btn erp-btn--primary">Guardar</button>
                </div>
            </form>
        </div>
        <script>document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var m = document.getElementById('taskReassignModal'); if (m) m.classList.remove('is-open'); } });</script>
        @include('admin.tasks._form_modal', ['mode' => 'edit', 'task' => $task, 'users' => $users])
    @endunless

    @if (session('success')) <div class="task-alert task-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="task-alert task-alert--bad" role="alert">{{ $errors->first() }}</div> @endif

    <section class="erp-card" data-erp-tabs>
        <x-erp.tab-nav :tabs="['general' => 'General', 'historial' => 'Historial', 'notas' => 'Notas' . ($notesCount ? ' (' . $notesCount . ')' : '')]" active="general" />

        <div class="erp-tabpanel is-active" data-panel="general">
            <div class="erp-card-body">
                <div class="erp-grid erp-grid-2">
                    <div>
                        <x-erp.facts :items="[
                            ['label' => 'Folio', 'value' => $folio],
                            ['label' => 'Estado', 'value' => $task->trashed() ? 'Eliminada' : \App\Models\Task::statusLabel($task->status)],
                            ['label' => 'Origen', 'value' => $origin],
                            ['label' => 'Tipo', 'value' => $typeLabel],
                            ['label' => 'Prioridad', 'value' => $priorityLabel],
                            ['label' => 'Responsable', 'value' => $task->assignee?->full_name ?? 'Sin asignar'],
                            ['label' => 'Vence', 'value' => $due],
                            ['label' => 'Cerrada el', 'value' => $task->closed_at?->format('d/m/Y H:i') ?? '—'],
                        ]" />

                        <h3 class="erp-card-title" style="margin:22px 0 6px;">Descripción</h3>
                        @if ($task->description)
                            <p class="task-desc">{{ $task->description }}</p>
                        @else
                            <p class="erp-cell-sub" style="margin:0;">Sin descripción.</p>
                        @endif
                    </div>

                    <div>
                        @unless ($task->trashed())
                            <form method="POST" action="{{ route('admin.tasks.update', $task) }}" class="task-form">
                                @csrf @method('PUT')
                                <div class="task-field">
                                    <label for="taskStatus">Estado</label>
                                    <select id="taskStatus" name="status" class="erp-select">
                                        @foreach (\App\Models\Task::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="task-field">
                                    <label for="taskAssignee">Responsable</label>
                                    <select id="taskAssignee" name="assigned_to" class="erp-select">
                                        <option value="">Sin asignar</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->id }}" @selected((int) $task->assigned_to === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="task-field">
                                    <label for="taskDescription">Descripción</label>
                                    <textarea id="taskDescription" name="description">{{ old('description', $task->description) }}</textarea>
                                </div>
                                <button type="submit" class="erp-btn erp-btn--primary">Guardar cambios</button>
                            </form>
                        @endunless
                    </div>
                </div>
            </div>
        </div>

        <div class="erp-tabpanel" data-panel="historial">
            <div class="erp-card-body">
                @if (count($timeline))
                    <ul class="task-tl">
                        @foreach ($timeline as $item)
                            <li class="task-tl-item">
                                <span class="task-tl-dot"></span>
                                <div class="task-tl-title">{{ $item['title'] }}</div>
                                <div class="task-tl-meta">{{ $item['actor'] }} · {{ str_replace('.', '', $item['at']->locale('es')->translatedFormat('d M Y · H:i')) }}</div>
                                @if (! empty($item['body']))<div class="task-tl-body">{{ $item['body'] }}</div>@endif
                                @if (! empty($item['email']))
                                    @php $mail = $item['email']; @endphp
                                    <details class="task-mail">
                                        <summary>Ver correo enviado</summary>
                                        <dl class="task-mail-head">
                                            <dt>De</dt><dd>{{ $mail['from'] ?: '—' }}</dd>
                                            <dt>Para</dt><dd>{{ implode(', ', (array) $mail['to']) ?: '—' }}</dd>
                                            <dt>Fecha</dt><dd>{{ $mail['at']->format('d/m/Y H:i') }}</dd>
                                            <dt>Asunto</dt><dd>{{ $mail['subject'] ?: '(sin asunto)' }}</dd>
                                            @if (count($mail['attachments']))<dt>Adjuntos</dt><dd>{{ implode(', ', $mail['attachments']) }}</dd>@endif
                                        </dl>
                                        <div class="task-mail-body">{{ trim((string) $mail['body']) !== '' ? trim($mail['body']) : '(sin contenido de texto)' }}</div>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="erp-cell-sub" style="margin:0;">Sin actividad registrada.</p>
                @endif
            </div>
        </div>

        <div class="erp-tabpanel" data-panel="notas">
            <div class="erp-card-body">
                @unless ($task->trashed())
                    <form method="POST" action="{{ route('admin.tasks.notes.store', $task) }}" class="task-form">
                        @csrf
                        <div class="task-field">
                            <textarea name="body" required maxlength="2000" placeholder="Escribe una nota interna...">{{ old('body') }}</textarea>
                        </div>
                        <button type="submit" class="erp-btn erp-btn--primary">Agregar nota</button>
                    </form>
                @endunless
                @if ($notesCount)
                    <div class="task-notes-list">
                        @foreach ($notes as $note)
                            <div class="task-note">
                                <div class="task-note-text">{{ $note->body }}</div>
                                <div class="task-note-meta">{{ $note->author?->full_name ?? 'Sistema' }} · {{ str_replace('.', '', $note->created_at->locale('es')->translatedFormat('d M Y')) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="erp-cell-sub" style="margin:14px 0 0;">Sin notas.</p>
                @endif
            </div>
        </div>
    </section>

    @if ($relatedLabel)
        <x-erp.card title="Relacionado con" :sub="$relatedLabel">
            <x-slot:actions>
                @if ($relatedUrl)<a href="{{ $relatedUrl }}" class="erp-btn erp-btn--sm">Abrir</a>@endif
            </x-slot:actions>
            @if ($customer)
                <x-erp.facts :items="array_values(array_filter([
                    ['label' => 'Cliente', 'value' => $customer['name'] ?? null],
                    ['label' => 'Empresa', 'value' => $customer['company'] ?? null],
                    ['label' => 'Teléfono', 'value' => $customer['phone'] ?? null],
                    ['label' => 'Correo', 'value' => $customer['email'] ?? null],
                    ['label' => 'RFC', 'value' => $customer['rfc'] ?? null],
                ], fn ($f) => ! empty($f['value'])))" />
            @elseif ($lead)
                <x-erp.facts :items="array_values(array_filter([
                    ['label' => 'Contacto', 'value' => $lead->contact_name],
                    ['label' => 'Correo', 'value' => $lead->contact_email],
                    ['label' => 'Teléfono', 'value' => $lead->contact_phone],
                    ['label' => 'Origen', 'value' => $lead->source_label],
                    ['label' => 'Estado del lead', 'value' => $lead->status_label],
                ], fn ($f) => ! empty($f['value'])))" />
            @endif
            @if ($quick)
                <x-erp.facts style="margin-top:14px;" :items="array_values(array_filter([
                    ['label' => 'Cotización', 'value' => $quick['quote_number'] ?? null],
                    ['label' => 'Estatus', 'value' => $quick['status_label'] ?? null],
                    ['label' => 'Total', 'value' => $quick['total'] ?? null],
                    ['label' => 'Enviada', 'value' => $quick['sent_at'] ?? null],
                    ['label' => 'Vigente hasta', 'value' => $quick['valid_until'] ?? null],
                ], fn ($f) => ! empty($f['value'])))" />
            @endif
            @if (count($items))
                <div class="task-items">
                    @foreach ($items as $item)
                        <div class="task-item">
                            @if (! empty($item['image_url']))<img src="{{ $item['image_url'] }}" alt="">@else<span class="task-item-ph"></span>@endif
                            <div>
                                <div class="erp-cell-main">{{ $item['name'] }}</div>
                                <div class="erp-cell-sub">{{ $item['sku'] ?? 'Sin SKU' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-erp.card>
    @endif
</div>
</div>
@endsection
