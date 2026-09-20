@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css', 'resources/js/frontend/shop/mercadopago-checkout.js'];

    // Datos que consume resources/js/frontend/shop/mercadopago-checkout.js.
    // chargeUrl siempre apunta a la ruta de cobro real (aunque estemos en
    // modo reintento, es el mismo endpoint -- el "reintento" es solo que se
    // vuelve a mostrar el Brick de ese cobro).
    $mpCheckoutData = [
        'publicKey' => $publicKey,
        'retryOnly' => $retryOnly,
        'payerEmail' => $order->contact_email,
        'payments' => $payments->map(fn ($p) => [
            'id' => $p->id,
            'chargeGroup' => $p->charge_group,
            'includesMsi' => (bool) $p->includes_msi,
            'amount' => (float) $p->amount,
            'status' => $p->status,
            'containerId' => 'cardform-' . $p->charge_group,
            'chargeUrl' => route('checkout.payment.mercadopago.charge', [$order->order_number, $p->id]),
        ])->values(),
    ];
@endphp

@section('title', 'Pago con Mercado Pago — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <h1 class="checkout-title">Finalizar pedido</h1>

    @if (session('info'))
        <div class="checkout-alert" style="background:#eef2ff;color:#3730a3;">{{ session('info') }}</div>
    @endif

    <p style="margin:0 0 20px;color:#4b5563;">
        Pedido <strong>{{ $order->order_number }}</strong> — total <strong>${{ number_format($order->total, 2) }} {{ $order->currency }}</strong>.
    </p>

    @if (blank($publicKey))
        {{-- Sin Public Key configurada para el rol "api" (Checkout API/
             Tarjeta) en /admin/integraciones, new MercadoPago(null) falla en
             silencio del lado del cliente y los campos de tarjeta se quedan
             vacíos para siempre sin ningún mensaje -- se corta aquí con un
             aviso claro en vez de montar un formulario que nunca va a
             funcionar. --}}
        <div class="checkout-alert checkout-alert--error">
            El pago con tarjeta no está disponible en este momento (falta configurar Mercado Pago). Contáctanos para completar tu pago.
        </div>
    @else
        {{-- mercadopago-checkout.js (init()) lee #mp-checkout-data y construye
             aquí dentro el markup real de cada cobro (CardForm o solo-lectura
             para los ya resueltos en modo reintento) -- ver buildCardFormBlocksMarkup(). --}}
        <div class="mp-charges" id="mpCardFormRoot"></div>
    @endif

    <div class="checkout-actions">
        <a href="{{ route('checkout.index') }}" class="checkout-back">Volver al inicio del pedido</a>
    </div>
</div>

@unless (blank($publicKey))
    <script type="application/json" id="mp-checkout-data">@json($mpCheckoutData)</script>

    {{-- SDK JS oficial de Mercado Pago (Payment Brick). Script clásico (no
         type="module"), así que se ejecuta ANTES que el bundle de Vite de esta
         página (los módulos de Vite se difieren por spec) -- para cuando
         mercadopago-checkout.js corre, window.MercadoPago ya existe. --}}
    <script src="https://sdk.mercadopago.com/js/v2"></script>
@endunless
@endsection
