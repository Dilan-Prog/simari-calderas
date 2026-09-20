@extends('frontend.shop.layouts.master')

@php
    $shopVite = [
        'resources/css/frontend/shop/checkout.css',
        'resources/js/frontend/shop/mercadopago-checkout.js',
        'resources/js/frontend/shop/checkout-payment.js',
        'resources/js/frontend/shop/checkout-accordion.js',
    ];

    $requiresInvoiceOld = old('requires_invoice');
    $usoCfdiOldMatch = collect($usoCfdiOptions)->firstWhere('value', old('uso_cfdi'));
    $usoCfdiOldLabel = $usoCfdiOldMatch['label'] ?? '';
    $regimenFiscalOldMatch = collect($regimenFiscalOptions)->firstWhere('value', old('regimen_fiscal'));
    $regimenFiscalOldLabel = $regimenFiscalOldMatch['label'] ?? '';

    // Detecta cuál (si acaso) de los métodos ya cargados es Mercado Pago --
    // mismo criterio que CheckoutController::confirm() usa para ramificar.
    $mercadoPagoMethodId = optional($paymentMethods->first(function ($m) {
        return in_array($m->type, ['pasarela', 'digital'], true) && ($m->details['processor'] ?? null) === 'mercadopago';
    }))->id;
@endphp

