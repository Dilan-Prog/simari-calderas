@extends('admin.layouts.master')

@section('title')
    DevOps - Admin
@endsection

@push('styles')
    @vite('resources/css/admin/pages/devops.css')
@endpush

@section('content')
<div class="devops-page">
    <div>
        <p class="breadcrumb-clients-manager">Panel de Control &gt; <strong>DevOps</strong></p>
        <h1 style="margin:0 0 4px;">DevOps</h1>
        <p class="breadcrumb-clients-manager main">Consola SQL — solo administradores</p>
    </div>

    <div class="devops-warning">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
        <span>Esta herramienta ejecuta SQL directamente sobre la base de datos real. Las sentencias que modifican datos (INSERT/UPDATE/DELETE/DDL) generan un respaldo automático antes de ejecutarse y piden confirmación explícita. Solo se permite una sentencia por ejecución.</span>
    </div>

    <div class="devops-card">
        <p class="devops-card__title">Ejecutar sentencia SQL</p>
        <textarea id="devopsSqlInput" class="devops-sql-input" placeholder="SELECT * FROM products LIMIT 10;" spellcheck="false"></textarea>
        <div class="devops-actions">
            <button type="button" class="button-primary size-adjustment" id="devopsRunBtn">Ejecutar</button>
        </div>

        <div class="devops-results" id="devopsResults">
            <p class="devops-results__empty">Los resultados aparecerán aquí.</p>
        </div>
    </div>

    <div class="devops-card">
        <p class="devops-card__title">Historial reciente</p>
        <div class="devops-table-wrap">
            <table class="devops-history-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>SQL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $entry)
                        <tr>
                            <td>{{ $entry->performed_at?->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $entry->performedBy?->first_name ?? '—' }} {{ $entry->performedBy?->last_name ?? '' }}</td>
                            <td>
                                @php
                                    $badgeClass = match(true) {
                                        $entry->action === 'select' => 'devops-badge--select',
                                        $entry->action === 'write' => 'devops-badge--write',
                                        str_contains($entry->action, 'failed') => 'devops-badge--failed',
                                        default => 'devops-badge--rejected',
                                    };
                                @endphp
                                <span class="devops-badge {{ $badgeClass }}">{{ $entry->action }}</span>
                            </td>
                            <td><span class="devops-history-sql" title="{{ $entry->description }}">{{ $entry->description }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:#9ca3af; padding:24px 0;">Sin ejecuciones registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($history->hasPages())
            {{ $history->links('admin.components.pagination') }}
        @endif
    </div>
</div>

@include('admin.devops.partials._confirm_modal')
@endsection

@push('scripts')
    @include('admin.devops._scripts')
@endpush
