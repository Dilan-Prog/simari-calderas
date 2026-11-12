@extends('admin.layouts.master')
@section('title')
    Configuración del Embudo de Venta - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
    <style>
        .fs-wrap { display: grid; gap: 16px; max-width: 760px; }
        .fs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
        .fs-full { grid-column: 1 / -1; }
        .fs-state { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px; margin: 0 0 12px; }
        .fs-state b { font-weight: 800; }
        .fs-warn { border: 1px solid var(--erp-warn-dot, #f59e0b); background: var(--erp-warn-bg, #fef3c7); color: var(--erp-warn, #a16207); border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 12px; }
        .fs-help { font-size: 12px; color: var(--erp-muted, #6b7280); margin: 6px 0 0; }
        .fs-inline { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
        .fs-inline .erp-field { flex: 1 1 240px; }
        .fs-sugg { background: none; border: 0; padding: 0; color: var(--erp-accent-dark, #c4480a); font: inherit; font-weight: 700; cursor: pointer; text-decoration: underline; }
        @media (max-width: 640px) { .fs-grid { grid-template-columns: 1fr; } }
    </style>
@endpush
@section('content')
    <div class="erp-page">
        <x-erp.page-header title="Configuración del Embudo de Venta"
            desc="Elige con qué WhatsApp se atiende y desde qué correo se contestan los mensajes."
            :crumbs="[['Ventas', null], ['Embudo de Venta', route('admin.whatsapp-funnel.index')], ['Configuración', null]]" />

        @if (session('success'))<div class="crm-flash ok" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="crm-flash err" role="alert">{{ $errors->first() }}</div>@endif

        <div class="fs-wrap">
            <form method="POST" action="{{ route('admin.whatsapp-funnel.settings.update') }}" class="fs-wrap" autocomplete="off">
                @csrf

                <x-erp.card title="WhatsApp" sub="Cuenta que usa el embudo y la bandeja de leads para escribir cuando el lead aún no tiene una conversación.">
                    @if ($accounts->isEmpty())
                        <div class="fs-warn">No hay cuentas de WhatsApp activas. Conéctalas en Integraciones &gt; WhatsApp.</div>
                    @else
                        <label class="erp-field" for="fsAccount">WhatsApp para atender
                            <select class="erp-select" id="fsAccount" name="whatsapp_account_id">
                                <option value="">Automático (la primera cuenta activa)</option>
                                @foreach ($accounts as $a)
                                    <option value="{{ $a->id }}" @selected((int) old('whatsapp_account_id', $selectedAccount) === $a->id)>
                                        {{ $a->name }} · {{ $a->phone_number }} · {{ ($a->connection_type ?? 'meta_cloud_api') === 'meta_cloud_api' ? 'API oficial' : 'WhatsApp Web (QR)' }}
                                    </option>
                                @endforeach
                            </select>
                            <small>Si el lead ya tiene una conversación, se sigue contestando por la cuenta de esa conversación. La bandeja de leads solo usa cuentas de la API oficial.</small>
                        </label>
                    @endif
                </x-erp.card>

                <x-erp.card title="Correo para responder" sub="Desde qué buzón salen las respuestas a los clientes (por ejemplo administracion@, donde les llegan los correos).">
                    <p class="fs-state">
                        <span>Hoy las respuestas salen de:</span>
                        <b>{{ $sender['address'] ?: 'sin remitente' }}</b>
                        @if ($usesDefault)
                            <x-erp.chip tone="warn" label="Remitente general (no-reply)" />
                        @else
                            <x-erp.chip tone="ok" label="Buzón configurado" />
                        @endif
                    </p>
                    @if ($usesDefault)
                        <div class="fs-warn">
                            Mientras no captures el buzón, el cliente recibe las respuestas de <b>{{ $sender['address'] ?: 'no-reply' }}</b> y no puede contestar a tu correo de atención.
                            @if ($suggestion)
                                Sugerido: <button type="button" class="fs-sugg" data-fs-fill="{{ $suggestion }}">usar {{ $suggestion }}</button>
                            @endif
                        </div>
                    @endif
                    @if ($smtpProblem)<div class="fs-warn">{{ $smtpProblem }}</div>@endif

                    <div class="fs-grid">
                        <label class="erp-field">Correo desde el que se responde
                            <input class="erp-input" type="email" name="mail_from_address" id="fsFrom" value="{{ old('mail_from_address', $values['from_address']) }}" placeholder="{{ $suggestion ?: 'administracion@tuempresa.com' }}">
                        </label>
                        <label class="erp-field">Nombre que ve el cliente
                            <input class="erp-input" type="text" name="mail_from_name" value="{{ old('mail_from_name', $values['from_name']) }}" placeholder="Equiterm Industries">
                        </label>
                        <label class="erp-field">Usuario del buzón
                            <input class="erp-input" type="text" name="mail_username" id="fsUser" value="{{ old('mail_username', $values['username']) }}" placeholder="Normalmente es el mismo correo" autocomplete="off">
                        </label>
                        <label class="erp-field">Contraseña del buzón
                            <input class="erp-input" type="password" name="mail_password" value="" autocomplete="new-password"
                                placeholder="{{ $hasPassword ? '•••••••• (vacío = conservar la actual)' : 'La contraseña de ese correo' }}">
                        </label>
                    </div>
                    <p class="fs-help">El servidor, el puerto y el cifrado son los de Integraciones &gt; Correo. La contraseña se guarda cifrada y nunca se vuelve a mostrar.</p>

                    <div class="erp-form-actions">
                        @unless ($usesDefault)
                            <label style="margin-right:auto;font-size:12.5px;display:flex;align-items:center;gap:6px;">
                                <input type="checkbox" name="clear_mailbox" value="1"> Quitar este buzón y usar el remitente general
                            </label>
                        @endunless
                        <button class="erp-btn erp-btn--primary" type="submit">Guardar configuración</button>
                    </div>
                </x-erp.card>
            </form>

            <x-erp.card title="Probar el correo" sub="Envía un correo de prueba con el remitente de arriba para confirmar que sale del buzón correcto.">
                <form method="POST" action="{{ route('admin.whatsapp-funnel.settings.test-mail') }}" class="fs-inline">
                    @csrf
                    <label class="erp-field">Enviar la prueba a
                        <input class="erp-input" type="email" name="test_to" value="{{ old('test_to', auth()->user()->email) }}" required>
                    </label>
                    <button class="erp-btn" type="submit">Enviar prueba</button>
                </form>
            </x-erp.card>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.querySelectorAll('[data-fs-fill]').forEach(function (b) {
            b.addEventListener('click', function () {
                var v = b.getAttribute('data-fs-fill');
                var from = document.getElementById('fsFrom'), user = document.getElementById('fsUser');
                if (from) from.value = v;
                if (user && !user.value) user.value = v;
                if (from) from.focus();
            });
        });
    </script>
@endpush
