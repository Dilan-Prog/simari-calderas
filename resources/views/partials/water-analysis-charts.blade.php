{{--
    Gráficas del Reporte de Análisis Químico (compartido por Portal > Monitoreo y la ficha admin del cliente):
    KPIs, último registro, línea por parámetro (con mínimo/máximo punteados), pastel de estado y área apilada de cumplimiento.

    Variables:
      $analysis    (obligatoria) App\Services\WaterAnalysisInsights::forCustomer
      $periodUrls  (obligatoria) [7 => url, 30 => url, 90 => url] enlaces del selector de periodo
      $idp         prefijo de los ids del DOM (default 'ma' => maLine / maPie / maArea / maChips); único por página
      $heading, $subheading   título y descripción del encabezado
      $headActions (opcional) HTML ya escapado/confiable que se muestra junto al selector de periodo
      $emptyAction (opcional) ['url' => ..., 'label' => ...] enlace del estado vacío
--}}
@php
    $idp = $idp ?? 'ma';
    $heading = $heading ?? 'Análisis químico del agua';
    $subheading = $subheading ?? 'Resumen de los registros diarios de dureza, pH, alcalinidad, conductividad y STD.';
    $headActions = $headActions ?? null;
    $emptyAction = $emptyAction ?? null;
    $k = $analysis['kpis'];
    $statusLabel = ['ok' => 'Dentro de rango', 'bajo' => 'Bajo', 'alto' => 'Alto', 'sin_rango' => 'Sin rango definido'];
    $statusClass = ['ok' => 'ok', 'bajo' => 'low', 'alto' => 'high', 'sin_rango' => 'none'];
