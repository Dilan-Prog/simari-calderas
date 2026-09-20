@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css'];
@endphp

@section('title', 'Pedido confirmado — Equiterm Industries')

@section('content')
@php
    // loadMissing() es idempotente -- seguro llamarlo aunque el controller ya
    // haya cargado alguna de estas relaciones (CheckoutController::confirm()
    // no se toca aquí, este eager-load vive en la propia vista).
    $storeOrder->loadMissing(['items.product.images', 'paymentMethod']);
@endphp
<div class="eq-checkout">
    <div class="checkout-confirmation">
        {{-- Esta vista SOLO se renderiza cuando el pago realmente quedó
             aprobado o en un estado asíncrono válido (in_process/pending,
             ver MercadoPagoCheckoutController::thanks()) -- un pago
             rechazado/cancelado nunca llega aquí, se queda en la página de
             checkout con un modal de reintento en su lugar. --}}
        <div class="checkout-confirmation__icon">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h1 class="checkout-confirmation__title">¡Pedido confirmado!</h1>
        <p class="checkout-confirmation__folio">Tu número de pedido es <strong>{{ $storeOrder->order_number }}</strong>.</p>
        <p class="checkout-confirmation__text">
            Hemos recibido tu pedido por un total de <strong>${{ number_format($storeOrder->total, 2) }} {{ $storeOrder->currency }}</strong>.
            Te contactaremos a <strong>{{ $storeOrder->contact_email }}</strong> con los siguientes pasos para tu pago y envío.
        </p>

        <div class="checkout-confirmation__items">
            @foreach ($storeOrder->items as $item)
                <div class="checkout-confirmation__item">
                    <div class="checkout-confirmation__item-img">
                        @if ($item->product && $item->product->images->first())
                            <img src="{{ $item->product->images->first()->url }}" alt="{{ $item->product_name }}">
                        @endif
                    </div>
                    <div class="checkout-confirmation__item-info">
                        <div class="checkout-confirmation__item-name">{{ $item->product_name }}</div>
                        <div class="checkout-confirmation__item-sku">{{ $item->product_sku }} · Cantidad {{ $item->quantity }}</div>
                    </div>
                    <div class="checkout-confirmation__item-total">${{ number_format($item->line_total, 2) }}</div>
                </div>
            @endforeach
        </div>

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

        <div class="checkout-confirmation__cards">
            <div class="checkout-confirmation__card">
                <div class="checkout-confirmation__card-label">Enviar a</div>
                <div class="checkout-confirmation__card-title">{{ $storeOrder->contact_name }}</div>
                <div class="checkout-confirmation__card-text">
                    {{ $storeOrder->shipping_address_line1 }}
                    @if (!empty($storeOrder->shipping_address_line2))
                        , {{ $storeOrder->shipping_address_line2 }}
                    @endif
                    <br>
                    {{ $storeOrder->shipping_city }}, {{ $storeOrder->shipping_state }}, CP {{ $storeOrder->shipping_postal_code }}
                </div>
            </div>
            <div class="checkout-confirmation__card">
                <div class="checkout-confirmation__card-label">Método de pago</div>
                <div class="checkout-confirmation__card-title">{{ $storeOrder->paymentMethod->name ?? 'No especificado' }}</div>
                <div class="checkout-confirmation__card-text">Entrega estimada: {{ config('shop.delivery_estimate_label', '3–5 días hábiles') }}</div>
            </div>
        </div>

        <a href="{{ route('catalog.index') }}" class="checkout-confirmation__link">Volver al catálogo</a>
    </div>
</div>

@php
    // GA4 e-commerce (vía GTM) -- independiente de AdTracking/endpoints de
    // /api/v1/ad-tracking/*, que son nuestro propio sistema de atribución y
    // no se tocan. order_number como transaction_id: es el folio único y
    // estable del pedido (no se regenera si esta página se recarga), así
    // GA4 no cuenta la misma venta dos veces.
    $ga4OrderItems = $storeOrder->items->map(fn ($i) => [
        'item_id' => $i->product_sku,
        'item_name' => $i->product_name,
        'price' => (float) $i->unit_price,
        'quantity' => $i->quantity,
    ])->values();
@endphp
<script>
    window.dataLayer = window.dataLayer || [];
    dataLayer.push({ ecommerce: null }); // limpia el ecommerce previo antes de cada push (recomendación de Google)
    dataLayer.push({
        event: 'purchase',
        ecommerce: {
            transaction_id: @json($storeOrder->order_number),
            currency: @json($storeOrder->currency),
            value: {{ (float) $storeOrder->total }},
            items: @json($ga4OrderItems),
        },
    });
</script>

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
