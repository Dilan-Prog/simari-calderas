@extends('admin.layouts.master')

@section('title', 'Workflow: '.$workflow->name.' - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.wf-show-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }
.wf-show-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-show-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-show-breadcrumb a { color: inherit; text-decoration: none; }
.wf-show-breadcrumb-current { color: #374151; }

.wf-show-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.wf-show-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.wf-show-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0; }
.wf-show-subtitle { font-size: 14px; color: var(--text-description-color); margin: 4px 0 0; }
.wf-show-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.wf-btn-edit { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; }
.wf-btn-edit:hover { background: var(--button-primary-color-hover); color: #fff; }
.wf-btn-back { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; transition: background .2s; white-space: nowrap; }
.wf-btn-back:hover { background: #F9FAFB; color: #111827; }

.wf-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.wf-badge-active   { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.wf-badge-inactive { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.wf-badge-type     { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }

.wf-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 20px 24px; }
.wf-card-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }
.wf-card-title svg { color: var(--secondary-color); }

.wf-props-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px 24px; }
.wf-prop { display: flex; flex-direction: column; gap: 3px; }
.wf-prop-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #9CA3AF; }
.wf-prop-value { font-size: 14px; color: #111827; }
.wf-prop-value.muted { color: #9CA3AF; }
.wf-prop-full { grid-column: 1 / -1; }
.wf-trigger-box { background: #F9FAFB; border: 1px solid #F3F4F6; border-radius: 6px; padding: 10px 12px; font-size: 12px; color: #374151; white-space: pre-wrap; word-break: break-word; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

.wf-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.wf-table-scroll { overflow-x: auto; }
.wf-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.wf-table thead tr { background: var(--header-footer-color); height: 44px; }
.wf-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.wf-table tbody tr.wf-enrollment-row { height: 52px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.wf-table tbody tr.wf-enrollment-row:hover { background: rgba(255,247,237,.4); }
.wf-table td { padding: 10px 20px; vertical-align: middle; font-size: 13px; color: #111827; }
.wf-empty-cell { padding: 32px 20px; text-align: center; color: #9CA3AF; font-size: 13px; }

.wf-status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.wf-status-active    { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.wf-status-waiting    { background: #FFF7ED; color: var(--secondary-color); border-color: #FED7AA; }
.wf-status-completed { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }
.wf-status-cancelled,
.wf-status-failed    { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.wf-status-default   { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }

.wf-toggle-logs-btn { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 12px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 12px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .15s; }
.wf-toggle-logs-btn:hover { background: #F9FAFB; }
.wf-toggle-logs-btn svg { transition: transform .15s; }
.wf-toggle-logs-btn.open svg { transform: rotate(90deg); }

.wf-logs-row td { padding: 0; }
.wf-logs-wrap { padding: 4px 20px 18px 44px; background: #FAFAFA; }
.wf-logs-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #F3F4F6; border-radius: 6px; overflow: hidden; }
.wf-logs-table th { text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #9CA3AF; padding: 8px 14px; border-bottom: 1px solid #F3F4F6; }
.wf-logs-table td { font-size: 12.5px; color: #374151; padding: 8px 14px; border-bottom: 1px solid #F3F4F6; vertical-align: top; }
.wf-logs-table tr:last-child td { border-bottom: none; }
.wf-log-result { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; border: 1px solid; white-space: nowrap; }
.wf-log-result-success { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
.wf-log-result-error,
.wf-log-result-failed   { background: #FEF2F2; color: #DC2626; border-color: #FECACA; }
.wf-log-result-skipped  { background: #F3F4F6; color: #4B5563; border-color: #E5E7EB; }
.wf-log-result-default  { background: #EFF6FF; color: #3B82F6; border-color: #BFDBFE; }
.wf-logs-empty { padding: 14px; text-align: center; color: #9CA3AF; font-size: 12.5px; }

.wf-pagination-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-top: 1px solid #F3F4F6; gap: 16px; flex-wrap: wrap; }
.wf-pagination-info { font-size: 13px; color: var(--text-description-color); }
.wf-pagination-info strong { font-weight: 600; color: #111827; }

@media (max-width: 900px) {
    .wf-props-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .wf-show-page { padding: 16px; }
    .wf-props-grid { grid-template-columns: 1fr; }
    .wf-show-header { flex-direction: column; align-items: stretch; }
    .wf-logs-wrap { padding: 4px 12px 18px 20px; }
}
</style>
@endpush

@section('content')
<div class="wf-show-page">

    <div class="wf-show-header">
        <div>
            <div class="wf-show-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('admin.workflows.index') }}">Workflows</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="wf-show-breadcrumb-current">{{ $workflow->name }}</span>
            </div>
            <div class="wf-show-title-row">
                <h1 class="wf-show-title">{{ $workflow->name }}</h1>
                <span class="wf-badge {{ $workflow->is_active ? 'wf-badge-active' : 'wf-badge-inactive' }}">
                    {{ $workflow->is_active ? 'Activo' : 'Inactivo' }}
                </span>
                <span class="wf-badge wf-badge-type">{{ $workflow->type }}</span>
            </div>
            <p class="wf-show-subtitle">
                Ejecuciones (enrollments) de este workflow, con su bitácora de pasos — vista de debug.
            </p>
        </div>
        <div class="wf-show-actions">
            <a href="{{ route('admin.workflows.index') }}" class="wf-btn-back">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                Volver
            </a>
            <a href="{{ route('admin.workflows.edit', $workflow) }}" class="wf-btn-edit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                Editar workflow
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;color:#16A34A;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 16px;color:#DC2626;font-size:13px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Datos básicos del workflow --}}
    <div class="wf-card">
        <h2 class="wf-card-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            Propiedades del workflow
        </h2>
        <div class="wf-props-grid">
            <div class="wf-prop">
                <span class="wf-prop-label">ID</span>
                <span class="wf-prop-value">#{{ $workflow->id }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Tipo</span>
                <span class="wf-prop-value">{{ $workflow->type }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Reinscripción permitida</span>
                <span class="wf-prop-value">{{ $workflow->reenrollment_allowed ? 'Sí' : 'No' }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Creado por</span>
                <span class="wf-prop-value {{ $workflow->creator ? '' : 'muted' }}">{{ $workflow->creator?->name ?? '—' }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Creado</span>
                <span class="wf-prop-value">{{ $workflow->created_at?->format('d M Y, H:i') }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Actualizado</span>
                <span class="wf-prop-value">{{ $workflow->updated_at?->format('d M Y, H:i') }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Total inscripciones</span>
                <span class="wf-prop-value">{{ $enrollments->total() }}</span>
            </div>
            <div class="wf-prop">
                <span class="wf-prop-label">Steps</span>
                <span class="wf-prop-value">{{ $workflow->allSteps()->count() }}</span>
            </div>
            <div class="wf-prop wf-prop-full">
                <span class="wf-prop-label">Enrollment trigger (JSON)</span>
                @if($workflow->enrollment_trigger)
                    <pre class="wf-trigger-box">{{ json_encode($workflow->enrollment_trigger, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <span class="wf-prop-value muted">—</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Enrollments --}}
    <div class="wf-table-card">
        <div class="wf-table-scroll">
            <table class="wf-table">
                <thead>
                    <tr>
                        <th style="width:36px;"></th>
                        <th>#</th>
                        <th>Enrollable</th>
                        <th>Estatus</th>
                        <th>Inscrito</th>
                        <th>Completado</th>
                        <th>Paso actual</th>
                        <th>Logs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        @php
                            $statusClass = 'wf-status-'.strtolower($enrollment->status ?? '');
                        @endphp
                        <tr class="wf-enrollment-row">
                            <td>
                                @if($enrollment->logs->isNotEmpty())
                                <button type="button" class="wf-toggle-logs-btn" data-wf-toggle="wf-logs-{{ $enrollment->id }}" aria-expanded="false" title="Ver bitácora">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                                @endif
                            </td>
                            <td>{{ $enrollment->id }}</td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:2px;">
                                    <span style="font-weight:500;">{{ class_basename($enrollment->enrollable_type) }} #{{ $enrollment->enrollable_id }}</span>
                                    <span style="font-size:11px;color:#9CA3AF;">{{ $enrollment->enrollable_type }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="wf-status-badge {{ $statusClass ?: 'wf-status-default' }}">
                                    {{ ucfirst($enrollment->status ?? '—') }}
                                </span>
                            </td>
                            <td>{{ $enrollment->enrolled_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>{{ $enrollment->completed_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>{{ $enrollment->current_step_id ? '#'.$enrollment->current_step_id : '—' }}</td>
                            <td>{{ $enrollment->logs->count() }}</td>
                        </tr>
                        <tr class="wf-logs-row" id="wf-logs-{{ $enrollment->id }}" style="display:none;">
                            <td colspan="8">
                                <div class="wf-logs-wrap">
                                    @if($enrollment->logs->isEmpty())
                                        <div class="wf-logs-empty">Esta inscripción no tiene entradas de bitácora todavía.</div>
                                    @else
                                        <table class="wf-logs-table">
                                            <thead>
                                                <tr>
                                                    <th>Step</th>
                                                    <th>Acción tomada</th>
                                                    <th>Resultado</th>
                                                    <th>Mensaje</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($enrollment->logs->sortBy('logged_at') as $log)
                                                    @php
                                                        $resultClass = 'wf-log-result-'.strtolower($log->result ?? '');
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            @if($log->step)
                                                                {{ $log->step->step_type }}
                                                                @if($log->step->action_type)
                                                                    <span style="color:#9CA3AF;">({{ $log->step->action_type }})</span>
                                                                @endif
                                                                <span style="color:#9CA3AF;"> #{{ $log->step_id }}</span>
                                                            @else
                                                                <span style="color:#9CA3AF;">{{ $log->step_id ? '#'.$log->step_id : '—' }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $log->action_taken }}</td>
                                                        <td>
                                                            <span class="wf-log-result {{ $resultClass ?: 'wf-log-result-default' }}">
                                                                {{ $log->result }}
                                                            </span>
                                                        </td>
                                                        <td style="max-width:320px;">{{ $log->message ?? '—' }}</td>
                                                        <td style="white-space:nowrap;">{{ $log->logged_at?->format('d M Y, H:i:s') ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="wf-empty-cell">Este workflow todavía no tiene inscripciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enrollments->hasPages())
        <div class="wf-pagination-bar">
            <span class="wf-pagination-info">
                Mostrando <strong>{{ $enrollments->firstItem() }}</strong>–<strong>{{ $enrollments->lastItem() }}</strong>
                de <strong>{{ $enrollments->total() }}</strong> inscripciones
            </span>
            {{ $enrollments->appends(request()->query())->links('admin.components.pagination') }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-wf-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-wf-toggle');
            var row = document.getElementById(targetId);
            if (!row) return;
            var isOpen = row.style.display !== 'none';
            row.style.display = isOpen ? 'none' : 'table-row';
            btn.classList.toggle('open', !isOpen);
            btn.setAttribute('aria-expanded', String(!isOpen));
        });
    });
});
</script>
@endpush
