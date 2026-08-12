@extends('admin.layouts.master')

@section('title', 'Actividad por vendedor - Admin')

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

.prebuilt-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }
.prebuilt-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.prebuilt-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.prebuilt-breadcrumb-current { color: #374151; }
.prebuilt-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.prebuilt-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }

.prebuilt-filter-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 16px; }
.prebuilt-filter-row { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
.prebuilt-filter-field { display: flex; flex-direction: column; gap: 6px; min-width: 180px; }
.prebuilt-filter-label { font-size: 13px; font-weight: 500; color: #374151; }
.prebuilt-filter-input { height: 40px; padding: 0 12px; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 13px; font-family: var(--font-family); color: #111827; background: #fff; box-sizing: border-box; }
.prebuilt-filter-input:focus { outline: none; border-color: var(--secondary-color); box-shadow: 0 0 0 3px rgba(255,98,19,.12); }
.prebuilt-btn-filter { height: 40px; padding: 0 16px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--secondary-color); color: var(--secondary-color); background: transparent; border-radius: 6px; font-size: 13px; font-weight: 500; font-family: var(--font-family); cursor: pointer; transition: background .2s; white-space: nowrap; }
.prebuilt-btn-filter:hover { background: #FFF7ED; }

.prebuilt-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 24px; }
.prebuilt-chart-wrapper { position: relative; height: 340px; }

.prebuilt-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.prebuilt-table-scroll { overflow-x: auto; }
.prebuilt-table { width: 100%; border-collapse: collapse; min-width: 480px; }
.prebuilt-table thead tr { background: var(--header-footer-color); height: 44px; }
.prebuilt-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.prebuilt-table tbody tr { height: 48px; border-bottom: 1px solid #F3F4F6; }
.prebuilt-table tbody tr:last-child { border-bottom: none; }
.prebuilt-table td { padding: 0 20px; vertical-align: middle; font-size: 14px; color: #374151; }

.prebuilt-empty { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; text-align: center; padding: 48px 20px; }
.prebuilt-empty svg { opacity: .4; }
.prebuilt-empty p { font-size: 14px; margin: 0; }

@media (max-width: 640px) {
    .prebuilt-page { padding: 16px; gap: 16px; }
    .prebuilt-card { padding: 16px; }
    .prebuilt-chart-wrapper { height: 260px; }
    .prebuilt-filter-field { min-width: 0; flex: 1 1 140px; }
}
</style>
@endpush

@section('content')
<div class="prebuilt-page">

    <div>
        <div class="prebuilt-breadcrumb">
            <span>Panel de Control</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <a href="{{ route('admin.reports.index') }}" style="color:inherit;text-decoration:none;">Reportes</a>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
            <span class="prebuilt-breadcrumb-current">Actividad por vendedor</span>
        </div>
        <h1 class="prebuilt-title">Actividad por vendedor</h1>
        <p class="prebuilt-subtitle">Movimientos de etapa realizados por cada usuario</p>
    </div>

    <form method="GET" class="prebuilt-filter-card">
        <div class="prebuilt-filter-row">
            <div class="prebuilt-filter-field">
                <label class="prebuilt-filter-label" for="date_from">Fecha desde</label>
                <input type="date" name="date_from" id="date_from" class="prebuilt-filter-input" value="{{ request('date_from') }}">
            </div>
            <div class="prebuilt-filter-field">
                <label class="prebuilt-filter-label" for="date_to">Fecha hasta</label>
                <input type="date" name="date_to" id="date_to" class="prebuilt-filter-input" value="{{ request('date_to') }}">
            </div>
            <button type="submit" class="prebuilt-btn-filter">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z"/></svg>
                Filtrar
            </button>
        </div>
    </form>

    <div class="prebuilt-card">
        <div class="prebuilt-chart-wrapper">
            <canvas id="report-chart"></canvas>
        </div>
    </div>

    {{--
        Datos embebidos para el JS externo (chart.js/auto). Mismo patrón
        que admin.reports.show: canvas #report-chart + JSON en
        #report-data con { type, labels, datasets }.
    --}}
    <script type="application/json" id="report-data">
        {!! Js::from([
            'type' => 'bar',
            'labels' => $rows->pluck('user_name')->values()->all(),
            'datasets' => [[
                'label' => 'Movimientos de etapa',
                'data' => $rows->pluck('moves_count')->values()->all(),
            ]],
        ]) !!}
    </script>

    <div class="prebuilt-table-card">
        <div class="prebuilt-table-scroll">
            <table class="prebuilt-table">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th>Movimientos de etapa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->user_name }}</td>
                            <td>{{ $row->moves_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="prebuilt-empty">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                                    <p>Sin actividad registrada en el rango seleccionado.</p>
                                </div>
                            </td>
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
