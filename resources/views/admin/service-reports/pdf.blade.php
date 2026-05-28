<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $report->report_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #111827;
            background: #fff;
            padding: 0;
        }

        /* ── Page layout ── */
        .page { padding: 28px 32px; }

        /* ── Header ── */
        .pdf-header { display: table; width: 100%; margin-bottom: 6px; }
        .pdf-header-logo { display: table-cell; width: 50%; vertical-align: middle; }
        .pdf-header-info { display: table-cell; width: 50%; text-align: right; vertical-align: middle; }
        .company-name { font-size: 18px; font-weight: bold; color: #111827; letter-spacing: .02em; }
        .company-tagline { font-size: 9px; color: #6B7280; margin-top: 2px; }
        .report-number { font-size: 16px; font-weight: bold; color: #ff6213; }
        .report-date   { font-size: 9px; color: #6B7280; margin-top: 2px; }
        .header-divider { border: none; border-top: 3px solid #ff6213; margin: 10px 0 16px; }

        /* ── Section titles ── */
        .section-title {
            font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em;
            color: #6B7280; border-bottom: 1px solid #E5E7EB; padding-bottom: 4px; margin-bottom: 8px; margin-top: 16px;
        }

        /* ── Info grid ── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-table td { padding: 4px 8px; font-size: 10px; vertical-align: top; }
        .info-table td:first-child { color: #6B7280; width: 140px; white-space: nowrap; }
        .info-table td:last-child  { color: #111827; font-weight: 500; }
        .info-row-2 { display: table; width: 100%; }
        .info-col   { display: table-cell; width: 50%; vertical-align: top; }

        /* ── Badge ── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-draft       { background: #F3F4F6; color: #6B7280; }
        .badge-in_progress { background: #EFF6FF; color: #3B82F6; }
        .badge-completed   { background: #F0FDF4; color: #16A34A; }
        .badge-signed      { background: #F5F3FF; color: #7C3AED; }

        /* ── Measurements table ── */
        .meas-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .meas-table thead tr { background: #1A2535; }
        .meas-table thead th { color: #D1D5DC; padding: 6px 10px; text-align: left; font-size: 9px;
                                text-transform: uppercase; letter-spacing: .06em; font-weight: bold; }
        .meas-table tbody tr { border-bottom: 1px solid #F3F4F6; }
        .meas-table tbody td { padding: 6px 10px; color: #374151; }
        .meas-table tbody tr:nth-child(even) td { background: #F9FAFB; }
        .dot-ok  { color: #16A34A; font-weight: bold; }
        .dot-bad { color: #DC2626; font-weight: bold; }
        .dot-nd  { color: #9CA3AF; }

        /* ── Text blocks ── */
        .text-block { font-size: 10px; color: #374151; line-height: 1.6; white-space: pre-wrap;
                      background: #F9FAFB; border: 1px solid #F3F4F6; border-radius: 4px; padding: 10px 12px; }

        /* ── Systems checked ── */
        .sys-table { width: 100%; border-collapse: collapse; }
        .sys-table td { padding: 3px 8px; font-size: 10px; color: #374151; width: 33%; }

        /* ── Custom fields ── */
        .cf-table { width: 100%; border-collapse: collapse; }
        .cf-table tr { border-bottom: 1px solid #F3F4F6; }
        .cf-table td { padding: 5px 8px; font-size: 10px; }
        .cf-table td:first-child { color: #6B7280; width: 40%; }
        .cf-table td:last-child  { color: #111827; font-weight: 500; }

        /* ── Signature ── */
        .signature-block { display: table; width: 100%; margin-top: 24px; border-top: 2px solid #ff6213; padding-top: 12px; }
        .signature-img-wrap { display: table-cell; width: 40%; vertical-align: top; }
        .signature-img { max-height: 80px; max-width: 180px; }
        .signature-info { display: table-cell; vertical-align: bottom; padding-left: 24px; }
        .signer-name     { font-size: 12px; font-weight: bold; color: #111827; }
        .signer-position { font-size: 10px; color: #374151; }
        .signer-phone    { font-size: 9px; color: #6B7280; }
        .signer-date     { font-size: 9px; color: #9CA3AF; margin-top: 4px; }

        /* ── Footer ── */
        .pdf-footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 6px 32px;
                      border-top: 1px solid #F3F4F6; font-size: 8px; color: #9CA3AF;
                      display: table; width: 100%; background: #fff; }
        .footer-left  { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

    <div class="page">

        {{-- ── Header ── --}}
        <div class="pdf-header">
            <div class="pdf-header-logo">
                <div class="company-name">QUITERM INDUSTRIES</div>
                <div class="company-tagline">Soluciones en Tratamiento de Agua</div>
            </div>
            <div class="pdf-header-info">
                <div class="report-number">{{ $report->report_number }}</div>
                <div class="report-date">{{ now()->format('d/m/Y') }}</div>
            </div>
        </div>
        <hr class="header-divider">

        {{-- ── Datos generales ── --}}
        <div class="section-title">Datos del Reporte</div>
        <div class="info-row-2">
            <div class="info-col">
                <table class="info-table">
                    <tr><td>Folio</td><td><strong>{{ $report->report_number }}</strong></td></tr>
                    <tr><td>Tipo de Servicio</td><td>{{ $report->service_type_label }}</td></tr>
                    <tr><td>Fecha de Servicio</td><td>{{ $report->service_date->format('d/m/Y') }}</td></tr>
                    @if($report->week_number)
                        <tr><td>Semana</td><td>{{ $report->week_number }}</td></tr>
                    @endif
                    @if($report->location)
                        <tr><td>Ubicación</td><td>{{ $report->location }}</td></tr>
                    @endif
                </table>
            </div>
            <div class="info-col">
                <table class="info-table">
                    <tr><td>Estado</td><td>
                        <span class="badge badge-{{ $report->status }}">{{ $report->status_label }}</span>
                    </td></tr>
                    <tr><td>Encargado</td><td>{{ $report->assigned_user_name }}</td></tr>
                    @if($report->createdBy)
                        <tr><td>Elaboró</td><td>{{ $report->createdBy->first_name }} {{ $report->createdBy->last_name }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- ── Cliente ── --}}
        <div class="section-title">Cliente</div>
        <div class="info-row-2">
            <div class="info-col">
                <table class="info-table">
                    <tr><td>Nombre</td><td>{{ $report->customer_name }}</td></tr>
                    @if($report->customer_company)
                        <tr><td>Empresa</td><td>{{ $report->customer_company }}</td></tr>
                    @endif
                </table>
            </div>
            <div class="info-col">
                <table class="info-table">
                    @if($report->customer_rfc)
                        <tr><td>RFC</td><td>{{ $report->customer_rfc }}</td></tr>
                    @endif
                    @if($report->customer_phone)
                        <tr><td>Teléfono</td><td>{{ $report->customer_phone }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- ── Mediciones ── --}}
        @if($report->usesMeasurementsForm() && $report->measurements->isNotEmpty())
            <div class="section-title">Resultados de Análisis ({{ $report->measurements->count() }} parámetros)</div>
            <table class="meas-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Parámetro</th>
                        <th>Unidad</th>
                        <th>Resultado</th>
                        <th>Límite Mín.</th>
                        <th>Límite Máx.</th>
                        <th>Rango</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->measurements as $i => $m)
                        <tr>
                            <td style="color:#9CA3AF;">{{ $i + 1 }}</td>
                            <td><strong>{{ $m->parameter }}</strong></td>
                            <td>{{ $m->unit ?? '—' }}</td>
                            <td><strong>{{ $m->result ?? '—' }}</strong></td>
                            <td>{{ $m->limit_min !== null ? $m->limit_min : '—' }}</td>
                            <td>{{ $m->limit_max !== null ? $m->limit_max : '—' }}</td>
                            <td>
                                @if($m->in_range === true) <span class="dot-ok">✓ Dentro</span>
                                @elseif($m->in_range === false) <span class="dot-bad">✗ Fuera</span>
                                @else <span class="dot-nd">N/D</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- ── Actividades ── --}}
        @if($report->usesActivityForm() && $report->activity)
            <div class="section-title">Actividades Realizadas</div>
            <div class="text-block">{{ $report->activity->content }}</div>
            @if($report->activity->systems_checked && count($report->activity->systems_checked))
                <div class="section-title" style="margin-top:12px;">Sistemas Revisados</div>
                <table class="sys-table">
                    @foreach(array_chunk($report->activity->systems_checked, 3) as $row)
                        <tr>
                            @foreach($row as $sys)
                                <td>✓ {{ $sys }}</td>
                            @endforeach
                            @for($i = count($row); $i < 3; $i++) <td></td> @endfor
                        </tr>
                    @endforeach
                </table>
            @endif
        @endif

        {{-- ── Custom fields ── --}}
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

        {{-- ── Muestreo ── --}}
        @if($report->include_sampling)
            <div class="section-title">Datos de Muestreo</div>
            <table class="info-table">
                <tr>
                    <td>Fecha de Toma de Muestra</td>
                    <td>{{ str_pad($report->sampling_date_day,2,'0',STR_PAD_LEFT) }}/{{ str_pad($report->sampling_date_month,2,'0',STR_PAD_LEFT) }}/{{ $report->sampling_date_year }}</td>
                </tr>
                @if($report->analyst_name)
                    <tr><td>Analista</td><td>{{ $report->analyst_name }}{{ $report->analyst_position ? ' — '.$report->analyst_position : '' }}</td></tr>
                @endif
            </table>
        @endif

        {{-- ── Observaciones ── --}}
        @if($report->observations)
            <div class="section-title">Observaciones</div>
            <div class="text-block">{{ $report->observations }}</div>
        @endif

        @if($report->recommendations)
            <div class="section-title">Recomendaciones</div>
            <div class="text-block">{{ $report->recommendations }}</div>
        @endif

        {{-- ── Firma ── --}}
        @if($report->status === 'signed' && $report->signature_data)
            <div class="signature-block">
                <div class="signature-img-wrap">
                    <img src="{{ $report->signature_data }}" class="signature-img" alt="Firma">
                </div>
                <div class="signature-info">
                    <div class="signer-name">{{ $report->signature_name }}</div>
                    <div class="signer-position">{{ $report->signature_position }}</div>
                    @if($report->signature_phone)
                        <div class="signer-phone">{{ $report->signature_phone }}</div>
                    @endif
                    <div class="signer-date">Firmado el {{ $report->signed_at->format('d/m/Y \a \l\a\s H:i') }}</div>
                </div>
            </div>
        @else
            <div style="margin-top:32px; padding-top:12px; border-top:1px solid #E5E7EB;">
                <div style="width:240px; border-bottom:1px solid #111827; height:48px; display:inline-block;"></div>
                <div style="font-size:9px; color:#6B7280; margin-top:4px;">Firma del Responsable</div>
                <div style="font-size:9px; color:#6B7280;">Nombre: ___________________________</div>
                <div style="font-size:9px; color:#6B7280; margin-top:2px;">Cargo: ____________________________</div>
            </div>
        @endif

    </div>{{-- /page --}}

    {{-- ── Footer ── --}}
    <div class="pdf-footer">
        <div class="footer-left">QUITERM INDUSTRIES · Reporte de Servicio {{ $report->report_number }}</div>
        <div class="footer-right">Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>

</body>
</html>
