@extends('admin.layouts.master')
@section('title')
    Conversiones a Google - Admin
@endsection
@push('styles')
    @vite(['resources/css/admin/pages/erp-core.css', 'resources/css/admin/pages/erp-crm.css'])
@endpush
@push('scripts')
    @vite(['resources/js/admin/erp-crm.js'])
@endpush
@section('content')
    <div class="erp-page">
        <div class="erp-page-head">
            <div>
                <h1 class="erp-title">Conversiones a Google</h1>
                <p class="erp-subtitle">
                    Cotizado (secundaria) y Venta ganada (principal) enviadas con la Data Manager API.
                    @unless ($configured)
                        <strong class="erp-tone-warn">Credenciales de Google sin configurar: las conversiones quedan en cola.</strong>
                    @endunless
                </p>
            </div>
            <div style="display:flex; gap:8px;">
                <a class="erp-btn" href="{{ route('admin.google-ads.index') }}">Panel legado</a>
                <form method="POST" action="{{ route('admin.google-conversions.retry-failed') }}" data-crm-confirm="¿Reintentar todas las subidas fallidas?">@csrf
                    <button class="erp-btn primary" type="submit">Reintentar fallidas</button>
                </form>
            </div>
        </div>

        @if (session('success'))<div class="crm-flash ok">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="crm-flash err">{{ session('error') }}</div>@endif

        <div class="crm-summary">
            <a class="crm-stat" href="{{ route('admin.google-conversions.index', ['status' => 'enviada']) }}"><strong class="erp-tone-ok">{{ $summary['enviadas'] }}</strong><span>Enviadas</span></a>
            <a class="crm-stat" href="{{ route('admin.google-conversions.index', ['status' => 'pendiente']) }}"><strong class="erp-tone-warn">{{ $summary['pendientes'] }}</strong><span>Pendientes</span></a>
            <a class="crm-stat" href="{{ route('admin.google-conversions.index', ['status' => 'fallida']) }}"><strong class="erp-tone-{{ $summary['fallidas'] ? 'bad' : 'ok' }}">{{ $summary['fallidas'] }}</strong><span>Fallidas</span></a>
            <a class="crm-stat" href="{{ route('admin.google-conversions.index', ['status' => 'sin_atribucion']) }}"><strong>{{ $summary['sin_atribucion'] }}</strong><span>Sin atribución</span></a>
            <a class="crm-stat" href="{{ route('admin.google-conversions.index', ['por_vencer' => 1]) }}"><strong class="erp-tone-{{ $summary['por_vencer'] ? 'warn' : 'ok' }}">{{ $summary['por_vencer'] }}</strong><span>GCLID por vencer ({{ config('google_conversions.expiring_days') }} d)</span></a>
        </div>

        <form method="GET" class="crm-filters">
            <label>Buscar<input type="text" name="q" value="{{ request('q') }}" placeholder="Order ID o identificador"></label>
            <label>Estado
                <select name="status" data-crm-autosubmit>
                    <option value="">Todos</option>
                    @foreach (\App\Models\GoogleConversionUpload::STATUSES as $k => $label)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Acción
                <select name="action" data-crm-autosubmit>
                    <option value="">Todas</option>
                    @foreach (\App\Models\GoogleConversionUpload::ACTIONS as $k => $label)
                        <option value="{{ $k }}" @selected(request('action') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Operación
                <select name="operation" data-crm-autosubmit>
                    <option value="">Todas</option>
                    @foreach (\App\Models\GoogleConversionUpload::OPERATIONS as $k => $label)
                        <option value="{{ $k }}" @selected(request('operation') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Desde<input type="date" name="desde" value="{{ request('desde') }}"></label>
            <label>Hasta<input type="date" name="hasta" value="{{ request('hasta') }}"></label>
            <button class="erp-btn" type="submit">Filtrar</button>
        </form>

        @if (count($chips))
            <div class="crm-chips">
                @foreach ($chips as $chip)
                    <a class="crm-chip-filter" href="{{ $chip['remove'] }}" title="Quitar filtro">{{ $chip['label'] }}: {{ $chip['value'] }} ✕</a>
                @endforeach
                <a class="crm-chip-filter" href="{{ route('admin.google-conversions.index') }}">Limpiar todo</a>
            </div>
        @endif

        <div class="crm-table-wrap">
            <table class="crm-table">
                <thead>
                    <tr><th>#</th><th>Order ID</th><th>Acción</th><th>Operación</th><th>Identificador</th><th>Valor (MXN)</th><th>Estado</th><th>Intentos</th><th>Fecha</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($uploads as $u)
                    <tr>
                        <td>{{ $u->id }}</td>
                        <td><a href="{{ route('admin.google-conversions.show', $u) }}">{{ $u->order_id }}</a></td>
                        <td>{{ $u->action_label }}</td>
                        <td>{{ $u->operation_label }}</td>
                        <td class="crm-mono">{{ strtoupper($u->identifier_type) }}@if ($u->identifier): {{ \Illuminate\Support\Str::limit($u->identifier, 18) }}@endif
                            @if ($u->gclid_expires_at && $u->status === 'pendiente')<div class="crm-muted">vence {{ $u->gclid_expires_at->format('d/m/Y') }}</div>@endif
                        </td>
                        <td>{{ $u->conversion_value !== null ? '$' . number_format((float) $u->conversion_value, 2) : '—' }}</td>
                        <td><span class="erp-chip erp-tone-{{ $u->status_tone }}">{{ $u->status_label }}</span>
                            @if ($u->last_error)<div class="crm-muted" title="{{ $u->last_error }}">{{ \Illuminate\Support\Str::limit($u->last_error, 50) }}</div>@endif
                        </td>
                        <td>{{ $u->attempts }}/{{ config('google_conversions.max_attempts') }}</td>
                        <td>{{ $u->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($u->canRetry())
                                <form method="POST" action="{{ route('admin.google-conversions.retry', $u) }}">@csrf
                                    <button class="erp-btn small" type="submit">Reintentar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="crm-empty">Aún no hay conversiones con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="crm-pager">{{ $uploads->links() }}</div>
    </div>
@endsection
