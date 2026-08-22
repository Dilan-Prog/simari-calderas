@extends('frontend.shop.layouts.master')

@php
    $shopVite = ['resources/css/frontend/shop/account.css', 'resources/js/frontend/shop/account.js'];

    // Tras una validación fallida del REGISTRO, back() puede aterrizar en la
    // URL de login (si el usuario cambió de tab client-side). first_name solo
    // existe en el form de registro, así que old() lo delata.
    $registerAttempt = old('first_name') !== null || old('last_name') !== null || old('company') !== null;
    $activeTab = $registerAttempt ? 'register' : ($mode ?? 'login');
@endphp

@section('title', ($activeTab === 'register' ? 'Crear cuenta' : 'Iniciar sesión') . ' — Equiterm Industries')

@section('content')
<div class="eq-shop-account eq-shop-account--auth"
    x-data="authTabs('{{ $activeTab }}', '{{ route('shop.login') }}', '{{ route('shop.register') }}')">

    <div class="auth-form-col">
        <div class="auth-card">

            {{-- Tabs segmentados --}}
            <div class="auth-tabs">
                <button type="button" :class="{ 'is-active': tab === 'login' }" @click="switchTo('login')">Iniciar sesión</button>
                <button type="button" :class="{ 'is-active': tab === 'register' }" @click="switchTo('register')">Crear cuenta</button>
            </div>

            {{-- LOGIN --}}
            <div x-show="tab === 'login'" x-cloak>
                <h1 class="auth-title">Bienvenido de nuevo</h1>
                <p class="auth-subtitle">Ingresa tus datos para acceder a tu cuenta.</p>

                @if (session('status'))
                    <div class="auth-status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('shop.login.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="visitor_uuid" id="visitorUuidField" value="">

                    <div class="auth-field">
                        <label for="login_email">Correo electrónico</label>
                        <input type="email" id="login_email" name="email"
                            value="{{ $registerAttempt ? '' : old('email') }}"
                            placeholder="tucorreo@empresa.com" autocomplete="email" required>
                    </div>

                    <div class="auth-field">
                        <div class="auth-field__label-row">
                            <label for="login_password">Contraseña</label>
                            <a href="{{ route('shop.password.request') }}" class="auth-link">¿Olvidaste tu contraseña?</a>
                        </div>
                        <input type="password" id="login_password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                    </div>

                    <label class="auth-check">
                        <input type="checkbox" name="remember" value="1"> Mantener sesión iniciada
                    </label>

                    @if (!$registerAttempt && $errors->any())
                        <div class="auth-error">{{ $errors->first() }}</div>
                    @endif

                    <button type="submit" class="auth-btn">Iniciar sesión</button>
                </form>

                <div class="auth-divider"><span>o continúa con</span></div>

                <div class="auth-social">
                    <button type="button" disabled aria-disabled="true" title="Próximamente">
                        <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.5 12.2c0-.8-.1-1.6-.2-2.3H12v4.4h5.9c-.3 1.4-1 2.5-2.2 3.3v2.7h3.5c2-1.9 3.3-4.7 3.3-8.1z"/><path fill="#34A853" d="M12 23c3 0 5.4-1 7.2-2.7l-3.5-2.7c-1 .7-2.2 1.1-3.7 1.1-2.9 0-5.3-1.9-6.2-4.6H2.2v2.8C4 20.5 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.8 14.1c-.2-.7-.4-1.4-.4-2.1s.1-1.4.4-2.1V7.1H2.2C1.4 8.6 1 10.3 1 12s.4 3.4 1.2 4.9l3.6-2.8z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.6l3.1-3.1C17.4 2.2 15 1 12 1 7.7 1 4 3.5 2.2 7.1l3.6 2.8c.9-2.7 3.3-4.5 6.2-4.5z"/></svg>
                        Google
                    </button>
                    <button type="button" disabled aria-disabled="true" title="Próximamente">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12z"/></svg>
                        Facebook
                    </button>
                </div>
                <p class="auth-social__hint">Inicio de sesión con Google y Facebook próximamente.</p>
            </div>

            {{-- REGISTRO --}}
            <div x-show="tab === 'register'" x-cloak>
                <h1 class="auth-title">Crea tu cuenta</h1>
                <p class="auth-subtitle">Regístrate para cotizar y comprar más rápido.</p>

                <form method="POST" action="{{ route('shop.register.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="visitor_uuid" id="visitorUuidField" value="">

                    <div class="auth-field auth-field--half">
                        <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="Nombre" required>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Apellido" required>
                    </div>
                    <div class="auth-field">
                        <input type="text" name="company" value="{{ old('company') }}" placeholder="Empresa (opcional)">
                    </div>
                    <div class="auth-field">
                        <input type="email" name="email" value="{{ $registerAttempt ? old('email') : '' }}" placeholder="Correo electrónico" autocomplete="email" required>
                    </div>
                    <div class="auth-field">
                        <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Teléfono" autocomplete="tel" required>
                    </div>
                    <div class="auth-field">
                        <input type="password" name="password" placeholder="Contraseña" autocomplete="new-password" required>
                    </div>
                    <div class="auth-field">
                        <input type="password" name="password_confirmation" placeholder="Confirmar contraseña" autocomplete="new-password" required>
                    </div>

                    <label class="auth-check auth-check--terms">
                        <input type="checkbox" required>
                        <span>Acepto los <a href="{{ route('terms-of-service') }}" class="auth-link" target="_blank">Términos y condiciones</a> y el <a href="{{ route('privacy-notice') }}" class="auth-link" target="_blank">Aviso de privacidad</a></span>
                    </label>

                    @if ($registerAttempt && $errors->any())
                        <div class="auth-error">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" class="auth-btn">Crear cuenta</button>
                </form>
            </div>

        </div>
    </div>

    {{-- Panel de beneficios --}}
    <div class="auth-benefits">
        <div class="auth-benefits__inner">
            <div class="auth-benefits__eyebrow">Ahorra tiempo y dinero</div>
            <h2>Compra más rápido y accede a precios preferenciales</h2>
            <div class="auth-benefits__list">
                @foreach (['Precios preferenciales y descuentos por volumen', 'Checkout más rápido, sin volver a capturar tus datos', 'Rastrea tus pedidos y cotizaciones en tiempo real', 'Soporte técnico prioritario para tu equipo'] as $benefit)
                    <div class="auth-benefits__item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--secondary-color)" stroke-width="2.4"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ $benefit }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
