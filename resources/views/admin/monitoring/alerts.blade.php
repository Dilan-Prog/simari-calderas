@extends('admin.layouts.master')
@section('title', 'Alertas de Monitoreo - Admin')
@push('styles')
    @include('admin.monitoring._styles')
@endpush
@section('content')
@php
    use App\Http\Controllers\Backend\MonitoringController;
@endphp
<div class="erp-page mon-page">

    <x-erp.page-header
        title="Alertas de monitoreo"
        desc="Lecturas del agua fuera del rango aceptable en todos los clientes."
        :crumbs="[['Producción', null], ['Monitoreo de Clientes', route('admin.monitoring.index')], ['Alertas', null]]">
        <a href="{{ route('admin.monitoring.index') }}" class="erp-btn">Ver programas</a>
    </x-erp.page-header>

    <div class="mon-kpis" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="mon-kpi">
            <span><i class="erp-dot erp-dot--{{ $alerts->count() > 0 ? 'bad' : 'ok' }}"></i>Lecturas fuera de rango</span>
            <b>{{ $alerts->count() }}</b>
            <small>Últimos {{ $days }} días</small>
        </div>
        <div class="mon-kpi">
            <span><i class="erp-dot erp-dot--warn"></i>Clientes afectados</span>
            <b>{{ $alerts->pluck('program.customer_id')->filter()->unique()->count() }}</b>
        </div>
        <div class="mon-kpi">
            <span><i class="erp-dot erp-dot--info"></i>Parámetros distintos</span>
            <b>{{ $alerts->pluck('item_id')->unique()->count() }}</b>
        </div>
    </div>

    <div class="erp-card">
        <div class="erp-toolbar">
            <form method="GET" class="mon-toolbar-form">
                <select name="customer_id" class="erp-select" onchange="this.form.submit()" aria-label="Cliente">
                    <option value="">Todos los clientes</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}" @selected($customerId === $c->id)>{{ MonitoringController::customerName($c) }}{{ $c->company ? ' · ' . $c->company : '' }}</option>
                    @endforeach
                </select>
                <select name="days" class="erp-select" onchange="this.form.submit()" aria-label="Periodo">
                    @foreach ([7 => 'Últimos 7 días', 30 => 'Últimos 30 días', 90 => 'Últimos 90 días', 365 => 'Último año'] as $d => $l)
                        <option value="{{ $d }}" @selected($days === $d)>{{ $l }}</option>
                    @endforeach
                </select>
                @if ($customerId)<a href="{{ route('admin.monitoring.alerts', ['days' => $days]) }}" class="erp-btn erp-btn--link">Quitar filtro</a>@endif
            </form>
        </div>
        <div class="mon-panel">
            @include('admin.monitoring._alerts_table', ['alerts' => $alerts, 'showProgram' => true])
        </div>
    </div>
</div>
@endsection
