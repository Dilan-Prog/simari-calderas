@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/checkout.css'];
@endphp

@section('title', 'Tu carrito — Equiterm Industries')

@section('content')
<div class="eq-checkout">
    <h1 class="checkout-title">Finalizar pedido</h1>

    <div class="checkout-steps">
        <span class="checkout-steps__item is-active"><span class="checkout-steps__dot">1</span><span class="checkout-steps__label">Carrito</span></span>
        <span class="checkout-steps__item"><span class="checkout-steps__dot">2</span><span class="checkout-steps__label">Envío</span></span>
        <span class="checkout-steps__item"><span class="checkout-steps__dot">3</span><span class="checkout-steps__label">Pago</span></span>
    </div>

    @if (session('error'))
        <div class="checkout-alert checkout-alert--error">{{ session('error') }}</div>
    @endif

    @if ($cart->items->isEmpty())
        <div class="checkout-empty">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.6"><path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 8H6"/><circle cx="9" cy="21" r="1.4"/><circle cx="18" cy="21" r="1.4"/></svg>
            <div class="checkout-empty__text">Tu carrito está vacío.</div>
            <a href="{{ route('catalog.index') }}" class="checkout-empty__link">Explorar catálogo</a>
        </div>
    @else
        <div class="checkout-layout">
            <div>
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
                                @else
                                    <span class="checkout-cart-item__name">Producto no disponible</span>
                                @endif
                                <form method="POST" action="{{ route('cart.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                    <button type="submit" class="checkout-cart-item__remove">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2m2 0-1 13a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Eliminar
                                    </button>
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

                <a href="{{ route('checkout.shipping') }}" class="checkout-submit" style="text-decoration:none;">Continuar a envío</a>
            </div>

            <aside class="checkout-summary">
                <h2 class="checkout-summary__title">Resumen del pedido</h2>
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
                <div class="checkout-summary__row">
                    <span>Subtotal ({{ $cart->items->sum('quantity') }} artículos)</span>
                    <span>${{ number_format($subtotal, 2) }} MXN</span>
                </div>
                <div class="checkout-summary__row">
                    <span>Envío</span>
                    <span>{{ $shippingTotal > 0 ? '$' . number_format($shippingTotal, 2) . ' MXN' : 'Gratis' }}</span>
                </div>
                <div class="checkout-summary__row checkout-summary__row--total">
                    <span>Total</span>
                    <span>${{ number_format($subtotal + $shippingTotal, 2) }} MXN</span>
                </div>
                <a href="{{ route('checkout.shipping') }}" class="checkout-summary__continue">Continuar a envío</a>
                <a href="{{ route('catalog.index') }}" class="checkout-summary__back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Seguir comprando
                </a>
                <div class="checkout-summary__foot">
                    <div class="checkout-summary__foot-badge">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                        Pago seguro protegido
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
