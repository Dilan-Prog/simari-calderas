@extends('customers.layouts.master')

@section('title', 'Servicio ' . $service->service_number)

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page">

    {{-- ── Header ── --}}
    <div class="ts-show-header">
        <div style="display:flex;align-items:flex-start;gap:0.75rem">
            <a href="{{ route('customer.technical-services.index') }}" class="ts-show-back" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7M19 12H5"/>
                </svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:0.25rem">
                    <span>Panel de Control</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <a href="{{ route('customer.technical-services.index') }}"
                       style="color:inherit;text-decoration:none">Servicios Técnicos</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="ts-breadcrumb__current">{{ $service->service_number }}</span>
                </div>
                <div class="ts-show-title-row">
                    <span class="ts-show-num">{{ $service->service_number }}</span>
                    @php
                        $statusClasses = [
                            'scheduled'   => 'ts-badge--scheduled',
                            'in_progress' => 'ts-badge--in-progress',
                            'completed'   => 'ts-badge--completed',
                            'cancelled'   => 'ts-badge--cancelled',
                        ];
                        $priorityClasses = ['normal'=>'ts-priority--normal','high'=>'ts-priority--high','urgent'=>'ts-priority--urgent'];
                        $priorityLabels  = ['normal'=>'Normal','high'=>'Alta','urgent'=>'Urgente'];
                    @endphp
                    <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                        {{ $service->status_label ?? $service->status }}
                    </span>
                    <span class="ts-priority {{ $priorityClasses[$service->priority ?? 'normal'] ?? 'ts-priority--normal' }}">
                        {{ $priorityLabels[$service->priority ?? 'normal'] ?? 'Normal' }}
                    </span>
                </div>
                <h1 class="ts-show-title">{{ $service->service_type_label }}</h1>
            </div>
        </div>
    </div>

    {{-- ── Status timeline ── --}}
    @php
        $timeline = [
            'scheduled'   => ['Creado', 'Programado'],
            'in_progress' => ['Creado', 'Programado', 'En Proceso'],
            'completed'   => ['Creado', 'Programado', 'En Proceso', 'Completado'],
            'cancelled'   => ['Creado', 'Cancelado'],
        ];
        $tlSteps = $timeline[$service->status] ?? ['Creado'];
    @endphp
    <div class="ts-card" style="margin-bottom:1rem">
        <div class="ts-timeline">
            @foreach($tlSteps as $tlStep)
                <div class="ts-tl-item {{ $loop->last ? 'ts-tl-item--active' : '' }}">
                    <div class="ts-tl-dot"></div>
                    {{ $tlStep }}
                </div>
                @if(!$loop->last)
                    <div class="ts-tl-arrow"></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="ts-show-grid">

        {{-- ── General info ── --}}
        <div class="ts-card">
            <div class="ts-card__title">Información General</div>
            <div class="ts-card__divider"></div>
            <div class="ts-detail-grid" style="gap:1.25rem 1.5rem">
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Folio</span>
                    <span class="ts-detail-value" style="font-family:monospace;color:var(--ts-primary)">
                        {{ $service->service_number }}
                    </span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Tipo de Servicio</span>
                    <span class="ts-detail-value">{{ $service->service_type_label }}</span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Fecha</span>
                    <span class="ts-detail-value">
                        {{ $service->service_date ? \Carbon\Carbon::parse($service->service_date)->format('d M Y') : '—' }}
                    </span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Hora</span>
                    <span class="ts-detail-value">{{ $service->service_time ?? '—' }}</span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Duración Estimada</span>
                    <span class="ts-detail-value">{{ $service->estimated_duration ?? '—' }}</span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Prioridad</span>
                    <span class="ts-detail-value">
                        <span class="ts-priority {{ $priorityClasses[$service->priority ?? 'normal'] ?? 'ts-priority--normal' }}">
                            {{ $priorityLabels[$service->priority ?? 'normal'] ?? 'Normal' }}
                        </span>
                    </span>
                </div>
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Descripción</span>
                    <span class="ts-detail-value">{{ $service->description ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ── Service location ── --}}
        <div class="ts-card">
            <div class="ts-card__title">Ubicación del Servicio</div>
            <div class="ts-card__divider"></div>
            <div class="ts-detail-grid" style="gap:1.25rem 1.5rem">
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Dirección</span>
                    <span class="ts-detail-value">{{ $service->address ?? '—' }}</span>
                </div>
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Referencia</span>
                    <span class="ts-detail-value">{{ $service->reference ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ── Technicians ── --}}
        <div class="ts-card">
            <div class="ts-card__title">Técnicos Asignados</div>
            <div class="ts-card__divider"></div>
            @if($service->assignedTechnicians->isEmpty())
                <p style="font-size:0.875rem;color:#9CA3AF;text-align:center;padding:1rem 0">
                    Sin técnicos asignados
                </p>
            @else
            <div class="ts-tech-list">
                @foreach($service->assignedTechnicians as $tech)
                <div class="ts-tech-item">
                    <div class="ts-tech-avatar-lg">
                        {{ mb_strtoupper(mb_substr($tech->full_name, 0, 2)) }}
                    </div>
                    <div class="ts-tech-info">
                        <div class="ts-tech-info__name">{{ $tech->full_name }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ── Materials ── --}}
        <div class="ts-card">
            <div class="ts-card__title">Materiales Planificados</div>
            <div class="ts-card__divider"></div>
            @if($service->plannedMaterials->isEmpty())
                <p style="font-size:0.875rem;color:#9CA3AF;text-align:center;padding:1rem 0">
                    Sin materiales registrados
                </p>
            @else
            <div class="ts-mat-list">
                @foreach($service->plannedMaterials as $mat)
                <div class="ts-mat-item">
                    <span class="ts-mat-item__name">{{ $mat->product_name }}</span>
                    <span class="ts-mat-item__qty">{{ $mat->quantity }}</span>
                    <span class="ts-mat-item__unit">{{ $mat->unit ?? '' }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
