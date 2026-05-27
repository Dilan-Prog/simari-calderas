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

                    {{-- Selector de cliente registrado --}}
                    <div class="form-group">
                        <label class="form-label">Buscar Cliente registrado</label>
                        <div class="client-select-wrap">
                            <input type="text" id="clientSearchInput" class="form-input"
                                   placeholder="Escribe el nombre o empresa..."
                                   autocomplete="off">
                            <div id="clientDropdown" class="client-dropdown" style="display:none">
                                @foreach($customers as $customer)
                                    <div class="client-dropdown__item"
                                         data-name="{{ trim($customer->first_name . ' ' . $customer->last_name) }}"
                                         data-company="{{ $customer->company ?? '' }}"
                                         data-email="{{ $customer->email ?? '' }}"
                                         data-phone="{{ $customer->phone ?? '' }}"
                                         data-rfc="{{ $customer->rfc ?? '' }}">
                                        <span class="client-dropdown__name">{{ $customer->company }}</span>
                                        @if($customer->company)
                                            <span class="client-dropdown__company">{{ trim($customer->first_name . ' ' . $customer->last_name) }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="client-dropdown__empty" style="display:none">Sin resultados</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nombre completo <span class="form-req">*</span></label>
                        <input type="text" name="guest_name" id="guestName" class="form-input"
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

@endsection

@push('scripts')
<script src="{{ asset('js/quotes.js') }}"></script>
<script>
window.ADMIN_QUOTES_CONFIG = {
    searchUrl: "{{ route('admin.quotes.search-products') }}"
};

// ── Inline product search ────────────────────────────────
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
                  QuoteForm.addRow(p);
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

// ── Buscador de cliente registrado ──────────────────────
(function () {
    const searchInput = document.getElementById('clientSearchInput');
    const dropdown    = document.getElementById('clientDropdown');
    const items       = Array.from(dropdown.querySelectorAll('.client-dropdown__item'));
    const emptyMsg    = dropdown.querySelector('.client-dropdown__empty');

    function filterItems(q) {
        q = q.toLowerCase().trim();
        let visible = 0;
        items.forEach(item => {
            const name    = item.dataset.name.toLowerCase();
            const company = item.dataset.company.toLowerCase();
            const match   = !q || name.includes(q) || company.includes(q);
            item.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        emptyMsg.style.display = visible === 0 ? '' : 'none';
    }

    searchInput.addEventListener('focus', () => {
        filterItems(searchInput.value);
        dropdown.style.display = 'block';
    });

    searchInput.addEventListener('input', () => {
        filterItems(searchInput.value);
        dropdown.style.display = 'block';
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.client-select-wrap')) {
            dropdown.style.display = 'none';
        }
    });

    items.forEach(item => {
        item.addEventListener('click', () => {
            // Rellenar campos del receptor
            document.getElementById('guestName').value                           = item.dataset.name;
            document.querySelector('[name="guest_company"]').value               = item.dataset.company;
            document.querySelector('[name="guest_email"]').value                 = item.dataset.email;
            document.querySelector('[name="guest_phone"]').value                 = item.dataset.phone;
            document.querySelector('[name="guest_rfc"]').value                   = item.dataset.rfc;

            // Actualizar el buscador con el nombre seleccionado
            searchInput.value = item.dataset.name + (item.dataset.company ? ' — ' + item.dataset.company : '');
            dropdown.style.display = 'none';
        });
    });
})();
</script>
@vite(['resources/js/admin-quotes.js'])
@endpush
