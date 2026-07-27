@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 1')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step1">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes de Servicio</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <a href="{{ route('admin.service-reports.show', $report) }}" style="color:#6B7280; text-decoration:none;">{{ $report->report_number }}</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">Paso 1 — Datos Generales</span>
    </div>

    {{-- Progress bar --}}
    <div class="sr-progress">
        @php
            $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma'];
        @endphp
        @foreach($stepLabels as $i => $label)
            @php
                $n = $i + 1;
                $isActive = $n === 1;
                $isDone   = !$isActive && $n <= $report->current_step;
            @endphp
            <div class="sr-step-item {{ $isActive ? 'active' : ($isDone ? 'done' : '') }}">
                <div class="sr-step-circle">
                    @if($isDone)
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $n }}
                    @endif
                </div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form card --}}
    <div class="sr-form-card" style="margin-bottom:32px;">
        <div class="sr-form-header">
            <h2>Paso 1 — Datos Generales</h2>
            <p>Información principal del reporte y del cliente</p>
        </div>

        <form method="POST" action="{{ route('admin.service-reports.save-step', [$report, 1]) }}" id="srStep1Form">
            @csrf

            <div class="sr-form-body">

                {{-- ── Sección: Servicio ── --}}
                <div class="sr-grid-2">
                    <div class="sr-section-title">Datos del Servicio</div>

                    @php
                        $linkedServiceId = old('service_id', $report->service_id);
                        $linkedService   = $linkedServiceId ? $availableServices->firstWhere('id', (int) $linkedServiceId) : null;
                    @endphp

                    <div class="sr-field sr-full">
                        <label class="sr-label" for="serviceSearchInput">Servicio programado de origen <span class="sr-req">*</span></label>
                        <div class="sr-client-select-wrap" id="srServicePicker">
                            <input type="text" id="serviceSearchInput" class="sr-input"
                                   placeholder="Buscar por folio o cliente…" autocomplete="off"
                                   value="{{ $linkedService ? $linkedService->service_number . ' — ' . ($linkedService->customer->company ?: trim($linkedService->customer->first_name . ' ' . $linkedService->customer->last_name)) : '' }}">
                            <div id="serviceDropdown" class="sr-client-dropdown" style="display:none;">
                                @foreach($availableServices as $svc)
                                    @php
                                        $svcCustomerLabel = $svc->customer->company
                                            ?: trim("{$svc->customer->first_name} {$svc->customer->last_name}");
                                    @endphp
                                    <div class="sr-client-dropdown__item"
                                         data-id="{{ $svc->id }}"
                                         data-service-number="{{ $svc->service_number }}"
                                         data-customer-name="{{ $svcCustomerLabel }}"
                                         data-customer-company="{{ $svc->customer->company ?? '' }}"
                                         data-customer-rfc="{{ $svc->customer->rfc ?? '' }}"
                                         data-customer-phone="{{ $svc->customer->phone ?? '' }}">
                                        <span class="sr-client-dropdown__name">{{ $svc->service_number }}</span>
                                        <span class="sr-client-dropdown__company">{{ $svcCustomerLabel }}</span>
                                    </div>
                                @endforeach
                                <div class="sr-client-dropdown__empty" style="display:none;">Sin servicios programados disponibles</div>
                            </div>
                        </div>
                        <input type="hidden" name="service_id" id="serviceIdInput" value="{{ $linkedServiceId }}">
                        <span class="sr-hint">Solo se listan servicios en estado Programado, En Proceso o Completado.</span>
                        <span id="serviceIdError" class="sr-error" style="display:none">Debes seleccionar un servicio programado.</span>
                        @error('service_id')<span class="sr-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="service_date">Fecha de Servicio <span class="sr-req">*</span></label>
                        <input type="date" id="service_date" name="service_date"
                               class="sr-input {{ $errors->has('service_date') ? 'is-invalid' : '' }}"
                               value="{{ old('service_date', $report->service_date?->format('Y-m-d')) }}" required>
                        @error('service_date') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="assigned_user_id">Encargado <span class="sr-req">*</span></label>
                        <div class="sr-select-wrap">
                            <select id="assigned_user_id" name="assigned_user_id"
                                    class="sr-select {{ $errors->has('assigned_user_id') ? 'is-invalid' : '' }}" required>
                                <option value="">— Selecciona un encargado —</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ old('assigned_user_id', $report->assigned_user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->first_name }} {{ $user->last_name }}
                                        @if($user->position) — {{ $user->position }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('assigned_user_id') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="service_type">Tipo de Servicio <span class="sr-req">*</span></label>
                        <div class="sr-select-wrap">
                            <select id="service_type" name="service_type"
                                    class="sr-select {{ $errors->has('service_type') ? 'is-invalid' : '' }}" required>
                                <option value="">— Selecciona un tipo —</option>
                                @foreach($serviceTypes as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('service_type', $report->service_type) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('service_type') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field" id="customTypeField"
                         style="{{ old('service_type', $report->service_type) === 'custom' ? '' : 'display:none;' }}">
                        <label class="sr-label" for="custom_service_type">Nombre del Tipo Personalizado <span class="sr-req">*</span></label>
                        <input type="text" id="custom_service_type" name="custom_service_type"
                               class="sr-input {{ $errors->has('custom_service_type') ? 'is-invalid' : '' }}"
                               value="{{ old('custom_service_type', $report->custom_service_type) }}"
                               placeholder="Ej: Análisis Microbiológico" maxlength="100">
                        @error('custom_service_type') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="week_number">Número de Semana</label>
                        <input type="number" id="week_number" name="week_number" class="sr-input"
                               value="{{ old('week_number', $report->week_number) }}" min="1" max="53" placeholder="1–53">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="location">Ubicación / Sitio</label>
                        <input type="text" id="location" name="location" class="sr-input"
                               value="{{ old('location', $report->location) }}"
                               placeholder="Ej: Planta Monterrey" maxlength="200">
                    </div>
                </div>

                {{-- ── Sección: Cliente (derivado del servicio elegido — copia editable) ── --}}
                <div class="sr-grid-2" style="margin-top:24px;">
                    <div class="sr-section-title">Datos del Cliente</div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_name">Nombre del Cliente <span class="sr-req">*</span></label>
                        <input type="text" id="customer_name" name="customer_name"
                               class="sr-input {{ $errors->has('customer_name') ? 'is-invalid' : '' }}"
                               value="{{ old('customer_name', $report->customer_name) }}" required maxlength="200">
                        @error('customer_name') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_company">Empresa</label>
                        <input type="text" id="customer_company" name="customer_company" class="sr-input"
                               value="{{ old('customer_company', $report->customer_company) }}" maxlength="200">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_rfc">RFC</label>
                        <input type="text" id="customer_rfc" name="customer_rfc" class="sr-input"
                               value="{{ old('customer_rfc', $report->customer_rfc) }}" maxlength="20">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_phone">Teléfono</label>
                        <input type="text" id="customer_phone" name="customer_phone" class="sr-input"
                               value="{{ old('customer_phone', $report->customer_phone) }}" maxlength="30">
                    </div>
                </div>

            </div>{{-- /form-body --}}

            <div class="sr-form-footer">
                <a href="{{ route('admin.service-reports.show', $report) }}" class="sr-btn-outline">
                    ← Volver al reporte
                </a>
                <button type="submit" class="sr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const typeSelect  = document.getElementById('service_type');
    const customField = document.getElementById('customTypeField');
    const customInput = document.getElementById('custom_service_type');

    function toggleCustom() {
        const show = typeSelect.value === 'custom';
        customField.style.display = show ? '' : 'none';
        customInput.required = show;
    }
    typeSelect.addEventListener('change', toggleCustom);
    toggleCustom();

    // Servicio de origen — buscador estilo tarjetas
    const searchInput  = document.getElementById('serviceSearchInput');
    const dropdown     = document.getElementById('serviceDropdown');
    const serviceIdInput = document.getElementById('serviceIdInput');
    const serviceIdError = document.getElementById('serviceIdError');
    const allItems      = Array.from(dropdown.querySelectorAll('.sr-client-dropdown__item'));
    const emptyMsg      = dropdown.querySelector('.sr-client-dropdown__empty');

    searchInput.addEventListener('focus', function () {
        filterServices(this.value);
        dropdown.style.display = 'block';
    });

    searchInput.addEventListener('input', function () {
        filterServices(this.value);
        dropdown.style.display = 'block';
    });

    function filterServices(q) {
        q = q.trim().toLowerCase();
        let visibleCount = 0;
        allItems.forEach(function (item) {
            const number   = (item.dataset.serviceNumber || '').toLowerCase();
            const customer = (item.dataset.customerName  || '').toLowerCase();
            const matches  = !q || number.includes(q) || customer.includes(q);
            item.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        emptyMsg.style.display = visibleCount === 0 ? '' : 'none';
    }

    allItems.forEach(function (item) {
        item.addEventListener('click', function () {
            serviceIdInput.value = item.dataset.id;
            serviceIdError.style.display = 'none';
            document.getElementById('customer_name').value    = item.dataset.customerName;
            document.getElementById('customer_company').value = item.dataset.customerCompany;
            document.getElementById('customer_rfc').value     = item.dataset.customerRfc;
            document.getElementById('customer_phone').value   = item.dataset.customerPhone;
            searchInput.value      = item.dataset.serviceNumber + ' — ' + item.dataset.customerName;
            dropdown.style.display = 'none';
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#srServicePicker')) {
            dropdown.style.display = 'none';
        }
    });

    document.getElementById('srStep1Form').addEventListener('submit', function (e) {
        if (!serviceIdInput.value) {
            e.preventDefault();
            serviceIdError.style.display = 'block';
            searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
@endpush
