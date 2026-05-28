<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $report->report_number }}</title>
<style>

@page {
    margin: 130px 0px 70px 0px;
}

* { box-sizing: border-box; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1a1a2e;
    background: #fff;
    margin: 0;
    padding: 0;
}

/* ════════════════════════════════════════════════════
   HEADER FIJO
   ════════════════════════════════════════════════════ */
#pdf-header {
    position: fixed;
    top: -130px;
    left: 0;
    right: 0;
    background: #1a1a1a;
}
.header-body {
    padding: 20px 44px 14px 44px;
    display: table;
    width: 100%;
}
.header-left  { display: table-cell; vertical-align: top; }
.header-right { display: table-cell; vertical-align: top; text-align: right; width: 46%; }

.company-meta p { font-size: 9.5px; color: #9CA3AF; line-height: 1.7; margin: 0; margin-top: 5px; }

.rpt-label  {
    font-size: 9px; font-weight: bold; color: #ff6213;
    letter-spacing: 3px; text-transform: uppercase; margin-bottom: 4px;
}
.rpt-number { font-size: 22px; font-weight: bold; color: #ffffff; line-height: 1; }
.rpt-dates  { margin-top: 6px; }
.rpt-dates p { font-size: 9.5px; color: #9CA3AF; line-height: 1.8; display: inline; margin: 0; }
.rpt-dates span { color: #D1D5DC; font-weight: bold; }

.header-accent { height: 3px; background: #ff6213; margin: 0; }


/* ════════════════════════════════════════════════════
   FOOTER FIJO
   ════════════════════════════════════════════════════ */
#pdf-footer {
    position: fixed;
    bottom: -70px;
    left: 0;
    right: 0;
    height: 50px;
    background: #1a1a1a;
    border-top: 2px solid #ff6213;
}
.footer-inner  { display: table; width: 100%; height: 50px; padding: 0 44px; }
.footer-brand  { display: table-cell; width: 30%; vertical-align: middle; }
.footer-center {
    display: table-cell; width: 40%; vertical-align: middle;
    text-align: center; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-right  {
    display: table-cell; width: 30%; vertical-align: middle;
    text-align: right; font-size: 8px; color: #6B7280; line-height: 1.7;
}
.footer-orange { color: #ff6213; }


/* ════════════════════════════════════════════════════
   CONTENIDO
   ════════════════════════════════════════════════════ */
#content {
    padding: 0 44px;
}


/* ════════════════════════════════════════════════════
   RECEPTOR / CLIENTE
   ════════════════════════════════════════════════════ */
.receptor {
    border: 1px solid #d0d0d0;
    border-radius: 5px;
    margin-top: 22px;
    margin-bottom: 14px;
    overflow: hidden;
}
.receptor-header {
    background: #1a1a1a;
    padding: 10px 18px;
    display: table;
    width: 100%;
}
.receptor-header-left  { display: table-cell; vertical-align: middle; }
.receptor-header-right { display: table-cell; vertical-align: middle; text-align: right; }
.receptor-title {
    font-size: 8px; font-weight: bold; color: #ff6213;
    text-transform: uppercase; letter-spacing: 2px; margin: 0 0 2px;
}
.receptor-name-large {
    font-size: 16px; font-weight: bold; color: #ffffff; line-height: 1.1;
}
.receptor-company-tag {
    font-size: 10px; color: #9CA3AF; margin-top: 2px;
}
.receptor-badge {
    display: inline-block; background: #ff6213; color: #fff;
    font-size: 8px; font-weight: bold; letter-spacing: 1px;
    text-transform: uppercase; padding: 3px 10px; border-radius: 3px;
}
.receptor-body {
    background: #fafafa;
    padding: 14px 18px;
    display: table;
    width: 100%;
}
.receptor-col  { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
.receptor-col:last-child { padding-right: 0; border-left: 1px solid #e5e5e5; padding-left: 20px; }
.r-label { font-size: 8px; color: #999; text-transform: uppercase; letter-spacing: .8px; margin-bottom: 2px; }
.r-value { font-size: 11px; color: #1a1a2e; font-weight: bold; margin-bottom: 10px; }


/* ════════════════════════════════════════════════════
   TIRA DE CONDICIONES
   ════════════════════════════════════════════════════ */
.conditions {
    display: table;
    width: 100%;
    margin-bottom: 16px;
}
.cond-item { display: table-cell; vertical-align: top; padding-right: 12px; }
.cond-item:last-child { padding-right: 0; }
.cond-label { font-size: 9px; color: #999; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
.cond-value { font-size: 11px; color: #333; font-weight: 600; }


/* ════════════════════════════════════════════════════
   SECTION TITLE
   ════════════════════════════════════════════════════ */
.section-title {
    font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em;
    color: #6B7280; border-bottom: 1px solid #E5E7EB;
    padding-bottom: 4px; margin-bottom: 10px; margin-top: 18px;
}


/* ════════════════════════════════════════════════════
   TABLA DE MEDICIONES
   ════════════════════════════════════════════════════ */
.meas-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 16px;
}
thead { display: table-header-group; }
.meas-table thead tr { background: #141516; }
.meas-table thead th {
    padding: 9px 8px;
    text-align: left;
    font-size: 9px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: bold;
}
.meas-table thead th.th-right { text-align: right; }
.meas-table tbody tr { page-break-inside: avoid; }
.meas-table tbody tr:nth-child(even) { background: #f5f5f5; }
.meas-table tbody td {
    padding: 8px;
    font-size: 10px;
    color: #444;
    border-bottom: 1px solid #eee;
    vertical-align: top;
    word-wrap: break-word;
}
.dot-ok  { color: #16A34A; font-weight: bold; }
.dot-bad { color: #DC2626; font-weight: bold; }
.dot-nd  { color: #9CA3AF; }


/* ════════════════════════════════════════════════════
   TEXTO LIBRE
   ════════════════════════════════════════════════════ */
.text-block {
    font-size: 10px; color: #374151; line-height: 1.6; white-space: pre-wrap;
    background: #F9FAFB; border: 1px solid #eee; border-radius: 4px; padding: 10px 12px;
    margin-bottom: 4px;
}


/* ════════════════════════════════════════════════════
   SISTEMAS REVISADOS
   ════════════════════════════════════════════════════ */
.sys-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
.sys-table td { padding: 4px 8px; font-size: 10px; color: #374151; width: 33%; }


/* ════════════════════════════════════════════════════
   CAMPOS PERSONALIZADOS
   ════════════════════════════════════════════════════ */
.cf-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
.cf-table tr { border-bottom: 1px solid #F3F4F6; }
.cf-table tr:nth-child(even) { background: #f5f5f5; }
.cf-table td { padding: 6px 8px; font-size: 10px; }
.cf-table td:first-child { color: #6B7280; width: 40%; }
.cf-table td:last-child  { color: #1a1a2e; font-weight: 600; }


/* ════════════════════════════════════════════════════
   INFO TABLE (muestreo / analista)
   ════════════════════════════════════════════════════ */
.info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.info-table td { padding: 5px 8px; font-size: 10px; vertical-align: top; }
.info-table td:first-child { color: #6B7280; width: 160px; white-space: nowrap; }
.info-table td:last-child  { color: #1a1a2e; font-weight: 600; }
.info-table tr:nth-child(even) td { background: #f9f9f9; }


/* ════════════════════════════════════════════════════
   BADGE DE ESTADO
   ════════════════════════════════════════════════════ */
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
.badge-draft       { background: #F3F4F6; color: #6B7280; }
.badge-in_progress { background: #EFF6FF; color: #3B82F6; }
.badge-completed   { background: #F0FDF4; color: #16A34A; }
.badge-signed      { background: #F5F3FF; color: #7C3AED; }


/* ════════════════════════════════════════════════════
   FIRMA
   ════════════════════════════════════════════════════ */
.sig-section {
    margin-top: 28px;
    border: 1px solid #d0d0d0;
    border-radius: 5px;
    overflow: hidden;
    page-break-inside: avoid;
}
.sig-section-header {
    background: #1a1a1a;
    padding: 10px 18px;
    display: table;
    width: 100%;
}
.sig-section-title {
    font-size: 8px; font-weight: bold; color: #ff6213;
    text-transform: uppercase; letter-spacing: 2px; margin: 0 0 1px;
}
.sig-section-subtitle {
    font-size: 9px; color: #9CA3AF;
}
.sig-cols { display: table; width: 100%; }
.sig-col  {
    display: table-cell; width: 50%; vertical-align: top;
    padding: 16px 20px;
    background: #fafafa;
}
.sig-col:last-child {
    border-left: 1px solid #e0e0e0;
    background: #fff;
}
.sig-col-label {
    font-size: 8px; font-weight: bold; color: #999;
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;
    padding-bottom: 6px; border-bottom: 1px solid #eee;
}
.sig-box {
    height: 70px; border: 1px dashed #D1D5DB; border-radius: 4px;
    background: #fff; margin-bottom: 12px;
    display: table; width: 100%;
}
.sig-box-inner {
    display: table-cell; vertical-align: middle; text-align: center;
    font-size: 9px; color: #ccc;
}
.sig-img { max-height: 64px; max-width: 220px; display: block; margin: 0 auto; }
.sig-name     { font-size: 12px; font-weight: bold; color: #1a1a2e; margin-bottom: 2px; }
.sig-position { font-size: 10px; color: #374151; }
.sig-phone    { font-size: 9px; color: #6B7280; margin-top: 1px; }
.sig-date     { font-size: 8px; color: #9CA3AF; margin-top: 6px; }
.sig-line     { border-bottom: 1px solid #555; height: 48px; width: 100%; display: block; margin-bottom: 8px; }

</style>
</head>
<body>

{{-- ── Header fijo ──────────────────────────────────────────── --}}
<div id="pdf-header">
    <div class="header-body">
        <div class="header-left">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:28px;width:auto;display:block;">
            <div class="company-meta">
                <p>administracion@equitermindustries.com.mx</p>
                <p>México, Aguascalientes</p>
            </div>
        </div>
        <div class="header-right">
            <div class="rpt-label">Reporte de Servicio</div>
            <div class="rpt-number">{{ $report->report_number }}</div>
            <div class="rpt-dates">
                <p>Fecha: <span>{{ $report->service_date->format('d/m/Y') }}</span></p>
                @if($report->week_number)
                <p>&nbsp;&nbsp;Semana: <span>{{ $report->week_number }}</span></p>
                @endif
                <p>&nbsp;&nbsp;Estado: <span>{{ $report->status_label }}</span></p>
            </div>
        </div>
    </div>
    <div class="header-accent"></div>
</div>

{{-- ── Footer fijo ──────────────────────────────────────────── --}}
<div id="pdf-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <img src="{{ public_path('images/logo/equiterm-logo-blanco-color-3x.png') }}"
                 alt="Equiterm Industries" style="height:22px;width:auto;">
        </div>
        <div class="footer-center">
            Reporte generado el {{ now()->format('d/m/Y H:i') }}<br>
            Documento oficial de servicio — Equiterm Industries
        </div>
        <div class="footer-right">
            administracion@equitermindustries.com.mx<br>
            <span class="footer-orange">equitermindustries.com.mx</span>
        </div>
    </div>
</div>

{{-- ── Contenido principal ──────────────────────────────────── --}}
<div id="content">

    {{-- Cliente --}}
    <div class="receptor">
        <div class="receptor-header">
            <div class="receptor-header-left">
                <div class="receptor-title">Reporte para</div>
                <div class="receptor-name-large">{{ $report->customer_name }}</div>
                @if($report->customer_company)
                <div class="receptor-company-tag">{{ $report->customer_company }}</div>
                @endif
            </div>
            <div class="receptor-header-right">
                <div class="receptor-badge">{{ $report->service_type_label }}</div>
            </div>
        </div>
        <div class="receptor-body">
            <div class="receptor-col">
                @if($report->customer_company)
                <div class="r-label">Empresa</div>
                <div class="r-value">{{ $report->customer_company }}</div>
                @endif
                @if($report->customer_rfc)
                <div class="r-label">RFC</div>
                <div class="r-value">{{ $report->customer_rfc }}</div>
                @endif
            </div>
            <div class="receptor-col">
                @if($report->customer_phone)
                <div class="r-label">Teléfono</div>
                <div class="r-value">{{ $report->customer_phone }}</div>
                @endif
                <div class="r-label">Fecha de Servicio</div>
                <div class="r-value">{{ $report->service_date->format('d/m/Y') }}</div>
                @if($report->location)
                <div class="r-label">Ubicación</div>
                <div class="r-value">{{ $report->location }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Condiciones --}}
    <div class="conditions">
        <div class="cond-item">
            <div class="cond-label">Tipo de Servicio</div>
            <div class="cond-value">{{ $report->service_type_label }}</div>
        </div>
        <div class="cond-item">
            <div class="cond-label">Fecha</div>
            <div class="cond-value">{{ $report->service_date->format('d/m/Y') }}</div>
        </div>
        @if($report->location)
        <div class="cond-item">
            <div class="cond-label">Ubicación</div>
            <div class="cond-value">{{ $report->location }}</div>
        </div>
        @endif
        <div class="cond-item">
            <div class="cond-label">Encargado</div>
            <div class="cond-value">{{ $report->assigned_user_name }}</div>
        </div>
        @if($report->createdBy)
        <div class="cond-item">
            <div class="cond-label">Elaboró</div>
            <div class="cond-value">{{ $report->createdBy->first_name }} {{ $report->createdBy->last_name }}</div>
        </div>
        @endif
    </div>

    {{-- Mediciones --}}
    @if($report->usesMeasurementsForm() && $report->measurements->isNotEmpty())
        <div class="section-title">Resultados de Análisis ({{ $report->measurements->count() }} parámetros)</div>
        <table class="meas-table">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:28%;">Parámetro</th>
                    <th style="width:12%;">Unidad</th>
                    <th style="width:14%;">Resultado</th>
                    <th class="th-right" style="width:12%;">Límite Mín.</th>
                    <th class="th-right" style="width:12%;">Límite Máx.</th>
                    <th class="th-right" style="width:18%;">Rango</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->measurements as $i => $m)
                    <tr>
                        <td style="text-align:center;color:#aaa;">{{ $i + 1 }}</td>
                        <td style="font-weight:bold;color:#1a1a2e;">{{ $m->parameter }}</td>
                        <td style="color:#888;">{{ $m->unit ?? '—' }}</td>
                        <td style="font-weight:bold;">{{ $m->result ?? '—' }}</td>
                        <td style="text-align:right;">{{ $m->limit_min !== null ? $m->limit_min : '—' }}</td>
                        <td style="text-align:right;">{{ $m->limit_max !== null ? $m->limit_max : '—' }}</td>
                        <td style="text-align:right;">
                            @if($m->in_range === true)  <span class="dot-ok">✓ Dentro</span>
                            @elseif($m->in_range === false) <span class="dot-bad">✗ Fuera</span>
                            @else <span class="dot-nd">N/D</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Actividades --}}
    @if($report->usesActivityForm() && $report->activity)
        <div class="section-title">Actividades Realizadas</div>
        @if($report->activity->content)
            <div class="text-block">{{ $report->activity->content }}</div>
        @endif
        @if($report->activity->systems_checked && count($report->activity->systems_checked))
            <div class="section-title" style="margin-top:14px;">Sistemas Revisados</div>
            <table class="sys-table">
                @foreach(array_chunk($report->activity->systems_checked, 3) as $row)
                    <tr>
                        @foreach($row as $sys)
                            <td>✓ {{ $sys }}</td>
                        @endforeach
                        @for($j = count($row); $j < 3; $j++) <td></td> @endfor
                    </tr>
                @endforeach
            </table>
        @endif
    @endif

    {{-- Campos personalizados --}}
    @if($report->usesCustomForm() && $report->customFields->isNotEmpty())
        <div class="section-title">Campos del Reporte</div>
        <table class="cf-table">
            @foreach($report->customFields as $cf)
                <tr>
                    <td>{{ $cf->field_name }}</td>
                    <td>{{ $cf->field_value ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Muestreo --}}
    @if($report->include_sampling)
        <div class="section-title">Datos de Muestreo</div>
        <table class="info-table">
            <tr>
                <td>Fecha de Toma de Muestra</td>
                <td>{{ str_pad($report->sampling_date_day,2,'0',STR_PAD_LEFT) }}/{{ str_pad($report->sampling_date_month,2,'0',STR_PAD_LEFT) }}/{{ $report->sampling_date_year }}</td>
            </tr>
            @if($report->analyst_name)
                <tr>
                    <td>Analista</td>
                    <td>{{ $report->analyst_name }}{{ $report->analyst_position ? ' — '.$report->analyst_position : '' }}</td>
                </tr>
            @endif
        </table>
    @endif

    {{-- Observaciones --}}
    @if($report->observations)
        <div class="section-title">Observaciones</div>
        <div class="text-block">{{ $report->observations }}</div>
    @endif

    @if($report->recommendations)
        <div class="section-title">Recomendaciones</div>
        <div class="text-block">{{ $report->recommendations }}</div>
    @endif

    {{-- Evidencia fotográfica --}}
    @if($report->images->isNotEmpty())
        <div class="section-title">Evidencia Fotográfica ({{ $report->images->count() }})</div>
        <table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
            @foreach($report->images->chunk(3) as $row)
            <tr>
                @foreach($row as $img)
                <td style="width:33%; padding:4px; vertical-align:top;">
                    <img src="{{ storage_path('app/public/' . $img->path) }}"
                         style="width:100%; max-height:160px; object-fit:cover; border-radius:4px; border:1px solid #e5e5e5; display:block;"
                         alt="Evidencia">
                </td>
                @endforeach
                @for($j = $row->count(); $j < 3; $j++)
                <td style="width:33%; padding:4px;"></td>
                @endfor
            </tr>
            @endforeach
        </table>
    @endif

    {{-- Firma de Recibido --}}
    <div class="sig-section">
        <div class="sig-section-header">
            <div class="sig-section-title">Firma de Conformidad</div>
            <div class="sig-section-subtitle">Este documento certifica que el servicio fue realizado satisfactoriamente</div>
        </div>
        <div style="padding: 20px 44px; text-align: center;">
            <div class="sig-col-label" style="margin-bottom:14px;">Firma de Recibido — Cliente</div>
            <div style="margin: 0 auto; width: 60%;">
                <div class="sig-box" style="{{ $report->signature_data ? 'border:1px solid #D1D5DB;' : '' }}">
                    <div class="sig-box-inner">
                        @if($report->signature_data)
                            <img src="{{ $report->signature_data }}" class="sig-img" alt="Firma">
                        @else
                            Firma aquí
                        @endif
                    </div>
                </div>
                <div style="border-bottom: 1px solid #555; margin: 12px auto 6px; width: 100%;"></div>
                <div class="sig-name">{{ $report->customer_name }}</div>
                @if($report->customer_company)
                <div class="sig-position">{{ $report->customer_company }}</div>
                @endif
                <div style="font-size:8px; color:#999; margin-top:6px; text-transform:uppercase; letter-spacing:1px;">Nombre y Firma</div>
            </div>
        </div>
    </div>

</div>{{-- /#content --}}
</body>
</html>
