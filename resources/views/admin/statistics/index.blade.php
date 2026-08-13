@extends('admin.layouts.master')

@section('title')
    {{ $section['title'] }} - Estadísticas - Admin
@endsection

@section('content')
<div class="stats-module">
    <div class="stats-content">
        <div class="stats-breadcrumb">Panel de Control &gt; Estadísticas &gt; <span>{{ $section['title'] }}</span></div>

        <div class="stats-header">
            <div>
                <h1 class="stats-page-title">{{ $section['title'] }}</h1>
                <div class="stats-page-subtitle">{{ $section['subtitle'] }}</div>
            </div>
        </div>

        <form method="GET" id="statsPeriodForm" class="stats-filterbar">
            <div class="stats-filterbar-label">Periodo</div>
            <div class="stats-period-group">
                @foreach ($periods as $slug => $label)
                    <button type="submit" name="period" value="{{ $slug }}"
                        class="stats-period-btn {{ $period['period'] === $slug ? 'is-active' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
            <div class="stats-range-chip">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4" stroke-linecap="round"/></svg>
                <span>{{ $period['rangeLabel'] }}</span>
            </div>
            <input type="hidden" name="period" value="{{ $period['period'] }}" id="statsPeriodHidden">
            <label class="stats-compare-toggle {{ $period['compare'] ? 'is-on' : '' }}">
                <input type="checkbox" name="compare" value="1" {{ $period['compare'] ? 'checked' : '' }} onchange="this.form.submit()">
                <span class="stats-compare-knob"></span>
                <span class="stats-compare-label">Comparar con {{ $period['compareLabel'] }}</span>
            </label>
            <div class="stats-filterbar-hint">El filtro aplica a toda la página · cada panel hereda el rango</div>
        </form>

        @if (count($kpis))
            <div class="stats-kpi-grid">
                @foreach ($kpis as $kpi)
                    @include('admin.statistics.partials._kpi', ['kpi' => $kpi])
                @endforeach
            </div>
        @endif

        @if (count($panels))
            <div class="stats-panel-grid">
                @foreach ($panels as $panel)
                    @include('admin.statistics.partials._panel', ['panel' => $panel])
                @endforeach
            </div>
        @endif

        @if ($activeSection === 'resumen' && count($domainCards))
            <div class="stats-domains">
                <div class="stats-domains-title">Entrar a un dominio</div>
                <div class="stats-domains-grid">
                    @foreach ($domainCards as $card)
                        <a href="{{ route('admin.statistics.show', $card['id']) }}" class="stats-domain-card">
                            <div class="stats-domain-top">
                                <div class="stats-domain-chip" style="background:{{ $card['chipBg'] }};color:{{ $card['chipColor'] }}">{!! $card['icon'] !!}</div>
                                <div class="stats-domain-title">{{ $card['title'] }}</div>
                            </div>
                            <div class="stats-domain-text">{{ $card['text'] }}</div>
                            <div class="stats-domain-bottom">
                                <span class="stats-domain-value">{{ $card['value'] }}</span>
                                <span class="stats-domain-trend">{{ $card['trend'] }}</span>
                                <span class="stats-domain-link">Ver detalle →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
@vite(['resources/css/admin/pages/statistics.css'])
@endpush

@push('scripts')
@vite(['resources/js/admin/statistics-panel.js'])
<script>
    // El toggle de comparar ya auto-envía el form (onchange); los botones
    // de periodo son <button type="submit"> normales — sin JS adicional
    // necesario para el filtro, es navegación GET simple, mismo criterio
    // que el resto del admin (no SPA fuera de Automatizaciones).
</script>
@endpush
