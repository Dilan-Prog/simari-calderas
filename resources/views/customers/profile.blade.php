@extends('customers.layouts.master')

@section('title', 'Mi perfil - Portal de servicios')

@push('styles')
<style>
    .cp-page { padding:32px; overflow-y:auto; height:calc(100vh - 64px); box-sizing:border-box; font-family:var(--font-family); }
    .cp-breadcrumb { display:flex; align-items:center; gap:6px; font-size:13px; color:#9ca3af; margin-bottom:16px; }
    .cp-breadcrumb strong { color:#374151; font-weight:600; }

    .cp-header { display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; background:#fff; padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #f3f4f6; }
    .cp-header-left { display:flex; align-items:center; gap:18px; min-width:0; }
    .cp-avatar { width:80px; height:80px; flex-shrink:0; background:#22c55e; color:#fff; font-weight:700; font-size:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; }
    .cp-who { display:flex; flex-direction:column; gap:8px; align-items:flex-start; min-width:0; }
    .cp-who h2 { margin:0; font-size:22px; font-weight:700; color:#1f2937; }
    .cp-line { display:flex; align-items:center; gap:8px; margin:0; font-size:14px; color:#6b7280; }
    .cp-badge { display:inline-flex; align-items:center; padding:3px 12px; border-radius:999px; font-size:12px; font-weight:600; }
    .cp-badge--active { background:#d1fae5; color:#065f46; }
    .cp-badge--off { background:#f3f4f6; color:#6b7280; }
    .cp-readonly { font-size:12px; color:#9ca3af; max-width:280px; text-align:right; line-height:1.4; }

    .cp-stats { display:grid; grid-template-columns:repeat(5, 1fr); gap:16px; margin-bottom:20px; }
    .cp-stat { background:#fff; border-radius:12px; padding:16px 18px; border:1px solid #f3f4f6; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .cp-stat p { margin:0 0 4px; font-size:12px; font-weight:500; color:#6b7280; }
    .cp-stat h3 { margin:0; font-size:24px; font-weight:700; color:#1f2937; }

    .cp-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
    .cp-card { background:#fff; padding:20px; border-radius:12px; display:flex; flex-direction:column; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,.06); border:1px solid #f3f4f6; min-width:0; }
    .cp-card h3 { margin:0; font-size:15px; font-weight:700; color:#1f2937; }
    .cp-card-head { display:flex; justify-content:space-between; align-items:center; gap:10px; }
    .cp-field { display:flex; flex-direction:column; gap:3px; }
    .cp-label { font-size:12px; font-weight:600; color:#6b7280; }
    .cp-value { display:flex; align-items:center; gap:8px; font-size:14px; color:#1f2937; margin:0; overflow-wrap:anywhere; }
    .cp-value svg { color:#9ca3af; flex-shrink:0; }
    .cp-tag { font-size:11px; font-weight:600; color:#2563eb; background:#eff6ff; border:1px solid #bfdbfe; border-radius:999px; padding:2px 10px; }
    .cp-section-title { font-size:15px; font-weight:700; color:#1f2937; margin:0 0 12px; }
    .cp-empty { color:#9ca3af; font-size:14px; margin:0; }

    .cp-card--form { gap:16px; align-self:start; }
    .cp-hint { display:block; margin:4px 0 0; font-size:12px; color:#6b7280; line-height:1.4; }
    .cp-req { color:#dc2626; }
    .cp-form { display:flex; flex-direction:column; gap:18px; }
    .cp-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; }
    .cp-form-grid--one { grid-template-columns:1fr; }
    .cp-full { grid-column:1 / -1; }
    .cp-fgroup { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .cp-flabel { font-size:13px; font-weight:600; color:#374151; }
    .cp-input { width:100%; box-sizing:border-box; height:42px; padding:0 12px; font:inherit; font-size:14px; color:#1f2937; background:#fff; border:1px solid #d1d5db; border-radius:8px; transition:border-color .15s, box-shadow .15s; }
    .cp-input--upper { text-transform:uppercase; }
    .cp-input:focus { outline:none; border-color:#ff6213; box-shadow:0 0 0 3px rgba(255,98,19,.18); }
    .cp-fgroup.has-error .cp-input { border-color:#dc2626; }
    .cp-fgroup.has-error .cp-input:focus { box-shadow:0 0 0 3px rgba(220,38,38,.18); }
    .cp-error { font-size:12px; font-weight:500; color:#dc2626; }
    .cp-actions { display:flex; justify-content:flex-end; padding-top:16px; border-top:1px solid #f3f4f6; }
    .cp-btn { display:inline-flex; align-items:center; justify-content:center; height:42px; padding:0 20px; font:inherit; font-size:14px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid transparent; transition:background .15s, border-color .15s; }
    .cp-btn:focus-visible { outline:none; box-shadow:0 0 0 3px rgba(255,98,19,.28); }
    .cp-btn--primary { background:#ff6213; color:#fff; }
    .cp-btn--primary:hover { background:#e5560f; }
    .cp-btn--outline { background:#fff; color:#374151; border-color:#d1d5db; }
    .cp-btn--outline:hover { border-color:#ff6213; color:#c2410c; }

    @media (max-width:1100px) { .cp-stats { grid-template-columns:repeat(3, 1fr); } }
    @media (max-width:900px) { .cp-grid { grid-template-columns:1fr; } .cp-readonly { text-align:left; max-width:none; } }
    @media (max-width:640px) {
        .cp-page { padding:16px; }
        .cp-stats { grid-template-columns:repeat(2, 1fr); }
        .cp-avatar { width:60px; height:60px; font-size:22px; }
        .cp-form-grid { grid-template-columns:1fr; }
        .cp-input { height:46px; font-size:16px; }
        .cp-actions .cp-btn { width:100%; height:48px; }
    }
</style>
@endpush

@section('content')
@php
    $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    $initial = mb_strtoupper(mb_substr($fullName !== '' ? $fullName : 'C', 0, 1));

    $statusLabel = match ($customer->status) {
        'active'    => 'Activo',
        'inactive'  => 'Inactivo',
        'suspended' => 'Suspendido',
        default     => $customer->status ?: '—',
    };

    $since = $customer->created_at ? $customer->created_at->isoFormat('D [de] MMMM [de] YYYY') : '—';
@endphp
<div class="cp-page">

    <div class="cp-breadcrumb">
        <span>Portal de servicios</span>
        <span>&rsaquo;</span>
        <strong>Mi perfil</strong>
    </div>

    {{-- ── Header ── --}}
    <div class="cp-header">
        <div class="cp-header-left">
            <div class="cp-avatar">{{ $initial }}</div>
            <div class="cp-who">
                <h2>{{ $fullName !== '' ? $fullName : 'Cliente' }}</h2>
                <p class="cp-line">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>
                    {{ $customer->company ?: '—' }}
                </p>
                <p class="cp-line">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Cliente desde {{ $since }}
                </p>
                <span class="cp-badge {{ $customer->status === 'active' ? 'cp-badge--active' : 'cp-badge--off' }}">{{ $statusLabel }}</span>
            </div>
        </div>
        <p class="cp-readonly">Puedes actualizar tus datos de contacto, fiscales y tu contraseña. El estado de la cuenta lo administra Equiterm.</p>
    </div>

    {{-- ── Resumen (solo conteos) ── --}}
    <div class="cp-stats">
        <div class="cp-stat"><p>Servicios totales</p><h3>{{ $counts['services_total'] }}</h3></div>
        <div class="cp-stat"><p>Servicios próximos</p><h3>{{ $counts['services_upcoming'] }}</h3></div>
        <div class="cp-stat"><p>Servicios realizados</p><h3>{{ $counts['services_completed'] }}</h3></div>
        <div class="cp-stat"><p>Reportes de servicio</p><h3>{{ $counts['reports_total'] }}</h3></div>
        <div class="cp-stat"><p>Reportes firmados</p><h3>{{ $counts['reports_signed'] }}</h3></div>
    </div>

    {{-- ── Formularios ── --}}
    <div class="cp-grid">

        {{-- Datos personales y fiscales --}}
        <div class="cp-card cp-card--form">
            <div>
                <h3>Datos de contacto y fiscales</h3>
                <p class="cp-hint">Mantén tus datos al día para recibir avisos de tus servicios. Los campos con <span class="cp-req">*</span> son obligatorios.</p>
            </div>

            <form method="POST" action="{{ route('customer.profile.update') }}" class="cp-form" novalidate>
                @csrf
                @method('PUT')

                <div class="cp-form-grid">
                    <div class="cp-fgroup @error('first_name') has-error @enderror">
                        <label class="cp-flabel" for="cp-first-name">Nombre <span class="cp-req">*</span></label>
                        <input type="text" id="cp-first-name" name="first_name" class="cp-input" maxlength="100" required
                               autocomplete="given-name" value="{{ old('first_name', $customer->first_name) }}"
                               @error('first_name') aria-invalid="true" aria-describedby="cp-first-name-err" @enderror>
                        @error('first_name') <span class="cp-error" id="cp-first-name-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('last_name') has-error @enderror">
                        <label class="cp-flabel" for="cp-last-name">Apellido</label>
                        <input type="text" id="cp-last-name" name="last_name" class="cp-input" maxlength="100"
                               autocomplete="family-name" value="{{ old('last_name', $customer->last_name) }}"
                               @error('last_name') aria-invalid="true" aria-describedby="cp-last-name-err" @enderror>
                        @error('last_name') <span class="cp-error" id="cp-last-name-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('email') has-error @enderror">
                        <label class="cp-flabel" for="cp-email">Correo electrónico <span class="cp-req">*</span></label>
                        <input type="email" id="cp-email" name="email" class="cp-input" maxlength="255" required
                               autocomplete="email" inputmode="email" value="{{ old('email', $customer->email) }}"
                               @error('email') aria-invalid="true" aria-describedby="cp-email-err" @enderror>
                        @error('email')
                            <span class="cp-error" id="cp-email-err" role="alert">{{ $message }}</span>
                        @else
                            <span class="cp-hint">Es el correo con el que inicias sesión en el portal.</span>
                        @enderror
                    </div>

                    <div class="cp-fgroup @error('phone') has-error @enderror">
                        <label class="cp-flabel" for="cp-phone">Teléfono <span class="cp-req">*</span></label>
                        <input type="tel" id="cp-phone" name="phone" class="cp-input" maxlength="20" required
                               autocomplete="tel" inputmode="tel" value="{{ old('phone', $customer->phone) }}"
                               @error('phone') aria-invalid="true" aria-describedby="cp-phone-err" @enderror>
                        @error('phone') <span class="cp-error" id="cp-phone-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup cp-full @error('company') has-error @enderror">
                        <label class="cp-flabel" for="cp-company">Empresa</label>
                        <input type="text" id="cp-company" name="company" class="cp-input" maxlength="255"
                               autocomplete="organization" value="{{ old('company', $customer->company) }}"
                               @error('company') aria-invalid="true" aria-describedby="cp-company-err" @enderror>
                        @error('company') <span class="cp-error" id="cp-company-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('rfc') has-error @enderror">
                        <label class="cp-flabel" for="cp-rfc">RFC</label>
                        <input type="text" id="cp-rfc" name="rfc" class="cp-input cp-input--upper" maxlength="13"
                               autocomplete="off" autocapitalize="characters" spellcheck="false"
                               value="{{ old('rfc', $customer->rfc) }}"
                               @error('rfc') aria-invalid="true" aria-describedby="cp-rfc-err" @enderror>
                        @error('rfc') <span class="cp-error" id="cp-rfc-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('tipo_persona') has-error @enderror">
                        <label class="cp-flabel" for="cp-tipo">Tipo de persona</label>
                        @php($tipoActual = old('tipo_persona', $customer->tipo_persona))
                        <select id="cp-tipo" name="tipo_persona" class="cp-input"
                                @error('tipo_persona') aria-invalid="true" aria-describedby="cp-tipo-err" @enderror>
                            <option value="">Sin especificar</option>
                            <option value="fisica" @selected($tipoActual === 'fisica')>Persona física</option>
                            <option value="moral" @selected($tipoActual === 'moral')>Persona moral</option>
                        </select>
                        @error('tipo_persona') <span class="cp-error" id="cp-tipo-err" role="alert">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="cp-actions">
                    <button type="submit" class="cp-btn cp-btn--primary">Guardar cambios</button>
                </div>
            </form>
        </div>

        {{-- Cambiar contraseña --}}
        <div class="cp-card cp-card--form">
            <div>
                <h3>Cambiar contraseña</h3>
                <p class="cp-hint">Usa al menos 8 caracteres. Seguirás con la sesión iniciada después del cambio.</p>
            </div>

            <form method="POST" action="{{ route('customer.profile.password') }}" class="cp-form" novalidate>
                @csrf
                @method('PUT')

                <div class="cp-form-grid cp-form-grid--one">
                    <div class="cp-fgroup @error('current_password') has-error @enderror">
                        <label class="cp-flabel" for="cp-current-password">Contraseña actual <span class="cp-req">*</span></label>
                        <input type="password" id="cp-current-password" name="current_password" class="cp-input" required
                               autocomplete="current-password"
                               @error('current_password') aria-invalid="true" aria-describedby="cp-current-password-err" @enderror>
                        @error('current_password') <span class="cp-error" id="cp-current-password-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('password') has-error @enderror">
                        <label class="cp-flabel" for="cp-new-password">Nueva contraseña <span class="cp-req">*</span></label>
                        <input type="password" id="cp-new-password" name="password" class="cp-input" required minlength="8"
                               autocomplete="new-password"
                               @error('password') aria-invalid="true" aria-describedby="cp-new-password-err" @enderror>
                        @error('password') <span class="cp-error" id="cp-new-password-err" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup">
                        <label class="cp-flabel" for="cp-confirm-password">Confirmar nueva contraseña <span class="cp-req">*</span></label>
                        <input type="password" id="cp-confirm-password" name="password_confirmation" class="cp-input" required minlength="8"
                               autocomplete="new-password">
                    </div>
                </div>

                <div class="cp-actions">
                    <button type="submit" class="cp-btn cp-btn--outline">Actualizar contraseña</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
