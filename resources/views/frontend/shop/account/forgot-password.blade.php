@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/account.css', 'resources/js/frontend/shop/account.js'];
@endphp

@section('title', 'Recuperar contraseña — Equiterm Industries')

@section('content')
<div class="eq-shop-account eq-shop-account--single">
    <div class="auth-card">
        <h1 class="auth-title">Recuperar contraseña</h1>
        <p class="auth-subtitle">
            Escribe el correo con el que te registraste y te enviaremos un enlace
            para establecer una nueva contraseña.
        </p>

        @if (session('status'))
            <div class="auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('shop.password.email') }}" novalidate>
            @csrf

            <div class="auth-field">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    placeholder="tucorreo@empresa.com" autocomplete="email" required autofocus>
            </div>

            @if ($errors->any())
                <div class="auth-error">{{ $errors->first() }}</div>
            @endif

            <button type="submit" class="auth-btn">Enviar enlace</button>
        </form>

        <p class="auth-card__footer">
            <a href="{{ route('shop.login') }}" class="auth-link">← Volver a iniciar sesión</a>
        </p>
    </div>
</div>
@endsection
