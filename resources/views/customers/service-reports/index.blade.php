@extends('customers.layouts.master')
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
            <p class="sr-page-subtitle">Consulta los reportes técnicos de los servicios realizados</p>
        </div>
    </div>

    {{-- ── FILTERS ── --}}
    <div class="sr-filters">
        <form method="GET" action="{{ route('customer.service-reports.index') }}">
            <div class="sr-filters-grid">

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
                            <td colspan="6">
                                <div class="sr-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" x2="8" y1="13" y2="13"/>
                                        <line x1="16" x2="8" y1="17" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                    <p>No hay reportes registrados</p>
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($reports as $report)
                            <tr>
                                {{-- FOLIO --}}
                                <td>
                                    <a href="{{ route('customer.service-reports.show', $report) }}" class="sr-folio-link">
                                        {{ $report->report_number }}
                                    </a>
                                </td>

                                {{-- TIPO DE SERVICIO --}}
                                <td class="sr-td-type">{{ $report->service_type_label }}</td>

                                {{-- ENCARGADO --}}
                                <td class="sr-td-assigned">{{ $report->assignedUser?->first_name }} {{ $report->assignedUser?->last_name }}</td>

                                {{-- ESTADO --}}
                                <td>
                                    <span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span>
                                </td>

                                {{-- FECHA --}}
                                <td class="sr-td-date">{{ $report->service_date?->format('d/m/Y') }}</td>

                                {{-- ACCIONES --}}
                                <td>
                                    <div class="sr-actions">

                                        {{-- Ver --}}
                                        <a href="{{ route('customer.service-reports.show', $report) }}" class="sr-action-btn" title="Ver reporte">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>

                                        {{-- Descargar PDF --}}
                                        <a href="{{ route('customer.service-reports.download-pdf', $report) }}" class="sr-action-btn" title="Descargar PDF" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                            </svg>
                                        </a>

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
                    @if($reports->onFirstPage())
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                    @else
                        <a href="{{ $reports->appends(request()->query())->previousPageUrl() }}" class="sr-page-btn" title="Página anterior">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </a>
                    @endif

                    @php
                        $currentPage = $reports->currentPage();
                        $lastPage    = $reports->lastPage();
                        $start       = max(1, $currentPage - 2);
                        $end         = min($lastPage, $currentPage + 2);
                    @endphp

                    @for($page = $start; $page <= $end; $page++)
                        @if($page === $currentPage)
                            <span class="sr-page-btn sr-page-btn--active">{{ $page }}</span>
                        @else
                            <a href="{{ $reports->appends(request()->query())->url($page) }}" class="sr-page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    @if($reports->hasMorePages())
                        <a href="{{ $reports->appends(request()->query())->nextPageUrl() }}" class="sr-page-btn" title="Página siguiente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    @else
                        <button class="sr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
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
                <div class="sr-mobile-card">
                    <div class="sr-mobile-card__top">
                        <a href="{{ route('customer.service-reports.show', $report) }}" class="sr-mobile-card__num">
                            {{ $report->report_number }}
                        </a>
                        <span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span>
                    </div>
                    <div class="sr-mobile-card__type">{{ $report->service_type_label }}</div>
                    <div class="sr-mobile-card__meta">📅 {{ $report->service_date?->format('d/m/Y') }}</div>
                    <div class="sr-mobile-card__analyst">👤 Encargado: {{ $report->assignedUser?->first_name }} {{ $report->assignedUser?->last_name }}</div>
                    <div class="sr-mobile-card__divider"></div>
                    <div class="sr-mobile-card__footer">
                        <a href="{{ route('customer.service-reports.show', $report) }}" class="sr-mobile-card__btn">
                            Ver →
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>{{-- /sr-wrapper --}}

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.sr-table tbody tr').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.sr-actions')) return;
            var folioLink = row.querySelector('.sr-folio-link');
            if (folioLink) window.location.href = folioLink.href;
        });
    });
</script>
@endpush
