@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number . ' — Resumen')

@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-create-wrap mdr-page-step5">

    <div style="font-size:12px; color:#6B7280; margin-bottom:16px; display:flex; align-items:center; gap:6px;">
        <a href="{{ route('admin.material-delivery-reports.index') }}" style="color:#6B7280; text-decoration:none;">Reportes</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span style="color:#374151; font-weight:500;">{{ $report->report_number }} — Resumen</span>
    </div>

    <div class="mdr-progress">
        @php $stepLabels = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen','Firma']; @endphp
        @foreach($stepLabels as $i => $label)
            @php $n = $i + 1; $cls = $n < 5 ? 'done' : ($n === 5 ? 'active' : ''); @endphp
            <div class="mdr-step-item {{ $cls }}">
                <div class="mdr-step-circle">@if($n < 5) ✓ @else {{ $n }} @endif</div>
                <span class="mdr-step-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    @php
        $mdrCustomer = $report->customer ?? $report->salesOrder?->customer;
        $mdrCustomerName = $mdrCustomer
            ? trim(($mdrCustomer->first_name ?? '') . ' ' . ($mdrCustomer->last_name ?? ''))
            : '—';
        $mdrCustomerCompany = $mdrCustomer->company ?? null;
    @endphp

    {{-- ── Info general ── --}}
    <div class="mdr-summary-card">
        <div class="mdr-summary-card-header">
            📋 Datos Generales
            <a href="{{ route('admin.material-delivery-reports.step', [$report, 1]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
        </div>
        <div class="mdr-summary-body">
            <dl class="mdr-dl">
                <dt class="mdr-dt">Folio</dt>
                <dd class="mdr-dd">{{ $report->report_number }}</dd>
                <dt class="mdr-dt">Pedido</dt>
                <dd class="mdr-dd">{{ $report->salesOrder->order_number ?? '—' }}</dd>
                <dt class="mdr-dt">Fecha de Entrega</dt>
                <dd class="mdr-dd">{{ $report->delivery_date ? $report->delivery_date->translatedFormat('d \d\e F \d\e Y') : '—' }}</dd>
                @if($report->delivery_location)
                    <dt class="mdr-dt">Ubicación de Entrega</dt>
                    <dd class="mdr-dd">{{ $report->delivery_location }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ── Cliente ── --}}
    <div class="mdr-summary-card">
        <div class="mdr-summary-card-header">👤 Cliente</div>
        <div class="mdr-summary-body">
            <dl class="mdr-dl">
                <dt class="mdr-dt">Nombre</dt>
                <dd class="mdr-dd">{{ $mdrCustomerName }}</dd>
                @if($mdrCustomerCompany)
                    <dt class="mdr-dt">Empresa</dt>
                    <dd class="mdr-dd">{{ $mdrCustomerCompany }}</dd>
                @endif
            </dl>
        </div>
    </div>

    {{-- ── Líneas entregadas ── --}}
    @if($report->items->isNotEmpty())
        <div class="mdr-summary-card">
            <div class="mdr-summary-card-header">
                📦 Líneas Entregadas ({{ $report->items->count() }})
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 2]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="mdr-summary-body" style="padding:0;">
                <table class="mdr-mini-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Unidad</th>
                            <th>Entregado en este evento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->product_sku ?? '—' }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td><strong>{{ $item->quantity_delivered_in_event }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Observaciones ── --}}
    @if($report->observations)
        <div class="mdr-summary-card">
            <div class="mdr-summary-card-header">
                📝 Observaciones
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 3]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="mdr-summary-body">
                <p style="font-size:13px; color:#374151; margin:0; white-space:pre-wrap;">{{ $report->observations }}</p>
            </div>
        </div>
    @endif

    {{-- ── Evidencia fotográfica ── --}}
    @if($report->images->isNotEmpty())
        <div class="mdr-summary-card">
            <div class="mdr-summary-card-header">
                📷 Evidencia Fotográfica ({{ $report->images->count() }})
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 4]) }}" style="margin-left:auto; font-size:12px; color:#ff6213; text-decoration:none; font-weight:400;">Editar</a>
            </div>
            <div class="mdr-summary-body">
                <div class="mdr-summary-photos">
                    @foreach($report->images as $img)
                        <div class="mdr-summary-photo">
                            <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Evidencia' }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ── Verificación de etapas ── --}}
    <div class="mdr-summary-card">
        <div class="mdr-summary-card-header">✅ Verificación de Etapas</div>
        <div class="mdr-summary-body">
            @php
                $stepsOk = [
                    'Datos Generales'          => true,
                    'Líneas Entregadas'        => $report->current_step >= 2,
                    'Observaciones'            => $report->current_step >= 3,
                    'Evidencia Fotográfica'    => $report->current_step >= 4,
                ];
            @endphp
            @foreach($stepsOk as $label => $ok)
                <div class="mdr-step-check">
                    @if($ok)
                        <svg class="mdr-check-icon-ok" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    @else
                        <svg class="mdr-check-icon-pnd" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
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
    <form method="POST" action="{{ route('admin.material-delivery-reports.save-step', [$report, 5]) }}" style="display:contents;">
        @csrf
        <div class="mdr-form-footer">
            <a href="{{ route('admin.material-delivery-reports.step', [$report, 4]) }}" class="mdr-btn-outline">← Anterior</a>
            <button type="submit" class="mdr-btn-primary">Continuar a Firma →</button>
        </div>
    </form>

</div>
@endsection
