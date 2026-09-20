/**
 * Checkout de una sola página, tipo acordeón: Carrito / Dirección de envío /
 * Método de pago apilados en resources/views/frontend/shop/checkout/index.blade.php,
 * sin navegar entre URLs. Reemplaza el indicador de "pasos" horizontal que
 * usaba una página por paso.
 *
 * También absorbe la lógica que antes vivía inline en el ya eliminado
 * shipping.blade.php: selector de dirección guardada, modal real de
 * Agregar/Editar dirección, combos buscables (Estado/Uso CFDI/Régimen
 * Fiscal), toggle de factura, y la captura progresiva de contacto.
 */

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function initAccordion() {
    const root = document.getElementById('checkoutAccordion');
    if (!root) return;

    const sections = Array.prototype.slice.call(root.querySelectorAll('[data-accordion-section]'));
    const byName = {};
    sections.forEach((el) => { byName[el.dataset.accordionSection] = el; });

    // 3 piezas de estado independientes por sección -- separadas a propósito
    // (en vez de un solo string de estado) porque "abierta" y "completada"
    // no son excluyentes: reabrir Envío para editarlo mientras Pago sigue
    // sin confirmarse no debe hacer que Pago aparezca como "Completado".
    const unlocked = { cart: true, shipping: false, payment: false };
    const completed = { cart: false, shipping: false, payment: false };
    let openSection = 'cart';

    function render() {
        Object.keys(byName).forEach((key) => {
            const el = byName[key];
            const statusEl = el.querySelector('[data-accordion-status]');
            el.classList.remove('is-locked', 'is-open', 'is-complete');

            if (!unlocked[key]) {
                el.classList.add('is-locked');
                if (statusEl) statusEl.textContent = 'Pendiente';
            } else if (openSection === key) {
                el.classList.add('is-open');
                if (statusEl) statusEl.textContent = '';
            } else if (completed[key]) {
                el.classList.add('is-complete');
                if (statusEl) statusEl.textContent = 'Completado';
            } else if (statusEl) {
                statusEl.textContent = '';
            }
        });
    }

    // Mercado Pago es el método pre-seleccionado por defecto -- en vez de
    // pedir un clic extra en "Continuar con Mercado Pago" antes de mostrar
    // el formulario de tarjeta, el Brick se arma solo apenas la sección de
    // Pago queda abierta (ver window.CheckoutPaymentFlow en
    // checkout-payment.js, que ya trae su propio guard para no relanzarse
    // si ya está activo/en curso).
    function maybeAutoStartMercadoPago() {
        if (openSection === 'payment') {
            window.CheckoutPaymentFlow?.autoStartIfMercadoPago?.();
        }
    }

    function openOnly(name) {
        openSection = name;
        render();
        byName[name].scrollIntoView({ behavior: 'smooth', block: 'start' });
        maybeAutoStartMercadoPago();
    }

    // Estado inicial: si el cliente ya llenó envío antes en esta sesión
    // (recargó la página, volvió de un paso previo), saltar directo a Pago
    // en vez de forzarlo a recapturar todo.
    if (root.dataset.shippingCompleted === '1') {
        completed.cart = true;
        unlocked.shipping = true;
        completed.shipping = true;
        unlocked.payment = true;
        openSection = 'payment';
    }
    render();
    maybeAutoStartMercadoPago();

    // Clic en el encabezado de una sección ya desbloqueada la abre (acordeón:
    // solo una sección abierta a la vez, las demás se colapsan).
    sections.forEach((section) => {
        const header = section.querySelector('[data-accordion-header]');
        if (!header) return;
        header.addEventListener('click', () => {
            const name = section.dataset.accordionSection;
            if (!unlocked[name] || openSection === name) return;
            openOnly(name);
        });
    });

    // Botón "Continuar a envío" del Carrito -- pura UI, ambas secciones ya
    // están renderizadas en la misma página, no hace falta pedir nada.
    root.querySelectorAll('[data-accordion-continue]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.accordionContinue;
            completed.cart = true;
            unlocked[target] = true;
            openOnly(target);
        });
    });

    // Envío del formulario de Dirección de envío por fetch() -- ver
    // CheckoutController::storeShipping(), que ahora responde JSON cuando se
    // le pide Accept: application/json, en vez de forzar una navegación.
    const shippingForm = document.getElementById('shippingForm');
    if (shippingForm) {
        const errorBox = document.getElementById('shippingInlineError');
        const submitBtn = document.getElementById('shippingSubmitBtn');

        shippingForm.addEventListener('submit', (e) => {
            e.preventDefault();
            if (errorBox) errorBox.style.display = 'none';
            if (submitBtn) submitBtn.disabled = true;

            fetch(shippingForm.getAttribute('action') || root.dataset.shippingStoreUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: new FormData(shippingForm),
            })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        const messages = data.errors
                            ? Object.values(data.errors).flat()
                            : [data.message || 'No se pudo guardar. Revisa los datos e intenta de nuevo.'];
                        if (errorBox) {
                            errorBox.innerHTML = '<ul>' + messages.map((m) => `<li>${m}</li>`).join('') + '</ul>';
                            errorBox.style.display = 'block';
                        }
                        if (submitBtn) submitBtn.disabled = false;
                        return;
                    }

                    completed.shipping = true;
                    unlocked.payment = true;
                    openOnly('payment');
                    if (submitBtn) submitBtn.disabled = false;
                })
                .catch(() => {
                    if (errorBox) {
                        errorBox.textContent = 'No se pudo conectar con el servidor. Intenta de nuevo.';
                        errorBox.style.display = 'block';
                    }
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }
}

