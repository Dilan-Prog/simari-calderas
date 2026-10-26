@extends('customers.layouts.master')

@section('title', 'Mi perfil - Portal de servicios')

@push('styles')
@include('customers._profile-styles')
@endpush

@section('content')
@php
    $fullName = $member->full_name;
    $initial = mb_strtoupper(mb_substr($fullName !== '' ? $fullName : 'U', 0, 1));
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
                <h2>{{ $fullName !== '' ? $fullName : 'Usuario' }}</h2>
                <p class="cp-line">{{ $member->email }}</p>
                <p class="cp-line">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>
                    {{ $customer->company ?: trim($customer->first_name . ' ' . $customer->last_name) }}
                </p>
                <span class="cp-badge cp-badge--active">{{ $member->role?->name ?? 'Sin rol' }}</span>
            </div>
        </div>
        <p class="cp-readonly">Eres usuario del equipo de esta cuenta. Los datos de la empresa y los accesos los administra el titular.</p>
    </div>

    <div class="cp-grid">

        {{-- Datos personales --}}
        <div class="cp-card cp-card--form">
            <div>
                <h3>Mis datos</h3>
                <p class="cp-hint">Los campos con <span class="cp-req">*</span> son obligatorios. Tu correo de acceso solo lo puede cambiar el titular.</p>
            </div>

            <form method="POST" action="{{ route('customer.profile.update') }}" class="cp-form" novalidate>
                @csrf
                @method('PUT')

                <div class="cp-form-grid">
                    <div class="cp-fgroup @error('first_name') has-error @enderror">
                        <label class="cp-flabel" for="cp-first-name">Nombre <span class="cp-req">*</span></label>
                        <input type="text" id="cp-first-name" name="first_name" class="cp-input" maxlength="100" required
                               autocomplete="given-name" value="{{ old('first_name', $member->first_name) }}">
                        @error('first_name') <span class="cp-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('last_name') has-error @enderror">
                        <label class="cp-flabel" for="cp-last-name">Apellido</label>
                        <input type="text" id="cp-last-name" name="last_name" class="cp-input" maxlength="100"
                               autocomplete="family-name" value="{{ old('last_name', $member->last_name) }}">
                        @error('last_name') <span class="cp-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup">
                        <label class="cp-flabel" for="cp-email">Correo electrónico</label>
                        <input type="email" id="cp-email" class="cp-input" value="{{ $member->email }}" readonly disabled>
                    </div>

                    <div class="cp-fgroup @error('phone') has-error @enderror">
                        <label class="cp-flabel" for="cp-phone">Teléfono</label>
                        <input type="tel" id="cp-phone" name="phone" class="cp-input" maxlength="30"
                               autocomplete="tel" inputmode="tel" value="{{ old('phone', $member->phone) }}">
                        @error('phone') <span class="cp-error" role="alert">{{ $message }}</span> @enderror
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
                        <input type="password" id="cp-current-password" name="current_password" class="cp-input" required autocomplete="current-password">
                        @error('current_password') <span class="cp-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup @error('password') has-error @enderror">
                        <label class="cp-flabel" for="cp-new-password">Nueva contraseña <span class="cp-req">*</span></label>
                        <input type="password" id="cp-new-password" name="password" class="cp-input" required minlength="8" autocomplete="new-password">
                        @error('password') <span class="cp-error" role="alert">{{ $message }}</span> @enderror
                    </div>

                    <div class="cp-fgroup">
                        <label class="cp-flabel" for="cp-confirm-password">Confirmar nueva contraseña <span class="cp-req">*</span></label>
                        <input type="password" id="cp-confirm-password" name="password_confirmation" class="cp-input" required minlength="8" autocomplete="new-password">
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
