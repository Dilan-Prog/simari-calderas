@extends('customers.layouts.master')

@section('title', 'Historial de servicios')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
    <style>
        .hx-filters { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:1rem; }
        .hx-input, .hx-select {
            height:36px; padding:0 .75rem; border:1px solid var(--ts-border); border-radius:6px;
            font-size:.8125rem; color:var(--ts-text-body); background:var(--ts-bg-white); outline:none;
            font-family:var(--ts-font);
        }
        .hx-input { min-width:220px; flex:1 1 220px; max-width:360px; }
        .hx-input:focus, .hx-select:focus { border-color:var(--ts-primary); }
        .hx-btn {
            height:36px; padding:0 1rem; border:none; border-radius:6px; cursor:pointer;
            background:var(--ts-primary); color:#fff; font-size:.8125rem; font-weight:500;
            display:inline-flex; align-items:center; text-decoration:none;
        }
        .hx-btn:hover { background:var(--ts-primary-hover); color:#fff; }
        .hx-btn--ghost { background:var(--ts-bg-white); color:var(--ts-text-body); border:1px solid var(--ts-border); }
        .hx-btn--ghost:hover { background:#F9FAFB; color:var(--ts-text-body); }
        .hx-report {
            display:inline-flex; align-items:center; gap:.25rem; font-size:.75rem; font-weight:500;
            color:var(--ts-primary); text-decoration:none; padding:.125rem .5rem; margin:.125rem .25rem .125rem 0;
            border:1px solid #FFD9C7; border-radius:999px; background:#FFF7F4; white-space:nowrap;
        }
        .hx-report:hover { background:#FFEDE3; }
        .hx-none { color:var(--ts-text-light); font-size:.8125rem; }
        .hx-techs { font-size:.8125rem; color:var(--ts-text-body); }
        @media (max-width: 768px) {
            .hx-input { max-width:none; flex-basis:100%; }
        }
    </style>
@endpush

@section('content')
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Historial</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Historial de servicios</h1>
            <p class="ts-subtitle">Servicios ya realizados en tus instalaciones</p>
        </div>
    </div>

    <form method="GET" action="{{ route('customer.history') }}" class="hx-filters">
        <input type="search" name="q" class="hx-input" value="{{ $search }}"
               placeholder="Buscar por folio o tipo de servicio" maxlength="100">
        <select name="year" class="hx-select" aria-label="Año">
            <option value="">Todos los años</option>
            @foreach($years as $y)
                <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
            @endforeach
        </select>
        <button type="submit" class="hx-btn">Filtrar</button>
        @if($search !== '' || $year)
            <a href="{{ route('customer.history') }}" class="hx-btn hx-btn--ghost">Limpiar</a>
        @endif
    </form>

    {{-- Tabla (escritorio) --}}
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>FOLIO</th>
                    <th>TIPO</th>
                    <th>FECHA</th>
                    <th>TÉCNICOS</th>
                    <th>REPORTES</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                    @php $serviceReports = $reports->get($service->id, collect()); @endphp
                    <tr>
                        <td><div class="ts-table-num">{{ $service->service_number }}</div></td>
                        <td><div class="ts-table-type">{{ $service->service_type_label }}</div></td>
                        <td class="ts-table-date">
                            {{ $service->service_date ? $service->service_date->format('d/m/Y') : '—' }}
                            @if($service->service_time)
                                <br><span style="font-size:0.75rem;color:#9CA3AF">{{ substr($service->service_time, 0, 5) }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="hx-techs">{{ $service->assignedTechnicians->pluck('full_name')->implode(', ') ?: '—' }}</span>
                        </td>
                        <td>
                            @forelse($serviceReports as $report)
                                <a class="hx-report" href="{{ route('customer.service-reports.show', $report->id) }}">
                                    {{ $report->report_number }}
                                </a>
                            @empty
                                <span class="hx-none">—</span>
                            @endforelse
                        </td>
                        <td>
                            <div class="ts-table-actions">
                                <a href="{{ route('customer.technical-services.show', $service->id) }}"
                                   class="ts-action-btn ts-action-btn--primary" title="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="ts-table-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8 2v4M16 2v4"/>
                                    <rect width="18" height="18" x="3" y="4" rx="2"/>
                                    <path d="M3 10h18"/>
                                </svg>
                                <p>{{ ($search !== '' || $year) ? 'No hay servicios realizados con esos filtros' : 'Aún no hay servicios realizados' }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($services->hasPages())
            <div class="ts-pagination">
                <span class="ts-pagination__info">
                    Mostrando {{ $services->firstItem() }}–{{ $services->lastItem() }}
                    de {{ $services->total() }} registros
                </span>
                <div class="ts-pagination__links">
                    {{ $services->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- Tarjetas (móvil) --}}
    <div class="ts-mobile-cards">
        @forelse($services as $service)
            @php $serviceReports = $reports->get($service->id, collect()); @endphp
            <div class="ts-mobile-card">
                <div class="ts-mobile-card__top">
                    <span class="ts-mobile-card__num">{{ $service->service_number }}</span>
                    <span class="ts-badge ts-badge--completed">Completado</span>
                </div>
                <div class="ts-mobile-card__type">{{ $service->service_type_label }}</div>
                <div class="ts-mobile-card__meta">
                    <span>📅 {{ $service->service_date ? $service->service_date->format('d/m/Y') : '—' }}</span>
                </div>
                @if($serviceReports->isNotEmpty())
                    <div style="margin-top:.5rem">
                        @foreach($serviceReports as $report)
                            <a class="hx-report" href="{{ route('customer.service-reports.show', $report->id) }}">{{ $report->report_number }}</a>
                        @endforeach
                    </div>
                @endif
                <div class="ts-mobile-card__divider"></div>
                <div class="ts-mobile-card__footer">
                    <span class="ts-mobile-card__techs">
                        👤 {{ $service->assignedTechnicians->pluck('full_name')->implode(', ') ?: 'Sin técnicos' }}
                    </span>
                    <a href="{{ route('customer.technical-services.show', $service->id) }}" class="ts-mobile-card__btn">Ver →</a>
                </div>
            </div>
        @empty
            <div class="ts-mobile-empty">
                <p>{{ ($search !== '' || $year) ? 'No hay servicios realizados con esos filtros' : 'Aún no hay servicios realizados' }}</p>
            </div>
        @endforelse

        @if($services->hasPages())
            <div class="ts-pagination" style="justify-content:center">
                <div class="ts-pagination__links">{{ $services->links() }}</div>
            </div>
        @endif
    </div>

</div>
@endsection
