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
    use App\Models\ProjectPhase;
    use App\Support\ErpUi;
    $canEdit = auth()->user()->hasPermission('projects', 'edit');
    $canDelete = auth()->user()->hasPermission('projects', 'delete');
    $days = $project->daysToDue();
    $closed = $project->status === 'cerrado';
    $dueHint = (!$closed && $days !== null) ? ($days < 0 ? 'Vencido hace ' . abs($days) . ' d' : ($days === 0 ? 'Vence hoy' : 'Faltan ' . $days . ' d')) : null;
    $dueText = $project->due_date ? $project->due_date->format('d/m/Y') . ($dueHint ? ' (' . mb_strtolower($dueHint) . ')' : '') : null;
    $fmtUser = fn ($u) => $u ? trim($u->first_name . ' ' . $u->last_name) : '—';
    $amount = $project->quotesTotalMxn();
    $p2pr = ['Levantamiento', 'Planeación', 'MRP', 'Fabricación', 'Instalación', 'Cierre'];
    $phaseTone = fn ($s) => $s === 'completada' ? 'ok' : ($s === 'en_curso' ? 'info' : 'neu');
    $flowDocs = collect(\App\Services\Erp\DocumentFlow::flow($project))->reject(fn ($n) => !empty($n['current']));
    $tabs = [
        'general' => 'General',
        'fases' => 'Fases (' . $project->phases->count() . ')',
        'asociados' => 'Asociados (' . ($project->quotes->count() + $project->technicalServices->count() + $project->serviceReports->count() + $project->salesOrders->count()) . ')',
        'materiales' => 'Materiales (' . $requirements->count() . ')',
        'documentos' => 'Documentos (' . $flowDocs->count() . ')',
        'historial' => 'Historial',
        'notas' => 'Notas',
    ];
    $actionLabels = ['created' => 'Creado', 'updated' => 'Actualizado', 'deleted' => 'Eliminado'];
