@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 3')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; min-height: 100%; padding: 32px; }
    .sr-progress { display: flex; align-items: center; background: #fff; border-radius: 8px;
                   box-shadow: 0 1px 2px rgba(0,0,0,.06); padding: 20px 24px; margin-bottom: 24px; }
    .sr-step-item { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .sr-step-item:not(:last-child)::after { content: ''; position: absolute; top: 16px; left: 60%; width: 80%; height: 2px; background: #E5E7EB; z-index: 0; }
    .sr-step-item.done::after { background: #ff6213; }
    .sr-step-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                      font-size: 13px; font-weight: 600; z-index: 1; border: 2px solid #E5E7EB; background: #fff; color: #9CA3AF; }
    .sr-step-item.active .sr-step-circle { border-color: #ff6213; background: #ff6213; color: #fff; }
    .sr-step-item.done   .sr-step-circle { border-color: #ff6213; background: #fff; color: #ff6213; }
    .sr-step-label { font-size: 11px; color: #9CA3AF; margin-top: 6px; text-align: center; white-space: nowrap; }
    .sr-step-item.active .sr-step-label, .sr-step-item.done .sr-step-label { color: #374151; }

    .sr-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); display: flex; flex-direction: column; max-height: 420px; overflow-y: auto; }
    .sr-form-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
    .sr-form-header h2 { margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #111827; }
    .sr-form-header p  { margin: 0; font-size: 13px; color: #6B7280; }
    .sr-form-body  { padding: 24px; }
    .sr-form-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; gap: 12px; }

    .sr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .sr-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .sr-label { font-size: 13px; font-weight: 500; color: #374151; display: block; margin-bottom: 6px; }
    .sr-label .sr-req { color: #ff6213; }
    .sr-input, .sr-select, .sr-textarea {
        height: 40px; padding: 0 12px; border-radius: 6px; border: 1px solid #D1D5DB;
        font-size: 13px; font-family: 'Inter', sans-serif; color: #111827; background: #fff;
        outline: none; width: 100%; box-sizing: border-box; transition: border-color .15s, box-shadow .15s;
    }
    .sr-textarea { height: auto; padding: 10px 12px; resize: vertical; min-height: 120px; }
    .sr-input:focus, .sr-select:focus, .sr-textarea:focus { border-color: #ff6213; box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
    .sr-field { margin-bottom: 16px; }
    .sr-section-divider { border: none; border-top: 1px solid #F3F4F6; margin: 24px 0; }

    /* Toggle */
    .sr-toggle-row { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .sr-toggle { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .sr-toggle input { opacity: 0; width: 0; height: 0; }
    .sr-toggle-slider {
        position: absolute; inset: 0; background: #D1D5DB; border-radius: 12px; cursor: pointer; transition: background .2s;
    }
    .sr-toggle-slider::before {
        content: ''; position: absolute; width: 18px; height: 18px; border-radius: 50%; background: #fff;
        left: 3px; top: 3px; transition: transform .2s;
    }
    .sr-toggle input:checked + .sr-toggle-slider { background: #ff6213; }
    .sr-toggle input:checked + .sr-toggle-slider::before { transform: translateX(20px); }
    .sr-toggle-label { font-size: 14px; font-weight: 500; color: #111827; }

    .sr-sampling-block { background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 8px; padding: 16px 20px; margin-top: 8px; }

    .sr-btn-primary { height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-grid-2, .sr-grid-3 { grid-template-columns: 1fr; }
        .sr-step-label { display: none; }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 3</span>
    </div>

    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 3 ? 'done' : ($n === 3 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">@if($n < 3) ✓ @else {{ $n }} @endif</div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="sr-form-card">
        <div class="sr-form-header">
            <h2>Paso 3 — Observaciones y Recomendaciones</h2>
            <p>Notas técnicas, conclusiones y datos de muestreo</p>
        </div>

        <form method="POST" action="{{ route('admin.service-reports.save-step', [$report, 3]) }}">
            @csrf
            <div class="sr-form-body">

                <div class="sr-field">
                    <label class="sr-label" for="observations">Observaciones</label>
                    <textarea id="observations" name="observations" class="sr-textarea" rows="5" placeholder="Condiciones observadas, hallazgos técnicos…">{{ old('observations', $report->observations) }}</textarea>
                </div>

                <div class="sr-field">
                    <label class="sr-label" for="recommendations">Recomendaciones</label>
                    <textarea id="recommendations" name="recommendations" class="sr-textarea" rows="5" placeholder="Acciones correctivas, mantenimiento sugerido…">{{ old('recommendations', $report->recommendations) }}</textarea>
                </div>

                <hr class="sr-section-divider">

                {{-- Muestreo toggle --}}
                <div class="sr-toggle-row">
                    <label class="sr-toggle">
                        <input type="checkbox" id="includeSampling" name="include_sampling" value="1"
                            {{ old('include_sampling', $report->include_sampling) ? 'checked' : '' }}>
                        <span class="sr-toggle-slider"></span>
                    </label>
                    <span class="sr-toggle-label">Incluir datos de muestreo</span>
                </div>

                <div id="samplingBlock" style="{{ old('include_sampling', $report->include_sampling) ? '' : 'display:none;' }}">
                    <div class="sr-sampling-block">
                        <p style="font-size:13px; font-weight:500; color:#92400E; margin:0 0 16px;">Fecha de Toma de Muestra</p>
                        <div class="sr-grid-3">
                            <div class="sr-field">
                                <label class="sr-label">Día <span class="sr-req">*</span></label>
                                <input type="number" name="sampling_date_day" class="sr-input" min="1" max="31"
                                    value="{{ old('sampling_date_day', $report->sampling_date_day) }}" placeholder="DD">
                            </div>
                            <div class="sr-field">
                                <label class="sr-label">Mes <span class="sr-req">*</span></label>
                                <input type="number" name="sampling_date_month" class="sr-input" min="1" max="12"
                                    value="{{ old('sampling_date_month', $report->sampling_date_month) }}" placeholder="MM">
                            </div>
                            <div class="sr-field">
                                <label class="sr-label">Año <span class="sr-req">*</span></label>
                                <input type="number" name="sampling_date_year" class="sr-input" min="2000" max="2099"
                                    value="{{ old('sampling_date_year', $report->sampling_date_year) }}" placeholder="YYYY">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="sr-section-divider">

                {{-- Analista --}}
                <p style="font-size:13px; font-weight:600; color:#374151; margin:0 0 16px;">Datos del Analista</p>
                <div class="sr-grid-2">
                    <div class="sr-field">
                        <label class="sr-label" for="analyst_name">Nombre del Analista</label>
                        <input type="text" id="analyst_name" name="analyst_name" class="sr-input"
                            value="{{ old('analyst_name', $report->analyst_name) }}" maxlength="150" placeholder="Nombre completo">
                    </div>
                    <div class="sr-field">
                        <label class="sr-label" for="analyst_position">Cargo / Puesto</label>
                        <input type="text" id="analyst_position" name="analyst_position" class="sr-input"
                            value="{{ old('analyst_position', $report->analyst_position) }}" maxlength="100" placeholder="Ej: Químico Analista">
                    </div>
                </div>

            </div>

            <div class="sr-form-footer">
                <a href="{{ route('admin.service-reports.step', [$report, 2]) }}" class="sr-btn-outline">← Anterior</a>
                <button type="submit" class="sr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('includeSampling').addEventListener('change', function () {
    document.getElementById('samplingBlock').style.display = this.checked ? '' : 'none';
});
</script>
@endpush
