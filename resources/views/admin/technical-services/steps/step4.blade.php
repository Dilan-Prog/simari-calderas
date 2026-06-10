<div class="ts-card">
    <h2 class="ts-card__title">Confirmación</h2>
    <p class="ts-card__subtitle">Revisa la información antes de programar el servicio</p>
    <div class="ts-card__divider"></div>

    {{-- Datos generales --}}
    <div class="ts-confirm-section">
        <div class="ts-confirm-section__title">Datos Generales</div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Folio</span>
            <span class="ts-confirm-row__val" style="font-family:monospace;color:var(--ts-primary)">
                {{ $service->service_number }}
            </span>
        </div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Tipo de servicio</span>
            <span class="ts-confirm-row__val">{{ $service->service_type_label }}</span>
        </div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Cliente</span>
            <span class="ts-confirm-row__val">
                {{ $service->customer?->company ?: trim(($service->customer?->first_name ?? '') . ' ' . ($service->customer?->last_name ?? '')) }}
            </span>
        </div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Fecha y hora</span>
            <span class="ts-confirm-row__val">
                {{ $service->service_date ? $service->service_date->format('d M Y') : '—' }}
                {{ $service->service_time ? '· ' . substr($service->service_time, 0, 5) : '' }}
            </span>
        </div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Duración estimada</span>
            <span class="ts-confirm-row__val">{{ $service->estimated_duration_label }}</span>
        </div>
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Prioridad</span>
            <span class="ts-confirm-row__val">{{ $service->priority_label }}</span>
        </div>
        @if($service->address)
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Dirección</span>
            <span class="ts-confirm-row__val">{{ $service->address }}</span>
        </div>
        @endif
        @if($service->description)
        <div class="ts-confirm-row">
            <span class="ts-confirm-row__key">Descripción</span>
            <span class="ts-confirm-row__val">{{ $service->description }}</span>
        </div>
        @endif
    </div>

    {{-- Técnicos --}}
    <div class="ts-confirm-section">
        <div class="ts-confirm-section__title">
            Técnicos ({{ $service->assignedTechnicians->count() }})
        </div>
        @if($service->assignedTechnicians->isEmpty())
            <p style="font-size:0.875rem;color:#9CA3AF">Sin técnicos asignados</p>
        @else
            @foreach($service->assignedTechnicians as $tech)
            <div class="ts-confirm-row">
                <span class="ts-confirm-row__key" style="display:flex;align-items:center;gap:0.5rem">
                    <div class="ts-tech-avatar" style="width:22px;height:22px;font-size:0.625rem;flex-shrink:0">
                        {{ mb_strtoupper(mb_substr($tech->first_name, 0, 1)) }}
                    </div>
                    {{ trim("{$tech->first_name} {$tech->last_name}") }}
                </span>
                <span class="ts-confirm-row__val" style="color:#9CA3AF;font-size:0.75rem">
                    {{ $tech->position ?? $tech->email ?? '' }}
                </span>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Materiales --}}
    <div class="ts-confirm-section">
        <div class="ts-confirm-section__title">
            Materiales ({{ $service->plannedMaterials->count() }})
        </div>
        @if($service->plannedMaterials->isEmpty())
            <p style="font-size:0.875rem;color:#9CA3AF">Sin materiales planificados</p>
        @else
            @foreach($service->plannedMaterials as $mat)
            <div class="ts-confirm-row">
                <span class="ts-confirm-row__key">{{ $mat->product_name }}</span>
                <span class="ts-confirm-row__val">
                    {{ $mat->quantity }} {{ $mat->unit_label }}
                </span>
            </div>
            @endforeach
        @endif
    </div>

</div>

{{-- Wizard footer — POST regular (no AJAX) --}}
<div class="ts-wizard-footer">
    <a href="{{ route('admin.technical-services.step', [$service, 3]) }}" class="ts-btn">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 19-7-7 7-7M19 12H5"/>
        </svg>
        Anterior
    </a>

    <form action="{{ route('admin.technical-services.save-step', [$service, 4]) }}"
          method="POST" style="display:inline">
        @csrf
        <button type="submit" class="ts-btn ts-btn--primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
            Confirmar y Programar
        </button>
    </form>
</div>
