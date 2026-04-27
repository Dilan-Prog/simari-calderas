@extends('admin.layouts.master')
@section('title')
    Dashboard - Admin
@endsection
@section('content')
<div class="dash-page">

    {{-- ── Header ── --}}
    <div class="dash-header">
        <h1 class="dash-title">Resumen General</h1>
        <span class="dash-timestamp">Última actualización: Hoy, 10:30 AM</span>
    </div>

    {{-- ── Stats grid ── --}}
    <div class="dash-stats-grid">

        {{-- Productos Activos --}}
        <div class="dash-stat-card">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Productos Activos</p>
                    <h3 class="dash-stat-value">3</h3>
                </div>
                <div class="dash-stat-icon blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                        <path d="M12 22V12"/>
                        <polyline points="3.29 7 12 12 20.71 7"/>
                        <path d="m7.5 4.27 9 5.15"/>
                    </svg>
                </div>
            </div>
            <span class="dash-stat-badge">+2.5%</span>
        </div>

        {{-- Artículos Blog --}}
        <div class="dash-stat-card">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Artículos Blog</p>
                    <h3 class="dash-stat-value">2</h3>
                </div>
                <div class="dash-stat-icon purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                        <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                        <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                    </svg>
                </div>
            </div>
            <span class="dash-stat-badge">+1 esta semana</span>
        </div>

        {{-- Contactos WhatsApp --}}
        <div class="dash-stat-card">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Contactos WhatsApp</p>
                    <h3 class="dash-stat-value">128</h3>
                </div>
                <div class="dash-stat-icon green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </div>
            </div>
            <span class="dash-stat-badge">+12% vs mes anterior</span>
        </div>

        {{-- Visitas Totales --}}
        <div class="dash-stat-card">
            <div class="dash-stat-card-top">
                <div>
                    <p class="dash-stat-label">Visitas Totales</p>
                    <h3 class="dash-stat-value">14.2k</h3>
                </div>
                <div class="dash-stat-icon orange">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>
                    </svg>
                </div>
            </div>
            <span class="dash-stat-badge">+5.4% este mes</span>
        </div>

    </div>

    {{-- ── Bottom grid ── --}}
    <div class="dash-bottom-grid">

        {{-- Traffic chart --}}
        <div class="dash-panel">
            <div class="dash-chart-header">
                <h3 class="dash-panel-title">Tráfico del Sitio</h3>
                <select class="dash-chart-select" id="dashChartRange">
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Último mes</option>
                    <option value="365">Este año</option>
                </select>
            </div>
            <div class="dash-chart-wrapper">
                <canvas id="dashTrafficChart"></canvas>
            </div>
        </div>

        {{-- Quick access --}}
        <div class="dash-panel dash-quick-access">
            <h3 class="dash-panel-title" style="margin-bottom:16px;">Accesos Rápidos</h3>

            <div class="dash-quick-btns">

                <button class="dash-quick-btn">
                    <div class="dash-quick-btn-left">
                        <div class="dash-quick-btn-icon blue">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                <path d="M12 22V12"/>
                                <polyline points="3.29 7 12 12 20.71 7"/>
                                <path d="m7.5 4.27 9 5.15"/>
                            </svg>
                        </div>
                        <span class="dash-quick-btn-label">Agregar Producto</span>
                    </div>
                    <svg class="dash-quick-btn-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>

                <button class="dash-quick-btn">
                    <div class="dash-quick-btn-left">
                        <div class="dash-quick-btn-icon green">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/>
                                <path d="M14 2v4a2 2 0 0 0 2 2h4"/>
                                <path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>
                            </svg>
                        </div>
                        <span class="dash-quick-btn-label">Escribir Artículo</span>
                    </div>
                    <svg class="dash-quick-btn-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>

                <button class="dash-quick-btn">
                    <div class="dash-quick-btn-left">
                        <div class="dash-quick-btn-icon purple">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <span class="dash-quick-btn-label">Configurar WhatsApp</span>
                    </div>
                    <svg class="dash-quick-btn-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </button>

            </div>

            <div class="dash-performance">
                <div class="dash-performance-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                        <polyline points="16 7 22 7 22 13"/>
                    </svg>
                    <span>Rendimiento</span>
                </div>
                <p class="dash-performance-text">
                    El sitio ha mejorado un <span class="dash-performance-highlight">12%</span> en velocidad de carga esta semana.
                </p>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    const datasets = {
        7:   [1200, 1800, 1500, 2200, 2800, 3100, 2600],
        30:  [800, 1200, 1000, 1400, 1100, 1600, 2000, 1800, 2100, 1900,
              2300, 2500, 2200, 2700, 2400, 2800, 2600, 3000, 2900, 3200,
              3100, 2800, 3300, 3500, 3200, 3600, 3400, 3700, 3500, 3900],
        365: [1800, 2200, 2800, 3200, 3600, 4000, 3800],
    };

    const labels = {
        7:   ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
        30:  Array.from({ length: 30 }, (_, i) => `Día ${i + 1}`),
        365: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
    };

    const ctx = document.getElementById('dashTrafficChart').getContext('2d');

    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0.05, 'rgba(0, 84, 255, 0.10)');
    gradient.addColorStop(0.95, 'rgba(0, 84, 255, 0.00)');

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels[365],
            datasets: [{
                data: datasets[365],
                borderColor: '#ff6213',
                borderWidth: 2,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#ff6213',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#ffffff',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    titleColor: '#1f2937',
                    bodyColor: '#6b7280',
                    padding: 10,
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y.toLocaleString() + ' visitas',
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 12, family: 'Inter, sans-serif' },
                    },
                },
                y: {
                    grid: { color: '#f3f4f6' },
                    border: { display: false, dash: [4, 4] },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 12, family: 'Inter, sans-serif' },
                        callback: v => v >= 1000 ? (v / 1000) + 'k' : v,
                    },
                    beginAtZero: true,
                },
            },
        },
    });

    document.getElementById('dashChartRange').addEventListener('change', function () {
        const range = this.value;
        chart.data.labels = labels[range];
        chart.data.datasets[0].data = datasets[range];
        chart.update();
    });
})();
</script>
@endpush
