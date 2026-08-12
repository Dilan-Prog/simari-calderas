@extends('admin.layouts.master')
@section('title', 'Reportes de Entrega de Material')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush
@section('content')
<div class="mdr-wrapper">

    {{-- ── PAGE HEADER ── --}}
    <div class="mdr-page-header">
        <div>
            <div class="mdr-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
                <span class="mdr-breadcrumb-current">Reportes de Entrega de Material</span>
            </div>
            <h1 class="mdr-page-title">Reportes de Entrega de Material</h1>
            <p class="mdr-page-subtitle">Gestiona y genera reportes de entrega de material a clientes</p>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            @permiso('material-delivery-reports', 'create')
            <a href="{{ route('admin.material-delivery-reports.create') }}" class="mdr-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/><path d="M12 5v14"/>
                </svg>
                Nuevo Reporte
            </a>
            @endpermiso
        </div>
    </div>

    {{-- ── FILTERS ── --}}
    <div class="mdr-filters">
        <form method="GET" action="{{ route('admin.material-delivery-reports.index') }}">
            <div class="mdr-filters-grid">

                {{-- Search --}}
                <div class="mdr-input-wrap">
                    <span class="mdr-input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="search"
                        class="mdr-input"
                        placeholder="Buscar por folio, pedido o cliente…"
                        value="{{ request('search') }}"
                    >
                </div>

                {{-- Estado --}}
                <div class="mdr-select-wrap">
                    <select name="status" class="mdr-select">
                        <option value="">Todos los estados</option>
                        <option value="draft"  {{ request('status') == 'draft'  ? 'selected' : '' }}>Borrador</option>
                        <option value="signed" {{ request('status') == 'signed' ? 'selected' : '' }}>Firmado</option>
                    </select>
                    <span class="mdr-select-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </span>
                </div>

                {{-- Rango de fechas --}}
                <div style="display:flex; gap:8px;">
                    <div class="mdr-input-wrap" style="flex:1;">
                        <input
                            type="date"
                            name="date_from"
                            class="mdr-input"
                            style="padding-left:12px;"
                            value="{{ request('date_from') }}"
                            placeholder="Desde"
                        >
                    </div>
                    <div class="mdr-input-wrap" style="flex:1;">
                        <input
                            type="date"
                            name="date_to"
                            class="mdr-input"
                            style="padding-left:12px;"
                            value="{{ request('date_to') }}"
                            placeholder="Hasta"
                        >
                    </div>
                </div>

                {{-- Botón Filtrar --}}
                <button type="submit" class="mdr-btn-filter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/>
                    </svg>
                </button>

            </div>
        </form>
    </div>

    {{-- ── TABLE CARD ── --}}
    <div class="mdr-table-card">
        <div class="mdr-table-scroll">
            <table class="mdr-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Fecha de Entrega</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if($reports->isEmpty())
                        <tr>
                            <td colspan="6">
                                <div class="mdr-empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                        <line x1="16" x2="8" y1="13" y2="13"/>
                                        <line x1="16" x2="8" y1="17" y2="17"/>
                                        <polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                    <p>No hay reportes registrados</p>
                                    @permiso('material-delivery-reports', 'create')
                                    <a href="{{ route('admin.material-delivery-reports.create') }}" class="mdr-btn-new" style="margin-top:4px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"/><path d="M12 5v14"/>
                                        </svg>
                                        Crear primer reporte
                                    </a>
                                    @endpermiso
                                </div>
                            </td>
                        </tr>
                    @else
                        @foreach($reports as $report)
                            @php
                                $mdrCustomer = $report->customer ?? $report->salesOrder?->customer;
                                $mdrCustomerLabel = $mdrCustomer
                                    ? ($mdrCustomer->company ?: trim(($mdrCustomer->first_name ?? '') . ' ' . ($mdrCustomer->last_name ?? '')))
                                    : '—';
                            @endphp
                            <tr>
                                {{-- FOLIO --}}
                                <td>
                                    <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-folio-link">
                                        {{ $report->report_number }}
                                    </a>
                                </td>

                                {{-- PEDIDO --}}
                                <td class="mdr-td-order">
                                    @if($report->salesOrder)
                                        @if(\Illuminate\Support\Facades\Route::has('admin.sales-orders.show'))
                                            <a href="{{ route('admin.sales-orders.show', $report->salesOrder) }}">{{ $report->salesOrder->order_number }}</a>
                                        @else
                                            {{ $report->salesOrder->order_number }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- CLIENTE --}}
                                <td class="mdr-td-client">{{ $mdrCustomerLabel }}</td>

                                {{-- FECHA DE ENTREGA --}}
                                <td class="mdr-td-date">{{ optional($report->delivery_date)->format('d/m/Y') ?? '—' }}</td>

                                {{-- ESTADO --}}
                                <td>
                                    <span class="mdr-badge {{ $report->status === 'signed' ? 'mdr-badge--signed' : 'mdr-badge--draft' }}">
                                        {{ $report->status_label ?? ($report->status === 'signed' ? 'Firmado' : 'Borrador') }}
                                    </span>
                                </td>

                                {{-- ACCIONES --}}
                                <td>
                                    <div class="mdr-actions">

                                        {{-- Ver --}}
                                        <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-action-btn" title="Ver reporte">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>

                                        {{-- Descargar PDF --}}
                                        <a href="{{ route('admin.material-delivery-reports.download-pdf', $report) }}" class="mdr-action-btn" title="Descargar PDF" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>
                                            </svg>
                                        </a>

                                        {{-- Eliminar --}}
                                        @permiso('material-delivery-reports', 'delete')
                                        @if($report->isDeletable())
                                            <form
                                                method="POST"
                                                action="{{ route('admin.material-delivery-reports.destroy', $report) }}"
                                                style="display:inline;"
                                                onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="mdr-action-btn mdr-action-btn--delete" title="Eliminar reporte">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                        @endpermiso

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
            <div class="mdr-pagination">
                <span class="mdr-pagination-info">
                    Mostrando
                    <strong>{{ $reports->firstItem() }}–{{ $reports->lastItem() }}</strong>
                    de
                    <strong>{{ $reports->total() }}</strong>
                    reportes
                </span>

                <div class="mdr-pagination-nav">
                    {{-- Primera página --}}
                    @if($reports->onFirstPage())
                        <button class="mdr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
                            </svg>
                        </button>
                        <button class="mdr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"/>
                            </svg>
                        </button>
                    @else
                        <a href="{{ $reports->appends(request()->query())->url(1) }}" class="mdr-page-btn" title="Primera página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>
                            </svg>
                        </a>
                        <a href="{{ $reports->appends(request()->query())->previousPageUrl() }}" class="mdr-page-btn" title="Página anterior">
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
                            <span class="mdr-page-btn mdr-page-btn--active">{{ $page }}</span>
                        @else
                            <a href="{{ $reports->appends(request()->query())->url($page) }}" class="mdr-page-btn">{{ $page }}</a>
                        @endif
                    @endfor

                    {{-- Siguiente / Última --}}
                    @if($reports->hasMorePages())
                        <a href="{{ $reports->appends(request()->query())->nextPageUrl() }}" class="mdr-page-btn" title="Página siguiente">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                        <a href="{{ $reports->appends(request()->query())->url($lastPage) }}" class="mdr-page-btn" title="Última página">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 17 5-5-5-5"/><path d="m13 17 5-5-5-5"/>
                            </svg>
                        </a>
                    @else
                        <button class="mdr-page-btn" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </button>
                        <button class="mdr-page-btn" disabled>
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
    <div class="mdr-mobile-cards">
        @if($reports->isEmpty())
            <div class="mdr-mobile-empty">No hay reportes registrados</div>
        @else
            @foreach($reports as $report)
                @php
                    $mdrMCustomer = $report->customer ?? $report->salesOrder?->customer;
                    $mdrMCustomerLabel = $mdrMCustomer
                        ? ($mdrMCustomer->company ?: trim(($mdrMCustomer->first_name ?? '') . ' ' . ($mdrMCustomer->last_name ?? '')))
                        : '—';
                @endphp
                <div class="mdr-mobile-card">
                    <div class="mdr-mobile-card__top">
                        <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-mobile-card__num">
                            {{ $report->report_number }}
                        </a>
                        <span class="mdr-badge {{ $report->status === 'signed' ? 'mdr-badge--signed' : 'mdr-badge--draft' }}">
                            {{ $report->status_label ?? ($report->status === 'signed' ? 'Firmado' : 'Borrador') }}
                        </span>
                    </div>
                    <div class="mdr-mobile-card__order">{{ $report->salesOrder->order_number ?? '—' }}</div>
                    <div class="mdr-mobile-card__meta">📅 {{ optional($report->delivery_date)->format('d/m/Y') ?? '—' }}</div>
                    <div class="mdr-mobile-card__company">🏢 {{ $mdrMCustomerLabel }}</div>
                    <div class="mdr-mobile-card__divider"></div>
                    <div class="mdr-mobile-card__footer">
                        <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-mobile-card__btn">
                            Ver →
                        </a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Botón nuevo reporte flotante en mobile --}}
    @permiso('material-delivery-reports', 'create')
    <a href="{{ route('admin.material-delivery-reports.create') }}" class="mdr-mobile-new-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/><path d="M12 5v14"/>
        </svg>
        Nuevo Reporte
    </a>
    @endpermiso

</div>{{-- /mdr-wrapper --}}

@endsection

@push('scripts')
<script>
    /* Row click → ir al show, excepto cuando se hace click en los botones de acción */
    document.querySelectorAll('.mdr-table tbody tr').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.mdr-actions')) return;
            var folioLink = row.querySelector('.mdr-folio-link');
            if (folioLink) window.location.href = folioLink.href;
        });
    });
</script>
@endpush
