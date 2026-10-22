@extends('customers.layouts.master')

@section('title', 'Mis Pedidos')

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page">

    <div class="ts-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"/>
        </svg>
        <span class="ts-breadcrumb__current">Mis Pedidos</span>
    </div>

    <div class="ts-header-top">
        <div>
            <h1 class="ts-title">Mis Pedidos</h1>
            <p class="ts-subtitle">Consulta el estatus, el envío y el detalle de tus compras en la tienda</p>
        </div>
    </div>

    @include('frontend.shop.account.partials.order-styles')

    <div class="eqo">
        @if ($orders->isEmpty())
            <div class="eqo-empty">
                <p>Aún no tienes pedidos en la tienda.</p>
                <a href="{{ route('catalog.index') }}" class="eqo-btn">Ver productos</a>
            </div>
        @else
            <div class="eqo-card eqo-list">
                @foreach ($orders as $o)
                    <a href="{{ route('customer.orders.show', $o['number']) }}" class="eqo-row">
                        <div class="eqo-row__id">
                            <div class="eqo-row__num">{{ $o['number'] }}</div>
                            <div class="eqo-row__date">{{ $o['date_short'] }}</div>
                        </div>
                        <span class="eqo-pill" style="background:{{ $o['status_bg'] }};color:{{ $o['status_color'] }};">{{ $o['status_label'] }}</span>
                        <div class="eqo-row__items">{{ $o['items_count'] }} {{ $o['items_count'] === 1 ? 'artículo' : 'artículos' }}</div>
                        <div class="eqo-row__total">${{ number_format($o['total'], 2) }}</div>
                        <svg class="eqo-row__chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endforeach
            </div>
            @if ($hasMore)
                <p class="eqo-note">Mostrando tus {{ $orders->count() }} pedidos más recientes.</p>
            @endif
        @endif
    </div>
</div>
@endsection
