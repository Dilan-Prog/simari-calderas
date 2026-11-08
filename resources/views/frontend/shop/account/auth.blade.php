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

                @php $regErr = fn (string $k) => $registerAttempt ? $errors->first($k) : ''; @endphp
                <form method="POST" action="{{ route('shop.register.store') }}" novalidate data-reg-form>
                    @csrf
                    <input type="hidden" name="visitor_uuid" id="visitorUuidField" value="">

                    <p class="auth-required-note"><span class="auth-req" aria-hidden="true">*</span> Campos obligatorios</p>

                    <div class="auth-field auth-field--half">
                        <div class="auth-field__cell {{ $regErr('first_name') ? 'is-invalid' : '' }}">
                            <label for="reg_first_name">Nombre <span class="auth-req" aria-hidden="true">*</span></label>
                            <input type="text" id="reg_first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Ej. María" maxlength="100" autocomplete="given-name" data-rule="name" required aria-required="true">
                            <p class="auth-field-error" data-err-for="first_name">{{ $regErr('first_name') }}</p>
                        </div>
                        <div class="auth-field__cell {{ $regErr('last_name') ? 'is-invalid' : '' }}">
                            <label for="reg_last_name">Apellido <span class="auth-req" aria-hidden="true">*</span></label>
                            <input type="text" id="reg_last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Ej. López" maxlength="100" autocomplete="family-name" data-rule="name" required aria-required="true">
                            <p class="auth-field-error" data-err-for="last_name">{{ $regErr('last_name') }}</p>
                        </div>
                    </div>
                    <div class="auth-field {{ $regErr('company') ? 'is-invalid' : '' }}">
                        <label for="reg_company">Empresa <small class="auth-optional">(opcional)</small></label>
                        <input type="text" id="reg_company" name="company" value="{{ old('company') }}" placeholder="Razón social o nombre comercial" maxlength="255" autocomplete="organization" data-rule="company">
                        <p class="auth-field-error" data-err-for="company">{{ $regErr('company') }}</p>
                    </div>
                    <div class="auth-field {{ $regErr('email') ? 'is-invalid' : '' }}">
                        <label for="reg_email">Correo electrónico <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="email" id="reg_email" name="email" value="{{ $registerAttempt ? old('email') : '' }}" placeholder="tucorreo@empresa.com" maxlength="255" autocomplete="email" data-rule="email" required aria-required="true">
                        <p class="auth-field-error" data-err-for="email">{{ $regErr('email') }}</p>
                    </div>
                    <div class="auth-field {{ $regErr('phone') ? 'is-invalid' : '' }}">
                        <label for="reg_phone">Teléfono <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="tel" id="reg_phone" name="phone" value="{{ old('phone') }}" placeholder="10 dígitos, ej. 449 123 4567" maxlength="20" inputmode="tel" autocomplete="tel" data-rule="phone" required aria-required="true">
                        <p class="auth-field-error" data-err-for="phone">{{ $regErr('phone') }}</p>
                    </div>
                    <div class="auth-field {{ $regErr('password') ? 'is-invalid' : '' }}">
                        <label for="reg_password">Contraseña <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="password" id="reg_password" name="password" placeholder="Mínimo 8 caracteres" autocomplete="new-password" data-rule="password" required aria-required="true">
                        <p class="auth-field-error" data-err-for="password">{{ $regErr('password') }}</p>
                    </div>
                    <div class="auth-field">
                        <label for="reg_password_confirmation">Confirmar contraseña <span class="auth-req" aria-hidden="true">*</span></label>
                        <input type="password" id="reg_password_confirmation" name="password_confirmation" placeholder="Repite tu contraseña" autocomplete="new-password" data-rule="confirm" required aria-required="true">
                        <p class="auth-field-error" data-err-for="password_confirmation"></p>
                    </div>

                    <div class="auth-terms-wrap {{ $regErr('terms') ? 'is-invalid' : '' }}">
                        <label class="auth-check auth-check--terms">
                            <input type="checkbox" name="terms" value="1" data-rule="terms" required aria-required="true" @checked($registerAttempt && old('terms'))>
                            <span><span class="auth-req" aria-hidden="true">*</span> Acepto los <a href="{{ route('terms-of-service') }}" class="auth-link" target="_blank">Términos y condiciones</a> y el <a href="{{ route('privacy-notice') }}" class="auth-link" target="_blank">Aviso de privacidad</a></span>
                        </label>
                        <p class="auth-field-error" data-err-for="terms">{{ $regErr('terms') }}</p>
                    </div>

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

