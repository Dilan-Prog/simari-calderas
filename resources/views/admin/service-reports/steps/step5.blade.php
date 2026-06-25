@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Resumen')

@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-create-wrap sr-page-step5">

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
