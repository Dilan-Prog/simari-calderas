@extends('admin.layouts.master')

@section('title', 'Servicio ' . $service->service_number)

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page">

    {{-- ── Header ──────────────────────────────────── --}}
    <div class="ts-show-header">
        <div style="display:flex;align-items:flex-start;gap:0.75rem">
            <a href="{{ route('admin.technical-services.index') }}" class="ts-show-back" title="Volver">
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
                    <a href="{{ route('admin.technical-services.index') }}"
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
                    @endphp
                    <span class="ts-badge {{ $statusClasses[$service->status] ?? '' }}">
                        {{ $service->status_label ?? $service->status }}
                    </span>
                    @php
                        $priorityClasses = ['normal'=>'ts-priority--normal','high'=>'ts-priority--high','urgent'=>'ts-priority--urgent'];
                        $priorityLabels  = ['normal'=>'Normal','high'=>'Alta','urgent'=>'Urgente'];
                    @endphp
                    <span class="ts-priority {{ $priorityClasses[$service->priority ?? 'normal'] ?? 'ts-priority--normal' }}">
                        {{ $priorityLabels[$service->priority ?? 'normal'] ?? 'Normal' }}
                    </span>
                </div>
                <h1 class="ts-show-title">{{ $service->service_type_label }}</h1>
            </div>
        </div>

        <div class="ts-show-actions">
            @if($service->status === 'completed')
            <a href="{{ route('admin.technical-services.generate-report', $service) }}"
               class="ts-btn ts-btn--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <path d="M16 13H8M16 17H8M10 9H8"/>
                </svg>
                Generar Reporte
            </a>
            @endif

            @if($service->status === 'cancelled')
            <a href="{{ route('admin.technical-services.index') }}" class="ts-btn ts-btn--success">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                OK
            </a>

            <form action="{{ route('admin.technical-services.destroy', $service) }}"
                  method="POST"
                  style="display:inline"
                  onsubmit="return confirm('¿Eliminar definitivamente el servicio {{ $service->service_number }}? Esta acción no se puede deshacer.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="ts-btn ts-btn--danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M8 6V4h8v2"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                    </svg>
                    Eliminar
                </button>
            </form>
            @endif

            @if($service->status === 'scheduled')
            <button type="button" class="ts-btn ts-btn--warning"
                    onclick="TechnicalServices.updateServiceStatus({{ $service->id }}, 'in_progress')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                Marcar En Proceso
            </button>
            @endif

            @if($service->status === 'in_progress')
            <button type="button" class="ts-btn ts-btn--success"
                    onclick="TechnicalServices.updateServiceStatus({{ $service->id }}, 'completed')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                Marcar Completado
            </button>
            @endif

            @if($service->isEditable())
            <a href="{{ route('admin.technical-services.edit', $service) }}" class="ts-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                    <path d="m15 5 4 4"/>
                </svg>
                Editar
            </a>
            @endif

            @if(!in_array($service->status, ['cancelled','completed']))
            <button type="button" class="ts-btn ts-btn--danger"
                    onclick="TechnicalServices.confirmCancelService({{ $service->id }}, '{{ $service->service_number }}')">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m15 9-6 6M9 9l6 6"/>
                </svg>
                Cancelar Servicio
            </button>
            @endif
        </div>
    </div>

    {{-- ── Status timeline ─────────────────────────── --}}
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
            @foreach($tlSteps as $i => $tlStep)
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

        {{-- ── General info ──────────────────────── --}}
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

        {{-- ── Customer info ──────────────────────── --}}
        <div class="ts-card">
            <div class="ts-card__title">Datos del Cliente</div>
            <div class="ts-card__divider"></div>
            <div class="ts-detail-grid" style="gap:1.25rem 1.5rem">
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Cliente</span>
                    <span class="ts-detail-value">{{ $service->customer_name }}</span>
                </div>
                <div class="ts-detail-field">
                    <span class="ts-detail-label">Empresa</span>
                    <span class="ts-detail-value">{{ $service->customer_company ?? '—' }}</span>
                </div>
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Dirección</span>
                    <span class="ts-detail-value">{{ $service->address ?? '—' }}</span>
                </div>
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Referencia</span>
                    <span class="ts-detail-value">{{ $service->reference ?? '—' }}</span>
                </div>
                @if(isset($service->fromQuote) && $service->fromQuote)
                <div class="ts-detail-field" style="grid-column:1/-1">
                    <span class="ts-detail-label">Cotización</span>
                    <a href="{{ route('admin.quotes.show', $service->fromQuote) }}"
                       class="ts-detail-value ts-detail-value--link">
                        {{ $service->fromQuote->quote_number }}
                    </a>
                </div>
                @endif
            </div>

            @if(!$service->from_quote_id && auth()->user()->isAdmin())
            <div class="ts-card__divider" style="margin-top:1rem"></div>
            <p style="font-size:0.8125rem;color:#6B7280;margin:0.75rem 0 0.5rem;">
                Este servicio no tiene una cotización de origen vinculada.
            </p>
            <form method="POST" action="{{ route('admin.technical-services.attach-quote', $service) }}" style="display:flex;gap:0.5rem;">
                @csrf
                @method('PATCH')
                <select name="from_quote_id" class="ts-select-field" required style="flex:1">
                    <option value="">Seleccionar cotización aceptada...</option>
                    @foreach($acceptedQuotes as $quote)
                        <option value="{{ $quote->id }}">
                            {{ $quote->quote_number }} — {{ $quote->customer->company ?: trim("{$quote->customer->first_name} {$quote->customer->last_name}") }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="ts-btn ts-btn--primary">Vincular</button>
            </form>
            @endif
        </div>

        {{-- ── Technicians ────────────────────────── --}}
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
                        <div class="ts-tech-info__role">{{ $tech->email ?? '—' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ── Materials ──────────────────────────── --}}
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
                    @if($mat->notes)
                        <span style="font-size:0.75rem;color:#9CA3AF">— {{ $mat->notes }}</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ── Metadata ──────────────────────────────── --}}
    <div class="ts-card" style="margin-top:1rem">
        <div style="display:flex;gap:2rem;flex-wrap:wrap">
            <div class="ts-detail-field">
                <span class="ts-detail-label">Creado</span>
                <span class="ts-detail-value">
                    {{ $service->created_at ? \Carbon\Carbon::parse($service->created_at)->format('d M Y H:i') : '—' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Mobile sticky action footer ──────────── --}}
    <div class="ts-show-mobile-actions">
        @if($service->status === 'completed')
        <a href="{{ route('admin.technical-services.generate-report', $service) }}"
           class="ts-btn ts-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <path d="M16 13H8M16 17H8M10 9H8"/>
            </svg>
            Generar Reporte
        </a>
        @endif

        @if($service->status === 'scheduled')
        <button type="button" class="ts-btn ts-btn--warning"
                onclick="TechnicalServices.updateServiceStatus({{ $service->id }}, 'in_progress')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            Marcar En Proceso
        </button>
        @endif

        @if($service->status === 'in_progress')
        <button type="button" class="ts-btn ts-btn--success"
                onclick="TechnicalServices.updateServiceStatus({{ $service->id }}, 'completed')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            Marcar Completado
        </button>
        @endif

        @if($service->isEditable())
        <a href="{{ route('admin.technical-services.edit', $service) }}" class="ts-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
                <path d="m15 5 4 4"/>
            </svg>
            Editar
        </a>
        @endif

        @if($service->status === 'cancelled')
        <a href="{{ route('admin.technical-services.index') }}" class="ts-btn ts-btn--success">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            OK
        </a>
        <form action="{{ route('admin.technical-services.destroy', $service) }}"
              method="POST" style="display:contents"
              onsubmit="return confirm('¿Eliminar definitivamente el servicio {{ $service->service_number }}? Esta acción no se puede deshacer.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="ts-btn ts-btn--danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M8 6V4h8v2"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                </svg>
                Eliminar
            </button>
        </form>
        @endif

        @if(!in_array($service->status, ['cancelled','completed']))
        <button type="button" class="ts-btn ts-btn--danger"
                onclick="TechnicalServices.confirmCancelService({{ $service->id }}, '{{ $service->service_number }}')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="m15 9-6 6M9 9l6 6"/>
            </svg>
            Cancelar Servicio
        </button>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
window.__tsCurrentService = {
    id: {{ $service->id }},
    status: @json($service->status),
};
</script>
    @vite('resources/js/admin/technical-services.js')
@endpush
