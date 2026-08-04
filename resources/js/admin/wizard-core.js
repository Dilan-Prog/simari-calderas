/**
 * Motor genérico de wizard de pasos, sin dependencias de dominio.
 * Reutilizado por quote-wizard.js y po-wizard.js.
 *
 * No es server-persisted: solo muestra/oculta paneles de un único <form>
 * ya existente (nunca deshabilita campos, para que sigan viajando en el
 * submit final). El submit real vive en el último panel del wizard.
 *
 * config = {
 *   totalSteps: number,
 *   stepPanelSelector: string,     // ej '.qwiz-step-panel'
 *   stepAttr: string,              // ej 'qwizStep' -> data-qwiz-step="N"
 *   barItemSelector: string,       // ej '.qwiz-step' (círculos de la barra)
 *   barConnectorSelector: string,  // ej '.qwiz-step-connector'
 *   nextBtnSelector: string,
 *   backBtnSelector: string,
 *   validateStep: async|sync fn(stepNumber) => bool,  // opcional, validación extra por paso
 *   onEnterStep: fn(stepNumber),                       // opcional
 *   hasLockedContent: fn() => bool,                    // opcional
 *   clearLockedContent: fn(),                           // opcional
 *   lockedFromStep: number,        // pasos >= este se consideran "con contenido bloqueado"
 *   confirmModalSelector: string,
 *   confirmYesSelector: string,
 *   confirmNoSelector: string,
 * }
 */
window.AdminWizard = (function () {
    function init(config) {
        const {
            totalSteps,
            stepPanelSelector,
            stepAttr,
            barItemSelector,
            barConnectorSelector,
            nextBtnSelector,
            backBtnSelector,
            validateStep,
            onEnterStep,
            hasLockedContent,
            clearLockedContent,
            lockedFromStep,
            confirmModalSelector,
            confirmYesSelector,
            confirmNoSelector,
        } = config;

        let current = 1;
        const nextBtn = nextBtnSelector ? document.querySelector(nextBtnSelector) : null;
        const backBtn = backBtnSelector ? document.querySelector(backBtnSelector) : null;

        function panels() {
            return document.querySelectorAll(stepPanelSelector);
        }

        function getPanel(n) {
            return document.querySelector(`${stepPanelSelector}[data-${stepAttr}="${n}"]`);
        }

        function updateBar(n) {
            if (barItemSelector) {
                document.querySelectorAll(barItemSelector).forEach((item, idx) => {
                    const stepNum = idx + 1;
                    item.classList.remove('active', 'completed');
                    if (stepNum < n) item.classList.add('completed');
                    else if (stepNum === n) item.classList.add('active');
                });
            }
            if (barConnectorSelector) {
                document.querySelectorAll(barConnectorSelector).forEach((c, idx) => {
                    c.classList.toggle('done', (idx + 1) < n);
                });
            }
        }

        function showStep(n) {
            panels().forEach((p) => p.classList.remove('active'));
            const panel = getPanel(n);
            if (panel) panel.classList.add('active');
            updateBar(n);
            if (nextBtn) nextBtn.style.display = n === totalSteps ? 'none' : '';
            if (backBtn) backBtn.style.display = n === 1 ? 'none' : '';
            current = n;
            if (typeof onEnterStep === 'function') onEnterStep(n);
            panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Valida los campos [required] visibles dentro del panel activo,
        // además de cualquier validador custom pasado por config.validateStep.
        function validateNativeRequired(panel) {
            const fields = panel.querySelectorAll('[required]');
            let valid = true;
            let firstInvalid = null;
            fields.forEach((f) => {
                if (!f.checkValidity()) {
                    valid = false;
                    f.classList.add('has-error');
                    if (!firstInvalid) firstInvalid = f;
                    f.addEventListener('input', () => f.classList.remove('has-error'), { once: true });
                    f.addEventListener('change', () => f.classList.remove('has-error'), { once: true });
                } else {
                    f.classList.remove('has-error');
                }
            });
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return valid;
        }

        async function goNext() {
            const panel = getPanel(current);
            if (panel && !validateNativeRequired(panel)) return;
            if (typeof validateStep === 'function') {
                const ok = await validateStep(current);
                if (!ok) return;
            }
            if (current < totalSteps) showStep(current + 1);
        }

        function showConfirm() {
            return new Promise((resolve) => {
                const modal = confirmModalSelector ? document.querySelector(confirmModalSelector) : null;
                if (!modal) {
                    resolve(true);
                    return;
                }
                modal.classList.add('active');
                const yesBtn = document.querySelector(confirmYesSelector);
                const noBtn = document.querySelector(confirmNoSelector);

                function cleanup(result) {
                    modal.classList.remove('active');
                    yesBtn?.removeEventListener('click', onYes);
                    noBtn?.removeEventListener('click', onNo);
                    resolve(result);
                }
                function onYes() {
                    cleanup(true);
                }
                function onNo() {
                    cleanup(false);
                }
                yesBtn?.addEventListener('click', onYes);
                noBtn?.addEventListener('click', onNo);
            });
        }

        async function goBack() {
            if (current <= 1) return;
            const leavingLockedStep = lockedFromStep && current >= lockedFromStep;
            if (leavingLockedStep && typeof hasLockedContent === 'function' && hasLockedContent()) {
                const confirmed = await showConfirm();
                if (!confirmed) return;
                if (typeof clearLockedContent === 'function') clearLockedContent();
            }
            showStep(current - 1);
        }

        nextBtn?.addEventListener('click', goNext);
        backBtn?.addEventListener('click', goBack);

        showStep(1);

        return {
            goToStep: showStep,
            getCurrentStep: () => current,
        };
    }

    return { init };
})();