<style>
    .eq-shop-account .auth-required-note { margin: 0 0 14px; font-size: 12.5px; color: #4b5563; }
    .eq-shop-account .auth-req { color: #c81e1e; font-weight: 800; }
    .eq-shop-account .auth-optional { font-weight: 500; color: #6b7280; }
    .eq-shop-account .auth-field-error { margin: 5px 0 0; font-size: 12px; line-height: 1.35; color: #c81e1e; }
    .eq-shop-account .auth-field-error:empty { display: none; }
    .eq-shop-account .is-invalid input:not([type="checkbox"]) { border-color: #c81e1e; background: #fff8f8; }
    .eq-shop-account .is-invalid input:not([type="checkbox"]):focus { box-shadow: 0 0 0 3px rgba(200, 30, 30, .12); }
    .eq-shop-account .auth-terms-wrap { margin-bottom: 14px; }
    .eq-shop-account .auth-terms-wrap .auth-check { margin-bottom: 0; }
    .eq-shop-account .auth-terms-wrap .auth-field-error { margin-top: 6px; }
    .eq-shop-account .auth-field__cell { min-width: 0; }
</style>

<script>
    (function () {
        var form = document.querySelector('form[data-reg-form]');
        if (!form) return;

        // Caracteres que NO se aceptan en cada tipo de campo (se descartan al escribir o pegar).
        var STRIP = {
            name: /[^\p{L}\p{M}\s.'’\-]/gu,
            company: /[^\p{L}\p{N}\p{M}\s.,&'’\-\/()]/gu,
            phone: /[^\d\s\-().+]/g
        };
        var HINT = {
            name: 'Solo letras, espacios, punto, apóstrofo y guion.',
            company: 'Solo letras, números, espacios y . , & \' - / ( ).',
            phone: 'Solo números, espacios, guiones y paréntesis.'
        };
        var NAME_OK = /^[\p{L}\p{M}][\p{L}\p{M}\s.'’\-]*$/u;
        var COMPANY_OK = /^[\p{L}\p{N}][\p{L}\p{N}\p{M}\s.,&'’\-\/()]*$/u;
        var PHONE_OK = /^\+?[\d\s\-().]+$/;
        var EMAIL_OK = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

        function errBox(el) { return form.querySelector('[data-err-for="' + el.name + '"]'); }
        function wrap(el) { return el.closest('.auth-field__cell, .auth-field, .auth-terms-wrap'); }

        function setError(el, msg) {
            var box = errBox(el), w = wrap(el);
            if (box) box.textContent = msg || '';
            if (w) w.classList.toggle('is-invalid', !!msg);
            el.setAttribute('aria-invalid', msg ? 'true' : 'false');
            return msg;
        }

        function check(el) {
            var rule = el.dataset.rule, v = el.value.trim();
            if (rule === 'terms') return el.checked ? '' : 'Debes aceptar los Términos y condiciones y el Aviso de privacidad.';
            if (rule === 'name') {
                if (!v) return el.name === 'first_name' ? 'Escribe tu nombre.' : 'Escribe tu apellido.';
                return NAME_OK.test(v) ? '' : HINT.name;
            }
            if (rule === 'company') return !v || COMPANY_OK.test(v) ? '' : HINT.company;
            if (rule === 'email') {
                if (!v) return 'Escribe tu correo electrónico.';
                return EMAIL_OK.test(v) ? '' : 'Escribe un correo válido, por ejemplo nombre@empresa.com.';
            }
            if (rule === 'phone') {
                if (!v) return 'Escribe tu teléfono.';
                if (!PHONE_OK.test(v)) return HINT.phone;
                var n = v.replace(/\D/g, '').length;
                return n >= 10 && n <= 15 ? '' : 'El teléfono debe tener entre 10 y 15 dígitos.';
            }
            if (rule === 'password') {
                if (!el.value) return 'Escribe una contraseña.';
                return el.value.length >= 8 ? '' : 'La contraseña debe tener al menos 8 caracteres.';
            }
            if (rule === 'confirm') {
                if (!el.value) return 'Confirma tu contraseña.';
                return el.value === form.elements.password.value ? '' : 'Las contraseñas no coinciden.';
            }
            return '';
        }

        var fields = [].slice.call(form.querySelectorAll('[data-rule]'));
        var touched = new WeakSet();

        fields.forEach(function (el) {
            el.addEventListener('input', function () {
                var strip = STRIP[el.dataset.rule];
                if (strip) {
                    var clean = el.value.replace(strip, '');
                    if (clean !== el.value) {
                        el.value = clean;
                        setError(el, HINT[el.dataset.rule]); // avisa por qué desapareció el carácter
                        return;
                    }
                }
                if (touched.has(el) || wrap(el).classList.contains('is-invalid')) setError(el, check(el));
                if (el.dataset.rule === 'password' && form.elements.password_confirmation.value) {
                    setError(form.elements.password_confirmation, check(form.elements.password_confirmation));
                }
            });
            el.addEventListener(el.type === 'checkbox' ? 'change' : 'blur', function () {
                touched.add(el);
                setError(el, check(el));
            });
        });

        form.addEventListener('submit', function (e) {
            var first = null;
            fields.forEach(function (el) {
                touched.add(el);
                if (setError(el, check(el)) && !first) first = el;
            });
            if (first) { e.preventDefault(); first.focus(); }
        });
    })();
</script>
@endsection
