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

                <form method="POST" action="{{ route('admin.order-emails.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- ── General ─────────────────────────────────────────────── --}}
                    <div class="pform-panel">
                        <div class="erp-panel-header">
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
                        </div>

                        <div class="pform-field">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                <input type="checkbox" name="enabled" value="1" {{ old('enabled', $enabled) ? 'checked' : '' }}>
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
                    </div>

                    {{-- ── Un panel por correo ─────────────────────────────────── --}}
                    @foreach ($events as $key => $event)
                        <div class="pform-panel" id="evento-{{ $key }}">
                            <div class="erp-panel-header">
                                <div class="erp-panel-heading">
                                    <h2 class="pform-panel-title">{{ $event['label'] }}</h2>
                                    <p class="erp-panel-subtitle">{{ $event['when'] }}</p>
                                </div>
                                <a href="{{ route('admin.order-emails.preview', $key) }}" target="_blank" rel="noopener" class="pform-btn">Vista previa</a>
                            </div>

                            <div class="pform-field">
                                <label style="display:flex;align-items:center;gap:8px;font-weight:600;">
                                    <input type="checkbox" name="events[{{ $key }}][enabled]" value="1"
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
                        </div>
                    @endforeach

                    <div class="pform-panel">
                        <h2 class="pform-panel-title">Datos que se pueden insertar</h2>
                        <p class="erp-panel-subtitle">Escríbelos tal cual en el asunto, el título o el mensaje; se reemplazan con los datos del pedido.</p>
                        <ul style="margin:10px 0 0;padding-left:18px;">
                            @foreach ($tokens as $token => $description)
                                <li><code>{{ $token }}</code> — {{ $description }}</li>
                            @endforeach
                        </ul>
                        <p class="pform-hint" style="margin-top:10px;">Los datos del pedido (partidas, totales, dirección) y los datos bancarios o instrucciones de pago del método elegido se agregan solos; los configuras en Métodos de pago y en Envíos.</p>
                    </div>

                    @if ($canEdit)
                        <button type="submit" class="pform-btn primary">Guardar correos de pedidos</button>
                    @endif
                </form>

                {{-- ── Correo de prueba ────────────────────────────────────────── --}}
                @if ($canEdit)
                    <div class="pform-panel" style="margin-top:20px;">
                        <h2 class="pform-panel-title">Enviar una prueba</h2>
                        <p class="erp-panel-subtitle">Usa un pedido de ejemplo (nunca datos de un cliente real) y los textos que tengas guardados. Guarda primero si acabas de editar.</p>
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
                    </div>
                @endif

                {{-- ── Historial ──────────────────────────────────────────────── --}}
                <div class="pform-panel" style="margin-top:20px;">
                    <h2 class="pform-panel-title">Últimos correos</h2>
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
                </div>

            </div>
        </section>
    </div>
@endsection
