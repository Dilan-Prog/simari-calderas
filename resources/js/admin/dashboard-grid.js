import Sortable from 'sortablejs';
import { renderChart } from './charts.js';

/**
 * JS compartido por resources/views/admin/reports/show.blade.php (y sus
 * prebuilt/*.blade.php, mismo patrón) y admin/dashboards/show.blade.php,
 * admin/dashboards/edit.blade.php.
 *
 * - reports/show.blade.php y reports/prebuilt/*: un único canvas
 *   #report-chart + datos embebidos en el <script type="application/json">
 *   #report-data.
 * - dashboards/show.blade.php: múltiples widgets, cada uno con canvas
 *   #dashboard-chart-{reportId} y datos embebidos en
 *   #dashboard-data-{reportId}.
 * - dashboards/edit.blade.php: no dibuja gráficos (solo tarjetas para
 *   reordenar), por lo que las búsquedas de canvas simplemente no
 *   encuentran nada ahí; si en el futuro se agregan widgets con
 *   data-report-id para reordenar, se inicializa SortableJS con un fetch
 *   a la ruta de reorderReports (mismo comportamiento que
 *   dashboard-editor.js, que ya cubre #dashboardReportsGrid).
 */
(function () {
    'use strict';

    // ── reports/show.blade.php y reports/prebuilt/*.blade.php ──────────
    var singleReportCanvas = document.getElementById('report-chart');
    if (singleReportCanvas && document.getElementById('report-data')) {
        renderChart('report-chart', 'report-data');
    }

    // ── dashboards/show.blade.php ───────────────────────────────────────
    document.querySelectorAll('.dashboard-widget[data-report-id]').forEach(function (widget) {
        var reportId = widget.getAttribute('data-report-id');
        var canvas = document.getElementById('dashboard-chart-' + reportId);
        var dataEl = document.getElementById('dashboard-data-' + reportId);
        if (!canvas || !dataEl) return;

        var chartType = canvas.dataset.chartType || undefined;
        renderChart('dashboard-chart-' + reportId, 'dashboard-data-' + reportId, chartType);
    });

    // ── dashboards/edit.blade.php: reordenar widgets con SortableJS ────
    // Grid genérico opcional (data-report-id + data-reorder-url) distinto
    // de #dashboardReportsGrid, por si alguna vista futura reutiliza este
    // patrón fuera del editor dedicado.
    var reorderGrid = document.querySelector('[data-reorder-url]:not(#dashboardReportsGrid)');
    if (reorderGrid) {
        var reorderUrl = reorderGrid.getAttribute('data-reorder-url');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        new Sortable(reorderGrid, {
            animation: 150,
            draggable: '[data-report-id]',
            onEnd: function () {
                var reportIds = Array.prototype.map.call(
                    reorderGrid.querySelectorAll('[data-report-id]'),
                    function (widget) { return widget.getAttribute('data-report-id'); }
                );

                fetch(reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ report_ids: reportIds }),
                }).catch(function () {});
            },
        });
    }
})();
