@extends('admin.layouts.master')

@section('title', 'Nueva Secuencia de Correo - Admin')

@push('styles')
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.es-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 860px; margin: 0 auto; }
.es-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.es-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.es-form-breadcrumb-current { color: #374151; }
.es-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.es-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.es-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.es-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; }
.es-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.es-form-field { display: flex; flex-direction: column; gap: 6px; }
.es-form-field-full { grid-column: 1 / -1; }
.es-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.es-form-label .req { color: #DC2626; }
.es-form-input, .es-form-select {
    width: 100%; padding: 0 12px; height: 40px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.es-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.es-form-input:focus, .es-form-select:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.es-form-hint { font-size: 12px; color: #9CA3AF; }
.es-form-error { font-size: 12px; color: #DC2626; }
.es-form-input.is-invalid, .es-form-select.is-invalid { border-color: #DC2626; }

.es-checkbox-row { display: flex; align-items: center; gap: 8px; height: 40px; }
.es-checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--secondary-color); cursor: pointer; }
.es-checkbox-row label { font-size: 13px; color: #374151; cursor: pointer; }

.es-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.es-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.es-btn-cancel:hover { background: #F9FAFB; }
.es-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.es-btn-save:hover { background: var(--button-primary-color-hover); }

@media (max-width: 640px) {
    .es-form-page { padding: 16px; }
    .es-form-grid { grid-template-columns: 1fr; }
    .es-form-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="es-form-page">

    <div>
        <div class="es-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.email-sequences.index') }}" style="color:inherit;text-decoration:none;">Secuencias de correo</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="es-form-breadcrumb-current">Nueva</span>
        </div>
        <h1 class="es-form-title">Nueva secuencia de correo</h1>
        <p class="es-form-subtitle">Define los datos básicos de la secuencia. Los pasos se agregan en la pantalla siguiente.</p>
    </div>

    @if($errors->any())
    <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 16px;color:#DC2626;font-size:13px;">
        <strong>Corrige los siguientes errores:</strong>
        <ul style="margin:6px 0 0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.email-sequences.store') }}" id="email-sequence-form">
        @csrf

        <div class="es-form-card">
            <h2 class="es-form-section-title">Datos de la secuencia</h2>
            <div class="es-form-grid">

                <div class="es-form-field es-form-field-full">
                    <label class="es-form-label" for="name">Nombre <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="es-form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Bienvenida a nuevos clientes">
                    @error('name') <span class="es-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="es-form-field">
                    <label class="es-form-label" for="owner_id">Propietario</label>
                    <select id="owner_id" name="owner_id" class="es-form-select @error('owner_id') is-invalid @enderror">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('owner_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('owner_id') <span class="es-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="es-form-field">
                    <span class="es-form-label">Opciones</span>
                    <div class="es-checkbox-row">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="is_active">Activa</label>
                    </div>
                </div>
            </div>

            <div class="es-form-actions">
                <a href="{{ route('admin.email-sequences.index') }}" class="es-btn-cancel">Cancelar</a>
                <button type="submit" class="es-btn-save">Crear secuencia</button>
            </div>
        </div>
    </form>

</div>
@endsection
