import Chart from 'chart.js/auto';

/**
 * Módulo vanilla JS reusable para inicializar gráficos de Chart.js en el
 * panel admin (Reportes / Dashboards / Metas del CRM).
 *
 * renderChart(canvasId, dataOrScriptId, chartType)
 *   - canvasId: id del elemento <canvas> donde se dibuja el gráfico.
 *   - dataOrScriptId: si es un string, se trata como el id de un
 *     <script type="application/json"> embebido en la vista y se hace
 *     JSON.parse() de su contenido; si es un objeto, se usa directo como
 *     el `data` de Chart.js ({ labels, datasets }).
 *   - chartType: tipo de gráfico de Chart.js ('bar', 'line', 'pie',
 *     'doughnut', ...). Opcional si los datos embebidos ya traen un
 *     campo `type` (ver admin.reports.show / prebuilt).
 *
 * Usa chart.js/auto para no tener que registrar manualmente cada
 * controlador/elemento/escala.
 */
export function renderChart(canvasId, dataOrScriptId, chartType) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    var chartData = dataOrScriptId;

    if (typeof dataOrScriptId === 'string') {
        var scriptEl = document.getElementById(dataOrScriptId);
        if (!scriptEl) return null;

        try {
            chartData = JSON.parse(scriptEl.textContent);
        } catch (e) {
            return null;
        }
    }

    chartData = chartData || {};

    var type = chartType || chartData.type || 'bar';

    var config = {
        type: type,
        data: {
            labels: chartData.labels || [],
            datasets: chartData.datasets || [],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: (chartData.datasets || []).length > 1 || type === 'pie' || type === 'doughnut',
                },
            },
        },
    };

    return new Chart(canvas, config);
}

export default renderChart;
