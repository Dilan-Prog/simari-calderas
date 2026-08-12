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

<script>
window.__tsConfig = {
    step: 1,
    isEdit: {{ $isEdit ? 'true' : 'false' }},
    saveUrl: "{{ $saveUrl }}",
    stepUrl: "{{ $stepUrl }}",
    indexUrl: "{{ $indexUrl }}",
    searchTechUrl: "{{ $searchUrl }}",
    technicalServicesBaseUrl: "{{ url('/admin/technical-services') }}",
    serviceId: {{ $isEdit ? $service->id : 'null' }},
    currentUrl: window.location.pathname
};
</script>
<form id="ts-wizard-form">
    <input type="hidden" name="draft_token" id="ts-draft-token" value="{{ old('draft_token') }}">

    <div class="ts-card">
        <h2 class="ts-card__title">Datos Generales</h2>
        <p class="ts-card__subtitle">Información básica del servicio técnico</p>
        <div class="ts-card__divider"></div>

        <div class="ts-form-grid" style="gap:1rem">

            {{-- Folio + Fecha + Hora --}}
            <div class="ts-form-grid ts-form-grid--3">
                <div class="ts-field">
                    <label class="ts-label">Folio</label>
                    <input type="text" class="ts-input ts-input--disabled"
                           value="{{ $service?->service_number ?? 'Auto-generado' }}" disabled>
                </div>
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

            @php
                $linkedQuoteId = old('from_quote_id', $service?->from_quote_id ?? $fromQuote?->id);
                $linkedQuote   = $linkedQuoteId ? $acceptedQuotes->firstWhere('id', (int) $linkedQuoteId) : null;
                // Modo legacy: solo aplica al EDITAR un servicio que ya existía sin
                // cotización de origen (borradores viejos). Los servicios nuevos
                // siempre se crean a partir de una cotización aceptada.
                $isLegacyEdit  = $isEdit && $service->customer_id && !$service->from_quote_id;
            @endphp

            {{-- Cliente --}}
            <div class="ts-field">
                <label class="ts-label">Cliente <span class="ts-label__req">*</span></label>
                <div class="ts-field-select-wrap">
                    <select name="customer_id" id="tsCustomerSelect" class="ts-select-field" required>
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

            {{-- Cotización de origen — filtrada por el cliente elegido arriba --}}
            <div class="ts-field">
                <label class="ts-label">
                    Cotización de origen
                    @if($isLegacyEdit)
                        (opcional)
                    @else
                        <span class="ts-label__req">*</span>
                    @endif
                </label>
                <div class="client-select-wrap" id="ts-quote-picker" style="{{ $linkedQuote ? 'display:none' : '' }}">
                    <input type="text" id="tsQuoteSearchInput" class="ts-input"
                           placeholder="Selecciona un cliente primero..." autocomplete="off" disabled>
                    <div id="tsQuoteDropdown" class="client-dropdown" style="display:none">
                        @foreach($acceptedQuotes as $quote)
                            @php
                                $qCustomerLabel = $quote->customer->company
                                    ?: trim("{$quote->customer->first_name} {$quote->customer->last_name}");
                            @endphp
                            <div class="client-dropdown__item"
                                 data-id="{{ $quote->id }}"
                                 data-quote-number="{{ $quote->quote_number }}"
                                 data-customer-id="{{ $quote->customer_id }}"
                                 data-customer-label="{{ $qCustomerLabel }}">
                                <span class="client-dropdown__name">{{ $quote->quote_number }}</span>
                                <span class="client-dropdown__company">{{ $qCustomerLabel }}</span>
                            </div>
                        @endforeach
                        <div class="client-dropdown__empty" style="display:none">Sin cotizaciones aceptadas para este cliente</div>
                    </div>
                </div>
                <div id="ts-quote-linked" class="ts-quote-banner" style="{{ $linkedQuote ? '' : 'display:none' }}">
                    <span>Servicio ligado a la cotización <strong id="ts-quote-linked-number">{{ $linkedQuote?->quote_number }}</strong>
                    (<span id="ts-quote-linked-customer">{{ $linkedQuote ? ($linkedQuote->customer->company ?: trim("{$linkedQuote->customer->first_name} {$linkedQuote->customer->last_name}")) : '' }}</span>)</span>
                    <button type="button" id="ts-quote-unlink" class="ts-btn ts-btn--ghost" style="padding:2px 10px">Quitar vínculo</button>
                </div>
                <input type="hidden" name="from_quote_id" id="tsFromQuoteId" value="{{ $linkedQuoteId }}" {{ $isLegacyEdit ? '' : 'required' }}>
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

