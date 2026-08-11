@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 3')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step3">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 3</span>
    </div>

    <div class="mdr-progress">
        @php $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 3 ? 'done' : ($n === 3 ? 'active' : ''); @endphp
            <div class="mdr-step-item {{ $cls }}">
                <div class="mdr-step-circle">@if($n < 3) ✓ @else {{ $n }} @endif</div>
                <span class="mdr-step-label">{{ $label }}</span>
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

    <div class="mdr-form-card">
        <div class="mdr-form-header">
            <h2>Paso 3 — Observaciones</h2>
            <p>Notas sobre la entrega (condiciones, incidencias, aclaraciones)</p>
        </div>

        <form method="POST" action="{{ route('admin.material-delivery-reports.save-step', [$report, 3]) }}">
            @csrf
            <div class="mdr-form-body">

                <div class="mdr-field">
                    <label class="mdr-label" for="observations">Observaciones</label>
                    <textarea id="observations" name="observations" class="mdr-textarea" rows="8" placeholder="Condiciones de la entrega, incidencias, aclaraciones…">{{ old('observations', $report->observations) }}</textarea>
                </div>

            </div>

            <div class="mdr-form-footer">
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 2]) }}" class="mdr-btn-outline">← Anterior</a>
                <button type="submit" class="mdr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>
</div>
@endsection
