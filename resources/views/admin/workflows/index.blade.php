@extends('admin.layouts.master')

@section('title', 'Automatizaciones - Admin')

@push('styles')
@vite(['resources/css/admin/pages/workflows-index.css'])
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

/* ── Header ─────────────────────────────── */
.wf-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.wf-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-breadcrumb-current { color: #374151; }
.wf-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wf-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.wf-header-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.wf-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.wf-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }
.wf-btn-secondary { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; transition: background .2s; white-space: nowrap; flex-shrink: 0; }
.wf-btn-secondary:hover { background: #F9FAFB; color: #111827; }

/* ── Tabs ───────────────────────────────── */
/* .wf-tabs / .wf-tab CSS vive en admin.workflows.partials._tabs para que
   viaje con el partial en las 4 páginas que lo incluyen. */

/* ── KPI cards ──────────────────────────── */
.wf-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.wf-kpi-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 16px 18px; display: flex; flex-direction: column; gap: 4px; }
.wf-kpi-label { font-size: 12px; color: var(--text-description-color); font-weight: 500; }
.wf-kpi-value { font-size: 22px; font-weight: 700; color: #111827; font-variant-numeric: tabular-nums; }
.wf-kpi-sub { font-size: 12px; color: #9CA3AF; }
.wf-kpi-card.wf-kpi-danger .wf-kpi-value { color: #DC2626; }

/* ── Toolbar ────────────────────────────── */
.wf-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 12px 16px; }
.wf-search-field { position: relative; flex: 1 1 220px; min-width: 200px; }
.wf-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; pointer-events: none; display: flex; align-items: center; }
.wf-search-input { width: 100%; height: 38px; padding: 0 12px 0 34px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; box-sizing: border-box; transition: border-color .2s, box-shadow .2s; }
.wf-search-input:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.wf-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.wf-chip { height: 32px; padding: 0 12px; border-radius: 999px; border: 1px solid #D1D5DB; background: #fff; color: #374151; font-size: 12px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .15s, color .15s, border-color .15s; white-space: nowrap; }
.wf-chip:hover { background: #F9FAFB; }
.wf-chip.wf-chip-active { background: var(--secondary-color); border-color: var(--secondary-color); color: #fff; }
.wf-sort-select { height: 38px; padding: 0 30px 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; cursor: pointer; }
.wf-view-toggle { display: inline-flex; border: 1px solid #D1D5DB; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
.wf-view-btn { width: 36px; height: 38px; display: inline-flex; align-items: center; justify-content: center; background: #fff; border: none; color: var(--text-description-color); cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.wf-view-btn + .wf-view-btn { border-left: 1px solid #D1D5DB; }
.wf-view-btn.wf-view-btn-active { background: #FFF7ED; color: var(--secondary-color); }
.wf-view-btn:hover { background: #F9FAFB; }

/* ── Grid view ──────────────────────────── */
.wf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.wf-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 18px; display: flex; flex-direction: column; gap: 12px; }
.wf-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.wf-card-name { font-size: 15px; font-weight: 600; color: #111827; text-decoration: none; }
.wf-card-name:hover { color: var(--secondary-color); text-decoration: underline; }
.wf-card-desc { font-size: 13px; color: var(--text-description-color); margin: 0; }
.wf-card-actions { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
.wf-action-stack { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.wf-action-chip { width: 26px; height: 26px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }
.wf-node-count { font-size: 12px; color: var(--text-description-color); }
.wf-card-meta { font-size: 12px; color: var(--text-description-color); }
.wf-card-meta.wf-meta-error { color: #DC2626; font-weight: 500; }
.wf-progress-row { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--text-description-color); margin-bottom: 4px; }
.wf-progress-bar { width: 100%; height: 6px; border-radius: 999px; background: #F3F4F6; overflow: hidden; }
.wf-progress-fill { height: 100%; border-radius: 999px; }
.wf-progress-fill.wf-good { background: #16A34A; }
.wf-progress-fill.wf-warn { background: #F59E0B; }
.wf-progress-fill.wf-bad { background: #DC2626; }
.wf-progress-fill.wf-neutral { background: #D1D5DB; }
.wf-card-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #F3F4F6; }

/* ── Toggle switch ──────────────────────── */
.wf-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
.wf-switch input { opacity: 0; width: 0; height: 0; }
.wf-switch-slider { position: absolute; cursor: pointer; inset: 0; background-color: #D1D5DB; transition: .2s; border-radius: 22px; }
.wf-switch-slider::before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: #fff; transition: .2s; border-radius: 50%; }
.wf-switch input:checked + .wf-switch-slider { background-color: #16A34A; }
.wf-switch input:checked + .wf-switch-slider::before { transform: translateX(18px); }
.wf-switch input:disabled + .wf-switch-slider { opacity: .5; cursor: not-allowed; }

/* ── Dropdown menu (3 puntos) ───────────── */
.wf-menu { position: relative; }
.wf-menu-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); cursor: pointer; list-style: none; }
.wf-menu-btn::-webkit-details-marker { display: none; }
.wf-menu-btn:hover { background: #F3F4F6; color: #111827; }
.wf-menu[open] .wf-menu-btn { background: #F3F4F6; }
.wf-menu-list { position: absolute; right: 0; top: 36px; z-index: 20; background: #fff; border-radius: 8px; box-shadow: var(--shadow-md); border: 1px solid #F3F4F6; min-width: 170px; padding: 6px; display: flex; flex-direction: column; gap: 2px; }
.wf-menu-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 13px; color: #374151; text-decoration: none; background: transparent; border: none; width: 100%; text-align: left; cursor: pointer; font-family: var(--font-family); }
.wf-menu-item:hover { background: #F9FAFB; color: #111827; }
.wf-menu-item-danger { color: #DC2626; }
.wf-menu-item-danger:hover { background: #FEF2F2; color: #DC2626; }

/* ── List view (tabla) ──────────────────── */
.wf-list-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.wf-table-scroll { overflow-x: auto; }
.wf-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.wf-table thead tr { background: var(--header-footer-color); height: 44px; }
.wf-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.wf-table tbody tr { height: 56px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.wf-table tbody tr:last-child { border-bottom: none; }
.wf-table tbody tr:hover { background: rgba(255,247,237,.4); }
.wf-table td { padding: 0 20px; vertical-align: middle; }
.wf-td-name { font-size: 14px; color: #111827; font-weight: 500; }
.wf-td-name a { color: inherit; text-decoration: none; }
.wf-td-name a:hover { color: var(--secondary-color); text-decoration: underline; }
.wf-td-progress { display: flex; align-items: center; gap: 8px; min-width: 140px; }
.wf-td-progress .wf-progress-bar { width: 80px; }
.wf-actions { display: flex; align-items: center; gap: 4px; justify-content: flex-end; }
.wf-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.wf-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }

/* ── Empty states ───────────────────────── */
.wf-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; padding: 48px 20px; text-align: center; background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); }
.wf-empty svg { opacity: .4; }
.wf-empty p { font-size: 14px; margin: 0; }
.wf-empty-row td { padding: 48px 20px; text-align: center; }
.wf-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.wf-empty-inner svg { opacity: .4; }
.wf-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 1024px) {
    .wf-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .wf-page { padding: 16px; gap: 16px; }
    .wf-header { flex-direction: column; align-items: stretch; }
    .wf-btn-new, .wf-btn-secondary { justify-content: center; }
    .wf-kpis { grid-template-columns: 1fr; }
    .wf-toolbar { flex-direction: column; align-items: stretch; }
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
            <a href="{{ route('admin.workflows.templates') }}" class="wf-btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                Desde plantilla
            </a>
            <form method="POST" action="{{ route('admin.workflows.quick-create') }}" class="wf-btn-new-form">
                @csrf
                <button type="submit" class="wf-btn-new">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nuevo workflow
                </button>
            </form>
        </div>
    </div>

    @include('admin.workflows.partials._tabs')

    {{-- KPIs --}}
    <div class="wf-kpis">
        <div class="wf-kpi-card">
            <span class="wf-kpi-label">Activos</span>
            <span class="wf-kpi-value">{{ $kpis['active_count'] }} <span style="font-size:14px;font-weight:400;color:#9CA3AF;">de {{ $kpis['total_count'] }}</span></span>
        </div>
        <div class="wf-kpi-card">
            <span class="wf-kpi-label">Ejecuciones (30 días)</span>
            <span class="wf-kpi-value">{{ $kpis['executions_30d'] }}</span>
        </div>
        <div class="wf-kpi-card">
            <span class="wf-kpi-label">Tasa de éxito</span>
            <span class="wf-kpi-value">{{ $kpis['success_rate_pct'] }}%</span>
        </div>
        <div class="wf-kpi-card {{ $kpis['workflows_with_errors'] > 0 ? 'wf-kpi-danger' : '' }}">
            <span class="wf-kpi-label">Con errores</span>
            <span class="wf-kpi-value">{{ $kpis['workflows_with_errors'] }}</span>
        </div>
    </div>

    @if($workflows->isNotEmpty())
    {{-- Toolbar --}}
    <div class="wf-toolbar">
        <div class="wf-search-field">
            <span class="wf-search-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </span>
            <input type="text" id="wfSearch" class="wf-search-input" placeholder="Buscar workflow..." autocomplete="off">
        </div>

        <div class="wf-chips">
            <button type="button" class="wf-chip wf-chip-active" data-filter="all">Todos</button>
            <button type="button" class="wf-chip" data-filter="active">Activos</button>
            <button type="button" class="wf-chip" data-filter="paused">Pausados</button>
            <button type="button" class="wf-chip" data-filter="errors">Con errores</button>
        </div>

        <select id="wfSort" class="wf-sort-select">
            <option value="reciente">Más reciente</option>
            <option value="nombre">Nombre (A-Z)</option>
            <option value="ejecuciones">Más ejecuciones</option>
            <option value="exito">Tasa de éxito</option>
        </select>

        <div class="wf-view-toggle">
            <button type="button" id="wfViewGrid" class="wf-view-btn wf-view-btn-active" title="Vista de tarjetas">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
            </button>
            <button type="button" id="wfViewList" class="wf-view-btn" title="Vista de lista">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
            </button>
        </div>
    </div>

    {{-- Vista grid --}}
    <div id="wfGrid" class="wf-grid">
        @foreach($workflows as $workflow)
        @php
            $successPct = $workflow->stats['success_pct'];
            $hasError = !$workflow->stats['last_ok'];
            $barClass = $workflow->stats['total'] == 0 ? 'wf-neutral' : ($successPct >= 90 ? 'wf-good' : ($successPct >= 80 ? 'wf-warn' : 'wf-bad'));
        @endphp
        <div class="wf-card"
             data-workflow-row="{{ $workflow->id }}"
             data-active="{{ $workflow->is_active ? '1' : '0' }}"
             data-has-error="{{ $hasError ? '1' : '0' }}"
             data-runs="{{ $workflow->stats['total'] }}"
             data-success="{{ $successPct }}"
             data-edited="{{ $workflow->updated_at->timestamp }}"
             data-name="{{ strtolower($workflow->name) }}">

            <div class="wf-card-top">
                <div>
                    <a href="{{ route('admin.workflows.edit', $workflow) }}" class="wf-card-name">{{ $workflow->name }}</a>
                    @if($workflow->description)
                        <p class="wf-card-desc">{{ $workflow->description }}</p>
                    @endif
                </div>
                <div class="wf-card-actions">
                    <label class="wf-switch">
                        <input type="checkbox" class="wf-toggle-active" data-id="{{ $workflow->id }}" {{ $workflow->is_active ? 'checked' : '' }}>
                        <span class="wf-switch-slider"></span>
                    </label>
                    <details class="wf-menu">
                        <summary class="wf-menu-btn" title="Más acciones">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                        </summary>
                        <div class="wf-menu-list">
                            <a href="{{ route('admin.workflows.edit', $workflow) }}" class="wf-menu-item">Abrir editor</a>
                            <form method="POST" action="{{ route('admin.workflows.duplicate', $workflow) }}" onsubmit="return confirm('¿Duplicar este workflow?')">
                                @csrf
                                <button type="submit" class="wf-menu-item">Duplicar</button>
                            </form>
                            <a href="{{ route('admin.workflows.show', $workflow) }}" class="wf-menu-item">Ver ejecuciones</a>
                            <button type="button" class="wf-menu-item wf-menu-item-danger btn-delete-workflow" data-id="{{ $workflow->id }}" data-name="{{ $workflow->name }}">Eliminar</button>
                        </div>
                    </details>
                </div>
            </div>

            <div class="wf-action-stack">
                @foreach($workflow->action_stack as $action)
                    <span class="wf-action-chip" style="background:{{ $action['bg'] }}; color:{{ $action['color'] }};" title="{{ $action['label'] }}">{{ $action['initials'] }}</span>
                @endforeach
                <span class="wf-node-count">{{ $workflow->node_count }} {{ Str::plural('paso', $workflow->node_count) }}</span>
            </div>

            <div>
                <div class="wf-progress-row">
                    <span>Éxito 30 días</span>
                    <span>{{ $successPct }}%</span>
                </div>
                <div class="wf-progress-bar">
                    <div class="wf-progress-fill {{ $barClass }}" style="width: {{ $successPct }}%;"></div>
                </div>
            </div>

            <div class="wf-card-bottom">
                <span class="wf-card-meta {{ $hasError ? 'wf-meta-error' : '' }}">
                    Última ejecución: {{ $workflow->stats['last_enrolled_at'] ? $workflow->stats['last_enrolled_at']->diffForHumans() : 'nunca' }}
                </span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Vista lista --}}
    <div id="wfList" class="wf-list-card" style="display:none;">
        <div class="wf-table-scroll">
            <table class="wf-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Acciones usadas</th>
                        <th>Pasos</th>
                        <th>Éxito 30 días</th>
                        <th>Última ejecución</th>
                        <th>Activo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="wfListBody">
                    @foreach($workflows as $workflow)
                    @php
                        $successPct = $workflow->stats['success_pct'];
                        $hasError = !$workflow->stats['last_ok'];
                        $barClass = $workflow->stats['total'] == 0 ? 'wf-neutral' : ($successPct >= 90 ? 'wf-good' : ($successPct >= 80 ? 'wf-warn' : 'wf-bad'));
                    @endphp
                    <tr data-workflow-row="{{ $workflow->id }}"
                        data-active="{{ $workflow->is_active ? '1' : '0' }}"
                        data-has-error="{{ $hasError ? '1' : '0' }}"
                        data-runs="{{ $workflow->stats['total'] }}"
                        data-success="{{ $successPct }}"
                        data-edited="{{ $workflow->updated_at->timestamp }}"
                        data-name="{{ strtolower($workflow->name) }}">
                        <td class="wf-td-name">
                            <a href="{{ route('admin.workflows.edit', $workflow) }}">{{ $workflow->name }}</a>
                        </td>
                        <td>
                            <div class="wf-action-stack">
                                @foreach($workflow->action_stack as $action)
                                    <span class="wf-action-chip" style="background:{{ $action['bg'] }}; color:{{ $action['color'] }};" title="{{ $action['label'] }}">{{ $action['initials'] }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>{{ $workflow->node_count }}</td>
                        <td>
                            <div class="wf-td-progress">
                                <div class="wf-progress-bar">
                                    <div class="wf-progress-fill {{ $barClass }}" style="width: {{ $successPct }}%;"></div>
                                </div>
                                <span>{{ $successPct }}%</span>
                            </div>
                        </td>
                        <td class="{{ $hasError ? 'wf-meta-error' : '' }}" style="font-size:13px;">
                            {{ $workflow->stats['last_enrolled_at'] ? $workflow->stats['last_enrolled_at']->diffForHumans() : 'nunca' }}
                        </td>
                        <td>
                            <label class="wf-switch">
                                <input type="checkbox" class="wf-toggle-active" data-id="{{ $workflow->id }}" {{ $workflow->is_active ? 'checked' : '' }}>
                                <span class="wf-switch-slider"></span>
                            </label>
                        </td>
                        <td>
                            <div class="wf-actions">
                                <details class="wf-menu">
                                    <summary class="wf-menu-btn" title="Más acciones">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                    </summary>
                                    <div class="wf-menu-list">
                                        <a href="{{ route('admin.workflows.edit', $workflow) }}" class="wf-menu-item">Abrir editor</a>
                                        <form method="POST" action="{{ route('admin.workflows.duplicate', $workflow) }}" onsubmit="return confirm('¿Duplicar este workflow?')">
                                            @csrf
                                            <button type="submit" class="wf-menu-item">Duplicar</button>
                                        </form>
                                        <a href="{{ route('admin.workflows.show', $workflow) }}" class="wf-menu-item">Ver ejecuciones</a>
                                        <button type="button" class="wf-menu-item wf-menu-item-danger btn-delete-workflow" data-id="{{ $workflow->id }}" data-name="{{ $workflow->name }}">Eliminar</button>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Estado vacío por filtro (oculto por defecto, mostrado por JS) --}}
    <div id="wfEmptyFiltered" class="wf-empty" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <p>Sin resultados para tu búsqueda</p>
    </div>
    @else
    {{-- Estado vacío real (sin workflows creados) --}}
    <div class="wf-empty">
        <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.24 7.76 2.83-2.83"/><path d="M18 12h4"/><path d="m16.24 16.24 2.83 2.83"/><path d="M12 18v4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M2 12h4"/><path d="m4.93 4.93 2.83 2.83"/></svg>
        <p>Aún no hay automatizaciones creadas</p>
        <form method="POST" action="{{ route('admin.workflows.quick-create') }}">
            @csrf
            <button type="submit" style="font-size:13px;color:#ff6213;text-decoration:none;background:none;border:none;padding:0;cursor:pointer;font:inherit;">Crear la primera automatización</button>
        </form>
    </div>
    @endif

</div>

{{-- Modal de confirmación de borrado --}}
<div id="deleteWorkflowModal" class="ap-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(17,24,39,.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; padding:24px; width:100%; max-width:400px; font-family:var(--font-family);">
        <h3 style="margin:0 0 8px; font-size:16px; font-weight:600; color:#111827;">Eliminar workflow</h3>
        <p style="margin:0 0 20px; font-size:14px; color:var(--text-description-color);">
            ¿Seguro que deseas eliminar <strong id="delWorkflowName"></strong>? Esta acción no se puede deshacer.
        </p>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="delWorkflowCancel" style="height:36px; padding:0 14px; border:1px solid #D1D5DB; background:#fff; border-radius:6px; font-size:13px; cursor:pointer;">Cancelar</button>
            <button type="button" id="delWorkflowConfirm" style="height:36px; padding:0 14px; border:none; background:#DC2626; color:#fff; border-radius:6px; font-size:13px; cursor:pointer;">Eliminar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@vite(['resources/js/admin/workflows-index.js'])
@endpush
