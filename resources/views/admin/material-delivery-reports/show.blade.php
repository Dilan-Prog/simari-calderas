@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number)
@push('styles')
    @vite('resources/css/material-delivery-reports.css')
@endpush

@section('content')
<div class="mdr-show-wrap">

    @if(session('success'))
        <div class="mdr-flash-ok">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mdr-flash-err">{{ session('error') }}</div>
    @endif

    {{-- Steps progress banner (only while editing) --}}
    @if($report->isEditable() && $report->current_step < 5)
        @php
            $stepNames = ['Datos Generales','Líneas Entregadas','Observaciones','Evidencia Fotográfica','Resumen'];
        @endphp
        <div style="background:#FFF7ED; border:1px solid #FDBA74; border-radius:8px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6213" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                <span style="font-size:13px; color:#92400E; font-weight:500;">Reporte incompleto — faltan pasos por completar</span>
                <div style="display:flex; align-items:center; gap:6px;">
                    @foreach($stepNames as $i => $sName)
                        @php $sn = $i + 1; @endphp
                        <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:500;
                                     padding:2px 8px; border-radius:20px;
                                     {{ $sn <= $report->current_step ? 'background:#FEF3C7; color:#92400E; border:1px solid #FDE68A;' : 'background:#F3F4F6; color:#9CA3AF; border:1px solid #E5E7EB;' }}">
                            @if($sn <= $report->current_step)
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            @else
                                {{ $sn }}
                            @endif
                            {{ $sName }}
                        </span>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('admin.material-delivery-reports.step', [$report, $report->current_step + 1]) }}"
               style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px; height:36px; padding:0 16px; border-radius:6px;
                      background:#ff6213; color:#fff; font-size:13px; font-weight:500; text-decoration:none; white-space:nowrap;">
                Continuar → Paso {{ $report->current_step + 1 }}
            </a>
        </div>
    @endif

    {{-- Breadcrumb --}}
    <div class="mdr-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <a href="{{ route('admin.material-delivery-reports.index') }}">Reportes de Entrega de Material</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <span>{{ $report->report_number }}</span>
    </div>

    {{-- Top bar --}}
    <div class="mdr-show-topbar">
        <div class="mdr-show-topbar-left">
            <div class="mdr-show-title-row">
                <a href="{{ route('admin.material-delivery-reports.index') }}" class="mdr-btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <h1 class="mdr-show-title">{{ $report->report_number }}</h1>
                <span class="mdr-badge {{ $report->status === 'signed' ? 'mdr-badge--signed' : 'mdr-badge--draft' }}">{{ $report->status_label ?? ($report->status === 'signed' ? 'Firmado' : 'Borrador') }}</span>
            </div>
        </div>
        <div class="mdr-show-actions">
            @if($report->isEditable())
                <a href="{{ route('admin.material-delivery-reports.edit', $report) }}" class="mdr-btn mdr-btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Editar
                </a>
            @endif
            <button type="button" class="mdr-btn mdr-btn-outline" onclick="openPdfModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Vista previa PDF
            </button>
            @if($report->isEditable())
                @if($report->current_step >= 5)
                    <a href="{{ route('admin.material-delivery-reports.step', [$report, 6]) }}" class="mdr-btn mdr-btn-sign">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                        Firmar
                    </a>
                @else
                    <a href="{{ route('admin.material-delivery-reports.step', [$report, $report->current_step + 1]) }}" class="mdr-btn mdr-btn-sign">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        Continuar (Paso {{ $report->current_step + 1 }})
                    </a>
                @endif
            @endif
            @if($report->isDeletable())
                <form method="POST" action="{{ route('admin.material-delivery-reports.destroy', $report) }}" style="display:inline;"
                    onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="mdr-btn mdr-btn-danger">Eliminar</button>
                </form>
            @endif
        </div>
    </div>

    @php
        $mdrCustomer = $report->customer ?? $report->salesOrder?->customer;
        $mdrCustomerName = $mdrCustomer
            ? trim(($mdrCustomer->first_name ?? '') . ' ' . ($mdrCustomer->last_name ?? ''))
            : '—';
        $mdrCustomerCompany = $mdrCustomer->company ?? null;
    @endphp

    {{-- Two-column grid --}}
    <div class="mdr-show-grid">
        {{-- ══ LEFT — Document card ══ --}}
        <div class="mdr-doc-card">
            {{-- Document header --}}
            <div class="mdr-doc-header">
                <div class="mdr-doc-header-brand">
                    <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                             alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
                    <div class="mdr-doc-header__company-meta">
                            <p>administracion@equitermindustries.com.mx</p>
                            <p>México, Aguascalientes</p>
                        </div>
                </div>
                <div class="mdr-doc-header-folio">
                    <p class="mdr-doc-rpt-label">Reporte de Entrega de Material</p>
                    <p class="mdr-doc-rpt-number">{{ $report->report_number }}</p>
                </div>
            </div>
            <div class="mdr-doc-accent"></div>

            <div class="mdr-doc-body">

                {{-- Client + report data --}}
                <div class="mdr-doc-info-grid">
                    <div>
                        <p class="mdr-doc-section-label">Datos del Cliente</p>
                        <p class="mdr-doc-client-name">{{ $mdrCustomerCompany ?? $mdrCustomerName }}</p>
                        @if($mdrCustomerCompany)
                            <p class="mdr-doc-client-sub">{{ $mdrCustomerName }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="mdr-doc-section-label">Datos de la Entrega</p>
                        <div class="mdr-doc-kv-list">
                            <div class="mdr-doc-kv-row">
                                <span class="mdr-doc-kv-key">Pedido</span>
                                <span class="mdr-doc-kv-val">{{ $report->salesOrder->order_number ?? '—' }}</span>
                            </div>
                            <div class="mdr-doc-kv-row">
                                <span class="mdr-doc-kv-key">Fecha de Entrega</span>
                                <span class="mdr-doc-kv-val">{{ $report->delivery_date ? $report->delivery_date->translatedFormat('d \d\e F \d\e Y') : '—' }}</span>
                            </div>
                            @if($report->delivery_location)
                                <div class="mdr-doc-kv-row">
                                    <span class="mdr-doc-kv-key">Ubicación</span>
                                    <span class="mdr-doc-kv-val">{{ $report->delivery_location }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Delivered lines --}}
                @if($report->items->isNotEmpty())
                    <div class="mdr-doc-lines-wrap">
                        <p class="mdr-doc-table-label">Líneas Entregadas</p>
                        <table class="mdr-doc-lines-table">
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
                                        <td class="td-product">{{ $item->product_name }}</td>
                                        <td class="td-sku">{{ $item->product_sku ?? '—' }}</td>
                                        <td class="td-num">{{ $item->unit ?? '—' }}</td>
                                        <td class="td-result">{{ $item->quantity_delivered_in_event }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Observations --}}
                @if($report->observations)
                    <div class="mdr-doc-obs-block">
                        <p class="mdr-doc-section-label" style="margin:0 0 4px;">Observaciones</p>
                        <p>{{ $report->observations }}</p>
                    </div>
                @endif

                {{-- Photo evidence --}}
                @if($report->images->count())
                    <div class="mdr-doc-photos">
                        <p class="mdr-doc-section-label">Evidencia Fotográfica</p>
                        <div class="mdr-doc-photos-grid">
                            @foreach($report->images as $img)
                                <div class="mdr-photo-item" onclick="openLightbox('{{ $img->url }}')">
                                    <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Evidencia' }}">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Signature --}}
                @if($report->status === 'signed' && $report->signature_data)
                    <div class="mdr-doc-sig">
                        <p class="mdr-doc-section-label">Firma de Quien Recibe el Material</p>
                        <div class="mdr-doc-sig-grid">
                            <div>
                                <p class="mdr-doc-sig-sublabel">Nombre</p>
                                <p class="mdr-doc-sig-val">{{ $report->received_by_name }}</p>
                            </div>
                            <div>
                                <p class="mdr-doc-sig-sublabel">Cargo</p>
                                <p class="mdr-doc-sig-val">{{ $report->received_by_position ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="mdr-doc-sig-sublabel">Firmado</p>
                                <p class="mdr-doc-sig-val">{{ $report->signed_at ? $report->signed_at->format('d/m/Y · H:i') : '—' }}</p>
                            </div>
                        </div>
                        <div class="mdr-doc-sig-img-wrap">
                            <img src="{{ $report->signature_data }}" alt="Firma">
                        </div>
                    </div>
                @endif

            </div>{{-- /mdr-doc-body --}}
        </div>{{-- /mdr-doc-card --}}

        {{-- ══ RIGHT — Panel ══ --}}
        <div>

            {{-- Card 1: Report info --}}
            <div class="mdr-panel-card">
                <h3 class="mdr-panel-title">Información del Reporte</h3>
                <div class="mdr-info-dl">
                    <div class="mdr-info-row">
                        <span class="mdr-info-key">Folio</span>
                        <span class="mdr-info-val">{{ $report->report_number }}</span>
                    </div>
                    <div class="mdr-info-row">
                        <span class="mdr-info-key">Estado</span>
                        <span class="mdr-info-val"><span class="mdr-badge {{ $report->status === 'signed' ? 'mdr-badge--signed' : 'mdr-badge--draft' }}">{{ $report->status_label ?? ($report->status === 'signed' ? 'Firmado' : 'Borrador') }}</span></span>
                    </div>
                    <div class="mdr-info-row">
                        <span class="mdr-info-key">Pedido</span>
                        <span class="mdr-info-val">{{ $report->salesOrder->order_number ?? '—' }}</span>
                    </div>
                    <div class="mdr-info-row">
                        <span class="mdr-info-key">Fecha de Entrega</span>
                        <span class="mdr-info-val">{{ $report->delivery_date ? $report->delivery_date->format('d/m/Y') : '—' }}</span>
                    </div>
                    @if($report->delivery_location)
                        <div class="mdr-info-row">
                            <span class="mdr-info-key">Ubicación</span>
                            <span class="mdr-info-val">{{ $report->delivery_location }}</span>
                        </div>
                    @endif
                    @if($report->created_at)
                        <div class="mdr-info-row">
                            <span class="mdr-info-key">Fecha de creación</span>
                            <span class="mdr-info-val">{{ $report->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card 2: Timeline --}}
            <div class="mdr-panel-card">
                <h3 class="mdr-panel-title">Historial</h3>
                <ol class="mdr-timeline">
                    {{-- Borrador --}}
                    <li class="mdr-timeline-item">
                        <span class="mdr-timeline-dot mdr-timeline-dot--done"></span>
                        <p class="mdr-timeline-label">Borrador creado</p>
                        @if($report->created_at)
                            <p class="mdr-timeline-time">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $report->created_at->format('d M · H:i') }}
                            </p>
                        @endif
                    </li>

                    {{-- Firmado --}}
                    @if($report->status === 'signed')
                        <li class="mdr-timeline-item">
                            <span class="mdr-timeline-dot mdr-timeline-dot--current"></span>
                            <p class="mdr-timeline-label">
                                Firmado
                                <span class="mdr-timeline-badge">actual</span>
                            </p>
                            @if($report->signed_at)
                                <p class="mdr-timeline-time">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $report->signed_at->format('d M · H:i') }}
                                </p>
                            @endif
                        </li>
                    @else
                        <li class="mdr-timeline-item">
                            <span class="mdr-timeline-dot mdr-timeline-dot--pending"></span>
                            <p class="mdr-timeline-label" style="color:#9CA3AF;">Firmado</p>
                        </li>
                    @endif
                </ol>
            </div>

            {{-- Card 3: Quick actions --}}
            <div class="mdr-panel-card">
                <h3 class="mdr-panel-title">Acciones Rápidas</h3>
                <a href="{{ route('admin.material-delivery-reports.download-pdf', $report) }}" class="mdr-action-btn mdr-action-btn--pdf">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Descargar PDF
                </a>
                @if($report->isEditable() || auth()->user()->isAdmin())
                    <a href="{{ route('admin.material-delivery-reports.step', [$report, 4]) }}" class="mdr-action-btn mdr-action-btn--secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        Agregar Imágenes al reporte
                    </a>
                @endif
            </div>

        </div>{{-- /right panel --}}

    </div>{{-- /mdr-show-grid --}}

    {{-- ── Mobile sticky action footer ─────────────────────────────────────────── --}}
    <div class="mdr-show-mobile-actions">
        @if($report->isEditable())
            @if($report->current_step >= 5)
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 6]) }}" class="mdr-btn mdr-btn-sign">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Firmar Reporte
                </a>
            @else
                <a href="{{ route('admin.material-delivery-reports.step', [$report, $report->current_step + 1]) }}" class="mdr-btn mdr-btn-sign">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    Continuar — Paso {{ $report->current_step + 1 }}
                </a>
            @endif
        @endif

        <a href="{{ route('admin.material-delivery-reports.download-pdf', $report) }}" class="mdr-btn mdr-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Descargar PDF
        </a>

        @if($report->isEditable())
            <a href="{{ route('admin.material-delivery-reports.edit', $report) }}" class="mdr-btn mdr-btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                Editar
            </a>
        @endif

        @if($report->isDeletable())
            <form method="POST" action="{{ route('admin.material-delivery-reports.destroy', $report) }}"
                  style="display:contents"
                  onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')">
                @csrf @method('DELETE')
                <button type="submit" class="mdr-btn mdr-btn-danger mdr-btn-danger-link">Eliminar</button>
            </form>
        @endif
    </div>

    {{-- Lightbox --}}
    <div class="mdr-lightbox" id="mdrLightbox" onclick="closeLightbox()">
        <button class="mdr-lightbox-close" onclick="closeLightbox()">×</button>
        <img src="" id="mdrLightboxImg" alt="Evidencia" onclick="event.stopPropagation()">
    </div>