@section('title', 'Finalizar pedido — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <h1 class="checkout-title">Finalizar pedido</h1>

    @if (session('error'))
        <div class="checkout-alert checkout-alert--error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="checkout-alert checkout-alert--error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($cart->items->isEmpty())
        <div class="checkout-empty">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.6"><path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 8H6"/><circle cx="9" cy="21" r="1.4"/><circle cx="18" cy="21" r="1.4"/></svg>
            <div class="checkout-empty__text">Tu carrito está vacío.</div>
            <a href="{{ route('catalog.index') }}" class="checkout-empty__link">Explorar catálogo</a>
        </div>
    @else
        <div class="checkout-layout">
            <div id="checkoutAccordion"
                data-shipping-completed="{{ $shippingCompleted ? '1' : '0' }}"
                data-capture-contact-url="{{ route('checkout.capture-contact') }}">

                {{-- ============ SECCIÓN 1: CARRITO ============ --}}
                <div class="checkout-accordion-section" data-accordion-section="cart">
                    <button type="button" class="checkout-accordion-section__header" data-accordion-header>
                        <span class="checkout-accordion-section__num">1</span>
                        <span class="checkout-accordion-section__title">Carrito</span>
                        <span class="checkout-accordion-section__status" data-accordion-status></span>
                    </button>

                    <div class="checkout-accordion-section__body" data-accordion-body>
                        <div class="checkout-cart-list">
                            @foreach ($cart->items as $item)
                                <div class="checkout-cart-item">
                                    <div class="checkout-cart-item__img">
                                        @if ($item->product && $item->product->images->first())
                                            <img src="{{ $item->product->images->first()->url }}" alt="{{ $item->product->name }}">
                                        @endif
                                    </div>
                                    <div class="checkout-cart-item__info">
                                        @if ($item->product)
                                            <a href="{{ route('product.show', $item->product->slug) }}" class="checkout-cart-item__name">{{ $item->product->resolveVariables($item->product->name) }}</a>
                                            <div class="checkout-cart-item__sku">SKU {{ $item->product->sku }}</div>
                                            <div class="checkout-cart-item__warranty">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                Garantía de fábrica incluida
                                            </div>
                                        @else
                                            <span class="checkout-cart-item__name">Producto no disponible</span>
                                        @endif
                                        <form method="POST" action="{{ route('cart.remove') }}" class="checkout-cart-item__remove-form">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                            <button type="submit" class="checkout-cart-item__remove">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m2 0-1 13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                Eliminar
                                            </button>
                                            <span class="checkout-cart-item__qty-spinner" aria-hidden="true"></span>
                                        </form>
                                    </div>
                                    <div class="checkout-cart-item__actions">
                                        <form method="POST" action="{{ route('cart.update') }}" class="checkout-cart-item__qty">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                            <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" {{ $item->quantity <= 1 ? 'disabled' : '' }}>−</button>
                                            <span class="checkout-cart-item__qty-value">{{ $item->quantity }}</span>
                                            <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}">+</button>
                                            <span class="checkout-cart-item__qty-spinner" aria-hidden="true"></span>
                                        </form>
                                        <div class="checkout-cart-item__total">${{ number_format($item->lineTotal(), 2) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="checkout-trust-row">
                            <div class="checkout-trust-row__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                Envío gratis y asegurado
                            </div>
                            <div class="checkout-trust-row__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>
                                Compra 100% protegida
                            </div>
                            <div class="checkout-trust-row__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M11 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM23 21v-2a4 4 0 0 0-3-3.87M17 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round"/></svg>
                                Soporte técnico especializado
                            </div>
                        </div>

                        <div class="checkout-actions">
                            <button type="button" class="checkout-submit" data-accordion-continue="shipping">Continuar a envío</button>
                        </div>
                    </div>
                </div>

                {{-- ============ SECCIÓN 2: DIRECCIÓN DE ENVÍO ============ --}}
                <div class="checkout-accordion-section" data-accordion-section="shipping">
                    <button type="button" class="checkout-accordion-section__header" data-accordion-header>
                        <span class="checkout-accordion-section__num">2</span>
                        <span class="checkout-accordion-section__title">Dirección de envío</span>
                        <span class="checkout-accordion-section__status" data-accordion-status></span>
                    </button>

                    <div class="checkout-accordion-section__body" data-accordion-body>
                        @if ($customer && $addresses->isNotEmpty())
                            <div class="checkout-form" id="addressPickerCard">
                                <div class="checkout-form__head">
                                    <div class="checkout-form__head-title">Dirección de envío</div>
                                </div>

                                <div class="address-picker-grid">
                                    @foreach ($addresses as $addr)
                                        <div class="address-picker-card" data-address-card
                                            data-id="{{ $addr->id }}"
                                            data-recipient="{{ $addr->recipient_name }}"
                                            data-phone="{{ $addr->phone }}"
                                            data-postal="{{ $addr->postal_code }}"
                                            data-state="{{ $addr->state }}"
                                            data-city="{{ $addr->city }}"
                                            data-line2="{{ $addr->address_line2 }}"
                                            data-line1="{{ $addr->address_line1 }}"
                                            data-reference="{{ $addr->reference }}"
                                            data-label="{{ $addr->label }}"
                                            data-is-default="{{ $addr->is_default ? '1' : '0' }}"
                                            data-update-url="{{ route('shop.addresses.update', $addr) }}">
                                            <input type="radio" name="address_picker" value="{{ $addr->id }}" data-address-radio
                                                {{ $prefill && $prefill->id === $addr->id ? 'checked' : '' }}>
                                            <div class="address-picker-card__body">
                                                <div class="address-picker-card__name">
                                                    {{ $addr->recipient_name }}
                                                    @if ($addr->label)<span class="address-picker-card__label">{{ $addr->label }}</span>@endif
                                                </div>
                                                <div class="address-picker-card__lines">
                                                    {{ $addr->address_line1 }}<br>
                                                    @if ($addr->address_line2){{ $addr->address_line2 }}, @endif{{ $addr->city }}, {{ $addr->state }}<br>
                                                    C.P. {{ $addr->postal_code }}
                                                </div>
                                                <div class="address-picker-card__phone">{{ $addr->phone }}</div>
                                            </div>
                                            <div class="address-picker-card__actions">
                                                <button type="button" class="address-picker-card__btn address-picker-card__btn--edit" data-address-edit>
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    Editar
                                                </button>
                                                <button type="button" class="address-picker-card__btn address-picker-card__btn--delete" data-address-delete
                                                    data-delete-url="{{ route('shop.addresses.destroy', $addr) }}"
                                                    data-delete-label="{{ $addr->label ?: $addr->recipient_name }}">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="address-picker-add" id="addNewAddressBtn">+ Agregar nueva dirección</button>
                            </div>
                        @endif

                        @if ($customer)
                            {{-- Modal real (vanilla JS, sin Alpine) para Agregar/Editar
                                 dirección guardada -- nunca toca el formulario principal
                                 de abajo, que solo confirma los datos de ESTE pedido. --}}
                            <div class="checkout-address-modal" id="addressModal" style="display:none;">
                                <div class="checkout-address-modal__backdrop" data-address-modal-close></div>
                                <div class="checkout-address-modal__box">
                                    <div class="checkout-address-modal__header">
                                        <div class="checkout-address-modal__title" id="addressModalTitle">Agregar dirección</div>
                                        <button type="button" class="checkout-address-modal__close" data-address-modal-close>&times;</button>
                                    </div>
                                    <form method="POST" id="addressModalForm" action="{{ route('shop.addresses.store') }}">
                                        @csrf
                                        <input type="hidden" name="_method" id="addressModalMethod" value="POST">
                                        <div class="checkout-form__grid">
                                            <div class="checkout-form__span2 checkout-form__field">
                                                <label for="amLabel">Etiqueta (opcional)</label>
                                                <input type="text" id="amLabel" name="label" maxlength="100" placeholder="Ej. Casa, Oficina">
                                            </div>
                                            <div class="checkout-form__field">
                                                <label for="amRecipient">Nombre de quien recibe</label>
                                                <input type="text" id="amRecipient" name="recipient_name" maxlength="150" required placeholder="Nombre completo">
                                            </div>
                                            <div class="checkout-form__field">
                                                <label for="amPhone">Teléfono</label>
                                                <input type="tel" id="amPhone" name="phone" maxlength="30" required placeholder="10 dígitos">
                                            </div>
                                        </div>
                                        <div class="checkout-form__grid checkout-form__grid--tight">
                                            <div class="checkout-form__span2 checkout-form__field">
                                                <label for="amPostal">Código postal</label>
                                                <input type="text" id="amPostal" name="postal_code" maxlength="20" required placeholder="Código postal">
                                            </div>
                                            <div class="checkout-form__field">
                                                <label for="amState">Estado</label>
                                                <select id="amState" name="state" required>
                                                    <option value="">Selecciona un estado</option>
                                                    @foreach ($estadosMexico as $estado)
                                                        <option value="{{ $estado }}">{{ $estado }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="checkout-form__field">
                                                <label for="amCity">Ciudad</label>
                                                <input type="text" id="amCity" name="city" maxlength="100" required placeholder="Ciudad">
                                            </div>
                                            <div class="checkout-form__span2 checkout-form__field">
                                                <label for="amLine2">Colonia</label>
                                                <input type="text" id="amLine2" name="address_line2" maxlength="255" required placeholder="Colonia">
                                            </div>
                                            <div class="checkout-form__span2 checkout-form__field">
                                                <label for="amLine1">Calle y número</label>
                                                <input type="text" id="amLine1" name="address_line1" maxlength="255" required placeholder="Calle y número">
                                            </div>
                                            <div class="checkout-form__span2 checkout-form__field">
                                                <label for="amReference">Referencias de entrega (opcional)</label>
                                                <input type="text" id="amReference" name="reference" maxlength="255" placeholder="Ej. Entre calle X y Y, portón negro">
                                            </div>
                                        </div>
                                        <label class="checkout-form__checkbox checkout-form__checkbox--plain">
                                            <input type="checkbox" id="amDefault" name="is_default" value="1">
                                            <span>Establecer como predeterminada</span>
                                        </label>
                                        <div class="checkout-address-modal__actions">
                                            <button type="button" class="checkout-back" data-address-modal-close>Cancelar</button>
                                            <button type="submit" class="checkout-submit">Guardar dirección</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Modal de confirmación real para "Eliminar" -- reemplaza el
                                 confirm() nativo del navegador (aparecía en inglés y con
                                 estilo genérico del sistema operativo). --}}
                            <div class="checkout-address-modal" id="addressDeleteModal" style="display:none;">
                                <div class="checkout-address-modal__backdrop" data-address-delete-modal-close></div>
                                <div class="checkout-address-modal__box checkout-address-modal__box--sm">
                                    <div class="checkout-address-modal__icon checkout-address-modal__icon--danger">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <div class="checkout-address-modal__title" style="text-align:center;">¿Eliminar esta dirección?</div>
                                    <p class="checkout-address-modal__text" id="addressDeleteModalText"></p>
                                    <form method="POST" id="addressDeleteModalForm">
                                        @csrf
                                        @method('DELETE')
                                        <div class="checkout-address-modal__actions">
                                            <button type="button" class="checkout-back" data-address-delete-modal-close>Cancelar</button>
                                            <button type="submit" class="checkout-submit checkout-submit--danger">Eliminar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="checkout-alert checkout-alert--error" id="shippingInlineError" style="display:none;"></div>

                        <form method="POST" action="{{ route('checkout.shipping.store') }}" id="shippingForm" enctype="multipart/form-data" data-accordion-form>
                            @csrf
                            <div class="checkout-form">
                                <div class="checkout-form__head">
                                    <div class="checkout-form__head-title">{{ $customer && $addresses->isNotEmpty() ? 'Confirmar datos de envío' : 'Datos de envío' }}</div>
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
                                        <input type="text" name="shipping_postal_code" id="shippingPostalCode" maxlength="20" required placeholder="Código postal"
                                            value="{{ old('shipping_postal_code', $prefill->postal_code ?? '') }}">
                                        <div class="checkout-form__hint" id="shippingCpHint">Escribe tu código postal para habilitar el resto de la dirección.</div>
                                    </div>

                                    <div class="checkout-form__combo checkout-form__span2" data-combo="estado">
                                        <input type="text" name="shipping_state" maxlength="100" required placeholder="Buscar estado..." autocomplete="off"
                                            value="{{ old('shipping_state', $prefill->state ?? '') }}" data-combo-input data-cp-gated>
                                        <div class="checkout-form__combo-list" data-combo-list>
                                            @foreach ($estadosMexico as $estado)
                                                <div class="checkout-form__combo-option" data-value="{{ $estado }}">{{ $estado }}</div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <input type="text" name="shipping_city" maxlength="100" required placeholder="Ciudad" data-cp-gated
                                        value="{{ old('shipping_city', $prefill->city ?? '') }}">

                                    <input type="text" name="shipping_address_line2" maxlength="255" required placeholder="Colonia" data-cp-gated
                                        value="{{ old('shipping_address_line2', $prefill->address_line2 ?? '') }}">

                                    <div class="checkout-form__span2">
                                        <input type="text" name="shipping_address_line1" maxlength="255" required placeholder="Calle y número" data-cp-gated
                                            value="{{ old('shipping_address_line1', $prefill->address_line1 ?? '') }}">
                                    </div>

                                    <div class="checkout-form__span2">
                                        <input type="text" name="shipping_reference" maxlength="255" placeholder="Referencias de entrega (opcional)" data-cp-gated
                                            value="{{ old('shipping_reference', $prefill->reference ?? '') }}">
                                    </div>
                                </div>

                                <div class="checkout-delivery-callout">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="8" width="15" height="9" rx="1"/><path d="M16 11h3l3 3v3h-6z"/><circle cx="6" cy="19" r="1.5"/><circle cx="17.5" cy="19" r="1.5"/></svg>
                                    <div>
                                        <div class="checkout-delivery-callout__title">Entrega estimada: {{ $deliveryEstimateLabel }}</div>
                                        <div class="checkout-delivery-callout__text">Envío rastreable con seguro incluido a toda la República Mexicana.</div>
                                    </div>
                                </div>

                                <div class="checkout-form__divider"></div>

                                <div class="checkout-invoice-card">
                                    <label class="checkout-invoice-card__header" for="requiresInvoice">
                                        <input type="checkbox" name="requires_invoice" id="requiresInvoice" value="1" {{ $requiresInvoiceOld ? 'checked' : '' }}>
                                        <span>
                                            <span class="checkout-invoice-card__title">Necesito factura (CFDI 4.0)</span>
                                            <span class="checkout-invoice-card__desc">Actívalo sólo si requieres comprobante fiscal. Podemos timbrar dentro del mes en curso.</span>
                                        </span>
                                    </label>

                                <div id="invoiceFields" class="checkout-invoice-card__body checkout-form__grid checkout-form__grid--tight checkout-form__invoice-fields{{ $requiresInvoiceOld ? '' : ' is-hidden' }}">
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
                                </div>{{-- /.checkout-invoice-card --}}

                                @if ($customer && $addresses->isEmpty())
                                    <label class="checkout-form__checkbox checkout-form__checkbox--plain">
                                        <input type="checkbox" name="save_address" value="1" checked>
                                        <span>Guardar esta dirección para la próxima vez</span>
                                    </label>
                                @endif

                                <div class="checkout-form__trust-line">
                                    <div class="checkout-form__badge">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                        Nunca compartimos tus datos con terceros
                                    </div>
                                    <div class="checkout-form__badge">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Facturación CFDI 4.0 disponible
                                    </div>
                                </div>
                            </div>

                            <div class="checkout-actions">
                                <button type="submit" class="checkout-submit" id="shippingSubmitBtn">Continuar a método de pago</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============ SECCIÓN 3: MÉTODO DE PAGO ============ --}}
                <div class="checkout-accordion-section" data-accordion-section="payment">
                    <button type="button" class="checkout-accordion-section__header" data-accordion-header>
                        <span class="checkout-accordion-section__num">3</span>
                        <span class="checkout-accordion-section__title">Método de pago</span>
                        <span class="checkout-accordion-section__status" data-accordion-status></span>
                    </button>

                    <div class="checkout-accordion-section__body" data-accordion-body>
                        <div class="checkout-payment">
                            <div class="checkout-payment__head">
                                <div class="checkout-payment__head-title">Método de pago</div>
                                <div class="checkout-form__badge">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                    Conexión encriptada SSL
                                </div>
                            </div>

                            <div class="checkout-alert checkout-alert--error" id="mpInlineError" style="display:none;"></div>

                            @if ($paymentMethods->isEmpty())
                                <div class="checkout-alert checkout-alert--error">
                                    No hay métodos de pago disponibles en este momento. Por favor contáctanos para completar tu pedido.
                                </div>
                            @else
                                {{-- El <form> queda deliberadamente "vacío" (solo CSRF + el
                                     flag de sub-flujo) -- todo lo demás (radios, botón de
                                     envío) vive AFUERA de esta etiqueta, asociado vía el
                                     atributo form="paymentForm" (mismo patrón que ya usaba
                                     #paymentSubmitBtn). Es necesario: mercadopago-checkout.js
                                     inyecta su propio <form> por cada "cobro" de tarjeta
                                     (CardForm exige un <form> real, no un <div>, para poder
                                     enganchar su evento submit) dentro de #mpCardContainer --
                                     un <form> anidado dentro de otro es HTML inválido y los
                                     navegadores lo descartan en silencio (confirmado en vivo:
                                     "Could not find HTML element for provided id"). Sacar los
                                     radios de aquí evita ese anidamiento por completo. --}}
                                <form method="POST" action="{{ route('checkout.confirm') }}" id="paymentForm" data-mp-method-id="{{ $mercadoPagoMethodId }}">
                                    @csrf
                                    <input type="hidden" name="mp_payment_flow" id="mpPaymentFlow" value="card">
                                </form>

                                <div id="paymentMethodsWrap">
                                    <div class="checkout-payment-methods">
                                        @foreach ($paymentMethods as $method)
                                            @if ($mercadoPagoMethodId && $method->id === $mercadoPagoMethodId)
                                                @php
                                                    // Desglose de MSI mostrado bajo la opción "Mercado Pago"
                                                    // -- ahí sí es honesto porque es la propia simulación de
                                                    // Checkout Pro; bajo "Tarjeta" no se replica (dependería
                                                    // de elegibilidad real del BIN, que resuelve el <select>
                                                    // de cuotas del CardForm, no un cálculo estático).
                                                    $msiBreakdown = collect([3, 6, 9, 12])->map(fn ($months) => [
                                                        'months' => $months,
                                                        'amount' => $summary['total'] / $months,
                                                    ]);
                                                @endphp

                                                {{-- TARJETA: Mercado Pago Checkout API vía CardForm/Secure
                                                     Fields (mercadopago-checkout.js) -- número, vencimiento y
                                                     CVV quedan dentro de iframes seguros de MP, el resto del
                                                     formulario (nombre, layout, logos) es 100% nuestro. --}}
                                                <div class="checkout-payment-method-group">
                                                    <label class="checkout-payment-method checkout-payment-method--split">
                                                        <input type="radio" name="payment_method_id" form="paymentForm" value="{{ $method->id }}" {{ $loop->first ? 'checked' : '' }} required data-mp-radio data-mp-flow="card">
                                                        <div class="checkout-payment-method__main">
                                                            <div class="checkout-payment-method__name-row">
                                                                <div class="checkout-payment-method__name">Tarjeta de crédito o débito</div>
                                                                @if ($method->allows_invoicing)
                                                                    <span class="checkout-payment-method__badge">Factura CFDI disponible</span>
                                                                @endif
                                                            </div>
                                                            <div class="checkout-payment-method__description">Visa, Mastercard y American Express. Procesamiento con tecnología de Mercado Pago y autenticación 3-D Secure.</div>
                                                            <div class="checkout-payment-method__bullets">
                                                                <span class="mp-trust-badge">
                                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20" stroke-linecap="round"/></svg>
                                                                    Cargo inmediato, pedido liberado al instante
                                                                </span>
                                                                <span class="mp-trust-badge mp-trust-badge--pci">
                                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                                                    Certificado PCI-DSS
                                                                </span>
                                                            </div>
                                                            <div class="mp-secure-badge mp-secure-badge--inline">
                                                                @include('frontend.shop.partials.mercadopago-logo', ['class' => 'mp-secure-badge__logo', 'width' => 15, 'height' => 15])
                                                                Pago seguro con tecnología de <strong class="mp-secure-badge__brand">Mercado Pago</strong>
                                                            </div>
                                                        </div>
                                                        <div class="mp-brand-logos-row">
                                                            <span class="mp-brand-logo-box"><img src="{{ asset('images/payment-logos/visa.jpg') }}" alt="Visa"></span>
                                                            <span class="mp-brand-logo-box"><img src="{{ asset('images/payment-logos/mastercard.jpg') }}" alt="Mastercard"></span>
                                                            <span class="mp-brand-logo-box"><img src="{{ asset('images/payment-logos/amex.webp') }}" alt="American Express"></span>
                                                        </div>
                                                    </label>

                                                    <div class="mp-accordion-panel" id="mpCardPanel">
                                                        <div class="mp-accordion-panel__inner">
                                                            <div id="mpCardContainer"></div>
                                                            <div class="mp-secure-badge">
                                                                @include('frontend.shop.partials.mercadopago-logo', ['class' => 'mp-secure-badge__logo', 'width' => 16, 'height' => 16])
                                                                Pago seguro con tecnología de <strong class="mp-secure-badge__brand">Mercado Pago</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- MERCADO PAGO (Checkout Pro): Wallet/Efectivo/Transferencia/
                                                     Mercado Crédito -- 100% redirect al entorno hospedado de
                                                     MP (checkoutPro(), ya probado en vivo). Nunca comparte
                                                     formulario con la Tarjeta de arriba. --}}
                                                <div class="checkout-payment-method-group">
                                                    <label class="checkout-payment-method checkout-payment-method--split{{ $cartWouldSplitMsi ? ' checkout-payment-method--disabled' : '' }}">
                                                        <input type="radio" name="payment_method_id" form="paymentForm" value="{{ $method->id }}" required data-mp-radio data-mp-flow="checkout_pro" {{ $cartWouldSplitMsi ? 'disabled' : '' }}>
                                                        <div class="checkout-payment-method__main">
                                                            <div class="checkout-payment-method__name-row">
                                                                <div class="checkout-payment-method__name">Mercado Pago</div>
                                                                <span class="checkout-form__badge checkout-payment-method__badge mp-msi-badge">
                                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M8 3v4M16 3v4M3 10.5h18" stroke-linecap="round"/></svg>
                                                                    Hasta 12 meses sin intereses
                                                                </span>
                                                                @if ($method->allows_invoicing)
                                                                    <span class="checkout-payment-method__badge">Factura CFDI disponible</span>
                                                                @endif
                                                            </div>
                                                            <div class="checkout-payment-method__description">Paga con tarjeta, saldo en Mercado Pago o efectivo en tiendas. El pago se completa en la ventana segura de Mercado Pago.</div>
                                                            @if ($cartWouldSplitMsi)
                                                                <div class="checkout-payment-method__note">No disponible cuando tu carrito combina productos con y sin meses sin intereses — usa "Tarjeta de crédito o débito".</div>
                                                            @else
                                                                <div class="mp-msi-breakdown">
                                                                    @foreach ($msiBreakdown as $msi)
                                                                        <div class="mp-msi-breakdown__item">
                                                                            <div class="mp-msi-breakdown__months">{{ $msi['months'] }} pagos de</div>
                                                                            <div class="mp-msi-breakdown__amount">${{ number_format($msi['amount'], 2) }}</div>
                                                                            <div class="mp-msi-breakdown__label">sin intereses</div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                                <div class="mp-secure-badge mp-secure-badge--inline">
                                                                    @include('frontend.shop.partials.mercadopago-logo', ['class' => 'mp-secure-badge__logo', 'width' => 15, 'height' => 15])
                                                                    Pago seguro con tecnología de <strong class="mp-secure-badge__brand">Mercado Pago</strong>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        @include('frontend.shop.partials.mercadopago-logo', ['class' => 'checkout-payment-method__mp-logo', 'width' => 28, 'height' => 28])
                                                    </label>

                                                    @unless ($cartWouldSplitMsi)
                                                        <div class="mp-accordion-panel" id="mpCheckoutProPanel">
                                                            <div class="mp-accordion-panel__inner">
                                                                <div class="mp-checkoutpro-notice">
                                                                    <span class="mp-checkoutpro-notice__icon">
                                                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#00bcff" stroke-width="2.2"><path d="M14 4h6v6M20 4l-9 9" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 14v5a1.8 1.8 0 0 1-1.8 1.8H5.8A1.8 1.8 0 0 1 4 19V8.6A1.8 1.8 0 0 1 5.8 6.8h5" stroke-linecap="round"/></svg>
                                                                    </span>
                                                                    <div>
                                                                        <div class="mp-checkoutpro-notice__title">Se abrirá la ventana de Mercado Pago para procesar tu pago</div>
                                                                        <div class="mp-checkoutpro-notice__text">Al confirmar el pedido te llevamos al entorno seguro de Mercado Pago, donde eliges tarjeta, saldo o efectivo y, si aplica, tus meses sin intereses. Al terminar regresas automáticamente a Equiterm Industries con tu comprobante.</div>
                                                                        <div class="mp-checkoutpro-notice__footnote">
                                                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                                                            Equiterm Industries no recibe ni almacena los datos de tu tarjeta.
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endunless
                                                </div>
                                            @continue
                                            @endif
                                            <div class="checkout-payment-method-group">
                                            <label class="checkout-payment-method{{ $method->type === 'transferencia' ? ' checkout-payment-method--split' : '' }}">
                                                <input type="radio" name="payment_method_id" form="paymentForm" value="{{ $method->id }}" {{ $loop->first ? 'checked' : '' }} required>
                                                <div class="checkout-payment-method__main">
                                                    <div class="checkout-payment-method__name-row">
                                                        <div class="checkout-payment-method__name">{{ $method->name }}</div>

                                                        @if ($method->type === 'transferencia')
                                                            <span class="checkout-payment-method__badge checkout-payment-method__badge--success">Sin comisión</span>
                                                        @endif

                                                        @if ($method->allows_invoicing)
                                                            <span class="checkout-payment-method__badge">Factura CFDI disponible</span>
                                                        @endif
                                                    </div>

                                                    @if ($method->description)
                                                        <div class="checkout-payment-method__description">{{ $method->description }}</div>
                                                    @endif

                                                    @switch($method->type)
                                                        @case('transferencia')
                                                            @if ($method->clabe)
                                                                <div class="checkout-clabe-box">
                                                                    <div class="checkout-clabe-box__row">
                                                                        <div class="checkout-clabe-box__main">
                                                                            <div class="checkout-clabe-box__label">Clabe interbancaria</div>
                                                                            <div class="checkout-clabe-box__value" data-clabe-value="{{ $method->clabe }}">{{ $method->clabe }}</div>
                                                                        </div>
                                                                        <button type="button" class="checkout-clabe-box__copy" data-copy-clabe>Copiar</button>
                                                                    </div>
                                                                    @if ($method->bank_name || $method->beneficiary_name)
                                                                        <div class="checkout-clabe-box__meta">
                                                                            @if ($method->bank_name)
                                                                                <div>
                                                                                    <div class="checkout-clabe-box__label">Banco</div>
                                                                                    <div class="checkout-clabe-box__meta-value">{{ $method->bank_name }}</div>
                                                                                </div>
                                                                            @endif
                                                                            @if ($method->beneficiary_name)
                                                                                <div>
                                                                                    <div class="checkout-clabe-box__label">Beneficiario</div>
                                                                                    <div class="checkout-clabe-box__meta-value">{{ $method->beneficiary_name }}</div>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                            <div class="checkout-payment-method__details">
                                                                @if ($method->account_number)
                                                                    <div>Cuenta: {{ $method->account_number }}</div>
                                                                @endif
                                                                @if (!empty($method->details['moneda']) && $method->details['moneda'] !== 'MXN')
                                                                    <div>Moneda: {{ $method->details['moneda'] }}</div>
                                                                @endif
                                                                @if (!empty($method->details['swift_aba']))
                                                                    <div>SWIFT/ABA: {{ $method->details['swift_aba'] }}</div>
                                                                @endif
                                                            </div>
                                                            @break

                                                        @case('pasarela')
                                                        @case('digital')
                                                            @php
                                                                $processorLabels = [
                                                                    'stripe' => 'Stripe',
                                                                    'conekta' => 'Conekta',
                                                                    'mercadopago' => 'Mercado Pago',
                                                                    'openpay' => 'Openpay',
                                                                    'paypal' => 'PayPal',
                                                                    'clip' => 'Clip',
                                                                ];
                                                                $processor = $method->details['processor'] ?? null;
                                                                $msi = $method->details['msi'] ?? [];
                                                            @endphp
                                                            <div class="checkout-payment-method__details">
                                                                @if ($processor)
                                                                    <div>{{ $processorLabels[$processor] ?? $processor }}</div>
                                                                @endif
                                                                @if (!empty($msi))
                                                                    <div>Meses sin intereses disponibles: {{ implode(', ', $msi) }}</div>
                                                                @endif
                                                            </div>
                                                            @break

                                                        @case('referencia')
                                                            @php
                                                                $vigenciaLabels = [
                                                                    '24h' => '24 horas',
                                                                    '48h' => '48 horas',
                                                                    '72h' => '72 horas',
                                                                    '7dias' => '7 días',
                                                                ];
                                                            @endphp
                                                            <div class="checkout-payment-method__details">
                                                                @if (!empty($method->details['convenio']))
                                                                    <div>{{ $method->details['convenio'] }}</div>
                                                                @endif
                                                                @if (!empty($method->details['vigencia']))
                                                                    <div>Vigencia: {{ $vigenciaLabels[$method->details['vigencia']] ?? $method->details['vigencia'] }}</div>
                                                                @endif
                                                                @if (!empty($method->details['instrucciones']))
                                                                    <div>{!! nl2br(e($method->details['instrucciones'])) !!}</div>
                                                                @endif
                                                            </div>
                                                            @break

                                                        @case('credito')
                                                            @php
                                                                $plazoLabels = ['15' => '15 días', '30' => '30 días', '45' => '45 días', '60' => '60 días'];
                                                                $plazo = $method->details['plazo_credito'] ?? null;
                                                            @endphp
                                                            <div class="checkout-payment-method__details">
                                                                @if ($plazo)
                                                                    <div>Plazo: {{ $plazoLabels[$plazo] ?? $plazo . ' días' }}</div>
                                                                @endif
                                                                @if (!empty($method->details['linea_credito']))
                                                                    <div>Línea de crédito: ${{ number_format($method->details['linea_credito'], 2) }} MXN</div>
                                                                @endif
                                                            </div>
                                                            @break

                                                        @default
                                                            {{-- 'otro' (y cualquier tipo sin manejador específico): solo se muestra el nombre. --}}
                                                    @endswitch
                                                </div>
                                                @if ($method->type === 'transferencia')
                                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.8" class="checkout-payment-method__bank-icon"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4v16" stroke-linecap="round"/></svg>
                                                @endif
                                            </label>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="checkout-trust-row" style="margin-top:22px;">
                                        <div class="checkout-trust-row__item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>
                                            Compra 100% protegida
                                        </div>
                                        <div class="checkout-trust-row__item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                            Conexión encriptada SSL
                                        </div>
                                        <div class="checkout-trust-row__item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Garantía de devolución
                                        </div>
                                    </div>
                                </div>{{-- /#paymentMethodsWrap --}}
                            @endif
                        </div>

                        @if ($paymentMethods->isNotEmpty())
                            <div class="checkout-actions" id="paymentActionsWrap">
                                <button type="submit" form="paymentForm" class="checkout-submit" id="paymentSubmitBtn">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                                    <span id="paymentSubmitBtnLabel">Confirmar y pagar ${{ number_format($summary['total'], 2) }} MXN</span>
                                </button>
                            </div>
                            <div class="checkout-payment__legal">
                                Al confirmar aceptas los <a href="{{ $termsUrl }}" target="_blank" rel="noopener">Términos y Condiciones</a> y el <a href="{{ route('privacy-notice') }}" target="_blank" rel="noopener">Aviso de Privacidad</a> de Equiterm Industries.
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            <aside class="checkout-summary">
                {{-- Encabezado colapsable (móvil <900px, ver checkout.css /
                     initSummaryToggle() en checkout-accordion.js): en
                     escritorio siempre se ve el título fijo de abajo; en
                     móvil este toggle reemplaza el título y controla si el
                     detalle está expandido o colapsado. --}}
                <div class="checkout-summary__toggle" id="checkoutSummaryToggle">
                    <div>
                        <div class="checkout-summary__toggle-title">Resumen del pedido</div>
                        <div class="checkout-summary__toggle-sub">{{ $cart->items->sum('quantity') }} artículos · ${{ number_format($subtotal + $taxTotal + $shippingTotal, 2) }} MXN</div>
                    </div>
                    <span class="checkout-summary__toggle-label" id="checkoutSummaryToggleLabel">Ver detalle</span>
                </div>
                <h2 class="checkout-summary__title checkout-summary__deskhead">Resumen del pedido</h2>

                <div class="checkout-summary__body is-collapsed" id="checkoutSummaryBody">
                <div class="checkout-summary__items">
                    @foreach ($cart->items as $item)
                        <div class="checkout-summary__item">
                            <div class="checkout-summary__item-img">
                                @if ($item->product && $item->product->images->first())
                                    <img src="{{ $item->product->images->first()->url }}" alt="">
                                @endif
                            </div>
                            <div class="checkout-summary__item-name">{{ $item->product->name ?? 'Producto' }}<span> × {{ $item->quantity }}</span></div>
                            <div class="checkout-summary__item-total">${{ number_format($item->lineTotal(), 2) }}</div>
                        </div>
                    @endforeach
                </div>

                @if (!empty($freeShippingProgress))
                    <div class="checkout-shipping-progress">
                        @foreach ($freeShippingProgress as $progress)
                            <div class="checkout-shipping-progress__item">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h11l4 4v6h-2M3 7v10h2M3 7l2-3h7l2 3M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                <span>Te faltan <strong>${{ number_format($progress['remaining'], 2) }} MXN</strong> para envío gratis en <strong>{{ $progress['label'] }}</strong></span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="checkout-summary__row">
                    <span>Subtotal ({{ $cart->items->sum('quantity') }} artículos)</span>
                    <span>${{ number_format($subtotal, 2) }} MXN</span>
                </div>
                <div class="checkout-summary__row">
                    <span>IVA ({{ number_format(\App\Models\Products::ivaRate(), 0) }}%)</span>
                    <span>${{ number_format($taxTotal, 2) }} MXN</span>
                </div>
                <div class="checkout-summary__row">
                    <span>Envío</span>
                    <span>{{ $shippingTotal > 0 ? '$' . number_format($shippingTotal, 2) . ' MXN' : 'Gratis' }}</span>
                </div>
                <div class="checkout-summary__row checkout-summary__row--total">
                    <span>Total</span>
                    <span>${{ number_format($subtotal + $taxTotal + $shippingTotal, 2) }} MXN</span>
                </div>
                <div class="checkout-summary__price-note">Precios en pesos mexicanos, IVA incluido en el total.</div>
                <a href="{{ route('catalog.index') }}" class="checkout-summary__back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Seguir comprando
                </a>
                <div class="checkout-summary__foot">
                    <div class="checkout-summary__foot-badge">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Compra 100% protegida con
                    </div>
                    <div class="checkout-summary__foot-mp">
                        @include('frontend.shop.partials.mercadopago-logo', ['width' => 20, 'height' => 20])
                        <span class="mp-secure-badge__brand">Mercado Pago</span>
                    </div>
                    <div class="checkout-summary__foot-note">Compra respaldada por la garantía de satisfacción Equiterm Industries</div>
                </div>
                </div>{{-- /.checkout-summary__body --}}
            </aside>
        </div>

        @php
            // GA4 e-commerce (vía GTM) -- independiente de AdTracking (más
            // abajo en este mismo archivo), que es nuestro propio sistema de
            // atribución y no se toca. unit_price_snapshot ya es el precio
            // sin IVA (ver Cart::subtotal()), mismo criterio que $subtotal.
            $ga4CartItems = $cart->items->filter(fn ($i) => $i->product)->map(fn ($i) => [
                'item_id' => $i->product->sku,
                'item_name' => $i->product->resolveVariables($i->product->name),
                'price' => (float) $i->unit_price_snapshot,
                'quantity' => $i->quantity,
            ])->values();
        @endphp
        <script>
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({ ecommerce: null }); // limpia el ecommerce previo antes de cada push (recomendación de Google)
            dataLayer.push({
                event: 'begin_checkout',
                ecommerce: {
                    currency: 'MXN',
                    value: {{ (float) $subtotal }},
                    items: @json($ga4CartItems),
                },
            });
        </script>
    @endif
</div>

{{--
  ad-tracking.js se carga vía @vite() como <script type="module">, que el
  navegador SIEMPRE difiere hasta después de terminar de parsear el
  documento (justo antes de DOMContentLoaded) -- un <script> síncrono
  normal en esta posición del body corre ANTES de que exista
  window.AdTracking, así que la llamada nunca dispararía. Se espera a
  DOMContentLoaded para garantizar el orden correcto.
--}}
<script>
  document.addEventListener('DOMContentLoaded', () => window.AdTracking?.track('checkout_start'));
</script>
@endsection
