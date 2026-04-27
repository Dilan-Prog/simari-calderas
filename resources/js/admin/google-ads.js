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

    let searchTimer;
    $('#gaSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.search($(this).val()).draw(), 400);
    });

    $('#gaDateFrom, #gaDateTo').on('change', function () {
        table.ajax.reload();
    });

    $('#gaClearFilters').on('click', function () {
        $('#gaSearch').val('');
        $('#gaDateFrom').val('');
        $('#gaDateTo').val('');
        table.search('').draw();
    });
});
