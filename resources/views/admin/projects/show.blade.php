@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-projects.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-projects.js'])
@endpush
@section('title')
    {{ $project->project_number }} - Proyectos - Admin
@endsection
@section('content')
@php
    use App\Models\Project;
    $canEdit = auth()->user()->hasPermission('projects', 'edit');
    $canDelete = auth()->user()->hasPermission('projects', 'delete');
    $days = $project->daysToDue();
    $dueText = $project->due_date
        ? $project->due_date->format('d/m/Y') . ($project->status !== 'cerrado' && $days !== null ? ($days < 0 ? ' (vencido hace ' . abs($days) . ' d)' : ($days === 0 ? ' (hoy)' : ' (faltan ' . $days . ' d)')) : '')
        : null;
    $steps = collect(Project::STATUSES)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values()->all();
    $fmtUser = fn ($u) => $u ? trim($u->first_name . ' ' . $u->last_name) : '—';
    $tabs = [
        'general' => 'General',
        'fases' => 'Fases (' . $project->phases->count() . ')',
        'cotizaciones' => 'Cotizaciones (' . $project->quotes->count() . ')',
        'servicios' => 'Servicios y reportes (' . ($project->technicalServices->count() + $project->serviceReports->count()) . ')',
        'pedidos' => 'Pedidos (' . $project->salesOrders->count() . ')',
        'historial' => 'Historial',
    ];
