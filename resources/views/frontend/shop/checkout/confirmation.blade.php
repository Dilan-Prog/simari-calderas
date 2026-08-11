@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css'];
@endphp

@section('title', 'Pedido confirmado — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <div class="checkout-confirmation">
        <div class="checkout-confirmation__icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h1 class="checkout-confirmation__title">¡Pedido confirmado!</h1>
        <p class="checkout-confirmation__folio">Tu número de pedido es <strong>{{ $storeOrder->order_number }}</strong>.</p>
        <p class="checkout-confirmation__text">
            Hemos recibido tu pedido por un total de <strong>${{ number_format($storeOrder->total, 2) }} {{ $storeOrder->currency }}</strong>.
            Te contactaremos a <strong>{{ $storeOrder->contact_email }}</strong> con los siguientes pasos para tu pago y envío.
        </p>
        <a href="{{ route('catalog.index') }}" class="checkout-confirmation__link">Volver al catálogo</a>
    </div>
</div>
@endsection