@endphp
<div class="erp-page prj-page">

    <x-erp.object-header
        :eyebrow="'Proyecto · ' . $project->project_number"
        :title="$project->name"
        :subtitle="$project->display_customer"
        :status="Project::statusLabel($project->status)"
        :tone="ErpUi::tone($project->status)"
        :back="route('admin.projects.index')"
        process="Plan-to-Produce"
        :steps="$p2pr"
        :current="$stage"
        :facts="[
            ['label' => 'Persona a cargo', 'value' => $fmtUser($project->manager)],
            ['label' => 'Fecha límite', 'value' => $dueText],
            ['label' => 'Avance', 'value' => $progress . '%'],
            ['label' => 'Monto (MXN)', 'value' => $amount > 0 ? '$' . number_format($amount, 2) : null],
        ]">
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.projects.update-status', $project) }}" class="prj-status-form">
                @csrf @method('PATCH')
                <select name="status" class="erp-select" aria-label="Estado del proyecto">
                    @foreach (Project::STATUSES as $k => $l)
                        <option value="{{ $k }}" @selected($project->status === $k)>{{ $l }}</option>
                    @endforeach
                </select>
                <button type="submit" class="erp-btn">Cambiar estado</button>
            </form>
            <a href="{{ route('admin.projects.edit', $project) }}" class="erp-btn erp-btn--primary">Editar</a>
        @endif
        @if ($canDelete)
            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('¿Eliminar este proyecto? Los documentos asociados se conservan.');">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn erp-btn--danger">Eliminar</button>
            </form>
        @endif
    </x-erp.object-header>

    <div class="erp-split">
        <div class="erp-card prj-main" data-erp-tabs>
            <x-erp.tab-nav :tabs="$tabs" />

            {{-- GENERAL --}}
            <div class="erp-tabpanel is-active" data-panel="general">
                <div class="prj-progress-row">
                    <span class="erp-fact-label">Avance global</span>
                    <x-erp.progress :value="$progress" width="100%" class="prj-progress-wide" />
                </div>
                <x-erp.facts :items="[
                    ['label' => 'Cliente', 'value' => $project->customer ? $project->display_customer : null],
                    ['label' => 'Empresa', 'value' => $project->company ?: null],
                    ['label' => 'Persona a cargo', 'value' => $fmtUser($project->manager)],
                    ['label' => 'Inicio', 'value' => $project->start_date?->format('d/m/Y')],
                    ['label' => 'Fecha límite', 'value' => $dueText],
                    ['label' => 'Duración estimada', 'value' => $project->estimated_duration_days ? $project->estimated_duration_days . ' días' : null],
                    ['label' => 'Tiempo estimado', 'value' => $project->estimated_time ?: null],
                    ['label' => 'Creado por', 'value' => $fmtUser($project->createdBy), 'sub' => $project->created_at?->format('d/m/Y')],
                ]" />
                @if ($project->description)
                    <h3 class="prj-h3">Descripción</h3>
                    <p class="prj-text">{{ $project->description }}</p>
                @endif
                @include('admin.production-orders._project_panel')
            </div>

            {{-- FASES --}}
            <div class="erp-tabpanel" data-panel="fases">
                <h2 class="prj-h2">Línea de tiempo</h2>
                @if ($timeline['min'])
                    <div class="prj-gantt">
                        <div class="prj-gantt-axis">
                            <span>{{ $timeline['min']->format('d/m/Y') }}</span>
                            <span>{{ $timeline['max']->format('d/m/Y') }}</span>
                        </div>
                        @foreach ($timeline['rows'] as $row)
                            @php $ph = $row['phase']; @endphp
                            <div class="prj-gantt-row">
                                <div class="prj-gantt-label" title="{{ $ph->name }}">{{ $loop->iteration }}. {{ $ph->name }}</div>
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
                        <div class="prj-gantt-legend"><span><i class="prj-gantt-today-key"></i>Hoy</span></div>
                    </div>
                @else
                    <p class="prj-muted">Define fechas en las fases para ver la línea de tiempo.</p>
                @endif

                <h2 class="prj-h2">Detalle de fases</h2>
                <div class="prj-phase-cards">
                @forelse ($project->phases as $ph)
                    <article class="prj-phasecard">
                        <header>
                            <strong>{{ $loop->iteration }}. {{ $ph->name }}</strong>
                            <x-erp.chip :tone="$phaseTone($ph->status)" :label="ProjectPhase::STATUSES[$ph->status] ?? $ph->status" />
                        </header>
                        <p class="prj-muted">{{ $ph->start_date?->format('d/m/Y') ?? '—' }} → {{ $ph->end_date?->format('d/m/Y') ?? '—' }}</p>
                        @if ($ph->description)<p class="prj-text">{{ $ph->description }}</p>@endif
                        @if ($ph->requirements)<p class="prj-text"><b>Requisitos:</b> {{ $ph->requirements }}</p>@endif
                        <p class="prj-muted">Encargados: <b>{{ $ph->assignees->map(fn ($u) => $fmtUser($u))->join(', ') ?: '—' }}</b></p>
                        <x-erp.progress :value="$ph->progress" width="100%" class="prj-progress-wide" />
                        @if ($canEdit)
                            <form method="POST" action="{{ route('admin.projects.update-phase', [$project, $ph]) }}" class="prj-inline-form">
                                @csrf @method('PATCH')
                                <select name="status" class="erp-select" aria-label="Estado de la fase">
                                    @foreach (ProjectPhase::STATUSES as $k => $l)
                                        <option value="{{ $k }}" @selected($ph->status === $k)>{{ $l }}</option>
                                    @endforeach
                                </select>
                                <input type="number" name="progress" min="0" max="100" value="{{ $ph->progress }}" class="erp-input prj-num-input" aria-label="Avance">
                                <button type="submit" class="erp-btn erp-btn--sm">Actualizar</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="prj-muted">Este proyecto aún no tiene fases.</p>
                @endforelse
                </div>
            </div>

            {{-- ASOCIADOS --}}
            <div class="erp-tabpanel" data-panel="asociados">
                <div class="prj-section-head">
                    <h2 class="prj-h2">Cotizaciones</h2>
                    @if (auth()->user()->hasPermission('quotes', 'create'))
                        <a href="{{ route('admin.quotes.create') }}" class="erp-btn erp-btn--sm">+ Nueva cotización</a>
                    @endif
                </div>
                @include('admin.projects._doc_table', ['docs' => $project->quotes, 'type' => 'quote', 'numberField' => 'quote_number', 'routeName' => 'admin.quotes.show', 'extra' => fn ($d) => $d->total !== null ? '$' . number_format($d->total, 2) . ' ' . ($d->currency ?? '') : ''])
                @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'quote', 'label' => 'Asociar cotización existente']) @endif

                <h2 class="prj-h2">Servicios técnicos</h2>
                @include('admin.projects._doc_table', ['docs' => $project->technicalServices, 'type' => 'service', 'numberField' => 'service_number', 'routeName' => 'admin.technical-services.show', 'extra' => fn ($d) => $d->service_date ? \Illuminate\Support\Carbon::parse($d->service_date)->format('d/m/Y') : ''])
                @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'service', 'label' => 'Asociar servicio técnico existente']) @endif

                <h2 class="prj-h2">Reportes de servicio</h2>
                @include('admin.projects._doc_table', ['docs' => $project->serviceReports, 'type' => 'report', 'numberField' => 'report_number', 'routeName' => 'admin.service-reports.show', 'extra' => fn ($d) => ''])
                @if ($canEdit) @include('admin.projects._attach_box', ['type' => 'report', 'label' => 'Asociar reporte existente']) @endif

                <h2 class="prj-h2">Pedidos</h2>
                <p class="prj-muted">Se generan al aceptar una cotización de productos asociada al proyecto.</p>
                @include('admin.projects._doc_table', ['docs' => $project->salesOrders, 'type' => 'order', 'numberField' => 'order_number', 'routeName' => 'admin.sales-orders.show', 'extra' => fn ($d) => ''])
            </div>

            {{-- MATERIALES --}}
            <div class="erp-tabpanel" data-panel="materiales">
                <div class="prj-section-head">
                    <h2 class="prj-h2">Requerimientos de materiales</h2>
                    @if (\Illuminate\Support\Facades\Route::has('admin.mrp.index') && auth()->user()->hasPermission('mrp', 'view'))
                        <a href="{{ route('admin.mrp.index') }}" class="erp-btn erp-btn--sm">Ir a MRP</a>
                    @endif
                </div>
                @if ($requirements->isEmpty())
                    <x-erp.empty title="Sin requerimientos de materiales" text="Aún no se ha generado un requerimiento (MRP) para este proyecto." />
                @else
                    <div class="erp-table-wrap">
                        <table class="erp-table">
                            <thead><tr><th>Folio</th><th>Estado</th><th>Requerido para</th><th class="is-num">Líneas</th></tr></thead>
                            <tbody>
                            @foreach ($requirements as $req)
                                <tr>
                                    <td class="erp-cell-folio">
                                        @if (\Illuminate\Support\Facades\Route::has('admin.mrp.show'))
                                            <a href="{{ route('admin.mrp.show', $req->id) }}">{{ $req->number }}</a>
                                        @else {{ $req->number }} @endif
                                    </td>
                                    <td><x-erp.status :value="$req->status" :label="\App\Models\MaterialRequirement::statusLabel($req->status)" :tone="\App\Models\MaterialRequirement::statusTone($req->status)" /></td>
                                    <td>@if ($req->needed_by_date)<x-erp.due :date="$req->needed_by_date" :closed="in_array($req->status, ['cubierto', 'cancelado'], true)" />@else — @endif</td>
                                    <td class="is-num">{{ $req->lines_count }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- DOCUMENTOS (flujo) --}}
            <div class="erp-tabpanel" data-panel="documentos">
                <h2 class="prj-h2">Documentos del flujo</h2>
                @forelse ($flowDocs as $n)
                    @php $tn = ErpUi::tone($n['status'] ?? null); @endphp
                    @if ($n['url']) <a href="{{ $n['url'] }}" class="prj-doc-row"> @else <div class="prj-doc-row"> @endif
                        <span class="prj-doc-main"><b>{{ $n['number'] }}</b><small>{{ $n['label'] }}</small></span>
                        @if (!empty($n['status']))
                            <span class="erp-tone-{{ $tn }} prj-doc-status"><i class="erp-dot erp-dot--{{ $tn }}"></i>{{ ErpUi::label($n['status']) }}</span>
                        @endif
                        <span class="prj-doc-go" aria-hidden="true">›</span>
                    @if ($n['url']) </a> @else </div> @endif
                @empty
                    <x-erp.empty title="Sin documentos enlazados" text="Los documentos aparecen aquí al asociarlos o generarlos desde el proyecto." />
                @endforelse
            </div>

            {{-- HISTORIAL --}}
            <div class="erp-tabpanel" data-panel="historial">
                <h2 class="prj-h2">Historial</h2>
                <ol class="prj-timeline">
                    @forelse ($logs as $log)
                        <li>
                            <div class="prj-tl-title">{{ $actionLabels[$log->action] ?? $log->action }}</div>
                            <div class="prj-muted">{{ $log->performedBy ? $fmtUser($log->performedBy) : 'Sistema' }} · {{ $log->performed_at?->format('d/m/Y H:i') }}</div>
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
                </ol>
            </div>

            {{-- NOTAS --}}
            <div class="erp-tabpanel" data-panel="notas">
                <div class="prj-section-head">
                    <h2 class="prj-h2">Notas internas</h2>
                    @if ($canEdit)
                        <a href="{{ route('admin.projects.edit', $project) }}" class="erp-btn erp-btn--sm">Editar notas</a>
                    @endif
                </div>
                @if ($project->notes)
                    <div class="prj-note">
                        <p class="prj-text">{{ $project->notes }}</p>
                        <div class="prj-muted">{{ $fmtUser($project->createdBy) }} · {{ $project->updated_at?->format('d/m/Y') }}</div>
                    </div>
                @else
                    <x-erp.empty title="Sin notas" text="Agrega notas internas desde la edición del proyecto." />
                @endif
            </div>
        </div>

        <aside class="prj-aside">
            <x-erp.document-flow :document="$project" />
        </aside>
    </div>
</div>
@endsection
