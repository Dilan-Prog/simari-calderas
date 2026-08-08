@extends('admin.layouts.master')
@section('title', 'Editar Orden de Compra - Admin')
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush
@section('content')
    <div class="po-page">

        {{-- Si un submit falló la validación server-side en un campo de los
             Pasos 2 o 3, el wizard (que siempre arranca en el Paso 1 vía
             AdminWizard.init) necesita saber a qué paso saltar para que el
             error no quede oculto por el CSS de paneles. Ver po-wizard.js. --}}
        @if ($errors->any())
            <script>
                window.__poFormErrorStep = {{
                    $errors->has('currency') || $errors->has('exchange_rate') || $errors->has('internal_reference')
                        ? 2
                        : (
                            $errors->has('status') || $errors->has('tax_rate') || $errors->has('subtotal')
                            || $errors->has('tax_total') || $errors->has('total') || $errors->has('items')
                            || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'items.'))
                                ? 3
                                : 1
                        )
                }};
            </script>
        @endif

        {{-- Header --}}
        <header class="po-header">
            <nav class="po-breadcrumb">
                <a href="{{ route('admin.purchase-orders.index') }}">Órdenes de Compra</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6" />
                </svg>
                <span>Editar Orden</span>
            </nav>
            <div class="po-header__title-row">
                <a href="{{ route('admin.purchase-orders.index') }}" class="po-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7" />
                        <path d="M19 12H5" />
                    </svg>
                </a>
                <h1 class="po-header__title">Editar Orden de Compra</h1>
            </div>
        </header>

        {{-- Barra de progreso del wizard --}}
        <div class="powiz-bar">
            <div class="powiz-step active">
                <div class="powiz-step__circle">1</div>
                <div class="powiz-step__label">Proveedor</div>
            </div>
            <div class="powiz-step-connector"></div>
            <div class="powiz-step">
                <div class="powiz-step__circle">2</div>
                <div class="powiz-step__label">Moneda y configuración</div>
            </div>
            <div class="powiz-step-connector"></div>
            <div class="powiz-step">
                <div class="powiz-step__circle">3</div>
                <div class="powiz-step__label">Productos</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.purchase-orders.update', $order->id) }}" id="poEditForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="po_number" value="{{ $order->po_number }}">
            <input type="hidden" name="created_by_user_id" value="{{ $order->created_by_user_id }}">
            <input type="hidden" name="subtotal" id="hiddenSubtotal" value="{{ $order->subtotal }}">
            <input type="hidden" name="tax_total" id="hiddenTaxTotal" value="{{ $order->tax_total }}">
            <input type="hidden" name="total" id="hiddenTotal" value="{{ $order->total }}">

            {{-- ── PASO 1 — Proveedor ── --}}
            <div class="powiz-step-panel active" data-powiz-step="1">
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                            <line x1="3" x2="21" y1="6" y2="6" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        Datos del Proveedor
                    </h2>

                    <div class="po-form-group">
                        <label class="po-label">
                            PROVEEDOR <span class="po-req">*</span>
                        </label>
                        <select name="supplier_id" id="supplierSelect" class="po-select {{ $errors->has('supplier_id') ? 'is-invalid' : '' }}" required>
                            <option value="">Buscar proveedor...</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" data-contact="{{ $supplier->contact_name ?? '' }}"
                                    data-email="{{ $supplier->email ?? '' }}"
                                    data-phone="{{ $supplier->phone ?? '' }}" data-rfc="{{ $supplier->rfc ?? '' }}"
                                    data-terms="{{ $supplier->payment_terms ?? '' }}"
                                    data-company="{{ $supplier->company_name }}"
                                    {{ old('supplier_id', $order->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ strtoupper($supplier->company_name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Supplier info card --}}
                    <div id="supplierInfoCard" class="po-supplier-card"
                        style="{{ $order->supplier ? '' : 'display:none;' }}">
                        <p class="po-supplier-card__name" id="infoCompany">
                            {{ $order->supplier->company_name ?? '—' }}
                        </p>
                        <div class="po-supplier-card__grid">
                            <span class="po-supplier-card__label">Contacto:</span>
                            <span id="infoContact">{{ $order->supplier->contact_name ?? '—' }}</span>
                            <span class="po-supplier-card__label">Email:</span>
                            <span id="infoEmail">{{ $order->supplier->email ?? '—' }}</span>
                            <span class="po-supplier-card__label">Teléfono:</span>
                            <span id="infoPhone">{{ $order->supplier->phone ?? '—' }}</span>
                            <span class="po-supplier-card__label">RFC:</span>
                            <span id="infoRfc">{{ $order->supplier->rfc ?? '—' }}</span>
                            <span class="po-supplier-card__label">Términos:</span>
                            <span id="infoTerms">{{ $order->supplier->payment_terms ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="po-form-grid">
                        <div class="po-form-group">
                            <label class="po-label">
                                FECHA DE LA ORDEN <span class="po-req">*</span>
                            </label>
                            <input type="date" name="order_date" class="po-input {{ $errors->has('order_date') ? 'is-invalid' : '' }}"
                                value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required>
                            @error('order_date')
                                <span class="field-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="po-form-group">
                            <label class="po-label">FECHA DE ENTREGA ESPERADA</label>
                            <input type="date" name="expected_delivery_date" class="po-input"
                                value="{{ old('expected_delivery_date', $order->expected_delivery_date?->format('Y-m-d') ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── PASO 2 — Moneda y configuración ── --}}
            <div class="powiz-step-panel" data-powiz-step="2">
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" x2="12" y1="6" y2="18"/>
                            <path d="M15 9.5a2.5 2.5 0 0 0-2.5-2.5h-1a2.5 2.5 0 0 0 0 5h1a2.5 2.5 0 0 1 0 5h-1a2.5 2.5 0 0 1-2.5-2.5"/>
                        </svg>
                        Configuración
                    </h2>

                    <div class="po-form-grid">
                        <div class="po-form-group">
                            <label class="po-label">MONEDA <span class="po-req">*</span></label>
                            <select name="currency" id="currencySelect" class="po-select {{ $errors->has('currency') ? 'is-invalid' : '' }}">
                                <option value="MXN" {{ old('currency', $order->currency) === 'MXN' ? 'selected' : '' }}>MXN</option>
                                <option value="USD" {{ old('currency', $order->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                            </select>
                            @error('currency')
                                <span class="field-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="po-form-group">
                            <label class="po-label">TIPO DE CAMBIO (USD → MXN) <span class="po-req">*</span></label>
                            <input type="number" name="exchange_rate" id="exchangeRate" class="po-input {{ $errors->has('exchange_rate') ? 'is-invalid' : '' }}"
                                step="0.0001" min="0.01"
                                value="{{ old('exchange_rate', $order->exchange_rate ?? $defaultExchangeRate) }}">
                            @error('exchange_rate')
                                <span class="field-error-msg">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <p class="po-help-note">
                        Estos valores se aplican a todos los productos que agregues en el siguiente paso.
                    </p>

                    <div class="po-form-group">
                        <label class="po-label">REFERENCIA INTERNA</label>
                        <input type="text" name="internal_reference" class="po-input {{ $errors->has('internal_reference') ? 'is-invalid' : '' }}"
                            placeholder="Ej: REF-2026-001" value="{{ old('internal_reference', $order->internal_reference ?? '') }}">
                        @error('internal_reference')
                            <span class="field-error-msg">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="po-form-group">
                        <label class="po-label">NOTAS AL PROVEEDOR</label>
                        <textarea name="notes" class="po-textarea" rows="3"
                            placeholder="Instrucciones especiales, condiciones de entrega...">{{ old('notes', $order->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── PASO 3 — Productos + Resumen + Estado + Guardar ── --}}
            <div class="powiz-step-panel" data-powiz-step="3">
                <div class="po-layout">

                    {{-- ── LEFT COLUMN ── --}}
                    <div class="po-main">

                        {{-- Productos / Servicios --}}
                        <div class="po-card">
                            <h2 class="po-card__header">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                    fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
                                    <path d="M12 22V12" />
                                    <polyline points="3.29 7 12 12 20.71 7" />
                                </svg>
                                Productos / Servicios
                            </h2>

                            <div class="po-products-toolbar">
                                <div class="po-inline-search" id="poInlineSearch">
                                    <div class="po-inline-search__wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="po-inline-search__icon">
                                            <circle cx="11" cy="11" r="8" />
                                            <path d="m21 21-4.3-4.3" />
                                        </svg>
                                        <input type="text" id="poProductInput" class="po-inline-search__input"
                                            placeholder="Buscar producto del catálogo..." autocomplete="off">
                                        <button type="button" id="poProductClear" class="po-inline-search__clear"
                                            style="display:none;">✕</button>
                                    </div>
                                    <div id="poProductDropdown" class="po-inline-search__dropdown" style="display:none;">
                                        <div id="poProductLoading" class="po-inline-search__loading" style="display:none;">
                                            Buscando...
                                        </div>
                                        <div id="poProductEmpty" class="po-inline-search__empty" style="display:none;">
                                            Sin resultados
                                        </div>
                                        <ul id="poProductList" class="po-inline-search__list"></ul>
                                    </div>
                                </div>

                                <button type="button" id="btnAddFreeLine" class="po-btn-add-row">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 5v14" />
                                        <path d="M5 12h14" />
                                    </svg>
                                    Agregar línea libre
                                </button>
                            </div>

                            <div class="po-table-wrap">
                                <table class="po-table">
                                    <thead>
                                        <tr>
                                            <th class="po-col-num">#</th>
                                            <th class="po-col-name">PRODUCTO / DESCRIPCIÓN</th>
                                            <th class="po-col-sku">SKU</th>
                                            <th class="po-col-qty">CANT.</th>
                                            <th class="po-col-price">PRECIO UNIT.</th>
                                            <th class="po-col-disc">DESC. %</th>
                                            <th class="po-col-total">SUBTOTAL</th>
                                            <th class="po-col-del"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="poItemsBody">
                                        <tr id="poEmptyRow" class="po-table__empty" style="display:none;">
                                            <td colspan="8">Agrega productos usando los botones de arriba</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    {{-- ── RIGHT SIDEBAR ── --}}
                    <aside class="po-sidebar">

                        {{-- Resumen --}}
                        <div class="po-card">
                            <h2 class="po-card__header">Resumen de la Orden</h2>
                            <div class="po-summary">
                                <div class="po-summary__row">
                                    <span>Subtotal</span>
                                    <span id="summarySubtotal" class="po-summary__val">
                                        ${{ number_format($order->subtotal, 2) }}
                                    </span>
                                </div>
                                <div class="po-summary__row">
                                    <span>Descuento global</span>
                                    <div class="po-summary__input-group">
                                        <input type="number" name="discount_total" id="globalDiscount"
                                            class="po-summary__input" value="{{ $order->discount_total }}" min="0"
                                            max="100" step="0.01">
                                        <span class="po-summary__pct">%</span>
                                        <span id="summaryDiscount" class="po-summary__val po-summary__val--red">
                                            -${{ number_format($order->discount_total, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="po-summary__row">
                                    <span>Base gravable</span>
                                    <span id="summaryBase" class="po-summary__val">
                                        ${{ number_format($order->subtotal - $order->discount_total, 2) }}
                                    </span>
                                </div>
                                <div class="po-summary__row">
                                    <span>IVA</span>
                                    <div class="po-summary__input-group">
                                        <input type="number" name="tax_rate" id="taxRate" class="po-summary__input {{ $errors->has('tax_rate') ? 'is-invalid' : '' }}"
                                            value="{{ old('tax_rate', $order->tax_rate) }}" min="0" max="100" step="0.01">
                                        <span class="po-summary__pct">%</span>
                                        <span id="summaryTax" class="po-summary__val">
                                            ${{ number_format($order->tax_total, 2) }}
                                        </span>
                                    </div>
                                    @error('tax_rate')
                                        <span class="field-error-msg">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="po-summary__total">
                                    <span>TOTAL</span>
                                    <span id="summaryTotal" class="po-summary__total-val">
                                        ${{ number_format($order->total, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Estado --}}
                        <div class="po-card">
                            <h2 class="po-card__header">Estado de la Orden</h2>
                            <select name="status" id="statusSelect" class="po-select po-status-select {{ $errors->has('status') ? 'is-invalid' : '' }}">
                                <option value="pending" {{ old('status', $order->status) === 'pending' ? 'selected' : '' }}>
                                    ● Pendiente
                                </option>
                                <option value="accepted" {{ old('status', $order->status) === 'accepted' ? 'selected' : '' }}>
                                    ● Aceptada
                                </option>
                                <option value="rejected" {{ old('status', $order->status) === 'rejected' ? 'selected' : '' }}>
                                    ● Rechazada
                                </option>
                            </select>
                            @error('status')
                                <span class="field-error-msg">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Info adicional --}}
                        <div class="po-card">
                            <h2 class="po-card__header">Información adicional</h2>
                            <div class="po-info-row">
                                <span class="po-info-row__label">Creada por</span>
                                <span class="po-info-row__val">
                                    {{ $order->createdBy->first_name ?? 'Admin' }}
                                    {{ $order->createdBy->last_name ?? '' }}
                                </span>
                            </div>
                            <div class="po-info-row">
                                <span class="po-info-row__label">Folio</span>
                                <span class="po-info-row__val po-info-row__val--orange">
                                    {{ $order->po_number }}
                                </span>
                            </div>
                            <div class="po-info-row">
                                <span class="po-info-row__label">Creada el</span>
                                <span class="po-info-row__val">
                                    {{ $order->created_at->format('d M Y') }}
                                </span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <button type="submit" name="action" value="save" class="po-btn-save">
                            Guardar Cambios →
                        </button>
                        <a href="{{ route('admin.purchase-orders.index') }}" class="po-btn-draft"
                            style="display:block;text-align:center;text-decoration:none;">
                            Cancelar
                        </a>

                    </aside>
                </div>
            </div>

            {{-- Footer del wizard --}}
            <div class="powiz-footer">
                <button type="button" id="powizBackBtn" class="powiz-btn--ghost">← Atrás</button>
                <button type="button" id="powizNextBtn" class="powiz-btn--primary">Siguiente →</button>
            </div>
        </form>

        {{-- Modal de confirmación al retroceder con productos agregados --}}
        <div class="powiz-confirm-backdrop">
            <div class="powiz-confirm-modal">
                <p>Si regresas, se perderán los productos que ya agregaste. ¿Continuar?</p>
                <div class="powiz-confirm-modal__actions">
                    <button type="button" id="powizConfirmNo" class="powiz-btn--ghost">Cancelar</button>
                    <button type="button" id="powizConfirmYes" class="powiz-btn--primary">Continuar</button>
                </div>
            </div>
        </div>

    </div>
@endsection
@include('admin.purchase-orders.edit._scripts')
@push('scripts')
    @vite(['resources/js/admin/wizard-core.js', 'resources/js/admin/po-wizard.js'])
@endpush
