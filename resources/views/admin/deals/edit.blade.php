@extends('admin.layouts.master')

@section('title', 'Editar Negocio - Admin')

@push('styles')
@vite('resources/css/admin/pages/pipeline.css')
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

.deal-form-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; max-width: 860px; margin: 0 auto; }
.deal-form-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.deal-form-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.deal-form-breadcrumb-current { color: #374151; }
.deal-form-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.deal-form-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0 0 8px; }

.deal-form-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.deal-form-section-title { font-size: 14px; font-weight: 600; color: #111827; margin: 0 0 16px; padding-bottom: 10px; border-bottom: 1px solid #F3F4F6; }
.deal-form-section-title:not(:first-child) { margin-top: 28px; }
.deal-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.deal-form-field { display: flex; flex-direction: column; gap: 6px; }
.deal-form-field-full { grid-column: 1 / -1; }
.deal-form-label { font-size: 13px; font-weight: 500; color: #374151; }
.deal-form-label .req { color: #DC2626; }
.deal-form-input, .deal-form-select, .deal-form-textarea {
    width: 100%; height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px;
    font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box;
}
.deal-form-textarea { height: auto; padding: 10px 12px; min-height: 90px; resize: vertical; }
.deal-form-select { appearance: none; cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px; }
.deal-form-input:focus, .deal-form-select:focus, .deal-form-textarea:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.deal-form-hint { font-size: 12px; color: #9CA3AF; }
.deal-form-error { font-size: 12px; color: #DC2626; }
.deal-form-input.is-invalid, .deal-form-select.is-invalid, .deal-form-textarea.is-invalid { border-color: #DC2626; }

.deal-snapshot-box { grid-column: 1 / -1; background: #FAFBFC; border: 1px dashed #D1D5DB; border-radius: 6px; padding: 16px; display: none; }
.deal-snapshot-box.is-visible { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.deal-snapshot-box-label { grid-column: 1 / -1; font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }

.deal-form-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 24px; padding-top: 20px; border-top: 1px solid #F3F4F6; }
.deal-btn-cancel { height: 40px; padding: 0 16px; border: 1px solid #D1D5DB; background: #fff; color: #374151; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; display: inline-flex; align-items: center; cursor: pointer; transition: background .15s; }
.deal-btn-cancel:hover { background: #F9FAFB; }
.deal-btn-save { height: 40px; padding: 0 20px; border: none; background: var(--button-primary-color); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; }
.deal-btn-save:hover { background: var(--button-primary-color-hover); }

@media (max-width: 640px) {
    .deal-form-page { padding: 16px; }
    .deal-form-grid, .deal-snapshot-box.is-visible { grid-template-columns: 1fr; }
    .deal-form-card { padding: 16px; }
}
</style>
@endpush

@section('content')
<div class="deal-form-page">

    <div>
        <div class="deal-form-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.deals.table') }}" style="color:inherit;text-decoration:none;">Negocios</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.deals.show', $deal) }}" style="color:inherit;text-decoration:none;">{{ $deal->folio }}</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="deal-form-breadcrumb-current">Editar</span>
        </div>
        <h1 class="deal-form-title">Editar negocio</h1>
        <p class="deal-form-subtitle">{{ $deal->folio }} — {{ $deal->name }}</p>
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

    <form method="POST" action="{{ route('admin.deals.update', $deal) }}" id="deal-form">
        @csrf
        @method('PUT')

        <div class="deal-form-card">
            <h2 class="deal-form-section-title">Datos del negocio</h2>
            <div class="deal-form-grid">

                <div class="deal-form-field deal-form-field-full">
                    <label class="deal-form-label" for="name">Nombre del negocio <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="deal-form-input @error('name') is-invalid @enderror" value="{{ old('name', $deal->name) }}" required maxlength="255">
                    @error('name') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="pipeline_id">Pipeline <span class="req">*</span></label>
                    <select id="pipeline_id" name="pipeline_id" class="deal-form-select @error('pipeline_id') is-invalid @enderror" required>
                        <option value="">Selecciona un pipeline</option>
                        @foreach($pipelines as $pipeline)
                            <option value="{{ $pipeline->id }}" {{ (string) old('pipeline_id', $deal->pipeline_id) === (string) $pipeline->id ? 'selected' : '' }}>{{ $pipeline->name }}</option>
                        @endforeach
                    </select>
                    @error('pipeline_id') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="pipeline_stage_id">Etapa</label>
                    <select id="pipeline_stage_id" name="pipeline_stage_id" class="deal-form-select @error('pipeline_stage_id') is-invalid @enderror">
                        <option value="">Cargando etapas...</option>
                    </select>
                    <span class="deal-form-hint">Para mover de etapa con historial, usa el kanban.</span>
                    @error('pipeline_stage_id') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="amount">Monto</label>
                    <input type="number" id="amount" name="amount" class="deal-form-input @error('amount') is-invalid @enderror" value="{{ old('amount', $deal->amount) }}" step="0.01" min="0" placeholder="0.00">
                    @error('amount') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="currency">Moneda</label>
                    <select id="currency" name="currency" class="deal-form-select @error('currency') is-invalid @enderror">
                        <option value="MXN" {{ old('currency', $deal->currency ?? 'MXN') === 'MXN' ? 'selected' : '' }}>MXN</option>
                        <option value="USD" {{ old('currency', $deal->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                    @error('currency') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="expected_close_date">Fecha estimada de cierre</label>
                    <input type="date" id="expected_close_date" name="expected_close_date" class="deal-form-input @error('expected_close_date') is-invalid @enderror" value="{{ old('expected_close_date', optional($deal->expected_close_date)->format('Y-m-d')) }}">
                    @error('expected_close_date') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="owner_id">Responsable</label>
                    <select id="owner_id" name="owner_id" class="deal-form-select @error('owner_id') is-invalid @enderror">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) old('owner_id', $deal->owner_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('owner_id') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="source">Origen</label>
                    <input type="text" id="source" name="source" class="deal-form-input @error('source') is-invalid @enderror" value="{{ old('source', $deal->source) }}" maxlength="100" placeholder="Ej. Referencia, Sitio web, Llamada...">
                    @error('source') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="status">Estado</label>
                    <select id="status" name="status" class="deal-form-select @error('status') is-invalid @enderror">
                        <option value="open" {{ old('status', $deal->status) === 'open' ? 'selected' : '' }}>Abierto</option>
                        <option value="won"  {{ old('status', $deal->status) === 'won'  ? 'selected' : '' }}>Ganado</option>
                        <option value="lost" {{ old('status', $deal->status) === 'lost' ? 'selected' : '' }}>Perdido</option>
                    </select>
                    @error('status') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-form-field">
                    <label class="deal-form-label" for="lost_reason">Motivo de pérdida</label>
                    <input type="text" id="lost_reason" name="lost_reason" class="deal-form-input @error('lost_reason') is-invalid @enderror" value="{{ old('lost_reason', $deal->lost_reason) }}" placeholder="Solo si el estado es Perdido">
                    @error('lost_reason') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <h2 class="deal-form-section-title">Cliente / Contacto</h2>
            <div class="deal-form-grid">

                <div class="deal-form-field deal-form-field-full">
                    <label class="deal-form-label" for="customer_id">Cliente existente</label>
                    <select id="customer_id" name="customer_id" class="deal-form-select @error('customer_id') is-invalid @enderror">
                        <option value="">— Sin cliente (usar datos de contacto abajo) —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (string) old('customer_id', $deal->customer_id) === (string) $customer->id ? 'selected' : '' }}>
                                {{ trim($customer->first_name.' '.$customer->last_name) }}{{ $customer->company ? ' — '.$customer->company : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="deal-form-hint">Si no seleccionas un cliente, captura los datos de contacto manualmente.</span>
                    @error('customer_id') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>

                <div class="deal-snapshot-box" id="snapshot-box">
                    <span class="deal-snapshot-box-label">Datos de contacto (sin cliente registrado)</span>

                    <div class="deal-form-field">
                        <label class="deal-form-label" for="company_snapshot">Empresa</label>
                        <input type="text" id="company_snapshot" name="company_snapshot" class="deal-form-input @error('company_snapshot') is-invalid @enderror" value="{{ old('company_snapshot', $deal->company_snapshot) }}" maxlength="255">
                        @error('company_snapshot') <span class="deal-form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="deal-form-field">
                        <label class="deal-form-label" for="contact_snapshot_name">Nombre de contacto</label>
                        <input type="text" id="contact_snapshot_name" name="contact_snapshot_name" class="deal-form-input @error('contact_snapshot_name') is-invalid @enderror" value="{{ old('contact_snapshot_name', $deal->contact_snapshot_name) }}" maxlength="180">
                        @error('contact_snapshot_name') <span class="deal-form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="deal-form-field">
                        <label class="deal-form-label" for="contact_snapshot_email">Email de contacto</label>
                        <input type="email" id="contact_snapshot_email" name="contact_snapshot_email" class="deal-form-input @error('contact_snapshot_email') is-invalid @enderror" value="{{ old('contact_snapshot_email', $deal->contact_snapshot_email) }}" maxlength="255">
                        @error('contact_snapshot_email') <span class="deal-form-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="deal-form-field">
                        <label class="deal-form-label" for="contact_snapshot_phone">Teléfono de contacto</label>
                        <input type="text" id="contact_snapshot_phone" name="contact_snapshot_phone" class="deal-form-input @error('contact_snapshot_phone') is-invalid @enderror" value="{{ old('contact_snapshot_phone', $deal->contact_snapshot_phone) }}" maxlength="30">
                        @error('contact_snapshot_phone') <span class="deal-form-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <h2 class="deal-form-section-title">Notas</h2>
            <div class="deal-form-grid">
                <div class="deal-form-field deal-form-field-full">
                    <label class="deal-form-label" for="notes">Notas internas</label>
                    <textarea id="notes" name="notes" class="deal-form-textarea @error('notes') is-invalid @enderror">{{ old('notes', $deal->notes) }}</textarea>
                    @error('notes') <span class="deal-form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="deal-form-actions">
                <a href="{{ route('admin.deals.show', $deal) }}" class="deal-btn-cancel">Cancelar</a>
                <button type="submit" class="deal-btn-save">Guardar cambios</button>
            </div>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var pipelinesData = @json($pipelines->mapWithKeys(function ($p) {
        return [$p->id => $p->stages->map(function ($s) {
            return ['id' => $s->id, 'name' => $s->name];
        })];
    }));

    var currentPipelineId = @json($deal->pipeline_id);
    var currentStageId = @json($deal->pipeline_stage_id);
    var oldPipelineId = @json(old('pipeline_id'));
    var oldStageId = @json(old('pipeline_stage_id'));

    var pipelineSelect = document.getElementById('pipeline_id');
    var stageSelect = document.getElementById('pipeline_stage_id');

    function populateStages(pipelineId, selectedStageId) {
        stageSelect.innerHTML = '';
        var stages = pipelinesData[pipelineId] || [];

        if (!pipelineId || stages.length === 0) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = pipelineId ? 'Este pipeline no tiene etapas' : 'Selecciona un pipeline primero';
            stageSelect.appendChild(opt);
            return;
        }

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Sin etapa';
        stageSelect.appendChild(placeholder);

        stages.forEach(function (stage) {
            var opt = document.createElement('option');
            opt.value = stage.id;
            opt.textContent = stage.name;
            if (selectedStageId && String(selectedStageId) === String(stage.id)) {
                opt.selected = true;
            }
            stageSelect.appendChild(opt);
        });
    }

    pipelineSelect.addEventListener('change', function () {
        populateStages(this.value, null);
    });

    var initialPipelineId = oldPipelineId || currentPipelineId;
    var initialStageId = oldPipelineId ? oldStageId : currentStageId;
    populateStages(initialPipelineId, initialStageId);

    /* ── Customer vs snapshot toggle ───────── */
    var customerSelect = document.getElementById('customer_id');
    var snapshotBox = document.getElementById('snapshot-box');

    function toggleSnapshot() {
        if (customerSelect.value) {
            snapshotBox.classList.remove('is-visible');
        } else {
            snapshotBox.classList.add('is-visible');
        }
    }

    customerSelect.addEventListener('change', toggleSnapshot);
    toggleSnapshot();

})();
</script>
@endpush
