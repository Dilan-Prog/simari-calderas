import Sortable from 'sortablejs';

(function () {
    'use strict';

    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function jsonFetch(url, options) {
        options = options || {};
        options.headers = Object.assign(
            {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            options.headers || {}
        );
        return fetch(url, options).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) {
                    var err = new Error(data.message || 'Error');
                    err.data = data;
                    throw err;
                }
                return data;
            });
        });
    }

    function showFeedback(message, isError) {
        var el = document.getElementById('dashboard-feedback');
        if (!el) return;
        el.textContent = message;
        el.style.color = isError ? '#DC2626' : '#16A34A';
        window.clearTimeout(showFeedback._t);
        showFeedback._t = window.setTimeout(function () {
            el.textContent = '';
        }, 3000);
    }

    // ── Grid de reportes: reordenar (SortableJS) + quitar ────────────────
    var grid = document.getElementById('dashboardReportsGrid');

    if (grid) {
        var reorderUrl = grid.dataset.reorderUrl || '';
        var removeUrlTemplate = grid.dataset.removeUrlTemplate || '';

        new Sortable(grid, {
            animation: 150,
            draggable: '.dashboard-widget',
            onEnd: function () {
                var reportIds = Array.prototype.map.call(
                    grid.querySelectorAll('.dashboard-widget[data-report-id]'),
                    function (widget) { return widget.getAttribute('data-report-id'); }
                );

                if (!reorderUrl) return;

                jsonFetch(reorderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ report_ids: reportIds }),
                })
                    .then(function () { showFeedback('Orden actualizado.', false); })
                    .catch(function () { showFeedback('No se pudo actualizar el orden.', true); });
            },
        });

        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.dashboard-widget-remove');
            if (!btn) return;

            var reportId = btn.getAttribute('data-report-id');
            if (!reportId || !removeUrlTemplate) return;
            if (!confirm('¿Quitar este reporte del dashboard?')) return;

            var url = removeUrlTemplate.replace('__REPORT_ID__', reportId);

            // POST + X-HTTP-METHOD-OVERRIDE en vez de DELETE nativo: algunos
            // hostings compartidos bloquean el verbo DELETE real (405) antes
            // de que la petición llegue a Laravel.
            jsonFetch(url, { method: 'POST', headers: { 'X-HTTP-METHOD-OVERRIDE': 'DELETE' } })
                .then(function () {
                    var widget = grid.querySelector('.dashboard-widget[data-report-id="' + reportId + '"]');
                    if (widget) widget.remove();
                    showFeedback('Reporte quitado del dashboard.', false);

                    // Re-habilita el reporte en el select de "agregar reporte existente".
                    var select = document.getElementById('report_id');
                    if (select) {
                        var label = widget ? widget.querySelector('div > div').textContent : '';
                        var option = document.createElement('option');
                        option.value = reportId;
                        option.textContent = label;
                        select.appendChild(option);
                        var addBtn = document.querySelector('#add-report-form button[type="submit"]');
                        if (addBtn) addBtn.disabled = false;
                    }
                })
                .catch(function () { showFeedback('No se pudo quitar el reporte.', true); });
        });
    }

    // ── Agregar reporte existente ─────────────────────────────────────────
    var addForm = document.getElementById('add-report-form');

    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var select = document.getElementById('report_id');
            var reportId = select ? select.value : '';
            if (!reportId) {
                showFeedback('Selecciona un reporte primero.', true);
                return;
            }

            jsonFetch(addForm.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_id: reportId }),
            })
                .then(function () {
                    showFeedback('Reporte agregado. Recarga la página para verlo en el tablero.', false);
                    window.location.reload();
                })
                .catch(function (err) {
                    showFeedback((err.data && err.data.message) || 'No se pudo agregar el reporte.', true);
                });
        });
    }

    // ── Compartir con (multi-select de usuarios) ──────────────────────────
    var shareForm = document.getElementById('share-form');

    if (shareForm) {
        shareForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var select = document.getElementById('share-users');
            var userIds = select
                ? Array.prototype.map.call(select.selectedOptions, function (opt) { return opt.value; })
                : [];

            jsonFetch(shareForm.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_ids: userIds }),
            })
                .then(function () { showFeedback('Acceso actualizado.', false); })
                .catch(function () { showFeedback('No se pudo actualizar el acceso.', true); });
        });
    }
})();
