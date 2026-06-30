@extends('customers.layouts.master')

@section('title', 'Servicios Técnicos')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page" id="ts-page">

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
            <p class="ts-subtitle">Consulta tus servicios técnicos programados y realizados</p>
        </div>
    </div>

    {{-- Filtro de estado --}}
    <div class="ts-toolbar">
        <form method="GET" action="{{ route('customer.technical-services.index') }}" class="ts-filters">
            <div class="ts-select-wrap">
                <select class="ts-select" name="status" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="scheduled"   {{ request('status') == 'scheduled'   ? 'selected' : '' }}>Programado</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                    <option value="completed"   {{ request('status') == 'completed'   ? 'selected' : '' }}>Completado</option>
                    <option value="cancelled"   {{ request('status') == 'cancelled'   ? 'selected' : '' }}>Cancelado</option>
                </select>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </div>
        </form>
    </div>

    {{-- ── TABLE VIEW ── --}}
    <div class="ts-table-wrap">
        <table class="ts-table">
            <thead>
                <tr>
                    <th>FOLIO</th>
                    <th>TIPO</th>
                    <th>FECHA</th>
                    <th>ESTADO</th>
                    <th>TÉCNICOS</th>
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
                    <td><div class="ts-table-type">{{ $service->service_type_label }}</div></td>
                    <td class="ts-table-date">
                        {{ $service->service_date ? \Carbon\Carbon::parse($service->service_date)->format('d M Y') : '—' }}
                        @if($service->service_time)
                            <br><span style="font-size:0.75rem;color:#9CA3AF">{{ $service->service_time }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                            {{ $statusLabels[$service->status] ?? $service->status }}
                        </span>
                    </td>
                    <td>
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
                            <a href="{{ route('customer.technical-services.show', $service) }}"
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
                            <p>No hay servicios registrados</p>
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

    {{-- ── MOBILE CARDS ── --}}
    <div class="ts-mobile-cards" id="ts-mobile-cards">
    @forelse($services as $service)
    <div class="ts-mobile-card">
        <div class="ts-mobile-card__top">
            <span class="ts-mobile-card__num">{{ $service->service_number }}</span>
            <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                {{ $statusLabels[$service->status] ?? $service->status }}
            </span>
        </div>
        <div class="ts-mobile-card__type">{{ $service->service_type_label }}</div>
        <div class="ts-mobile-card__meta">
            <span>📅 {{ $service->service_date ? \Carbon\Carbon::parse($service->service_date)->format('d M Y') : '—' }}</span>
            @if($service->service_time)
                <span>⏰ {{ $service->service_time }}</span>
            @endif
        </div>
        <div class="ts-mobile-card__divider"></div>
        <div class="ts-mobile-card__footer">
            <span class="ts-mobile-card__techs">
                👤 {{ $service->assignedTechnicians->pluck('full_name')->implode(', ') ?: 'Sin técnicos' }}
            </span>
            <a href="{{ route('customer.technical-services.show', $service) }}" class="ts-mobile-card__btn">
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

</div>{{-- /ts-page --}}

@endsection
