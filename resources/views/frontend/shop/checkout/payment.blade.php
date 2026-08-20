@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css'];
@endphp

@section('title', 'Pago — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <h1 class="checkout-title">Finalizar pedido</h1>

    <div class="checkout-steps">
        <span class="checkout-steps__item is-done"><span class="checkout-steps__dot">✓</span><span class="checkout-steps__label">Carrito</span></span>
        <span class="checkout-steps__item is-done"><span class="checkout-steps__dot">✓</span><span class="checkout-steps__label">Envío</span></span>
        <span class="checkout-steps__item is-active"><span class="checkout-steps__dot">3</span><span class="checkout-steps__label">Pago</span></span>
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
            <div class="checkout-payment">
                <div class="checkout-payment__head">
                    <div class="checkout-payment__head-title">Método de pago</div>
                    <div class="checkout-form__badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Conexión encriptada SSL
                    </div>
                </div>

                @if ($paymentMethods->isEmpty())
                    <div class="checkout-alert checkout-alert--error">
                        No hay métodos de pago disponibles en este momento. Por favor contáctanos para completar tu pedido.
                    </div>
                @else
                    <form method="POST" action="{{ route('checkout.confirm') }}" id="paymentForm">
                        @csrf

                        <div class="checkout-payment-methods">
                            @foreach ($paymentMethods as $method)
                                <label class="checkout-payment-method">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" {{ $loop->first ? 'checked' : '' }} required>
                                    <div>
                                        <div class="checkout-payment-method__name-row">
                                            <div class="checkout-payment-method__name">{{ $method->name }}</div>

                                            @if ($method->allows_invoicing)
                                                {{-- Este badge es solo informativo. El checkbox real "¿Requiere factura?" (RFC,
                                                     uso CFDI, etc.) vive en el paso de Envío (shipping.blade.php), que ocurre
                                                     ANTES de que el cliente elija método de pago, por lo que allows_invoicing
                                                     no puede condicionar si esa opción se ofrece — reordenar los pasos del
                                                     wizard está fuera del alcance de esta tarea. --}}
                                                <span class="checkout-form__badge checkout-payment-method__badge">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                                                    Factura CFDI disponible
                                                </span>
                                            @endif
                                        </div>

                                        @if ($method->description)
                                            <div class="checkout-payment-method__description">{{ $method->description }}</div>
                                        @endif

                                        @switch($method->type)
                                            @case('transferencia')
                                                <div class="checkout-payment-method__details">
                                                    @if ($method->bank_name)
                                                        <div>Banco: {{ $method->bank_name }}</div>
                                                    @endif
                                                    @if ($method->clabe)
                                                        <div>CLABE: {{ $method->clabe }}</div>
                                                    @endif
                                                    @if ($method->account_number)
                                                        <div>Cuenta: {{ $method->account_number }}</div>
                                                    @endif
                                                    @if ($method->beneficiary_name)
                                                        <div>Beneficiario: {{ $method->beneficiary_name }}</div>
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
                                </label>
                            @endforeach
                        </div>

                        <div class="checkout-trust-row" style="margin-top:22px;">
                            <div class="checkout-trust-row__item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/></svg>
                                Compra 100% protegida
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            @if ($paymentMethods->isNotEmpty())
                <div class="checkout-actions">
                    <a href="{{ route('checkout.shipping') }}" class="checkout-back">Atrás</a>
                    <button type="submit" form="paymentForm" class="checkout-submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Confirmar y pagar ${{ number_format($summary['total'], 2) }} MXN
                    </button>
                </div>
            @else
                <a href="{{ route('checkout.shipping') }}" class="checkout-back">Atrás</a>
            @endif
        </div>

        <aside class="checkout-summary">
            <h2 class="checkout-summary__title">Resumen del pedido</h2>

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
                <span>Subtotal</span>
                <span>${{ number_format($summary['subtotal'], 2) }} MXN</span>
            </div>
            <div class="checkout-summary__row">
                <span>IVA ({{ number_format(\App\Models\Products::ivaRate(), 0) }}%)</span>
                <span>${{ number_format($summary['taxTotal'], 2) }} MXN</span>
            </div>
            <div class="checkout-summary__row">
                <span>Envío</span>
                <span>{{ $summary['shippingTotal'] > 0 ? '$' . number_format($summary['shippingTotal'], 2) . ' MXN' : 'Gratis' }}</span>
            </div>
            <div class="checkout-summary__row checkout-summary__row--total" style="margin-bottom:0;">
                <span>Total</span>
                <span>${{ number_format($summary['total'], 2) }} MXN</span>
            </div>
        </aside>
    </div>
</div>
@endsection
