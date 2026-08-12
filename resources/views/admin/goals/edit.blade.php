@extends('admin.layouts.master')

@section('title', 'Editar Meta - Admin')

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

.goal-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 720px; margin: 0 auto; }
.goal-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.goal-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.goal-form-breadcrumb-current { color: #374151; }
.goal-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.goal-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.goal-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.goal-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.goal-form-field { display: flex; flex-direction: column; gap: 6px; }
.goal-form-field-full { grid-column: 1 / -1; }
.goal-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.goal-form-label .req { color: #DC2626; }
.goal-form-input, .goal-form-select {
    width: 100%; height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.goal-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.goal-form-input:focus, .goal-form-select:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.goal-form-hint { font-size: 12px; color: #9CA3AF; }
.goal-form-error { font-size: 12px; color: #DC2626; }
.goal-form-input.is-invalid, .goal-form-select.is-invalid { border-color: #DC2626; }

.goal-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.goal-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.goal-btn-cancel:hover { background: #F9FAFB; }
.goal-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.goal-btn-save:hover { background: var(--button-primary-color-hover); }

@media (max-width: 640px) {
    .goal-form-page { padding: 16px; }
    .goal-form-grid { grid-template-columns: 1fr; }
    .goal-form-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="goal-form-page">

    <div>
        <div class="goal-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.goals.index') }}" style="color:inherit;text-decoration:none;">Metas</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="goal-form-breadcrumb-current">Editar</span>
        </div>
        <h1 class="goal-form-title">Editar meta</h1>
        <p class="goal-form-subtitle">{{ $goal->name }}</p>
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

    <form method="POST" action="{{ route('admin.goals.update', $goal) }}">
        @csrf
        @method('PUT')

        <div class="goal-form-card">
            <div class="goal-form-grid">

                <div class="goal-form-field goal-form-field-full">
                    <label class="goal-form-label" for="name">Nombre de la meta <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="goal-form-input @error('name') is-invalid @enderror" value="{{ old('name', $goal->name) }}" required maxlength="255">
                    @error('name') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="goal-form-field">
                    <label class="goal-form-label" for="metric_type">Métrica <span class="req">*</span></label>
                    <select id="metric_type" name="metric_type" class="goal-form-select @error('metric_type') is-invalid @enderror" required>
                        <option value="">Selecciona una métrica</option>
                        <option value="deals_won_amount" {{ old('metric_type', $goal->metric_type) === 'deals_won_amount' ? 'selected' : '' }}>Monto de negocios ganados</option>
                        <option value="deals_won_count" {{ old('metric_type', $goal->metric_type) === 'deals_won_count' ? 'selected' : '' }}>Negocios ganados (cantidad)</option>
                        <option value="emails_sent" {{ old('metric_type', $goal->metric_type) === 'emails_sent' ? 'selected' : '' }}>Correos enviados</option>
                    </select>
                    @error('metric_type') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="goal-form-field">
                    <label class="goal-form-label" for="target_value">Valor objetivo <span class="req">*</span></label>
                    <input type="number" id="target_value" name="target_value" class="goal-form-input @error('target_value') is-invalid @enderror" value="{{ old('target_value', $goal->target_value) }}" step="0.01" min="0" required>
                    @error('target_value') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="goal-form-field">
                    <label class="goal-form-label" for="period_start">Inicio del período <span class="req">*</span></label>
                    <input type="date" id="period_start" name="period_start" class="goal-form-input @error('period_start') is-invalid @enderror" value="{{ old('period_start', optional($goal->period_start)->format('Y-m-d')) }}" required>
                    @error('period_start') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="goal-form-field">
                    <label class="goal-form-label" for="period_end">Fin del período <span class="req">*</span></label>
                    <input type="date" id="period_end" name="period_end" class="goal-form-input @error('period_end') is-invalid @enderror" value="{{ old('period_end', optional($goal->period_end)->format('Y-m-d')) }}" required>
                    @error('period_end') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="goal-form-field goal-form-field-full">
                    <label class="goal-form-label" for="owner_id">Responsable</label>
                    <select id="owner_id" name="owner_id" class="goal-form-select @error('owner_id') is-invalid @enderror">
                        <option value="">Meta de equipo</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('owner_id', $goal->owner_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <span class="goal-form-hint">Deja en blanco para una meta de equipo (sin responsable individual).</span>
                    @error('owner_id') <span class="goal-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="goal-form-actions">
                <a href="{{ route('admin.goals.index') }}" class="goal-btn-cancel">Cancelar</a>
                <button type="submit" class="goal-btn-save">Guardar cambios</button>
            </div>
        </div>
    </form>

</div>
@endsection