function initAddressPicker() {
    const addressCards = Array.prototype.slice.call(document.querySelectorAll('[data-address-card]'));
    if (!addressCards.length) return;

    const form = document.getElementById('shippingForm');

    function setField(name, value) {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.value = value || '';
    }

    function fillFromCard(card) {
        setField('contact_name', card.dataset.recipient);
        setField('contact_phone', card.dataset.phone);
        setField('shipping_postal_code', card.dataset.postal);
        setField('shipping_state', card.dataset.state);
        setField('shipping_city', card.dataset.city);
        setField('shipping_address_line2', card.dataset.line2);
        setField('shipping_address_line1', card.dataset.line1);
        setField('shipping_reference', card.dataset.reference);
        // El CP llega ya lleno vía dataset -- dispara 'input' para que
        // initPostalCodeGating() habilite el resto de los campos recién
        // rellenados (si el formulario había arrancado bloqueado).
        const cpInput = form.querySelector('[name="shipping_postal_code"]');
        if (cpInput) cpInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    addressCards.forEach((card) => {
        const radio = card.querySelector('[data-address-radio]');
        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-address-edit], form, button')) return;
            radio.checked = true;
            fillFromCard(card);
        });
    });

    // Modal de Agregar/Editar dirección guardada.
    const addressModal = document.getElementById('addressModal');
    if (!addressModal) return;

    const modalForm = document.getElementById('addressModalForm');
    const modalTitle = document.getElementById('addressModalTitle');
    const modalMethod = document.getElementById('addressModalMethod');
    const addStoreUrl = modalForm.getAttribute('action');
    const fields = {
        label: document.getElementById('amLabel'),
        recipient_name: document.getElementById('amRecipient'),
        phone: document.getElementById('amPhone'),
        postal_code: document.getElementById('amPostal'),
        state: document.getElementById('amState'),
        city: document.getElementById('amCity'),
        address_line2: document.getElementById('amLine2'),
        address_line1: document.getElementById('amLine1'),
        reference: document.getElementById('amReference'),
    };

    function openModal(title, values, actionUrl, method) {
        modalTitle.textContent = title;
        modalForm.setAttribute('action', actionUrl);
        modalMethod.value = method;
        Object.keys(fields).forEach((key) => {
            fields[key].value = (values && values[key]) || '';
        });
        document.getElementById('amDefault').checked = !!(values && values.is_default);
        addressModal.style.display = 'flex';
    }

    function closeModal() {
        addressModal.style.display = 'none';
    }

    addressModal.querySelectorAll('[data-address-modal-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    addressCards.forEach((card) => {
        const editBtn = card.querySelector('[data-address-edit]');
        if (!editBtn) return;
        editBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('Editar dirección', {
                label: card.dataset.label,
                recipient_name: card.dataset.recipient,
                phone: card.dataset.phone,
                postal_code: card.dataset.postal,
                state: card.dataset.state,
                city: card.dataset.city,
                address_line2: card.dataset.line2,
                address_line1: card.dataset.line1,
                reference: card.dataset.reference,
                is_default: card.dataset.isDefault === '1',
            }, card.dataset.updateUrl, 'PUT');
        });
    });

    const addNewBtn = document.getElementById('addNewAddressBtn');
    if (addNewBtn) {
        addNewBtn.addEventListener('click', () => {
            openModal('Agregar dirección', null, addStoreUrl, 'POST');
        });
    }

    // Modal de confirmación para "Eliminar" (reemplaza el confirm() nativo).
    const deleteModal = document.getElementById('addressDeleteModal');
    if (deleteModal) {
        const deleteForm = document.getElementById('addressDeleteModalForm');
        const deleteText = document.getElementById('addressDeleteModalText');

        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }

        deleteModal.querySelectorAll('[data-address-delete-modal-close]').forEach((el) => {
            el.addEventListener('click', closeDeleteModal);
        });

        addressCards.forEach((card) => {
            const deleteBtn = card.querySelector('[data-address-delete]');
            if (!deleteBtn) return;
            deleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                deleteForm.setAttribute('action', deleteBtn.dataset.deleteUrl);
                deleteText.textContent = `"${deleteBtn.dataset.deleteLabel}" se eliminará de tu cuenta. Esta acción no se puede deshacer.`;
                deleteModal.style.display = 'flex';
            });
        });
    }
}

