@php
    // En modo creación $service es null; en modo edición $service tiene ID
    $isEdit    = isset($service) && $service?->id;
    $saveUrl   = $isEdit
        ? route('admin.technical-services.save-step', [$service, 1])
        : route('admin.technical-services.store');
    $stepUrl   = $isEdit
        ? route('admin.technical-services.step', [$service, '__STEP__'])
        : '';
    $indexUrl  = route('admin.technical-services.index');
    $searchUrl = route('admin.technical-services.search-technicians');
@endphp

<form id="ts-wizard-form"
      data-step="1"
      data-save-url="{{ $saveUrl }}"
      data-step-url="{{ $stepUrl }}"
      data-index-url="{{ $indexUrl }}"
      data-search-tech-url="{{ $searchUrl }}">

    <div class="ts-card">
        <h2 class="ts-card__title">Datos Generales</h2>
        <p class="ts-card__subtitle">Información básica del servicio técnico</p>
        <div class="ts-card__divider"></div>

        <div class="ts-form-grid" style="gap:1rem">

            {{-- Folio + Fecha + Hora --}}
            <div class="ts-form-grid ts-form-grid--2">
                <div class="ts-field">
                    <label class="ts-label">Folio</label>
                    <input type="text" class="ts-input ts-input--disabled"
                           value="{{ $service?->service_number ?? 'Auto-generado' }}" disabled>
                </div>
                <div class="ts-form-grid ts-form-grid--2">
                    <div class="ts-field">
                        <label class="ts-label">Fecha <span class="ts-label__req">*</span></label>
                        <input type="date" name="service_date" class="ts-input" required
                               value="{{ old('service_date', $service?->service_date
                                    ? $service->service_date->format('Y-m-d')
                                    : now()->format('Y-m-d')) }}">
                        <span class="ts-field-error"></span>
                    </div>
                    <div class="ts-field">
                        <label class="ts-label">Hora <span class="ts-label__req">*</span></label>
                        <input type="time" name="service_time" class="ts-input" required
                               value="{{ old('service_time', $service?->service_time
                                    ? substr($service->service_time, 0, 5)
                                    : '09:00') }}">
                        <span class="ts-field-error"></span>
                    </div>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="ts-field">
                <label class="ts-label">Cliente <span class="ts-label__req">*</span></label>
                <div class="ts-field-select-wrap">
                    <select name="customer_id" class="ts-select-field" required>
                        <option value="">Seleccionar cliente...</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id', $service?->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->company ?: trim("{$customer->first_name} {$customer->last_name}") }}
                            </option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>
                <span class="ts-field-error"></span>
            </div>

            {{-- Tipo de servicio --}}
            <div class="ts-field">
                <label class="ts-label">Tipo de servicio <span class="ts-label__req">*</span></label>
                <div class="ts-field-select-wrap">
                    <select name="service_type_id" class="ts-select-field" required>
                        <option value="">Seleccionar tipo...</option>
                        @foreach($serviceTypes as $typeId => $typeName)
                            <option value="{{ $typeId }}"
                                {{ old('service_type_id', $service?->service_type_id) == $typeId ? 'selected' : '' }}>
                                {{ $typeName }}
                            </option>
                        @endforeach
                    </select>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>
                <span class="ts-field-error"></span>
            </div>

            {{-- Duración + Prioridad --}}
            <div class="ts-form-grid ts-form-grid--2">
                <div class="ts-field">
                    <label class="ts-label">Duración estimada</label>
                    <div class="ts-field-select-wrap">
                        <select name="estimated_duration" class="ts-select-field">
                            @php
                                $durations = [
                                    1   => '1 hora',
                                    2   => '2 horas',
                                    3   => '3 horas',
                                    4   => '4 horas',
                                    4.5 => 'Medio día',
                                    8   => 'Día completo',
                                ];
                            @endphp
                            @foreach($durations as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('estimated_duration', $service?->estimated_duration) == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                </div>
                <div class="ts-field">
                    <label class="ts-label">Prioridad</label>
                    <div class="ts-field-select-wrap">
                        <select name="priority" class="ts-select-field">
                            <option value="normal"
                                {{ old('priority', $service?->priority ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high"
                                {{ old('priority', $service?->priority) === 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="urgent"
                                {{ old('priority', $service?->priority) === 'urgent' ? 'selected' : '' }}>Urgente</option>
                        </select>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Dirección --}}
            <div class="ts-field">
                <label class="ts-label">Dirección del servicio</label>
                <input type="text" name="address" class="ts-input"
                       placeholder="Calle, colonia, ciudad..."
                       value="{{ old('address', $service?->address) }}">
            </div>

            {{-- Referencia --}}
            <div class="ts-field">
                <label class="ts-label">Referencia / indicaciones</label>
                <input type="text" name="reference" class="ts-input"
                       placeholder="Ej: Planta baja, nave 3..."
                       value="{{ old('reference', $service?->reference) }}">
            </div>

            {{-- Descripción --}}
            <div class="ts-field">
                <label class="ts-label">Descripción del servicio</label>
                <textarea name="description" class="ts-textarea"
                          placeholder="Describe el trabajo a realizar..."
                          rows="3">{{ old('description', $service?->description) }}</textarea>
            </div>

        </div>
    </div>

    {{-- Wizard footer --}}
    <div class="ts-wizard-footer">
        <button type="button" id="ts-btn-back" class="ts-btn ts-btn--ghost">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 19-7-7 7-7M19 12H5"/>
            </svg>
            Cancelar
        </button>
        <button type="button" id="ts-btn-next" class="ts-btn ts-btn--primary">
            Siguiente: Técnicos
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 14 0M13 6l6 6-6 6"/>
            </svg>
        </button>
    </div>

</form>
