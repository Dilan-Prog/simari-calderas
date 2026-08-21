@extends('admin.layouts.master')
@push('styles')
    @vite('resources/css/admin/pages/payment-methods.css')
@endpush
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
                    <p class="breadcrumb-clients-manager main">Correo SMTP, WhatsApp Business y Webhooks salientes</p>
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

            {{-- Hub de dos columnas: sidebar de secciones + panel de contenido.
                 Toggle 100% CSS-class + JS vanilla (sin framework), mismo
                 criterio "sin dependencias nuevas" del resto del admin. --}}
            <div class="integr-hub">
                <nav class="integr-sidebar" id="integrSidebar">
                    <button type="button" class="integr-sidebar-item active" data-panel="smtp">
                        <span class="integr-sidebar-item-title">Correo SMTP</span>
                        <span class="integr-sidebar-item-desc">Envío de correos del sitio</span>
                    </button>
                    <button type="button" class="integr-sidebar-item" data-panel="whatsapp">
                        <span class="integr-sidebar-item-title">WhatsApp Business</span>
                        <span class="integr-sidebar-item-desc">Cuentas Meta Cloud API</span>
                    </button>
                    <button type="button" class="integr-sidebar-item" data-panel="webhooks">
                        <span class="integr-sidebar-item-title">Webhooks</span>
                        <span class="integr-sidebar-item-desc">Envíos salientes a servicios externos</span>
                    </button>
                </nav>

                <div class="integr-content">

                    {{-- ============ Panel 1: Correo SMTP (sin cambios de lógica) ============ --}}
                    <div class="integr-panel active" id="integrPanel-smtp">
                        <form method="POST" action="{{ route('admin.integrations.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="pform-panel-wrap">
                                <div class="pform-panel">
                                    <h2 class="pform-panel-title">Correo saliente (SMTP)</h2>
                                    <p class="pform-hint" style="margin-bottom:16px;">
                                        Con estos datos se envían los correos del sitio (recuperación de contraseña de
                                        clientes, etc.).
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
                    </div>

                    {{-- ============ Panel 2: WhatsApp Business (resumen liviano) ============ --}}
                    <div class="integr-panel" id="integrPanel-whatsapp">
                        <div class="pform-panel-wrap">
                            <div class="pform-panel">
                                <h2 class="pform-panel-title">WhatsApp Business</h2>
                                <p class="pform-hint" style="margin-bottom:16px;">
                                    Los números de WhatsApp Business (Meta Cloud API) usados por el Embudo de Venta y
                                    el webhook público se administran en su propia pantalla, con su propio CRUD y
                                    datos de configuración del webhook de Meta.
                                </p>
                                <a href="{{ route('admin.whatsapp-accounts.index') }}" class="pform-btn primary"
                                    style="display:inline-block; text-decoration:none;">
                                    Ir a Cuentas de WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- ============ Panel 3: Webhooks (CRUD modal, patrón Credenciales) ============ --}}
                    <div class="integr-panel" id="integrPanel-webhooks">
                        <header class="clients-manager-main" style="margin-bottom:4px;">
                            <div>
                                <h2 class="pform-panel-title" style="margin:0;">Webhooks</h2>
                                <p class="pform-hint">Envíos salientes configurables usados por las automatizaciones
                                    del sistema</p>
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

                </div>
            </div>
        </section>
    </div>

    <style>
        .integr-hub {
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
                width: 240px;
                gap: 6px;
            }
        }

        .integr-sidebar-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            align-items: flex-start;
            text-align: left;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #ffffff;
            cursor: pointer;
            font-family: var(--font-family);
            white-space: nowrap;
            transition: border-color .15s ease, background-color .15s ease;
        }

        @media (min-width: 769px) {
            .integr-sidebar-item {
                white-space: normal;
            }
        }

        .integr-sidebar-item:hover:not(.active) {
            border-color: #d1d5db;
        }

        .integr-sidebar-item.active {
            border-color: var(--button-primary-color, #ff6213);
            background: #fff7ed;
        }

        .integr-sidebar-item-title {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .integr-sidebar-item-desc {
            font-size: 11px;
            color: #6b7280;
        }

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

        .integr-header-row {
            display: flex;
            align-items: flex-end;
            gap: 10px;
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
        // Toggle de secciones del hub (sin dependencias — misma idea que
        // cualquier tab-switcher del admin, aquí implementado ad-hoc porque
        // esta página no tenía tabs previamente).
        document.querySelectorAll('#integrSidebar .integr-sidebar-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('#integrSidebar .integr-sidebar-item').forEach(i => i.classList.remove('active'));
                document.querySelectorAll('.integr-panel').forEach(p => p.classList.remove('active'));

                item.classList.add('active');
                document.getElementById('integrPanel-' + item.dataset.panel)?.classList.add('active');
            });
        });
    </script>
@endsection
@include('admin.integrations.partials._webhook_modal_form')
@include('admin.integrations.partials._webhook_modal_delete')
@include('admin.integrations.partials._webhook_scripts')
