@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-projects.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-projects.js'])
@endpush
@section('title')
    {{ $project->exists ? 'Editar ' . $project->project_number : 'Nuevo proyecto' }} - Proyectos - Admin
@endsection
@section('content')
@php
    $isEdit = $project->exists;
    $customerLabel = $project->customer ? trim($project->customer->first_name . ' ' . $project->customer->last_name) : ($preset['customer'] ?? '');
    $customerId = old('customer_id', $project->customer_id ?? ($preset['customer_id'] ?? ''));
    $oldPhases = old('phases');
    $phaseRows = is_array($oldPhases)
        ? collect($oldPhases)->values()->map(fn ($p) => (object) [
            'name' => $p['name'] ?? null, 'status' => $p['status'] ?? 'pendiente', 'progress' => $p['progress'] ?? 0,
            'description' => $p['description'] ?? null, 'requirements' => $p['requirements'] ?? null,
            'start_date' => $p['start_date'] ?? null, 'end_date' => $p['end_date'] ?? null,
            'assignees' => collect($p['assignees'] ?? [])->map(fn ($id) => (object) ['id' => (int) $id]),
        ])
        : $phases;
@endphp
<div class="erp-page prj-page">
    <x-erp.page-header
        :title="$isEdit ? 'Editar proyecto' : 'Nuevo proyecto'"
        :desc="!empty($preset['quote_number']) ? 'Se asociará a la cotización ' . $preset['quote_number'] . '.' : null"
        :crumbs="[['Planeación', null], ['Proyectos', route('admin.projects.index')], [$isEdit ? $project->project_number : 'Nuevo', null]]" />

    @if ($errors->any())
        <div class="prj-alert" role="alert">
            <strong>Revisa los campos:</strong>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="prj-form" data-project-form>
        @csrf
        @if ($isEdit) @method('PUT') @endif
        @if (!empty($preset['quote_id'])) <input type="hidden" name="from_quote" value="{{ $preset['quote_id'] }}"> @endif

        <x-erp.card title="General" class="prj-card">
            <div class="erp-form-grid prj-grid">
                <label class="erp-field prj-span2">Nombre del proyecto *
                    <input type="text" name="name" class="erp-input" required maxlength="255" value="{{ old('name', $project->name) }}">
                </label>

                <label class="erp-field prj-span2 prj-customer" data-customer-picker data-url="{{ route('admin.projects.customers') }}">Cliente
                    <input type="hidden" name="customer_id" value="{{ $customerId }}" data-customer-id>
                    <input type="text" class="erp-input" placeholder="Buscar cliente..." autocomplete="off" value="{{ $customerLabel }}" data-customer-input>
                    <div class="prj-suggest" data-customer-list hidden></div>
                    <small><a href="#" data-customer-clear>Quitar cliente</a></small>
                </label>

                <label class="erp-field">Empresa (texto libre)
                    <input type="text" name="company" class="erp-input" maxlength="255" value="{{ old('company', $project->company ?? ($preset['company'] ?? '')) }}">
                </label>
                <label class="erp-field">Persona a cargo
                    <select name="manager_user_id" class="erp-select">
                        <option value="">— Sin asignar —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('manager_user_id', $project->manager_user_id) === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Estado
                    <select name="status" class="erp-select">
                        @foreach (\App\Models\Project::STATUSES as $k => $l)
                            <option value="{{ $k }}" @selected(old('status', $project->status) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="erp-field">Tiempo estimado (texto)
                    <input type="text" name="estimated_time" class="erp-input" maxlength="255" placeholder="Ej. 3 semanas" value="{{ old('estimated_time', $project->estimated_time) }}">
                </label>
                <label class="erp-field">Duración estimada (días)
                    <input type="number" min="0" name="estimated_duration_days" class="erp-input" value="{{ old('estimated_duration_days', $project->estimated_duration_days) }}">
                </label>
                <label class="erp-field">Fecha de inicio
                    <input type="date" name="start_date" class="erp-input" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
                </label>
                <label class="erp-field">Fecha límite
                    <input type="date" name="due_date" class="erp-input" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}">
                </label>
                <label class="erp-field prj-span2">Descripción
                    <textarea name="description" rows="3" class="erp-input">{{ old('description', $project->description) }}</textarea>
                </label>
                <label class="erp-field prj-span2">Notas internas
                    <textarea name="notes" rows="2" class="erp-input">{{ old('notes', $project->notes) }}</textarea>
                </label>
            </div>
        </x-erp.card>

        <x-erp.card title="Fases" sub="Etapas del proyecto con sus encargados y requisitos." class="prj-card">
            <x-slot:actions>
                <button type="button" class="erp-btn erp-btn--sm" data-phase-add>+ Agregar fase</button>
            </x-slot:actions>
            <div data-phase-list>
                @foreach ($phaseRows as $i => $phase)
                    @include('admin.projects._phase_row', ['i' => $i, 'phase' => $phase])
                @endforeach
            </div>
            <p class="prj-muted" data-phase-empty @if (count($phaseRows)) hidden @endif>Aún no hay fases. Agrega las etapas del proyecto con sus encargados y requisitos.</p>
            <template data-phase-template>
                @include('admin.projects._phase_row', ['i' => '__IDX__', 'phase' => null])
            </template>
        </x-erp.card>

        <div class="erp-form-actions">
            <a href="{{ $isEdit ? route('admin.projects.show', $project) : route('admin.projects.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $isEdit ? 'Guardar cambios' : 'Crear proyecto' }}</button>
        </div>
    </form>
</div>
@endsection
