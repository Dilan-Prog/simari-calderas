@extends('admin.layouts.master')

@section('title', 'Editar ' . $dashboard->name . ' - Admin')

@section('content')
<div style="padding: 32px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <h1 style="font-size:24px; font-weight:700; margin:0;">Editar dashboard</h1>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.dashboards.show', $dashboard) }}" class="btn btn-secondary">Ver dashboard</a>
            <a href="{{ route('admin.dashboards.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
    <p style="color:#6B7280; margin:0 0 24px;">Arrastra los widgets para reordenarlos. Los cambios de orden se guardan automáticamente.</p>

    <div id="dashboard-feedback" style="margin-bottom:16px;"></div>

    <div style="display:flex; gap:32px; align-items:flex-start;">

        {{-- ── Columna principal: nombre + reportes ────────────────────── --}}
        <div style="flex: 1; min-width: 0;">

            <div style="border:1px solid #E5E7EB; border-radius:8px; padding:16px; margin-bottom:24px;">
                <form id="dashboard-settings-form" action="{{ route('admin.dashboards.update', $dashboard) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
                        <div class="form-group" style="flex:1; min-width:200px; margin:0;">
                            <label for="name">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ $dashboard->name }}" required>
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:8px; margin:0 0 8px;">
                            <input type="checkbox" name="is_shared" id="is_shared" value="1" {{ $dashboard->is_shared ? 'checked' : '' }}>
                            <label for="is_shared" style="margin:0;">Compartir con todo el equipo</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <h2 style="font-size:16px; font-weight:600; margin:0;">Reportes en este dashboard</h2>

                <form id="add-report-form" action="{{ route('admin.dashboards.addReport', $dashboard) }}" method="POST" style="display:flex; gap:8px;">
                    @csrf
                    <select name="report_id" id="report_id" class="form-control" style="min-width:220px;">
                        <option value="">-- Selecciona un reporte --</option>
                        @foreach ($availableReports as $report)
                            <option value="{{ $report->id }}">{{ $report->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-secondary" {{ $availableReports->isEmpty() ? 'disabled' : '' }}>+ Agregar reporte existente</button>
                </form>
            </div>

            {{-- Grid sortable: cada widget lleva data-report-id para que
                 SortableJS y el fetch de reordenar lo identifiquen. --}}
            <div
                id="dashboardReportsGrid"
                data-reorder-url="{{ route('admin.dashboards.reorderReports', $dashboard) }}"
                data-remove-url-template="{{ route('admin.dashboards.removeReport', ['dashboard' => $dashboard, 'report' => '__REPORT_ID__']) }}"
                style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;"
            >
                @forelse ($dashboard->reports as $report)
                    <div class="dashboard-widget" data-report-id="{{ $report->id }}" style="border:1px solid #E5E7EB; border-radius:8px; padding:16px; background:#fff; cursor:grab;">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                            <div>
                                <div style="font-weight:600;">{{ $report->name }}</div>
                                <div style="color:#6B7280; font-size:12px;">{{ $report->data_source }} &middot; {{ $report->chart_type }}</div>
                            </div>
                            <button type="button" class="dashboard-widget-remove" data-report-id="{{ $report->id }}" title="Quitar del dashboard" style="border:none; background:none; color:#DC2626; cursor:pointer; font-size:18px; line-height:1;">&times;</button>
                        </div>
                    </div>
                @empty
                    <div id="dashboardReportsEmpty" style="color:#6B7280; grid-column: 1 / -1;">
                        Todavía no hay reportes agregados a este dashboard.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Panel lateral: Compartir con ─────────────────────────────── --}}
        <div style="width: 280px; flex-shrink:0; border:1px solid #E5E7EB; border-radius:8px; padding:16px;">
            <h2 style="font-size:16px; font-weight:600; margin:0 0 12px;">Compartir con</h2>
            <p style="color:#6B7280; font-size:12px; margin:0 0 12px;">Usuarios específicos que podrán ver este dashboard (además del propietario y, si está marcado como compartido, de todo el equipo).</p>

            <form id="share-form" data-url="{{ route('admin.dashboards.share', $dashboard) }}">
                @csrf
                <select id="share-users" name="user_ids[]" multiple size="10" class="form-control" style="width:100%;">
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ $dashboard->sharedWith->contains('id', $user->id) ? 'selected' : '' }}>
                            {{ $user->full_name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary" style="margin-top:12px; width:100%;">Guardar acceso</button>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/admin/dashboard-grid.js'])
@include('admin.dashboards.partials._scripts')
@endpush
