@extends('customers.layouts.master')
@section('title', 'Reporte ' . $report->report_number)
@push('styles')
    @vite('resources/css/service-reports.css')
@endpush

@section('content')
<div class="sr-show-wrap">

    {{-- Breadcrumb --}}
    <div class="sr-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <a href="{{ route('customer.service-reports.index') }}">Reportes de Servicio</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <span>{{ $report->report_number }}</span>
    </div>

    {{-- Top bar --}}
    <div class="sr-show-topbar">
        <div class="sr-show-topbar-left">
            <div class="sr-show-title-row">
                <a href="{{ route('customer.service-reports.index') }}" class="sr-btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <h1 class="sr-show-title">{{ $report->report_number }}</h1>
                <span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span>
            </div>
        </div>
        <div class="sr-show-actions">
            <button type="button" class="sr-btn sr-btn-outline" onclick="openPdfModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Vista previa PDF
            </button>
            <a href="{{ route('customer.service-reports.download-pdf', $report) }}" class="sr-btn sr-btn-sign">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Descargar PDF
            </a>
        </div>
    </div>

    {{-- Two-column grid --}}
    <div class="sr-show-grid">
        {{-- ══ LEFT — Document card ══ --}}
        <div class="sr-doc-card">
            <div class="sr-doc-header">
                <div class="sr-doc-header-brand">
                    <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                             alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
                    <div class="sr-doc-header__company-meta">
                            <p>administracion@equitermindustries.com.mx</p>
                            <p>México, Aguascalientes</p>
                        </div>
                </div>
                <div class="sr-doc-header-folio">
                    <p class="sr-doc-rpt-label">Reporte Técnico</p>
                    <p class="sr-doc-rpt-number">{{ $report->report_number }}</p>
                </div>
            </div>
            <div class="sr-doc-accent"></div>

            <div class="sr-doc-body">

                {{-- Client + report data --}}
                <div class="sr-doc-info-grid">
                    <div>
                        <p class="sr-doc-section-label">Datos del Cliente</p>
                        <p class="sr-doc-client-name">{{ $report->customer_company ?? $report->customer_name }}</p>
                        @if($report->customer_company)
                            <p class="sr-doc-client-sub">{{ $report->customer_name }}</p>
                        @endif
                        @if($report->customer_rfc || $report->customer_phone)
                            <p class="sr-doc-client-sub">
                                @if($report->customer_rfc) RFC: {{ $report->customer_rfc }} @endif
                                @if($report->customer_rfc && $report->customer_phone) · @endif
                                @if($report->customer_phone) {{ $report->customer_phone }} @endif
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="sr-doc-section-label">Datos del Reporte</p>
                        <div class="sr-doc-kv-list">
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Fecha</span>
                                <span class="sr-doc-kv-val">{{ $report->service_date?->translatedFormat('d \d\e F \d\e Y') }}</span>
                            </div>
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Tipo</span>
                                <span class="sr-doc-kv-val">{{ $report->service_type_label }}</span>
                            </div>
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Encargado</span>
                                <span class="sr-doc-kv-val">{{ $report->assignedUser?->first_name }} {{ $report->assignedUser?->last_name }}</span>
                            </div>
                            @if($report->week_number)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">Semana</span>
                                    <span class="sr-doc-kv-val">{{ $report->week_number }}</span>
                                </div>
                            @endif
                            @if($report->location)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">Ubicación</span>
                                    <span class="sr-doc-kv-val">{{ $report->location }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Measurements --}}
                @if($report->usesMeasurementsForm() && $report->measurements->isNotEmpty())
                    <div class="sr-doc-meas-wrap">
                        <p class="sr-doc-table-label">Parámetros de Medición</p>
                        <table class="sr-doc-meas-table">
                            <thead>
                                <tr>
                                    <th>Parámetro</th>
                                    <th>Unidades</th>
                                    <th>Resultado</th>
                                    <th>Mín.</th>
                                    <th>Máx.</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report->measurements as $m)
                                    <tr>
                                        <td class="td-param">{{ $m->parameter }}</td>
                                        <td class="td-unit">{{ $m->unit ?? '—' }}</td>
                                        <td class="td-result">{{ $m->result ?? '—' }}</td>
                                        <td class="td-num">{{ $m->limit_min !== null ? $m->limit_min : '—' }}</td>
                                        <td class="td-num">{{ $m->limit_max !== null ? $m->limit_max : '—' }}</td>
                                        <td>
                                            @if($m->in_range === true)
                                                <span style="color:#16A34A; font-weight:500;">✓ Dentro</span>
                                            @elseif($m->in_range === false)
                                                <span style="color:#DC2626; font-weight:500;">✗ Fuera</span>
                                            @else
                                                <span style="color:#9CA3AF;">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Activities --}}
                @if($report->usesActivityForm() && $report->activity)
                    <div class="sr-doc-activity">
                        <p class="sr-doc-section-label">Actividades Realizadas</p>
                        @if($report->activity->content)
                            <p>{{ $report->activity->content }}</p>
                        @endif
                        @if($report->activity->systems_checked && count($report->activity->systems_checked))
                            <p class="sr-doc-section-label" style="margin-top:12px;">Sistemas Revisados</p>
                            <div class="sr-check-list">
                                @foreach($report->activity->systems_checked as $sys)
                                    <span class="sr-check-chip">{{ $sys }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Custom fields --}}
                @if($report->usesCustomForm() && $report->customFields->isNotEmpty())
                    <div style="margin-bottom:24px;">
                        <p class="sr-doc-section-label">Campos Personalizados</p>
                        <div class="sr-doc-kv-list">
                            @foreach($report->customFields as $cf)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">{{ $cf->field_name }}</span>
                                    <span class="sr-doc-kv-val">{{ $cf->field_value ?? '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Observations --}}
                @if($report->observations)
                    <div class="sr-doc-obs-block">
                        <p class="sr-doc-section-label" style="margin:0 0 4px;">Observaciones</p>
                        <p>{{ $report->observations }}</p>
                    </div>
                @endif

                @if($report->recommendations)
                    <div class="sr-doc-obs-block">
                        <p class="sr-doc-section-label" style="margin:0 0 4px;">Recomendaciones</p>
                        <p>{{ $report->recommendations }}</p>
                    </div>
                @endif

                @if($report->include_sampling)
                    <div class="sr-doc-obs-block">
                        <p class="sr-doc-section-label" style="margin:0 0 4px;">Datos de Muestreo</p>
                        <div class="sr-doc-kv-list">
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Fecha de Muestra</span>
                                <span class="sr-doc-kv-val">{{ str_pad($report->sampling_date_day,2,'0',STR_PAD_LEFT) }}/{{ str_pad($report->sampling_date_month,2,'0',STR_PAD_LEFT) }}/{{ $report->sampling_date_year }}</span>
                            </div>
                            @if($report->analyst_name)
                                <div class="sr-doc-kv-row">
                                    <span class="sr-doc-kv-key">Analista</span>
                                    <span class="sr-doc-kv-val">{{ $report->analyst_name }}</span>
                                </div>
                                @if($report->analyst_position)
                                    <div class="sr-doc-kv-row">
                                        <span class="sr-doc-kv-key">Cargo Analista</span>
                                        <span class="sr-doc-kv-val">{{ $report->analyst_position }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Photo evidence --}}
                @if($report->images->count())
                    <div class="sr-doc-photos">
                        <p class="sr-doc-section-label">Evidencia Fotográfica</p>
                        <div class="sr-doc-photos-grid">
                            <div>
                                <p class="sr-doc-photos-col-label">Antes del servicio</p>
                                <div class="sr-doc-photos-inner">
                                    @foreach($report->images->take(2) as $img)
                                        <div class="sr-photo-item" onclick="openLightbox('{{ asset($img->path) }}')">
                                            <img src="{{ asset($img->path) }}" alt="Evidencia">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="sr-doc-photos-col-label">Después del servicio</p>
                                <div class="sr-doc-photos-inner">
                                    @foreach($report->images->slice(2, 2) as $img)
                                        <div class="sr-photo-item" onclick="openLightbox('{{ $img->url }}')">
                                            <img src="{{ $img->url }}" alt="Evidencia">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Signature --}}
                @if($report->status === 'signed' && $report->signature_data)
                    <div class="sr-doc-sig">
                        <p class="sr-doc-section-label">Firma del Encargado</p>
                        <div class="sr-doc-sig-grid">
                            <div>
                                <p class="sr-doc-sig-sublabel">Nombre</p>
                                <p class="sr-doc-sig-val">{{ $report->signature_name }}</p>
                            </div>
                            <div>
                                <p class="sr-doc-sig-sublabel">Cargo</p>
                                <p class="sr-doc-sig-val">{{ $report->signature_position }}</p>
                            </div>
                            <div>
                                <p class="sr-doc-sig-sublabel">Firmado</p>
                                <p class="sr-doc-sig-val">{{ $report->signed_at?->format('d/m/Y · H:i') }}</p>
                            </div>
                        </div>
                        <div class="sr-doc-sig-img-wrap">
                            <img src="{{ $report->signature_data }}" alt="Firma">
                        </div>
                    </div>
                @endif

            </div>{{-- /sr-doc-body --}}
        </div>{{-- /sr-doc-card --}}

        {{-- ══ RIGHT — Panel ══ --}}
        <div>

            {{-- Card 1: Report info --}}
            <div class="sr-panel-card">
                <h3 class="sr-panel-title">Información del Reporte</h3>
                <div class="sr-info-dl">
                    <div class="sr-info-row">
                        <span class="sr-info-key">Folio</span>
                        <span class="sr-info-val">{{ $report->report_number }}</span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Estado</span>
                        <span class="sr-info-val"><span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span></span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Tipo de Servicio</span>
                        <span class="sr-info-val">{{ $report->service_type_label }}</span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Fecha de Servicio</span>
                        <span class="sr-info-val">{{ $report->service_date?->format('d/m/Y') }}</span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Encargado</span>
                        <span class="sr-info-val">{{ $report->assignedUser?->first_name }} {{ $report->assignedUser?->last_name }}</span>
                    </div>
                    @if($report->week_number)
                        <div class="sr-info-row">
                            <span class="sr-info-key">Semana</span>
                            <span class="sr-info-val">{{ $report->week_number }}</span>
                        </div>
                    @endif
                    @if($report->location)
                        <div class="sr-info-row">
                            <span class="sr-info-key">Ubicación</span>
                            <span class="sr-info-val">{{ $report->location }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card 2: Quick actions --}}
            <div class="sr-panel-card">
                <h3 class="sr-panel-title">Acciones Rápidas</h3>
                <a href="{{ route('customer.service-reports.download-pdf', $report) }}" class="sr-action-btn sr-action-btn--pdf">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Descargar PDF
                </a>
            </div>

        </div>{{-- /right panel --}}

    </div>{{-- /sr-show-grid --}}

    {{-- ── Mobile sticky action footer ── --}}
    <div class="sr-show-mobile-actions">
        <a href="{{ route('customer.service-reports.download-pdf', $report) }}" class="sr-btn sr-btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
            Descargar PDF
        </a>
    </div>

