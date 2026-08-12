@extends('admin.layouts.master')

@section('title', $dashboard->name . ' - Admin')

@section('content')
<div style="padding: 32px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <div>
            <h1 style="font-size:24px; font-weight:700; margin:0;">{{ $dashboard->name }}</h1>
            <p style="color:#6B7280; margin:4px 0 0;">
                {{ $dashboard->owner?->full_name }}
                @if ($dashboard->is_shared)
                    &middot; <span class="badge badge-success">Compartido</span>
                @endif
            </p>
        </div>
        <div style="display:flex; gap:8px;">
            @can('crm-reports')
                <a href="{{ route('admin.dashboards.edit', $dashboard) }}" class="btn btn-secondary">Editar</a>
            @endcan
            <a href="{{ route('admin.dashboards.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    @if ($dashboard->reports->isEmpty())
        <div style="color:#6B7280; margin-top:24px;">
            Este dashboard todavía no tiene reportes agregados.
        </div>
    @else
        {{-- Grid de widgets. Cada widget trae su propio canvas y su propio
             bloque JSON embebido con los datos ya construidos por
             ReportBuilderService (ver DashboardController@show). El script
             de inicialización de Chart.js itera sobre
             [data-report-id] para instanciar cada gráfico. --}}
        <div id="dashboardWidgetsGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:24px; margin-top:24px;">
            @foreach ($dashboard->reports as $report)
                @php $reportId = $report->id; @endphp
                <div class="dashboard-widget" data-report-id="{{ $reportId }}" style="border:1px solid #E5E7EB; border-radius:8px; padding:16px; background:#fff;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                        <div>
                            <div style="font-weight:600;">{{ $report->name }}</div>
                            <div style="color:#6B7280; font-size:12px;">{{ $report->data_source }} &middot; {{ $report->chart_type }}</div>
                        </div>
                        <a href="{{ route('admin.reports.show', $report) }}" style="font-size:12px;">Ver reporte completo</a>
                    </div>

                    <canvas id="dashboard-chart-{{ $reportId }}" height="180" data-chart-type="{{ $report->chart_type === 'table' ? 'bar' : $report->chart_type }}"></canvas>

                    {{-- Datos embebidos: id="dashboard-data-{reportId}",
                         application/json, leídos por JS.parse(textContent). --}}
                    <script type="application/json" id="dashboard-data-{{ $reportId }}">{!! \Illuminate\Support\Js::from($builtReports[$reportId] ?? ['labels' => [], 'datasets' => []]) !!}</script>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('styles')
@vite(['resources/css/admin/pages/crm-reports.css'])
@endpush

@push('scripts')
{{-- Itera sobre cada widget (#dashboardWidgetsGrid [data-report-id]) e
     instancia su gráfico con renderChart(), leyendo el canvas
     #dashboard-chart-{reportId} y su JSON embebido en
     #dashboard-data-{reportId}. Ver resources/js/admin/dashboard-grid.js. --}}
@vite(['resources/js/admin/dashboard-grid.js'])
@endpush
