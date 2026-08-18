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

        <div style="text-align:left; max-width:320px; margin:0 auto 28px;">
            <div class="checkout-summary__row">
                <span>Subtotal</span>
                <span>${{ number_format($storeOrder->subtotal, 2) }} MXN</span>
            </div>
            <div class="checkout-summary__row">
                <span>IVA</span>
                <span>${{ number_format($storeOrder->tax_total, 2) }} MXN</span>
            </div>
            <div class="checkout-summary__row">
                <span>Envío</span>
                <span>{{ $storeOrder->shipping_total > 0 ? '$' . number_format($storeOrder->shipping_total, 2) . ' MXN' : 'Gratis' }}</span>
            </div>
            <div class="checkout-summary__row checkout-summary__row--total" style="margin-bottom:0;">
                <span>Total</span>
                <span>${{ number_format($storeOrder->total, 2) }} {{ $storeOrder->currency }}</span>
            </div>
        </div>

        <a href="{{ route('catalog.index') }}" class="checkout-confirmation__link">Volver al catálogo</a>
    </div>
</div>

@php
    // Mismo plazo ya publicado en Términos y Condiciones para equipo en
    // existencia ("5-10 días hábiles") — se usa el límite superior (10)
    // como estimado, sin prometer de más. Cuenta días hábiles reales
    // (salta sábados/domingos) desde hoy.
    $reviewDeliveryDate = now();
    $businessDaysAdded = 0;
    while ($businessDaysAdded < 10) {
        $reviewDeliveryDate = $reviewDeliveryDate->addDay();
        if (! $reviewDeliveryDate->isWeekend()) {
            $businessDaysAdded++;
        }
    }
@endphp
{{--
  Integración de la aceptación — Reseñas de Clientes en Google. Paso
  obligatorio de Merchant Center: le permite a Google mostrar, tras esta
  compra, la encuesta de opt-in para que el cliente puntúe al negocio.
  No se manda el arreglo opcional "products" (GTIN) porque el catálogo no
  registra GTIN por producto.
--}}
<script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer></script>
<script>
  window.renderOptIn = function() {
    window.gapi.load('surveyoptin', function() {
      window.gapi.surveyoptin.render({
        "merchant_id": 5841352274,
        "order_id": @json($storeOrder->order_number),
        "email": @json($storeOrder->contact_email),
        "delivery_country": @json($storeOrder->shipping_country),
        "estimated_delivery_date": @json($reviewDeliveryDate->format('Y-m-d'))
      });
    });
  }
</script>
@endsection
