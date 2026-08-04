@extends('admin.layouts.master')

@section('title', 'Servicios Técnicos')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page" id="ts-page">

    {{-- Toolbar móvil (oculto en desktop) --}}
    <div class="ts-mobile-toolbar">
        <div class="ts-mobile-search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="search" class="ts-mobile-search" id="ts-mobile-search" placeholder="Buscar servicio...">
        </div>
        <div class="ts-mobile-select-wrap">
            <select class="ts-mobile-status-select" id="ts-mobile-status-filter">
                <option value="">Todos los estados</option>
                <option value="scheduled">Programado</option>
                <option value="in_progress">En Proceso</option>
                <option value="completed">Completado</option>
                <option value="cancelled">Cancelado</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </div>
    </div>

    {{-- Breadcrumb --}}
    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Servicios Técnicos</span>
    </div>

    {{-- Header --}}
    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Servicios Técnicos</h1>
            <p class="ts-subtitle">Programa y gestiona los servicios técnicos</p>
        </div>
        <div class="ts-header-actions">
            <div class="ts-view-toggle">
                <button class="ts-view-toggle__btn ts-view-toggle__btn--active" data-view="calendar" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 2v4M16 2v4"/>
                        <rect width="18" height="18" x="3" y="4" rx="2"/>
                        <path d="M3 10h18"/>
                    </svg>
                    Calendario
                </button>
                <button class="ts-view-toggle__btn" data-view="table" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h18M3 18h18M3 6h18"/>
                    </svg>
                    Tabla
                </button>
            </div>
            <a href="{{ route('admin.technical-services.create') }}" class="ts-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M12 5v14"/>
                </svg>
                Nuevo Servicio
            </a>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="ts-toolbar">
        <div class="ts-period-tabs">
            <button class="ts-period-tab ts-period-tab--active" data-period="month" type="button">Mes</button>
            <button class="ts-period-tab" data-period="week" type="button">Semana</button>
            <button class="ts-period-tab" data-period="day" type="button">Día</button>
        </div>
        <div class="ts-filters">
            <div class="ts-select-wrap">
                <select class="ts-select" id="ts-filter-tech">
                    <option value="">Todos los técnicos</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                    @endforeach
                </select>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
            <div class="ts-select-wrap">
                <select class="ts-select" id="ts-filter-status">
                    <option value="">Todos los estados</option>
                    <option value="scheduled">Programado</option>
                    <option value="in_progress">En Proceso</option>
                    <option value="completed">Completado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- ── CALENDAR VIEW ───────────────────────────────── --}}
    <div id="ts-calendar-view">
        <div class="ts-nav-card">
            <div class="ts-nav-controls">
                <button class="ts-nav-btn" id="ts-nav-prev" type="button" title="Anterior">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </button>
                <span class="ts-nav-title" id="ts-nav-title">
                    {{ $currentMonth->translatedFormat('F Y') }}
                </span>
                <button class="ts-nav-btn" id="ts-nav-next" type="button" title="Siguiente">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>
                <button class="ts-nav-today" id="ts-nav-today" type="button">Hoy</button>
            </div>
        </div>

        <script>
        window.__tsConfig = {
            services: @json($calendarServices),
            month: {{ $currentMonth->month }},
            year: {{ $currentYear }}
        };
        </script>
        <div id="ts-calendar"></div>

        <div class="ts-legend">
            <span class="ts-legend__label">Leyenda:</span>
            <span class="ts-legend__item ts-legend__item--draft">Borrador</span>
            <span class="ts-legend__item ts-legend__item--scheduled">Programado</span>
            <span class="ts-legend__item ts-legend__item--in_progress">En Proceso</span>
            <span class="ts-legend__item ts-legend__item--completed">Completado</span>
            <span class="ts-legend__item ts-legend__item--cancelled">Cancelado</span>
        </div>
    </div>

    {{-- ── TABLE VIEW ──────────────────────────────────── --}}
    <div id="ts-table-view" style="display:none">
        <div style="display:flex;justify-content:flex-end;margin-bottom:8px;">
            @include('admin.components._column_visibility_menu', [
                'tableKey' => 'technical-services.index',
                'columnDefs' => [
                    'cliente' => 'Cliente',
                    'tipo' => 'Tipo',
                    'fecha' => 'Fecha',
                    'estado' => 'Estado',
                    'tecnicos' => 'Técnicos',
                ],
            ])
        </div>
        <div class="ts-table-wrap">
            <table class="ts-table">
                <thead>
                    <tr>
                        <th>FOLIO</th>
                        <th data-col="cliente">CLIENTE</th>
                        <th data-col="tipo">TIPO</th>
                        <th data-col="fecha">FECHA</th>
                        <th data-col="estado">ESTADO</th>
                        <th data-col="tecnicos">TÉCNICOS</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusClasses = [
                            'scheduled'   => 'ts-badge--scheduled',
                            'in_progress' => 'ts-badge--in-progress',
                            'completed'   => 'ts-badge--completed',
                            'cancelled'   => 'ts-badge--cancelled',
                        ];
                        $statusLabels = [
                            'scheduled'   => 'Programado',
                            'in_progress' => 'En Proceso',
                            'completed'   => 'Completado',
                            'cancelled'   => 'Cancelado',
                        ];
                    @endphp

                    @forelse($services as $service)
                    <tr>
                        <td><div class="ts-table-num">{{ $service->service_number }}</div></td>
                        <td data-col="cliente"><div class="ts-table-customer">{{ $service->customer_name }}</div></td>
                        <td data-col="tipo"><div class="ts-table-type">{{ $service->service_type_label }}</div></td>
                        <td class="ts-table-date" data-col="fecha">
                            {{ $service->service_date ? \Carbon\Carbon::parse($service->service_date)->format('d M Y') : '—' }}
                            @if($service->service_time)
                                <br><span style="font-size:0.75rem;color:#9CA3AF">{{ $service->service_time }}</span>
                            @endif
                        </td>
                        <td data-col="estado">
                            <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                                {{ $statusLabels[$service->status] ?? $service->status }}
                            </span>
                        </td>
                        <td data-col="tecnicos">
                            <div class="ts-table-techs">
                                @foreach($service->assignedTechnicians->take(3) as $tech)
                                    <div class="ts-tech-avatar" title="{{ $tech->full_name }}">
                                        {{ mb_strtoupper(mb_substr($tech->full_name, 0, 1)) }}
                                    </div>
                                @endforeach
                                @if($service->assignedTechnicians->count() > 3)
                                    <div class="ts-tech-avatar" title="{{ $service->assignedTechnicians->count() - 3 }} más">
                                        +{{ $service->assignedTechnicians->count() - 3 }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="ts-table-actions">
                                <a href="{{ route('admin.technical-services.show', $service) }}"
                                   class="ts-action-btn ts-action-btn--primary" title="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a href="{{ route('admin.technical-services.edit', $service) }}"
                                   class="ts-action-btn" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                                        <path d="m15 5 4 4"/>
                                    </svg>
                                </a>
                                @if(!in_array($service->status, ['cancelled','completed']))
                                <button type="button" class="ts-action-btn ts-action-btn--danger" title="Cancelar"
                                        onclick="TechnicalServices.confirmCancelService({{ $service->id }}, '{{ $service->service_number }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <path d="m15 9-6 6M9 9l6 6"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="ts-table-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8 2v4M16 2v4"/>
                                    <rect width="18" height="18" x="3" y="4" rx="2"/>
                                    <path d="M3 10h18"/>
                                </svg>
                                <p>No hay servicios registrados</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if(is_object($services) && method_exists($services, 'links'))
            <div class="ts-pagination">
                <span class="ts-pagination__info">
                    Mostrando {{ $services->firstItem() }}–{{ $services->lastItem() }}
                    de {{ $services->total() }} registros
                </span>
                <div class="ts-pagination__links">
                    {{ $services->links('admin.components.pagination') }}
                </div>
            </div>
            @endif
        </div>
    </div>


    {{-- ── MOBILE CARDS (ocultas en desktop) ─────────────────── --}}
    <div class="ts-mobile-cards" id="ts-mobile-cards">
    @php
        $mStatusClasses = [
            'scheduled'   => 'ts-badge--scheduled',
            'in_progress' => 'ts-badge--in-progress',
            'completed'   => 'ts-badge--completed',
            'cancelled'   => 'ts-badge--cancelled',
        ];
        $mStatusLabels = [
            'scheduled'   => 'Programado',
            'in_progress' => 'En Proceso',
            'completed'   => 'Completado',
            'cancelled'   => 'Cancelado',
        ];
    @endphp
    @forelse($services as $service)
    <div class="ts-mobile-card"
         data-status="{{ $service->status }}"
         data-search="{{ strtolower($service->service_number . ' ' . $service->customer_name . ' ' . $service->service_type_label . ' ' . ($service->customer_company ?? '')) }}">
        <div class="ts-mobile-card__top">
            <span class="ts-mobile-card__num">{{ $service->service_number }}</span>
            <span class="ts-badge {{ $mStatusClasses[$service->status] ?? '' }}">
                {{ $mStatusLabels[$service->status] ?? $service->status }}
            </span>
        </div>
        <div class="ts-mobile-card__type">{{ $service->service_type_label }}</div>
        <div class="ts-mobile-card__meta">
            <span>📅 {{ $service->service_date ? \Carbon\Carbon::parse($service->service_date)->format('d M Y') : '—' }}</span>
            @if($service->service_time)
                <span>⏰ {{ $service->service_time }}</span>
            @endif
        </div>
        <div class="ts-mobile-card__company">
            🏢 {{ $service->customer_name }}{{ $service->customer_company ? ' — ' . $service->customer_company : '' }}
        </div>
        <div class="ts-mobile-card__divider"></div>
        <div class="ts-mobile-card__footer">
            <span class="ts-mobile-card__techs">
                👤 {{ $service->assignedTechnicians->pluck('full_name')->implode(', ') ?: 'Sin técnicos' }}
            </span>
            <a href="{{ route('admin.technical-services.show', $service) }}" class="ts-mobile-card__btn">
                Ver →
            </a>
        </div>
    </div>
    @empty
    <div class="ts-mobile-empty">
        <p>No hay servicios registrados</p>
    </div>
    @endforelse
    </div>

    {{-- FAB: Nuevo Servicio (solo mobile) --}}
    <a href="{{ route('admin.technical-services.create') }}" class="ts-fab-new" title="Nuevo Servicio">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5v14"/>
        </svg>
    </a>

</div>{{-- /ts-page --}}

{{-- Quick create modal (calendar cell click) --}}
<div class="ts-overlay" id="ts-quick-overlay">
    <div class="ts-modal" id="ts-quick-modal">
        <div class="ts-modal__header">
            <span class="ts-modal__title">Nuevo Servicio</span>
            <button type="button" class="ts-modal-close" onclick="TechnicalServices.closeQuickModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <p style="font-size:0.875rem;color:#6B7280;margin:0 0 1rem">
            Fecha seleccionada: <strong id="ts-quick-date-label"></strong>
        </p>
        <form action="{{ route('admin.technical-services.create') }}" method="GET" id="ts-quick-form">
            <input type="hidden" name="date" id="ts-quick-date">
        </form>
        <div class="ts-modal__footer">
            <button type="button" class="ts-btn ts-btn--ghost" onclick="TechnicalServices.closeQuickModal()">
                Cancelar
            </button>
            <button type="submit" form="ts-quick-form" class="ts-btn ts-btn--primary">
                Crear Servicio
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var input = document.getElementById('ts-quick-date');
    var label = document.getElementById('ts-quick-date-label');
    if (input && label) {
        var months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        function updateLabel() {
            var d = input.value;
            if (!d) return;
            var p = d.split('-');
            label.textContent = p[2] + ' ' + months[parseInt(p[1],10)-1] + ' ' + p[0];
        }
        input.addEventListener('change', updateLabel);
    }
})();
</script>

@endsection

@push('scripts')
    @vite('resources/js/admin/technical-services.js')
<script>
(function () {
    var searchInput  = document.getElementById('ts-mobile-search');
    var statusSelect = document.getElementById('ts-mobile-status-filter');
    var cards        = document.querySelectorAll('#ts-mobile-cards .ts-mobile-card');

    function filterCards() {
        var q      = (searchInput ? searchInput.value.trim().toLowerCase() : '');
        var status = (statusSelect ? statusSelect.value : '');
        cards.forEach(function (card) {
            var matchSearch = !q      || (card.dataset.search || '').includes(q);
            var matchStatus = !status || card.dataset.status === status;
            card.classList.toggle('ts-mobile-card--hidden', !(matchSearch && matchStatus));
        });
    }

    if (searchInput)  searchInput.addEventListener('input',  filterCards);
    if (statusSelect) statusSelect.addEventListener('change', filterCards);
})();
</script>
<script src="{{ asset('js/admin/column-visibility.js') }}"></script>
<script>
    initColumnVisibility({
        tableKey: 'technical-services.index',
        savedColumns: @json($visibleColumns),
        saveUrl: '{{ route('admin.column-preferences.update') }}',
    });
</script>
@endpush
