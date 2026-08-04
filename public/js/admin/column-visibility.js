/**
 * Mecanismo genérico "mostrar/ocultar columnas" para las tablas de listado
 * del admin, con persistencia por usuario en el servidor (en vez de
 * localStorage). Vanilla JS plano, sin build step — se sirve directo desde
 * public/js/admin/, igual que public/js/quotes.js.
 *
 * Uso (por cada vista que incluya el partial
 * admin.components._column_visibility_menu):
 *
 *   initColumnVisibility({
 *     tableKey: 'clients.index',
 *     savedColumns: @json($visibleColumns), // array de claves o null
 *     saveUrl: '{{ route('admin.column-preferences.update') }}',
 *   });
 *
 * applyFn es opcional: por defecto se aplica/oculta vía la clase CSS
 * .col-hidden sobre document.querySelectorAll('[data-col="KEY"]') (cubre
 * tanto <th> como <td>, y tablas con cabecera/cuerpo separados). Google Ads
 * (la única tabla DataTables real del admin) debe pasar su propio applyFn
 * que use column().visible() en vez del toggle de clase CSS.
 */
function initColumnVisibility(options) {
    'use strict';

    options = options || {};
    var tableKey = options.tableKey;
    var savedColumns = options.savedColumns || null;
    var saveUrl = options.saveUrl;
    var applyFn = typeof options.applyFn === 'function' ? options.applyFn : null;

    if (!tableKey || !saveUrl) {
        return;
    }

    var wrap = document.querySelector('.col-vis-wrap[data-table-key="' + tableKey + '"]');
    if (!wrap) {
        return;
    }

    var btn = wrap.querySelector('.col-vis-btn');
    var menu = wrap.querySelector('.col-vis-menu');
    var checkboxes = wrap.querySelectorAll('.col-vis-checkbox');

    function defaultApply(colKey, visible) {
        var els = document.querySelectorAll('[data-col="' + colKey + '"]');
        els.forEach(function (el) {
            el.classList.toggle('col-hidden', !visible);
        });
    }

    function apply(colKey, visible) {
        if (applyFn) {
            applyFn(colKey, visible);
        } else {
            defaultApply(colKey, visible);
        }
    }

    function applyAll() {
        checkboxes.forEach(function (cb) {
            apply(cb.dataset.colKey, cb.checked);
        });
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function save() {
        var visible = [];
        checkboxes.forEach(function (cb) {
            if (cb.checked) {
                visible.push(cb.dataset.colKey);
            }
        });

        fetch(saveUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                table_key: tableKey,
                columns: visible,
            }),
        }).catch(function () {
            // Guardado falló silenciosamente: la vista actual ya refleja
            // la elección del usuario, solo no persiste para la próxima
            // carga. No es un error fatal para la interacción en curso.
        });
    }

    // Aplica la preferencia guardada del servidor (si existe) destildando
    // los checkboxes cuya clave no esté en savedColumns.
    if (savedColumns !== null) {
        checkboxes.forEach(function (cb) {
            cb.checked = savedColumns.indexOf(cb.dataset.colKey) !== -1;
        });
    }

    applyAll();

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            apply(cb.dataset.colKey, cb.checked);
            save();
        });
    });

    if (btn && menu) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', function (e) {
            if (menu.style.display !== 'none' && !menu.contains(e.target) && e.target !== btn) {
                menu.style.display = 'none';
            }
        });
    }
}
