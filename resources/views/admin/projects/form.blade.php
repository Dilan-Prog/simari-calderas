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
<div class="container user-manager">
<section class="clients-manager-section prj-page">
    <div class="erp-page-head">
        <div>
            <p class="breadcrumb-clients-manager" style="margin-bottom:4px;">
                Panel de Control &gt; <a href="{{ route('admin.projects.index') }}">Proyectos</a> &gt; <strong>{{ $isEdit ? $project->project_number : 'Nuevo' }}</strong>
            </p>
            <h1 class="erp-title">{{ $isEdit ? 'Editar proyecto' : 'Nuevo proyecto' }}</h1>
            @if (!empty($preset['quote_number']))
                <p class="erp-subtitle">Se asociará a la cotización {{ $preset['quote_number'] }}.</p>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="prj-alert">
            <strong>Revisa los campos:</strong>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEdit ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="prj-form" data-project-form>
        @csrf
        @if ($isEdit) @method('PUT') @endif
        @if (!empty($preset['quote_id'])) <input type="hidden" name="from_quote" value="{{ $preset['quote_id'] }}"> @endif

        <div class="prj-panel">
            <h2>General</h2>
            <div class="prj-grid">
                <label class="prj-span2">Nombre del proyecto *
                    <input type="text" name="name" class="users-manager-input" required maxlength="255" value="{{ old('name', $project->name) }}">
                </label>

                <label class="prj-span2 prj-customer" data-customer-picker data-url="{{ route('admin.projects.customers') }}">Cliente
                    <input type="hidden" name="customer_id" value="{{ $customerId }}" data-customer-id>
                    <input type="text" class="users-manager-input" placeholder="Buscar cliente..." autocomplete="off" value="{{ $customerLabel }}" data-customer-input>
                    <div class="prj-suggest" data-customer-list hidden></div>
                    <small class="prj-muted"><a href="#" data-customer-clear>Quitar cliente</a></small>
                </label>

                <label>Empresa (texto libre)
                    <input type="text" name="company" class="users-manager-input" maxlength="255" value="{{ old('company', $project->company ?? ($preset['company'] ?? '')) }}">
                </label>
                <label>Persona a cargo
                    <select name="manager_user_id" class="users-manager-input">
                        <option value="">— Sin asignar —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" @selected((int) old('manager_user_id', $project->manager_user_id) === $u->id)>{{ $u->first_name }} {{ $u->last_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Estado
                    <select name="status" class="users-manager-input">
                        @foreach (\App\Models\Project::STATUSES as $k => $l)
                            <option value="{{ $k }}" @selected(old('status', $project->status) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Tiempo estimado (texto)
                    <input type="text" name="estimated_time" class="users-manager-input" maxlength="255" placeholder="Ej. 3 semanas" value="{{ old('estimated_time', $project->estimated_time) }}">
                </label>
                <label>Duración estimada (días)
                    <input type="number" min="0" name="estimated_duration_days" class="users-manager-input" value="{{ old('estimated_duration_days', $project->estimated_duration_days) }}">
                </label>
                <label>Fecha de inicio
                    <input type="date" name="start_date" class="users-manager-input" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}">
                </label>
                <label>Fecha límite
                    <input type="date" name="due_date" class="users-manager-input" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}">
                </label>
                <label class="prj-span2">Descripción
                    <textarea name="description" rows="3" class="users-manager-input">{{ old('description', $project->description) }}</textarea>
                </label>
                <label class="prj-span2">Notas internas
                    <textarea name="notes" rows="2" class="users-manager-input">{{ old('notes', $project->notes) }}</textarea>
                </label>
            </div>
        </div>

        <div class="prj-panel">
            <div class="prj-panel-head">
                <h2>Fases</h2>
                <button type="button" class="prj-btn" data-phase-add>+ Agregar fase</button>
            </div>
            <div data-phase-list>
                @foreach ($phaseRows as $i => $phase)
                    @include('admin.projects._phase_row', ['i' => $i, 'phase' => $phase])
                @endforeach
            </div>
            <p class="prj-muted" data-phase-empty @if (count($phaseRows)) hidden @endif>Aún no hay fases. Agrega las etapas del proyecto con sus encargados y requisitos.</p>
            <template data-phase-template>
                @include('admin.projects._phase_row', ['i' => '__IDX__', 'phase' => null])
            </template>
        </div>

        <div class="prj-actions">
            <a href="{{ $isEdit ? route('admin.projects.show', $project) : route('admin.projects.index') }}" class="prj-btn">Cancelar</a>
            <button type="submit" class="prj-btn prj-btn-primary">{{ $isEdit ? 'Guardar cambios' : 'Crear proyecto' }}</button>
        </div>
    </form>
</section>
</div>
@endsection