</div>{{-- /sr-show-wrap --}}

{{-- Lightbox --}}
<div class="sr-lightbox" id="srLightbox" onclick="closeLightbox()">
    <button class="sr-lightbox-close" onclick="closeLightbox()">×</button>
    <img src="" id="srLightboxImg" alt="Evidencia" onclick="event.stopPropagation()">
</div>

{{-- Modal PDF --}}
<div id="sr-pdf-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.65); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:8px; width:90vw; max-width:960px; height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.4);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 18px; background:#1a1a1a; border-bottom:2px solid #ff6213; flex-shrink:0;">
            <span style="color:#fff; font-size:14px; font-weight:600;">Vista previa — {{ $report->report_number }}</span>
            <div style="display:flex; gap:10px; align-items:center;">
                <a href="{{ route('customer.service-reports.download-pdf', $report) }}"
                   style="display:inline-flex; align-items:center; gap:6px; background:#ff6213; color:#fff; border:none; padding:7px 14px; border-radius:5px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17V3"/><path d="m6 11 6 6 6-6"/><path d="M19 21H5"/></svg>
                    Descargar PDF
                </a>
                <button onclick="closePdfModal()" style="background:transparent; border:none; cursor:pointer; color:#9CA3AF; font-size:20px; line-height:1; padding:4px 8px;" title="Cerrar">&times;</button>
            </div>
        </div>
        <iframe id="sr-pdf-frame" src="" style="flex:1; border:none; background:#525659;" title="Vista previa PDF"></iframe>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openLightbox(src) {
    document.getElementById('srLightboxImg').src = src;
    document.getElementById('srLightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('srLightbox').classList.remove('open');
}

function openPdfModal() {
    const modal = document.getElementById('sr-pdf-modal');
    const frame = document.getElementById('sr-pdf-frame');
    modal.style.display = 'flex';
    if (!frame.src || frame.src === window.location.href) {
        frame.src = '{{ route('customer.service-reports.pdf-preview', $report) }}';
    }
    document.body.style.overflow = 'hidden';
}
function closePdfModal() {
    document.getElementById('sr-pdf-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('sr-pdf-modal').addEventListener('click', function (e) {
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
