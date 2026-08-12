@extends('admin.layouts.master')

@section('title', 'Nueva Campaña - Admin')

@push('styles')
<style>
:root {
    --secondary-color: #ff6213;
    --button-primary-color: #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family: 'Inter', sans-serif;
}
.ec-form-page { padding: 32px; font-family: var(--font-family); max-width: 640px; margin: 0 auto; }
.ec-form-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 20px; }
.ec-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.ec-field { display: flex; flex-direction: column; gap: 6px; }
.ec-label { font-size: 13px; font-weight: 500; color: #374151; }
.ec-input, .ec-select { height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); }
.ec-error { font-size: 12px; color: #DC2626; }
.ec-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
.ec-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; }
.ec-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; }
.ec-btn-save:hover { background: var(--button-primary-color-hover); }
</style>
@endpush

@section('content')
<div class="ec-form-page">
    <h1 class="ec-form-title">Nueva campaña de email</h1>

    @if($errors->any())
        <div style="background:#FEF2F2;border:1px solid #FECACA;border-radius:8px;padding:14px 16px;color:#DC2626;font-size:13px;margin-bottom:16px;">
            <strong>Corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.email-campaigns.store') }}">
        @csrf
        <div class="ec-form-card">
            <div class="ec-field">
                <label class="ec-label" for="name">Nombre de la campaña *</label>
                <input type="text" id="name" name="name" class="ec-input" value="{{ old('name') }}" required maxlength="255">
                @error('name') <span class="ec-error">{{ $message }}</span> @enderror
            </div>

            <div class="ec-field">
                <label class="ec-label" for="template_id">Plantilla *</label>
                <select id="template_id" name="template_id" class="ec-select" required>
                    <option value="">Selecciona una plantilla</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ (string) old('template_id') === (string) $template->id ? 'selected' : '' }}>{{ $template->name }}</option>
                    @endforeach
                </select>
                @error('template_id') <span class="ec-error">{{ $message }}</span> @enderror
            </div>

            <div class="ec-field">
                <label class="ec-label" for="list_id">Lista de destinatarios *</label>
                <select id="list_id" name="list_id" class="ec-select" required>
                    <option value="">Selecciona una lista</option>
                    @foreach($lists as $list)
                        <option value="{{ $list->id }}" {{ (string) old('list_id') === (string) $list->id ? 'selected' : '' }}>{{ $list->name }}</option>
                    @endforeach
                </select>
                @error('list_id') <span class="ec-error">{{ $message }}</span> @enderror
            </div>

            <div class="ec-field">
                <label class="ec-label" for="scheduled_at">Programar envío (opcional)</label>
                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="ec-input" value="{{ old('scheduled_at') }}">
                <span style="font-size:12px;color:#9CA3AF;">
                    La campaña se crea como borrador; si indicas una fecha, prográmala desde la pantalla de detalle una vez creada.
                </span>
                @error('scheduled_at') <span class="ec-error">{{ $message }}</span> @enderror
            </div>

            <div class="ec-actions">
                <a href="{{ route('admin.email-campaigns.index') }}" class="ec-btn-cancel">Cancelar</a>
                <button type="submit" class="ec-btn-save">Guardar como borrador</button>
            </div>
        </div>
    </form>
</div>
@endsection
