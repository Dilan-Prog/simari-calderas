{{--
    Tarjeta KPI — mismo patrón visual que .dash-stat-card de
    resources/views/admin/dashboard.blade.php (chip de ícono a color,
    valor grande, badge de tendencia), namespaced .stats-kpi-card para no
    heredar reglas de dashboard.css sin querer.
    $kpi: ['label','value','trend','trendUp','hint','color','icon']
--}}
@php
    $chipColors = [
        'brand' => ['rgba(249,115,22,.1)', '#ea580c'],
        'info'  => ['rgba(59,130,246,.1)', '#2563eb'],
        'ok'    => ['rgba(34,197,94,.1)', '#16a34a'],
        'violet'=> ['rgba(168,85,247,.1)', '#9333ea'],
    ];
    [$chipBg, $chipColor] = $chipColors[$kpi['color'] ?? 'brand'] ?? $chipColors['brand'];
    $trendUp = $kpi['trendUp'] ?? true;
@endphp
<div class="stats-kpi-card">
    <div class="stats-kpi-top">
        <div class="stats-kpi-text">
            <div class="stats-kpi-label">{{ $kpi['label'] }}</div>
            <div class="stats-kpi-value">{{ $kpi['value'] }}</div>
        </div>
        <div class="stats-kpi-chip" style="background:{{ $chipBg }};color:{{ $chipColor }}">
            {!! $kpi['icon'] ?? '' !!}
        </div>
    </div>
    <div class="stats-kpi-bottom">
        <span class="stats-kpi-trend {{ $trendUp ? 'is-up' : 'is-down' }}">{{ $kpi['trend'] }}</span>
        <span class="stats-kpi-hint">{{ $kpi['hint'] }}</span>
    </div>
</div>
