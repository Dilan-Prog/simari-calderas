@extends('admin.layouts.master')
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-finance.css'])
@endpush
@section('title')
    {{ $doc ? 'Editar ' . $doc->number : 'Nueva ' . $cfg['singular'] }} - Admin
@endsection
@section('content')
@php
    $r = $cfg['route'];
    $pc = $cfg['party_col'];
    $isRec = $cfg['kind'] === 'receivable';
    $v = fn ($key, $default = null) => old($key, $doc?->{$key} ?? $default);
    $money = fn ($key) => old($key, $doc ? number_format((float) $doc->{$key}, 2, '.', '') : null);
@endphp
<div class="erp-page fin-page fin-docs-form">

    <x-erp.page-header
        :title="$doc ? 'Editar ' . $doc->number : 'Nueva ' . $cfg['singular']"
        desc="El saldo y el estatus se recalculan solos con los pagos aplicados."
        :crumbs="[['Finanzas', route('admin.finance.dashboard')], [$cfg['title'], route($r . '.index')], [$doc ? $doc->number : 'Nueva', null]]" />

    <form method="POST" action="{{ $doc ? route($r . '.update', $doc->id) : route($r . '.store') }}" class="erp-card fin-form-card">
        @csrf
        @if ($doc) @method('PUT') @endif

        <div class="erp-card-body">
        <div class="erp-form-grid">
            <label class="erp-field fin-full">{{ $cfg['party_label'] }}
                <select name="{{ $pc }}" class="erp-select" required>
                    <option value="">Selecciona…</option>
                    @foreach ($parties as $id => $name)
                        <option value="{{ $id }}" @selected((int) old($pc, $doc?->{$pc} ?? ($prefill[$pc] ?? 0)) === $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="erp-field">Fecha de emisión
                <input type="date" name="issue_date" class="erp-input" value="{{ old('issue_date', $doc?->issue_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            </label>
            <label class="erp-field">Días de crédito
                <input type="number" name="credit_days" class="erp-input" min="0" max="3650" value="{{ $v('credit_days', 0) }}">
                <small>El vencimiento = emisión + días.</small>
            </label>
            <label class="erp-field">Vencimiento (opcional)
                <input type="date" name="due_date" class="erp-input" value="{{ old('due_date') }}">
                <small>Si lo capturas, se usa en lugar de los días.{{ $doc ? ' Actual: ' . $doc->due_date->format('d/m/Y') : '' }}</small>
            </label>

            <label class="erp-field">Moneda
                <select name="currency" class="erp-select" id="finCurrency">
                    <option value="MXN" @selected($v('currency', 'MXN') === 'MXN')>MXN</option>
                    <option value="USD" @selected($v('currency', 'MXN') === 'USD')>USD</option>
                </select>
            </label>
            <label id="finRateWrap" class="erp-field">Tipo de cambio (MXN por USD)
                <input type="number" step="0.0001" min="0.0001" name="exchange_rate" id="finRate" class="erp-input" value="{{ $v('exchange_rate', 1) }}">
            </label>

            <label class="erp-field">Subtotal
                <input type="number" step="0.01" min="0" name="subtotal" id="finSubtotal" class="erp-input" value="{{ $money('subtotal') }}" required>
            </label>
            <label class="erp-field">Impuestos (IVA)
                <input type="number" step="0.01" min="0" name="tax_total" id="finTax" class="erp-input" value="{{ $money('tax_total') ?? '0.00' }}">
            </label>
            <label class="erp-field">Total
                <input type="number" step="0.01" min="0" name="total" id="finTotal" class="erp-input" value="{{ $money('total') }}">
                <small>Vacío = subtotal + impuestos.</small>
            </label>

            @if ($isRec)
                @php
                    $currentOrigin = old('source_document', ($doc && $doc->source_type && $doc->source_id) ? $doc->source_type . ':' . $doc->source_id : '');
                @endphp
                <label class="erp-field fin-full">Documento de origen
                    <select name="source_document" class="erp-select" data-fin-party-filter>
                        <option value="">Ninguno (captura manual)</option>
                        @foreach ($origins as $group)
                            @if (count($group['items']))
                                <optgroup label="{{ $group['label'] }}">
                                    @foreach ($group['items'] as $item)
                                        <option value="{{ $item['value'] }}" data-party="{{ $item['party'] }}" @selected($currentOrigin === $item['value'])>{{ $item['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <small>El pedido, cotización, proyecto u orden de tienda del que viene este cobro. Al elegir un cliente solo se muestran sus documentos.</small>
                    @error('source_document')<small style="color:#b91c1c">{{ $message }}</small>@enderror
                </label>
                <label class="erp-field fin-full">Proyecto (opcional)
                    <select name="project_id" class="erp-select" data-fin-party-filter>
                        <option value="">Sin proyecto</option>
                        @foreach ($projects as $item)
                            <option value="{{ $item['value'] }}" data-party="{{ $item['party'] }}" @selected((int) old('project_id', $doc?->project_id ?? 0) === (int) $item['value'])>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                    <small>Sirve para saber cuánto se cobra por cada proyecto.</small>
                </label>
            @else
                <label class="erp-field fin-full">Orden de compra (opcional)
                    <select name="purchase_order_id" class="erp-select" data-fin-party-filter>
                        <option value="">Sin orden de compra</option>
                        @foreach ($purchaseOrders as $item)
                            <option value="{{ $item['value'] }}" data-party="{{ $item['party'] }}" @selected((int) old('purchase_order_id', $doc?->purchase_order_id ?? 0) === (int) $item['value'])>{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                    <small>Al elegir un proveedor solo se muestran sus órdenes de compra.</small>
                </label>
                <label class="erp-field">Folio de factura del proveedor
                    <input type="text" maxlength="60" name="supplier_invoice" class="erp-input" value="{{ $v('supplier_invoice') }}">
                </label>
            @endif

            <label class="erp-field fin-full">Notas
                <textarea name="notes" rows="3" class="erp-input">{{ $v('notes') }}</textarea>
            </label>
        </div>

        <div class="erp-form-actions">
            <a href="{{ $doc ? route($r . '.show', $doc->id) : route($r . '.index') }}" class="erp-btn">Cancelar</a>
            <button type="submit" class="erp-btn erp-btn--primary">{{ $doc ? 'Guardar cambios' : 'Crear ' . $cfg['singular'] }}</button>
        </div>
        </div>
    </form>

</div>
@endsection
@push('scripts')
    @vite(['resources/js/admin/erp-finance.js'])
@endpush
@push('scripts')
    <script>
        /* Selects de documento / proyecto / orden de compra: al elegir cliente o proveedor solo se muestran sus documentos. */
        (function () {
            var party = document.querySelector('select[name="{{ $pc }}"]');
            var selects = Array.prototype.slice.call(document.querySelectorAll('select[data-fin-party-filter]'));
            if (!party || !selects.length) return;

            selects.forEach(function (sel) {
                var all = Array.prototype.slice.call(sel.children).map(function (n) { return n.cloneNode(true); });
                var apply = function (initial) {
                    var p = party.value, keep = sel.value;
                    var cur = sel.options[sel.selectedIndex];
                    /* Al cambiar de cliente/proveedor, un documento de otro se deselecciona. */
                    if (initial !== true && cur && cur.getAttribute('data-party') && p && cur.getAttribute('data-party') !== p) keep = '';
                    var matches = function (o) {
                        var dp = o.getAttribute('data-party');
                        return !p || !dp || dp === p || o.value === keep;
                    };
                    sel.innerHTML = '';
                    all.forEach(function (node) {
                        if (node.tagName === 'OPTGROUP') {
                            var g = node.cloneNode(false);
                            Array.prototype.slice.call(node.children).forEach(function (o) { if (matches(o)) g.appendChild(o.cloneNode(true)); });
                            if (g.children.length) sel.appendChild(g);
                        } else if (matches(node)) {
                            sel.appendChild(node.cloneNode(true));
                        }
                    });
                    sel.value = keep;
                    if (sel.value !== keep) sel.value = '';
                };
                party.addEventListener('change', function () { apply(false); });
                apply(true);
            });
        })();
    </script>
@endpush