@endphp
<div class="container user-manager">
<section class="clients-manager-section prj-page">

    <x-erp.object-header
        :title="$project->project_number . ' · ' . $project->name"
        :subtitle="$project->display_customer"
        :status="Project::statusLabel($project->status)"
        :tone="Project::statusTone($project->status)"
        :back="route('admin.projects.index')"
        :facts="[
            ['label' => 'Responsable', 'value' => $fmtUser($project->manager)],
            ['label' => 'Fecha límite', 'value' => $dueText],
            ['label' => 'Avance', 'value' => $progress . '%'],
            ['label' => 'Tiempo estimado', 'value' => $project->estimated_time ?: ($project->estimated_duration_days ? $project->estimated_duration_days . ' días' : null)],
        ]">
        @if ($canEdit)
            <a href="{{ route('admin.projects.edit', $project) }}" class="prj-btn">Editar</a>
        @endif
        @if ($canDelete)
            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('¿Eliminar este proyecto? Los documentos asociados se conservan.');">
                @csrf @method('DELETE')
                <button type="submit" class="prj-btn prj-btn-danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <x-erp.document-flow :document="$project" />

    <div class="prj-panel prj-statusbar">
        <x-erp.process-chevrons :steps="$steps" :current="$project->status" />
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.projects.update-status', $project) }}" class="prj-status-form">
                @csrf @method('PATCH')
                <select name="status" class="users-manager-input">
                    @foreach (Project::STATUSES as $k => $l)
                        <option value="{{ $k }}" @selected($project->status === $k)>{{ $l }}</option>
                    @endforeach
                </select>
                <button type="submit" class="prj-btn prj-btn-primary">Cambiar estado</button>
            </form>
        @endif
    </div>

    <nav class="prj-tabs" role="tablist" data-prj-tabs>
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" class="prj-tab {{ $loop->first ? 'is-active' : '' }}" data-tab="{{ $key }}">{{ $label }}</button>
        @endforeach
    </nav>

    {{-- GENERAL --}}
    <div class="prj-tabpanel is-active" data-panel="general">
        <div class="prj-panel">
            <div class="prj-progress-wrap">
                <div class="prj-progress"><span style="width: {{ $progress }}%"></span></div>
                <strong>{{ $progress }}%</strong>
            </div>
            <dl class="prj-dl">
                <div><dt>Cliente</dt><dd>
                    @if ($project->customer) {{ $project->display_customer }} @else — @endif
                </dd></div>
                <div><dt>Empresa</dt><dd>{{ $project->company ?: '—' }}</dd></div>
                <div><dt>Persona a cargo</dt><dd>{{ $fmtUser($project->manager) }}</dd></div>
                <div><dt>Inicio</dt><dd>{{ $project->start_date?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt>Fecha límite</dt><dd><span class="prj-due prj-due-{{ $project->dueTone() }}"><i></i>{{ $dueText ?? '—' }}</span></dd></div>
                <div><dt>Duración estimada</dt><dd>{{ $project->estimated_duration_days ? $project->estimated_duration_days . ' días' : '—' }}</dd></div>
                <div><dt>Creado por</dt><dd>{{ $fmtUser($project->createdBy) }} · {{ $project->created_at?->format('d/m/Y') }}</dd></div>
            </dl>
            @if ($project->description)
                <h3 class="prj-h3">Descripción</h3>
                <p class="prj-text">{{ $project->description }}</p>
            @endif
            @if ($project->notes)
                <h3 class="prj-h3">Notas</h3>
                <p class="prj-text">{{ $project->notes }}</p>
            @endif
        </div>
        @include('admin.production-orders._project_panel')
    </div>

    {{-- FASES --}}
    <div class="prj-tabpanel" data-panel="fases" hidden>
        <div class="prj-panel">
            <h2>Línea de tiempo</h2>
            @if ($timeline['min'])
                <div class="prj-gantt">
                    <div class="prj-gantt-axis">
                        <span>{{ $timeline['min']->format('d/m/Y') }}</span>
                        <span>{{ $timeline['max']->format('d/m/Y') }}</span>
                    </div>
                    @foreach ($timeline['rows'] as $row)
                        @php $ph = $row['phase']; @endphp
                        <div class="prj-gantt-row">
                            <div class="prj-gantt-label" title="{{ $ph->name }}">{{ $ph->name }}</div>
                            <div class="prj-gantt-track">
                                @if ($timeline['today'] !== null)<i class="prj-gantt-today" style="left: {{ $timeline['today'] }}%"></i>@endif
                                @if ($row['width'] > 0)
                                    <div class="prj-gantt-bar prj-ph-{{ $ph->status }}" style="left: {{ $row['left'] }}%; width: {{ $row['width'] }}%"
                                         title="{{ $ph->start_date?->format('d/m/Y') }} – {{ $ph->end_date?->format('d/m/Y') }} · {{ $ph->progress }}%">
                                        <span class="prj-gantt-fill" style="width: {{ $ph->progress }}%"></span>
                                        <em>{{ $ph->progress }}%</em>
                                    </div>
                                @else
                                    <span class="prj-muted prj-gantt-nodates">Sin fechas</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="prj-muted">Define fechas en las fases para ver la línea de tiempo.</p>
            @endif
        </div>

        <div class="prj-panel">
            <h2>Detalle de fases</h2>
            @forelse ($project->phases as $ph)
                <article class="prj-phasecard">
                    <header>
                        <span class="prj-phase-num">{{ $loop->iteration }}</span>
                        <strong>{{ $ph->name }}</strong>
                        <span class="erp-chip erp-tone-{{ $ph->status === 'completada' ? 'ok' : ($ph->status === 'en_curso' ? 'warn' : 'neutral') }}">{{ \App\Models\ProjectPhase::STATUSES[$ph->status] ?? $ph->status }}</span>
                        <span class="prj-muted">{{ $ph->start_date?->format('d/m/Y') ?? '—' }} → {{ $ph->end_date?->format('d/m/Y') ?? '—' }}</span>
                    </header>
                    <div class="prj-progress prj-progress-sm"><span style="width: {{ $ph->progress }}%"></span></div>
                    @if ($ph->description)<p class="prj-text">{{ $ph->description }}</p>@endif
                    @if ($ph->requirements)<p class="prj-text"><b>Requisitos:</b> {{ $ph->requirements }}</p>@endif
                    <p class="prj-muted">Encargados: {{ $ph->assignees->map(fn ($u) => $fmtUser($u))->join(', ') ?: '—' }}</p>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('admin.projects.update-phase', [$project, $ph]) }}" class="prj-inline-form">
                            @csrf @method('PATCH')
                            <select name="status" class="users-manager-input">
                                @foreach (\App\Models\ProjectPhase::STATUSES as $k => $l)
                                    <option value="{{ $k }}" @selected($ph->status === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="progress" min="0" max="100" value="{{ $ph->progress }}" class="users-manager-input" style="width:90px" aria-label="Avance">
                            <button type="submit" class="prj-btn">Actualizar</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="prj-muted">Este proyecto aún no tiene fases.</p>
            @endforelse
        </div>
    </div>

    {{-- COTIZACIONES --}}
    <div class="prj-tabpanel" data-panel="cotizaciones" hidden>
        <div class="prj-panel">
            <div class="prj-panel-head">
                <h2>Cotizaciones asociadas</h2>
                @if (auth()->user()->hasPermission('quotes', 'create'))
                    <a href="{{ route('admin.quotes.create') }}" class="prj-btn">+ Nueva cotización</a>
                @endif
            </div>
            @include('admin.projects._doc_table', ['docs' => $project->quotes, 'type' => 'quote', 'numberField' => 'quote_number', 'routeName' => 'admin.quotes.show', 'extra' => fn ($d) => $d->total !== null ? '$' . number_format($d->total, 2) . ' ' . ($d->currency ?? '') : ''])
            @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'quote', 'label' => 'Asociar cotización existente']) @endif
        </div>
    </div>

    {{-- SERVICIOS Y REPORTES --}}
    <div class="prj-tabpanel" data-panel="servicios" hidden>
        <div class="prj-panel">
            <h2>Servicios técnicos</h2>
            @include('admin.projects._doc_table', ['docs' => $project->technicalServices, 'type' => 'service', 'numberField' => 'service_number', 'routeName' => 'admin.technical-services.show', 'extra' => fn ($d) => $d->service_date ? \Illuminate\Support\Carbon::parse($d->service_date)->format('d/m/Y') : ''])
            @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'service', 'label' => 'Asociar servicio técnico existente']) @endif
        </div>
        <div class="prj-panel">
            <h2>Reportes de servicio</h2>
            @include('admin.projects._doc_table', ['docs' => $project->serviceReports, 'type' => 'report', 'numberField' => 'report_number', 'routeName' => 'admin.service-reports.show', 'extra' => fn ($d) => ''])
            @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'report', 'label' => 'Asociar reporte existente']) @endif
        </div>
    </div>

    {{-- PEDIDOS --}}
    <div class="prj-tabpanel" data-panel="pedidos" hidden>
        <div class="prj-panel">
            <h2>Pedidos</h2>
            <p class="prj-muted">Se generan al aceptar una cotización de productos asociada al proyecto.</p>
            @include('admin.projects._doc_table', ['docs' => $project->salesOrders, 'type' => 'order', 'numberField' => 'order_number', 'routeName' => 'admin.sales-orders.show', 'extra' => fn ($d) => ''])
        </div>
    </div>

    {{-- HISTORIAL --}}
    <div class="prj-tabpanel" data-panel="historial" hidden>
        <div class="prj-panel">
            <h2>Historial</h2>
            @php
                $actionLabels = ['created' => 'Creado', 'updated' => 'Actualizado', 'deleted' => 'Eliminado'];
            @endphp
            <ul class="prj-log">
                @forelse ($logs as $log)
                    <li>
                        <div class="prj-log-head">
                            <strong>{{ $actionLabels[$log->action] ?? $log->action }}</strong>
                            <span class="prj-muted">{{ $log->performedBy ? $fmtUser($log->performedBy) : 'Sistema' }} · {{ $log->performed_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($log->action === 'updated' && is_array($log->new_value))
                            <ul class="prj-log-changes">
                                @foreach ($log->new_value as $field => $val)
                                    @php
                                        $old = $log->old_value[$field] ?? null;
                                        $fmt = fn ($v) => $field === 'status' && is_string($v) ? Project::statusLabel($v) : (is_scalar($v) || $v === null ? ($v === null || $v === '' ? '—' : \Illuminate\Support\Str::limit((string) $v, 80)) : json_encode($v));
                                    @endphp
                                    <li><code>{{ $field }}</code>: {{ $fmt($old) }} → {{ $fmt($val) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @empty
                    <li class="prj-muted">Sin movimientos registrados.</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
</div>
@endsection
