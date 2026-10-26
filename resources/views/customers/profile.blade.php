@extends('customers.layouts.master')

@section('title', 'Mi perfil - Portal de servicios')

@push('styles')
@include('customers._profile-styles')
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
