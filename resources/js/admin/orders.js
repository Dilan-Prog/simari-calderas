/**
 * Módulo Órdenes (admin) — interactividad del modal compartido de cambio
 * de estatus (index y show comparten el mismo overlay vía
 * admin.orders.partials._status_modal), copiar datos de CFDI al
 * portapapeles, e inicialización del menú de columnas visibles.
 *
 * FLOW/META/MOTIVOS de abajo son copias EXACTAS de las de
 * app/Support/StoreOrderStatus.php — solo para pintar (label/color/
 * descripción) y para decidir si una opción es "retroceso" comparando
 * índices en FLOW. El JS nunca decide qué destinos son válidos: eso
 * siempre viene ya calculado server-side en data-allowed
 * (StoreOrderStatus::allowedTransitions()).
 */
(function () {
    'use strict';

    var FLOW = ['pendiente_pago', 'pagado', 'en_preparacion', 'enviado', 'entregado'];

    var META = {
        pendiente_pago: { label: 'Pendiente de pago', color: '#6b7280', bg: '#f1f2f4', description: 'Orden creada en el checkout; el pago aún no se confirma.' },
        pagado: { label: 'Pagado', color: '#0f6fbd', bg: '#e8f2fb', description: 'Pago verificado. Lista para preparar en almacén.' },
        en_preparacion: { label: 'En preparación', color: '#b45309', bg: '#fef3c7', description: 'Almacén surtiendo y empacando las partidas.' },
        enviado: { label: 'Enviado', color: '#6d28d9', bg: '#f1ebfd', description: 'Entregada a paquetería. Se enlazará con el módulo de Envíos.' },
        entregado: { label: 'Entregado', color: '#0f7a4f', bg: '#e6f6ee', description: 'Confirmada la recepción por el cliente. Ciclo cerrado.' },
        cancelado: { label: 'Cancelado', color: '#c81e1e', bg: '#fdecec', description: 'Cancelada por el cliente o por el equipo. Rama terminal.' },
        reembolsado: { label: 'Reembolsado', color: '#0e7490', bg: '#e3f4f7', description: 'Pago devuelto al cliente después de cancelar.' },
    };

    var MOTIVOS = {
        sin_pago: 'Pago no recibido en el plazo',
        cliente: 'Cancelada a petición del cliente',
        stock: 'Sin existencias / producto descontinuado',
        datos: 'Datos de envío o facturación incorrectos',
        duplicada: 'Orden duplicada',
        fraude: 'Sospecha de fraude',
    };

    // Estado de la selección vigente dentro del modal (se resetea cada vez
    // que se abre desde un trigger distinto).
    var pick = { status: null };

    function isBackward(currentStatus, toStatus) {
        var fromIndex = FLOW.indexOf(currentStatus);
        var toIndex = FLOW.indexOf(toStatus);
        if (fromIndex === -1 || toIndex === -1) return false; // 'cancelado'/'reembolsado' como origen: nunca es retroceso
        return toIndex < fromIndex;
    }

    // ---- Modal de cambio de estatus -----------------------------------

    function openStatusModal(trigger) {
        var orderId = trigger.dataset.orderId;
        var folio = trigger.dataset.folio;
        var currentStatus = trigger.dataset.currentStatus;
        var updateUrl = trigger.dataset.updateUrl;
        var allowed;
        try {
            allowed = JSON.parse(trigger.dataset.allowed || '[]');
        } catch (e) {
            allowed = [];
        }

        pick.status = null;
        pick.currentStatus = currentStatus;

        var overlay = document.getElementById('orderStatusModalOverlay');
        var form = document.getElementById('orderStatusForm');
        if (!overlay || !form) return;

        form.action = updateUrl;
        document.getElementById('orderStatusPick').value = '';

        var folioEl = document.getElementById('orderStatusModalFolio');
        if (folioEl) folioEl.textContent = folio;

        var currentEl = document.getElementById('orderStatusModalCurrent');
        if (currentEl) {
            var currentMeta = META[currentStatus] || {};
            currentEl.textContent = currentMeta.label || currentStatus;
            currentEl.style.color = currentMeta.color || '';
        }

        buildOptionsList(allowed, currentStatus);
        resetMotivoSelect();
        resetNoteField(false);
        updateApplyBtn();

        overlay.classList.add('is-open');
    }

    function buildOptionsList(allowed, currentStatus) {
        var list = document.getElementById('orderStatusOptionsList');
        if (!list) return;
        list.innerHTML = '';

        allowed.forEach(function (statusKey) {
            var meta = META[statusKey];
            if (!meta) return; // clave desconocida: nunca debería pasar, se ignora en vez de romper el modal

            var option = document.createElement('div');
            option.className = 'orders-status-option';
            option.dataset.status = statusKey;
            option.innerHTML =
                '<span class="orders-status-option-radio"></span>' +
                '<span class="orders-status-pill" style="color:' + meta.color + ';background:' + meta.bg + '">' + escapeHtml(meta.label) + '</span>' +
                '<span class="orders-status-option-desc">' + escapeHtml(meta.description) + '</span>';

            option.addEventListener('click', function () {
                selectOption(statusKey, currentStatus);
            });

            list.appendChild(option);
        });
    }

    function selectOption(statusKey, currentStatus) {
        pick.status = statusKey;
        document.getElementById('orderStatusPick').value = statusKey;

        document.querySelectorAll('#orderStatusOptionsList .orders-status-option').forEach(function (el) {
            el.classList.toggle('is-selected', el.dataset.status === statusKey);
        });

        toggleMotivoWrap(statusKey === 'cancelado');
        updateNoteLabel(isBackward(currentStatus, statusKey));
        updateApplyBtn();
    }

    function toggleMotivoWrap(show) {
        var wrap = document.getElementById('orderStatusMotivoWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';

        var select = document.getElementById('orderStatusMotivo');
        if (show && select) {
            populateMotivoSelect(select);
        }
    }

    function populateMotivoSelect(select) {
        if (select.dataset.populated === '1') return; // ya armado, no reconstruir en cada click
        select.innerHTML = '<option value="">Selecciona un motivo…</option>';
        Object.keys(MOTIVOS).forEach(function (key) {
            var opt = document.createElement('option');
            opt.value = key;
            opt.textContent = MOTIVOS[key];
            select.appendChild(opt);
        });
        select.dataset.populated = '1';
    }

    function resetMotivoSelect() {
        var wrap = document.getElementById('orderStatusMotivoWrap');
        if (wrap) wrap.style.display = 'none';
        var select = document.getElementById('orderStatusMotivo');
        if (select) {
            select.value = '';
            select.dataset.populated = '';
        }
    }

    function updateNoteLabel(backward) {
        var label = document.getElementById('orderStatusNoteLabel');
        var hint = document.getElementById('orderStatusNoteHint');
        var textarea = document.getElementById('orderStatusNote');

        if (label) label.textContent = backward ? 'Nota interna (obligatoria al retroceder) *' : 'Nota interna (opcional)';
        if (hint) hint.style.display = backward ? '' : 'none';
        if (textarea) textarea.classList.toggle('is-required', backward);
    }

    function resetNoteField(backward) {
        updateNoteLabel(backward);
        var textarea = document.getElementById('orderStatusNote');
        if (textarea) textarea.value = '';
    }

    function updateApplyBtn() {
        var applyBtn = document.getElementById('orderStatusApplyBtn');
        if (!applyBtn) return;

        if (!pick.status) {
            applyBtn.disabled = true;
            return;
        }

        if (pick.status === 'cancelado') {
            var motivoSelect = document.getElementById('orderStatusMotivo');
            if (!motivoSelect || !motivoSelect.value) {
                applyBtn.disabled = true;
                return;
            }
        }

        if (isBackward(pick.currentStatus, pick.status)) {
            var note = document.getElementById('orderStatusNote');
            if (!note || !note.value.trim()) {
                applyBtn.disabled = true;
                return;
            }
        }

        applyBtn.disabled = false;
    }

    function closeStatusModal() {
        var overlay = document.getElementById('orderStatusModalOverlay');
        if (overlay) overlay.classList.remove('is-open');
        pick.status = null;
        pick.currentStatus = null;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str === undefined || str === null ? '' : String(str);
        return div.innerHTML;
    }

    // ---- Copiar datos CFDI ---------------------------------------------

    function copyCfdi(btn) {
        var text = btn.dataset.copyText || '';
        if (!navigator.clipboard || !text) return;

        navigator.clipboard.writeText(text).then(function () {
            var original = btn.textContent;
            btn.textContent = '¡Copiado!';
            setTimeout(function () { btn.textContent = original; }, 1500);
        });
    }

    // ---- Menú de columnas visibles --------------------------------------

    // orders.js es un entry de Vite (sin acceso a Blade), así que no puede
    // llamar a initColumnVisibility() inline con @json()/route() como hacen
    // la mayoría de los listados (ver sales-orders/index.blade.php) — el
    // único precedente de invocar este mecanismo compartido desde un módulo
    // JS bundleado es resources/js/admin/google-ads.js, que lee su config
    // desde data-attributes del DOM en vez de recibirla por Blade. Aquí se
    // reutiliza ese mismo puente: el wrap del menú (admin.components.
    // _column_visibility_menu) ya trae data-table-key; savedColumns/saveUrl
    // se leen de data-saved-columns/data-save-columns-url sobre ese mismo
    // elemento.
    function initOrdersColumnVisibility() {
        if (typeof window.initColumnVisibility !== 'function') return;

        var wrap = document.querySelector('.col-vis-wrap[data-table-key="orders.index"]');
        if (!wrap) return;

        var savedColumns = null;
        try {
            savedColumns = JSON.parse(wrap.dataset.savedColumns || 'null');
        } catch (e) {
            savedColumns = null;
        }

        window.initColumnVisibility({
            tableKey: 'orders.index',
            savedColumns: savedColumns,
            saveUrl: wrap.dataset.saveColumnsUrl,
        });
    }

    // ---- Bootstrap -------------------------------------------------------

    function init() {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.js-open-status-modal');
            if (trigger) {
                openStatusModal(trigger);
                return;
            }

            var copyBtn = e.target.closest('.js-copy-cfdi');
            if (copyBtn) {
                copyCfdi(copyBtn);
                return;
            }

            if (e.target.id === 'orderStatusCancelBtn') {
                closeStatusModal();
                return;
            }

            if (e.target.id === 'orderStatusModalOverlay') {
                closeStatusModal();
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target.id === 'orderStatusMotivo' || e.target.id === 'orderStatusNote') {
                updateApplyBtn();
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.id === 'orderStatusNote') {
                updateApplyBtn();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeStatusModal();
        });

        initOrdersColumnVisibility();
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
