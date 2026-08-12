@extends('admin.layouts.master')

@section('title', 'Nueva Automatización - Admin')

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

.wf-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 860px; margin: 0 auto; }
.wf-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-form-breadcrumb-current { color: #374151; }
.wf-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wf-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.wf-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.wf-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; }
.wf-form-section-title:not(:first-child) { margin-top: 28px; }
.wf-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.wf-form-field { display: flex; flex-direction: column; gap: 6px; }
.wf-form-field-full { grid-column: 1 / -1; }
.wf-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.wf-form-label .req { color: #DC2626; }
.wf-form-input, .wf-form-select, .wf-form-textarea {
    width: 100%; padding: 0 12px; height: 40px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.wf-form-textarea { height: auto; padding: 10px 12px; min-height: 120px; resize: vertical; font-family: 'SFMono-Regular', Consolas, Menlo, monospace; }
.wf-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.wf-form-input:focus, .wf-form-select:focus, .wf-form-textarea:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.wf-form-hint { font-size: 12px; color: #9CA3AF; }
.wf-form-hint code { background: #F3F4F6; border-radius: 3px; padding: 1px 5px; font-size: 11px; }
.wf-form-error { font-size: 12px; color: #DC2626; }
.wf-form-input.is-invalid, .wf-form-select.is-invalid, .wf-form-textarea.is-invalid { border-color: #DC2626; }

.wf-checkbox-row { display: flex; align-items: center; gap: 8px; height: 40px; }
.wf-checkbox-row input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--secondary-color); cursor: pointer; }
.wf-checkbox-row label { font-size: 13px; color: #374151; cursor: pointer; }

.wf-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.wf-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.wf-btn-cancel:hover { background: #F9FAFB; }
.wf-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.wf-btn-save:hover { background: var(--button-primary-color-hover); }

@media (max-width: 640px) {
    .wf-form-page { padding: 16px; }
    .wf-form-grid { grid-template-columns: 1fr; }
    .wf-form-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="wf-form-page">

    <div>
        <div class="wf-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.workflows.index') }}" style="color:inherit;text-decoration:none;">Automatizaciones</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="wf-form-breadcrumb-current">Nueva</span>
        </div>
        <h1 class="wf-form-title">Nueva automatización</h1>
        <p class="wf-form-subtitle">Define los datos básicos del workflow. Los pasos se agregan en la pantalla siguiente.</p>
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

    <form method="POST" action="{{ route('admin.workflows.store') }}" id="workflow-form">
        @csrf

        <div class="wf-form-card">
            <h2 class="wf-form-section-title">Datos del workflow</h2>
            <div class="wf-form-grid">

                <div class="wf-form-field wf-form-field-full">
                    <label class="wf-form-label" for="name">Nombre <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="wf-form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" required maxlength="255" placeholder="Ej. Seguimiento de negocios sin actividad">
                    @error('name') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="wf-form-field">
                    <label class="wf-form-label" for="type">Tipo <span class="req">*</span></label>
                    <select id="type" name="type" class="wf-form-select @error('type') is-invalid @enderror" required>
                        <option value="">Selecciona un tipo</option>
                        @foreach(['contact' => 'Contacto', 'company' => 'Empresa', 'deal' => 'Negocio', 'date_based' => 'Basado en fecha'] as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('type') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="wf-form-hint">Define sobre qué tipo de registro corre este workflow.</span>
                    @error('type') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="wf-form-field">
                    <span class="wf-form-label">Opciones</span>
                    <div style="display:flex; gap:20px;">
                        <div class="wf-checkbox-row">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                            <label for="is_active">Activo</label>
                        </div>
                        <div class="wf-checkbox-row">
                            <input type="checkbox" id="reenrollment_allowed" name="reenrollment_allowed" value="1" {{ old('reenrollment_allowed') ? 'checked' : '' }}>
                            <label for="reenrollment_allowed">Permitir reinscripción</label>
                        </div>
                    </div>
                </div>

                <div class="wf-form-field wf-form-field-full">
                    <label class="wf-form-label" for="enrollment_trigger">Disparador de inscripción (JSON)</label>
                    <textarea id="enrollment_trigger" name="enrollment_trigger" class="wf-form-textarea @error('enrollment_trigger') is-invalid @enderror" placeholder='{"event":"updated","field":"pipeline_stage_id"}'>{{ old('enrollment_trigger') }}</textarea>
                    <span class="wf-form-hint">
                        Escribe el objeto JSON crudo que describe cuándo se inscribe un registro en este workflow
                        (aún no hay un constructor visual). Ejemplo: <code>{"event":"updated","field":"pipeline_stage_id"}</code>.
                        Déjalo vacío si por ahora no aplica.
                    </span>
                    @error('enrollment_trigger') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="wf-form-actions">
                <a href="{{ route('admin.workflows.index') }}" class="wf-btn-cancel">Cancelar</a>
                <button type="submit" class="wf-btn-save">Crear workflow</button>
            </div>
        </div>
    </form>

</div>
@endsection