@endphp
@once
<style>
    /* :where() deja especificidad 0: si la página ya define ts-card / mn-* (portal), esos estilos mandan. */
    :where(.ma-card) { background: var(--ts-bg-white, #fff); border-radius: 8px; box-shadow: var(--ts-shadow-sm, 0 1px 2px rgba(0,0,0,.05)); padding: 1.5rem; margin-bottom: 1rem; }
    :where(.ma-title) { margin: 0 0 .75rem; font-size: 1rem; font-weight: 700; color: var(--ts-text-title, #111827); }
    :where(.ma-muted) { color: var(--ts-text-muted, #6B7280); font-size: .8125rem; }
    .ma-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
    .ma-head-right { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .ma-period { display: inline-flex; background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 3px; gap: 2px; }
    .ma-period a { padding: .4rem .8rem; border-radius: 8px; font-size: .8125rem; font-weight: 600; color: #4B5563; text-decoration: none; }
    .ma-period a:hover { background: #F3F4F6; }
    .ma-period a.is-active { background: #ff6213; color: #fff; }
    .ma-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .ma-kpi { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: .75rem .875rem; }
    .ma-kpi small { display: block; font-size: .6875rem; text-transform: uppercase; letter-spacing: .04em; color: #6B7280; font-weight: 600; }
    .ma-kpi strong { display: block; margin-top: .125rem; font-size: 1.25rem; color: #111827; }
    .ma-kpi strong.is-bad { color: #B91C1C; }
    .ma-latest { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
    .ma-param { background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: .75rem .875rem; border-left: 4px solid #D1D5DB; }
    .ma-param--ok { border-left-color: #16A34A; } .ma-param--low { border-left-color: #F59E0B; } .ma-param--high { border-left-color: #DC2626; }
    .ma-param small { display: block; font-size: .75rem; color: #4B5563; font-weight: 600; }
    .ma-param strong { display: block; margin: .15rem 0; font-size: 1.25rem; color: #111827; }
    .ma-param strong span { font-size: .75rem; font-weight: 500; color: #6B7280; }
    .ma-param em { display: block; font-style: normal; font-size: .75rem; color: #6B7280; }
    .ma-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1rem; margin-bottom: 1rem; }
    .ma-grid > *, .ma-box { min-width: 0; }
    .ma-box { position: relative; height: 300px; }
    .ma-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .75rem; }
    .ma-chip { border: 1px solid #E5E7EB; background: #fff; border-radius: 9999px; padding: .3rem .8rem; font-size: .8125rem; font-weight: 600; color: #4B5563; cursor: pointer; font-family: inherit; }
    .ma-chip:hover { border-color: #ff6213; color: #ff6213; }
    .ma-chip.is-active { background: #ff6213; border-color: #ff6213; color: #fff; }
    .ma-empty { text-align: center; padding: 2rem 1rem; color: #6B7280; }
    .ma-empty a { color: #ff6213; font-weight: 600; }
    @media (max-width: 1024px) { .ma-latest { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ma-grid { grid-template-columns: minmax(0, 1fr); } .ma-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 640px) { .ma-box { height: 250px; } }
</style>
@endonce

<section aria-labelledby="{{ $idp }}-title">
    <div class="ma-head">
        <div>
            <h2 class="mn-section-title ma-title" id="{{ $idp }}-title" style="margin-bottom:.15rem">{{ $heading }}</h2>
            <p class="mn-muted ma-muted" style="margin:0">{{ $subheading }}</p>
        </div>
        <div class="ma-head-right">
            {!! $headActions !!}
            <div class="ma-period" role="group" aria-label="Periodo">
                @foreach (\App\Services\WaterAnalysisInsights::PERIODS as $p)
                    <a href="{{ $periodUrls[$p] }}" class="{{ $analysis['days'] === $p ? 'is-active' : '' }}"
                       @if ($analysis['days'] === $p) aria-current="true" @endif>{{ $p }} días</a>
                @endforeach
            </div>
        </div>
    </div>

    @if (! $analysis['has_data'])
        <div class="ts-card ma-card ma-empty">
            <p style="margin:0 0 .35rem">Aún no hay registros de análisis químico en los últimos {{ $analysis['days'] }} días.</p>
            @if ($emptyAction)
                <a href="{{ $emptyAction['url'] }}">{{ $emptyAction['label'] }}</a>
            @endif
        </div>
    @else
        <div class="ma-kpis">
            <div class="ma-kpi"><small>Días con registro</small><strong>{{ $k['days_logged'] }} <span class="mn-muted ma-muted" style="font-weight:500;font-size:.8rem">de {{ $k['days_window'] }}</span></strong></div>
            <div class="ma-kpi"><small>Lecturas capturadas</small><strong>{{ $k['readings'] }}</strong></div>
            <div class="ma-kpi"><small>Cumplimiento</small><strong>{{ $k['compliance'] !== null ? $k['compliance'] . '%' : 'Sin rangos' }}</strong></div>
            <div class="ma-kpi"><small>Fuera de rango</small><strong class="{{ $k['out_of_range'] > 0 ? 'is-bad' : '' }}">{{ $k['out_of_range'] }}</strong></div>
        </div>

        @if ($analysis['latest'])
            <p class="mn-muted ma-muted" style="margin:0 0 .5rem">Último registro: <strong>{{ $analysis['latest']['date'] }}</strong></p>
            <div class="ma-latest">
                @foreach ($analysis['latest']['values'] as $v)
                    <div class="ma-param ma-param--{{ $v['status'] ? $statusClass[$v['status']] : 'none' }}">
                        <small>{{ $v['name'] }}</small>
                        <strong>{{ $v['value'] ?? '—' }} <span>{{ $v['value'] !== null ? $v['unit'] : '' }}</span></strong>
                        <em>
                            @if ($v['status'] && $v['status'] !== 'sin_rango')
                                {{ $statusLabel[$v['status']] }} · {{ $v['min'] ?? '—' }} – {{ $v['max'] ?? '—' }}
                            @elseif ($v['value'] !== null)
                                Sin rango definido
                            @else
                                Sin lectura
                            @endif
                        </em>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="ma-grid">
            <div class="ts-card ma-card">
                <h3 class="mn-section-title ma-title" style="margin-bottom:.15rem">Tendencia por parámetro</h3>
                <p class="mn-muted ma-muted" style="margin:0 0 .75rem">Línea de cada lectura; las líneas punteadas marcan el mínimo y el máximo cuando están definidos.</p>
                <div class="ma-chips" id="{{ $idp }}Chips" role="tablist" aria-label="Parámetro"></div>
                <div class="ma-box"><canvas id="{{ $idp }}Line" role="img" aria-label="Gráfica de línea de la tendencia del parámetro seleccionado"></canvas></div>
            </div>
            <div class="ts-card ma-card">
                <h3 class="mn-section-title ma-title" style="margin-bottom:.15rem">Estado de las lecturas</h3>
                <p class="mn-muted ma-muted" style="margin:0 0 .75rem">Proporción de lecturas del periodo según su estado.</p>
                <div class="ma-box"><canvas id="{{ $idp }}Pie" role="img" aria-label="Gráfica de pastel con el estado de las lecturas"></canvas></div>
            </div>
        </div>

        <div class="ts-card ma-card mn-block">
            <h3 class="mn-section-title ma-title" style="margin-bottom:.15rem">Cumplimiento diario</h3>
            <p class="mn-muted ma-muted" style="margin:0 0 .75rem">Cuántos parámetros quedaron dentro, fuera o sin rango definido en cada día registrado.</p>
            <div class="ma-box"><canvas id="{{ $idp }}Area" role="img" aria-label="Gráfica de área del cumplimiento diario"></canvas></div>
        </div>
    @endif
</section>

@if ($analysis['has_data'])
    @once
        <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    @endonce
    <script>
        (function () {
            var P = @json($idp);
            var A = @json($analysis);
            var FONT = { family: 'Inter, system-ui, sans-serif', size: 12 };
            Chart.defaults.font = FONT;
            Chart.defaults.color = '#6B7280';

            // ── Línea: un parámetro a la vez ──
            var lineChart = null;
            var chipsBox = document.getElementById(P + 'Chips');
            function fmt(v, d) { return v === null || v === undefined ? '—' : Number(v).toLocaleString('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d }); }

            function drawLine(idx) {
                var s = A.series[idx];
                var n = A.labels.length;
                var datasets = [{
                    label: s.name + (s.unit && s.unit !== '—' ? ' (' + s.unit + ')' : ''),
                    data: s.values, borderColor: '#ff6213', backgroundColor: '#ff6213', borderWidth: 2.5,
                    pointRadius: 3, pointHoverRadius: 5, tension: 0.3, spanGaps: true
                }];
                if (s.min !== null) datasets.push({ label: 'Mínimo', data: Array(n).fill(s.min), borderColor: '#F59E0B', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill: false });
                if (s.max !== null) datasets.push({ label: 'Máximo', data: Array(n).fill(s.max), borderColor: '#DC2626', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill: false });
                if (lineChart) lineChart.destroy();
                lineChart = new Chart(document.getElementById(P + 'Line'), {
                    type: 'line',
                    data: { labels: A.labels, datasets: datasets },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                            tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + fmt(c.parsed.y, s.decimals); } } } },
                        scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10, autoSkip: true } }, y: { grid: { color: '#F3F4F6' } } }
                    }
                });
                Array.prototype.forEach.call(chipsBox.children, function (c, i) { c.classList.toggle('is-active', i === idx); c.setAttribute('aria-selected', i === idx ? 'true' : 'false'); });
            }

            A.series.forEach(function (s, i) {
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'ma-chip'; b.setAttribute('role', 'tab'); b.textContent = s.name.replace(/\s*\(.*\)/, '');
                b.addEventListener('click', function () { drawLine(i); });
                chipsBox.appendChild(b);
            });
            if (A.series.length) drawLine(0);

            // ── Pastel: estado de las lecturas ──
            var D = A.distribution;
            var pieLabels = ['Dentro de rango', 'Bajo', 'Alto', 'Sin rango definido'];
            var pieData = [D.ok, D.bajo, D.alto, D.sin_rango];
            new Chart(document.getElementById(P + 'Pie'), {
                type: 'pie',
                data: { labels: pieLabels, datasets: [{ data: pieData, backgroundColor: ['#16A34A', '#F59E0B', '#DC2626', '#9CA3AF'], borderColor: '#fff', borderWidth: 2 }] },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { callbacks: { label: function (c) { var t = pieData.reduce(function (a, b) { return a + b; }, 0); return c.label + ': ' + c.parsed + ' (' + (t ? Math.round(c.parsed / t * 100) : 0) + '%)'; } } } }
                }
            });

            // ── Área apilada: cumplimiento diario ──
            new Chart(document.getElementById(P + 'Area'), {
                type: 'line',
                data: {
                    labels: A.labels,
                    datasets: [
                        { label: 'Dentro de rango', data: A.area.ok, borderColor: '#16A34A', backgroundColor: 'rgba(22,163,74,.35)', fill: true, tension: 0.3, pointRadius: 2 },
                        { label: 'Fuera de rango', data: A.area.out, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,.35)', fill: true, tension: 0.3, pointRadius: 2 },
                        { label: 'Sin rango definido', data: A.area.none, borderColor: '#9CA3AF', backgroundColor: 'rgba(156,163,175,.3)', fill: true, tension: 0.3, pointRadius: 2 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10, autoSkip: true } },
                        y: { stacked: true, beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { precision: 0 }, title: { display: true, text: 'Parámetros' } } }
                }
            });
        })();
    </script>
@endif
