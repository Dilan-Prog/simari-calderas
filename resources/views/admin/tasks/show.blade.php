@extends('admin.layouts.master')

@section('title', 'Tarea ' . $task->title . ' - Admin')

@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css'])
<style>
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
    $subtitle = $relatedLabel ?: ($email ? 'Correo de ' . $email->from_email : null);
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
            ['label' => 'Responsable', 'value' => $task->assignee?->full_name ?? 'Sin asignar'],
            ['label' => 'Vence', 'value' => $due],
            ['label' => 'Creada', 'value' => $task->created_at->format('d/m/Y H:i')],
        ]">
        @unless ($task->trashed())
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

    @if (session('success')) <div class="task-alert task-alert--ok" role="status">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="task-alert task-alert--bad" role="alert">{{ $errors->first() }}</div> @endif

    <section class="erp-card" data-erp-tabs>
        <x-erp.tab-nav :tabs="['general' => 'General', 'historial' => 'Historial']" active="general" />

        <div class="erp-tabpanel is-active" data-panel="general">
            <div class="erp-card-body">
                <div class="erp-grid erp-grid-2">
                    <div>
                        <x-erp.facts :items="[
                            ['label' => 'Folio', 'value' => $folio],
                            ['label' => 'Estado', 'value' => $task->trashed() ? 'Eliminada' : \App\Models\Task::statusLabel($task->status)],
                            ['label' => 'Origen', 'value' => $origin],
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
                <x-erp.timeline :items="$timeline" />
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
