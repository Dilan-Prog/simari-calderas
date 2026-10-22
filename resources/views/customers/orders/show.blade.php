@extends('customers.layouts.master')

@section('title', 'Pedido ' . $o['number'])

@push('styles')
    @vite('resources/css/admin/technical-services.css')
@endpush

@section('content')
<div class="ts-page">

    <div class="ts-show-header">
        <div style="display:flex;align-items:flex-start;gap:0.75rem">
            <a href="{{ route('customer.orders.index') }}" class="ts-show-back" title="Volver">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 19-7-7 7-7M19 12H5"/>
                </svg>
            </a>
            <div>
                <div class="ts-breadcrumb" style="margin-bottom:0.25rem">
                    <span>Panel de Control</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <a href="{{ route('customer.orders.index') }}" style="color:inherit;text-decoration:none">Mis Pedidos</a>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                    <span class="ts-breadcrumb__current">{{ $o['number'] }}</span>
                </div>
                <div class="ts-show-title-row">
                    <span class="ts-show-num">{{ $o['number'] }}</span>
                    <span class="eqo-pill" style="background:{{ $o['status_bg'] }};color:{{ $o['status_color'] }};">{{ $o['status_label'] }}</span>
                </div>
                <h1 class="ts-show-title">Realizado el {{ $o['date'] }}</h1>
            </div>
        </div>
    </div>

    @include('frontend.shop.account.partials.order-styles')

    <div class="eqo">
        @include('frontend.shop.account.partials.order-detail', ['o' => $o])
    </div>
</div>
@endsection