/**
 * Los mensajes nativos de validación HTML5 ("Please fill out this field.",
 * "Please check this box...") los arma el navegador según SU propio idioma
 * de interfaz, no el `lang` de la página -- no hay forma de "traducirlos"
 * solo con HTML. setCustomValidity() es la única API que permite forzar un
 * texto propio; se limpia en cada input/change para que la validación
 * vuelva a evaluarse normalmente (si no, un campo ya corregido seguiría
 * marcado inválido para siempre).
 */
function initSpanishValidationMessages() {
    // #paymentMethodsWrap (no #paymentForm): los radios de método de pago
    // viven fuera del <form>, asociados vía el atributo form="paymentForm"
    // (ver el comentario en index.blade.php).
    document.querySelectorAll('#shippingForm [required], #addressModalForm [required], #paymentMethodsWrap [required]').forEach((field) => {
        function applyMessage() {
            if (field.validity.valueMissing) {
                field.setCustomValidity(
                    field.type === 'checkbox'
                        ? 'Debes aceptar los Términos y Condiciones para continuar.'
                        : 'Este campo es obligatorio.'
                );
            } else if (field.validity.typeMismatch) {
                field.setCustomValidity('El formato no es válido.');
            } else {
                field.setCustomValidity('');
            }
        }

        field.addEventListener('invalid', applyMessage);
        field.addEventListener('input', () => field.setCustomValidity(''));
        field.addEventListener('change', () => field.setCustomValidity(''));
    });
}

function initInvoiceToggle() {
    const invoiceCheckbox = document.getElementById('requiresInvoice');
    const invoiceFields = document.getElementById('invoiceFields');
    if (invoiceCheckbox && invoiceFields) {
        invoiceCheckbox.addEventListener('change', () => {
            invoiceFields.classList.toggle('is-hidden', !invoiceCheckbox.checked);
        });
    }
}

function initSearchableCombos() {
    document.querySelectorAll('[data-combo]').forEach((wrap) => {
        const input = wrap.querySelector('[data-combo-input]');
        const hidden = wrap.querySelector('[data-combo-hidden]');
        const list = wrap.querySelector('[data-combo-list]');
        if (!input || !list) return;

        const options = Array.prototype.slice.call(list.querySelectorAll('.checkout-form__combo-option'));

        function filter() {
            const q = input.value.trim().toLowerCase();
            options.forEach((opt) => {
                opt.style.display = opt.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        }

        input.addEventListener('focus', () => { filter(); wrap.classList.add('is-open'); });
        input.addEventListener('input', () => {
            filter();
            if (hidden) hidden.value = ''; // el usuario está escribiendo, invalida la selección previa hasta que elija de nuevo
        });

        options.forEach((opt) => {
            opt.addEventListener('click', () => {
                input.value = opt.dataset.label || opt.dataset.value;
                if (hidden) hidden.value = opt.dataset.value;
                wrap.classList.remove('is-open');
            });
        });

        document.addEventListener('click', (e) => {
            if (!wrap.contains(e.target)) wrap.classList.remove('is-open');
        });
    });
}

function initProgressiveContactCapture() {
    const nameInput = document.querySelector('input[name="contact_name"]');
    const emailInput = document.querySelector('input[name="contact_email"]');
    const phoneInput = document.querySelector('input[name="contact_phone"]');
    const tokenInput = document.querySelector('#shippingForm input[name="_token"]');
    const captureUrl = document.getElementById('checkoutAccordion')?.dataset.captureContactUrl;
    if (!emailInput || !phoneInput || !tokenInput || !captureUrl) return;

    let lastSent = null;

    function captureContact() {
        const payload = {
            contact_name: nameInput ? nameInput.value.trim() : '',
            contact_email: emailInput.value.trim(),
            contact_phone: phoneInput.value.trim(),
        };
        if (!payload.contact_email && !payload.contact_phone) return;

        const key = JSON.stringify(payload);
        if (key === lastSent) return;
        lastSent = key;

        fetch(captureUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tokenInput.value },
            body: JSON.stringify(payload),
        }).catch(() => { /* silencioso a propósito */ });
    }

    emailInput.addEventListener('blur', captureContact);
    phoneInput.addEventListener('blur', captureContact);
}

