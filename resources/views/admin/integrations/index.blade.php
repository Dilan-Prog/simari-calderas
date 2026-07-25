@extends('admin.layouts.master')
@section('title')
    Integraciones - Admin
@endsection
@section('content')
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Integraciones
                    </p>
                    <h1>Integraciones</h1>
                    <p class="breadcrumb-clients-manager main">Credenciales de servicios externos: correo SMTP y futuras integraciones</p>
                </div>
            </header>

            @if (session('success'))
                <div class="pform-panel" style="border-left:4px solid #10b981;margin-top:16px;">
                    <p style="margin:0;color:#047857;font-weight:600;">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('mail_test_success'))
                <div class="pform-panel" style="border-left:4px solid #10b981;margin-top:16px;">
                    <p style="margin:0;color:#047857;font-weight:600;">{{ session('mail_test_success') }}</p>
                </div>
            @endif
            @if (session('mail_test_error'))
                <div class="pform-panel" style="border-left:4px solid #ef4444;margin-top:16px;">
                    <p style="margin:0;color:#b91c1c;font-weight:600;">{{ session('mail_test_error') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="pform-panel" style="border-left:4px solid #ef4444;margin-top:16px;">
                    @foreach ($errors->all() as $error)
                        <p style="margin:0;color:#b91c1c;font-weight:600;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.integrations.update') }}">
                @csrf
                @method('PUT')

                <div class="pform-panel-wrap" style="margin-top:20px;">
                    <div class="pform-panel">
                        <h2 class="pform-panel-title">Correo saliente (SMTP)</h2>
                        <p class="pform-hint" style="margin-bottom:16px;">
                            Con estos datos se envían los correos del sitio (recuperación de contraseña de clientes, etc.).
                            Si se dejan vacíos, se usa la configuración del servidor (.env).
                            Para Hostinger: servidor <code>smtp.hostinger.com</code>, puerto <code>465</code> con SSL.
                        </p>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_host">Servidor SMTP</label>
                            <input type="text" id="mail_host" name="mail_host" class="pform-input"
                                value="{{ old('mail_host', $values['mail.host']) }}" placeholder="smtp.hostinger.com">
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_port">Puerto</label>
                            <input type="number" id="mail_port" name="mail_port" class="pform-input"
                                value="{{ old('mail_port', $values['mail.port']) }}" placeholder="465" min="1" max="65535">
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_encryption">Cifrado</label>
                            <select id="mail_encryption" name="mail_encryption" class="pform-input">
                                @php $enc = old('mail_encryption', $values['mail.encryption'] ?? 'ssl'); @endphp
                                <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL (puerto 465)</option>
                                <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS / STARTTLS (puerto 587)</option>
                                <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>Sin cifrado</option>
                            </select>
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_username">Usuario (correo completo)</label>
                            <input type="text" id="mail_username" name="mail_username" class="pform-input"
                                value="{{ old('mail_username', $values['mail.username']) }}" placeholder="no-reply@equitermindustries.com.mx" autocomplete="off">
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_password">Contraseña</label>
                            <input type="password" id="mail_password" name="mail_password" class="pform-input"
                                placeholder="{{ $hasPassword ? '••••••••  (guardada — deja vacío para conservarla)' : 'Contraseña de la cuenta de correo' }}"
                                autocomplete="new-password">
                            <p class="pform-hint">Se guarda encriptada. Déjala vacía para no cambiarla.</p>
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_from_address">Correo remitente (From)</label>
                            <input type="email" id="mail_from_address" name="mail_from_address" class="pform-input"
                                value="{{ old('mail_from_address', $values['mail.from_address']) }}" placeholder="no-reply@equitermindustries.com.mx">
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="mail_from_name">Nombre del remitente</label>
                            <input type="text" id="mail_from_name" name="mail_from_name" class="pform-input"
                                value="{{ old('mail_from_name', $values['mail.from_name']) }}" placeholder="Equiterm Industries">
                        </div>

                        <button type="submit" class="pform-btn primary">Guardar configuración</button>
                    </div>
                </div>
            </form>

            <form method="POST" action="{{ route('admin.integrations.test-mail') }}">
                @csrf
                <div class="pform-panel-wrap" style="margin-top:20px;">
                    <div class="pform-panel">
                        <h2 class="pform-panel-title">Probar envío</h2>
                        <p class="pform-hint" style="margin-bottom:16px;">
                            Envía un correo de prueba usando la configuración <strong>guardada</strong>
                            (guarda primero si hiciste cambios).
                        </p>
                        <div class="pform-field">
                            <label class="pform-label" for="test_email">Enviar prueba a</label>
                            <input type="email" id="test_email" name="test_email" class="pform-input"
                                value="{{ old('test_email', auth()->user()->email ?? '') }}" placeholder="tucorreo@ejemplo.com">
                        </div>
                        <button type="submit" class="pform-btn primary">Enviar correo de prueba</button>
                    </div>
                </div>
            </form>
        </section>
    </div>
@endsection
