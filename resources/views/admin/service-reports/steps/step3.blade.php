@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 3')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step3">

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
