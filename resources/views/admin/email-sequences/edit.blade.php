@extends('admin.layouts.master')

@section('title', 'Editar Secuencia de Correo - Admin')

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

.es-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 920px; margin: 0 auto; }
.es-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.es-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.es-form-breadcrumb-current { color: #374151; }
.es-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.es-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.es-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.es-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
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

/* ── Step builder ─────────────────────────────── */
.es-btn-add-step { height: 34px; padding: 0 14px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: var(--font-family); cursor: pointer; transition: background .2s; }
.es-btn-add-step:hover { background: var(--button-primary-color-hover); }
.es-btn-add-step:disabled { opacity: .6; cursor: not-allowed; }

.es-steps-list { display: flex; flex-direction: column; gap: 10px; }
.es-steps-empty { font-size: 13px; color: var(--text-description-color); padding: 20px 0; text-align: center; }

.es-step-card { border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; background: #FAFBFC; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.es-step-order { font-size: 12px; font-weight: 700; color: #fff; background: var(--secondary-color); width: 24px; height: 24px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.es-step-fields { flex: 1 1 auto; display: flex; gap: 10px; flex-wrap: wrap; min-width: 260px; }
.es-step-field { display: flex; flex-direction: column; gap: 4px; }
.es-step-field label { font-size: 11px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .03em; }
.es-step-select { height: 34px; padding: 0 10px; border: 1px solid #D1D5DB; border-radius: 5px; font-size: 12.5px; font-family: var(--font-family); min-width: 220px; background: #fff; }
.es-step-number { height: 34px; width: 90px; padding: 0 10px; border: 1px solid #D1D5DB; border-radius: 5px; font-size: 12.5px; font-family: var(--font-family); }
.es-step-btn-remove { height: 32px; padding: 0 12px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 5px; font-size: 11.5px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .15s; flex-shrink: 0; }
.es-step-btn-remove:hover { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }

.es-enroll-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.es-enroll-row .es-form-field { flex: 1 1 260px; }
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
            <span class="es-form-breadcrumb-current">{{ $emailSequence->name }}</span>
        </div>
        <h1 class="es-form-title">Editar secuencia</h1>
        <p class="es-form-subtitle">Actualiza los datos básicos y arma la secuencia de correos que se envían al cliente inscrito.</p>
    </div>

    @if(session('success'))
    <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px;padding:14px 16px;color:#15803D;font-size:13px;">
        {{ session('success') }}
    </div>
    @endif

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

    {{-- Datos básicos --}}
    <form method="POST" action="{{ route('admin.email-sequences.update', $emailSequence) }}" id="email-sequence-form">
        @csrf
        @method('PUT')

        <div class="es-form-card">
            <h2 class="es-form-section-title">Datos de la secuencia</h2>
            <div class="es-form-grid">

                <div class="es-form-field es-form-field-full">
                    <label class="es-form-label" for="name">Nombre <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="es-form-input @error('name') is-invalid @enderror" value="{{ old('name', $emailSequence->name) }}" required maxlength="255">
                    @error('name') <span class="es-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="es-form-field">
                    <label class="es-form-label" for="owner_id">Propietario</label>
                    <select id="owner_id" name="owner_id" class="es-form-select @error('owner_id') is-invalid @enderror">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('owner_id', $emailSequence->owner_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('owner_id') <span class="es-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="es-form-field">
                    <span class="es-form-label">Opciones</span>
                    <div class="es-checkbox-row">
                        <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $emailSequence->is_active) ? 'checked' : '' }}>
                        <label for="is_active">Activa</label>
                    </div>
                </div>
            </div>

            <div class="es-form-actions">
                <a href="{{ route('admin.email-sequences.index') }}" class="es-btn-cancel">Volver</a>
                <button type="submit" class="es-btn-save">Guardar cambios</button>
            </div>
        </div>
    </form>

    {{-- Step builder --}}
    <div class="es-form-card">
        <h2 class="es-form-section-title">
            Pasos de la secuencia
            <button type="button" class="es-btn-add-step" id="btnAddStep" @if($templates->isEmpty()) disabled title="Crea primero una plantilla de correo" @endif>+ Agregar paso</button>
        </h2>

        <div class="es-steps-list"
             id="stepsList"
             data-add-step-url="{{ route('admin.email-sequences.add-step', $emailSequence) }}"
             data-remove-step-base-url="{{ url('/admin/marketing-por-correo/secuencias/pasos') }}">
            @forelse($emailSequence->steps as $step)
                @include('admin.email-sequences.partials._step_item', ['step' => $step, 'templates' => $templates])
            @empty
                <p class="es-steps-empty" id="stepsEmptyMsg">Esta secuencia todavía no tiene pasos. Agrega el primero con "+ Agregar paso".</p>
            @endforelse
        </div>
    </div>

    {{-- Inscribir cliente --}}
    <div class="es-form-card">
        <h2 class="es-form-section-title">Inscribir cliente</h2>
        <form method="POST" action="{{ route('admin.email-sequences.enroll-customer', $emailSequence) }}" class="es-enroll-row">
            @csrf
            <div class="es-form-field">
                <label class="es-form-label" for="customer_id">Cliente</label>
                <select id="customer_id" name="customer_id" class="es-form-select" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="es-btn-save" style="height:40px;">Inscribir</button>
        </form>
    </div>

</div>

@include('admin.email-sequences.partials._scripts')
@endsection
