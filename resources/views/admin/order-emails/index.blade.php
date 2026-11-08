@extends('admin.layouts.master')
@section('title')
    Correos de pedidos - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/erp-settings.css')
@endpush

@section('content')
    @php
        $statusMeta = [
            'sent'    => ['Enviado', '#0f7a4f', '#e6f6ee'],
            'failed'  => ['Falló', '#c81e1e', '#fdecec'],
            'skipped' => ['Omitido', '#b45309', '#fef3c7'],
        ];
        $canEdit = auth()->user()?->hasPermission('orders', 'edit');
    @endphp

    <div class="container user-manager">
        <section class="clients-manager-section">

            <header class="clients-manager-main" style="margin-bottom:4px;">
                <div>
                    <p class="breadcrumb-clients-manager main" style="margin-bottom:4px;">
                        Panel de Control &gt; Ecommerce &gt; Correos de pedidos
                    </p>
                    <h1>Correos de pedidos</h1>
                    <p class="breadcrumb-clients-manager main">Los avisos automáticos que recibe el cliente cuando compra en la tienda: pedido recibido con los datos para pagar, pago confirmado, envío, entrega y cancelación.</p>
                </div>
            </header>

            <div class="pform-panel-wrap" style="margin-top:20px;">

                <div class="oe-acc__tools">
                    <button type="button" class="pform-btn" data-oe-toggle="open">Expandir todo</button>
                    <button type="button" class="pform-btn" data-oe-toggle="close">Contraer todo</button>
                </div>

                <form method="POST" action="{{ route('admin.order-emails.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- ── Guía de uso ─────────────────────────────────────────── --}}
                    <details class="pform-panel oe-acc oe-guide" open>
                        <summary class="erp-panel-header oe-acc__summary">
                            <span class="erp-panel-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                                    <path d="M12 17h.01" />
                                </svg>
                            </span>
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Cómo configurar los correos de pedidos</h2>
                                <p class="erp-panel-subtitle">Guía rápida de 6 pasos, de la configuración a la primera prueba.</p>
                            </div>
                            <span class="oe-acc__chev" aria-hidden="true"></span>
                        </summary>

                        <ol class="oe-guide__steps">
                            <li>
                                <strong>Revisa desde qué correo salen.</strong>
                                En «Envío de correos», la línea <em>Sale desde</em> muestra el remitente. Si dice que es el remitente general (no-reply@), el cliente puede recibir el correo pero sus respuestas no llegan a nadie.
                                Para que las respuestas lleguen a tu bandeja, configura el «Buzón de la bandeja de leads» en <a href="{{ route('admin.integrations.index') }}">Integraciones</a>. Si aparece un aviso en rojo, el correo todavía no está configurado y no saldrá nada hasta corregirlo.
                            </li>
                            <li>
                                <strong>Enciende el envío.</strong>
                                Marca «Enviar correos automáticos de pedidos». Mientras esté apagado no sale ningún correo al cliente, aunque el correo en particular esté activo. El «Aviso al equipo» tiene su propio interruptor y funciona aparte.
                            </li>
                            <li>
                                <strong>Decide quién se entera de cada pedido.</strong>
                                «Copia oculta interna» manda una copia de cada correo que recibe el cliente. «Aviso al equipo» manda un correo aparte, con los datos del cliente y del pedido, a las direcciones que pongas (varias, separadas por coma). Al responderlo le escribes directo al cliente.
                            </li>
                            <li>
                                <strong>Ajusta cada correo.</strong>
                                Abre el que quieras: Pedido recibido, Pago confirmado, Enviado, Entregado o Cancelado. Puedes apagarlo con su casilla y cambiar el asunto, el título y el mensaje. Usa «Vista previa» para verlo tal como lo recibe el cliente. Los datos que se pueden insertar (como el número de pedido o el nombre) están en el desplegable «Datos que se pueden insertar».
                            </li>
                            <li>
                                <strong>Guarda y manda una prueba.</strong>
                                Pulsa «Guardar correos de pedidos» y luego, en «Enviar una prueba», elige el correo y tu dirección. Usa un pedido de ejemplo, nunca datos de un cliente real. Si no llega en unos minutos, revisa la carpeta de spam.
                            </li>
                            <li>
                                <strong>Vigila los envíos reales.</strong>
                                En «Últimos correos» ves cada correo que salió. <em>Enviado</em> salió bien; <em>Falló</em> muestra el motivo (casi siempre el correo del remitente sin configurar); <em>Omitido</em> significa que no había un correo válido a dónde mandarlo. Con «Reenviar» lo mandas de nuevo sin tocar el pedido.
                            </li>
                        </ol>

                        <div class="oe-guide__note">
                            <strong>Cuándo sale cada correo.</strong>
                            Las transferencias y otros métodos que no se cobran en línea mandan «Pedido recibido» al crear el pedido, con los datos para pagar. Los pagos con tarjeta o Mercado Pago no mandan ese aviso: el cliente recibe «Pago confirmado» cuando el pago se aprueba. «Enviado», «Entregado» y «Cancelado» salen al cambiar el estado del pedido en Órdenes. Los datos bancarios o instrucciones de pago se toman de «Métodos de pago».
                        </div>
                    </details>

                    {{-- ── General ─────────────────────────────────────────────── --}}
                    <details class="pform-panel oe-acc" open>
                        <summary class="erp-panel-header oe-acc__summary">
                            <span class="erp-panel-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="16" x="2" y="4" rx="2" />
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                </svg>
                            </span>
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Envío de correos</h2>
                                <p class="erp-panel-subtitle">Mientras esté apagado no sale ningún correo de pedidos.</p>
                            </div>
                            <span class="oe-acc__badge" data-badge-for="oe_enabled" data-on="Activo" data-off="Apagado"></span>
                            <span class="oe-acc__chev" aria-hidden="true"></span>
                        </summary>

                        <div class="pform-field">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                <input type="checkbox" id="oe_enabled" name="enabled" value="1" {{ old('enabled', $enabled) ? 'checked' : '' }}>
                                Enviar correos automáticos de pedidos
                            </label>
                        </div>

                        <div class="pform-field">
                            <span class="pform-label">Sale desde</span>
                            <p style="margin:0;">
                                <strong>{{ $sender['address'] ?: 'sin remitente' }}</strong>
                                @if ($sender['name']) ({{ $sender['name'] }}) @endif
                            </p>
                            @if ($smtpProblem)
                                <p class="pform-hint" style="color:#c81e1e;">{{ $smtpProblem }}</p>
                            @elseif ($sender['uses_default'])
                                <p class="pform-hint" style="color:#b45309;">Es el remitente general del sistema (normalmente no-reply@): si el cliente responde, nadie lo recibe. Para que las respuestas lleguen a tu bandeja, configura el «Buzón de la bandeja de leads» en <a href="{{ route('admin.integrations.index') }}">Integraciones</a>.</p>
                            @else
                                <p class="pform-hint">Es el buzón de la bandeja de leads: las respuestas del cliente llegan ahí.</p>
                            @endif
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="oe_copy_to">Copia oculta interna (opcional)</label>
                            <input type="text" id="oe_copy_to" name="copy_to" class="pform-input" maxlength="500"
                                value="{{ old('copy_to', $copyTo) }}" placeholder="ventas@tuempresa.com, administracion@tuempresa.com">
                            <p class="pform-hint">Cada correo que salga al cliente también le llega a estas direcciones, separadas por coma, para que sepan qué se envió.</p>
                            @error('copy_to') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                        </div>

                        <div class="pform-field">
                            <label class="pform-label" for="oe_footer">Texto al final de todos los correos (opcional)</label>
                            <textarea id="oe_footer" name="footer" class="pform-textarea" rows="3" maxlength="2000"
                                placeholder="Ej. Horario de atención, garantía, datos de contacto...">{{ old('footer', $footer) }}</textarea>
                            @error('footer') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                        </div>
                    </details>

                    {{-- ── Aviso interno ───────────────────────────────────────── --}}
                    <details class="pform-panel oe-acc" @if ($errors->has('staff_to')) open @endif>
                        <summary class="erp-panel-header oe-acc__summary">
                            <span class="erp-panel-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                </svg>
                            </span>
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Aviso al equipo</h2>
                                <p class="erp-panel-subtitle">Correo interno cuando entra un pedido nuevo en la tienda.</p>
                            </div>
                            <span class="oe-acc__badge" data-badge-for="oe_staff_enabled" data-on="Activo" data-off="Apagado"></span>
                            <span class="oe-acc__chev" aria-hidden="true"></span>
                        </summary>

                        <div class="pform-field">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                <input type="checkbox" id="oe_staff_enabled" name="staff_enabled" value="1" {{ old('staff_enabled', $staffEnabled) ? 'checked' : '' }}>
                                Avisar al equipo cuando entre un pedido nuevo
                            </label>
                            <label class="pform-label" for="oe_staff_to" style="margin-top:10px;">Correo(s) que reciben el aviso</label>
                            <input type="text" id="oe_staff_to" name="staff_to" class="pform-input" maxlength="500"
                                value="{{ old('staff_to', $staffTo) }}" placeholder="administracion@equitermindustries.com.mx">
                            <p class="pform-hint">Llega un correo con el cliente, los productos, el total y la dirección de envío. Al responderlo le escribes directo al cliente. Varias direcciones, separadas por coma. Los pedidos de Mercado Pago avisan hasta que el pago se aprueba.</p>
                            @error('staff_to') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                        </div>
                    </details>

                    {{-- ── Un desplegable por correo ───────────────────────────── --}}
                    @foreach ($events as $key => $event)
                        <details class="pform-panel oe-acc" id="evento-{{ $key }}"
                            @if ($errors->hasAny(["events.$key.subject", "events.$key.heading", "events.$key.intro"])) open @endif>
                            <summary class="erp-panel-header oe-acc__summary">
                                <div class="erp-panel-heading">
                                    <h2 class="pform-panel-title">{{ $event['label'] }}</h2>
                                    <p class="erp-panel-subtitle">{{ $event['when'] }}</p>
                                </div>
                                <span class="oe-acc__badge" data-badge-for="oe_{{ $key }}_enabled" data-on="Activo" data-off="Apagado"></span>
                                <span class="oe-acc__chev" aria-hidden="true"></span>
                            </summary>

                            <div class="pform-field" style="text-align:right;">
                                <a href="{{ route('admin.order-emails.preview', $key) }}" target="_blank" rel="noopener" class="pform-btn">Vista previa</a>
                            </div>

                            <div class="pform-field">
                                <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                    <input type="checkbox" id="oe_{{ $key }}_enabled" name="events[{{ $key }}][enabled]" value="1"
                                        {{ old("events.$key.enabled", $event['enabled']) ? 'checked' : '' }}>
                                    Enviar este correo
                                </label>
                            </div>

                            <div class="pform-field">
                                <label class="pform-label" for="oe_{{ $key }}_subject">Asunto</label>
                                <input type="text" id="oe_{{ $key }}_subject" name="events[{{ $key }}][subject]" class="pform-input" maxlength="200"
                                    value="{{ old("events.$key.subject", $event['subject']) }}">
                                @error("events.$key.subject") <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                            </div>

                            <div class="pform-field">
                                <label class="pform-label" for="oe_{{ $key }}_heading">Título dentro del correo</label>
                                <input type="text" id="oe_{{ $key }}_heading" name="events[{{ $key }}][heading]" class="pform-input" maxlength="150"
                                    value="{{ old("events.$key.heading", $event['heading']) }}">
                                @error("events.$key.heading") <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                            </div>

                            <div class="pform-field">
                                <label class="pform-label" for="oe_{{ $key }}_intro">Mensaje</label>
                                <textarea id="oe_{{ $key }}_intro" name="events[{{ $key }}][intro]" class="pform-textarea" rows="5" maxlength="3000">{{ old("events.$key.intro", $event['intro']) }}</textarea>
                                @error("events.$key.intro") <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                            </div>
                        </details>
                    @endforeach

                    <details class="pform-panel oe-acc">
                        <summary class="erp-panel-header oe-acc__summary">
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Datos que se pueden insertar</h2>
                                <p class="erp-panel-subtitle">Escríbelos tal cual en el asunto, el título o el mensaje; se reemplazan con los datos del pedido.</p>
                            </div>
                            <span class="oe-acc__chev" aria-hidden="true"></span>
                        </summary>
                        <ul style="margin:10px 0 0;padding-left:18px;">
                            @foreach ($tokens as $token => $description)
                                <li><code>{{ $token }}</code> — {{ $description }}</li>
                            @endforeach
                        </ul>
                        <p class="pform-hint" style="margin-top:10px;">Los datos del pedido (partidas, totales, dirección) y los datos bancarios o instrucciones de pago del método elegido se agregan solos; los configuras en Métodos de pago y en Envíos.</p>
                    </details>

                    @if ($canEdit)
                        <button type="submit" class="pform-btn primary">Guardar correos de pedidos</button>
                    @endif
                </form>

                {{-- ── Correo de prueba ────────────────────────────────────────── --}}
                @if ($canEdit)
                    <details class="pform-panel oe-acc" style="margin-top:20px;" @if ($errors->has('test') || $errors->has('to')) open @endif>
                        <summary class="erp-panel-header oe-acc__summary">
                            <div class="erp-panel-heading">
                                <h2 class="pform-panel-title">Enviar una prueba</h2>
                                <p class="erp-panel-subtitle">Usa un pedido de ejemplo (nunca datos de un cliente real) y los textos que tengas guardados. Guarda primero si acabas de editar.</p>
                            </div>
                            <span class="oe-acc__chev" aria-hidden="true"></span>
                        </summary>
                        <form method="POST" action="{{ route('admin.order-emails.test') }}" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;align-items:flex-end;">
                            @csrf
                            <div class="pform-field" style="margin:0;">
                                <label class="pform-label" for="oe_test_event">Correo</label>
                                <select id="oe_test_event" name="event" class="pform-input">
                                    @foreach ($events as $key => $event)
                                        <option value="{{ $key }}" @selected(old('event') === $key)>{{ $event['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pform-field" style="margin:0;min-width:260px;">
                                <label class="pform-label" for="oe_test_to">Enviar a</label>
                                <input type="email" id="oe_test_to" name="to" class="pform-input" maxlength="190" required value="{{ old('to') }}" placeholder="tu-correo@ejemplo.com">
                            </div>
                            <button type="submit" class="pform-btn">Enviar prueba</button>
                        </form>
                        @error('test') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                        @error('to') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                    </details>
                @endif

                {{-- ── Historial ──────────────────────────────────────────────── --}}
                <details class="pform-panel oe-acc" style="margin-top:20px;" @if ($errors->has('resend')) open @endif>
                    <summary class="erp-panel-header oe-acc__summary">
                        <div class="erp-panel-heading">
                            <h2 class="pform-panel-title">Últimos correos</h2>
                            <p class="erp-panel-subtitle">Cada correo que salió, si se envió o falló, y por qué.</p>
                        </div>
                        @if ($logs->isNotEmpty())
                            <span class="oe-acc__badge oe-acc__badge--count">{{ $logs->count() }}</span>
                        @endif
                        <span class="oe-acc__chev" aria-hidden="true"></span>
                    </summary>
                    @error('resend') <p class="pform-hint" style="color:#c81e1e;">{{ $message }}</p> @enderror
                    @if ($logs->isEmpty())
                        <p class="erp-panel-subtitle">Todavía no ha salido ningún correo de pedidos.</p>
                    @else
                        <div style="overflow-x:auto;margin-top:10px;">
                            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                                <thead>
                                    <tr style="text-align:left;color:#52606d;">
                                        <th style="padding:6px 8px;">Fecha</th>
                                        <th style="padding:6px 8px;">Pedido</th>
                                        <th style="padding:6px 8px;">Correo</th>
                                        <th style="padding:6px 8px;">Para</th>
                                        <th style="padding:6px 8px;">Estado</th>
                                        <th style="padding:6px 8px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($logs as $log)
                                        @php [$label, $color, $bg] = $statusMeta[$log->status] ?? [$log->status, '#52606d', '#f1f2f4']; @endphp
                                        <tr style="border-top:1px solid #eef0f2;">
                                            <td style="padding:6px 8px;white-space:nowrap;">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                            <td style="padding:6px 8px;">
                                                @if ($log->storeOrder)
                                                    <a href="{{ route('admin.orders.show', $log->store_order_id) }}">{{ $log->storeOrder->order_number }}</a>
                                                @endif
                                            </td>
                                            <td style="padding:6px 8px;">{{ $log->eventLabel() }}</td>
                                            <td style="padding:6px 8px;">{{ $log->to_email ?: '—' }}</td>
                                            <td style="padding:6px 8px;">
                                                <span style="background:{{ $bg }};color:{{ $color }};padding:2px 8px;border-radius:10px;font-weight:600;">{{ $label }}</span>
                                                @if ($log->error)
                                                    <div style="color:#7b8794;font-size:12px;max-width:340px;">{{ $log->error }}</div>
                                                @endif
                                            </td>
                                            <td style="padding:6px 8px;text-align:right;">
                                                @if ($canEdit)
                                                    <form method="POST" action="{{ route('admin.order-emails.resend', $log->id) }}" onsubmit="return confirm('¿Reenviar este correo al cliente?');">
                                                        @csrf
                                                        <button type="submit" class="pform-btn">Reenviar</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </details>

            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <style>
        .oe-acc > .oe-acc__summary { list-style: none; cursor: pointer; display: flex; align-items: center; gap: 12px; user-select: none; margin-bottom: 0; }
        .oe-acc > .oe-acc__summary::-webkit-details-marker { display: none; }
        .oe-acc[open] > .oe-acc__summary { margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid #eef0f2; }
        .oe-acc > .oe-acc__summary:hover .pform-panel-title { color: #ff6213; }
        .oe-acc > .oe-acc__summary:focus-visible { outline: 2px solid #ff6213; outline-offset: 4px; border-radius: 6px; }
        .oe-acc .erp-panel-heading { flex: 1; min-width: 0; }
        .oe-acc__badge { flex: none; font-size: 12px; font-weight: 600; padding: 2px 10px; border-radius: 10px; background: #f1f2f4; color: #52606d; }
        .oe-acc__badge.is-on { background: #e6f6ee; color: #0f7a4f; }
        .oe-acc__badge.is-off { background: #f1f2f4; color: #7b8794; }
        .oe-acc__badge--count { background: #fff1e8; color: #c4480a; }
        .oe-acc__chev { flex: none; width: 10px; height: 10px; border-right: 2px solid #7b8794; border-bottom: 2px solid #7b8794; transform: rotate(45deg); margin: 0 6px 4px 4px; transition: transform .18s ease; }
        .oe-acc[open] > .oe-acc__summary .oe-acc__chev { transform: rotate(-135deg); margin-bottom: -4px; }
        .oe-guide__steps { margin: 0; padding: 0; list-style: none; counter-reset: oe-step; display: grid; gap: 14px; }
        .oe-guide__steps li { counter-increment: oe-step; position: relative; padding-left: 40px; font-size: 14px; line-height: 1.55; color: #3e4c59; }
        .oe-guide__steps li::before { content: counter(oe-step); position: absolute; left: 0; top: 0; width: 26px; height: 26px; border-radius: 50%; background: #fff1e8; color: #c4480a; font-weight: 700; font-size: 13px; display: flex; align-items: center; justify-content: center; }
        .oe-guide__steps li strong { display: block; color: #141516; margin-bottom: 2px; }
        .oe-guide__note { margin-top: 18px; padding: 12px 14px; border-radius: 8px; background: #f7f8fa; border: 1px solid #eef0f2; font-size: 13px; line-height: 1.55; color: #52606d; }
        .oe-guide__note strong { color: #141516; }
        .oe-acc__tools { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        @media (max-width: 640px) { .oe-acc__badge { display: none; } }
    </style>
    <script>
        (function () {
            var panels = function () { return document.querySelectorAll('details.oe-acc'); };

            // Etiqueta Activo/Apagado del encabezado, ligada a la casilla de cada desplegable.
            function refreshBadge(badge) {
                var box = document.getElementById(badge.getAttribute('data-badge-for'));
                if (!box) { return; }
                var on = box.checked;
                badge.textContent = on ? badge.getAttribute('data-on') : badge.getAttribute('data-off');
                badge.classList.toggle('is-on', on);
                badge.classList.toggle('is-off', !on);
            }
            document.querySelectorAll('[data-badge-for]').forEach(function (badge) {
                refreshBadge(badge);
                var box = document.getElementById(badge.getAttribute('data-badge-for'));
                if (box) { box.addEventListener('change', function () { refreshBadge(badge); }); }
            });

            // Si un campo no pasa la validación del navegador, abre su desplegable para que se vea.
            document.addEventListener('invalid', function (e) {
                var d = e.target.closest && e.target.closest('details');
                if (d) { d.open = true; }
            }, true);

            // Expandir / contraer todo.
            document.querySelectorAll('[data-oe-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var open = btn.getAttribute('data-oe-toggle') === 'open';
                    panels().forEach(function (d) { d.open = open; });
                });
            });

            // Enlace directo (#evento-paid): abre ese desplegable.
            if (location.hash) {
                var target = document.querySelector(location.hash);
                if (target && target.tagName === 'DETAILS') { target.open = true; }
            }
        })();
    </script>
@endpush
