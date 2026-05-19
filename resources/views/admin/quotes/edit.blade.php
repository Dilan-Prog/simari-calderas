@extends('admin.layouts.master')

@section('title', 'Editar Cotización — ' . $quote->quote_number)

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
            <a href="{{ route('admin.quotes.show', $quote) }}">{{ $quote->quote_number }}</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            <span>Editar</span>
        </nav>

        <div class="quotes-header__title-row">
            <h1 class="quotes-header__title">Editar Cotización</h1>
            <span class="folio-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/>
                    <path d="M14 2v6h6"/>
                </svg>
                {{ $quote->quote_number }}
            </span>
        </div>
        <p class="quotes-header__subtitle">Modifica los datos del receptor o los productos de esta cotización</p>
    </header>

    <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" id="quoteEditForm" novalidate>
        @csrf
        @method('PUT')

        <div class="quotes-layout">

            {{-- ════ MAIN (columna izquierda) ════ --}}
            <div class="quotes-main">

                {{-- Datos del Receptor --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Datos del Receptor</h2>

                    <div class="form-group">
                        <label class="form-label">Nombre completo <span class="form-req">*</span></label>
                        <input type="text" name="guest_name" id="guestName" class="form-input"
                               value="{{ old('guest_name', $quote->guest_name) }}"
                               autocomplete="off" placeholder="Nombre y apellidos del receptor">
                        @error('guest_name')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Empresa</label>
                        <input type="text" name="guest_company" class="form-input"
                               value="{{ old('guest_company', $quote->guest_company) }}"
                               placeholder="Razón social o nombre comercial">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="guest_email" class="form-input"
                                   value="{{ old('guest_email', $quote->guest_email) }}"
                                   placeholder="correo@empresa.com">
                            @error('guest_email')<span class="form-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="guest_phone" class="form-input"
                                   value="{{ old('guest_phone', $quote->guest_phone) }}"
                                   placeholder="+52 449 000 0000">
                        </div>
                    </div>

                    {{-- Datos fiscales --}}
                    <div class="form-group--highlighted">
                        <span class="form-section-label">Datos Fiscales</span>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">RFC</label>
                                <input type="text" name="guest_rfc" class="form-input"
                                       value="{{ old('guest_rfc', $quote->guest_rfc) }}"
                                       placeholder="XXXX000000XX0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Válido hasta</label>
                                <input type="date" name="valid_until" class="form-input"
                                       value="{{ old('valid_until', $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '') }}">
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

                    {{-- Error inline de validación JS --}}
                    <div id="itemsError" class="alert-error" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        Debes agregar al menos un producto o servicio.
                    </div>

                    <div class="products-toolbar">

                        {{-- Búsqueda inline de productos --}}
                        <div class="inline-product-search" id="inlineProductSearch">
                            <div class="inline-product-search__input-wrap">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                                     class="inline-product-search__icon" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                                </svg>
                                <input type="text"
                                       id="inlineProductInput"
                                       class="inline-product-search__input"
                                       placeholder="Buscar por nombre, SKU, marca o categoría..."
                                       autocomplete="off">
                                <button type="button"
                                        class="inline-product-search__clear"
                                        id="inlineProductClear"
                                        style="display:none"
                                        aria-label="Limpiar búsqueda">✕</button>
                            </div>

                            <div class="inline-product-search__dropdown"
                                 id="inlineProductDropdown"
                                 style="display:none">
                                <div class="inline-product-search__loading"
                                     id="inlineProductLoading"
                                     style="display:none">
                                    <span class="inline-product-search__spinner"></span>
                                    Buscando productos...
                                </div>
                                <div class="inline-product-search__empty"
                                     id="inlineProductEmpty"
                                     style="display:none">
                                    Sin resultados para "<span id="inlineProductEmptyQuery"></span>"
                                </div>
                                <ul class="inline-product-search__list" id="inlineProductList"></ul>
                            </div>
                        </div>

                        <button type="button" class="btn-add-row" onclick="QuoteForm.addFreeRow()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                                 aria-hidden="true">
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

            {{-- ════ SIDEBAR (columna derecha) ════ --}}
            <aside class="summary-panel">

                {{-- Configuración --}}
                <div class="quotes-card">
                    <h2 class="quotes-card__header">Configuración</h2>

                    <div class="form-group">
                        <label class="form-label">IVA (%) <span class="form-req">*</span></label>
                        <input type="number" name="tax_rate" id="taxRate" class="form-input"
                               value="{{ old('tax_rate', $quote->tax_rate) }}"
                               step="0.01" min="0" max="100">
                        @error('tax_rate')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notas internas</label>
                        <textarea name="notes" class="form-textarea"
                                  placeholder="Observaciones visibles solo para el equipo">{{ old('notes', $quote->notes) }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top:4px;">
                        <label class="form-label" style="margin-bottom:8px;">Términos y condiciones</label>
                        <details class="accordion-conditions" {{ $quote->terms_conditions ? 'open' : '' }}>
                            <summary class="accordion-conditions__summary">Editar condiciones</summary>
                            <div class="accordion-conditions__body">
                                <textarea name="terms_conditions" class="form-textarea"
                                          placeholder="Términos que aparecerán en el PDF de la cotización">{{ old('terms_conditions', $quote->terms_conditions) }}</textarea>
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
                                       value="{{ old('discount_total', $quote->discount_total) }}"
                                       min="0" step="0.01" placeholder="0.00">
                            </span>
                        </div>
                        <div class="summary-totals__row">
                            <span>IVA (<span id="displayTaxRate">{{ $quote->tax_rate }}</span>%)</span>
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
                        Actualizar Cotización
                    </button>
                    <a href="{{ route('admin.quotes.show', $quote) }}" class="btn-cancel">Cancelar</a>
                </div>

            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quotes.js') }}"></script>
<script>
window.ADMIN_QUOTES_CONFIG = {
    searchUrl: "{{ route('admin.quotes.search-products') }}"
};

// ── Cargar items existentes y sincronizar totales ────────
(function () {
    const existingItems = {!! json_encode($quote->items->map(function ($i) {
        return [
            'product_id'       => $i->product_id,
            'product_name'     => $i->product_name,
            'product_sku'      => $i->product_sku,
            'quantity'         => $i->quantity,
            'unit_price'       => (float) $i->unit_price,
            'discount_percent' => (float) $i->discount_percent,
            'tax_percent'      => (float) $i->tax_percent,
            'line_total'       => (float) $i->line_total,
            'notes'            => $i->notes,
        ];
    })) !!};

    existingItems.forEach(item => QuoteForm.addProductRow(item, true));
    QuoteForm.calculateGlobalTotals();

    document.getElementById('taxRate').addEventListener('input', function () {
        document.getElementById('displayTaxRate').textContent = this.value || '0';
        QuoteForm.calculateGlobalTotals();
    });

    document.getElementById('discountTotal').addEventListener('input', QuoteForm.calculateGlobalTotals);
})();

// ── Validación + submit ──────────────────────────────────
(function () {
    const form      = document.getElementById('quoteEditForm');
    const nameField = form.querySelector('[name="guest_name"]');
    const itemsErr  = document.getElementById('itemsError');

    function clearNameError() {
        nameField.classList.remove('val-error-input');
        form.querySelectorAll('.val-error-msg[data-for="guest_name"]').forEach(e => e.remove());
    }

    nameField.addEventListener('input', clearNameError);

    form.addEventListener('submit', function (e) {
        let blocked = false;

        // Validar nombre
        if (!nameField.value.trim()) {
            nameField.classList.add('val-error-input');
            if (!form.querySelector('.val-error-msg[data-for="guest_name"]')) {
                const msg = document.createElement('span');
                msg.className  = 'val-error-msg';
                msg.dataset.for = 'guest_name';
                msg.textContent = 'El campo "Nombre completo" es requerido';
                nameField.insertAdjacentElement('afterend', msg);
            }
            blocked = true;
        }

        // Validar items
        if (!QuoteForm.serializeItems()) {
            itemsErr.style.display = 'flex';
            blocked = true;
        } else {
            itemsErr.style.display = 'none';
        }

        if (blocked) {
            e.preventDefault();
            const firstErr = form.querySelector('.val-error-input') || itemsErr;
            firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
})();

// ── Inline product search (igual que create) ─────────────
(function () {
    const input    = document.getElementById('inlineProductInput');
    const dropdown = document.getElementById('inlineProductDropdown');
    const list     = document.getElementById('inlineProductList');
    const loading  = document.getElementById('inlineProductLoading');
    const empty    = document.getElementById('inlineProductEmpty');
    const emptyQ   = document.getElementById('inlineProductEmptyQuery');
    const clearBtn = document.getElementById('inlineProductClear');
    const SEARCH_URL = window.ADMIN_QUOTES_CONFIG.searchUrl;

    let debounceTimer = null;
    let currentQuery  = '';

    function showLoading() {
        loading.style.display  = 'flex';
        empty.style.display    = 'none';
        list.style.display     = 'none';
        dropdown.style.display = 'block';
    }

    function showResults(items) {
        loading.style.display = 'none';
        if (items.length === 0) {
            empty.style.display    = 'flex';
            emptyQ.textContent     = currentQuery;
            list.style.display     = 'none';
        } else {
            empty.style.display    = 'none';
            list.style.display     = 'block';
            renderItems(items);
        }
    }

    function hideDropdown() {
        dropdown.style.display = 'none';
        list.innerHTML = '';
    }

    function formatPrice(n) {
        return '$' + parseFloat(n).toLocaleString('es-MX', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;')
                              .replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderItems(products) {
        list.innerHTML = '';
        products.forEach(p => {
            const hasStock = p.stock > 0;
            const li = document.createElement('li');
            li.className = 'inline-product-search__item' + (!hasStock ? ' is-out-of-stock' : '');
            const imgSrc = p.image_url ? esc(p.image_url) : '';
            li.innerHTML = `
                <div class="inline-product-search__item-img">
                    ${imgSrc
                        ? `<img src="${imgSrc}" alt="${esc(p.name)}" onerror="this.onerror=null;this.style.display='none';">`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>`
                    }
                </div>
                <div class="inline-product-search__item-info">
                    <span class="inline-product-search__item-name">${esc(p.name)}</span>
                    <span class="inline-product-search__item-sku">SKU: ${esc(p.sku)}</span>
                    <span class="inline-product-search__item-meta">
                        ${[p.category, p.brand].filter(Boolean).map(esc).join(' · ')}
                    </span>
                </div>
                <div class="inline-product-search__item-right">
                    <div class="inline-product-search__item-stock ${hasStock ? 'in-stock' : 'out-of-stock'}">
                        ${hasStock ? p.stock + ' disponibles' : 'Sin stock'}
                    </div>
                    <div class="inline-product-search__item-price">${formatPrice(p.price)}</div>
                    <button type="button"
                            class="inline-product-search__add-btn"
                            aria-label="Agregar ${esc(p.name)}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14"/><path d="M5 12h14"/>
                        </svg>
                    </button>
                </div>`;

            li.querySelector('.inline-product-search__add-btn')
              .addEventListener('click', () => {
                  QuoteForm.addProductRow(p);
                  document.getElementById('itemsError').style.display = 'none';
                  input.value = '';
                  clearBtn.style.display = 'none';
                  hideDropdown();
                  input.focus();
              });

            list.appendChild(li);
        });
    }

    async function fetchProducts(query) {
        try {
            const url = new URL(SEARCH_URL, window.location.origin);
            url.searchParams.set('q', query);
            const res  = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('Server error');
            const data = await res.json();
            return Array.isArray(data) ? data : (data.data ?? []);
        } catch {
            return [];
        }
    }

    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearBtn.style.display = q ? 'block' : 'none';
        clearTimeout(debounceTimer);
        if (q.length < 2) { hideDropdown(); return; }
        currentQuery = q;
        showLoading();
        debounceTimer = setTimeout(async () => {
            const products = await fetchProducts(q);
            if (currentQuery === q) showResults(products);
        }, 300);
    });

    clearBtn.addEventListener('click', () => {
        input.value = '';
        clearBtn.style.display = 'none';
        hideDropdown();
        input.focus();
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#inlineProductSearch')) hideDropdown();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { hideDropdown(); input.blur(); }
    });
})();
</script>
@vite(['resources/js/admin-quotes.js'])
@endpush
