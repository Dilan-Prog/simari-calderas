@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/payment-methods.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&display=swap" rel="stylesheet">
@endpush
@section('title')
    Integraciones - Admin
@endsection
@section('content')
    @php
        // "Configurada" = hay contraseña SMTP guardada (señal concreta y real,
        // a diferencia de solo tener host, que puede quedar vacío y caer al
        // fallback de .env sin que eso signifique nada operativo).
        $smtpConfigured = $hasPassword;
        // La antigua pantalla standalone "Cuentas de WhatsApp" ahora vive
        // aquí como 2 paneles (API vs Web/QR) -- cada uno con su propio
        // estado "conectada"/"sin configurar" en el sidebar, en vez del
        // resumen combinado que había antes.
        $metaWhatsappConfigured = $metaWhatsappActiveCount > 0;
        // QR-AISLADO · OBLIGATORIO NO TOCAR: // $qrWhatsappConfigured = $qrWhatsappConnectedCount > 0;
        $webhooksConfigured = $webhooks->count() > 0;
        // $mercadoPagoConfigured ya llega calculado desde IntegrationController::index()
        // (un sub-array por rol/aplicación de Mercado Pago -- ver $mercadoPago).

        $bounceWebhookUrl = route('webhooks.email-bounce');
        $whatsappWebhookUrl = route('whatsapp.webhook.verify');
        $mercadoPagoWebhookUrl = route('webhooks.mercadopago');
    @endphp
    <div class="container user-manager">
        <section class="clients-manager-section">

            {{-- Header --}}
            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Integraciones
                    </p>
                    <h1>Integraciones</h1>
                    <p class="breadcrumb-clients-manager main">Correo SMTP, WhatsApp Business, Webhooks salientes y Mercado Pago</p>
                </div>
            </header>

            @if (session('success'))
                <div class="pform-panel" style="border-left:4px solid #10b981;margin-top:16px;">
                    <p style="margin:0;color:#047857;font-weight:600;">{{ session('success') }}</p>
                </div>
            @endif
            {{-- Resultado de "Probar envío": aviso emergente (no una barra arriba de la
                 página) para que se vea aunque la vista se restaure más abajo. --}}
            @foreach (['mail_test_success' => 'success', 'mail_test_error' => 'error'] as $flashKey => $flashType)
                @if (session($flashKey))
                    <div class="toast-notification toast-{{ $flashType }} integr-mail-toast" role="status">
                        <div class="toast-body">
                            <p class="toast-title">{{ $flashType === 'success' ? 'Prueba de correo' : 'Falló la prueba de correo' }}</p>
                            <p class="toast-message">{{ session($flashKey) }}</p>
                        </div>
                        <button type="button" class="toast-close"
                            onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>
                    </div>
                @endif
            @endforeach
            @if ($errors->any())
                <div class="pform-panel" style="border-left:4px solid #ef4444;margin-top:16px;">
                    @foreach ($errors->all() as $error)
                        <p style="margin:0;color:#b91c1c;font-weight:600;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Hub de dos columnas: sidebar de secciones + panel de contenido.
                 Toggle 100% CSS-class + JS vanilla (sin framework), mismo
                 criterio "sin dependencias nuevas" del resto del admin. --}}
            <div class="integr-hub">
                <nav class="integr-sidebar" id="integrSidebar">
                    <button type="button" class="integr-sidebar-item active" data-panel="smtp">
                        <span class="integr-avatar">SM</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">Correo SMTP</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $smtpConfigured ? 'is-on' : '' }}"></i>
                                {{ $smtpConfigured ? 'Conectada' : 'Sin configurar' }}
                            </span>
                        </span>
                    </button>
                    <button type="button" class="integr-sidebar-item" data-panel="whatsapp-api">
                        <span class="integr-avatar">WA</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">WhatsApp API</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $metaWhatsappConfigured ? 'is-on' : '' }}"></i>
                                {{ $metaWhatsappConfigured ? $metaWhatsappActiveCount . ' cuenta' . ($metaWhatsappActiveCount === 1 ? '' : 's') . ' activa' . ($metaWhatsappActiveCount === 1 ? '' : 's') : 'Sin configurar' }}
                            </span>
                        </span>
                    </button>
                    {{-- QR-AISLADO · OBLIGATORIO NO TOCAR · INICIO — vía WhatsApp Web/Baileys (QR, NO oficial) DESACTIVADA: se usa SOLO la API oficial de Meta. Bloque envuelto en @if (false) para no interferir con el código original. No borrar ni reactivar sin consultar. --}}
                    @if (false)
                    <button type="button" class="integr-sidebar-item" data-panel="whatsapp-web">
                        <span class="integr-avatar">WW</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">WhatsApp Web</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $qrWhatsappConfigured ? 'is-on' : '' }}"></i>
                                {{ $qrWhatsappConfigured ? $qrWhatsappConnectedCount . ' conectada' . ($qrWhatsappConnectedCount === 1 ? '' : 's') : 'Sin conectar' }}
                            </span>
                        </span>
                    </button>
                    @endif
                    {{-- QR-AISLADO · OBLIGATORIO NO TOCAR · FIN --}}
                    <button type="button" class="integr-sidebar-item" data-panel="webhooks">
                        <span class="integr-avatar">WH</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">Webhooks</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $webhooksConfigured ? 'is-on' : '' }}"></i>
                                {{ $webhooksConfigured ? $webhooks->count() . ' registrado' . ($webhooks->count() === 1 ? '' : 's') : 'Sin webhooks' }}
                            </span>
                        </span>
                    </button>
                    <button type="button" class="integr-sidebar-item" data-panel="mercadopago">
                        <span class="integr-avatar">MP</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">Mercado Pago</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $mercadoPagoConfigured ? 'is-on' : '' }}"></i>
                                {{ $mercadoPagoConfigured ? 'Conectada' : 'Sin configurar' }}
                            </span>
                        </span>
                    </button>
                    <button type="button" class="integr-sidebar-item" data-panel="api-clients">
                        <span class="integr-avatar">N8</span>
                        <span class="integr-sidebar-item-text">
                            <span class="integr-sidebar-item-title">API / N8N</span>
                            <span class="integr-sidebar-item-status">
                                <i class="integr-dot {{ $apiClientsConfigured ? 'is-on' : '' }}"></i>
                                {{ $apiClientsConfigured ? $apiClients->count() . ' cliente' . ($apiClients->count() === 1 ? '' : 's') : 'Sin configurar' }}
                            </span>
                        </span>
                    </button>
                </nav>

                <div class="integr-content">

                    {{-- ============ Panel 1: Correo SMTP ============ --}}
                    <div class="integr-panel active" id="integrPanel-smtp">
                        <div class="integr-panel-card">
                            <div class="integr-panel-header">
                                <div class="integr-panel-avatar">SM</div>
                                <div class="integr-panel-header-text">
                                    <div class="integr-panel-title-row">
                                        <h2>Correo SMTP</h2>
                                        <span class="integr-badge {{ $smtpConfigured ? 'is-on' : '' }}">
                                            {{ $smtpConfigured ? 'Conectada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <p class="integr-panel-desc">
                                        Envía los correos transaccionales del sitio (recuperación de contraseña de
                                        clientes, notificaciones) a través del servidor SMTP configurado en la
                                        pestaña Credenciales.
                                    </p>
                                </div>
                            </div>

                            <div class="integr-tabs">
                                <button type="button" class="integr-tab active" data-tab="credenciales">Credenciales</button>
                                <button type="button" class="integr-tab" data-tab="avanzado">Avanzado</button>
                            </div>

                            {{-- ---- Credenciales: forms reales de SMTP (sin cambios de lógica) ---- --}}
                            <div class="integr-tab-content active" data-tab-content="credenciales">
                                <form method="POST" action="{{ route('admin.integrations.update') }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="pform-panel-wrap">
                                        <div class="pform-panel">
                                            <h2 class="pform-panel-title">Correo saliente (SMTP)</h2>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Con estos datos se envían los correos del sitio (recuperación de
                                                contraseña de clientes, etc.).
                                                Si se dejan vacíos, se usa la configuración del servidor (.env).
                                                Para Hostinger: servidor <code>smtp.hostinger.com</code>, puerto
                                                <code>465</code> con SSL.
                                            </p>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_host">Servidor SMTP</label>
                                                <input type="text" id="mail_host" name="mail_host" class="pform-input"
                                                    value="{{ old('mail_host', $values['mail.host']) }}"
                                                    placeholder="smtp.hostinger.com">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_port">Puerto</label>
                                                <input type="number" id="mail_port" name="mail_port" class="pform-input"
                                                    value="{{ old('mail_port', $values['mail.port']) }}" placeholder="465"
                                                    min="1" max="65535">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_encryption">Cifrado</label>
                                                <select id="mail_encryption" name="mail_encryption" class="pform-input">
                                                    @php $enc = old('mail_encryption', $values['mail.encryption'] ?? 'ssl'); @endphp
                                                    <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL (puerto
                                                        465)</option>
                                                    <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS / STARTTLS
                                                        (puerto 587)</option>
                                                    <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>Sin cifrado
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_username">Usuario (correo completo)</label>
                                                <input type="text" id="mail_username" name="mail_username" class="pform-input"
                                                    value="{{ old('mail_username', $values['mail.username']) }}"
                                                    placeholder="no-reply@equitermindustries.com.mx" autocomplete="off">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_password">Contraseña</label>
                                                <input type="password" id="mail_password" name="mail_password"
                                                    class="pform-input"
                                                    placeholder="{{ $hasPassword ? '••••••••  (guardada — deja vacío para conservarla)' : 'Contraseña de la cuenta de correo' }}"
                                                    autocomplete="new-password">
                                                <p class="pform-hint">Se guarda encriptada. Déjala vacía para no cambiarla.</p>
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_from_address">Correo remitente
                                                    (From)</label>
                                                <input type="email" id="mail_from_address" name="mail_from_address"
                                                    class="pform-input"
                                                    value="{{ old('mail_from_address', $values['mail.from_address']) }}"
                                                    placeholder="no-reply@equitermindustries.com.mx">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_from_name">Nombre del remitente</label>
                                                <input type="text" id="mail_from_name" name="mail_from_name"
                                                    class="pform-input"
                                                    value="{{ old('mail_from_name', $values['mail.from_name']) }}"
                                                    placeholder="Equiterm Industries">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_signature">Firma de los correos que se envían desde la bandeja</label>
                                                <textarea id="mail_signature" name="mail_signature" class="pform-input"
                                                    rows="4" maxlength="1000"
                                                    placeholder="Atentamente,&#10;Equipo de ventas&#10;Equiterm Industries">{{ old('mail_signature', $values['mail.signature']) }}</textarea>
                                                <p class="pform-hint">Opcional. Texto plano (máx. 1000 caracteres); se agrega al final de cada correo que envíes desde la bandeja de leads.</p>
                                            </div>

                                            <h3 class="pform-panel-title" style="font-size:15px;margin:24px 0 8px;padding-top:16px;border-top:1px solid var(--erp-border, #e5e7eb);">Buzón de la bandeja de leads (respuestas a clientes)</h3>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Normalmente <code>administracion@equitermindustries.com.mx</code> con la contraseña de ese buzón;
                                                si se deja vacío, las respuestas salen con el remitente general.
                                                Usa el mismo servidor, puerto y cifrado del SMTP de arriba.
                                            </p>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_inbox_username">Usuario del buzón (correo completo)</label>
                                                <input type="text" id="mail_inbox_username" name="mail_inbox_username" class="pform-input"
                                                    value="{{ old('mail_inbox_username', $values['mail.inbox.username']) }}"
                                                    maxlength="190" placeholder="administracion@equitermindustries.com.mx" autocomplete="off">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_inbox_password">Contraseña del buzón</label>
                                                <input type="password" id="mail_inbox_password" name="mail_inbox_password"
                                                    class="pform-input"
                                                    placeholder="{{ $hasInboxPassword ? '••••••••  (contraseña guardada — deja vacío para conservarla)' : 'Contraseña del buzón' }}"
                                                    autocomplete="new-password">
                                                <p class="pform-hint">
                                                    @if ($hasInboxPassword)<strong>Contraseña guardada.</strong> @endif
                                                    Se guarda encriptada. Déjala vacía para no cambiarla.
                                                </p>
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_inbox_from_address">Correo remitente de las respuestas (From / Reply-To)</label>
                                                <input type="email" id="mail_inbox_from_address" name="mail_inbox_from_address"
                                                    class="pform-input"
                                                    value="{{ old('mail_inbox_from_address', $values['mail.inbox.from_address']) }}"
                                                    placeholder="administracion@equitermindustries.com.mx">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="mail_inbox_from_name">Nombre del remitente de las respuestas</label>
                                                <input type="text" id="mail_inbox_from_name" name="mail_inbox_from_name"
                                                    class="pform-input"
                                                    value="{{ old('mail_inbox_from_name', $values['mail.inbox.from_name']) }}"
                                                    placeholder="Equiterm Industries">
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
                                                    value="{{ old('test_email', auth()->user()->email ?? '') }}"
                                                    placeholder="tucorreo@ejemplo.com">
                                            </div>
                                            <button type="submit" class="pform-btn primary">Enviar correo de prueba</button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('admin.integrations.test-quote-mail') }}">
                                    @csrf
                                    <div class="pform-panel-wrap" style="margin-top:20px;">
                                        <div class="pform-panel">
                                            <h2 class="pform-panel-title">Probar correo de cotización (con PDF)</h2>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Diagnóstico: manda el mismo tipo de correo que usan las automatizaciones
                                                (plantilla + PDF adjunto real) usando una cotización al azar de tu base
                                                de datos, a la dirección que pongas — para confirmar si el adjunto
                                                realmente llega.
                                            </p>
                                            <div class="pform-field">
                                                <label class="pform-label" for="test_quote_email">Enviar prueba a</label>
                                                <input type="email" id="test_quote_email" name="test_email" class="pform-input"
                                                    value="{{ old('test_email', auth()->user()->email ?? '') }}"
                                                    placeholder="tucorreo@ejemplo.com">
                                            </div>
                                            <button type="submit" class="pform-btn primary">Enviar correo de cotización de prueba</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            {{-- ---- Avanzado: webhook real de rebote (bounce) de Hostinger ---- --}}
                            <div class="integr-tab-content" data-tab-content="avanzado">
                                <div class="pform-panel-wrap">
                                    <div class="pform-panel">
                                        <h2 class="pform-panel-title">Webhook de rebotes (bounce)</h2>
                                        <p class="pform-hint" style="margin-bottom:16px;">
                                            Hostinger "Agentic Mail" notifica a esta URL cuando un correo enviado
                                            rebota, para que el sistema pueda registrarlo.
                                        </p>
                                        <div class="pform-field">
                                            <label class="pform-label">Callback URL</label>
                                            <div class="integr-copy-row">
                                                <input type="text" class="pform-input" value="{{ $bounceWebhookUrl }}" readonly>
                                                <button type="button" class="pform-btn outline integr-copy-btn"
                                                    data-value="{{ $bounceWebhookUrl }}">Copiar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ Panel 2a: WhatsApp API (Meta Cloud API) ============
                         Fusión de la antigua pantalla standalone
                         admin.whatsapp-accounts.index -- tabla+CRUD reales
                         filtrados a connection_type=meta_cloud_api. Backend
                         sin cambios: WhatsappAccountController. --}}
                    <div class="integr-panel" id="integrPanel-whatsapp-api">
                        <div class="integr-panel-card">
                            <div class="integr-panel-header">
                                <div class="integr-panel-avatar">WA</div>
                                <div class="integr-panel-header-text">
                                    <div class="integr-panel-title-row">
                                        <h2>WhatsApp API</h2>
                                        <span class="integr-badge {{ $metaWhatsappConfigured ? 'is-on' : '' }}">
                                            {{ $metaWhatsappConfigured ? 'Conectada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <p class="integr-panel-desc">
                                        Números de WhatsApp Business conectados vía Meta Cloud API, usados por el
                                        Embudo de Venta y el webhook público para enviar y recibir mensajes.
                                    </p>
                                </div>
                            </div>

                            <div class="integr-tabs">
                                <button type="button" class="integr-tab active" data-tab="credenciales">Credenciales</button>
                                <button type="button" class="integr-tab" data-tab="avanzado">Avanzado</button>
                                <button type="button" class="integr-tab" data-tab="prueba">Probar conexión</button>
                            </div>

                            <div class="integr-tab-content active" data-tab-content="credenciales">
                                <p class="ap-readonly-note" style="margin-bottom:12px;">
                                    El token de acceso se guarda cifrado. Por seguridad, el valor guardado nunca se
                                    muestra de nuevo — solo puedes reemplazarlo al editar.
                                </p>

                                <header class="clients-manager-main" style="margin-bottom:4px;">
                                    <div>
                                        <h2 class="pform-panel-title" style="margin:0;">Cuentas WhatsApp API</h2>
                                        <p class="pform-hint">Números conectados vía Meta Cloud API</p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @permiso('whatsapp', 'create')
                                        <button type="button" class="button-primary size-adjustment"
                                            id="btnNewWhatsappApiAccount">
                                            + Nueva cuenta
                                        </button>
                                        @endpermiso
                                    </div>
                                </header>

                                <main class="table-container-clients-manager" style="margin-top:16px;">
                                    <div class="table-scroll">
                                        <table class="clients-manager-table">
                                            <thead>
                                                <tr>
                                                    <th>NOMBRE</th>
                                                    <th>NÚMERO</th>
                                                    <th>PHONE NUMBER ID</th>
                                                    <th>ESTADO</th>
                                                    <th>ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="whatsappApiAccountsTableBody">
                                                @forelse ($metaWhatsappAccounts as $account)
                                                    <tr class="whatsapp-account-row" data-id="{{ $account->id }}">
                                                        <td>
                                                            <p class="pm-name">{{ $account->name }}</p>
                                                        </td>
                                                        <td class="pm-type">
                                                            {{ $account->phone_number ?? '—' }}
                                                        </td>
                                                        <td class="pm-type">
                                                            {{ $account->phone_number_id ?? '—' }}
                                                        </td>
                                                        <td>
                                                            @if ($account->is_active)
                                                                <span class="status-badge status-active">Activa</span>
                                                            @else
                                                                <span class="status-badge status-inactive">Inactiva</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="actions-container">
                                                                @permiso('whatsapp', 'edit')
                                                                <button type="button" class="action-btn btn-edit-whatsapp-account"
                                                                    data-id="{{ $account->id }}" title="Editar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path
                                                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                                    </svg>
                                                                </button>
                                                                @endpermiso
                                                                @permiso('whatsapp', 'delete')
                                                                <button type="button" class="action-btn btn-delete-whatsapp-account"
                                                                    data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                                                                    title="Eliminar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M3 6h18" />
                                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                                        <line x1="10" x2="10" y1="11" y2="17" />
                                                                        <line x1="14" x2="14" y1="11" y2="17" />
                                                                    </svg>
                                                                </button>
                                                                @endpermiso
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                                            No hay cuentas de WhatsApp API registradas.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </main>
                            </div>

                            {{-- ---- Avanzado: datos del webhook, necesarios al configurar el
                                 número en Meta for Developers > WhatsApp > Configuration. La
                                 URL se calcula con route(), así que siempre refleja el dominio
                                 real donde corre la app. --}}
                            <div class="integr-tab-content" data-tab-content="avanzado">
                                <div class="pform-panel-wrap">
                                    <div class="pform-panel">
                                        <h2 class="pform-panel-title">Webhook de WhatsApp API</h2>
                                        <p class="pform-hint" style="margin-bottom:16px;">
                                            URL que se registra en Meta for Developers &gt; WhatsApp &gt;
                                            Configuration. El "Token de verificación" que Meta pide es el mismo que
                                            cada cuenta guarda en su campo "Token de verificación del webhook"
                                            (pestaña Credenciales, arriba — edita la cuenta para verlo).
                                        </p>
                                        <div class="pform-field">
                                            <label class="pform-label">Callback URL</label>
                                            <div class="integr-copy-row">
                                                <input type="text" class="pform-input" value="{{ $whatsappWebhookUrl }}" readonly>
                                                <button type="button" class="pform-btn outline integr-copy-btn"
                                                    data-value="{{ $whatsappWebhookUrl }}">Copiar</button>
                                            </div>
                                        </div>
                                        @if (Str::startsWith($whatsappWebhookUrl, 'http://localhost') || Str::contains($whatsappWebhookUrl, '.test'))
                                            <p style="margin:8px 0 0; font-size:12px; color:#b45309;">
                                                Esta URL apunta a tu entorno local — Meta no puede alcanzarla. Configura
                                                el webhook hasta que el sitio esté publicado en un dominio real
                                                (Hostinger).
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ---- Probar conexión: verifica la cuenta contra Meta, manda un mensaje de prueba y muestra el estado
                                 del webhook (WhatsappConnectionTestController). --}}
                            <div class="integr-tab-content" data-tab-content="prueba">
                                @include('admin.integrations.partials._whatsapp_test_panel', ['accounts' => $metaWhatsappAccounts, 'webhookUrl' => $whatsappWebhookUrl])
                            </div>
                        </div>
                    </div>

                    {{-- QR-AISLADO · OBLIGATORIO NO TOCAR · INICIO — vía WhatsApp Web/Baileys (QR, NO oficial) DESACTIVADA: se usa SOLO la API oficial de Meta. Bloque envuelto en @if (false) para no interferir con el código original. No borrar ni reactivar sin consultar. --}}
                    @if (false)
                    {{-- ============ Panel 2b: WhatsApp Web (conexión por QR / Baileys) ============
                         Segunda mitad de la fusión de la antigua pantalla
                         standalone -- tabla+CRUD reales filtrados a
                         connection_type=baileys_qr, más el flujo de
                         escaneo/reconexión de QR. --}}
                    <div class="integr-panel" id="integrPanel-whatsapp-web">
                        <div class="integr-panel-card">
                            <div class="integr-panel-header">
                                <div class="integr-panel-avatar">WW</div>
                                <div class="integr-panel-header-text">
                                    <div class="integr-panel-title-row">
                                        <h2>WhatsApp Web</h2>
                                        <span class="integr-badge {{ $qrWhatsappConfigured ? 'is-on' : '' }}">
                                            {{ $qrWhatsappConfigured ? 'Conectada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <p class="integr-panel-desc">
                                        Números conectados escaneando un código QR desde WhatsApp en el teléfono —
                                        sin necesidad de configurar nada en Meta for Developers.
                                    </p>
                                </div>
                            </div>

                            <div class="integr-tabs">
                                <button type="button" class="integr-tab active" data-tab="credenciales">Credenciales</button>
                                <button type="button" class="integr-tab" data-tab="avanzado">Avanzado</button>
                            </div>

                            <div class="integr-tab-content active" data-tab-content="credenciales">
                                <header class="clients-manager-main" style="margin-bottom:4px;">
                                    <div>
                                        <h2 class="pform-panel-title" style="margin:0;">Cuentas WhatsApp Web</h2>
                                        <p class="pform-hint">Conectadas escaneando un código QR</p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @permiso('whatsapp', 'create')
                                        <button type="button" class="button-primary size-adjustment"
                                            id="btnNewWhatsappQrAccount">
                                            + Nueva cuenta
                                        </button>
                                        @endpermiso
                                    </div>
                                </header>

                                <main class="table-container-clients-manager" style="margin-top:16px;">
                                    <div class="table-scroll">
                                        <table class="clients-manager-table">
                                            <thead>
                                                <tr>
                                                    <th>NOMBRE</th>
                                                    <th>NÚMERO</th>
                                                    <th>ESTADO SESIÓN</th>
                                                    <th>ESTADO</th>
                                                    <th>ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="whatsappQrAccountsTableBody">
                                                @forelse ($qrWhatsappAccounts as $account)
                                                    <tr class="whatsapp-account-row" data-id="{{ $account->id }}">
                                                        <td>
                                                            <p class="pm-name">{{ $account->name }}</p>
                                                        </td>
                                                        <td class="pm-type">
                                                            {{ $account->phone_number ?? '—' }}
                                                        </td>
                                                        <td>
                                                            @php
                                                                $sessionLabels = [
                                                                    'connected' => ['Conectado', '#f0fff3', '#3cbe40', '#8bff8f'],
                                                                    'qr_pending' => ['Esperando QR', '#fffbeb', '#b45309', '#fde68a'],
                                                                    'disconnected' => ['Desconectado', '#f3f4f6', '#4b5563', '#acb5c1'],
                                                                ];
                                                                $sLabel = $sessionLabels[$account->session_status] ?? $sessionLabels['disconnected'];
                                                            @endphp
                                                            <span class="status-badge"
                                                                style="background:{{ $sLabel[1] }}; color:{{ $sLabel[2] }}; border:1px solid {{ $sLabel[3] }};">{{ $sLabel[0] }}</span>
                                                        </td>
                                                        <td>
                                                            @if ($account->is_active)
                                                                <span class="status-badge status-active">Activa</span>
                                                            @else
                                                                <span class="status-badge status-inactive">Inactiva</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="actions-container">
                                                                @if (empty($account->session_status) || $account->session_status === 'disconnected')
                                                                    @permiso('whatsapp', 'edit')
                                                                    <button type="button" class="action-btn btn-reconnect-whatsapp-account"
                                                                        data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                                                                        title="Reconectar">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
                                                                            <path d="M21 3v5h-5" />
                                                                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
                                                                            <path d="M3 21v-5h5" />
                                                                        </svg>
                                                                    </button>
                                                                    @endpermiso
                                                                @endif
                                                                @permiso('whatsapp', 'edit')
                                                                <button type="button" class="action-btn btn-edit-whatsapp-account"
                                                                    data-id="{{ $account->id }}" title="Editar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path
                                                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                                    </svg>
                                                                </button>
                                                                @endpermiso
                                                                @permiso('whatsapp', 'delete')
                                                                <button type="button" class="action-btn btn-delete-whatsapp-account"
                                                                    data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                                                                    title="Eliminar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M3 6h18" />
                                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                                        <line x1="10" x2="10" y1="11" y2="17" />
                                                                        <line x1="14" x2="14" y1="11" y2="17" />
                                                                    </svg>
                                                                </button>
                                                                @endpermiso
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                                            No hay cuentas de WhatsApp Web registradas.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </main>
                            </div>

                            <div class="integr-tab-content" data-tab-content="avanzado">
                                <div class="pform-panel-wrap">
                                    <div class="pform-panel">
                                        <h2 class="pform-panel-title">Sobre esta conexión</h2>
                                        <p class="pform-hint">
                                            No requiere configurar nada en Meta for Developers — la sesión se
                                            establece escaneando el código QR desde WhatsApp en el teléfono
                                            (Ajustes → Dispositivos vinculados → Vincular un dispositivo). No hay
                                            webhook que registrar manualmente.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    {{-- QR-AISLADO · OBLIGATORIO NO TOCAR · FIN --}}

                    {{-- ============ Panel 3: Webhooks (CRUD modal, patrón Credenciales) ============ --}}
                    <div class="integr-panel" id="integrPanel-webhooks">
                        <div class="integr-panel-card">
                            <div class="integr-panel-header">
                                <div class="integr-panel-avatar">WH</div>
                                <div class="integr-panel-header-text">
                                    <div class="integr-panel-title-row">
                                        <h2>Webhooks</h2>
                                        <span class="integr-badge {{ $webhooksConfigured ? 'is-on' : '' }}">
                                            {{ $webhooksConfigured ? 'Conectada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <p class="integr-panel-desc">
                                        Envíos salientes configurables (HTTP) que usan las automatizaciones del
                                        sistema para notificar a servicios externos.
                                    </p>
                                </div>
                            </div>

                            <div class="integr-tabs">
                                <button type="button" class="integr-tab active" data-tab="credenciales">Credenciales</button>
                                <button type="button" class="integr-tab" data-tab="avanzado">Avanzado</button>
                            </div>

                            <div class="integr-tab-content active" data-tab-content="credenciales">
                                <header class="clients-manager-main" style="margin-bottom:4px;">
                                    <div>
                                        <h2 class="pform-panel-title" style="margin:0;">Webhooks registrados</h2>
                                        <p class="pform-hint">Envíos salientes configurables usados por las
                                            automatizaciones del sistema</p>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <button type="button" class="button-primary size-adjustment" id="btnNewWebhook">
                                            + Nuevo
                                        </button>
                                    </div>
                                </header>

                                <main class="table-container-clients-manager" style="margin-top:16px;">
                                    <div class="table-scroll">
                                        <table class="clients-manager-table">
                                            <thead>
                                                <tr>
                                                    <th>NOMBRE</th>
                                                    <th>URL</th>
                                                    <th>MÉTODO</th>
                                                    <th>CREDENCIAL</th>
                                                    <th>ACCIONES</th>
                                                </tr>
                                            </thead>
                                            <tbody id="webhooksTableBody">
                                                @forelse ($webhooks as $webhook)
                                                    <tr class="webhook-row" data-id="{{ $webhook->id }}">
                                                        <td>
                                                            <p class="pm-name">{{ $webhook->name }}</p>
                                                        </td>
                                                        <td style="max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                            {{ $webhook->url }}
                                                        </td>
                                                        <td class="pm-type">
                                                            {{ $webhook->method }}
                                                        </td>
                                                        <td>
                                                            {{ $webhook->credential->name ?? '—' }}
                                                        </td>
                                                        <td>
                                                            <div class="actions-container">
                                                                <button type="button" class="action-btn btn-edit-webhook"
                                                                    data-id="{{ $webhook->id }}" title="Editar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                        height="16" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                        <path
                                                                            d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                                                                    </svg>
                                                                </button>
                                                                <button type="button" class="action-btn btn-delete-webhook"
                                                                    data-id="{{ $webhook->id }}"
                                                                    data-name="{{ $webhook->name }}" title="Eliminar">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                        height="16" viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2"
                                                                        stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M3 6h18" />
                                                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                                                        <line x1="10" x2="10" y1="11" y2="17" />
                                                                        <line x1="14" x2="14" y1="11" y2="17" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" style="text-align:center; padding:40px; color:#6b7280;">
                                                            No hay webhooks registrados.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </main>
                            </div>

                            <div class="integr-tab-content" data-tab-content="avanzado">
                                <div class="pform-panel-wrap">
                                    <div class="pform-panel">
                                        <h2 class="pform-panel-title">Sobre este endpoint</h2>
                                        <p class="pform-hint">
                                            Los webhooks son salientes: cada uno define su propia URL de destino en
                                            la pestaña Credenciales. No hay un endpoint de entrada compartido.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============ Panel: API / N8N ============ --}}
                    @include('admin.integrations.partials._api_clients_panel')

                    {{-- ============ Panel 4: Mercado Pago ============ --}}
                    <div class="integr-panel" id="integrPanel-mercadopago">
                        <div class="integr-panel-card">
                            <div class="integr-panel-header">
                                <div class="integr-panel-avatar">MP</div>
                                <div class="integr-panel-header-text">
                                    <div class="integr-panel-title-row">
                                        <h2>Mercado Pago</h2>
                                        <span class="integr-badge {{ $mercadoPagoConfigured ? 'is-on' : '' }}">
                                            {{ $mercadoPagoConfigured ? 'Conectada' : 'Sin configurar' }}
                                        </span>
                                    </div>
                                    <p class="integr-panel-desc">
                                        Mercado Pago emite credenciales por <strong>Aplicación</strong> (no una sola
                                        para toda la cuenta): el checkout público usa 2 aplicaciones distintas — una
                                        para Tarjeta (Checkout API/CardForm) y otra para la redirección de
                                        Wallet/Efectivo/Transferencia (Checkout Pro). Configura cada una por separado
                                        abajo.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.integrations.update-mercadopago') }}">
                                @csrf
                                @method('PUT')

                                @foreach ($mercadoPago as $role => $roleData)
                                    @php
                                        $mpMode = old("mercadopago.{$role}.mode", $roleData['mode']);
                                        $fid = fn (string $field) => "mercadopago_{$role}_{$field}"; // ids únicos por rol para los <label for>
                                    @endphp

                                    <div class="pform-panel-wrap">
                                        <div class="pform-panel">
                                            <div class="pform-panel-title-row">
                                                <h2 class="pform-panel-title">{{ $roleData['label'] }}</h2>
                                                <span class="integr-badge {{ $roleData['configured'] ? 'is-on' : '' }}">
                                                    {{ $roleData['configured'] ? 'Conectada' : 'Sin configurar' }}
                                                </span>
                                            </div>
                                            <p class="pform-hint" style="margin-bottom:12px;">
                                                Puedes guardar los 2 juegos de credenciales a la vez (sandbox y
                                                producción) — este interruptor decide cuál de los 2 usa de verdad
                                                el checkout ahora mismo para esta aplicación específica.
                                            </p>

                                            <input type="hidden" name="mercadopago[{{ $role }}][mode]" id="{{ $fid('mode') }}" value="{{ $mpMode }}">
                                            <div class="mp-mode-toggle" role="group" aria-label="Modo de {{ $roleData['label'] }}" data-mp-mode-toggle-for="{{ $fid('mode') }}">
                                                <button type="button" class="mp-mode-btn {{ $mpMode === 'sandbox' ? 'active' : '' }}"
                                                    data-mp-mode="sandbox">Sandbox (pruebas)</button>
                                                <button type="button" class="mp-mode-btn {{ $mpMode === 'live' ? 'active' : '' }}"
                                                    data-mp-mode="live">Producción (live)</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pform-panel-wrap">
                                        <div class="pform-panel">
                                            <div class="pform-panel-title-row">
                                                <h2 class="pform-panel-title">Credenciales de prueba (sandbox)</h2>
                                                @if ($roleData['mode'] === 'sandbox')
                                                    <span class="integr-badge is-on">Activo</span>
                                                @endif
                                            </div>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Pestaña "Credenciales de prueba" de la aplicación <strong>{{ $roleData['label'] }}</strong>
                                                en el panel de desarrolladores de Mercado Pago.
                                            </p>

                                            <div class="pform-field">
                                                <label class="pform-label" for="{{ $fid('sandbox_public_key') }}">Public Key (prueba)</label>
                                                <input type="text" id="{{ $fid('sandbox_public_key') }}" name="mercadopago[{{ $role }}][sandbox_public_key]"
                                                    class="pform-input"
                                                    value="{{ old("mercadopago.{$role}.sandbox_public_key", $roleData['sandbox_public_key']) }}"
                                                    placeholder="TEST-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                                    autocomplete="off">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="{{ $fid('sandbox_access_token') }}">Access Token (prueba)</label>
                                                <input type="password" id="{{ $fid('sandbox_access_token') }}"
                                                    name="mercadopago[{{ $role }}][sandbox_access_token]" class="pform-input"
                                                    placeholder="{{ $roleData['has_sandbox_access_token'] ? '••••••••  (guardado — deja vacío para conservarlo)' : 'TEST-xxxxxxxxxxxxxxxxxxxxxxxxx' }}"
                                                    autocomplete="new-password">
                                                <p class="pform-hint">Se guarda encriptado. Déjalo vacío para no cambiarlo.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pform-panel-wrap">
                                        <div class="pform-panel">
                                            <div class="pform-panel-title-row">
                                                <h2 class="pform-panel-title">Credenciales de producción (live)</h2>
                                                @if ($roleData['mode'] === 'live')
                                                    <span class="integr-badge is-on">Activo</span>
                                                @endif
                                            </div>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Pestaña "Credenciales de producción" de esta misma aplicación — solo se
                                                usan de verdad cuando el modo de arriba está en "Producción (live)".
                                            </p>

                                            <div class="pform-field">
                                                <label class="pform-label" for="{{ $fid('live_public_key') }}">Public Key (producción)</label>
                                                <input type="text" id="{{ $fid('live_public_key') }}" name="mercadopago[{{ $role }}][live_public_key]"
                                                    class="pform-input"
                                                    value="{{ old("mercadopago.{$role}.live_public_key", $roleData['live_public_key']) }}"
                                                    placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                                    autocomplete="off">
                                            </div>

                                            <div class="pform-field">
                                                <label class="pform-label" for="{{ $fid('live_access_token') }}">Access Token (producción)</label>
                                                <input type="password" id="{{ $fid('live_access_token') }}"
                                                    name="mercadopago[{{ $role }}][live_access_token]" class="pform-input"
                                                    placeholder="{{ $roleData['has_live_access_token'] ? '••••••••  (guardado — deja vacío para conservarlo)' : 'APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxx' }}"
                                                    autocomplete="new-password">
                                                <p class="pform-hint">Se guarda encriptado. Déjalo vacío para no cambiarlo.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pform-panel-wrap">
                                        <div class="pform-panel">
                                            <h2 class="pform-panel-title">Webhook — {{ $roleData['label'] }}</h2>
                                            <p class="pform-hint" style="margin-bottom:16px;">
                                                Mercado Pago asigna una firma secreta de webhook por aplicación —
                                                pega aquí la de <strong>{{ $roleData['label'] }}</strong> (la URL de
                                                notificación es la misma para las 2 aplicaciones, ver abajo).
                                            </p>

                                            <div class="pform-field">
                                                <label class="pform-label" for="{{ $fid('webhook_secret') }}">Webhook Secret</label>
                                                <div class="integr-copy-row">
                                                    <input type="password" id="{{ $fid('webhook_secret') }}"
                                                        name="mercadopago[{{ $role }}][webhook_secret]" class="pform-input"
                                                        placeholder="{{ $roleData['has_webhook_secret'] ? '••••••••  (guardado — deja vacío para conservarlo)' : 'Clave secreta del webhook' }}"
                                                        autocomplete="new-password">
                                                    <button type="button" class="pform-btn outline mp-generate-secret-btn" data-target="{{ $fid('webhook_secret') }}">Generar</button>
                                                </div>

                                                <div class="mp-generated-secret-row" data-for="{{ $fid('webhook_secret') }}" style="display:none; margin-top:10px;">
                                                    <div class="integr-copy-row">
                                                        <input type="text" class="pform-input mp-generated-secret-display" readonly>
                                                        <button type="button" class="pform-btn outline integr-copy-btn" data-value="">Copiar</button>
                                                    </div>
                                                    <p class="pform-hint" style="color:#0f7a4f;">
                                                        Cópiala y pégala en Mercado Pago (pestaña Webhooks de esta
                                                        aplicación &gt; "Firma secreta"). También quedó puesta arriba,
                                                        se guarda al hacer clic en "Guardar configuración".
                                                    </p>
                                                </div>

                                                <p class="pform-hint">Se guarda encriptado. Déjalo vacío para no
                                                    cambiarlo. Sin este valor, la firma del webhook de esta aplicación
                                                    no se verifica.</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="pform-panel-wrap">
                                    <div class="pform-panel">
                                        <h2 class="pform-panel-title">URL del Webhook</h2>
                                        <p class="pform-hint" style="margin-bottom:16px;">
                                            Misma URL para las {{ count($mercadoPago) }} aplicaciones — pégala en
                                            cada una (panel de desarrolladores de Mercado Pago &gt; esa aplicación
                                            &gt; Webhooks &gt; URL de notificación), cada una con su propia firma
                                            secreta capturada arriba.
                                        </p>
                                        <div class="pform-field">
                                            <div class="integr-copy-row">
                                                <input type="text" class="pform-input" value="{{ $mercadoPagoWebhookUrl }}" readonly>
                                                <button type="button" class="pform-btn outline integr-copy-btn"
                                                    data-value="{{ $mercadoPagoWebhookUrl }}">Copiar</button>
                                            </div>
                                        </div>

                                        <button type="submit" class="pform-btn primary" style="margin-top:16px;">Guardar configuración</button>
                                    </div>
                                </div>
                            </form>

                            <style>
                                .mp-mode-toggle { display: inline-flex; border: 1px solid #d6d3d1; border-radius: 8px; overflow: hidden; }
                                .mp-mode-btn { padding: 8px 16px; font-size: 13px; font-weight: 600; background: #fff; border: none; cursor: pointer; color: #57534e; }
                                .mp-mode-btn + .mp-mode-btn { border-left: 1px solid #d6d3d1; }
                                .mp-mode-btn.active { background: #ff6213; color: #fff; }
                                .pform-panel-title-row { display: flex; align-items: center; gap: 10px; }
                            </style>
                            <script>
                                // Un toggle sandbox/live independiente por rol -- cada uno escribe en su
                                // propio hidden input (data-mp-mode-toggle-for apunta al id de ese input).
                                document.querySelectorAll('[data-mp-mode-toggle-for]').forEach(function (toggle) {
                                    var hidden = document.getElementById(toggle.dataset.mpModeToggleFor);
                                    toggle.querySelectorAll('.mp-mode-btn').forEach(function (btn) {
                                        btn.addEventListener('click', function () {
                                            hidden.value = this.dataset.mpMode;
                                            toggle.querySelectorAll('.mp-mode-btn').forEach(function (b) { b.classList.remove('active'); });
                                            this.classList.add('active');
                                        });
                                    });
                                });

                                // Genera un secreto aleatorio del lado del navegador (crypto.getRandomValues,
                                // nunca se manda al servidor sin que el admin apachurre "Guardar configuración")
                                // -- uno independiente por rol (cada botón "Generar" solo toca el campo de su
                                // propio rol, vía data-target).
                                document.querySelectorAll('.mp-generate-secret-btn').forEach(function (btn) {
                                    btn.addEventListener('click', function () {
                                        var targetInput = document.getElementById(this.dataset.target);
                                        var row = document.querySelector('.mp-generated-secret-row[data-for="' + this.dataset.target + '"]');
                                        if (!targetInput || !row) return;

                                        var bytes = new Uint8Array(24);
                                        crypto.getRandomValues(bytes);
                                        var secret = Array.from(bytes).map(function (b) {
                                            return b.toString(16).padStart(2, '0');
                                        }).join('');

                                        targetInput.value = secret;
                                        row.querySelector('.mp-generated-secret-display').value = secret;
                                        row.style.display = 'block';
                                        row.querySelector('.integr-copy-btn').dataset.value = secret;
                                    });
                                });
                            </script>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>

    <style>
        .integr-hub {
            --integr-accent: #ff6213;
            --integr-border: #e3e5e9;
            font-family: 'Inter Tight', var(--font-family), system-ui, -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 20px;
        }

        @media (min-width: 769px) {
            .integr-hub {
                flex-direction: row;
                align-items: flex-start;
            }
        }

        /* ---------- Sidebar ---------- */
        .integr-sidebar {
            display: flex;
            flex-direction: row;
            overflow-x: auto;
            gap: 8px;
            flex-shrink: 0;
        }

        @media (min-width: 769px) {
            .integr-sidebar {
                flex-direction: column;
                width: 296px;
                gap: 6px;
                position: sticky;
                top: 20px;
            }
        }

        .integr-sidebar-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            text-align: left;
            padding: 12px 14px;
            border: 1px solid transparent;
            border-radius: 12px;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
            transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
        }

        @media (min-width: 769px) {
            .integr-sidebar-item {
                white-space: normal;
            }
        }

        .integr-sidebar-item:hover:not(.active) {
            background: #f9fafb;
        }

        .integr-sidebar-item.active {
            border-color: var(--integr-border);
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(17, 24, 39, .08);
        }

        .integr-avatar {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--integr-accent), #d1490c);
        }

        .integr-sidebar-item-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .integr-sidebar-item-title {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .integr-sidebar-item-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #6b7280;
        }

        .integr-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #9ca3af;
            flex-shrink: 0;
        }

        .integr-dot.is-on {
            background: #10b981;
        }

        /* ---------- Content panel ---------- */
        .integr-content {
            flex: 1;
            min-width: 0;
        }

        .integr-panel {
            display: none;
        }

        .integr-panel.active {
            display: block;
        }

        .integr-panel-card {
            background: #fff;
            border: 1px solid var(--integr-border);
            border-radius: 12px;
            padding: 20px;
        }

        .integr-panel-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .integr-panel-avatar {
            flex-shrink: 0;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--integr-accent), #d1490c);
        }

        .integr-panel-header-text {
            flex: 1;
            min-width: 0;
        }

        .integr-panel-title-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .integr-panel-title-row h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        .integr-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            background: #f3f4f6;
        }

        .integr-badge.is-on {
            color: #047857;
            background: #d1fae5;
        }

        .integr-panel-desc {
            margin: 6px 0 0;
            font-size: 13px;
            line-height: 1.5;
            color: #6b7280;
        }

        /* ---------- Tabs ---------- */
        .integr-tabs {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            border-bottom: 1px solid var(--integr-border);
        }

        .integr-tab {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 2px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            margin-bottom: -1px;
        }

        .integr-tab:hover {
            color: #111827;
        }

        .integr-tab.active {
            color: var(--integr-accent);
            border-bottom-color: var(--integr-accent);
        }

        .integr-tab-content {
            display: none;
            padding-top: 18px;
        }

        .integr-tab-content.active {
            display: block;
        }

        .integr-tab-content:focus-within .pform-input,
        .integr-hub .pform-input:focus {
            outline: none;
            border-color: var(--integr-accent);
            box-shadow: 0 0 0 3px rgba(255, 98, 19, .15);
        }

        /* ---------- Callback URL copy row (pestaña Avanzado) ---------- */
        .integr-copy-row {
            display: flex;
            gap: 8px;
        }

        .integr-copy-row .pform-input {
            flex: 1;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 12.5px;
            color: #374151;
            background: #f9fafb;
        }

        .integr-copy-btn {
            flex-shrink: 0;
            white-space: nowrap;
        }

        .wh-header-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            align-items: end;
        }

        .wh-header-row .ap-field-group {
            gap: 4px;
        }

        .wh-header-remove {
            height: 38px;
        }
    </style>

    <script>
        // ------------------------------------------------------------------
        // Vista guardada del hub: panel de integración activo + pestaña interna
        // de cada panel + posición de scroll. Se conserva al guardar/crear/
        // editar/eliminar/probar (sea con POST+redirect o con fetch+reload):
        //   1) URL: ?panel=...&tab=... (history.replaceState). Es lo que usan los
        //      redirects del servidor (ReturnsToIntegrationsHub) y lo que
        //      sobrevive a location.reload().
        //   2) localStorage: último panel/pestañas usados (también al volver a la
        //      pantalla más tarde). sessionStorage: scroll por panel+pestaña.
        // Todo el acceso a storage va en try/catch (modo privado, bloqueado...).
        // ------------------------------------------------------------------
        (function () {
            const VIEW_KEY = 'integraciones.vista';
            const SCROLL_KEY = 'integraciones.scroll';
            const sidebarItems = Array.from(document.querySelectorAll('#integrSidebar .integr-sidebar-item'));
            const validPanels = sidebarItems.map(i => i.dataset.panel);
            const panelEl = id => document.getElementById('integrPanel-' + id);

            const storeGet = (area, key) => {
                try { return window[area].getItem(key); } catch (e) { return null; }
            };
            const storeSet = (area, key, value) => {
                try { window[area].setItem(key, value); } catch (e) {}
            };
            const readJson = (area, key) => {
                try { return JSON.parse(storeGet(area, key)) || null; } catch (e) { return null; }
            };

            const state = { panel: validPanels[0], tabs: {} };

            const hasTab = (panel, tab) =>
                !!(tab && panelEl(panel)?.querySelector('.integr-tab[data-tab="' + CSS.escape(tab) + '"]'));
            const activeTab = panel => state.tabs[panel] || null;

            const showPanel = panel => {
                sidebarItems.forEach(i => i.classList.toggle('active', i.dataset.panel === panel));
                document.querySelectorAll('.integr-panel').forEach(p => p.classList.toggle('active', p.id === 'integrPanel-' + panel));
                state.panel = panel;
            };

            const showTab = (panel, tab) => {
                const el = panelEl(panel);
                if (!el || !hasTab(panel, tab)) return;
                el.querySelectorAll('.integr-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
                el.querySelectorAll('.integr-tab-content').forEach(c => c.classList.toggle('active', c.dataset.tabContent === tab));
                state.tabs[panel] = tab;
            };

            // Scroll: quien scrollea es el ancestro con overflow auto/scroll más cercano al hub
            // (hoy .container.user-manager; .admin-main en otros layouts) o, si no hay, la ventana.
            const hubEl = document.querySelector('.integr-hub');
            const scrollParent = () => {
                let el = hubEl.parentElement;
                while (el && el !== document.body) {
                    const oy = getComputedStyle(el).overflowY;
                    if ((oy === 'auto' || oy === 'scroll') && el.scrollHeight > el.clientHeight + 1) return el;
                    el = el.parentElement;
                }
                return null;
            };
            const currentScroll = () => scrollParent()?.scrollTop ?? window.scrollY ?? 0;
            const saveScroll = () => {
                storeSet('sessionStorage', SCROLL_KEY, JSON.stringify({
                    panel: state.panel, tab: activeTab(state.panel), top: currentScroll(),
                }));
            };

            // Escribe el estado en la URL (query, no hash: el Referer no lleva hash) y en localStorage.
            const persist = () => {
                storeSet('localStorage', VIEW_KEY, JSON.stringify(state));
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('panel', state.panel);
                    const tab = activeTab(state.panel);
                    if (tab) url.searchParams.set('tab', tab); else url.searchParams.delete('tab');
                    url.hash = '';
                    window.history.replaceState(window.history.state, '', url);
                } catch (e) {}
                saveScroll();
            };

            // --- Estado inicial: URL > localStorage > primer panel ---
            const saved = readJson('localStorage', VIEW_KEY);
            if (saved && typeof saved === 'object' && saved.tabs && typeof saved.tabs === 'object') {
                Object.keys(saved.tabs).forEach(p => {
                    if (validPanels.includes(p) && hasTab(p, saved.tabs[p])) showTab(p, saved.tabs[p]);
                });
            }
            const params = new URLSearchParams(window.location.search);
            const urlPanel = params.get('panel');
            const urlTab = params.get('tab');
            const initialPanel = validPanels.includes(urlPanel) ? urlPanel
                : (saved && validPanels.includes(saved.panel) ? saved.panel : validPanels[0]);
            showPanel(initialPanel);
            if (validPanels.includes(urlPanel) && hasTab(urlPanel, urlTab)) showTab(urlPanel, urlTab);

            // --- Interacciones ---
            sidebarItems.forEach(item => {
                item.addEventListener('click', () => {
                    showPanel(item.dataset.panel);
                    persist();
                });
            });

            // Pestañas Credenciales/Avanzado/..., con estado independiente por panel.
            document.querySelectorAll('.integr-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    const panel = tab.closest('.integr-panel').id.replace('integrPanel-', '');
                    showTab(panel, tab.dataset.tab);
                    persist();
                });
            });

            // Al enviar cualquier formulario del hub (guardar, probar envío, crear/eliminar
            // cliente o token...) el servidor recibe en qué panel/pestaña estabas y
            // redirige ahí con ese mismo estado.
            const upsertHidden = (form, name, value) => {
                let input = form.querySelector('input[type="hidden"][name="' + name + '"]');
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }
                input.value = value;
            };
            document.addEventListener('submit', e => {
                const form = e.target;
                if (!(form instanceof HTMLFormElement) || !form.closest('.integr-content')) return;
                if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
                const panel = form.closest('.integr-panel')?.id.replace('integrPanel-', '') || state.panel;
                upsertHidden(form, '_panel', panel);
                const tab = form.closest('.integr-tab-content')?.dataset.tabContent || activeTab(panel);
                if (tab) upsertHidden(form, '_tab', tab); else form.querySelector('input[name="_tab"]')?.remove();
                saveScroll();
            }, true);

            // Scroll: guardar mientras se mueve y al salir/recargar la página.
            let scrollTimer = null;
            const onScroll = () => { clearTimeout(scrollTimer); scrollTimer = setTimeout(saveScroll, 120); };
            // Los eventos scroll no burbujean: se captura en document para cubrir a cualquier contenedor.
            document.addEventListener('scroll', onScroll, { capture: true, passive: true });
            window.addEventListener('pagehide', saveScroll);
            window.addEventListener('beforeunload', saveScroll);

            // Restaurar el scroll si es la misma vista (mismo panel + pestaña). Si se acaba
            // de generar un token, se muestra ese token (solo se ve una vez) en vez de restaurar.
            const savedScroll = readJson('sessionStorage', SCROLL_KEY);
            persist();
            const newToken = document.getElementById('integrNewToken');
            if (newToken) {
                newToken.scrollIntoView({ block: 'center' });
                return;
            }
            if (savedScroll && savedScroll.panel === state.panel
                && (savedScroll.tab || null) === (activeTab(state.panel) || null) && savedScroll.top > 0) {
                let userMoved = false;
                ['wheel', 'touchstart', 'keydown', 'mousedown'].forEach(ev =>
                    window.addEventListener(ev, () => { userMoved = true; }, { once: true, passive: true }));
                const restore = () => {
                    if (userMoved) return;
                    const sp = scrollParent();
                    if (sp) sp.scrollTop = savedScroll.top;
                    else window.scrollTo(0, savedScroll.top);
                };
                restore();
                requestAnimationFrame(restore);
                window.addEventListener('load', restore, { once: true });
            }
        })();

        // Avisos de "Probar envío": se cierran solos.
        document.querySelectorAll('.integr-mail-toast').forEach(toast => setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 8000));

        // Copiar URL de callback (pestaña Avanzado) — mismo patrón que
        // .btn-copy-webhook-value en admin/whatsapp-accounts.
        document.querySelectorAll('.integr-copy-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.value).then(() => {
                    const original = btn.textContent;
                    btn.textContent = 'Copiado';
                    setTimeout(() => btn.textContent = original, 1500);
                });
            });
        });
    </script>
@endsection
@include('admin.integrations.partials._webhook_modal_form')
@include('admin.integrations.partials._webhook_modal_delete')
@include('admin.integrations.partials._webhook_scripts')
@include('admin.integrations.partials._whatsapp_api_modal_form')
{{-- QR-AISLADO · OBLIGATORIO NO TOCAR · INICIO — vía WhatsApp Web/Baileys (QR, NO oficial) DESACTIVADA: se usa SOLO la API oficial de Meta. Bloque envuelto en @if (false) para no interferir con el código original. No borrar ni reactivar sin consultar. --}}
{{-- @include('admin.integrations.partials._whatsapp_qr_modal_form') --}}
{{-- @include('admin.integrations.partials._whatsapp_qr_modal_scan') --}}
{{-- QR-AISLADO · OBLIGATORIO NO TOCAR · FIN --}}
@include('admin.integrations.partials._whatsapp_modal_delete')
@include('admin.integrations.partials._whatsapp_scripts')
