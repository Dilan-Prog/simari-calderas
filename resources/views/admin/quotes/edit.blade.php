@extends('admin.layouts.master')

@section('title', 'Editar Cotización - Admin')

@push('styles')
<style>
.qform-page { padding: 28px 32px; }
.qform-header { margin-bottom: 24px; }
.qform-title { font-size: 22px; font-weight: 700; color: #f1f1f1; margin: 0; }
.qform-subtitle { font-size: 13px; color: #888; margin: 4px 0 0; }
.qform-breadcrumb { font-size: 12px; color: #555; margin-bottom: 8px; }
.qform-breadcrumb a { color: #e8612c; text-decoration: none; }
.qform-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.qform-section { background: #16161f; border: 1px solid #2a2a38; border-radius: 10px; padding: 24px; }
.qform-section-full { grid-column: 1 / -1; }
.qform-section-title { font-size: 13px; font-weight: 700; color: #e8612c; text-transform: uppercase; letter-spacing: .8px; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #2a2a38; }
.qform-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.qform-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.qform-field:last-child { margin-bottom: 0; }
.qform-label { font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: .5px; }
.qform-input, .qform-select, .qform-textarea { background: #1c1c28; border: 1px solid #2e2e3e; color: #ddd; border-radius: 7px; padding: 9px 12px; font-size: 13px; font-family: inherit; transition: border-color .2s; width: 100%; box-sizing: border-box; }
.qform-input:focus, .qform-select:focus, .qform-textarea:focus { outline: none; border-color: #e8612c; }
.qform-textarea { resize: vertical; min-height: 80px; }
.qform-error { font-size: 11px; color: #e06060; margin-top: 3px; }
.qform-items-toolbar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.qform-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: background .2s; }
.qform-btn-primary { background: #e8612c; color: #fff; }
.qform-btn-primary:hover { background: #cf5425; }
.qform-btn-secondary { background: #2a2a38; color: #ccc; border: 1px solid #3a3a50; }
.qform-btn-secondary:hover { background: #333346; color: #fff; }
.qform-items-table-wrap { overflow-x: auto; }
.qform-items-table { width: 100%; border-collapse: collapse; min-width: 800px; }
.qform-items-table thead th { background: #1c1c28; padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700; color: #666; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #2a2a38; }
.qform-items-table tbody tr { border-bottom: 1px solid #1e1e2a; }
.qform-items-table td { padding: 8px 8px; vertical-align: middle; }
.qform-items-table .cell-input { background: #1c1c28; border: 1px solid #2e2e3e; color: #ddd; border-radius: 6px; padding: 7px 10px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
.qform-items-table .cell-input:focus { outline: none; border-color: #e8612c; }
.qform-items-table .col-num { width: 40px; text-align: center; color: #555; font-size: 12px; }
.qform-items-table .col-name { min-width: 200px; }
.qform-items-table .col-sku { width: 100px; }
.qform-items-table .col-qty { width: 80px; }
.qform-items-table .col-price { width: 110px; }
.qform-items-table .col-disc { width: 80px; }
.qform-items-table .col-total { width: 110px; text-align: right; font-weight: 600; color: #f1f1f1; font-size: 13px; padding-right: 12px; }
.qform-items-table .col-del { width: 40px; text-align: center; }
.qform-totals-wrap { display: flex; justify-content: flex-end; margin-top: 16px; }
.qform-totals-box { background: #1c1c28; border: 1px solid #2a2a38; border-radius: 8px; padding: 16px 20px; min-width: 280px; }
.qform-totals-row { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; font-size: 13px; color: #aaa; }
.qform-totals-row span:last-child { font-weight: 600; color: #ddd; }
.qform-totals-row.total-final { border-top: 1px solid #2e2e3e; margin-top: 8px; padding-top: 10px; font-size: 16px; color: #f1f1f1; font-weight: 700; }
.qform-totals-row.total-final span:last-child { color: #e8612c; }
.qform-discount-input { background: #16161f; border: 1px solid #2e2e3e; color: #ddd; border-radius: 5px; padding: 4px 8px; font-size: 13px; width: 100px; text-align: right; font-family: inherit; }
.qform-discount-input:focus { outline: none; border-color: #e8612c; }
.qform-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
.qform-btn-submit { background: #e8612c; color: #fff; padding: 11px 28px; font-size: 14px; }
.qform-btn-submit:hover { background: #cf5425; }
.qform-btn-cancel { background: #2a2a38; color: #ccc; border: 1px solid #3a3a50; padding: 11px 24px; font-size: 14px; text-decoration: none; }
.qform-btn-cancel:hover { background: #333346; color: #fff; }
.qsearch-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 1000; align-items: center; justify-content: center; }
.qsearch-backdrop.open { display: flex; }
.qsearch-modal { background: #16161f; border: 1px solid #2a2a38; border-radius: 12px; width: 520px; max-width: 95vw; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden; }
.qsearch-modal-header { padding: 18px 20px; border-bottom: 1px solid #2a2a38; display: flex; align-items: center; justify-content: space-between; }
.qsearch-modal-title { font-size: 15px; font-weight: 700; color: #f1f1f1; }
.qsearch-close { background: none; border: none; color: #666; cursor: pointer; font-size: 20px; line-height: 1; padding: 0; }
.qsearch-close:hover { color: #fff; }
.qsearch-input-wrap { padding: 14px 20px; border-bottom: 1px solid #2a2a38; }
.qsearch-input { width: 100%; background: #1c1c28; border: 1px solid #2e2e3e; color: #ddd; border-radius: 7px; padding: 10px 14px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
.qsearch-input:focus { outline: none; border-color: #e8612c; }
.qsearch-results { overflow-y: auto; flex: 1; }
.qsearch-result-item { display: flex; align-items: center; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #1e1e2a; cursor: pointer; transition: background .15s; }
.qsearch-result-item:hover { background: #1c1c28; }
.qsearch-result-img { width: 44px; height: 44px; border-radius: 6px; object-fit: cover; background: #2a2a38; flex-shrink: 0; }
.qsearch-result-img-placeholder { width: 44px; height: 44px; border-radius: 6px; background: #2a2a38; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #555; }
.qsearch-result-info { flex: 1; min-width: 0; }
.qsearch-result-name { font-size: 13px; font-weight: 600; color: #eee; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.qsearch-result-sku { font-size: 11px; color: #666; margin-top: 2px; }
.qsearch-result-price { font-size: 14px; font-weight: 700; color: #e8612c; white-space: nowrap; }
.qsearch-empty { padding: 40px 20px; text-align: center; color: #555; font-size: 13px; }
.qsearch-loading { padding: 40px 20px; text-align: center; color: #555; font-size: 13px; }
</style>
@endpush

@section('content')
<div class="qform-page">
    <div class="qform-header">
        <p class="qform-breadcrumb">
            <a href="{{ route('admin.quotes.index') }}">Cotizaciones</a> /
            <a href="{{ route('admin.quotes.show', $quote) }}">{{ $quote->quote_number }}</a> /
            Editar
        </p>
        <h1 class="qform-title">Editar Cotización</h1>
        <p class="qform-subtitle">{{ $quote->quote_number }}</p>
    </div>

    <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" id="quoteForm">
        @csrf @method('PUT')

        <div class="qform-grid">
            <div class="qform-section">
                <h2 class="qform-section-title">Datos del Receptor</h2>
                <div class="qform-field">
                    <label class="qform-label">Nombre completo *</label>
                    <input type="text" name="guest_name" class="qform-input" value="{{ old('guest_name', $quote->guest_name) }}" required>
                    @error('guest_name')<span class="qform-error">{{ $message }}</span>@enderror
                </div>
                <div class="qform-row">
                    <div class="qform-field">
                        <label class="qform-label">Correo electrónico</label>
                        <input type="email" name="guest_email" class="qform-input" value="{{ old('guest_email', $quote->guest_email) }}">
                        @error('guest_email')<span class="qform-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="qform-field">
                        <label class="qform-label">Teléfono</label>
                        <input type="text" name="guest_phone" class="qform-input" value="{{ old('guest_phone', $quote->guest_phone) }}">
                    </div>
                </div>
                <div class="qform-row">
                    <div class="qform-field">
                        <label class="qform-label">Empresa</label>
                        <input type="text" name="guest_company" class="qform-input" value="{{ old('guest_company', $quote->guest_company) }}">
                    </div>
                    <div class="qform-field">
                        <label class="qform-label">RFC</label>
                        <input type="text" name="guest_rfc" class="qform-input" value="{{ old('guest_rfc', $quote->guest_rfc) }}">
                    </div>
                </div>
            </div>

            <div class="qform-section">
                <h2 class="qform-section-title">Configuración</h2>
                <div class="qform-row">
                    <div class="qform-field">
                        <label class="qform-label">Válido hasta</label>
                        <input type="date" name="valid_until" class="qform-input" value="{{ old('valid_until', $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '') }}">
                    </div>
                    <div class="qform-field">
                        <label class="qform-label">IVA (%)</label>
                        <input type="number" name="tax_rate" id="taxRate" class="qform-input" value="{{ old('tax_rate', $quote->tax_rate) }}" step="0.01" min="0" max="100">
                        @error('tax_rate')<span class="qform-error">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="qform-field">
                    <label class="qform-label">Notas internas</label>
                    <textarea name="notes" class="qform-textarea">{{ old('notes', $quote->notes) }}</textarea>
                </div>
                <div class="qform-field">
                    <label class="qform-label">Términos y condiciones</label>
                    <textarea name="terms_conditions" class="qform-textarea">{{ old('terms_conditions', $quote->terms_conditions) }}</textarea>
                </div>
            </div>

            <div class="qform-section qform-section-full">
                <h2 class="qform-section-title">Productos / Servicios</h2>

                @error('items_json')
                <div style="background:#2a1010;border:1px solid #5a2020;border-radius:7px;padding:10px 14px;margin-bottom:14px;color:#e06060;font-size:13px;">
                    {{ $message }}
                </div>
                @enderror

                <div class="qform-items-toolbar">
                    <button type="button" class="qform-btn qform-btn-primary" onclick="openProductSearch()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        Buscar producto del catálogo
                    </button>
                    <button type="button" class="qform-btn qform-btn-secondary" onclick="QuoteForm.addFreeRow()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Agregar línea libre
                    </button>
                </div>

                <div class="qform-items-table-wrap">
                    <table class="qform-items-table">
                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <th class="col-name">Producto / Descripción</th>
                                <th class="col-sku">SKU</th>
                                <th class="col-qty">Cantidad</th>
                                <th class="col-price">Precio Unit.</th>
                                <th class="col-disc">Desc. %</th>
                                <th class="col-total">Subtotal</th>
                                <th class="col-del"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr id="emptyRow">
                                <td colspan="8" style="text-align:center;padding:30px;color:#444;font-size:13px;">
                                    Agrega productos usando los botones de arriba
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="qform-totals-wrap">
                    <div class="qform-totals-box">
                        <div class="qform-totals-row">
                            <span>Subtotal</span>
                            <span id="displaySubtotal">$0.00</span>
                        </div>
                        <div class="qform-totals-row">
                            <span>Descuento global</span>
                            <span>
                                <input type="number" name="discount_total" id="discountTotal" class="qform-discount-input" value="{{ old('discount_total', $quote->discount_total) }}" min="0" step="0.01">
                            </span>
                        </div>
                        <div class="qform-totals-row">
                            <span>IVA (<span id="displayTaxRate">{{ $quote->tax_rate }}</span>%)</span>
                            <span id="displayTaxTotal">$0.00</span>
                        </div>
                        <div class="qform-totals-row total-final">
                            <span>Total</span>
                            <span id="displayTotal">$0.00</span>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="items_json" id="itemsJson">
            </div>
        </div>

        <div class="qform-actions">
            <a href="{{ route('admin.quotes.show', $quote) }}" class="qform-btn qform-btn-cancel">Cancelar</a>
            <button type="submit" class="qform-btn qform-btn-submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Actualizar Cotización
            </button>
        </div>
    </form>
</div>

<div class="qsearch-backdrop" id="searchBackdrop" onclick="closeSearchOnBackdrop(event)">
    <div class="qsearch-modal">
        <div class="qsearch-modal-header">
            <span class="qsearch-modal-title">Buscar Producto</span>
            <button class="qsearch-close" onclick="closeProductSearch()">&#x2715;</button>
        </div>
        <div class="qsearch-input-wrap">
            <input type="text" id="searchInput" class="qsearch-input" placeholder="Nombre o SKU del producto..." autocomplete="off">
        </div>
        <div class="qsearch-results" id="searchResults">
            <div class="qsearch-empty">Escribe para buscar productos...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quotes.js') }}"></script>
<script>
(function() {
    const SEARCH_URL = "{{ route('admin.quotes.search-products') }}";
    const existingItems = @json($quote->items->map(fn($i) => [
        'product_id'       => $i->product_id,
        'product_name'     => $i->product_name,
        'product_sku'      => $i->product_sku,
        'quantity'         => $i->quantity,
        'unit_price'       => (float) $i->unit_price,
        'discount_percent' => (float) $i->discount_percent,
        'tax_percent'      => (float) $i->tax_percent,
        'line_total'       => (float) $i->line_total,
        'notes'            => $i->notes,
    ]));

    existingItems.forEach(item => QuoteForm.addProductRow(item, true));
    QuoteForm.calculateGlobalTotals();

    document.getElementById('taxRate').addEventListener('input', function() {
        document.getElementById('displayTaxRate').textContent = this.value || '0';
        QuoteForm.calculateGlobalTotals();
    });

    document.getElementById('discountTotal').addEventListener('input', QuoteForm.calculateGlobalTotals);

    document.getElementById('quoteForm').addEventListener('submit', function(e) {
        if (!QuoteForm.serializeItems()) {
            e.preventDefault();
            alert('Debe agregar al menos un producto.');
        }
    });

    window.openProductSearch = function() {
        document.getElementById('searchBackdrop').classList.add('open');
        document.getElementById('searchInput').value = '';
        document.getElementById('searchResults').innerHTML = '<div class="qsearch-empty">Escribe para buscar productos...</div>';
        setTimeout(() => document.getElementById('searchInput').focus(), 100);
    };

    window.closeProductSearch = function() {
        document.getElementById('searchBackdrop').classList.remove('open');
    };

    window.closeSearchOnBackdrop = function(e) {
        if (e.target === document.getElementById('searchBackdrop')) closeProductSearch();
    };

    let searchTimer;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) {
            document.getElementById('searchResults').innerHTML = '<div class="qsearch-empty">Escribe al menos 2 caracteres...</div>';
            return;
        }
        document.getElementById('searchResults').innerHTML = '<div class="qsearch-loading">Buscando...</div>';
        searchTimer = setTimeout(() => QuoteSearch.search(q, SEARCH_URL), 300);
    });

    window.QuoteSearch = {
        search: function(q, url) {
            fetch(url + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(products => {
                    const container = document.getElementById('searchResults');
                    if (!products.length) {
                        container.innerHTML = '<div class="qsearch-empty">No se encontraron productos.</div>';
                        return;
                    }
                    container.innerHTML = products.map(p => `
                        <div class="qsearch-result-item" onclick="QuoteSearch.select(${JSON.stringify(p).replace(/"/g, '&quot;')})">
                            ${p.cover_image_url
                                ? `<img src="${p.cover_image_url}" class="qsearch-result-img" onerror="this.style.display='none'">`
                                : `<div class="qsearch-result-img-placeholder"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>`
                            }
                            <div class="qsearch-result-info">
                                <div class="qsearch-result-name">${p.name}</div>
                                <div class="qsearch-result-sku">SKU: ${p.sku || '—'} · Stock: ${p.stock}</div>
                            </div>
                            <div class="qsearch-result-price">$${parseFloat(p.price).toFixed(2)}</div>
                        </div>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('searchResults').innerHTML = '<div class="qsearch-empty">Error al buscar productos.</div>';
                });
        },
        select: function(product) {
            QuoteForm.addProductRow(product);
            closeProductSearch();
        }
    };
})();
</script>
@endpush
