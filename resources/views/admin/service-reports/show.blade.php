@extends('admin.layouts.master')
@section('title', 'Reporte ' . $report->report_number)

@push('styles')
<style>
    /* ── Variables ─────────────────────────────────────────────────────────────── */
    :root {
        --background-primary:           #141516;
        --background-primary-white:     #F8F9FA;
        --background--white:            #ffffff;
        --header-footer-color:          #1A2535;
        --border-header-footer-color:   #2E3A46;
        --text-white-color:             #ffffff;
        --text-subwhite-color:          #D1D5DC;
        --text-description-color:       #6B7280;
        --secondary-color:              #ff6213;
        --button-primary-color:         #ff6213;
        --button-primary-color-hover:   #de4a00;
        --font-family:                  'Inter', sans-serif;
        --shadow-sm:                    0 1px 2px rgba(0,0,0,.06);
        --shadow-md:                    0 10px 20px rgba(0,0,0,.1);
    }

    /* ── Layout ─────────────────────────────────────────────────────────────────── */
    .sr-show-wrap {
        font-family: var(--font-family);
        background: var(--background-primary-white);
        flex: 1; overflow-y: auto;
        padding: 32px 32px 48px;
    }
    .sr-show-grid {
        display: grid;
        grid-template-columns: 8fr 4fr;
        gap: 20px;
        align-items: start;
    }

    /* ── Breadcrumb & Header ────────────────────────────────────────────────────── */
    .sr-breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6B7280; margin-bottom: 16px; flex-wrap: wrap; }
    .sr-breadcrumb a { color: #6B7280; text-decoration: none; }
    .sr-breadcrumb a:hover { color: #374151; }
    .sr-breadcrumb span { color: #374151; font-weight: 500; }

    .sr-show-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .sr-show-topbar-left { display: flex; flex-direction: column; gap: 10px; }
    .sr-show-title-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

    .sr-btn-back { width: 36px; height: 36px; border-radius: 6px; border: 1px solid #D1D5DB; background: #fff;
                   display: inline-flex; align-items: center; justify-content: center; color: #6B7280;
                   cursor: pointer; text-decoration: none; flex-shrink: 0; transition: background .15s; }
    .sr-btn-back:hover { background: #F9FAFB; }

    .sr-show-title { font-size: 24px; font-weight: 700; color: #111827; margin: 0; }

    .sr-show-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; flex-shrink: 0; }

    /* ── Badges ─────────────────────────────────────────────────────────────────── */
    .sr-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px;
                font-size: 12px; font-weight: 500; border: 1px solid; white-space: nowrap; }
    .sr-badge--draft       { background:#F3F4F6; color:#6B7280;  border-color:#D1D5DB; }
    .sr-badge--in-progress { background:#EFF6FF; color:#3B82F6;  border-color:#BFDBFE; }
    .sr-badge--completed   { background:#F0FDF4; color:#16A34A;  border-color:#BBF7D0; }
    .sr-badge--signed      { background:#F5F3FF; color:#7C3AED;  border-color:#DDD6FE; }

    /* ── Buttons ─────────────────────────────────────────────────────────────────── */
    .sr-btn { height: 40px; padding: 0 16px; border-radius: 6px; font-size: 13px; font-weight: 500;
               font-family: var(--font-family); cursor: pointer; display: inline-flex; align-items: center;
               gap: 6px; text-decoration: none; border: none; transition: background .15s, color .15s; white-space: nowrap; }
    .sr-btn-outline  { background: #fff; color: #111827; border: 1px solid #D1D5DB; }
    .sr-btn-outline:hover { background: #F9FAFB; color: #111827; }
    .sr-btn-sign     { background: #7C3AED; color: #fff; }
    .sr-btn-sign:hover { background: #6D28D9; color: #fff; }
    .sr-btn-danger   { background: #fff; color: #DC2626; border: 1px solid #FECACA; }
    .sr-btn-danger:hover { background: #FEF2F2; }

    /* ── Document card (left column) ────────────────────────────────────────────── */
    .sr-doc-card { background: #fff; border-radius: 12px; box-shadow: var(--shadow-md); overflow: hidden; }

    .sr-doc-header { background: var(--background-primary); padding: 24px 32px;
                     display: flex; align-items: center; justify-content: space-between; }
    .sr-doc-header-brand { }
    .sr-doc-brand-name   { font-size: 20px; font-weight: 700; color: #fff; letter-spacing: -.02em; margin: 0 0 4px; }
    .sr-doc-brand-tag    { font-size: 10px; font-weight: 500; color: var(--secondary-color);
                           text-transform: uppercase; letter-spacing: .15em; margin: 0; }
    .sr-doc-header-folio { text-align: right; }
    .sr-doc-rpt-label    { font-size: 10px; font-weight: 600; color: var(--secondary-color);
                           text-transform: uppercase; letter-spacing: .1em; margin: 0 0 4px; }
    .sr-doc-rpt-number   { font-size: 22px; font-weight: 700; color: #fff; font-variant-numeric: tabular-nums; margin: 0; }
    .sr-doc-accent       { height: 2px; background: var(--secondary-color); }

    .sr-doc-body { padding: 32px; }

    /* ── Client + Report data grid ──────────────────────────────────────────────── */
    .sr-doc-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 24px; }
    .sr-doc-section-label { font-size: 10px; font-weight: 600; text-transform: uppercase;
                            letter-spacing: .15em; color: #6B7280; margin: 0 0 8px; }
    .sr-doc-client-name { font-size: 14px; font-weight: 700; color: #111827; margin: 0 0 4px; }
    .sr-doc-client-sub  { font-size: 12px; color: #374151; margin: 0 0 2px; }

    .sr-doc-kv-list { display: flex; flex-direction: column; gap: 6px; }
    .sr-doc-kv-row  { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; }
    .sr-doc-kv-key  { color: #6B7280; }
    .sr-doc-kv-val  { color: #111827; font-weight: 500; text-align: right; }

    /* ── Measurements table ─────────────────────────────────────────────────────── */
    .sr-doc-table-label { font-size: 10px; font-weight: 600; text-transform: uppercase;
                          letter-spacing: .15em; color: #6B7280; margin: 0 0 12px; }
    .sr-doc-meas-wrap { margin-bottom: 24px; overflow-x: auto; }
    .sr-doc-meas-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .sr-doc-meas-table thead tr { background: #1A2535; height: 36px; }
    .sr-doc-meas-table thead th { padding: 0 12px; text-align: left; font-size: 10px; font-weight: 600;
                                   text-transform: uppercase; letter-spacing: .06em; color: #D1D5DC; white-space: nowrap; }
    .sr-doc-meas-table tbody tr { height: 40px; border-bottom: 1px solid #F3F4F6; }
    .sr-doc-meas-table tbody tr:nth-child(even) { background: #F8F9FA; }
    .sr-doc-meas-table tbody td { padding: 0 12px; vertical-align: middle; }
    .sr-doc-meas-table tbody td.td-param { font-weight: 500; color: #111827; }
    .sr-doc-meas-table tbody td.td-unit  { color: #6B7280; }
    .sr-doc-meas-table tbody td.td-num   { font-variant-numeric: tabular-nums; color: #6B7280; }
    .sr-doc-meas-table tbody td.td-result { font-variant-numeric: tabular-nums; font-weight: 500; color: #111827; }

    /* ── Observations ────────────────────────────────────────────────────────────── */
    .sr-doc-obs-block { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 6px;
                        padding: 16px; margin-bottom: 16px; }
    .sr-doc-obs-block p { font-size: 13px; color: #374151; line-height: 1.6; margin: 6px 0 0; white-space: pre-wrap; }

    /* ── Activity ────────────────────────────────────────────────────────────────── */
    .sr-doc-activity { margin-bottom: 24px; }
    .sr-doc-activity p { font-size: 13px; color: #374151; white-space: pre-wrap; line-height: 1.6; margin: 0 0 12px; }
    .sr-check-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .sr-check-chip { font-size: 12px; padding: 4px 10px; border-radius: 4px; background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

    /* ── Photos ──────────────────────────────────────────────────────────────────── */
    .sr-doc-photos { margin-bottom: 24px; }
    .sr-doc-photos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .sr-doc-photos-col-label { font-size: 11px; color: #6B7280; margin: 0 0 6px; }
    .sr-doc-photos-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .sr-photo-item { border-radius: 6px; overflow: hidden; border: 1px solid #E5E7EB;
                     aspect-ratio: 1/1; cursor: pointer; transition: box-shadow .15s; }
    .sr-photo-item:hover { box-shadow: 0 0 0 2px var(--secondary-color); }
    .sr-photo-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sr-photo-placeholder { border-radius: 6px; aspect-ratio: 1/1; background: #F3F4F6;
                             display: flex; align-items: center; justify-content: center; border: 1px solid #E5E7EB; }

    /* ── Signature ───────────────────────────────────────────────────────────────── */
    .sr-doc-sig { border-top: 2px solid var(--secondary-color); padding-top: 24px; }
    .sr-doc-sig-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .sr-doc-sig-sublabel { font-size: 11px; color: #6B7280; margin: 0 0 4px; }
    .sr-doc-sig-val { font-size: 13px; font-weight: 600; color: #111827; margin: 0; }
    .sr-doc-sig-img-wrap { height: 56px; background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px;
                           display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .sr-doc-sig-img-wrap img { max-height: 56px; object-fit: contain; width: 100%; }

    /* ── Right panel cards ───────────────────────────────────────────────────────── */
    .sr-panel-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm);
                     padding: 20px; margin-bottom: 16px; }
    .sr-panel-title { font-size: 12px; font-weight: 600; text-transform: uppercase;
                      letter-spacing: .08em; color: #6B7280; margin: 0 0 16px; }

    .sr-info-dl { display: flex; flex-direction: column; gap: 10px; }
    .sr-info-row { display: flex; justify-content: space-between; gap: 12px;
                   font-size: 13px; padding-bottom: 10px; border-bottom: 1px solid #F9FAFB; }
    .sr-info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .sr-info-key { color: #6B7280; flex-shrink: 0; }
    .sr-info-val { color: #111827; font-weight: 500; text-align: right; }

    /* ── Timeline ────────────────────────────────────────────────────────────────── */
    .sr-timeline { position: relative; padding-left: 20px; display: flex; flex-direction: column; gap: 16px; }
    .sr-timeline::before { content: ''; position: absolute; left: 5px; top: 6px; bottom: 6px;
                            width: 1px; background: #E5E7EB; }
    .sr-timeline-item { position: relative; }
    .sr-timeline-dot { position: absolute; left: -19px; top: 4px; width: 10px; height: 10px;
                       border-radius: 50%; border: 2px solid; }
    .sr-timeline-dot--done    { background: #16A34A; border-color: #16A34A; }
    .sr-timeline-dot--current { background: var(--secondary-color); border-color: var(--secondary-color); }
    .sr-timeline-dot--pending { background: #fff; border-color: #D1D5DB; }
    .sr-timeline-label { font-size: 13px; font-weight: 500; color: #111827; margin: 0 0 2px; }
    .sr-timeline-badge { font-size: 10px; font-weight: 600; text-transform: uppercase;
                         letter-spacing: .05em; color: var(--secondary-color); margin-left: 6px; }
    .sr-timeline-time { font-size: 11px; color: #6B7280; display: flex; align-items: center; gap: 4px; margin: 0; }

    /* ── Quick actions ───────────────────────────────────────────────────────────── */
    .sr-action-btn { width: 100%; height: 40px; border-radius: 6px; font-size: 13px; font-weight: 500;
                     font-family: var(--font-family); cursor: pointer; display: flex; align-items: center;
                     justify-content: center; gap: 8px; text-decoration: none; transition: background .15s;
                     margin-bottom: 8px; }
    .sr-action-btn:last-child { margin-bottom: 0; }
    .sr-action-btn--pdf      { background: #fff; color: var(--secondary-color); border: 1px solid var(--secondary-color); }
    .sr-action-btn--pdf:hover { background: #FFF7ED; }
    .sr-action-btn--secondary { background: #fff; color: #374151; border: 1px solid #D1D5DB; }
    .sr-action-btn--secondary:hover { background: #F9FAFB; color: #374151; }

    /* ── Lightbox ────────────────────────────────────────────────────────────────── */
    .sr-lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85);
                   z-index: 9999; align-items: center; justify-content: center; }
    .sr-lightbox.open { display: flex; }
    .sr-lightbox img { max-width: 90vw; max-height: 90vh; border-radius: 4px; }
    .sr-lightbox-close { position: absolute; top: 20px; right: 24px; color: #fff; font-size: 28px;
                          cursor: pointer; background: none; border: none; line-height: 1; }

    /* ── Flash ───────────────────────────────────────────────────────────────────── */
    .sr-flash-ok  { background:#F0FDF4; border:1px solid #BBF7D0; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#16A34A; }
    .sr-flash-err { background:#FEF2F2; border:1px solid #FECACA; border-radius:6px; padding:12px 16px; margin-bottom:16px; font-size:13px; color:#DC2626; }

    /* ── Responsive ──────────────────────────────────────────────────────────────── */
    @media (max-width: 900px) {
        .sr-show-grid { grid-template-columns: 1fr; }
        .sr-doc-info-grid { grid-template-columns: 1fr; gap: 20px; }
        .sr-doc-sig-grid { grid-template-columns: 1fr 1fr; }
        .sr-show-wrap { padding: 16px; }
    }
    @media (max-width: 600px) {
        .sr-doc-body { padding: 20px; }
        .sr-doc-photos-grid { grid-template-columns: 1fr; }
        .sr-doc-sig-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="sr-show-wrap">

    @if(session('success'))
        <div class="sr-flash-ok">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="sr-flash-err">{{ session('error') }}</div>
    @endif

    {{-- Breadcrumb --}}
    <div class="sr-breadcrumb">
        <span>Panel de Control</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <a href="{{ route('admin.service-reports.index') }}">Reportes de Servicio</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        <span>{{ $report->report_number }}</span>
    </div>

    {{-- Top bar --}}
    <div class="sr-show-topbar">
        <div class="sr-show-topbar-left">
            <div class="sr-show-title-row">
                <a href="{{ route('admin.service-reports.index') }}" class="sr-btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <h1 class="sr-show-title">{{ $report->report_number }}</h1>
                <span class="sr-badge {{ $report->status_color }}">{{ $report->status_label }}</span>
            </div>
        </div>
        <div class="sr-show-actions">
            @if($report->isEditable())
                <a href="{{ route('admin.service-reports.edit', $report) }}" class="sr-btn sr-btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Editar
                </a>
            @endif
            <button type="button" class="sr-btn sr-btn-outline" onclick="openPdfModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
                Vista previa PDF
            </button>
            @if($report->isEditable())
                <a href="{{ route('admin.service-reports.step', [$report, 6]) }}" class="sr-btn sr-btn-sign">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                    Firmar
                </a>
            @endif
            @if($report->isDeletable())
                <form method="POST" action="{{ route('admin.service-reports.destroy', $report) }}" style="display:inline;"
                    onsubmit="return confirm('¿Eliminar este reporte? Esta acción no se puede deshacer.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="sr-btn sr-btn-danger">Eliminar</button>
                </form>
            @endif
        </div>
    </div>

    {{-- Two-column grid --}}
    <div class="sr-show-grid">

        {{-- ══ LEFT — Document card ══ --}}
        <div class="sr-doc-card">

            {{-- Document header --}}
            <div class="sr-doc-header">
                <div class="sr-doc-header-brand">
                    <p class="sr-doc-brand-name">QUITERM INDUSTRIES</p>
                    <p class="sr-doc-brand-tag">Soluciones Térmicas Industriales</p>
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
                                <span class="sr-doc-kv-val">{{ $report->service_date->translatedFormat('d \d\e F \d\e Y') }}</span>
                            </div>
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Tipo</span>
                                <span class="sr-doc-kv-val">{{ $report->service_type_label }}</span>
                            </div>
                            <div class="sr-doc-kv-row">
                                <span class="sr-doc-kv-key">Encargado</span>
                                <span class="sr-doc-kv-val">{{ $report->assigned_user_name }}</span>
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
                                @foreach($report->measurements as $i => $m)
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
                                        <div class="sr-photo-item" onclick="openLightbox('{{ $img->url }}')">
                                            <img src="{{ $img->url }}" alt="Evidencia">
                                        </div>
                                    @endforeach
                                    @for($pi = $report->images->take(2)->count(); $pi < 2; $pi++)
                                        <div class="sr-photo-placeholder">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                        </div>
                                    @endfor
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
                                    @for($pi = $report->images->slice(2, 2)->count(); $pi < 2; $pi++)
                                        <div class="sr-photo-placeholder">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D1D5DB" stroke-width="2"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                        </div>
                                    @endfor
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
                                <p class="sr-doc-sig-val">{{ $report->signed_at->format('d/m/Y · H:i') }}</p>
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
                        <span class="sr-info-val">{{ $report->service_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Encargado</span>
                        <span class="sr-info-val">{{ $report->assigned_user_name }}</span>
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
                    <div class="sr-info-row">
                        <span class="sr-info-key">Creado por</span>
                        <span class="sr-info-val">{{ $report->createdBy?->first_name }} {{ $report->createdBy?->last_name }}</span>
                    </div>
                    <div class="sr-info-row">
                        <span class="sr-info-key">Fecha de creación</span>
                        <span class="sr-info-val">{{ $report->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Timeline --}}
            <div class="sr-panel-card">
                <h3 class="sr-panel-title">Historial</h3>
                <ol class="sr-timeline">
                    {{-- Borrador --}}
                    <li class="sr-timeline-item">
                        <span class="sr-timeline-dot sr-timeline-dot--done"></span>
                        <p class="sr-timeline-label">Borrador creado</p>
                        <p class="sr-timeline-time">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            {{ $report->created_at->format('d M · H:i') }}
                        </p>
                    </li>

                    {{-- En proceso --}}
                    @if(in_array($report->status, ['in_progress', 'completed', 'signed']))
                        <li class="sr-timeline-item">
                            <span class="sr-timeline-dot {{ $report->status === 'in_progress' ? 'sr-timeline-dot--current' : 'sr-timeline-dot--done' }}"></span>
                            <p class="sr-timeline-label">
                                En proceso
                                @if($report->status === 'in_progress') <span class="sr-timeline-badge">actual</span> @endif
                            </p>
                            <p class="sr-timeline-time">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $report->updated_at->format('d M · H:i') }}
                            </p>
                        </li>
                    @else
                        <li class="sr-timeline-item">
                            <span class="sr-timeline-dot sr-timeline-dot--pending"></span>
                            <p class="sr-timeline-label" style="color:#9CA3AF;">En proceso</p>
                        </li>
                    @endif

                    {{-- Firmado --}}
                    @if($report->status === 'signed')
                        <li class="sr-timeline-item">
                            <span class="sr-timeline-dot sr-timeline-dot--current"></span>
                            <p class="sr-timeline-label">
                                Firmado
                                <span class="sr-timeline-badge">actual</span>
                            </p>
                            <p class="sr-timeline-time">
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                {{ $report->signed_at->format('d M · H:i') }}
                            </p>
                        </li>
                    @else
                        <li class="sr-timeline-item">
                            <span class="sr-timeline-dot sr-timeline-dot--pending"></span>
                            <p class="sr-timeline-label" style="color:#9CA3AF;">Firmado</p>
                        </li>
                    @endif
                </ol>
            </div>

            {{-- Card 3: Quick actions --}}
            <div class="sr-panel-card">
                <h3 class="sr-panel-title">Acciones Rápidas</h3>
                <a href="{{ route('admin.service-reports.download-pdf', $report) }}" class="sr-action-btn sr-action-btn--pdf">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Descargar PDF
                </a>
                <button type="button" class="sr-action-btn sr-action-btn--secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Enviar por correo
                </button>
                <button type="button" class="sr-action-btn sr-action-btn--secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                    Duplicar reporte
                </button>
            </div>

        </div>{{-- /right panel --}}

    </div>{{-- /sr-show-grid --}}

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
                <a href="{{ route('admin.service-reports.download-pdf', $report) }}"
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
        frame.src = '{{ route('admin.service-reports.pdf-preview', $report) }}';
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
