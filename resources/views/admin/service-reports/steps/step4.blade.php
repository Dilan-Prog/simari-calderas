@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 4')

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
    .sr-form-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
    .sr-form-footer { padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: space-between; gap: 12px; }
    .sr-btn-primary { height: 40px; padding: 0 20px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }
    @media (max-width: 768px) { .sr-create-wrap { padding: 16px; } .sr-step-label { display: none; } }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Paso 4</span>
    </div>

    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 4 ? 'done' : ($n === 4 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">@if($n < 4) ✓ @else {{ $n }} @endif</div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="sr-form-card">
        <div style="padding:20px 24px; border-bottom:1px solid #F3F4F6;">
            <h2 style="margin:0 0 4px; font-size:16px; font-weight:600; color:#111827;">Paso 4 — Evidencia Fotográfica</h2>
            <p style="margin:0; font-size:13px; color:#6B7280;">Adjunta imágenes del servicio realizado</p>
        </div>

        <div style="padding:48px 24px; text-align:center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px; display:block;">
                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                <circle cx="9" cy="9" r="2"/>
                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
            </svg>
            <p style="font-size:15px; font-weight:600; color:#374151; margin:0 0 8px;">Módulo de imágenes próximamente</p>
            <p style="font-size:13px; color:#6B7280; margin:0 0 24px; max-width:400px; margin-left:auto; margin-right:auto;">
                La carga de evidencia fotográfica estará disponible en la próxima versión.
                Puedes continuar con el siguiente paso.
            </p>
        </div>

        <form method="POST" action="{{ route('service-reports.save-step', [$report, 4]) }}">
            @csrf
            <div class="sr-form-footer">
                <a href="{{ route('service-reports.step', [$report, 3]) }}" class="sr-btn-outline">← Anterior</a>
                <button type="submit" class="sr-btn-primary">Continuar →</button>
            </div>
        </form>
    </div>
</div>
@endsection
