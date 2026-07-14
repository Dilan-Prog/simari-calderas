@extends('admin.layouts.master')
@section('title', 'Nueva Orden de Compra - Admin')

{{-- FIX (bug de arquitectura): apuntaba a public/css/purchase-orders.css,
     un archivo que nunca existió (404 silencioso) — esta página siempre
     dependió por completo del bundle global de users.css para su estilo. --}}
@push('styles')
    @vite('resources/css/admin/pages/purchase-orders.css')
@endpush

@section('content')
<div class="po-page">

    {{-- Header --}}
    <header class="po-header">
        <nav class="po-breadcrumb">
            <a href="{{ route('admin.purchase-orders.index') }}">Órdenes de Compra</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m9 18 6-6-6-6"/>
            </svg>
            <span>Nueva Orden</span>
        </nav>
        <div class="po-header__title-row">
            <a href="{{ route('admin.purchase-orders.index') }}" class="po-back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                </svg>
            </a>
            <h1 class="po-header__title">Nueva Orden de Compra</h1>
        </div>
    </header>

    <form method="POST" action="{{ route('admin.purchase-orders.store') }}" id="poCreateForm">
        @csrf
        <input type="hidden" name="po_number"          value="{{ $nextPoNumber }}">
        <input type="hidden" name="created_by_user_id" value="{{ auth()->id() }}">
        <input type="hidden" name="currency"            value="MXN">
        <input type="hidden" name="subtotal"   id="hiddenSubtotal" value="0">
        <input type="hidden" name="tax_total"  id="hiddenTaxTotal" value="0">
        <input type="hidden" name="total"      id="hiddenTotal"    value="0">

        <div class="po-layout">

            {{-- ── LEFT COLUMN ── --}}
            <div class="po-main">

                {{-- Supplier info --}}
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" x2="21" y1="6" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        Datos del Proveedor
                    </h2>

                    <div class="po-form-group">
                        <label class="po-label">
                            PROVEEDOR <span class="po-req">*</span>
                        </label>
                        <select name="supplier_id" id="supplierSelect" class="po-select" required>
                            <option value="">Buscar proveedor...</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    data-contact="{{ $supplier->contact_name ?? '' }}"
                                    data-email="{{ $supplier->email ?? '' }}"
                                    data-phone="{{ $supplier->phone ?? '' }}"
                                    data-rfc="{{ $supplier->rfc ?? '' }}"
                                    data-terms="{{ $supplier->payment_terms ?? '' }}"
                                    data-company="{{ $supplier->company_name }}">
                                    {{ strtoupper($supplier->company_name) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier info card --}}
                    <div id="supplierInfoCard" class="po-supplier-card" style="display:none;">
                        <p class="po-supplier-card__name" id="infoCompany">—</p>
                        <div class="po-supplier-card__grid">
                            <span class="po-supplier-card__label">Contacto:</span>  <span id="infoContact">—</span>
                            <span class="po-supplier-card__label">Email:</span>      <span id="infoEmail">—</span>
                            <span class="po-supplier-card__label">Teléfono:</span>   <span id="infoPhone">—</span>
                            <span class="po-supplier-card__label">RFC:</span>        <span id="infoRfc">—</span>
                            <span class="po-supplier-card__label">Términos:</span>   <span id="infoTerms">—</span>
                        </div>
                    </div>

                    <div class="po-form-grid">
                        <div class="po-form-group">
                            <label class="po-label">FECHA DE LA ORDEN <span class="po-req">*</span></label>
                            <input type="date" name="order_date" class="po-input"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="po-form-group">
                            <label class="po-label">FECHA DE ENTREGA ESPERADA</label>
                            <input type="date" name="expected_delivery_date" class="po-input">
                        </div>
                    </div>

                    <div class="po-form-group">
                        <label class="po-label">REFERENCIA INTERNA</label>
                        <input type="text" name="internal_reference" class="po-input po-item-input--readonly"
                            value="{{ $nextInternalReference }}" readonly>
                    </div>

                    <div class="po-form-group">
                        <label class="po-label">NOTAS AL PROVEEDOR</label>
                        <textarea name="notes" class="po-textarea" rows="3"
                            placeholder="Instrucciones especiales, condiciones de entrega..."></textarea>
                    </div>
                </div>

                {{-- Productos / Servicios --}}
                <div class="po-card">
                    <h2 class="po-card__header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                            <path d="M12 22V12"/>
                            <polyline points="3.29 7 12 12 20.71 7"/>
                        </svg>
                        Productos / Servicios
                    </h2>

                    <div class="po-products-toolbar">
                        <div class="po-inline-search" id="poInlineSearch">
                            <div class="po-inline-search__wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="po-inline-search__icon">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                                </svg>
                                <input type="text" id="poProductInput" class="po-inline-search__input"
                                    placeholder="Buscar producto del catálogo..." autocomplete="off">
                                <button type="button" id="poProductClear"
                                    class="po-inline-search__clear" style="display:none;">✕</button>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14"/><path d="M5 12h14"/>
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
                                <tr id="poEmptyRow" class="po-table__empty">
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
                            <span id="summarySubtotal" class="po-summary__val">$0.00</span>
                        </div>
                        <div class="po-summary__row">
                            <span>Descuento global</span>
                            <div class="po-summary__input-group">
                                <input type="number" name="discount_total" id="globalDiscount"
                                    class="po-summary__input" value="0" min="0" max="100" step="0.01">
                                <span class="po-summary__pct">%</span>
                                <span id="summaryDiscount" class="po-summary__val po-summary__val--red">-$0.00</span>
                            </div>
                        </div>
                        <div class="po-summary__row">
                            <span>Base gravable</span>
                            <span id="summaryBase" class="po-summary__val">$0.00</span>
                        </div>
                        <div class="po-summary__row">
                            <span>IVA</span>
                            <div class="po-summary__input-group">
                                <input type="number" name="tax_rate" id="taxRate"
                                    class="po-summary__input" value="16" min="0" max="100" step="0.01">
                                <span class="po-summary__pct">%</span>
                                <span id="summaryTax" class="po-summary__val">$0.00</span>
                            </div>
                        </div>
                        <div class="po-summary__total">
                            <span>TOTAL</span>
                            <span id="summaryTotal" class="po-summary__total-val">$0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="po-card">
                    <h2 class="po-card__header">Estado de la Orden</h2>
                    <select name="status" id="statusSelect" class="po-select po-status-select">
                        <option value="pending">● Pendiente</option>
                        <option value="accepted">● Aceptada</option>
                        <option value="rejected">● Rechazada</option>
                    </select>
                </div>

                {{-- Info adicional --}}
                <div class="po-card">
                    <h2 class="po-card__header">Información adicional</h2>
                    <div class="po-info-row">
                        <span class="po-info-row__label">Creada por</span>
                        <span class="po-info-row__val">
                            {{ auth()->user()->first_name ?? 'Admin' }} {{ auth()->user()->last_name ?? '' }}
                        </span>
                    </div>
                    <div class="po-info-row">
                        <span class="po-info-row__label">Folio</span>
                        <span class="po-info-row__val po-info-row__val--orange">{{ $nextPoNumber }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <button type="submit" name="action" value="draft" class="po-btn-draft">
                    Guardar borrador
                </button>
                <button type="submit" name="action" value="save" class="po-btn-save">
                    Guardar y ver →
                </button>

            </aside>

        </div>
    </form>
</div>
@endsection
@include('admin.purchase-orders.create._scripts')

