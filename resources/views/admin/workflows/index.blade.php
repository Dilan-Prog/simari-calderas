@extends('admin.layouts.master')

@section('title', 'Automatizaciones - Admin')

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

.wf-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.wf-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.wf-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-breadcrumb-current { color: #374151; }
.wf-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wf-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.wf-header-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.wf-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.wf-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

.wf-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.wf-table-scroll { overflow-x: auto; }
.wf-table { width: 100%; border-collapse: collapse; min-width: 800px; }
.wf-table thead tr { background: var(--header-footer-color); height: 44px; }
.wf-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.wf-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.wf-table tbody tr:last-child { border-bottom: none; }
.wf-table tbody tr:hover { background: rgba(255,247,237,.4); }
.wf-table td { padding: 0 20px; vertical-align: middle; }
.wf-td-name { font-size: 14px; color: #111827; font-weight: 500; }
.wf-td-name a { color: inherit; text-decoration: none; }
.wf-td-name a:hover { color: var(--secondary-color); text-decoration: underline; }
.wf-type-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; white-space: nowrap; }
.wf-count { font-size: 14px; font-weight: 600; color: #111827; font-variant-numeric: tabular-nums; }
.wf-count-label { font-size: 12px; color: var(--text-description-color); font-weight: 400; margin-left: 4px; }

/* Toggle switch para is_active */
.wf-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
.wf-switch input { opacity: 0; width: 0; height: 0; }
.wf-switch-slider { position: absolute; cursor: pointer; inset: 0; background-color: #D1D5DB; transition: .2s; border-radius: 22px; }
.wf-switch-slider::before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: #fff; transition: .2s; border-radius: 50%; }
.wf-switch input:checked + .wf-switch-slider { background-color: #16A34A; }
.wf-switch input:checked + .wf-switch-slider::before { transform: translateX(18px); }
.wf-switch input:disabled + .wf-switch-slider { opacity: .5; cursor: not-allowed; }

.wf-actions { display: flex; align-items: center; gap: 4px; }
.wf-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.wf-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.wf-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }

.wf-empty-row td { padding: 48px 20px; text-align: center; }
.wf-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.wf-empty-inner svg { opacity: .4; }
.wf-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 640px) {
    .wf-page { padding: 16px; gap: 16px; }
    .wf-header { flex-direction: column; align-items: stretch; }
    .wf-btn-new { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="wf-page">

    {{-- Header --}}
    <div class="wf-header">
        <div>
            <div class="wf-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="wf-breadcrumb-current">Automatizaciones</span>
            </div>
            <h1 class="wf-title">Automatizaciones</h1>
            <p class="wf-subtitle">Workflows de enrollment/steps: campañas, secuencias y reglas automáticas</p>
        </div>
        <div class="wf-header-actions">
            <a href="{{ route('admin.workflows.create') }}" class="wf-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nueva automatización
            </a>
        </div>
    </div>

    {{-- Table card --}}
    <div class="wf-table-card">
        <div class="wf-table-scroll">
            <table class="wf-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Inscripciones activas</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="wf-table-body">
                    @forelse($workflows as $workflow)
                    <tr data-workflow-row="{{ $workflow->id }}">
                        <td class="wf-td-name">
                            <a href="{{ route('admin.workflows.show', $workflow) }}">{{ $workflow->name }}</a>
                        </td>
                        <td><span class="wf-type-pill">{{ $workflow->type }}</span></td>
                        <td>
                            <span class="wf-count">{{ $workflow->active_enrollments_count ?? 0 }}</span>
                            <span class="wf-count-label">activas</span>
                        </td>
                        <td>
                            <label class="wf-switch">
                                <input type="checkbox"
                                       class="wf-toggle-active"
                                       data-id="{{ $workflow->id }}"
                                       {{ $workflow->is_active ? 'checked' : '' }}>
                                <span class="wf-switch-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="wf-actions">
                                <a href="{{ route('admin.workflows.show', $workflow) }}" class="wf-action-btn" title="Ver">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                <a href="{{ route('admin.workflows.edit', $workflow) }}" class="wf-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                                </a>
                                <button type="button"
                                        class="wf-action-btn wf-action-btn-delete btn-delete-workflow"
                                        data-id="{{ $workflow->id }}"
                                        data-name="{{ $workflow->name }}"
                                        title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="wf-empty-row">
                        <td colspan="5">
                            <div class="wf-empty-inner">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.24 7.76 2.83-2.83"/><path d="M18 12h4"/><path d="m16.24 16.24 2.83 2.83"/><path d="M12 18v4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M2 12h4"/><path d="m4.93 4.93 2.83 2.83"/></svg>
                                <p>Aún no hay automatizaciones creadas.</p>
                                <a href="{{ route('admin.workflows.create') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Crear la primera automatización</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Modal de confirmación de borrado --}}
<div id="deleteWorkflowModal" class="ap-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(17,24,39,.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:100%; max-width:400px; font-family:var(--font-family);">
        <h3 style="margin:0 0 8px; font-size:16px; font-weight:600; color:#111827;">Eliminar automatización</h3>
        <p style="margin:0 0 20px; font-size:14px; color:var(--text-description-color);">
            ¿Seguro que deseas eliminar <strong id="delWorkflowName"></strong>? Esta acción no se puede deshacer.
        </p>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="delWorkflowCancel" style="height:36px; padding:0 14px; border:1px solid #D1D5DB; background:#fff; border-radius:6px; font-size:13px; cursor:pointer;">Cancelar</button>
            <button type="button" id="delWorkflowConfirm" style="height:36px; padding:0 14px; border:none; background:#DC2626; color:#fff; border-radius:6px; font-size:13px; cursor:pointer;">Eliminar</button>
        </div>
    </div>
</div>

@include('admin.workflows.partials._scripts')
@endsection
