<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — SIMARI Calderas</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body class="login-body">

    {{-- Destellos decorativos de fondo --}}
    <div class="login-bg-glow top-right"></div>
    <div class="login-bg-glow bottom-left"></div>

    <div class="login-wrapper">

        {{-- Logo --}}
        <div class="login-logo">
            <img src="{{ asset('images/logo/Logo_blanco.png') }}"
                 alt="SIMARI Calderas"
                 onerror="this.style.display='none'">
        </div>

        {{-- Tarjeta --}}
        <div class="login-card">

            <div class="login-card-header">
                <span class="login-role-badge">Panel Administrativo</span>
                <h1 class="login-card-title">Bienvenido de nuevo</h1>
                <p class="login-card-subtitle">Ingresa tus credenciales para continuar</p>
            </div>

            {{-- Estado de sesión --}}
            @if (session('status'))
                <div class="login-session-status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                {{-- Email --}}
                <div class="login-form-group">
                    <label class="login-label" for="email">Correo electrónico</label>
                    <div class="login-input-wrapper">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            class="login-input {{ $errors->has('email') ? 'error' : '' }}"
                            value="{{ old('email') }}"
                            placeholder="admin@simari.com"
                            required
                            autofocus
                            autocomplete="username">
                    </div>
                    @if ($errors->has('email'))
                        <span class="login-error-msg">{{ $errors->first('email') }}</span>
                    @endif
                </div>

                {{-- Contraseña --}}
                <div class="login-form-group">
                    <div class="login-row">
                        <label class="login-label" for="password" style="margin-bottom:0">
                            Contraseña
                        </label>
                        <a href="{{ route('password.request') }}" class="login-forgot-link">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <div class="login-input-wrapper" style="margin-top:8px">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="login-input {{ $errors->has('password') ? 'error' : '' }}"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password">
                    </div>
                    @if ($errors->has('password'))
                        <span class="login-error-msg">{{ $errors->first('password') }}</span>
                    @endif
                </div>

                {{-- Recordarme --}}
                <div class="login-remember">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Mantener sesión iniciada</label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="login-btn">
                    Iniciar sesión
                </button>

            </form>

        </div>

        {{-- Pie --}}
        <p class="login-footer-text">
            ¿No tienes acceso?
            <a href="mailto:soporte@simari.com">Contacta al administrador</a>
        </p>

    </div>

</body>
</html>
