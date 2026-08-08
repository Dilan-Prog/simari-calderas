/**
 * Bootstrap del wizard de 3 pasos para Órdenes de Compra (create/edit).
 * Usa el motor genérico de resources/js/admin/wizard-core.js.
 *
 * Pasos: 1) Proveedor  2) Moneda y configuración  3) Productos + Resumen
 *
 * Proveedor/moneda/tipo de cambio quedan fijos ANTES de poder tocar
 * productos (Paso 3) — así el bug de líneas con precio viejo tras cambiar
 * moneda/tipo de cambio desaparece por diseño: ya no se puede cambiar
 * ninguno de los dos una vez que hay productos agregados sin antes
 * confirmar que se perderán.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.AdminWizard) return;

    const wizard = window.AdminWizard.init({
        totalSteps: 3,
        stepPanelSelector: '.powiz-step-panel',
        // NOTA: wizard-core.js interpola este valor literalmente en
        // `[data-${stepAttr}="N"]` (sin convertir camelCase a kebab-case).
        // El markup usa `data-powiz-step="N"` (kebab), así que el valor
        // debe ser 'powiz-step', no 'powizStep' — confirmado contra el
        // precedente ya existente en quotes/create.blade.php, que usa
        // `data-qwiz-step="N"` de la misma forma.
        stepAttr: 'powiz-step',
        barItemSelector: '.powiz-step',
        barConnectorSelector: '.powiz-step-connector',
        nextBtnSelector: '#powizNextBtn',
        backBtnSelector: '#powizBackBtn',
        confirmModalSelector: '.powiz-confirm-backdrop',
        confirmYesSelector: '#powizConfirmYes',
        confirmNoSelector: '#powizConfirmNo',
        lockedFromStep: 2,

        validateStep(stepNumber) {
            if (stepNumber === 1) {
                const supplier = document.getElementById('supplierSelect');
                const orderDate = document.querySelector('[name="order_date"]');
                let ok = true;

                if (supplier && !supplier.value) {
                    supplier.classList.add('has-error');
                    ok = false;
                } else if (supplier) {
                    supplier.classList.remove('has-error');
                }

                if (orderDate && !orderDate.value) {
                    orderDate.classList.add('has-error');
                    ok = false;
                } else if (orderDate) {
                    orderDate.classList.remove('has-error');
                }

                return ok;
            }
            return true;
        },

        onEnterStep(stepNumber) {
            if (stepNumber === 3 && window.__poWizardBridge) {
                window.__poWizardBridge.refetchCatalog();
            }
        },

        hasLockedContent() {
            return !!(window.__poWizardBridge && window.__poWizardBridge.hasItems());
        },

        clearLockedContent() {
            if (window.__poWizardBridge) window.__poWizardBridge.clearItems();
        },
    });

    // Si el submit anterior falló validación server-side en un campo que
    // vive en el Paso 2 o 3, AdminWizard.init() ya forzó showStep(1) — el
    // panel con el @error real queda oculto por el CSS de paneles y el
    // usuario solo ve el toast genérico sin saber a qué paso ir. La vista
    // (create.blade.php / edit.blade.php) calcula el paso correcto en
    // window.__poFormErrorStep antes de que corra este script.
    if (wizard && window.__poFormErrorStep && window.__poFormErrorStep > 1) {
        wizard.goToStep(window.__poFormErrorStep);
    }
});
