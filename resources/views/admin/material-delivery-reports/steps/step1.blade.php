@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Paso 1')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step1">

    {{-- Breadcrumb --}}
    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes de Entrega de Material</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <a href="{{ route('admin.material-delivery-reports.show', $report) }}" style="color:#6B7280; text-decoration:none;">{{ $report->report_number }}</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">Paso 1 — Datos Generales</span>
    </div>

    {{-- Progress bar --}}
    <div class="mdr-progress">
        @php
            $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma'];
        @endphp
        @foreach($stepLabels as $i => $label)
            @php
                $n = $i + 1;
                $isActive = $n === 1;
                $isDone   = !$isActive && $n <= $report->current_step;
            @endphp
            <div class="mdr-step-item {{ $isActive ? 'active' : ($isDone ? 'done' : '') }}">
                <div class="mdr-step-circle">
                    @if($isDone)
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @else
                        {{ $n }}
                    @endif
                </div>
                <span class="mdr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div style="background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626;">
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $mdrCustomer = $report->customer ?? $report->salesOrder?->customer;
        $mdrCustomerName = $mdrCustomer
            ? trim(($mdrCustomer->first_name ?? '') . ' ' . ($mdrCustomer->last_name ?? ''))
            : '—';
        $mdrCustomerCompany = $mdrCustomer->company ?? null;
    @endphp

    {{-- Form card --}}
    <div class="mdr-form-card" style="margin-bottom:32px;">
        <div class="mdr-form-header">
            <h2>Paso 1 — Datos Generales</h2>
            <p>Pedido asociado y datos de la entrega</p>
        </div>

        <form method="POST" action="{{ route('admin.material-delivery-reports.save-step', [$report, 1]) }}" id="mdrStep1Form">
            @csrf

            <div class="mdr-form-body">

                {{-- ── Sección: Pedido (read-only, elegido en create) ── --}}
                <div class="mdr-grid-2">
                    <div class="mdr-section-title">Pedido Asociado</div>

                    <div class="mdr-field">
                        <label class="mdr-label">Folio de Pedido</label>
                        <input type="text" class="mdr-input" value="{{ $report->salesOrder->order_number ?? '—' }}" readonly disabled>
                    </div>

                    <div class="mdr-field">
                        <label class="mdr-label">Cliente</label>
                        <input type="text" class="mdr-input" value="{{ $mdrCustomerCompany ?: $mdrCustomerName }}" readonly disabled>
                    </div>
                </div>

                {{-- ── Sección: Entrega ── --}}
                <div class="mdr-grid-2" style="margin-top:24px;">
                    <div class="mdr-section-title">Datos de la Entrega</div>

                    <div class="mdr-field">
                        <label class="mdr-label" for="delivery_date">Fecha de Entrega <span class="mdr-req">*</span></label>
                        <input type="date" id="delivery_date" name="delivery_date"
                               class="mdr-input {{ $errors->has('delivery_date') ? 'is-invalid' : '' }}"
                               value="{{ old('delivery_date', $report->delivery_date?->format('Y-m-d')) }}" required>
                        @error('delivery_date') <span class="mdr-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="mdr-field">
                        <label class="mdr-label" for="delivery_location">Ubicación de Entrega</label>
                        <input type="text" id="delivery_location" name="delivery_location" class="mdr-input"
                               value="{{ old('delivery_location', $report->delivery_location) }}"
                               placeholder="Ej: Planta Monterrey" maxlength="200">
                    </div>
                </div>

            </div>{{-- /form-body --}}

            <div class="mdr-form-footer">
                <a href="{{ route('admin.material-delivery-reports.show', $report) }}" class="mdr-btn-outline">
                    ← Volver al reporte
                </a>
                <button type="submit" class="mdr-btn-primary">Guardar y continuar →</button>
            </div>
        </form>
    </div>

</div>
@endsection
