@extends('admin.layouts.master')

@section('title', $report->name . ' - Reportes - Admin')

@push('styles')
@vite(['resources/css/admin/pages/crm-reports.css'])
<style>
:root {
    --background--white:          #ffffff;
    --header-footer-color:        #1A2535;
    --text-subwhite-color:        #D1D5DC;
    --text-description-color:     #6B7280;
    --secondary-color:            #ff6213;
    --button-primary-color:       #ff6213;
    --button-primary-color-hover: #de4a00;
    --font-family:                'Inter', sans-serif;
    --shadow-sm:                  0 1px 2px rgba(0,0,0,.06);
    --shadow-md:                  0 10px 20px rgba(0,0,0,.1);
}

.report-show-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.report-show-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.report-show-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.report-show-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.report-show-breadcrumb-current { color: #374151; }
.report-show-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.report-show-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.report-show-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; white-space: nowrap; text-transform: capitalize; }
.report-show-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
.report-btn-export { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; }
.report-btn-export:hover { background: var(--button-primary-color-hover); color: #fff; }
.report-btn-delete { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: #fff; color: #DC2626; border: 1px solid #FECACA; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; white-space: nowrap; }
.report-btn-delete:hover { background: #FEF2F2; }

.report-show-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.report-show-chart-wrapper { position: relative; height: 380px; }

.report-show-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.report-show-table-scroll { overflow-x: auto; }
.report-show-table { width: 100%; border-collapse: collapse; min-width: 480px; }
.report-show-table thead tr { background: var(--header-footer-color); height: 44px; }
.report-show-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.report-show-table tbody tr { height: 48px; border-bottom: 1px solid #F3F4F6; }
.report-show-table tbody tr:last-child { border-bottom: none; }
.report-show-table td { padding: 0 20px; vertical-align: middle; font-size: 14px; color: #374151; }

@media (max-width: 640px) {
    .report-show-page { padding: 16px; gap: 16px; }
    .report-show-header { flex-direction: column; align-items: stretch; }
    .report-show-card { padding: 16px; }
    .report-show-chart-wrapper { height: 280px; }
}
</style>
@endpush

@section('content')
<div class="report-show-page">

    {{-- Header --}}
    <div class="report-show-header">
        <div>
            <div class="report-show-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('admin.reports.index') }}" style="color:inherit;text-decoration:none;">Reportes</a>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="report-show-breadcrumb-current">{{ $report->name }}</span>
            </div>
            <h1 class="report-show-title">{{ $report->name }}</h1>
            <div class="report-show-meta">
                <span class="report-show-pill">{{ str_replace('_', ' ', $report->data_source) }}</span>
                <span class="report-show-pill">{{ ucfirst($report->chart_type) }}</span>
                @if($report->creator)
                    <span style="font-size:13px;color:var(--text-description-color);">Creado por {{ $report->creator->name }}</span>
                @endif
            </div>
        </div>
        <div class="report-show-actions">
            <a href="{{ route('admin.reports.export', $report) }}" class="report-btn-export">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                Exportar a Excel
            </a>
            <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('¿Eliminar este reporte?')">
                @csrf @method('DELETE')
                <button type="submit" class="report-btn-delete">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- Chart --}}
    <div class="report-show-card">
        <div class="report-show-chart-wrapper">
            <canvas id="report-chart"></canvas>
        </div>
    </div>

    {{--
        Datos embebidos para que el JS externo (bundleado, chart.js/auto)
        inicialice el canvas #report-chart leyendo este JSON. Estructura:
        { type: 'bar'|'line'|'pie'|'doughnut'|'table', labels: [...], datasets: [{label, data}, ...] }
    --}}
    <script type="application/json" id="report-data">
        {!! Js::from([
            'type' => $report->chart_type,
            'labels' => $result['labels'] ?? [],
            'datasets' => $result['datasets'] ?? [],
        ]) !!}
    </script>

    {{-- Data table --}}
    <div class="report-show-table-card">
        <div class="report-show-table-scroll">
            <table class="report-show-table">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        @foreach(($result['datasets'] ?? []) as $dataset)
                            <th>{{ $dataset['label'] ?? 'Valor' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse(($result['labels'] ?? []) as $index => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            @foreach(($result['datasets'] ?? []) as $dataset)
                                <td>{{ $dataset['data'][$index] ?? '—' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 1 + count($result['datasets'] ?? []) }}" style="text-align:center;color:#9CA3AF;padding:32px 20px;">Sin datos para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
@vite(['resources/js/admin/dashboard-grid.js'])
@endpush
@endsection
