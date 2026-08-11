@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css'];
    $customer = auth('customer')->user();
    $estadosMexico = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
    ];
    $requiresInvoiceOld = old('requires_invoice');
    $usoCfdiOldMatch = collect($usoCfdiOptions)->firstWhere('value', old('uso_cfdi'));
    $usoCfdiOldLabel = $usoCfdiOldMatch['label'] ?? '';
    $regimenFiscalOldMatch = collect($regimenFiscalOptions)->firstWhere('value', old('regimen_fiscal'));
    $regimenFiscalOldLabel = $regimenFiscalOldMatch['label'] ?? '';
@endphp

@section('title', 'Envío — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <h1 class="checkout-title">Finalizar pedido</h1>

    <div class="checkout-steps">
        <span class="checkout-steps__item is-done"><span class="checkout-steps__dot">✓</span><span class="checkout-steps__label">Carrito</span></span>
        <span class="checkout-steps__item is-active"><span class="checkout-steps__dot">2</span><span class="checkout-steps__label">Envío</span></span>
        <span class="checkout-steps__item"><span class="checkout-steps__dot">3</span><span class="checkout-steps__label">Pago</span></span>
    </div>

    @if ($errors->any())
        <div class="checkout-alert checkout-alert--error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="checkout-layout">
        <div>
            <form method="POST" action="{{ route('checkout.shipping.store') }}" id="shippingForm" enctype="multipart/form-data">
                @csrf
                <div class="checkout-form">
                    <div class="checkout-form__head">
                        <div class="checkout-form__head-title">Datos de envío</div>
                        <div class="checkout-form__badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                            Datos protegidos
                        </div>
                    </div>

                    <div class="checkout-form__section-label">Contacto</div>
                    <div class="checkout-form__grid">
                        <div class="checkout-form__span2">
                            <input type="text" name="contact_name" maxlength="150" required placeholder="Nombre completo"
                                value="{{ old('contact_name', $prefill->recipient_name ?? ($customer ? trim($customer->first_name . ' ' . $customer->last_name) : '')) }}">
                        </div>
                        <input type="email" name="contact_email" maxlength="150" required placeholder="Correo electrónico"
                            value="{{ old('contact_email', $customer->email ?? '') }}">
                        <input type="tel" name="contact_phone" maxlength="30" required placeholder="Teléfono"
                            value="{{ old('contact_phone', $prefill->phone ?? ($customer->phone ?? '')) }}">
                    </div>

                    <div class="checkout-form__section-label">Dirección de entrega</div>
                    <div class="checkout-form__grid checkout-form__grid--tight">
                        <div class="checkout-form__span2">
                            <input type="text" name="shipping_postal_code" maxlength="20" required placeholder="Código postal"
                                value="{{ old('shipping_postal_code', $prefill->postal_code ?? '') }}">
                        </div>

                        <div class="checkout-form__combo checkout-form__span2" data-combo="estado">
                            <input type="text" name="shipping_state" maxlength="100" required placeholder="Buscar estado..." autocomplete="off"
                                value="{{ old('shipping_state', $prefill->state ?? '') }}" data-combo-input>
                            <div class="checkout-form__combo-list" data-combo-list>
                                @foreach ($estadosMexico as $estado)
                                    <div class="checkout-form__combo-option" data-value="{{ $estado }}">{{ $estado }}</div>
                                @endforeach
                            </div>
                        </div>

                        <input type="text" name="shipping_city" maxlength="100" required placeholder="Ciudad"
                            value="{{ old('shipping_city', $prefill->city ?? '') }}">

                        <input type="text" name="shipping_address_line2" maxlength="255" placeholder="Colonia, referencias (opcional)"
                            value="{{ old('shipping_address_line2', $prefill->address_line2 ?? '') }}">

                        <div class="checkout-form__span2">
                            <input type="text" name="shipping_address_line1" maxlength="255" required placeholder="Calle y número"
                                value="{{ old('shipping_address_line1', $prefill->address_line1 ?? '') }}">
                        </div>
                    </div>

                    <label class="checkout-form__checkbox checkout-form__checkbox--plain">
                        <input type="checkbox" name="requires_invoice" id="requiresInvoice" value="1" {{ $requiresInvoiceOld ? 'checked' : '' }}>
                        <span>¿Requiere factura?</span>
                    </label>

                    <div id="invoiceFields" class="checkout-form__grid checkout-form__grid--tight checkout-form__invoice-fields{{ $requiresInvoiceOld ? '' : ' is-hidden' }}">
                        <input type="text" name="rfc" maxlength="13" placeholder="RFC" style="text-transform:uppercase;"
                            value="{{ old('rfc') }}">

                        <div class="checkout-form__combo" data-combo="usoCfdi">
                            <input type="text" placeholder="Buscar uso de CFDI..." autocomplete="off" data-combo-input
                                value="{{ $usoCfdiOldLabel }}">
                            <input type="hidden" name="uso_cfdi" value="{{ old('uso_cfdi') }}" data-combo-hidden>
                            <div class="checkout-form__combo-list" data-combo-list>
                                @foreach ($usoCfdiOptions as $opt)
                                    <div class="checkout-form__combo-option" data-value="{{ $opt['value'] }}" data-label="{{ $opt['label'] }}">{{ $opt['label'] }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="checkout-form__span2">
                            <input type="text" name="razon_social" maxlength="150" placeholder="Razón social"
                                value="{{ old('razon_social') }}">
                        </div>

                        <div class="checkout-form__combo checkout-form__span2" data-combo="regimenFiscal">
                            <input type="text" placeholder="Buscar régimen fiscal..." autocomplete="off" data-combo-input
                                value="{{ $regimenFiscalOldLabel }}">
                            <input type="hidden" name="regimen_fiscal" value="{{ old('regimen_fiscal') }}" data-combo-hidden>
                            <div class="checkout-form__combo-list" data-combo-list>
                                @foreach ($regimenFiscalOptions as $opt)
                                    <div class="checkout-form__combo-option" data-value="{{ $opt['value'] }}" data-label="{{ $opt['label'] }}">{{ $opt['label'] }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="checkout-form__span2">
                            <input type="text" name="cp_fiscal" maxlength="10" placeholder="Código postal fiscal"
                                value="{{ old('cp_fiscal') }}">
                        </div>

                        <div class="checkout-form__span2">
                            <label class="checkout-form__file-label">Constancia de Situación Fiscal (SAT)</label>
                            <input type="file" name="tax_certificate" accept=".pdf,.jpg,.jpeg,.png" class="checkout-form__file">
                            <div class="checkout-form__file-hint">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                PDF, JPG o PNG. Máx. 5MB. Se usa solo para timbrar tu factura.
                            </div>
                        </div>
                    </div>

                    <label class="checkout-form__checkbox">
                        <input type="checkbox" name="terms_accepted" value="1" {{ old('terms_accepted') ? 'checked' : '' }} required>
                        <span>Acepto los <a href="{{ $termsUrl }}" target="_blank" rel="noopener">Términos y Condiciones</a></span>
                    </label>
                </div>

                <div class="checkout-actions">
                    <a href="{{ route('checkout.index') }}" class="checkout-back">Atrás</a>
                    <button type="submit" class="checkout-submit">Continuar a pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    // Toggle del bloque de factura.
    var invoiceCheckbox = document.getElementById('requiresInvoice');
    var invoiceFields = document.getElementById('invoiceFields');
    if (invoiceCheckbox && invoiceFields) {
        invoiceCheckbox.addEventListener('change', function () {
            invoiceFields.classList.toggle('is-hidden', !invoiceCheckbox.checked);
        });
    }

    // Combos buscables genéricos (Estado, Uso de CFDI, Régimen Fiscal).
    document.querySelectorAll('[data-combo]').forEach(function (wrap) {
        var input = wrap.querySelector('[data-combo-input]');
        var hidden = wrap.querySelector('[data-combo-hidden]');
        var list = wrap.querySelector('[data-combo-list]');
        if (!input || !list) return;

        var options = Array.prototype.slice.call(list.querySelectorAll('.checkout-form__combo-option'));

        function filter() {
            var q = input.value.trim().toLowerCase();
            options.forEach(function (opt) {
                opt.style.display = opt.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        }

        input.addEventListener('focus', function () { filter(); wrap.classList.add('is-open'); });
        input.addEventListener('input', function () {
            filter();
            if (hidden) hidden.value = ''; // el usuario está escribiendo, invalida la selección previa hasta que elija de nuevo
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function () {
                input.value = opt.dataset.label || opt.dataset.value;
                if (hidden) hidden.value = opt.dataset.value;
                wrap.classList.remove('is-open');
            });
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) wrap.classList.remove('is-open');
        });
    });
})();
</script>
@endsection