/**
 * El "Resumen del pedido" (barra lateral sticky) necesita saber la altura
 * real del header del sitio (también sticky) desde la primera carga -- sin
 * esto, ambos quedan pegados en top:0 y el header tapa la parte de arriba
 * de la tarjeta al hacer scroll. Se recalcula en resize porque el header
 * puede cambiar de alto en breakpoints angostos (la barra de envío se
 * envuelve a 2 líneas).
 */
function initStickyHeaderOffset() {
    const header = document.querySelector('.eq-header');
    if (!header) return;

    function measure() {
        document.documentElement.style.setProperty('--eq-header-height', `${header.getBoundingClientRect().height}px`);
    }

    measure();
    window.addEventListener('resize', measure);
}

/**
 * Resumen del pedido colapsable en móvil (<900px, ver checkout.css) --
 * en escritorio el body siempre está expandido (la regla .is-collapsed
 * solo aplica dentro del media query), este toggle es puramente móvil.
 */
function initSummaryToggle() {
    const toggle = document.getElementById('checkoutSummaryToggle');
    const body = document.getElementById('checkoutSummaryBody');
    const label = document.getElementById('checkoutSummaryToggleLabel');
    if (!toggle || !body) return;

    toggle.addEventListener('click', () => {
        const collapsed = body.classList.toggle('is-collapsed');
        if (label) label.textContent = collapsed ? 'Ver detalle' : 'Ocultar';
    });
}

/**
 * Bloquea Estado/Ciudad/Colonia/Referencias/Dirección hasta que el cliente
 * escriba un código postal de 5 dígitos -- gate puramente visual, sin
 * autofill real (no hay tabla Sepomex en el proyecto). Convive con
 * initSearchableCombos() porque ese combo solo reacciona a foco/input, que
 * no llega mientras el campo está disabled.
 */
function initPostalCodeGating() {
    const cpInput = document.getElementById('shippingPostalCode');
    const gatedFields = document.querySelectorAll('[data-cp-gated]');
    const hint = document.getElementById('shippingCpHint');
    if (!cpInput || !gatedFields.length) return;

    function sync() {
        const ready = cpInput.value.trim().length === 5;
        gatedFields.forEach((field) => { field.disabled = !ready; });
        if (hint) hint.style.display = ready ? 'none' : '';
    }

    cpInput.addEventListener('input', sync);
    sync(); // Ya prellenado (prefill de dirección guardada, `old()` tras un error) -- no debe arrancar bloqueado.
}

/**
 * Botón "Copiar" de la CLABE interbancaria (Transferencia SPEI). El texto
 * a copiar viene del propio dataset (no del textContent, para no depender
 * de espacios de formato que Blade pudiera insertar).
 */
function initClabeCopy() {
    document.querySelectorAll('[data-copy-clabe]').forEach((btn) => {
        const valueEl = btn.closest('.checkout-clabe-box__row')?.querySelector('[data-clabe-value]');
        if (!valueEl) return;

        const originalLabel = btn.textContent;

        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const clabe = valueEl.dataset.clabeValue.replace(/\s+/g, '');

            (navigator.clipboard?.writeText(clabe) ?? Promise.reject()).catch(() => {
                // Respaldo para navegadores/contextos sin Clipboard API (ej. http sin TLS).
                const helper = document.createElement('textarea');
                helper.value = clabe;
                helper.style.position = 'fixed';
                helper.style.opacity = '0';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                document.body.removeChild(helper);
            }).finally(() => {
                btn.textContent = 'Copiada';
                setTimeout(() => { btn.textContent = originalLabel; }, 1800);
            });
        });
    });
}

function initCartItemLoading() {
    document.querySelectorAll('.checkout-cart-item__qty, .checkout-cart-item__remove-form').forEach((form) => {
        form.addEventListener('submit', () => {
            form.classList.add('is-loading');
            // Deshabilitar los botones aquí mismo (síncrono) hace que el
            // navegador excluya el botón que disparó el submit (name="quantity")
            // del formulario ya enviado -- construye la lista de datos DESPUÉS
            // de este evento, así que llega sin "quantity" y el backend
            // responde "The quantity field is required.". Con setTimeout se
            // deshabilita en el siguiente tick, cuando el envío ya capturó el
            // valor del botón.
            setTimeout(() => {
                form.querySelectorAll('button').forEach((btn) => { btn.disabled = true; });
            }, 0);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initAccordion();
    initAddressPicker();
    initInvoiceToggle();
    initSearchableCombos();
    initProgressiveContactCapture();
    initSpanishValidationMessages();
    initStickyHeaderOffset();
    initSummaryToggle();
    initPostalCodeGating();
    initClabeCopy();
    initCartItemLoading();
});