</div>{{-- /mdr-show-wrap --}}

{{-- Modal PDF --}}
<div id="mdr-pdf-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.65); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; width:90vw; max-width:960px; height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.4);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#1a1a1a; border-bottom:2px solid #ff6213; flex-shrink:0;">
            <span style="color:#fff; font-size:14px; font-weight:600;">Vista previa — {{ $report->report_number }}</span>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="{{ route('admin.material-delivery-reports.download-pdf', $report) }}"
                   style="display:inline-flex; align-items:center; gap:6px; background:#ff6213; color:#fff; border:none; padding:7px 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"/><path d="m6 11 6 6 6-6"/><path d="M19 21H5"/></svg>
                    Descargar PDF
                </a>
                <button onclick="closePdfModal()" style="background:transparent; border:none; cursor:pointer; color:#9CA3AF; font-size:20px; line-height:1; padding:4px 8px;" title="Cerrar">&times;</button>
            </div>
        </div>
        <iframe id="mdr-pdf-frame" src="" style="flex:1; border:none; background:#525659;" title="Vista previa PDF"></iframe>
        @if($report->isEditable() || auth()->user()->isAdmin())
            <div style="display:flex; align-items:center; justify-content:center; padding:12px 18px; background:#fff; border-top:1px solid #E5E7EB; flex-shrink:0;">
                <a href="{{ route('admin.material-delivery-reports.step', [$report, 4]) }}"
                   style="display:inline-flex; align-items:center; gap:6px; background:#fff; color:#374151; border:1px solid #D1D5DB; padding:7px 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    Agregar Imágenes al reporte
                </a>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function openLightbox(src) {
    document.getElementById('mdrLightboxImg').src = src;
    document.getElementById('mdrLightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('mdrLightbox').classList.remove('open');
}

function openPdfModal() {
    // Móvil: algunos navegadores (iOS Safari) no renderizan PDFs embebidos en
    // <iframe>. En pantallas angostas se abre la vista previa en una pestaña
    // nueva, donde el visor nativo del teléfono sí funciona.
    if (window.innerWidth < 1024) {
        window.open('{{ route('admin.material-delivery-reports.pdf-preview', $report) }}', '_blank');
        return;
    }
    const modal = document.getElementById('mdr-pdf-modal');
    const frame = document.getElementById('mdr-pdf-frame');
    modal.style.display = 'flex';
    if (!frame.src || frame.src === window.location.href) {
        frame.src = '{{ route('admin.material-delivery-reports.pdf-preview', $report) }}';
    }
    document.body.style.overflow = 'hidden';
}
function closePdfModal() {
    document.getElementById('mdr-pdf-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('mdr-pdf-modal').addEventListener('click', function (e) {
    if (e.target === this) closePdfModal();
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closePdfModal();
        closeLightbox();
    }
});
</script>
@endpush
