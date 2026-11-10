@extends('admin.layouts.master')

@section('title', 'Servicios Técnicos')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page ts-admin ts-admin--index" id="ts-page">

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
                <option value="requested" @selected(request('status') === 'requested')>Solicitudes de clientes</option>
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
        {{-- Filtros extra en móvil: técnico y rango de fechas --}}
        <details class="ts-mobile-more" id="ts-mobile-more">
            <summary class="ts-mobile-more__summary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>
                </svg>
                Más filtros
                <span class="ts-mobile-more__count" id="ts-mobile-more-count" hidden></span>
            </summary>
            <div class="ts-mobile-more__body">
                <label class="ts-mobile-more__field ts-mobile-more__field--full">
                    <span>Técnico</span>
                    <select class="ts-mobile-status-select" id="ts-mobile-tech-filter">
                        <option value="">Todos los técnicos</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->full_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ts-mobile-more__field">
                    <span>Desde</span>
                    <input type="date" class="ts-mobile-date" id="ts-mobile-date-from">
                </label>
                <label class="ts-mobile-more__field">
                    <span>Hasta</span>
                    <input type="date" class="ts-mobile-date" id="ts-mobile-date-to">
                </label>
                <button type="button" class="ts-mobile-more__clear" id="ts-mobile-filters-clear">Limpiar filtros</button>
            </div>
        </details>
        <div class="ts-mobile-results" id="ts-mobile-results" aria-live="polite"></div>
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

    {{-- Solicitudes de clientes (portal de servicios) --}}
    @php $showingRequests = request('status') === 'requested'; @endphp
    @if(($pendingRequests ?? 0) > 0 || $showingRequests)
    <div class="ts-request-banner">
        <div class="ts-request-banner__text">
            <strong>
                @if($pendingRequests > 0)
                    {{ $pendingRequests }} {{ $pendingRequests === 1 ? 'solicitud de cliente por revisar' : 'solicitudes de clientes por revisar' }}
                @else
                    No hay solicitudes de clientes pendientes
                @endif
            </strong>
            <span>Enviadas desde el portal de servicios. Abre la solicitud para programarla (pasa a Programado) o cancelarla.</span>
        </div>
        @if($showingRequests)
            <a href="{{ route('admin.technical-services.index') }}" class="ts-btn">Ver todos los servicios</a>
        @else
            <a href="{{ route('admin.technical-services.index', ['status' => 'requested']) }}" class="ts-btn ts-btn--primary">Revisar solicitudes</a>
        @endif
    </div>
    <style>
        .ts-request-banner{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding:.875rem 1rem;background:#FFF7ED;border:1px solid #FED7AA;border-radius:8px;color:#9A3412;font-size:.8125rem}
        .ts-request-banner__text{display:flex;flex-direction:column;gap:.125rem}
        .ts-request-banner__text strong{font-size:.9375rem;color:#7C2D12}
        .ts-badge--request{background:#FFF7ED;color:#C2410C;border-color:#FED7AA}
    </style>
    @endif

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
                            'draft'       => 'ts-badge--draft',
                            'scheduled'   => 'ts-badge--scheduled',
                            'in_progress' => 'ts-badge--in-progress',
                            'completed'   => 'ts-badge--completed',
                            'cancelled'   => 'ts-badge--cancelled',
                        ];
                        $statusLabels = [
                            'draft'       => 'Borrador',
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
                            @if($service->is_customer_request)
                                <span class="ts-badge ts-badge--request">Solicitud de cliente</span>
                            @else
                                <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                                    {{ $statusLabels[$service->status] ?? $service->status }}
                                </span>
                            @endif
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
            'draft'       => 'ts-badge--draft',
            'scheduled'   => 'ts-badge--scheduled',
            'in_progress' => 'ts-badge--in-progress',
            'completed'   => 'ts-badge--completed',
            'cancelled'   => 'ts-badge--cancelled',
        ];
        $mStatusLabels = [
            'draft'       => 'Borrador',
            'scheduled'   => 'Programado',
            'in_progress' => 'En Proceso',
            'completed'   => 'Completado',
            'cancelled'   => 'Cancelado',
        ];
    @endphp
    @forelse($services as $service)
    @php
        $mDate  = $service->service_date ? \Carbon\Carbon::parse($service->service_date) : null;
        $mTechs = $service->assignedTechnicians;
    @endphp
    <article class="ts-mobile-card"
         data-status="{{ $service->is_customer_request ? 'requested' : $service->status }}"
         data-techs=",{{ $mTechs->pluck('id')->implode(',') }},"
         data-date="{{ $mDate ? $mDate->format('Y-m-d') : '' }}"
         data-search="{{ mb_strtolower($service->service_number . ' ' . $service->customer_name . ' ' . $service->service_type_label . ' ' . ($service->customer_company ?? '') . ' ' . $mTechs->pluck('full_name')->implode(' ')) }}">
        <div class="ts-mobile-card__top">
            <span class="ts-mobile-card__num" data-label="Folio">{{ $service->service_number }}</span>
            @if($service->is_customer_request)
                <span class="ts-badge ts-badge--request" data-label="Estado">Solicitud de cliente</span>
            @else
                <span class="ts-badge {{ $mStatusClasses[$service->status] ?? '' }}" data-label="Estado">
                    {{ $mStatusLabels[$service->status] ?? $service->status }}
                </span>
            @endif
        </div>
        <div class="ts-mobile-card__type" data-label="Servicio">{{ $service->service_type_label }}</div>
        <dl class="ts-mcard-fields">
            <div class="ts-mcard-field ts-mcard-field--full" data-label="Cliente">
                <dt>Cliente</dt>
                <dd>{{ $service->customer_name }}{{ $service->customer_company && $service->customer_company !== $service->customer_name ? ' — ' . $service->customer_company : '' }}</dd>
            </div>
            <div class="ts-mcard-field" data-label="Fecha">
                <dt>Fecha</dt>
                <dd>{{ $mDate ? $mDate->format('d M Y') : '—' }}@if($service->service_time) <span class="ts-mcard-muted">· {{ substr($service->service_time, 0, 5) }}</span>@endif</dd>
            </div>
            <div class="ts-mcard-field" data-label="Técnico">
                <dt>{{ $mTechs->count() > 1 ? 'Técnicos' : 'Técnico' }}</dt>
                <dd class="{{ $mTechs->isEmpty() ? 'ts-mcard-muted' : '' }}">{{ $mTechs->pluck('full_name')->implode(', ') ?: 'Sin técnicos' }}</dd>
            </div>
        </dl>
        <div class="ts-mobile-card__footer">
            <a href="{{ route('admin.technical-services.show', $service) }}" class="ts-mobile-card__btn ts-mobile-card__btn--primary">
                Ver detalle
            </a>
            @if($service->isEditable())
            <a href="{{ route('admin.technical-services.edit', $service) }}" class="ts-mobile-card__btn">
                Editar
            </a>
            @endif
        </div>
    </article>
    @empty
    <div class="ts-mobile-empty">
        <p>No hay servicios registrados</p>
    </div>
    @endforelse
    <div class="ts-mobile-empty ts-mobile-noresults" id="ts-mobile-noresults" hidden>
        <p>Ningún servicio coincide con los filtros</p>
    </div>
    @if(is_object($services) && method_exists($services, 'links') && $services->hasPages())
    <div class="ts-mobile-pagination">
        <span class="ts-mobile-pagination__info">
            {{ $services->firstItem() }}–{{ $services->lastItem() }} de {{ $services->total() }}
        </span>
        {{ $services->links('admin.components.pagination') }}
    </div>
    @endif
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
<div class="ts-overlay ts-admin-sheet" id="ts-quick-overlay">
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
    var techSelect   = document.getElementById('ts-mobile-tech-filter');
    var dateFrom     = document.getElementById('ts-mobile-date-from');
    var dateTo       = document.getElementById('ts-mobile-date-to');
    var clearBtn     = document.getElementById('ts-mobile-filters-clear');
    var moreCount    = document.getElementById('ts-mobile-more-count');
    var resultsEl    = document.getElementById('ts-mobile-results');
    var noResults    = document.getElementById('ts-mobile-noresults');
    var cards        = document.querySelectorAll('#ts-mobile-cards .ts-mobile-card');

    function filterCards() {
        var q      = (searchInput ? searchInput.value.trim().toLowerCase() : '');
        var status = (statusSelect ? statusSelect.value : '');
        var tech   = (techSelect ? techSelect.value : '');
        var from   = (dateFrom ? dateFrom.value : '');
        var to     = (dateTo ? dateTo.value : '');
        var visible = 0;
        cards.forEach(function (card) {
            var d = card.dataset.date || '';
            var matchSearch = !q      || (card.dataset.search || '').includes(q);
            var matchStatus = !status || card.dataset.status === status;
            var matchTech   = !tech   || (card.dataset.techs || '').indexOf(',' + tech + ',') !== -1;
            var matchFrom   = !from   || (d && d >= from);
            var matchTo     = !to     || (d && d <= to);
            var show = matchSearch && matchStatus && matchTech && matchFrom && matchTo;
            card.classList.toggle('ts-mobile-card--hidden', !show);
            if (show) visible++;
        });
        var extra = (tech ? 1 : 0) + (from ? 1 : 0) + (to ? 1 : 0);
        if (moreCount) { moreCount.hidden = !extra; moreCount.textContent = extra; }
        if (resultsEl && cards.length) {
            resultsEl.textContent = visible === cards.length
                ? cards.length + (cards.length === 1 ? ' servicio' : ' servicios')
                : visible + ' de ' + cards.length + ' servicios';
        }
        if (noResults) noResults.hidden = !(cards.length && visible === 0);
    }

    if (clearBtn) clearBtn.addEventListener('click', function () {
        if (techSelect) techSelect.value = '';
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';
        filterCards();
    });
    [techSelect, dateFrom, dateTo].forEach(function (el) {
        if (el) el.addEventListener('change', filterCards);
    });
    filterCards();

    // "Revisar solicitudes" (?status=requested) abre directo la vista de tabla ya filtrada.
    @if(request('status') === 'requested')
    window.addEventListener('load', function () {
        var btn = document.querySelector('.ts-view-toggle__btn[data-view="table"]');
        if (btn) btn.click();
    });
    @endif

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
