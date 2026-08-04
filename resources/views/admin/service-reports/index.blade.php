@extends('admin.layouts.master')
@section('title', 'Reportes de Servicio')

@push('styles')
    @vite('resources/css/service-reports.css')
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

        <div style="display:flex;align-items:center;gap:10px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'service-reports.index',
                'columnDefs' => [
                    'cliente' => 'Cliente',
                    'tipo_servicio' => 'Tipo de Servicio',
                    'encargado' => 'Encargado',
                    'estado' => 'Estado',
                    'fecha' => 'Fecha',
                ],
            ])
            <a href="{{ route('admin.service-reports.create') }}" class="sr-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Nuevo Reporte
            </a>
        </div>
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
                    {{-- FIX QA: these option values ("quimico", "mantenimiento"...)
                         never matched the real service_type enum stored in the DB
                         ("chemical_analysis", "maintenance_preventive"..., see
                         ServiceReportService::getServiceTypes()) — selecting ANY
                         type here always returned zero results. Now using the
                         real enum values/labels so every type is filterable. --}}
                    <select name="type" class="sr-select">
                        <option value="">Todos los tipos</option>
                        @foreach($serviceTypes as $value => $label)
                            <option value="{{ $value }}" {{ request('type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
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
                        <th data-col="cliente">Cliente</th>
                        <th data-col="tipo_servicio">Tipo de Servicio</th>
                        <th data-col="encargado">Encargado</th>
                        <th data-col="estado">Estado</th>
                        <th data-col="fecha">Fecha</th>
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
                                <td class="sr-td-client" data-col="cliente">{{ $report->customer_name }}</td>

                                {{-- TIPO DE SERVICIO --}}
                                <td class="sr-td-type" data-col="tipo_servicio">{{ $report->service_type_label }}</td>

                                {{-- ENCARGADO --}}
                                <td class="sr-td-assigned" data-col="encargado">{{ $report->assigned_user_name }}</td>

                                {{-- ESTADO --}}
                                <td data-col="estado">
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
                                <td class="sr-td-date" data-col="fecha">{{ $report->service_date }}</td>

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

    {{-- ── MOBILE CARDS (ocultas en desktop) ── --}}
    <div class="sr-mobile-cards">
        @if($reports->isEmpty())
            <div class="sr-mobile-empty">No hay reportes registrados</div>
        @else
            @foreach($reports as $report)
                @php
                    $mBadgeClass = match($report->status) {
                        'draft'       => 'sr-badge--draft',
                        'in_progress' => 'sr-badge--in-progress',
                        'completed'   => 'sr-badge--completed',
                        'signed'      => 'sr-badge--signed',
                        default       => 'sr-badge--draft',
                    };
                    $mBadgeLabel = match($report->status) {
                        'draft'       => 'Borrador',
                        'in_progress' => 'En Proceso',
                        'completed'   => 'Completado',
                        'signed'      => 'Firmado',
                        default       => $report->status,
                    };
                @endphp
                <div class="sr-mobile-card">
                    <div class="sr-mobile-card__top">
                        <a href="{{ route('admin.service-reports.show', $report) }}" class="sr-mobile-card__num">
                            {{ $report->report_number }}
                        </a>
                        <span class="sr-badge {{ $mBadgeClass }}">{{ $mBadgeLabel }}</span>
                    </div>
                    <div class="sr-mobile-card__type">{{ $report->service_type_label }}</div>
                    <div class="sr-mobile-card__meta">📅 {{ $report->service_date }}</div>
                    <div class="sr-mobile-card__company">🏢 {{ $report->customer_name }}</div>
                    <div class="sr-mobile-card__analyst">👤 Encargado: {{ $report->assigned_user_name }}</div>
                    <div class="sr-mobile-card__divider"></div>
                    <div class="sr-mobile-card__footer">
                        <a href="{{ route('admin.service-reports.show', $report) }}" class="sr-mobile-card__btn">
                            Ver →
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Botón nuevo reporte flotante en mobile --}}
    <a href="{{ route('admin.service-reports.create') }}" class="sr-mobile-new-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/><path d="M12 5v14"/>
        </svg>
        Nuevo Reporte
    </a>

</div>{{-- /sr-wrapper --}}

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
<script src="{{ asset('js/admin/column-visibility.js') }}"></script>
<script>
    initColumnVisibility({
        tableKey: 'service-reports.index',
        savedColumns: @json($visibleColumns),
        saveUrl: '{{ route('admin.column-preferences.update') }}',
    });
</script>
@endpush
