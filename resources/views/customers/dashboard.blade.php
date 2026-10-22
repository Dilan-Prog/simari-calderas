@extends('customers.layouts.master')

@section('title', 'Inicio - Portal de servicios')

@push('styles')
<style>
    .cd-sub { color:#6b7280; font-size:14px; margin:4px 0 0; }
    .cd-actions { display:flex; gap:10px; flex-wrap:wrap; }
    .cd-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; font-family:var(--font-family); border:1px solid transparent; transition:background-color .15s ease, border-color .15s ease; }
    .cd-btn--primary { background:var(--button-primary-color,#ff6213); color:#fff; }
    .cd-btn--primary:hover { background:var(--button-primary-color-hover,#de4a00); }
    .cd-btn--ghost { background:#fff; color:#374151; border-color:#e5e7eb; }
    .cd-btn--ghost:hover { background:#f9fafb; }
    .cd-stat-link { text-decoration:none; color:inherit; display:block; transition:transform .15s ease, box-shadow .15s ease; }
    .cd-stat-link:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.08); }
    .cd-stat-hint { font-size:11px; color:#9ca3af; font-weight:500; }
    .cd-grid { display:grid; grid-template-columns:3fr 2fr; gap:24px; align-items:start; }
    .cd-panel-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; gap:12px; }
    .cd-link-all { font-size:12px; font-weight:600; color:var(--secondary-color,#ff6213); text-decoration:none; white-space:nowrap; }
    .cd-link-all:hover { text-decoration:underline; }
    .cd-list { display:flex; flex-direction:column; gap:10px; }
    .cd-item { display:flex; align-items:center; gap:14px; padding:12px; border-radius:10px; background:#f9fafb; text-decoration:none; color:inherit; transition:background-color .15s ease; }
    .cd-item:hover { background:#f3f4f6; }
    .cd-date { flex-shrink:0; width:52px; text-align:center; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:6px 0; }
    .cd-date-day { display:block; font-size:18px; font-weight:700; color:#1f2937; line-height:1.1; }
    .cd-date-mon { display:block; font-size:10px; font-weight:600; color:#ff6213; text-transform:uppercase; letter-spacing:.05em; }
    .cd-item-main { flex:1; min-width:0; }
    .cd-item-title { font-size:14px; font-weight:600; color:#1f2937; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .cd-item-meta { font-size:12px; color:#6b7280; margin:2px 0 0; }
    .cd-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; border:1px solid transparent; white-space:nowrap; flex-shrink:0; }
    .cd-badge--scheduled { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
    .cd-badge--in_progress { background:#fffbeb; color:#d97706; border-color:#fde68a; }
    .cd-badge--completed { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
    .cd-badge--draft { background:#f3f4f6; color:#6b7280; border-color:#d1d5db; }
    .cd-badge--signed { background:#f5f3ff; color:#7c3aed; border-color:#ddd6fe; }
    .cd-empty { text-align:center; padding:28px 12px; color:#9ca3af; }
    .cd-empty svg { margin-bottom:8px; }
    .cd-empty p { margin:0; font-size:13px; }
    .cd-rate-tag { font-size:11px; font-weight:600; color:#d97706; background:#fffbeb; border:1px solid #fde68a; border-radius:999px; padding:2px 8px; white-space:nowrap; }
    @media (max-width:1024px) { .cd-grid { grid-template-columns:1fr; } }
    @media (max-width:640px) { .cd-actions { width:100%; } .cd-btn { flex:1; justify-content:center; } .dash-header { flex-direction:column; align-items:flex-start; gap:12px; } }
</style>
@endpush

@section('content')
@php
    $displayName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');

    $svcLabels = ['scheduled' => 'Programado', 'in_progress' => 'En proceso', 'completed' => 'Completado', 'cancelled' => 'Cancelado', 'draft' => 'Borrador'];

    $urlRequest  = Route::has('customer.service-requests.create') ? route('customer.service-requests.create') : null;
    $urlCalendar = Route::has('customer.calendar') ? route('customer.calendar') : null;
    $urlHistory  = Route::has('customer.history') ? route('customer.history') : null;
    $urlProfile  = Route::has('customer.profile') ? route('customer.profile') : null;
    $urlServices = route('customer.technical-services.index');
    $urlReports  = route('customer.service-reports.index');
@endphp
<div class="dash-page">

    {{-- ── Header ── --}}
    <div class="dash-header">
        <div>
            <h1 class="dash-title">{{ $greeting }}, {{ $displayName !== '' ? $displayName : 'cliente' }}</h1>
            <p class="cd-sub">Este es el resumen de tus servicios con Equiterm Industries.</p>
        </div>
        <div class="cd-actions">
            @if ($urlRequest)
                <a href="{{ $urlRequest }}" class="cd-btn cd-btn--primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Solicitar servicio
                </a>
            @endif
            @if ($urlCalendar)
                <a href="{{ $urlCalendar }}" class="cd-btn cd-btn--ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Ver calendario
                </a>
            @endif
        </div>
    </div>

    {{-- ── Stats ── --}}
    <div class="dash-stats-grid">

        <a class="dash-stat-card cd-stat-link" href="{{ $urlServices }}?status=scheduled">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Servicios próximos</p>
                    <h3 class="dash-stat-value">{{ $stats['upcoming'] }}</h3>
                </div>
                <div class="dash-stat-icon blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
            </div>
            <span class="cd-stat-hint">Programados a partir de hoy</span>
        </a>

        <a class="dash-stat-card cd-stat-link" href="{{ $urlServices }}?status=in_progress">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Servicios en proceso</p>
                    <h3 class="dash-stat-value">{{ $stats['in_process'] }}</h3>
                </div>
                <div class="dash-stat-icon orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                </div>
            </div>
            <span class="cd-stat-hint">Nuestro equipo está trabajando</span>
        </a>

        <a class="dash-stat-card cd-stat-link" href="{{ $urlServices }}?status=completed">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Servicios realizados</p>
                    <h3 class="dash-stat-value">{{ $stats['completed'] }}</h3>
                </div>
                <div class="dash-stat-icon green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                </div>
            </div>
            <span class="cd-stat-hint">Servicios completados</span>
        </a>

        <a class="dash-stat-card cd-stat-link" href="{{ $urlReports }}">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Reportes por calificar</p>
                    <h3 class="dash-stat-value">{{ $stats['pending_rating'] }}</h3>
                </div>
                <div class="dash-stat-icon purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/></svg>
                </div>
            </div>
            <span class="cd-stat-hint">Cuéntanos qué te pareció el servicio</span>
        </a>
    </div>

    {{-- ── Listas ── --}}
    <div class="cd-grid">

        {{-- Próximos servicios --}}
        <div class="dash-panel">
            <div class="cd-panel-head">
                <h3 class="dash-panel-title">Próximos servicios</h3>
                <a class="cd-link-all" href="{{ $urlServices }}">Ver todos</a>
            </div>

            @if ($upcomingServices->isEmpty())
                <div class="cd-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <p>No tienes servicios programados por ahora.</p>
                    @if ($urlRequest)
                        <p style="margin-top:6px;"><a class="cd-link-all" href="{{ $urlRequest }}">Solicita un servicio</a></p>
                    @endif
                </div>
            @else
                <div class="cd-list">
                    @foreach ($upcomingServices as $service)
                        @php
                            $d = $service->service_date ? \Carbon\Carbon::parse($service->service_date) : null;
                            $time = $service->service_time ? substr((string) $service->service_time, 0, 5) : null;
                        @endphp
                        <a class="cd-item" href="{{ route('customer.technical-services.show', $service) }}">
                            <div class="cd-date">
                                <span class="cd-date-day">{{ $d ? $d->format('d') : '—' }}</span>
                                <span class="cd-date-mon">{{ $d ? $d->isoFormat('MMM') : '' }}</span>
                            </div>
                            <div class="cd-item-main">
                                <p class="cd-item-title">{{ $service->service_type_label }}</p>
                                <p class="cd-item-meta">
                                    {{ $service->service_number }}
                                    · {{ $d ? $d->isoFormat('ddd D [de] MMMM') : 'Sin fecha' }}{{ $time ? ' · ' . $time . ' h' : '' }}
                                </p>
                            </div>
                            <span class="cd-badge cd-badge--{{ $service->status }}">{{ $svcLabels[$service->status] ?? $service->status_label }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Últimos reportes --}}
        <div class="dash-panel">
            <div class="cd-panel-head">
                <h3 class="dash-panel-title">Últimos reportes</h3>
                <a class="cd-link-all" href="{{ $urlReports }}">Ver todos</a>
            </div>

            @if ($latestReports->isEmpty())
                <div class="cd-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/></svg>
                    <p>Aún no tienes reportes de servicio.</p>
                </div>
            @else
                <div class="cd-list">
                    @foreach ($latestReports as $report)
                        <a class="cd-item" href="{{ route('customer.service-reports.show', $report) }}">
                            <div class="cd-item-main">
                                <p class="cd-item-title">{{ $report->report_number }}</p>
                                <p class="cd-item-meta">
                                    {{ $report->service_type_label }}
                                    · {{ $report->service_date ? $report->service_date->isoFormat('D MMM YYYY') : 'Sin fecha' }}
                                </p>
                            </div>
                            <span class="cd-badge cd-badge--{{ $report->status }}">{{ $report->status_label }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($urlHistory || $urlProfile)
        <div class="cd-actions" style="margin-top:24px;">
            @if ($urlHistory)
                <a href="{{ $urlHistory }}" class="cd-btn cd-btn--ghost">Historial de servicios</a>
            @endif
            @if ($urlProfile)
                <a href="{{ $urlProfile }}" class="cd-btn cd-btn--ghost">Mi perfil</a>
            @endif
        </div>
    @endif

</div>
@endsection
