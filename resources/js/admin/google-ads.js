import $ from 'jquery';
import JSZip from 'jszip';
import 'datatables.net';
import 'datatables.net-buttons/js/dataTables.buttons.mjs';
import 'datatables.net-buttons/js/buttons.html5.mjs';



window.$ = $;
window.jQuery = $;
window.JSZip = JSZip;

$(function () {
    const table = $('#gaTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        language: {
            processing:   'Cargando…',
            search:       'Buscar:',
            lengthMenu:   'Mostrar _MENU_ registros',
            info:         'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty:    'Mostrando 0 registros',
            infoFiltered: '(filtrado de _MAX_ total)',
            paginate: {
                first:    '«',
                last:     '»',
                next:     '›',
                previous: '‹',
            },
            emptyTable:  'No hay conversiones registradas',
            zeroRecords: 'Sin resultados para la búsqueda',
        },
        ajax: {
            url: window.gaTableUrl,
            type: 'GET',
            data: function (d) {
                d.date_from = $('#gaDateFrom').val();
                d.date_to   = $('#gaDateTo').val();
            },
        },
        columns: [
            { data: 0, width: '50px' },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4, width: '70px' },
            { data: 5 },
            { data: 6, orderable: false },
            { data: 7 },
            { data: 8 },
            { data: 9, orderable: false, searchable: false, width: '80px' },
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        layout: {
            topStart:    'pageLength',
            topEnd:      null,
            bottomStart: 'info',
            bottomEnd:   'paging',
        },
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Exportar CSV',
                className: 'dt-button',
                title: 'google-ads-conversiones',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
            },
        ],
    });

    if (typeof window.initColumnVisibility === 'function') {
        const gaTableEl = document.getElementById('gaTable');
        const columnIndexByKey = {
            gclid: 1,
            conversion: 2,
            valor: 3,
            moneda: 4,
            orden_id: 5,
            estado: 6,
            tiempo: 7,
            creado: 8,
        };
        let savedColumns = null;
        try {
            savedColumns = JSON.parse(gaTableEl.dataset.savedColumns);
        } catch (e) {
            savedColumns = null;
        }

        window.initColumnVisibility({
            tableKey: 'google-ads.index',
            savedColumns: savedColumns,
            saveUrl: gaTableEl.dataset.saveColumnsUrl,
            applyFn: function (colKey, visible) {
                table.column(columnIndexByKey[colKey]).visible(visible);
            },
        });
    }

    new $.fn.dataTable.Buttons(table, {
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Exportar CSV',
                className: 'dt-button',
                title: 'google-ads-conversiones',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] },
            },
        ],
    }).container().appendTo('#gaExportWrapper');

    // Mantiene el href del botón "Exportar todo (rango filtrado)" (export
    // real server-side, todas las filas del rango -- no solo la página
    // actual) sincronizado con los filtros de fecha/búsqueda vigentes.
    function updateServerExportLink() {
        const btn = document.getElementById('gaExportServerBtn');
        if (!btn) return;

        const params = new URLSearchParams();
        const from = $('#gaDateFrom').val();
        const to = $('#gaDateTo').val();
        const search = $('#gaSearch').val();
        if (from) params.set('date_from', from);
        if (to) params.set('date_to', to);
        if (search) params.set('search', search);

        const query = params.toString();
        btn.setAttribute('href', btn.dataset.baseUrl + (query ? '?' + query : ''));
    }

    let searchTimer;
    $('#gaSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            table.search($(this).val()).draw();
            updateServerExportLink();
        }, 400);
    });

    $('#gaDateFrom, #gaDateTo').on('change', function () {
        table.ajax.reload();
        updateServerExportLink();
    });

    $('#gaClearFilters').on('click', function () {
        $('#gaSearch').val('');
        $('#gaDateFrom').val('');
        $('#gaDateTo').val('');
        table.search('').draw();
        updateServerExportLink();
    });

    updateServerExportLink();
});
