@extends('admin.layouts.master')

@section('title', 'Nuevo Reporte - Admin')

@push('styles')
@vite(['resources/css/admin/pages/crm-reports.css'])
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

.report-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 1100px; margin: 0 auto; }
.report-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.report-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.report-form-breadcrumb-current { color: #374151; }
.report-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.report-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.report-form-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }

.report-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.report-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; }
.report-form-section-title:not(:first-child) { margin-top: 28px; }
.report-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.report-form-field { display: flex; flex-direction: column; gap: 6px; }
.report-form-field-full { grid-column: 1 / -1; }
.report-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.report-form-label .req { color: #DC2626; }
.report-form-input, .report-form-select, .report-form-textarea {
    width: 100%; height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.report-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.report-form-input:focus, .report-form-select:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.report-form-hint { font-size: 12px; color: #9CA3AF; }
.report-form-error { font-size: 12px; color: #DC2626; }
.report-form-input.is-invalid, .report-form-select.is-invalid { border-color: #DC2626; }

.report-fields-group { display: none; }
.report-fields-group.is-active { display: contents; }

.report-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.report-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.report-btn-cancel:hover { background: #F9FAFB; }
.report-btn-preview { height: 40px; padding: 0 16px; border: 1px solid var(--secondary-color); background: #fff; color: var(--secondary-color); border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .15s; display: inline-flex; align-items: center; gap: 6px; }
.report-btn-preview:hover { background: #FFF7ED; }
.report-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.report-btn-save:hover { background: var(--button-primary-color-hover); }

/* ── Preview panel ───────────────────────── */
.report-preview-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; position: sticky; top: 24px; display: flex; flex-direction: column; gap: 16px; }
.report-preview-wrapper { position: relative; height: 320px; }
.report-preview-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; height: 320px; color: #9CA3AF; text-align: center; }
.report-preview-empty svg { opacity: .4; }
.report-preview-empty p { font-size: 13px; margin: 0; max-width: 220px; }

@media (max-width: 900px) {
    .report-form-layout { grid-template-columns: 1fr; }
    .report-preview-card { position: static; }
}
@media (max-width: 640px) {
    .report-form-page { padding: 16px; }
    .report-form-grid { grid-template-columns: 1fr; }
    .report-form-card, .report-preview-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="report-form-page">

    <div>
        <div class="report-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.reports.index') }}" style="color:inherit;text-decoration:none;">Reportes</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="report-form-breadcrumb-current">Nuevo</span>
        </div>
        <h1 class="report-form-title">Nuevo reporte</h1>
        <p class="report-form-subtitle">Elige una fuente de datos, una métrica y un tipo de gráfico para construir tu reporte</p>
    </div>

    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 16px;color:#DC2626;font-size:13px;">
        <strong>Corrige los siguientes errores:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="report-form-layout">

        {{-- Builder form --}}
        <form method="POST" action="{{ route('admin.reports.store') }}" id="report-builder-form" data-preview-url="{{ route('admin.reports.preview') }}">
            @csrf

            <div class="report-form-card">
                <h2 class="report-form-section-title">Datos generales</h2>
                <div class="report-form-grid">

                    <div class="report-form-field report-form-field-full">
                        <label class="report-form-label" for="name">Nombre del reporte <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="report-form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Negocios ganados por mes">
                        @error('name') <span class="report-form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="report-form-field">
                        <label class="report-form-label" for="data_source">Fuente de datos <span class="req">*</span></label>
                        <select id="data_source" name="data_source" class="report-form-select @error('data_source') is-invalid @enderror" required>
                            <option value="deals" {{ old('data_source', 'deals') === 'deals' ? 'selected' : '' }}>Negocios (deals)</option>
                            <option value="email_campaigns" {{ old('data_source') === 'email_campaigns' ? 'selected' : '' }}>Campañas de email</option>
                            <option value="workflows" {{ old('data_source') === 'workflows' ? 'selected' : '' }}>Workflows</option>
                            <option value="contacts" {{ old('data_source') === 'contacts' ? 'selected' : '' }}>Contactos</option>
                            <option value="companies" {{ old('data_source') === 'companies' ? 'selected' : '' }}>Empresas</option>
                        </select>
                        @error('data_source') <span class="report-form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="report-form-field">
                        <label class="report-form-label" for="chart_type">Tipo de gráfico <span class="req">*</span></label>
                        <select id="chart_type" name="chart_type" class="report-form-select @error('chart_type') is-invalid @enderror" required>
                            <option value="bar" {{ old('chart_type', 'bar') === 'bar' ? 'selected' : '' }}>Barras</option>
                            <option value="line" {{ old('chart_type') === 'line' ? 'selected' : '' }}>Línea</option>
                            <option value="pie" {{ old('chart_type') === 'pie' ? 'selected' : '' }}>Pastel</option>
                            <option value="doughnut" {{ old('chart_type') === 'doughnut' ? 'selected' : '' }}>Dona</option>
                            <option value="table" {{ old('chart_type') === 'table' ? 'selected' : '' }}>Tabla</option>
                        </select>
                        @error('chart_type') <span class="report-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- ── Deals fields ────────────────────── --}}
                <h2 class="report-form-section-title">Métrica y agrupación</h2>
                <div class="report-form-grid">

                    <div class="report-fields-group" data-fields-for="deals">
                        <div class="report-form-field">
                            <label class="report-form-label" for="deals_metric">Métrica</label>
                            <select id="deals_metric" name="config[metric]" class="report-form-select" data-config-target="metric" data-source-scope="deals">
                                <option value="count" {{ old('config.metric', 'count') === 'count' ? 'selected' : '' }}>Cantidad de negocios</option>
                                <option value="sum_amount" {{ old('config.metric') === 'sum_amount' ? 'selected' : '' }}>Monto total</option>
                                <option value="avg_days_in_stage" {{ old('config.metric') === 'avg_days_in_stage' ? 'selected' : '' }}>Promedio de días en etapa</option>
                            </select>
                        </div>
                        <div class="report-form-field">
                            <label class="report-form-label" for="deals_group_by">Agrupar por</label>
                            <select id="deals_group_by" name="config[group_by]" class="report-form-select" data-config-target="group_by" data-source-scope="deals">
                                <option value="stage" {{ old('config.group_by', 'stage') === 'stage' ? 'selected' : '' }}>Etapa</option>
                                <option value="owner" {{ old('config.group_by') === 'owner' ? 'selected' : '' }}>Responsable</option>
                                <option value="month" {{ old('config.group_by') === 'month' ? 'selected' : '' }}>Mes</option>
                            </select>
                            <span class="report-form-hint">Se ignora si la métrica es "Promedio de días en etapa" (siempre agrupa por etapa).</span>
                        </div>
                        <div class="report-form-field">
                            <label class="report-form-label" for="deals_pipeline_id">Pipeline (filtro)</label>
                            <select id="deals_pipeline_id" name="config[filters][pipeline_id]" class="report-form-select" data-config-target="filter_pipeline_id" data-source-scope="deals">
                                <option value="">Todos los pipelines</option>
                                @foreach($pipelines as $pipeline)
                                    <option value="{{ $pipeline->id }}" {{ (string) old('config.filters.pipeline_id') === (string) $pipeline->id ? 'selected' : '' }}>{{ $pipeline->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="report-form-field"></div>
                        <div class="report-form-field">
                            <label class="report-form-label" for="deals_date_from">Fecha desde</label>
                            <input type="date" id="deals_date_from" name="config[filters][date_from]" class="report-form-input" value="{{ old('config.filters.date_from') }}" data-config-target="filter_date_from" data-source-scope="deals">
                        </div>
                        <div class="report-form-field">
                            <label class="report-form-label" for="deals_date_to">Fecha hasta</label>
                            <input type="date" id="deals_date_to" name="config[filters][date_to]" class="report-form-input" value="{{ old('config.filters.date_to') }}" data-config-target="filter_date_to" data-source-scope="deals">
                        </div>
                    </div>

                    {{-- ── Contacts / Companies fields ─────── --}}
                    <div class="report-fields-group" data-fields-for="contacts,companies">
                        <div class="report-form-field">
                            <label class="report-form-label" for="customers_group_by">Agrupar por</label>
                            <select id="customers_group_by" name="config[group_by]" class="report-form-select" data-config-target="group_by" data-source-scope="contacts,companies">
                                <option value="status" {{ old('config.group_by', 'status') === 'status' ? 'selected' : '' }}>Estado</option>
                                <option value="source" {{ old('config.group_by') === 'source' ? 'selected' : '' }}>Origen</option>
                            </select>
                        </div>
                    </div>

                    {{-- ── Email campaigns / Workflows: sin config adicional ─── --}}
                    <div class="report-fields-group" data-fields-for="email_campaigns,workflows">
                        <div class="report-form-field report-form-field-full">
                            <span class="report-form-hint">Esta fuente de datos no requiere métrica ni agrupación adicionales: se cuenta el total agrupado por estado automáticamente.</span>
                        </div>
                    </div>

                </div>

                <div class="report-form-actions">
                    <a href="{{ route('admin.reports.index') }}" class="report-btn-cancel">Cancelar</a>
                    <button type="button" class="report-btn-preview" id="preview-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                        Preview
                    </button>
                    <button type="submit" class="report-btn-save">Guardar reporte</button>
                </div>
            </div>
        </form>

        {{-- Preview panel --}}
        <div class="report-preview-card">
            <h2 class="report-form-section-title" style="margin:0;">Vista previa</h2>
            <div class="report-preview-wrapper">
                <canvas id="preview-chart"></canvas>
                <div class="report-preview-empty" id="preview-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    <p>Configura los campos y presiona "Preview" para ver el resultado antes de guardar.</p>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
@vite(['resources/js/admin/report-builder.js'])
<script>
(function () {
    'use strict';

    /**
     * Muestra/oculta los grupos de campos (métrica/agrupación/filtros)
     * según la fuente de datos seleccionada. Cada grupo declara a qué
     * data_source(s) pertenece en data-fields-for (separado por comas).
     * Los <select>/<input> con [data-config-target] son los únicos que
     * el JS del preview/submit debe leer para armar config{metric,
     * group_by, filters}; los campos ocultos no deben enviarse.
     */
    var dataSourceSelect = document.getElementById('data_source');
    var fieldGroups = document.querySelectorAll('.report-fields-group');

    function syncFieldGroups() {
        var current = dataSourceSelect.value;

        fieldGroups.forEach(function (group) {
            var scopes = (group.getAttribute('data-fields-for') || '').split(',');
            var isActive = scopes.indexOf(current) !== -1;
            group.classList.toggle('is-active', isActive);

            group.querySelectorAll('[data-config-target]').forEach(function (field) {
                field.disabled = !isActive;
            });
        });
    }

    dataSourceSelect.addEventListener('change', syncFieldGroups);
    syncFieldGroups();
})();
</script>
@endpush
