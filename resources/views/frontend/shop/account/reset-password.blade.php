@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/account.css', 'resources/js/frontend/shop/account.js'];
@endphp

@section('title', 'Restablecer contraseña — Equiterm Industries')

@section('content')
<div class="eq-shop-account eq-shop-account--single">
    <div class="auth-card">
        <h1 class="auth-title">Restablecer contraseña</h1>
        <p class="auth-subtitle">Define tu nueva contraseña para volver a entrar.</p>

        <form method="POST" action="{{ route('shop.password.update') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="auth-field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
            </div>

            <div class="auth-field">
                <label for="password">Nueva contraseña (mínimo 8 caracteres)</label>
                <input type="password" id="password" name="password" autocomplete="new-password" required autofocus>
            </div>

            <div class="auth-field">
                <label for="password_confirmation">Confirmar nueva contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

            @if ($errors->any())
                <div class="auth-error">{{ $errors->first() }}</div>
            @endif

            <button type="submit" class="auth-btn">Guardar contraseña</button>
        </form>
    </div>
</div>
@endsection
