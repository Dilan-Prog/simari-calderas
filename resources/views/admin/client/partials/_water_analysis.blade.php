{{--
    Pestaña "Análisis químico" de la ficha del cliente (solo lectura): gráficas del reporte diario que el cliente
    captura en su portal + tabla de los últimos registros. Var: $customer.
    Periodo con ?dias=7|30|90 (los enlaces llevan #water-analysis para conservar la pestaña).
--}}
@php
    $waInsights = app(\App\Services\WaterAnalysisInsights::class);
    $waAnalysis = $waInsights->forCustomer((int) $customer->id, (int) request('dias', 30));
    $waRecent = $waInsights->recentReports((int) $customer->id, 30);

    $waPeriodUrls = [];
    foreach (\App\Services\WaterAnalysisInsights::PERIODS as $p) {
        $waPeriodUrls[$p] = route('admin.clients.information', ['id' => $customer->id, 'dias' => $p]) . '#water-analysis';
    }

    $waHeadActions = auth()->user()?->hasPermission('erp-settings')
        ? new \Illuminate\Support\HtmlString('<a class="wa-link" href="' . e(route('admin.erp-settings.water-analysis.index')) . '">Configurar rangos</a>')
        : null;

    $waLast = $waRecent['rows'][0]['date'] ?? null;
    $waStatusLabel = ['ok' => 'Dentro de rango', 'bajo' => 'Bajo', 'alto' => 'Alto', 'sin_rango' => 'Sin rango definido'];
    $waStatusClass = ['ok' => 'ok', 'bajo' => 'low', 'alto' => 'high', 'sin_rango' => 'none'];
@endphp

@push('styles')
<style>
    .wa-link { font-size: .8125rem; font-weight: 600; color: #c2410c; text-decoration: none; }
    .wa-link:hover { text-decoration: underline; }
    .wa-table-wrap { margin-top: 1rem; }
    .wa-table-wrap h3 { margin: 0 0 .25rem; font-size: 1rem; font-weight: 700; color: #111827; }
    .wa-table-wrap > p { margin: 0 0 .75rem; font-size: .8125rem; color: #6b7280; }
    .wa-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
    .wa-table th { text-align: left; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: #6b7280; padding: 9px 12px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    .wa-table td { padding: 9px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .wa-table tr:last-child td { border-bottom: 0; }
    .wa-val { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12.5px; font-weight: 600; white-space: nowrap; border: 1px solid transparent; }
    .wa-val--ok { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
    .wa-val--low { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .wa-val--high { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .wa-val--none { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }
    .wa-none { color: #9ca3af; }
    .wa-notes { max-width: 260px; color: #374151; white-space: pre-line; overflow-wrap: anywhere; }
    .wa-legend { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
</style>
@endpush

{{-- ═════ Tab: Análisis químico ═════ --}}
<div class="tab-content-show-client" id="water-analysis">
    @include('partials.water-analysis-charts', [
        'analysis'    => $waAnalysis,
        'periodUrls'  => $waPeriodUrls,
        'idp'         => 'wa',
        'heading'     => 'Análisis químico del agua',
        'subheading'  => 'Registro diario que captura el cliente en su portal · ' . ($waLast ? 'Último registro: ' . $waLast : 'Sin registros todavía'),
        'headActions' => $waHeadActions,
    ])

    <div class="wa-table-wrap">
        <h3>Últimos registros</h3>
        <p>Los 30 registros más recientes, sin importar el periodo seleccionado. Esta vista es solo de consulta.</p>

        @if (empty($waRecent['rows']))
            <div class="ch-empty" style="padding:22px;text-align:center;color:#9ca3af;font-size:13px;background:#fff;border:1px dashed #e5e7eb;border-radius:10px;">
                Este cliente todavía no tiene registros de análisis químico.
            </div>
        @else
            <div class="table-scroll">
                <table class="wa-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            @foreach ($waRecent['parameters'] as $param)
                                <th>{{ $param['name'] }}@if ($param['unit'] && $param['unit'] !== '—') ({{ $param['unit'] }})@endif</th>
                            @endforeach
                            <th>Notas</th>
                            <th>Registró</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($waRecent['rows'] as $row)
                            <tr>
                                <td style="white-space:nowrap;font-weight:600">{{ $row['date'] }}</td>
                                @foreach ($row['cells'] as $cell)
                                    <td>
                                        @if ($cell['value'] === null)
                                            <span class="wa-none">—</span>
                                        @else
                                            <span class="wa-val wa-val--{{ $waStatusClass[$cell['status']] ?? 'none' }}" title="{{ $waStatusLabel[$cell['status']] ?? '' }}">{{ $cell['value'] }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="wa-notes">{{ $row['notes'] ?: '—' }}</td>
                                <td style="white-space:nowrap">{{ $row['recorded_by'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="wa-legend">
                @foreach ($waStatusLabel as $key => $label)
                    <span class="wa-val wa-val--{{ $waStatusClass[$key] }}">{{ $label }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        // Las gráficas se crean con la pestaña oculta: al abrirla se les pide recalcular su tamaño.
        (function () {
            var btn = document.querySelector('.tab-show-client[data-tab="water-analysis"]');
            if (btn) btn.addEventListener('click', function () { setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 50); });
        })();
    </script>
</div>
