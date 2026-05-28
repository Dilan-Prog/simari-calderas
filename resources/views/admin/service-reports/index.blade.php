@extends('admin.layouts.master')
@section('title', 'Reportes de Servicio')

@push('styles')
<style>
    :root {
        --background-primary:           #141516;
        --background-primary-white:     #F8F9FA;
        --background--white:            #ffffff;
        --header-footer-color:          #1A2535;
        --border-header-footer-color:   #2E3A46;
        --text-white-color:             #ffffff;
        --text-subwhite-color:          #D1D5DC;
        --text-description-color:       #6B7280;
        --secondary-color:              #ff6213;
        --button-primary-color:         #ff6213;
        --button-primary-color-hover:   #de4a00;
        --font-family:                  'Inter', sans-serif;
        --shadow-sm:                    0 1px 2px rgba(0, 0, 0, 0.06);
        --shadow-md:                    0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .sr-wrapper {
        font-family: var(--font-family);
        background-color: var(--background-primary-white);
        min-height: 100%;
        padding: 32px;
    }

    /* ── PAGE HEADER ── */
    .sr-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    .sr-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--text-description-color);
        margin-bottom: 8px;
    }

    .sr-breadcrumb span.sr-breadcrumb-current {
        color: #374151;
        font-weight: 500;
    }

    .sr-page-title {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        line-height: 1.25;
        margin: 0 0 4px 0;
    }

    .sr-page-subtitle {
        font-size: 14px;
        color: var(--text-description-color);
        margin: 0;
    }

    .sr-btn-new {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 40px;
        padding: 0 16px;
        border-radius: 8px;
        background-color: var(--button-primary-color);
        color: var(--text-white-color);
        font-size: 13px;
        font-weight: 500;
        font-family: var(--font-family);
        border: none;
        cursor: pointer;
        box-shadow: var(--shadow-md);
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 0.15s ease;
    }

    .sr-btn-new:hover {
        background-color: var(--button-primary-color-hover);
        color: var(--text-white-color);
        text-decoration: none;
    }

    /* ── FILTERS ── */
    .sr-filters {
        background-color: var(--background--white);
        border-radius: 8px;
        box-shadow: var(--shadow-sm);
        padding: 16px;
        margin-bottom: 24px;
    }

    .sr-filters-grid {
        display: grid;
        grid-template-columns: 4fr 2fr 2fr 3fr 1fr;
        gap: 12px;
        align-items: center;
    }

    .sr-input-wrap {
        position: relative;
    }

    .sr-input-wrap .sr-input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9CA3AF;
        pointer-events: none;
        display: flex;
        align-items: center;
    }

    .sr-input {
        width: 100%;
        height: 40px;
        padding: 0 12px 0 36px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        font-size: 13px;
        font-family: var(--font-family);
        color: #111827;
        background-color: var(--background--white);
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .sr-input::placeholder {
        color: #9CA3AF;
    }

    .sr-input:focus {
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(255, 98, 19, 0.12);
    }

    .sr-select-wrap {
        position: relative;
    }

    .sr-select-wrap .sr-select-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
        pointer-events: none;
        display: flex;
        align-items: center;
    }

    .sr-select {
        width: 100%;
        height: 40px;
        padding: 0 32px 0 12px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        font-size: 13px;
        font-family: var(--font-family);
        color: #111827;
        background-color: var(--background--white);
        outline: none;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .sr-select:focus {
        border-color: var(--secondary-color);
        box-shadow: 0 0 0 3px rgba(255, 98, 19, 0.12);
    }

    .sr-date-btn {
        width: 100%;
        height: 40px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        background-color: var(--background--white);
        font-size: 13px;
        font-family: var(--font-family);
        color: #9CA3AF;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 12px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.15s ease;
        box-sizing: border-box;
    }

    .sr-date-btn:hover,
    .sr-date-btn:focus {
        border-color: var(--secondary-color);
    }

    .sr-date-btn:hover .sr-date-cal-icon,
    .sr-date-btn:focus .sr-date-cal-icon {
        color: var(--secondary-color);
    }

    .sr-date-btn span {
        flex: 1;
        text-align: left;
    }

    .sr-date-cal-icon {
        color: #9CA3AF;
        display: flex;
        align-items: center;
        flex-shrink: 0;
        transition: color 0.15s ease;
    }

    .sr-date-chev-icon {
        color: #6B7280;
        display: flex;
        align-items: center;
        flex-shrink: 0;
    }

    .sr-btn-filter {
        width: 100%;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid var(--secondary-color);
        color: var(--secondary-color);
        background-color: transparent;
        font-family: var(--font-family);
        cursor: pointer;
        transition: background-color 0.15s ease;
        box-sizing: border-box;
    }

    .sr-btn-filter:hover {
        background-color: #FFF7ED;
    }

    /* ── TABLE CARD ── */
    .sr-table-card {
        background-color: var(--background--white);
        border-radius: 8px;
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .sr-table-scroll {
        overflow-x: auto;
    }

    .sr-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sr-table thead tr {
        background-color: var(--header-footer-color);
        height: 44px;
    }

    .sr-table thead th {
        padding: 0 20px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-subwhite-color);
        white-space: nowrap;
    }

    .sr-table tbody tr {
        height: 52px;
        border-bottom: 1px solid #F3F4F6;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .sr-table tbody tr:last-child {
        border-bottom: none;
    }

    .sr-table tbody tr:hover {
        background-color: rgba(255, 247, 237, 0.5);
    }

    .sr-table tbody td {
        padding: 0 20px;
        vertical-align: middle;
    }

    .sr-folio-link {
        font-size: 13px;
        font-weight: 600;
        color: var(--secondary-color);
        text-decoration: none;
        font-variant-numeric: tabular-nums;
    }

    .sr-folio-link:hover {
        text-decoration: underline;
        color: var(--secondary-color);
    }

    .sr-td-client {
        font-size: 14px;
        color: #111827;
        font-weight: 500;
    }

    .sr-td-type,
    .sr-td-assigned {
        font-size: 13px;
        color: #374151;
    }

    .sr-td-date {
        font-size: 13px;
        color: var(--text-description-color);
    }

    /* ── BADGES ── */
    .sr-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        border: 1px solid;
        white-space: nowrap;
    }

    .sr-badge--draft {
        background-color: #F3F4F6;
        color: #6B7280;
        border-color: #D1D5DB;
    }

    .sr-badge--in-progress {
        background-color: #EFF6FF;
        color: #3B82F6;
        border-color: #BFDBFE;
    }

    .sr-badge--completed {
        background-color: #F0FDF4;
        color: #16A34A;
        border-color: #BBF7D0;
    }

    .sr-badge--signed {
        background-color: #F5F3FF;
        color: #7C3AED;
        border-color: #DDD6FE;
    }

    /* ── ACTION BUTTONS ── */
    .sr-actions {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sr-action-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: none;
        background-color: transparent;
        color: #6B7280;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
        text-decoration: none;
        flex-shrink: 0;
    }

    .sr-action-btn:hover {
        background-color: #F3F4F6;
        color: var(--secondary-color);
    }

    .sr-action-btn--delete:hover {
        background-color: #FEF2F2;
        color: #DC2626;
    }

    .sr-action-btn--delete {
        color: #6B7280;
    }

    /* ── EMPTY STATE ── */
    .sr-empty-state {
        padding: 64px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 12px;
    }

    .sr-empty-state svg {
        color: #D1D5DB;
    }

    .sr-empty-state p {
        font-size: 14px;
        color: var(--text-description-color);
        margin: 0;
    }

    /* ── PAGINATION ── */
    .sr-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-top: 1px solid #F3F4F6;
    }

    .sr-pagination-info {
        font-size: 13px;
        color: var(--text-description-color);
    }

    .sr-pagination-info strong {
        font-weight: 600;
        color: #111827;
    }

    .sr-pagination-nav {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .sr-page-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        background-color: transparent;
        color: #6B7280;
        font-size: 13px;
        font-family: var(--font-family);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }

    .sr-page-btn:hover {
        background-color: #F9FAFB;
        color: #374151;
        text-decoration: none;
    }

    .sr-page-btn--active {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
        color: var(--text-white-color);
        font-weight: 500;
    }

    .sr-page-btn--active:hover {
        background-color: var(--button-primary-color-hover);
        color: var(--text-white-color);
    }

    .sr-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }

    /* Laravel default pagination override */
    .sr-pagination-nav .pagination {
        display: flex;
        align-items: center;
        gap: 4px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .sr-pagination-nav .pagination li span,
    .sr-pagination-nav .pagination li a {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 1px solid #D1D5DB;
        background-color: transparent;
        color: #374151;
        font-size: 13px;
        font-family: var(--font-family);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }

    .sr-pagination-nav .pagination li a:hover {
        background-color: #F9FAFB;
    }

    .sr-pagination-nav .pagination li.active span {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
        color: var(--text-white-color);
        font-weight: 500;
    }

    .sr-pagination-nav .pagination li.disabled span {
        opacity: 0.45;
        cursor: not-allowed;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
        .sr-filters-grid {
            grid-template-columns: 1fr 1fr;
        }

        .sr-filters-grid > *:first-child {
            grid-column: 1 / -1;
        }

        .sr-filters-grid > *:nth-child(5) {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 640px) {
        .sr-wrapper {
            padding: 16px;
        }

        .sr-page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .sr-filters-grid {
            grid-template-columns: 1fr;
        }

        .sr-filters-grid > * {
            grid-column: 1 / -1;
        }

        .sr-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sr-pagination {
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="sr-wrapper">

    {{-- ── PAGE HEADER ── --}}
    <div class="sr-page-header">
        <div>
            <div class="sr-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                <span class="sr-breadcrumb-current">Reportes de Servicio</span>
            </div>
            <h1 class="sr-page-title">Reportes de Servicio</h1>
            <p class="sr-page-subtitle">Gestiona y genera reportes técnicos de los servicios realizados</p>
        </div>

        <a href="{{ route('admin.service-reports.create') }}" class="sr-btn-new">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14"/><path d="M12 5v14"/>
            </svg>
            Nuevo Reporte
        </a>
    </div>

    {{-- ── FILTERS ── --}}
    <div class="sr-filters">
        <form method="GET" action="{{ route('admin.service-reports.index') }}">
            <div class="sr-filters-grid">

                {{-- Search --}}
                <div class="sr-input-wrap">
                    <span class="sr-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="search"
                        class="sr-input"
                        placeholder="Buscar por folio, cliente o tipo de servicio..."
                        value="{{ request('search') }}"
                    >
                </div>

                {{-- Tipo --}}
                <div class="sr-select-wrap">
                    <select name="type" class="sr-select">
                        <option value="">Todos los tipos</option>
                        <option value="quimico"       {{ request('type') == 'quimico'       ? 'selected' : '' }}>Análisis Químico</option>
                        <option value="mantenimiento" {{ request('type') == 'mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                        <option value="inspeccion"    {{ request('type') == 'inspeccion'    ? 'selected' : '' }}>Inspección</option>
                        <option value="limpieza"      {{ request('type') == 'limpieza'      ? 'selected' : '' }}>Limpieza</option>
                        <option value="calibracion"   {{ request('type') == 'calibracion'   ? 'selected' : '' }}>Calibración</option>
                    </select>
                    <span class="sr-select-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </span>
                </div>

                {{-- Estado --}}
                <div class="sr-select-wrap">
                    <select name="status" class="sr-select">
                        <option value="">Todos los estados</option>
                        <option value="draft"       {{ request('status') == 'draft'       ? 'selected' : '' }}>Borrador</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                        <option value="completed"   {{ request('status') == 'completed'   ? 'selected' : '' }}>Completado</option>
                        <option value="signed"      {{ request('status') == 'signed'      ? 'selected' : '' }}>Firmado</option>
                    </select>
                    <span class="sr-select-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </span>
                </div>

                {{-- Rango de fechas --}}
                <div style="display:flex; gap:8px;">
                    <div class="sr-input-wrap" style="flex:1;">
                        <input
                            type="date"
                            name="date_from"
                            class="sr-input"
                            style="padding-left:12px;"
                            value="{{ request('date_from') }}"
                            placeholder="Desde"
                        >
                    </div>
                    <div class="sr-input-wrap" style="flex:1;">
                        <input
                            type="date"
                            name="date_to"
                            class="sr-input"
                            style="padding-left:12px;"
                            value="{{ request('date_to') }}"
                            placeholder="Hasta"
                        >
                    </div>
                </div>

                {{-- Botón Filtrar --}}
                <button type="submit" class="sr-btn-filter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/>
                    </svg>
                </button>

            </div>
        </form>
    </div>

    {{-- ── TABLE CARD ── --}}
    <div class="sr-table-card">
        <div class="sr-table-scroll">
            <table class="sr-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Tipo de Servicio</th>
                        <th>Encargado</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if($reports->isEmpty())
                        <tr>
                            <td colspan="7">
                                <div class="sr-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" x2="8" y1="13" y2="13"/>
                                        <line x1="16" x2="8" y1="17" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                    <p>No hay reportes registrados</p>
                                    <a href="{{ route('admin.service-reports.create') }}" class="sr-btn-new" style="margin-top:4px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"/><path d="M12 5v14"/>
                                        </svg>
                                        Crear primer reporte
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($reports as $report)
                            <tr>
                                {{-- FOLIO --}}
                                <td>
                                    <a href="{{ route('admin.service-reports.show', $report) }}" class="sr-folio-link">
                                        {{ $report->report_number }}
                                    </a>
                                </td>

                                {{-- CLIENTE --}}
                                <td class="sr-td-client">{{ $report->customer_name }}</td>

                                {{-- TIPO DE SERVICIO --}}
                                <td class="sr-td-type">{{ $report->service_type }}</td>

                                {{-- ENCARGADO --}}
                                <td class="sr-td-assigned">{{ $report->assigned_user_name }}</td>

                                {{-- ESTADO --}}
                                <td>
                                    @php
                                        $badgeClass = match($report->status) {
                                            'draft'       => 'sr-badge--draft',
                                            'in_progress' => 'sr-badge--in-progress',
                                            'completed'   => 'sr-badge--completed',
                                            'signed'      => 'sr-badge--signed',
                                            default       => 'sr-badge--draft',
                                        };
                                        $badgeLabel = match($report->status) {
                                            'draft'       => 'Borrador',
                                            'in_progress' => 'En Proceso',
                                            'completed'   => 'Completado',
                                            'signed'      => 'Firmado',
                                            default       => $report->status,
                                        };
                                    @endphp
                                    <span class="sr-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                </td>

                                {{-- FECHA --}}
                                <td class="sr-td-date">{{ $report->service_date }}</td>

                                {{-- ACCIONES --}}
                                <td>
                                    <div class="sr-actions">

                                        {{-- Ver --}}
                                        <a href="{{ route('admin.service-reports.show', $report) }}" class="sr-action-btn" title="Ver reporte">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>

                                        {{-- Editar --}}
                                        <a href="{{ route('admin.service-reports.edit', $report) }}" class="sr-action-btn" title="Editar reporte">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>
                                            </svg>
                                        </a>

                                        {{-- Descargar PDF --}}
                                        <a href="{{ route('admin.service-reports.download-pdf', $report) }}" class="sr-action-btn" title="Descargar PDF" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                            </svg>
                                        </a>

                                        {{-- Eliminar --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.service-reports.destroy', $report) }}"
                                            style="display:inline;"
                                            onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="sr-action-btn sr-action-btn--delete" title="Eliminar reporte">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                                                </svg>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        {{-- ── PAGINATION ── --}}
        @if($reports->hasPages())
            <div class="sr-pagination">
                <span class="sr-pagination-info">
                    Mostrando
                    <strong>{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</strong>
                    de
                    <strong>{{ $reports->total() }}</strong>
                    reportes
                </span>

                <div class="sr-pagination-nav">
                    {{-- Primera página --}}
                    @if($reports->onFirstPage())
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
                            </svg>
                        </button>
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                    @else
                        <a href="{{ $reports->appends(request()->query())->url(1) }}" class="sr-page-btn" title="Primera página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
                            </svg>
                        </a>
                        <a href="{{ $reports->appends(request()->query())->previousPageUrl() }}" class="sr-page-btn" title="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </a>
                    @endif

                    {{-- Números de página --}}
                    @php
                        $currentPage  = $reports->currentPage();
                        $lastPage     = $reports->lastPage();
                        $start        = max(1, $currentPage - 2);
                        $end          = min($lastPage, $currentPage + 2);
                    @endphp

                    @for($page = $start; $page <= $end; $page++)
                        @if($page === $currentPage)
                            <span class="sr-page-btn sr-page-btn--active">{{ $page }}</span>
                        @else
                            <a href="{{ $reports->appends(request()->query())->url($page) }}" class="sr-page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    {{-- Siguiente / Última --}}
                    @if($reports->hasMorePages())
                        <a href="{{ $reports->appends(request()->query())->nextPageUrl() }}" class="sr-page-btn" title="Página siguiente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                        <a href="{{ $reports->appends(request()->query())->url($lastPage) }}" class="sr-page-btn" title="Última página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>
                            </svg>
                        </a>
                    @else
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    /* Row click → ir al show, excepto cuando se hace click en los botones de acción */
    document.querySelectorAll('.sr-table tbody tr').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.sr-actions')) return;
            var folioLink = row.querySelector('.sr-folio-link');
            if (folioLink) window.location.href = folioLink.href;
        });
    });
</script>
@endpush
