@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Resumen')

@push('styles')
<style>
    .sr-create-wrap { font-family: 'Inter', sans-serif; background: #F8F9FA; flex: 1; overflow-y: auto; padding: 32px 32px 48px; }
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

    .sr-summary-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,.06); margin-bottom: 16px; overflow: hidden; }
    .sr-summary-card-header { padding: 14px 20px; background: #F9FAFB; border-bottom: 1px solid #F3F4F6;
                              font-size: 13px; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 8px; }
    .sr-summary-body { padding: 16px 20px; }
    .sr-dl { display: grid; grid-template-columns: 200px 1fr; gap: 10px 16px; font-size: 13px; }
    .sr-dt { color: #6B7280; }
    .sr-dd { color: #111827; font-weight: 500; margin: 0; }
    .sr-divider { border: none; border-top: 1px solid #F3F4F6; margin: 12px 0; grid-column: 1 / -1; }

    /* Measurements mini table */
    .sr-mini-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .sr-mini-table thead th { background: #1A2535; color: #D1D5DC; padding: 8px 12px; text-align: left;
                               font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
    .sr-mini-table tbody td { padding: 8px 12px; border-bottom: 1px solid #F3F4F6; color: #374151; }
    .sr-mini-table tbody tr:last-child td { border-bottom: none; }
    .sr-dot-ok  { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #16A34A; }
    .sr-dot-bad { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #DC2626; }
    .sr-dot-nd  { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #D1D5DB; }

    /* Check list */
    .sr-check-list { display: flex; flex-wrap: wrap; gap: 8px; }
    .sr-check-chip { font-size: 12px; padding: 4px 10px; border-radius: 4px; background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

    /* Checklist steps */
    .sr-step-check { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #F3F4F6; font-size: 13px; }
    .sr-step-check:last-child { border-bottom: none; }
    .sr-check-icon-ok  { color: #16A34A; }
    .sr-check-icon-pnd { color: #D1D5DB; }

    /* Buttons */
    .sr-form-footer { padding: 16px 0; display: flex; justify-content: space-between; gap: 12px; }
    .sr-btn-primary { height: 40px; padding: 0 24px; border-radius: 8px; background: #ff6213; color: #fff;
                      font-size: 14px; font-weight: 500; font-family: 'Inter', sans-serif; border: none; cursor: pointer; transition: background .15s; }
    .sr-btn-primary:hover { background: #de4a00; }
    .sr-btn-outline { height: 40px; padding: 0 20px; border-radius: 8px; border: 1px solid #D1D5DB;
                      background: #fff; color: #374151; font-size: 13px; font-family: 'Inter', sans-serif;
                      cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #374151; }

    @media (max-width: 768px) {
        .sr-create-wrap { padding: 16px; }
        .sr-dl { grid-template-columns: 1fr; }
        .sr-step-label { display: none; }
    }
</style>
@endpush

@section('content')
<div class="sr-create-wrap">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.service-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Resumen</span>
    </div>

    <div class="sr-progress">
        @php $stepLabels = ['Datos Generales','Mediciones / Actividades','Observaciones','Evidencia','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 5 ? 'done' : ($n === 5 ? 'active' : ''); @endphp
            <div class="sr-step-item {{ $cls }}">
                <div class="sr-step-circle">@if($n < 5) ✓ @else {{ $n }} @endif</div>
                <span class="sr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- ── Info general ── --}}
    <div class="sr-summary-card">
        <div class="sr-summary-card-header">
            📋 Datos Generales
            <a href="{{ route('admin.service-reports.step', [$report, 1]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
        </div>
        <div class="sr-summary-body">
            <dl class="sr-dl">
                <dt class="sr-dt">Folio</dt>
                <dd class="sr-dd">{{ $report->report_number }}</dd>
                <dt class="sr-dt">Fecha de Servicio</dt>
                <dd class="sr-dd">{{ $report->service_date->translatedFormat('d \d\e F \d\e Y') }}</dd>
                <dt class="sr-dt">Tipo de Servicio</dt>
                <dd class="sr-dd">{{ $report->service_type_label }}</dd>
                <dt class="sr-dt">Encargado</dt>
                <dd class="sr-dd">{{ $report->assigned_user_name }}</dd>
                @if($report->week_number)
                    <dt class="sr-dt">Semana</dt>
                    <dd class="sr-dd">{{ $report->week_number }}</dd>
                @endif
                @if($report->location)
                    <dt class="sr-dt">Ubicación</dt>
                    <dd class="sr-dd">{{ $report->location }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ── Cliente ── --}}
    <div class="sr-summary-card">
        <div class="sr-summary-card-header">👤 Cliente</div>
        <div class="sr-summary-body">
            <dl class="sr-dl">
                <dt class="sr-dt">Nombre</dt>
                <dd class="sr-dd">{{ $report->customer_name }}</dd>
                @if($report->customer_company)
                    <dt class="sr-dt">Empresa</dt>
                    <dd class="sr-dd">{{ $report->customer_company }}</dd>
                @endif
                @if($report->customer_rfc)
                    <dt class="sr-dt">RFC</dt>
                    <dd class="sr-dd">{{ $report->customer_rfc }}</dd>
                @endif
                @if($report->customer_phone)
                    <dt class="sr-dt">Teléfono</dt>
                    <dd class="sr-dd">{{ $report->customer_phone }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ── Mediciones / Actividades ── --}}
    @if($report->usesMeasurementsForm() && $report->measurements->isNotEmpty())
        <div class="sr-summary-card">
            <div class="sr-summary-card-header">
                🔬 Mediciones ({{ $report->measurements->count() }})
                <a href="{{ route('admin.service-reports.step', [$report, 2]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="sr-summary-body" style="padding:0;">
                <table class="sr-mini-table">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Unidad</th>
                            <th>Resultado</th>
                            <th>Límites</th>
                            <th>Rango</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->measurements as $m)
                            <tr>
                                <td>{{ $m->parameter }}</td>
                                <td>{{ $m->unit ?? '—' }}</td>
                                <td><strong>{{ $m->result ?? '—' }}</strong></td>
                                <td style="color:#6B7280;">
                                    @if($m->limit_min !== null || $m->limit_max !== null)
                                        {{ $m->limit_min ?? '∞' }} – {{ $m->limit_max ?? '∞' }}
                                    @else —
                                    @endif
                                </td>
                                <td>
                                    @if($m->in_range === true) <span class="sr-dot-ok"></span> Dentro
                                    @elseif($m->in_range === false) <span class="sr-dot-bad"></span> Fuera
                                    @else <span class="sr-dot-nd"></span> N/D
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($report->usesActivityForm() && $report->activity)
        <div class="sr-summary-card">
            <div class="sr-summary-card-header">
                🔧 Actividades
                <a href="{{ route('admin.service-reports.step', [$report, 2]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="sr-summary-body">
                <p style="font-size:13px; color:#374151; margin:0 0 12px; white-space:pre-wrap;">{{ $report->activity->content }}</p>
                @if($report->activity->systems_checked && count($report->activity->systems_checked))
                    <p style="font-size:12px; font-weight:600; color:#6B7280; text-transform:uppercase; letter-spacing:.05em; margin:0 0 8px;">Sistemas Revisados</p>
                    <div class="sr-check-list">
                        @foreach($report->activity->systems_checked as $sys)
                            <span class="sr-check-chip">{{ $sys }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @elseif($report->usesCustomForm() && $report->customFields->isNotEmpty())
        <div class="sr-summary-card">
            <div class="sr-summary-card-header">
                ⚙️ Campos Personalizados
                <a href="{{ route('admin.service-reports.step', [$report, 2]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="sr-summary-body">
                <dl class="sr-dl">
                    @foreach($report->customFields as $cf)
                        <dt class="sr-dt">{{ $cf->field_name }}</dt>
                        <dd class="sr-dd">{{ $cf->field_value ?? '—' }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
    @endif

    {{-- ── Observaciones ── --}}
    @if($report->observations || $report->recommendations || $report->include_sampling)
        <div class="sr-summary-card">
            <div class="sr-summary-card-header">
                📝 Observaciones
                <a href="{{ route('admin.service-reports.step', [$report, 3]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="sr-summary-body">
                <dl class="sr-dl">
                    @if($report->observations)
                        <dt class="sr-dt">Observaciones</dt>
                        <dd class="sr-dd" style="white-space:pre-wrap;">{{ $report->observations }}</dd>
                    @endif
                    @if($report->recommendations)
                        <dt class="sr-dt">Recomendaciones</dt>
                        <dd class="sr-dd" style="white-space:pre-wrap;">{{ $report->recommendations }}</dd>
                    @endif
                    @if($report->include_sampling)
                        <dt class="sr-dt">Fecha de Muestreo</dt>
                        <dd class="sr-dd">
                            {{ str_pad($report->sampling_date_day, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($report->sampling_date_month, 2, '0', STR_PAD_LEFT) }}/{{ $report->sampling_date_year }}
                        </dd>
                    @endif
                    @if($report->analyst_name)
                        <dt class="sr-dt">Analista</dt>
                        <dd class="sr-dd">{{ $report->analyst_name }}{{ $report->analyst_position ? ' — '.$report->analyst_position : '' }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    @endif

    {{-- ── Verificación de etapas ── --}}
    <div class="sr-summary-card">
        <div class="sr-summary-card-header">✅ Verificación de Etapas</div>
        <div class="sr-summary-body">
            @php
                $stepsOk = [
                    'Datos Generales'           => true,
                    'Mediciones / Actividades'  => $report->current_step >= 2,
                    'Observaciones'             => $report->current_step >= 3,
                    'Evidencia'                 => $report->current_step >= 4,
                ];
            @endphp
            @foreach($stepsOk as $label => $ok)
                <div class="sr-step-check">
                    @if($ok)
                        <svg class="sr-check-icon-ok" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    @else
                        <svg class="sr-check-icon-pnd" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                    @endif
                    <span style="color:{{ $ok ? '#111827' : '#9CA3AF' }};">{{ $label }}</span>
                    @if(!$ok)
                        <span style="font-size:11px; color:#DC2626; margin-left:auto;">Pendiente</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <form method="POST" action="{{ route('admin.service-reports.save-step', [$report, 5]) }}" style="display:contents;">
        @csrf
        <div class="sr-form-footer">
            <a href="{{ route('admin.service-reports.step', [$report, 4]) }}" class="sr-btn-outline">← Anterior</a>
            <button type="submit" class="sr-btn-primary">Continuar a Firma →</button>
        </div>
    </form>

</div>
@endsection
