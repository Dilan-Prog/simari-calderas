@extends('admin.layouts.master')

@section('title', 'Reportes - Admin')

@push('styles')
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

.reports-page { padding: 32px; font-family: var(--font-family); display: flex; flex-direction: column; gap: 24px; }

.reports-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.reports-breadcrumb { display: flex; align-items: center; gap: 4px; font-size: 12px; color: var(--text-description-color); margin-bottom: 8px; }
.reports-breadcrumb svg { flex-shrink: 0; color: #9CA3AF; }
.reports-breadcrumb-current { color: #374151; }
.reports-title { font-size: 24px; font-weight: 700; color: #111827; line-height: 1.2; margin: 0 0 6px; }
.reports-subtitle { font-size: 14px; color: var(--text-description-color); margin: 0; }
.reports-btn-new { display: inline-flex; align-items: center; gap: 8px; height: 40px; padding: 0 16px; background: var(--button-primary-color); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; font-family: var(--font-family); text-decoration: none; cursor: pointer; box-shadow: var(--shadow-md); transition: background .2s; white-space: nowrap; }
.reports-btn-new:hover { background: var(--button-primary-color-hover); color: #fff; }

.reports-alert-success { background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 14px 16px; color: #16A34A; font-size: 13px; }

.reports-section-title { font-size: 16px; font-weight: 600; color: #111827; margin: 0; }

/* ── Prebuilt cards ─────────────────────── */
.reports-prebuilt-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.reports-prebuilt-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); padding: 20px; display: flex; flex-direction: column; gap: 10px; text-decoration: none; transition: box-shadow .2s, transform .2s; }
.reports-prebuilt-card:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.reports-prebuilt-icon { width: 36px; height: 36px; border-radius: 8px; background: #FFF7ED; color: var(--secondary-color); display: inline-flex; align-items: center; justify-content: center; }
.reports-prebuilt-name { font-size: 14px; font-weight: 600; color: #111827; margin: 0; }
.reports-prebuilt-desc { font-size: 13px; color: var(--text-description-color); margin: 0; line-height: 1.4; }
.reports-prebuilt-link { font-size: 13px; font-weight: 500; color: var(--secondary-color); display: inline-flex; align-items: center; gap: 4px; margin-top: auto; }

/* ── Table card ──────────────────────────── */
.reports-table-card { background: #fff; border-radius: 8px; box-shadow: var(--shadow-sm); overflow: hidden; }
.reports-table-scroll { overflow-x: auto; }
.reports-table { width: 100%; border-collapse: collapse; min-width: 720px; }
.reports-table thead tr { background: var(--header-footer-color); height: 44px; }
.reports-table thead th { padding: 0 20px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--text-subwhite-color); white-space: nowrap; }
.reports-table tbody tr { height: 52px; border-bottom: 1px solid #F3F4F6; transition: background .15s; }
.reports-table tbody tr:last-child { border-bottom: none; }
.reports-table tbody tr:hover { background: rgba(255,247,237,.4); }
.reports-table td { padding: 0 20px; vertical-align: middle; font-size: 14px; color: #374151; }
.reports-td-name { font-size: 14px; font-weight: 600; color: var(--secondary-color); text-decoration: none; }
.reports-td-name:hover { text-decoration: underline; color: var(--button-primary-color-hover); }
.reports-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500; background: #EFF6FF; color: #3B82F6; border: 1px solid #BFDBFE; white-space: nowrap; text-transform: capitalize; }
.reports-actions { display: flex; align-items: center; gap: 4px; }
.reports-action-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; display: inline-flex; align-items: center; justify-content: center; color: var(--text-description-color); text-decoration: none; cursor: pointer; transition: background .15s, color .15s; padding: 0; }
.reports-action-btn:hover { background: #F3F4F6; color: var(--secondary-color); }
.reports-action-btn-delete:hover { background: #FEF2F2 !important; color: #DC2626 !important; }
.reports-empty-row td { padding: 48px 20px; text-align: center; }
.reports-empty-inner { display: flex; flex-direction: column; align-items: center; gap: 10px; color: #9CA3AF; }
.reports-empty-inner svg { opacity: .4; }
.reports-empty-inner p { font-size: 14px; margin: 0; }

@media (max-width: 900px) {
    .reports-prebuilt-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .reports-page { padding: 16px; gap: 16px; }
    .reports-header { flex-direction: column; align-items: stretch; }
    .reports-btn-new { justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="reports-page">

    {{-- Header --}}
    <div class="reports-header">
        <div>
            <div class="reports-breadcrumb">
                <span>Panel de Control</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                <span class="reports-breadcrumb-current">Reportes</span>
            </div>
            <h1 class="reports-title">Reportes</h1>
            <p class="reports-subtitle">Reportes personalizados y análisis predefinidos del CRM</p>
        </div>
        <div>
            <a href="{{ route('admin.reports.create') }}" class="reports-btn-new">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                Nuevo reporte
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="reports-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Prebuilt reports --}}
    <div>
        <h2 class="reports-section-title" style="margin-bottom:14px;">Reportes predefinidos</h2>
        <div class="reports-prebuilt-grid">
            @foreach ($prebuiltReports as $prebuilt)
            <a href="{{ route($prebuilt['route']) }}" class="reports-prebuilt-card">
                <span class="reports-prebuilt-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                </span>
                <h3 class="reports-prebuilt-name">{{ $prebuilt['name'] }}</h3>
                <p class="reports-prebuilt-desc">{{ $prebuilt['description'] }}</p>
                <span class="reports-prebuilt-link">
                    Ver reporte
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Saved custom reports --}}
    <div>
        <h2 class="reports-section-title" style="margin-bottom:14px;">Mis reportes</h2>
        <div class="reports-table-card">
            <div class="reports-table-scroll">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fuente de datos</th>
                            <th>Tipo de gráfico</th>
                            <th>Creado por</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                        <tr>
                            <td><a href="{{ route('admin.reports.show', $report) }}" class="reports-td-name">{{ $report->name }}</a></td>
                            <td><span class="reports-pill">{{ str_replace('_', ' ', $report->data_source) }}</span></td>
                            <td>{{ ucfirst($report->chart_type) }}</td>
                            <td>{{ $report->creator?->name ?? '—' }}</td>
                            <td>{{ $report->created_at?->format('d M Y') }}</td>
                            <td>
                                <div class="reports-actions">
                                    <a href="{{ route('admin.reports.show', $report) }}" class="reports-action-btn" title="Ver">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.reports.destroy', $report) }}" onsubmit="return confirm('¿Eliminar este reporte?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="reports-action-btn reports-action-btn-delete" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="reports-empty-row">
                            <td colspan="6">
                                <div class="reports-empty-inner">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                                    <p>Aún no has creado ningún reporte personalizado.</p>
                                    <a href="{{ route('admin.reports.create') }}" style="font-size:13px;color:#ff6213;text-decoration:none;">Crear el primero</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
