import { renderChart } from './charts.js';

/**
 * JS de resources/views/admin/reports/create.blade.php.
 * Al hacer click en "Preview", envía la configuración actual del form
 * (data_source/chart_type/metric/group_by/filtros) por POST a la URL en
 * data-preview-url del <form id="report-builder-form">, y dibuja el
 * resultado en el canvas #preview-chart usando renderChart(), pasándole
 * los datos recién obtenidos directamente como objeto.
 */
(function () {
    'use strict';

    var form = document.getElementById('report-builder-form');
    var previewBtn = document.getElementById('preview-btn');
    if (!form || !previewBtn) return;

    var previewEmpty = document.getElementById('preview-empty');
    var previewCanvas = document.getElementById('preview-chart');

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var currentChart = null;

    function buildPayload() {
        var dataSource = form.querySelector('#data_source');
        var chartType = form.querySelector('#chart_type');

        var payload = {
            data_source: dataSource ? dataSource.value : null,
            chart_type: chartType ? chartType.value : null,
            config: {
                metric: null,
                group_by: null,
                filters: {},
            },
        };

        form.querySelectorAll('[data-config-target]:not(:disabled)').forEach(function (field) {
            var target = field.getAttribute('data-config-target');
            var value = field.value;

            if (target === 'metric') {
                payload.config.metric = value;
            } else if (target === 'group_by') {
                payload.config.group_by = value;
            } else if (target === 'filter_pipeline_id') {
                if (value) payload.config.filters.pipeline_id = value;
            } else if (target === 'filter_date_from') {
                if (value) payload.config.filters.date_from = value;
            } else if (target === 'filter_date_to') {
                if (value) payload.config.filters.date_to = value;
            }
        });

        return payload;
    }

    function showEmptyState(message) {
        if (previewCanvas) previewCanvas.style.display = 'none';
        if (previewEmpty) {
            previewEmpty.style.display = 'flex';
            var p = previewEmpty.querySelector('p');
            if (p && message) p.textContent = message;
        }
    }

    function showChart() {
        if (previewEmpty) previewEmpty.style.display = 'none';
        if (previewCanvas) previewCanvas.style.display = 'block';
    }

    previewBtn.addEventListener('click', function () {
        var previewUrl = form.getAttribute('data-preview-url');
        if (!previewUrl) return;

        previewBtn.disabled = true;

        fetch(previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(buildPayload()),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) {
                        var err = new Error(data.message || 'Error al generar la vista previa.');
                        err.data = data;
                        throw err;
                    }
                    return data;
                });
            })
            .then(function (result) {
                if (currentChart) {
                    currentChart.destroy();
                    currentChart = null;
                }

                showChart();

                var chartType = form.querySelector('#chart_type');

                currentChart = renderChart('preview-chart', {
                    type: (chartType ? chartType.value : null) || result.type,
                    labels: result.labels || [],
                    datasets: result.datasets || [],
                }, (chartType ? chartType.value : null) || result.type);
            })
            .catch(function () {
                showEmptyState('No se pudo generar la vista previa. Verifica la configuración e intenta de nuevo.');
            })
            .finally(function () {
                previewBtn.disabled = false;
            });
    });
})();
