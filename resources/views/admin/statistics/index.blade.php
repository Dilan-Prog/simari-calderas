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
                        onclick="document.getElementById('statsDateFrom').value='';document.getElementById('statsDateTo').value='';"
                        class="stats-period-btn {{ $period['period'] === $slug ? 'is-active' : '' }}">{{ $label }}</button>
                @endforeach
            </div>

            {{-- Selector de rango personalizado — mismo componente que Cotizaciones
                 (prefijo stats-dp-, ver resources/js/admin/statistics-datepicker.js).
                 Siempre muestra el rango efectivo actual (preset o personalizado);
                 abrirlo deja elegir un rango explícito que prevalece sobre el preset. --}}
            <div class="stats-datepicker-wrapper">
                <button type="button" id="statsDatePickerTrigger"
                    class="stats-dp-trigger{{ $period['period'] === 'custom' ? ' has-value' : '' }}"
                    data-initial-from="{{ $period['period'] === 'custom' ? $period['from']->format('Y-m-d') : '' }}"
                    data-initial-to="{{ $period['period'] === 'custom' ? $period['to']->format('Y-m-d') : '' }}"
                    aria-label="Seleccionar rango de fechas personalizado">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    <span id="statsDatePickerLabel" class="stats-dp-trigger-label">{{ $period['rangeLabel'] }}</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <input type="hidden" name="date_from" id="statsDateFrom" value="{{ $period['period'] === 'custom' ? $period['from']->format('Y-m-d') : '' }}">
                <input type="hidden" name="date_to" id="statsDateTo" value="{{ $period['period'] === 'custom' ? $period['to']->format('Y-m-d') : '' }}">
            </div>

            <label class="stats-compare-toggle {{ $period['compare'] ? 'is-on' : '' }}">
                <input type="checkbox" name="compare" value="1" {{ $period['compare'] ? 'checked' : '' }} onchange="this.form.submit()">
                <span class="stats-compare-knob"></span>
                <span class="stats-compare-label">Comparar con {{ $period['compareLabel'] }}</span>
            </label>
            <div class="stats-filterbar-hint">El filtro aplica a toda la página · cada panel hereda el rango</div>
        </form>

        {{-- ═══ Modal del selector de rango, fuera del <form> (mismo patrón que
             Cotizaciones) — sus botones solo mutan los inputs ocultos de arriba
             y disparan statsPeriodForm.submit() ═══ --}}
        <div id="statsDatePickerOverlay" class="stats-dp-overlay" role="dialog" aria-modal="true" aria-label="Selector de rango de fechas">
            <div class="stats-dp-modal">
                <div class="stats-dp-header">
                    <div class="stats-dp-header-text">
                        <h3>Seleccionar rango de fechas</h3>
                        <p>Elige una fecha de inicio y una de fin</p>
                    </div>
                    <button type="button" id="statsDpCloseBtn" class="stats-dp-close" aria-label="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>

                <div class="stats-dp-body">
                    <div class="stats-dp-quick">
                        <span class="stats-dp-quick-label">Rangos rápidos</span>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="today">Hoy</button>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="7days">Últimos 7 días</button>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="30days">Últimos 30 días</button>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="thismonth">Este mes</button>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="lastmonth">Mes anterior</button>
                        <button type="button" class="stats-dp-quick-btn" data-quick-range="90days">Últimos 90 días</button>
                    </div>

                    <div class="stats-dp-calendars">
                        <div class="stats-dp-nav">
                            <button type="button" id="statsDpPrevMonth" class="stats-dp-nav-btn" aria-label="Mes anterior">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <div class="stats-dp-months">
                                <div id="statsDpLeftTitle" class="stats-dp-month-title"></div>
                                <div id="statsDpRightTitle" class="stats-dp-month-title"></div>
                            </div>
                            <button type="button" id="statsDpNextMonth" class="stats-dp-nav-btn" aria-label="Mes siguiente">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                        </div>

                        <div class="stats-dp-cals">
                            <div class="stats-dp-calendar">
                                <div class="stats-dp-weekdays">
                                    <div class="stats-dp-weekday">L</div><div class="stats-dp-weekday">M</div>
                                    <div class="stats-dp-weekday">M</div><div class="stats-dp-weekday">J</div>
                                    <div class="stats-dp-weekday">V</div><div class="stats-dp-weekday">S</div>
                                    <div class="stats-dp-weekday">D</div>
                                </div>
                                <div id="statsDpCalLeft" class="stats-dp-days"></div>
                            </div>
                            <div class="stats-dp-calendar">
                                <div class="stats-dp-weekdays">
                                    <div class="stats-dp-weekday">L</div><div class="stats-dp-weekday">M</div>
                                    <div class="stats-dp-weekday">M</div><div class="stats-dp-weekday">J</div>
                                    <div class="stats-dp-weekday">V</div><div class="stats-dp-weekday">S</div>
                                    <div class="stats-dp-weekday">D</div>
                                </div>
                                <div id="statsDpCalRight" class="stats-dp-days"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stats-dp-footer">
                    <div class="stats-dp-footer-dates">
                        <div class="stats-dp-date-box" id="statsDpDisplayStart"><span style="color:#9CA3AF">Inicio</span></div>
                        <span class="stats-dp-footer-arrow">→</span>
                        <div class="stats-dp-date-box" id="statsDpDisplayEnd"><span style="color:#9CA3AF">Fin</span></div>
                    </div>
                    <div class="stats-dp-footer-actions">
                        <button type="button" id="statsDpClearBtn" class="stats-dp-btn-clear">Limpiar</button>
                        <button type="button" id="statsDpCancelBtn" class="stats-dp-btn-cancel">Cancelar</button>
                        <button type="button" id="statsDpApplyBtn" class="stats-dp-btn-apply" disabled>Aplicar rango</button>
                    </div>
                </div>
            </div>
        </div>

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
@vite(['resources/js/admin/statistics-panel.js', 'resources/js/admin/statistics-datepicker.js'])
<script>
    // El toggle de comparar ya auto-envía el form (onchange); los botones
    // de periodo son <button type="submit"> normales — sin JS adicional
    // necesario para el filtro, es navegación GET simple, mismo criterio
    // que el resto del admin (no SPA fuera de Automatizaciones).
</script>
@endpush
