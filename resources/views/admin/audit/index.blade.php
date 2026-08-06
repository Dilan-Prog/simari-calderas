@extends('admin.layouts.master')

@section('title')
    Auditoría Sistema - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/audit.css')
@endpush

@section('content')
<div class="audit-page">
    <div>
        <p class="breadcrumb-clients-manager">Panel de Control &gt; <strong>Auditoría Sistema</strong></p>
        <h1 style="margin:0 0 4px;">Auditoría Sistema</h1>
        <p class="breadcrumb-clients-manager main">Bitácora de creación, edición y eliminación de registros en todos los módulos</p>
    </div>

    <div class="audit-card">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="audit-filters">
            <div class="audit-filters__field">
                <label for="auditEntityType">Módulo</label>
                <select name="entity_type" id="auditEntityType">
                    <option value="">Todos</option>
                    @foreach ($entityTypes as $type)
                        <option value="{{ $type }}" @selected(request('entity_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filters__field">
                <label for="auditAction">Acción</label>
                <select name="action" id="auditAction">
                    <option value="">Todas</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filters__field">
                <label for="auditUser">Usuario</label>
                <select name="performed_by_user_id" id="auditUser">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) request('performed_by_user_id') === (string) $user->id)>
                            {{ trim($user->first_name . ' ' . $user->last_name) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filters__field">
                <label for="auditDateFrom">Desde</label>
                <input type="date" name="date_from" id="auditDateFrom" value="{{ request('date_from') }}">
            </div>
            <div class="audit-filters__field">
                <label for="auditDateTo">Hasta</label>
                <input type="date" name="date_to" id="auditDateTo" value="{{ request('date_to') }}">
            </div>
            <div class="audit-filters__actions">
                <button type="submit" class="button-primary size-adjustment">Filtrar</button>
                <a href="{{ route('admin.audit.index') }}" class="button-secondary size-adjustment">Limpiar</a>
            </div>
        </form>

        <div class="audit-table-wrap">
            <table class="audit-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>Usuario</th>
                        <th>Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->performed_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->entity_type }}</td>
                            <td>
                                @php
                                    $badgeClass = match(true) {
                                        $log->action === 'created' => 'audit-badge--created',
                                        $log->action === 'updated' => 'audit-badge--updated',
                                        $log->action === 'deleted' => 'audit-badge--deleted',
                                        default => 'audit-badge--other',
                                    };
                                @endphp
                                <span class="audit-badge {{ $badgeClass }}">{{ $log->action }}</span>
                            </td>
                            <td>#{{ $log->entity_id }}</td>
                            <td>{{ $log->performedBy ? trim($log->performedBy->first_name . ' ' . $log->performedBy->last_name) : 'Sistema' }}</td>
                            <td>
                                @if ($log->old_value || $log->new_value)
                                    <span class="audit-changes-preview" title="{{ json_encode(['old' => $log->old_value, 'new' => $log->new_value], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}">
                                        Ver cambios
                                    </span>
                                @else
                                    <span style="color:#9ca3af;">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; color:#9ca3af; padding:24px 0;">Sin registros de bitácora todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            {{ $logs->links('admin.components.pagination') }}
        @endif
    </div>
</div>
@endsection
