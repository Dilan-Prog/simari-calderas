@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/account.css', 'resources/js/frontend/shop/account.js'];

    $portalRequest = $customer->portalRequest;
    $portalRequestCompleted = (bool) ($portalRequest?->completed_at);
@endphp

@section('title', 'Mi cuenta — Equiterm Industries')

@section('content')
<div class="eq-shop-account eq-shop-account--portal" x-data="accountPortal()">
    <div class="portal-layout">

        @include('frontend.shop.account.partials.sidebar')

        <div class="portal-content">
            @if (session('status'))
                <div class="auth-status portal-flash">{{ session('status') }}</div>
            @endif

            @include('frontend.shop.account.partials.profile')
            @include('frontend.shop.account.partials.orders')
            @include('frontend.shop.account.partials.addresses')
            @include('frontend.shop.account.partials.payments')
            @include('frontend.shop.account.partials.favorites')
            @include('frontend.shop.account.partials.portal-request')
        </div>
    </div>

    @include('frontend.shop.account.partials.modals')
</div>
@endsection
