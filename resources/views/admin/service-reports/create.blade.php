@extends('admin.layouts.master')
@section('title', 'Nuevo Reporte de Servicio')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; min-height: 100%; padding: 32px; }

    /* Progress bar */
    .sr-progress { display: flex; align-items: center; background: #fff; border-radius: 8px;
                   box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 20px 24px; margin-bottom: 24px; gap: 0; }
    .sr-step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .sr-step-item:not(:last-child)::after {
        content: ''; position: absolute; top: 16px; left: 60%; width: 80%; height: 2px;
        background: #E5E7EB; z-index: 0;
    }
    .sr-step-item.done::after, .sr-step-item.active::after { background: #ff6213; }
    .sr-step-circle {
        width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-size: 13px; font-weight: 600; z-index: 1;
        border: 2px solid #E5E7EB; background: #fff; color: #9CA3AF;
    }
    .sr-step-item.active  .sr-step-circle { border-color: #ff6213; background: #ff6213; color: #fff; }
    .sr-step-item.done    .sr-step-circle { border-color: #ff6213; background: #fff; color: #ff6213; }
    .sr-step-label { font-size: 11px; color: #9CA3AF; margin-top: 6px; text-align: center; white-space: nowrap; }
    .sr-step-item.active .sr-step-label, .sr-step-item.done .sr-step-label { color: #374151; }

    /* Form card */
    .sr-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); display: flex; flex-direction: column; max-height: 420px; overflow-y: auto; }
    .sr-form-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
    .sr-form-header h2 { margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #111827; }
    .sr-form-header p  { margin: 0; font-size: 13px; color: #6B7280; }
    .sr-form-body  { padding: 24px; overflow-y: auto; flex: 1; }
    .sr-form-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6;
                      display: flex; justify-content: flex-end; gap: 12px; }

    /* Field groups */
    .sr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .sr-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .sr-full   { grid-column: 1 / -1; }
    .sr-section-title { font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase;
                        letter-spacing: .05em; padding: 16px 0 8px; border-bottom: 1px solid #F3F4F6;
                        margin-bottom: 16px; grid-column: 1 / -1; }
    .sr-field { display: flex; flex-direction: column; gap: 6px; }
    .sr-label { font-size: 13px; font-weight: 500; color: #374151; }
    .sr-label .sr-req { color: #ff6213; }
    .sr-input, .sr-select, .sr-textarea {
        height: 40px; padding: 0 12px; border-radius: 6px; border: 1px solid #D1D5DB;
        font-size: 13px; font-family: 'Inter', sans-serif; color: #111827;
        background: #fff; outline: none; width: 100%; box-sizing: border-box;
        transition: border-color .15s, box-shadow .15s;
    }
    .sr-textarea { height: auto; padding: 10px 12px; resize: vertical; }
    .sr-input:focus, .sr-select:focus, .sr-textarea:focus {
        border-color: #ff6213; box-shadow: 0 0 0 3px rgba(255,98,19,.12);
    }
    .sr-select { appearance: none; -webkit-appearance: none; cursor: pointer; }
    .sr-select-wrap { position: relative; }
    .sr-select-wrap::after {
        content: ''; pointer-events: none; position: absolute; right: 12px; top: 50%;
        transform: translateY(-50%); border: 5px solid transparent;
        border-top-color: #6B7280; margin-top: 3px;
    }
    .sr-input.is-invalid, .sr-select.is-invalid { border-color: #DC2626; }
    .sr-error { font-size: 12px; color: #DC2626; }
    .sr-hint  { font-size: 12px; color: #9CA3AF; }

    /* Customer dropdown */
    .sr-client-select-wrap { position: relative; }
    .sr-client-dropdown {
        position: absolute; z-index: 50; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1px solid #E5E7EB; border-radius: 6px;
        box-shadow: 0 4px 16px rgba(0,0,0,.12); max-height: 240px; overflow-y: auto;
    }
    .sr-client-dropdown__item {
        padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #F3F4F6; transition: background .1s;
    }
    .sr-client-dropdown__item:last-child { border-bottom: none; }
    .sr-client-dropdown__item:hover { background: #FFF7ED; }
    .sr-client-dropdown__item.hidden { display: none; }
    .sr-client-dropdown__name    { display: block; font-size: 13px; font-weight: 600; color: #111827; }
    .sr-client-dropdown__company { display: block; font-size: 12px; color: #6B7280; }
    .sr-client-dropdown__empty   { padding: 10px 14px; font-size: 13px; color: #9CA3AF; }

    /* Buttons */
    .sr-btn-primary {
        height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
        font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif;
        border: none; cursor: pointer; transition: background .15s;
    }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline {
        height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
        background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif;
        cursor: pointer; transition: background .15s;
    }
    .sr-btn-outline:hover { background: #F9FAFB; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-grid-2, .sr-grid-3 { grid-template-columns: 1fr; }
        .sr-progress { overflow-x: auto; }
        .sr-step-label { display: none; }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes de Servicio</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">Nuevo Reporte</span>
    </div>

    {{-- Progress bar --}}
    <div class="sr-progress">
        @php
            $steps = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma'];
        @endphp
        @foreach($steps as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="sr-step-item {{ $n === 1 ? 'active' : '' }}">
                <div class="sr-step-circle">{{ $n }}</div>
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

        <form method="POST" action="{{ route('admin.service-reports.store') }}" id="srCreateForm">
            @csrf
            <input type="hidden" name="customer_id" id="customerId">

            <div class="sr-form-body">

                {{-- ── Sección: Servicio ── --}}
                <div class="sr-grid-2">
                    <div class="sr-section-title">Datos del Servicio</div>

                    <div class="sr-field">
                        <label class="sr-label" for="service_date">Fecha de Servicio <span class="sr-req">*</span></label>
                        <input type="date" id="service_date" name="service_date" class="sr-input {{ $errors->has('service_date') ? 'is-invalid' : '' }}" value="{{ old('service_date', date('Y-m-d')) }}" required>
                        @error('service_date') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="assigned_user_id">Encargado <span class="sr-req">*</span></label>
                        <div class="sr-select-wrap">
                            <select id="assigned_user_id" name="assigned_user_id" class="sr-select {{ $errors->has('assigned_user_id') ? 'is-invalid' : '' }}" required>
                                <option value="">— Selecciona un encargado —</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_user_id') == $user->id ? 'selected' : '' }}>
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
                            <select id="service_type" name="service_type" class="sr-select {{ $errors->has('service_type') ? 'is-invalid' : '' }}" required>
                                <option value="">— Selecciona un tipo —</option>
                                @foreach($serviceTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('service_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('service_type') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field" id="customTypeField" style="display:none;">
                        <label class="sr-label" for="custom_service_type">Nombre del Tipo Personalizado <span class="sr-req">*</span></label>
                        <input type="text" id="custom_service_type" name="custom_service_type" class="sr-input {{ $errors->has('custom_service_type') ? 'is-invalid' : '' }}" value="{{ old('custom_service_type') }}" placeholder="Ej: Análisis Microbiológico" maxlength="100">
                        @error('custom_service_type') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="week_number">Número de Semana</label>
                        <input type="number" id="week_number" name="week_number" class="sr-input" value="{{ old('week_number') }}" min="1" max="53" placeholder="1–53">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="location">Ubicación / Sitio</label>
                        <input type="text" id="location" name="location" class="sr-input" value="{{ old('location') }}" placeholder="Ej: Planta Monterrey" maxlength="200">
                    </div>
                </div>

                {{-- ── Sección: Cliente ── --}}
                <div class="sr-grid-2" style="margin-top:24px;">
                    <div class="sr-section-title">Datos del Cliente</div>

                    <div class="sr-field sr-full">
                        <label class="sr-label" for="clientSearchInput">Buscar Cliente registrado</label>
                        <div class="sr-client-select-wrap">
                            <input type="text" id="clientSearchInput" class="sr-input"
                                   placeholder="Escribe el nombre o empresa…" autocomplete="off">
                            <div id="clientDropdown" class="sr-client-dropdown" style="display:none;">
                                @foreach($customers as $customer)
                                    <div class="sr-client-dropdown__item"
                                         data-id="{{ $customer->id }}"
                                         data-name="{{ trim($customer->first_name . ' ' . $customer->last_name) }}"
                                         data-company="{{ $customer->company ?? '' }}"
                                         data-email="{{ $customer->email ?? '' }}"
                                         data-phone="{{ $customer->phone ?? '' }}"
                                         data-rfc="{{ $customer->rfc ?? '' }}">
                                        <span class="sr-client-dropdown__name">{{ $customer->company ?: trim($customer->first_name . ' ' . $customer->last_name) }}</span>
                                        @if($customer->company)
                                            <span class="sr-client-dropdown__company">{{ trim($customer->first_name . ' ' . $customer->last_name) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="sr-client-dropdown__empty" style="display:none;">Sin resultados</div>
                            </div>
                        </div>
                        <span class="sr-hint">Selecciona un cliente para rellenar los campos automáticamente, o escríbelos manualmente.</span>
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_name">Nombre del Cliente <span class="sr-req">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" class="sr-input {{ $errors->has('customer_name') ? 'is-invalid' : '' }}" value="{{ old('customer_name') }}" required maxlength="200">
                        @error('customer_name') <span class="sr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_company">Empresa</label>
                        <input type="text" id="customer_company" name="customer_company" class="sr-input" value="{{ old('customer_company') }}" maxlength="200">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_rfc">RFC</label>
                        <input type="text" id="customer_rfc" name="customer_rfc" class="sr-input" value="{{ old('customer_rfc') }}" maxlength="20">
                    </div>

                    <div class="sr-field">
                        <label class="sr-label" for="customer_phone">Teléfono</label>
                        <input type="text" id="customer_phone" name="customer_phone" class="sr-input" value="{{ old('customer_phone') }}" maxlength="30">
                    </div>
                </div>

            </div>{{-- /form-body --}}

            <div class="sr-form-footer">
                <a href="{{ route('admin.service-reports.index') }}" class="sr-btn-outline">Cancelar</a>
                <button type="submit" class="sr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // Toggle custom_service_type field
    const typeSelect = document.getElementById('service_type');
    const customField = document.getElementById('customTypeField');
    const customInput = document.getElementById('custom_service_type');

    function toggleCustom() {
        const show = typeSelect.value === 'custom';
        customField.style.display = show ? '' : 'none';
        customInput.required = show;
    }
    typeSelect.addEventListener('change', toggleCustom);
    toggleCustom();

    // Customer client-side dropdown
    const searchInput = document.getElementById('clientSearchInput');
    const dropdown    = document.getElementById('clientDropdown');
    const idInput     = document.getElementById('customerId');
    const allItems    = Array.from(dropdown.querySelectorAll('.sr-client-dropdown__item'));
    const emptyMsg    = dropdown.querySelector('.sr-client-dropdown__empty');

    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();

        if (!q) {
            dropdown.style.display = 'none';
            return;
        }

        let visibleCount = 0;
        allItems.forEach(function (item) {
            const name    = (item.dataset.name    || '').toLowerCase();
            const company = (item.dataset.company || '').toLowerCase();
            const rfc     = (item.dataset.rfc     || '').toLowerCase();
            const matches = name.includes(q) || company.includes(q) || rfc.includes(q);
            item.classList.toggle('hidden', !matches);
            if (matches) visibleCount++;
        });

        emptyMsg.style.display  = visibleCount === 0 ? '' : 'none';
        dropdown.style.display  = '';
    });

    allItems.forEach(function (item) {
        item.addEventListener('click', function () {
            idInput.value = item.dataset.id;
            document.getElementById('customer_name').value    = item.dataset.name;
            document.getElementById('customer_company').value = item.dataset.company;
            document.getElementById('customer_rfc').value     = item.dataset.rfc;
            document.getElementById('customer_phone').value   = item.dataset.phone;
            searchInput.value      = item.dataset.company || item.dataset.name;
            dropdown.style.display = 'none';
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.sr-client-select-wrap')) {
            dropdown.style.display = 'none';
        }
    });
})();
</script>
@endpush
