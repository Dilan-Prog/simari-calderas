@extends('admin.layouts.master')
@section('title', 'Nuevo Reporte de Entrega de Material')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-create">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes de Entrega de Material</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">Nuevo Reporte</span>
    </div>

    {{-- Progress bar --}}
    <div class="mdr-progress">
        @php
            $steps = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma'];
        @endphp
        @foreach($steps as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="mdr-step-item {{ $n === 1 ? 'active' : '' }}">
                <div class="mdr-step-circle">{{ $n }}</div>
                <span class="mdr-step-label">{{ $label }}</span>
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
    <div class="mdr-form-card" style="margin-bottom:32px;">
        <div class="mdr-form-header">
            <h2>Paso 1 — Datos Generales</h2>
            <p>Selecciona el pedido a entregar y la fecha/ubicación de la entrega</p>
        </div>

        <form method="POST" action="{{ route('admin.material-delivery-reports.store') }}" id="mdrCreateForm">
            @csrf

            <div class="mdr-form-body">

                <div class="mdr-grid-2">
                    <div class="mdr-section-title">Pedido a Entregar</div>

                    <div class="mdr-field mdr-full">
                        <label class="mdr-label" for="orderSearchInput">Pedido <span class="mdr-req">*</span></label>
                        <div class="mdr-client-select-wrap" id="mdrOrderPicker">
                            <input type="text" id="orderSearchInput" class="mdr-input"
                                   placeholder="Buscar por folio o cliente…" autocomplete="off">
                            <div id="orderDropdown" class="mdr-client-dropdown" style="display:none;">
                                {{-- El controller actualmente pasa $salesOrders (verificado en vivo); se
                                     acepta también $availableSalesOrders por si el nombre cambia. --}}
                                @php $mdrAvailableOrders = $availableSalesOrders ?? $salesOrders ?? collect(); @endphp
                                @forelse($mdrAvailableOrders as $so)
                                    @php
                                        $soCustomer = $so->customer;
                                        $soCustomerLabel = $soCustomer
                                            ? ($soCustomer->company ?: trim(($soCustomer->first_name ?? '') . ' ' . ($soCustomer->last_name ?? '')))
                                            : '—';
                                        $soStatusLabel = \App\Models\SalesOrder::statusLabel($so->status);
                                    @endphp
                                    <div class="mdr-client-dropdown__item"
                                         data-id="{{ $so->id }}"
                                         data-order-number="{{ $so->order_number }}"
                                         data-customer-name="{{ $soCustomerLabel }}"
                                         data-status-label="{{ $soStatusLabel }}">
                                        <span class="mdr-client-dropdown__name">{{ $so->order_number }}</span>
                                        <span class="mdr-client-dropdown__company">{{ $soCustomerLabel }}</span>
                                        <span class="mdr-client-dropdown__status">{{ $soStatusLabel }}</span>
                                    </div>
                                @empty
                                    <div class="mdr-client-dropdown__empty">Sin pedidos elegibles disponibles</div>
                                @endforelse
                                <div class="mdr-client-dropdown__empty" style="display:none;">Sin resultados</div>
                            </div>
                        </div>
                        <input type="hidden" name="sales_order_id" id="salesOrderIdInput" value="{{ old('sales_order_id') }}">
                        <span class="mdr-hint">Solo se listan pedidos en preparación, parcialmente entregados o entregados.</span>
                        <span id="salesOrderIdError" class="mdr-error" style="display:none">Debes seleccionar un pedido.</span>
                        @error('sales_order_id')<span class="mdr-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="mdr-field">
                        <label class="mdr-label" for="delivery_date">Fecha de Entrega <span class="mdr-req">*</span></label>
                        <input type="date" id="delivery_date" name="delivery_date" class="mdr-input {{ $errors->has('delivery_date') ? 'is-invalid' : '' }}" value="{{ old('delivery_date', date('Y-m-d')) }}" required>
                        @error('delivery_date') <span class="mdr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="mdr-field">
                        <label class="mdr-label" for="delivery_location">Ubicación de Entrega</label>
                        <input type="text" id="delivery_location" name="delivery_location" class="mdr-input" value="{{ old('delivery_location') }}" placeholder="Ej: Planta Monterrey" maxlength="200">
                    </div>
                </div>

            </div>{{-- /form-body --}}

            <div class="mdr-form-footer">
                <a href="{{ route('admin.material-delivery-reports.index') }}" class="mdr-btn-outline">Cancelar</a>
                <button type="submit" class="mdr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchInput      = document.getElementById('orderSearchInput');
    const dropdown          = document.getElementById('orderDropdown');
    const salesOrderIdInput = document.getElementById('salesOrderIdInput');
    const salesOrderIdError = document.getElementById('salesOrderIdError');
    const allItems           = Array.from(dropdown.querySelectorAll('.mdr-client-dropdown__item'));
    const emptyMsg            = dropdown.querySelector('.mdr-client-dropdown__empty:last-child');

    searchInput.addEventListener('focus', function () {
        filterOrders(this.value);
        dropdown.style.display = 'block';
    });

    searchInput.addEventListener('input', function () {
        filterOrders(this.value);
        dropdown.style.display = 'block';
    });

    function filterOrders(q) {
        q = q.trim().toLowerCase();
        let visibleCount = 0;
        allItems.forEach(function (item) {
            const number   = (item.dataset.orderNumber || '').toLowerCase();
            const customer = (item.dataset.customerName || '').toLowerCase();
            const matches  = !q || number.includes(q) || customer.includes(q);
            item.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        if (emptyMsg) emptyMsg.style.display = (visibleCount === 0 && allItems.length > 0) ? '' : 'none';
    }

    allItems.forEach(function (item) {
        item.addEventListener('click', function () {
            salesOrderIdInput.value = item.dataset.id;
            salesOrderIdError.style.display = 'none';
            searchInput.value      = item.dataset.orderNumber + ' — ' + item.dataset.customerName;
            dropdown.style.display = 'none';
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#mdrOrderPicker')) {
            dropdown.style.display = 'none';
        }
    });

    document.getElementById('mdrCreateForm').addEventListener('submit', function (e) {
        if (!salesOrderIdInput.value) {
            e.preventDefault();
            salesOrderIdError.style.display = 'block';
            searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();
</script>
@endpush