<script>
(function () {
    const picker      = document.getElementById('ts-quote-picker');
    const searchInput = document.getElementById('tsQuoteSearchInput');
    const dropdown    = document.getElementById('tsQuoteDropdown');
    const customerSelect = document.getElementById('tsCustomerSelect');
    if (!picker || !searchInput || !dropdown) return;

    const items          = Array.from(dropdown.querySelectorAll('.client-dropdown__item'));
    const emptyMsg        = dropdown.querySelector('.client-dropdown__empty');
    const fromQuoteInput = document.getElementById('tsFromQuoteId');
    const linkedBanner   = document.getElementById('ts-quote-linked');
    const linkedNumber   = document.getElementById('ts-quote-linked-number');
    const linkedCustomer = document.getElementById('ts-quote-linked-customer');
    const unlinkBtn      = document.getElementById('ts-quote-unlink');

    function currentCustomerId() {
        return customerSelect ? customerSelect.value : '';
    }

    // Sin cliente elegido, el buscador de cotizaciones permanece deshabilitado.
    function updateQuoteAvailability() {
        const custId = currentCustomerId();
        if (!custId) {
            searchInput.disabled    = true;
            searchInput.placeholder = 'Selecciona un cliente primero...';
            searchInput.value       = '';
            dropdown.style.display  = 'none';
        } else {
            searchInput.disabled    = false;
            searchInput.placeholder = 'Buscar por folio, cliente o empresa...';
        }
    }

    // Filtra por texto Y por el cliente seleccionado — solo se listan
    // cotizaciones aceptadas del cliente elegido arriba.
    function filterItems(q) {
        q = q.toLowerCase().trim();
        const custId = currentCustomerId();
        let visible = 0;
        items.forEach(item => {
            const number          = item.dataset.quoteNumber.toLowerCase();
            const customer        = item.dataset.customerLabel.toLowerCase();
            const matchesCustomer = custId !== '' && item.dataset.customerId === custId;
            const matchesText     = !q || number.includes(q) || customer.includes(q);
            const match           = matchesCustomer && matchesText;
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        emptyMsg.style.display = visible === 0 ? '' : 'none';
    }

    function linkQuote(item) {
        fromQuoteInput.value = item.dataset.id;

        linkedNumber.textContent   = item.dataset.quoteNumber;
        linkedCustomer.textContent = item.dataset.customerLabel;

        picker.style.display       = 'none';
        linkedBanner.style.display = '';
    }

    function unlinkQuote() {
        fromQuoteInput.value       = '';
        linkedBanner.style.display = 'none';
        picker.style.display       = '';
        searchInput.value          = '';
        updateQuoteAvailability();
        filterItems('');
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', () => {
            updateQuoteAvailability();

            // Si la cotización ligada ya no pertenece al cliente recién
            // elegido, se desvincula para no dejar datos inconsistentes.
            const linkedId = fromQuoteInput.value;
            if (linkedId) {
                const linkedItem = items.find(i => i.dataset.id === linkedId);
                if (!linkedItem || linkedItem.dataset.customerId !== currentCustomerId()) {
                    unlinkQuote();
                }
            }

            filterItems(searchInput.value);
        });
    }

    searchInput.addEventListener('focus', () => {
        if (searchInput.disabled) return;
        filterItems(searchInput.value);
        dropdown.style.display = 'block';
    });

    searchInput.addEventListener('input', () => {
        filterItems(searchInput.value);
        dropdown.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#ts-quote-picker')) {
            dropdown.style.display = 'none';
        }
    });

    items.forEach(item => {
        item.addEventListener('click', () => {
            linkQuote(item);
            dropdown.style.display = 'none';
            searchInput.value = '';
        });
    });

    if (unlinkBtn) {
        unlinkBtn.addEventListener('click', unlinkQuote);
    }

    updateQuoteAvailability();
})();
</script>
