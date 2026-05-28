<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Equiterm Industries</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --orange:        #FF6213;
            --orange-dim:    rgba(255, 98, 19, 0.12);
            --orange-border: rgba(255, 98, 19, 0.25);
            --dark-l:        #1a1a1a;
            --dark-r:        #111111;
            --border:        rgba(255, 255, 255, 0.07);
            --border-input:  rgba(255, 255, 255, 0.12);
            --white:         #ffffff;
            --grey:          #888888;
            --grey-light:    #aaaaaa;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--dark-r);
            color: var(--white);
        }

        /* ── Accent bar ── */
        .accent-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--orange) 0%, #ff8c52 50%, var(--orange) 100%);
            z-index: 100;
        }

        /* ── Layout ── */
        .login-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ════════════════════════════
           LEFT PANEL
        ════════════════════════════ */
        .left-panel {
            background: var(--dark-l);
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 64px 56px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(255,98,19,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            bottom: -100px; left: -80px;
            width: 360px; height: 360px;
            background: radial-gradient(circle, rgba(255,98,19,0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Brand */
        .brand { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        /* Headline */
        .left-headline {
            font-size: 40px; font-weight: 700;
            line-height: 1.15; margin-bottom: 16px;
        }
        .left-headline em { font-style: normal; color: var(--orange); }
        .left-sub {
            font-size: 15px; color: var(--grey);
            line-height: 1.6; max-width: 340px; margin-bottom: 32px;
        }

        /* Chips */
        .chips { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 48px; }
        .chip {
            padding: 6px 14px; border-radius: 20px;
            border: 1px solid var(--orange-border);
            background: var(--orange-dim);
            color: var(--orange);
            font-size: 12px; font-weight: 600; letter-spacing: 0.04em;
        }

        /* Features */
        .features { display: flex; flex-direction: column; gap: 20px; margin-bottom: 52px; }
        .feature-row { display: flex; align-items: flex-start; gap: 16px; }
        .feature-icon {
            width: 40px; height: 40px;
            background: var(--orange-dim);
            border: 1px solid var(--orange-border);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-size: 18px;
        }
        .feature-text { display: flex; flex-direction: column; gap: 2px; }
        .feature-title { font-size: 14px; font-weight: 600; color: var(--white); }
        .feature-sub   { font-size: 12px; color: var(--grey); line-height: 1.5; }

        /* Status badge */
        .status-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 12px; color: var(--grey-light); font-weight: 500;
            width: fit-content;
        }
        .pulse-dot {
            width: 8px; height: 8px;
            background: #22c55e;
            border-radius: 50%;
            position: relative; flex-shrink: 0;
        }
        .pulse-dot::after {
            content: '';
            position: absolute; inset: -3px;
            border-radius: 50%;
            background: rgba(34,197,94,0.35);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.6); opacity: 0; }
        }

        /* ════════════════════════════
           RIGHT PANEL
        ════════════════════════════ */
        .right-panel {
            background: var(--dark-r);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 64px 48px;
            border-left: 1px solid var(--border);
        }
        .right-inner { width: 100%; max-width: 400px; }

        .right-heading   { font-size: 28px; font-weight: 700; color: var(--white); margin-bottom: 6px; }
        .right-subheading{ font-size: 14px; color: var(--grey); margin-bottom: 36px; }

        /* Session status */
        .session-status {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.25);
            color: #4ade80; border-radius: 8px;
            padding: 10px 14px; font-size: 13px; margin-bottom: 20px;
        }

        /* Form groups */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; color: var(--grey-light); margin-bottom: 8px; }

        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--grey); pointer-events: none;
            display: flex; align-items: center;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border-input);
            border-radius: 8px;
            padding: 12px 14px 12px 42px;
            font-size: 14px; font-family: inherit;
            color: var(--white); outline: none;
            transition: all 0.2s ease;
        }
        .form-input::placeholder { color: var(--grey); }
        .form-input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 3px rgba(255,98,19,0.18);
            background: rgba(255,255,255,0.06);
        }
        .form-input.is-error { border-color: #ef4444; }
        .error-msg { display: block; font-size: 12px; color: #f87171; margin-top: 5px; }

        /* Password toggle */
        .toggle-pw {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--grey); cursor: pointer; padding: 0;
            display: flex; align-items: center;
            transition: color 0.2s ease;
        }
        .toggle-pw:hover { color: var(--white); }
        .form-input.has-toggle { padding-right: 42px; }

        /* Remember + Forgot */
        .remember-row {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 24px;
        }
        .remember-check { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember-check input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--orange); cursor: pointer;
        }
        .remember-check span { font-size: 13px; color: var(--grey-light); }
        .forgot-link {
            font-size: 13px; color: var(--orange);
            text-decoration: none; font-weight: 500;
            transition: opacity 0.2s ease;
        }
        .forgot-link:hover { opacity: 0.75; }

        /* CTA */
        .btn-primary {
            width: 100%; background: var(--orange);
            color: #fff; border: none; border-radius: 10px;
            padding: 14px; font-size: 15px; font-weight: 600;
            font-family: inherit; cursor: pointer;
            transition: all 0.2s ease; margin-bottom: 28px;
        }
        .btn-primary:hover {
            background: #e0530e;
            box-shadow: 0 4px 20px rgba(255,98,19,0.4);
        }

        /* Divider */
        .divider { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .divider-line { flex: 1; height: 1px; background: var(--border-input); }
        .divider-text { font-size: 12px; color: var(--grey); white-space: nowrap; }

        /* OAuth */
        .oauth-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 36px; }
        .btn-ghost {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px 14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--border-input);
            border-radius: 10px; color: var(--grey-light);
            font-size: 13px; font-weight: 500; font-family: inherit;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.12); color: var(--white);
            box-shadow: 0 0 18px rgba(255,255,255,0.06);
        }
        .btn-ghost svg { width: 18px; height: 18px; flex-shrink: 0; }

        /* Footer */
        .right-footer {
            text-align: center; font-size: 12px; color: var(--grey);
            border-top: 1px solid var(--border); padding-top: 20px;
        }
        .right-footer a { color: var(--grey); text-decoration: none; transition: color 0.2s ease; }
        .right-footer a:hover { color: var(--grey-light); }
        .right-footer-links { display: flex; justify-content: center; gap: 16px; margin-top: 8px; }

        /* Responsive */
        @media (max-width: 900px) {
            .login-layout { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { border-left: none; }
        }
    </style>
</head>
<body>

    <div class="accent-bar"></div>

    <div class="login-layout">

        {{-- ═══ LEFT PANEL ═══ --}}
        <div class="left-panel">

            <div class="brand">
                <a href="{{ route('home') }}" aria-label="Ir al inicio">
                    <img class="header_logo" src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Logo Equiterm Industries" width="180" height="60">
                </a>
            </div>

            <h2 class="left-headline">Panel de gestión<br><em>industrial</em></h2>
            <p class="left-sub">
                Plataforma centralizada para supervisión de operaciones, análisis de datos
                y control de activos industriales en tiempo real.
            </p>

            <div class="chips">
                <span class="chip">Tiempo Real</span>
                <span class="chip">Seguro</span>
                <span class="chip">Analíticas</span>
            </div>

            <div class="features">
                <div class="feature-row">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">
                        <span class="feature-title">Monitoreo en tiempo real</span>
                        <span class="feature-sub">Visualiza métricas clave y KPIs de producción al instante.</span>
                    </div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">🔐</div>
                    <div class="feature-text">
                        <span class="feature-title">Acceso seguro por roles</span>
                        <span class="feature-sub">Control granular de permisos para cada área operativa.</span>
                    </div>
                </div>
                <div class="feature-row">
                    <div class="feature-icon">⚙️</div>
                    <div class="feature-text">
                        <span class="feature-title">Gestión de activos</span>
                        <span class="feature-sub">Administra equipos, mantenimientos y órdenes de trabajo.</span>
                    </div>
                </div>
            </div>

            <div class="status-badge">
                <span class="pulse-dot"></span>
                v3.2.1 — Sistema activo
            </div>

        </div>

        {{-- ═══ RIGHT PANEL ═══ --}}
        <div class="right-panel">
            <div class="right-inner">

                <h1 class="right-heading">Iniciar sesión</h1>
                <p class="right-subheading">Ingresa tus credenciales para acceder al panel</p>

                <x-auth-session-status class="session-status" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                            </span>
                            <input
                                id="email" type="email" name="email"
                                class="form-input @error('email') is-error @enderror"
                                value="{{ old('email') }}"
                                placeholder="usuario@equiterm.com"
                                required autofocus autocomplete="username">
                        </div>
                        @error('email')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group">
                        <label class="form-label" for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input
                                id="password" type="password" name="password"
                                class="form-input has-toggle @error('password') is-error @enderror"
                                placeholder="••••••••"
                                required autocomplete="current-password">
                            <button type="button" class="toggle-pw" id="togglePw" aria-label="Mostrar contraseña">
                                <svg id="eyeOpen" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg id="eyeClosed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="error-msg">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Remember + Forgot --}}
                    <div class="remember-row">
                        <label class="remember-check">
                            <input type="checkbox" name="remember" id="remember_me" {{ old('remember') ? 'checked' : '' }}>
                            <span>Recordarme</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">¿Olvidaste tu contraseña?</a>
                        @endif
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn-primary">
                        Iniciar Sesión &nbsp;→
                    </button>

                </form>

                <div class="divider">
                    <div class="divider-line"></div>
                    <span class="divider-text">o continúa con</span>
                    <div class="divider-line"></div>
                </div>

                <div class="oauth-row">
                    <button type="button" class="btn-ghost">
                        <svg viewBox="0 0 21 21" xmlns="http://www.w3.org/2000/svg">
                            <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                            <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                        </svg>
                        Microsoft
                    </button>
                    <button type="button" class="btn-ghost">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                </div>

                <div class="right-footer">
                    <span>&copy; {{ date('Y') }} Equiterm Industries. Todos los derechos reservados.</span>
                    <div class="right-footer-links">
                        <a href="#">Privacidad</a>
                        <a href="#">Términos</a>
                        <a href="mailto:soporte@equiterm.com">Soporte</a>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        const pwInput   = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePw');
        const eyeOpen   = document.getElementById('eyeOpen');
        const eyeClosed = document.getElementById('eyeClosed');

        toggleBtn.addEventListener('click', () => {
            const visible = pwInput.type === 'text';
            pwInput.type            = visible ? 'password' : 'text';
            eyeOpen.style.display   = visible ? 'block' : 'none';
            eyeClosed.style.display = visible ? 'none'  : 'block';
        });

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', () => input.style.borderColor = 'var(--orange)');
            input.addEventListener('blur',  () => {
                if (!input.classList.contains('is-error')) input.style.borderColor = '';
            });
        });
    </script>

</body>
</html>
