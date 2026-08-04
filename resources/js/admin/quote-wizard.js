/**
 * Bootstrap del wizard de 4 pasos para Cotizaciones (create y edit).
 * Usa el motor genérico window.AdminWizard (wizard-core.js).
 *
 * Diseño: moneda/tipo de cambio y cliente viven en pasos ANTERIORES
 * bloqueados (1 y 2), antes de poder tocar productos/servicios (paso 4).
 * Así, el bug de "productos con precio viejo tras cambiar moneda" se
 * evita por diseño: si el usuario retrocede a un paso bloqueado
 * teniendo productos ya agregados, se le avisa y, si confirma, se
 * vacía la tabla de items para forzar que los vuelva a agregar con la
 * moneda/tipo de cambio correctos.
 */
(function () {
    'use strict';

    var selectedType = null; // 'products' | 'services' | 'both'

    function q(id) {
        return document.getElementById(id);
    }

    // ── Paso 3: tarjetas de tipo de cotización ──────────────
    function initTypeCards() {
        var cards = document.querySelectorAll('.qwiz-type-card');
        var typeError = q('qwizTypeError');

        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                cards.forEach(function (c) { c.classList.remove('selected'); });
                card.classList.add('selected');
                selectedType = card.dataset.qwizType;
                if (typeError) typeError.style.display = 'none';
            });
        });
    }

    // ── Paso 4: mostrar/ocultar buscadores según el tipo elegido ──
    function applyTypeFilter() {
        var productAccordion = q('qwizProductAccordion');
        var serviceAccordion = q('qwizServiceAccordion');
        if (!productAccordion || !serviceAccordion) return;

        if (selectedType === 'products') {
            productAccordion.style.display = '';
            serviceAccordion.style.display = 'none';
        } else if (selectedType === 'services') {
            productAccordion.style.display = 'none';
            serviceAccordion.style.display = '';
        } else {
            // 'both' o sin selección (fallback defensivo) — muestra ambos
            productAccordion.style.display = '';
            serviceAccordion.style.display = '';
        }
    }

    // ── Contenido bloqueado: líneas ya agregadas a la cotización ──
    function hasLockedContent() {
        return document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)').length > 0;
    }

    function clearLockedContent() {
        var tbody = q('itemsTableBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        var empty = document.createElement('tr');
        empty.id = 'emptyRow';
        empty.className = 'products-table-empty';
        empty.innerHTML = '<td colspan="8">Agrega productos usando los botones de arriba</td>';
        tbody.appendChild(empty);
        if (window.QuoteForm) window.QuoteForm.calculateGlobalTotals();
    }

    // ── Validación custom por paso ───────────────────────────
    function validateStep(stepNumber) {
        if (stepNumber === 1) {
            var customerIdInput = q('customerIdInput');
            var customerIdError = q('customerIdError');
            if (!customerIdInput || !customerIdInput.value) {
                if (customerIdError) {
                    customerIdError.style.display = 'block';
                    customerIdError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            if (customerIdError) customerIdError.style.display = 'none';
            return true;
        }

        if (stepNumber === 3) {
            if (!selectedType) {
                var typeError = q('qwizTypeError');
                if (typeError) {
                    typeError.style.display = 'block';
                    typeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            return true;
        }

        return true;
    }

    function onEnterStep(stepNumber) {
        if (stepNumber === 4) applyTypeFilter();
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('quoteForm') && !document.getElementById('quoteEditForm')) return;
        if (!window.AdminWizard) return;

        initTypeCards();

        window.AdminWizard.init({
            totalSteps: 4,
            stepPanelSelector: '.qwiz-step-panel',
            stepAttr: 'qwiz-step',
            barItemSelector: '.qwiz-step',
            barConnectorSelector: '.qwiz-step-connector',
            nextBtnSelector: '#qwizNextBtn',
            backBtnSelector: '#qwizBackBtn',
            validateStep: validateStep,
            onEnterStep: onEnterStep,
            hasLockedContent: hasLockedContent,
            clearLockedContent: clearLockedContent,
            lockedFromStep: 2,
            confirmModalSelector: '.qwiz-confirm-backdrop',
            confirmYesSelector: '#qwizConfirmYes',
            confirmNoSelector: '#qwizConfirmNo',
        });
    });
})();
