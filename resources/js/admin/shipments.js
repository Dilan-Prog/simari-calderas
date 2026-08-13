/**
 * Módulo Envíos (admin) — interactividad de:
 *   (a) modal compartido de cambio de estatus de un Shipment (incluido desde
 *       admin.orders.partials._shipment_status_modal, disparado por el botón
 *       .js-open-shipment-status-modal de _shipment_card.blade.php);
 *   (b) formulario de registro de envío embebido en orders/show.blade.php
 *       (mostrar/ocultar, validación cosmética de la guía, preview del link
 *       de rastreo);
 *   (c) copiar guía al portapapeles;
 *   (d) menú de columnas visibles en shipments/index.blade.php.
 *
 * META/MOTIVOS de abajo son copias EXACTAS de las de
 * app/Support/ShipmentStatus.php — solo para pintar (label/color/
 * descripción). El JS nunca decide qué destinos son válidos: eso siempre
 * viene ya calculado server-side en data-allowed
 * (ShipmentStatus::allowedTransitions()).
 */
(function () {
    'use strict';

    var META = {
        preparando: { label: 'Preparando', color: '#b45309', bg: '#fef3c7', description: 'Guía generada; el paquete aún está en almacén.' },
        enviado: { label: 'Enviado', color: '#0f6fbd', bg: '#e8f2fb', description: 'Recolectado por la paquetería.' },
        en_transito: { label: 'En tránsito', color: '#6d28d9', bg: '#f1ebfd', description: 'En ruta hacia el domicilio del cliente.' },
        entregado: { label: 'Entregado', color: '#0f7a4f', bg: '#e6f6ee', description: 'Recibido y confirmado en destino.' },
        incidencia: { label: 'Incidencia', color: '#c81e1e', bg: '#fdecec', description: 'Retraso, domicilio incorrecto, daño o devolución.' },
    };

    // Estado de la selección vigente dentro del modal (se resetea cada vez
    // que se abre).
    var pick = { status: null };

    // ---- Modal de cambio de estatus -------------------------------------

    function openShipmentStatusModal(trigger) {
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

        var overlay = document.getElementById('shipmentStatusModalOverlay');
        var form = document.getElementById('shipmentStatusForm');
        if (!overlay || !form) return;

        form.action = updateUrl;
        document.getElementById('shipmentStatusPick').value = '';

        var currentEl = document.getElementById('shipmentStatusModalCurrent');
        if (currentEl) {
            var currentMeta = META[currentStatus] || {};
            currentEl.textContent = currentMeta.label || currentStatus;
            currentEl.style.color = currentMeta.color || '';
        }

        buildOptionsList(allowed);
        resetMotivoSelect();
        resetNoteField();
        toggleIncidenciaWarning(false);
        updateApplyBtn();

        overlay.classList.add('is-open');
    }

    function buildOptionsList(allowed) {
        var list = document.getElementById('shipmentStatusOptionsList');
        if (!list) return;
        list.innerHTML = '';

        allowed.forEach(function (statusKey) {
            var meta = META[statusKey];
            if (!meta) return; // clave desconocida: se ignora en vez de romper el modal

            var option = document.createElement('div');
            option.className = 'orders-status-option';
            option.dataset.status = statusKey;
            option.innerHTML =
                '<span class="orders-status-option-radio"></span>' +
                '<span class="orders-status-pill" style="color:' + meta.color + ';background:' + meta.bg + '">' + escapeHtml(meta.label) + '</span>' +
                '<span class="orders-status-option-desc">' + escapeHtml(meta.description) + '</span>';

            option.addEventListener('click', function () {
                selectOption(statusKey);
            });

            list.appendChild(option);
        });
    }

    function selectOption(statusKey) {
        pick.status = statusKey;
        document.getElementById('shipmentStatusPick').value = statusKey;

        document.querySelectorAll('#shipmentStatusOptionsList .orders-status-option').forEach(function (el) {
            el.classList.toggle('is-selected', el.dataset.status === statusKey);
        });

        var isIncidencia = statusKey === 'incidencia';
        toggleMotivoWrap(isIncidencia);
        toggleIncidenciaWarning(isIncidencia);
        updateNoteLabel(isIncidencia);
        updateApplyBtn();
    }

    function toggleMotivoWrap(show) {
        var wrap = document.getElementById('shipmentMotivoWrap');
        if (!wrap) return;
        wrap.style.display = show ? '' : 'none';
    }

    function toggleIncidenciaWarning(show) {
        var warning = document.getElementById('shipmentIncidenciaWarning');
        if (warning) warning.style.display = show ? '' : 'none';
    }

    function resetMotivoSelect() {
        toggleMotivoWrap(false);
        var select = document.getElementById('shipmentMotivo');
        if (select) select.value = '';
    }

    function updateNoteLabel(isIncidencia) {
        var label = document.getElementById('shipmentStatusNoteLabel');
        if (label) {
            label.textContent = isIncidencia
                ? 'Detalle de la incidencia (obligatorio) *'
                : 'Nota de seguimiento (opcional)';
        }
    }

    function resetNoteField() {
        updateNoteLabel(false);
        var textarea = document.getElementById('shipmentStatusNote');
        if (textarea) textarea.value = '';
    }

    function updateApplyBtn() {
        var applyBtn = document.getElementById('shipmentStatusApplyBtn');
        if (!applyBtn) return;

        if (!pick.status) {
            applyBtn.disabled = true;
            return;
        }

        if (pick.status === 'incidencia') {
            var motivoSelect = document.getElementById('shipmentMotivo');
            var note = document.getElementById('shipmentStatusNote');
            if (!motivoSelect || !motivoSelect.value || !note || !note.value.trim()) {
                applyBtn.disabled = true;
                return;
            }
        }

        applyBtn.disabled = false;
    }

    function closeShipmentStatusModal() {
        var overlay = document.getElementById('shipmentStatusModalOverlay');
        if (overlay) overlay.classList.remove('is-open');
        pick.status = null;
        pick.currentStatus = null;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str === undefined || str === null ? '' : String(str);
        return div.innerHTML;
    }

    // ---- Formulario de registro de envío --------------------------------

    function toggleShipmentForm() {
        var form = document.getElementById('shipmentRegisterForm');
        var empty = document.getElementById('shipmentEmptyState');
        if (form) form.hidden = false;
        if (empty) empty.hidden = true;
    }

    function hideShipmentForm() {
        var form = document.getElementById('shipmentRegisterForm');
        var empty = document.getElementById('shipmentEmptyState');
        if (form) form.hidden = true;
        if (empty) empty.hidden = false;
    }

    // Gating puramente cosmético: el servidor siempre revalida guía/carrier
    // al procesar admin.shipments.store.
    function handleGuiaInput(input) {
        var raw = input.value.replace(/\s+/g, '');
        if (raw !== input.value) input.value = raw;

        var hint = document.getElementById('shipmentFormGuiaHint');
        var submitBtn = document.getElementById('shipmentFormSubmitBtn');
        var isValid = raw.length >= 8;

        if (hint) {
            hint.textContent = isValid
                ? 'Guía lista para guardar.'
                : 'Ingresa la guía proporcionada por la paquetería (mínimo 8 caracteres).';
            hint.classList.toggle('is-valid', isValid);
            hint.classList.toggle('is-invalid', raw.length > 0 && !isValid);
        }

        if (submitBtn) submitBtn.disabled = !isValid;

        updateLinkPreview();
    }

    function updateLinkPreview() {
        var preview = document.getElementById('shipmentFormLinkPreview');
        var carrierSelect = document.getElementById('shipmentFormCarrier');
        var guiaInput = document.getElementById('shipmentFormGuia');
        if (!preview || !carrierSelect || !guiaInput) return;

        var selectedOption = carrierSelect.options[carrierSelect.selectedIndex];
        var urlTemplate = selectedOption ? selectedOption.dataset.url : '';
        var guia = guiaInput.value.trim();

        if (!urlTemplate || !guia) {
            preview.innerHTML = '';
            return;
        }

        var fullUrl = urlTemplate + guia;
        preview.innerHTML = 'Se enlazará a: <a href="' + fullUrl + '" target="_blank" rel="noopener">' + escapeHtml(fullUrl) + '</a>';
    }

    // ---- Copiar guía al portapapeles -------------------------------------

    function copyGuia(btn) {
        var text = btn.dataset.guia || '';
        if (!navigator.clipboard || !text) return;

        navigator.clipboard.writeText(text).then(function () {
            var original = btn.textContent;
            btn.textContent = '¡Copiado!';
            setTimeout(function () { btn.textContent = original; }, 1500);
        });
    }

    // ---- Menú de columnas visibles (shipments/index.blade.php) ----------

    // Mismo puente que orders.js: shipments.js es un entry de Vite (sin
    // acceso a Blade), así que lee su config desde data-attributes del wrap
    // del menú en vez de recibirla inline con @json()/route().
    function initShipmentsColumnVisibility() {
        if (typeof window.initColumnVisibility !== 'function') return;

        var wrap = document.querySelector('.col-vis-wrap[data-table-key="shipments.index"]');
        if (!wrap) return;

        var savedColumns = null;
        try {
            savedColumns = JSON.parse(wrap.dataset.savedColumns || 'null');
        } catch (e) {
            savedColumns = null;
        }

        window.initColumnVisibility({
            tableKey: 'shipments.index',
            savedColumns: savedColumns,
            saveUrl: wrap.dataset.saveColumnsUrl,
        });
    }

    // ---- Bootstrap -------------------------------------------------------

    function init() {
        document.addEventListener('click', function (e) {
            var statusTrigger = e.target.closest('.js-open-shipment-status-modal');
            if (statusTrigger) {
                openShipmentStatusModal(statusTrigger);
                return;
            }

            if (e.target.id === 'shipmentStatusCancelBtn') {
                closeShipmentStatusModal();
                return;
            }

            if (e.target.id === 'shipmentStatusModalOverlay') {
                closeShipmentStatusModal();
                return;
            }

            if (e.target.id === 'shipmentShowFormBtn') {
                toggleShipmentForm();
                return;
            }

            if (e.target.id === 'shipmentFormCancelBtn') {
                hideShipmentForm();
                return;
            }

            var copyBtn = e.target.closest('#shipmentCopyGuiaBtn');
            if (copyBtn) {
                copyGuia(copyBtn);
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.id === 'shipmentFormGuia') {
                handleGuiaInput(e.target);
            }
            if (e.target.id === 'shipmentStatusNote') {
                updateApplyBtn();
            }
        });

        document.addEventListener('change', function (e) {
            if (e.target.id === 'shipmentFormCarrier') {
                updateLinkPreview();
            }
            if (e.target.id === 'shipmentMotivo') {
                updateApplyBtn();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeShipmentStatusModal();
        });

        initShipmentsColumnVisibility();
    }

    document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', init)
        : init();

})();
