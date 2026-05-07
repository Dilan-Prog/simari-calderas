@extends('admin.layouts.master')

@section('title', 'Nueva Cotización - Admin')

@push('styles')
@vite(['resources/css/admin-quotes.css'])
@endpush

@section('content')
<div class="quotes-page">

    {{-- ── Header ─────────────────────────────────────── --}}
    <header class="quotes-header">
        <nav class="breadcrumb" aria-label="breadcrumb">
            <a href="{{ route('admin.quotes.index') }}">Cotizaciones</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span>Nueva Cotización</span>
        </nav>

        <div class="quotes-header__title-row">
            <h1 class="quotes-header__title">Nueva Cotización</h1>
            <span class="folio-badge" title="El folio se asigna automáticamente al guardar">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                    <path d="M14 2v6h6"/>
                </svg>
                COT-{{ date('Y') }}-XXXX
            </span>
        </div>
        <p class="quotes-header__subtitle">Completa los datos del receptor y agrega los productos o servicios a cotizar</p>
    </header>

    <form method="POST" action="{{ route('admin.quotes.store') }}" id="quoteForm">
        @csrf

        <div class="quotes-layout">

            {{-- ════════════════════════════════════════════
                 MAIN  (columna izquierda)
            ════════════════════════════════════════════ --}}
            <div class="quotes-main">

                {{-- Datos del Receptor --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Datos del Receptor</h2>

                    <div class="form-group">
                        <label class="form-label">Nombre completo <span class="form-req">*</span></label>
                        <input type="text" name="guest_name" class="form-input"
                               value="{{ old('guest_name') }}" required autocomplete="off"
                               placeholder="Nombre y apellidos del receptor">
                        @error('guest_name')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Empresa</label>
                        <input type="text" name="guest_company" class="form-input"
                               value="{{ old('guest_company') }}" placeholder="Razón social o nombre comercial">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="guest_email" class="form-input"
                                   value="{{ old('guest_email') }}" placeholder="correo@empresa.com">
                            @error('guest_email')<span class="form-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="guest_phone" class="form-input"
                                   value="{{ old('guest_phone') }}" placeholder="+52 449 000 0000">
                        </div>
                    </div>

                    {{-- Datos fiscales --}}
                    <div class="form-group--highlighted">
                        <span class="form-section-label">Datos Fiscales</span>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">RFC</label>
                                <input type="text" name="guest_rfc" class="form-input"
                                       value="{{ old('guest_rfc') }}" placeholder="XXXX000000XX0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Válido hasta</label>
                                <input type="date" name="valid_until" class="form-input"
                                       value="{{ old('valid_until') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Productos / Servicios --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Productos / Servicios</h2>

                    @error('items_json')
                    <div class="alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        {{ $message }}
                    </div>
                    @enderror

                    <div class="products-toolbar">
                        <button type="button" class="btn-outline" onclick="openProductSearch()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                            </svg>
                            Buscar en catálogo
                        </button>
                        <button type="button" class="btn-add-row" onclick="QuoteForm.addFreeRow()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5v14"/><path d="M5 12h14"/>
                            </svg>
                            Agregar línea libre
                        </button>
                    </div>

                    <div class="products-table-wrap">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-name">Producto / Descripción</th>
                                    <th class="col-sku">SKU</th>
                                    <th class="col-qty">Cant.</th>
                                    <th class="col-price">Precio Unit.</th>
                                    <th class="col-disc">Desc. %</th>
                                    <th class="col-total">Subtotal</th>
                                    <th class="col-del"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyRow" class="products-table-empty">
                                    <td colspan="8">Agrega productos usando los botones de arriba</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <input type="hidden" name="items_json" id="itemsJson">
                </div>
            </div>

            {{-- ════════════════════════════════════════════
                 SIDEBAR  (columna derecha)
            ════════════════════════════════════════════ --}}
            <aside class="summary-panel">

                {{-- Configuración --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Configuración</h2>

                    <div class="form-group">
                        <label class="form-label">IVA (%) <span class="form-req">*</span></label>
                        <input type="number" name="tax_rate" id="taxRate" class="form-input"
                               value="{{ old('tax_rate', 16) }}" step="0.01" min="0" max="100">
                        @error('tax_rate')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notas internas</label>
                        <textarea name="notes" class="form-textarea"
                                  placeholder="Observaciones visibles solo para el equipo">{{ old('notes') }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top:4px;">
                        <label class="form-label" style="margin-bottom:8px;">Términos y condiciones</label>
                        <details class="accordion-conditions">
                            <summary class="accordion-conditions__summary">Editar condiciones</summary>
                            <div class="accordion-conditions__body">
                                <textarea name="terms_conditions" class="form-textarea"
                                          placeholder="Términos que aparecerán en el PDF de la cotización">{{ old('terms_conditions') }}</textarea>
                            </div>
                        </details>
                    </div>
                </div>

                {{-- Resumen / Totales + CTA --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Resumen</h2>

                    <div class="summary-totals">
                        <div class="summary-totals__row">
                            <span>Subtotal</span>
                            <span id="displaySubtotal">$0.00</span>
                        </div>
                        <div class="summary-totals__row">
                            <span>Descuento global</span>
                            <span>
                                <input type="number" name="discount_total" id="discountTotal"
                                       class="discount-input"
                                       value="{{ old('discount_total', 0) }}"
                                       min="0" step="0.01" placeholder="0.00">
                            </span>
                        </div>
                        <div class="summary-totals__row">
                            <span>IVA (<span id="displayTaxRate">16</span>%)</span>
                            <span id="displayTaxTotal">$0.00</span>
                        </div>
                    </div>

                    <div class="summary-total-block">
                        <span class="summary-total-block__label">Total</span>
                        <span class="summary-total-block__amount" id="displayTotal">$0.00</span>
                    </div>
                    <p class="iva-note">IVA incluido</p>

                    <button type="submit" class="btn-primary btn-primary--full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Guardar Cotización
                    </button>
                    <a href="{{ route('admin.quotes.index') }}" class="btn-cancel">Cancelar</a>
                </div>

            </aside>
        </div>
    </form>
</div>

{{-- ── Modal búsqueda de productos ───────────────────── --}}
<div class="product-search-backdrop" id="searchBackdrop" onclick="closeSearchOnBackdrop(event)">
    <div class="product-search-modal">
        <div class="product-search-modal__header">
            <span class="product-search-modal__title">Buscar producto</span>
            <button class="product-search-close" onclick="closeProductSearch()" aria-label="Cerrar">&#x2715;</button>
        </div>
        <div class="product-search-input-wrap">
            <input type="text" id="searchInput" class="product-search-input"
                   placeholder="Nombre o SKU del producto..." autocomplete="off">
        </div>
        <div class="product-search-results" id="searchResults">
            <div class="product-search-empty">Escribe para buscar productos...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quotes.js') }}"></script>
<script>
window.ADMIN_QUOTES_CONFIG = {
    searchUrl: "{{ route('admin.quotes.search-products') }}"
};
</script>
@vite(['resources/js/admin-quotes.js'])
@endpush
