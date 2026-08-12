@extends('admin.layouts.master')

@section('title', 'Editar Automatización - Admin')

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

.wf-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 920px; margin: 0 auto; }
.wf-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.wf-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.wf-form-breadcrumb-current { color: #374151; }
.wf-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.wf-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.wf-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.wf-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
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
.wf-form-textarea { height: auto; padding: 10px 12px; min-height: 100px; resize: vertical; font-family: 'SFMono-Regular', Consolas, Menlo, monospace; }
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

/* ── Step builder ─────────────────────────────── */
.wf-btn-add-step { height: 34px; padding: 0 14px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: var(--font-family); cursor: pointer; transition: background .2s; }
.wf-btn-add-step:hover { background: var(--button-primary-color-hover); }

.wf-steps-list { display: flex; flex-direction: column; gap: 10px; }
.wf-steps-empty { font-size: 13px; color: var(--text-description-color); padding: 20px 0; text-align: center; }

.wf-step-card { border: 1px solid #E5E7EB; border-radius: 8px; padding: 12px 14px; background: #FAFBFC; }
.wf-step-children { margin-top: 10px; display: flex; flex-direction: column; gap: 10px; border-left: 2px dashed #E5E7EB; padding-left: 4px; }
.wf-step-card-main { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.wf-step-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; padding: 3px 8px; border-radius: 4px; flex-shrink: 0; }
.wf-step-badge-action { background: #DBEAFE; color: #1D4ED8; }
.wf-step-badge-condition { background: #FEF3C7; color: #B45309; }
.wf-step-badge-wait { background: #EDE9FE; color: #6D28D9; }
.wf-step-summary { flex: 1 1 auto; min-width: 200px; font-size: 13px; color: #111827; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.wf-step-config { font-size: 11px; color: #6B7280; background: #F3F4F6; border-radius: 4px; padding: 2px 6px; max-width: 420px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wf-step-actions { display: flex; gap: 6px; flex-shrink: 0; }
.wf-step-btn { height: 28px; padding: 0 10px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 5px; font-size: 11.5px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .15s; }
.wf-step-btn:hover { background: #F3F4F6; }
.wf-step-btn-delete:hover { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }

/* ── Modal ────────────────────────────────────── */
.wf-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(17,24,39,.5); z-index: 1000; align-items: center; justify-content: center; }
.wf-modal-overlay.active { display: flex; }
.wf-modal { background: #fff; border-radius: 10px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-md); }
.wf-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #F3F4F6; }
.wf-modal-header h3 { font-size: 15px; font-weight: 600; color: #111827; margin: 0; }
.wf-modal-close { border: none; background: none; cursor: pointer; color: #9CA3AF; font-size: 18px; line-height: 1; padding: 4px; }
.wf-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
.wf-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #F3F4F6; }
.wf-modal-errors { display: none; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 6px; padding: 10px 12px; color: #DC2626; font-size: 12.5px; }
.wf-modal-errors p { margin: 2px 0; }
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
            <span class="wf-form-breadcrumb-current">{{ $workflow->name }}</span>
        </div>
        <h1 class="wf-form-title">Editar workflow</h1>
        <p class="wf-form-subtitle">Actualiza los datos básicos y arma la secuencia de pasos que se ejecuta al inscribir un registro.</p>
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
    <form method="POST" action="{{ route('admin.workflows.update', $workflow) }}" id="workflow-form">
        @csrf
        @method('PUT')

        <div class="wf-form-card">
            <h2 class="wf-form-section-title">Datos del workflow</h2>
            <div class="wf-form-grid">

                <div class="wf-form-field wf-form-field-full">
                    <label class="wf-form-label" for="name">Nombre <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="wf-form-input @error('name') is-invalid @enderror" value="{{ old('name', $workflow->name) }}" required maxlength="255">
                    @error('name') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="wf-form-field">
                    <label class="wf-form-label" for="type">Tipo <span class="req">*</span></label>
                    <select id="type" name="type" class="wf-form-select @error('type') is-invalid @enderror" required>
                        @foreach(['contact' => 'Contacto', 'company' => 'Empresa', 'deal' => 'Negocio', 'date_based' => 'Basado en fecha'] as $value => $label)
                            <option value="{{ $value }}" {{ (string) old('type', $workflow->type) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="wf-form-field">
                    <span class="wf-form-label">Opciones</span>
                    <div style="display:flex; gap:20px;">
                        <div class="wf-checkbox-row">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $workflow->is_active) ? 'checked' : '' }}>
                            <label for="is_active">Activo</label>
                        </div>
                        <div class="wf-checkbox-row">
                            <input type="checkbox" id="reenrollment_allowed" name="reenrollment_allowed" value="1" {{ old('reenrollment_allowed', $workflow->reenrollment_allowed) ? 'checked' : '' }}>
                            <label for="reenrollment_allowed">Permitir reinscripción</label>
                        </div>
                    </div>
                </div>

                <div class="wf-form-field wf-form-field-full">
                    <label class="wf-form-label" for="enrollment_trigger">Disparador de inscripción (JSON)</label>
                    <textarea id="enrollment_trigger" name="enrollment_trigger" class="wf-form-textarea @error('enrollment_trigger') is-invalid @enderror" placeholder='{"event":"updated","field":"pipeline_stage_id"}'>{{ old('enrollment_trigger', $workflow->enrollment_trigger ? json_encode($workflow->enrollment_trigger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                    <span class="wf-form-hint">
                        Objeto JSON crudo que describe cuándo se inscribe un registro (aún no hay constructor visual).
                        Ejemplo: <code>{"event":"updated","field":"pipeline_stage_id"}</code>.
                    </span>
                    @error('enrollment_trigger') <span class="wf-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="wf-form-actions">
                <a href="{{ route('admin.workflows.index') }}" class="wf-btn-cancel">Volver</a>
                <button type="submit" class="wf-btn-save">Guardar cambios</button>
            </div>
        </div>
    </form>

    {{-- Step builder --}}
    <div class="wf-form-card">
        <h2 class="wf-form-section-title">
            Pasos del workflow
            <button type="button" class="wf-btn-add-step" id="btnAddStep">+ Agregar paso</button>
        </h2>

        <div class="wf-steps-list" id="stepsList" data-steps-base-url="{{ url('/admin/automatizaciones/'.$workflow->id.'/pasos') }}">
            @forelse($workflow->steps as $step)
                @include('admin.workflows.partials._step_item', ['step' => $step, 'depth' => 0, 'workflow' => $workflow])
            @empty
                <p class="wf-steps-empty" id="stepsEmptyMsg">Este workflow todavía no tiene pasos. Agrega el primero con "+ Agregar paso".</p>
            @endforelse
        </div>
    </div>

</div>

{{-- Modal: crear/editar paso --}}
<div class="wf-modal-overlay" id="stepModal">
    <div class="wf-modal">
        <div class="wf-modal-header">
            <h3 id="stepModalTitle">Agregar paso</h3>
            <button type="button" class="wf-modal-close" id="closeStepModal">&times;</button>
        </div>
        <div class="wf-modal-body">
            <div class="wf-modal-errors" id="stepModalErrors"></div>

            <input type="hidden" id="stepId" value="">
            <input type="hidden" id="stepParentId" value="">

            <div class="wf-form-field">
                <label class="wf-form-label" for="stepType">Tipo de paso <span class="req">*</span></label>
                <select id="stepType" class="wf-form-select">
                    <option value="action">Acción</option>
                    <option value="condition">Condición</option>
                    <option value="wait">Espera</option>
                </select>
            </div>

            {{-- Sub-campos: acción --}}
            <div class="wf-form-field" id="fieldActionType">
                <label class="wf-form-label" for="stepActionType">Tipo de acción <span class="req">*</span></label>
                <select id="stepActionType" class="wf-form-select">
                    <option value="move_deal_stage">Mover etapa del negocio</option>
                    <option value="create_task">Crear tarea</option>
                    <option value="send_email">Enviar correo</option>
                    <option value="update_field">Actualizar campo</option>
                    <option value="add_tag">Agregar etiqueta</option>
                </select>
            </div>

            <div class="wf-form-field" id="fieldActionConfig">
                <label class="wf-form-label" for="stepActionConfig">Configuración de la acción (JSON)</label>
                <textarea id="stepActionConfig" class="wf-form-textarea" placeholder='{"stage_id": 5}'></textarea>
                <span class="wf-form-hint" id="stepActionConfigHint">Ejemplo para "Mover etapa del negocio": <code>{"stage_id": 5}</code></span>
            </div>

            {{-- Sub-campos: condición --}}
            <div class="wf-form-field" id="fieldBranchCondition">
                <label class="wf-form-label" for="stepBranchCondition">Condición de la rama (JSON)</label>
                <textarea id="stepBranchCondition" class="wf-form-textarea" placeholder='{"field":"amount","operator":">","value":1000}'></textarea>
                <span class="wf-form-hint">Ejemplo: <code>{"field":"amount","operator":">","value":1000}</code>. Los pasos que agregues como sub-paso de esta condición se ejecutan cuando se cumple.</span>
            </div>

            {{-- Sub-campos: espera --}}
            <div class="wf-form-field" id="fieldWaitConfig">
                <label class="wf-form-label" for="stepWaitConfig">Duración de la espera (JSON)</label>
                <textarea id="stepWaitConfig" class="wf-form-textarea" placeholder='{"unit":"days","amount":2}'></textarea>
                <span class="wf-form-hint">Ejemplo: <code>{"unit":"days","amount":2}</code> (unit: days/hours/minutes).</span>
            </div>
        </div>
        <div class="wf-modal-footer">
            <button type="button" class="wf-btn-cancel" id="cancelStepModal">Cancelar</button>
            <button type="button" class="wf-btn-save" id="saveStepBtn">Guardar paso</button>
        </div>
    </div>
</div>

@include('admin.workflows.partials._step_form_scripts')

@push('styles')
    @vite(['resources/css/admin/pages/automations.css'])
@endpush
@endsection
