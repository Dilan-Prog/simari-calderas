/**
 * Tarjeta de crédito/débito vía Mercado Pago Checkout API (CardForm/Secure
 * Fields) -- reemplaza el Payment Brick que se usaba antes para esto. Solo
 * número/vencimiento/CVV quedan dentro de iframes seguros de MP (`iframe:
 * true` en mp.cardForm()); el resto del formulario (nombre, layout, logos,
 * vista previa de tarjeta) es 100% nuestro HTML/CSS. Wallet/Efectivo/
 * Transferencia/Mercado Crédito ya NO viven aquí -- son exclusivamente
 * Checkout Pro (ver checkout-payment.js), un radio aparte.
 *
 * Dos consumidores, ambos delegan 100% de la lógica de montaje a este
 * archivo (mountCardFormsInto):
 *
 * 1. resources/views/frontend/shop/checkout/pay-mercadopago.blade.php --
 *    página dedicada (reintento de un cobro rechazado, vía link firmado).
 * 2. resources/views/frontend/shop/checkout/index.blade.php (sección "Método
 *    de pago", radio "Tarjeta de crédito o débito") -- se renderiza EN LA
 *    MISMA página, sin navegar a una URL aparte: checkout-payment.js llama a
 *    window.MercadoPagoCardForm.render(container, config) con el config que
 *    devuelve CheckoutController::confirm() cuando responde JSON.
 */

const STATUS_DETAIL_LABELS = {
    cc_rejected_insufficient_amount: 'Fondos insuficientes.',
    cc_rejected_bad_filled_security_code: 'El código de seguridad (CVV) es incorrecto.',
    cc_rejected_bad_filled_date: 'La fecha de vencimiento de la tarjeta es incorrecta.',
    cc_rejected_bad_filled_card_number: 'El número de tarjeta es incorrecto.',
    cc_rejected_bad_filled_other: 'Revisa los datos de la tarjeta e intenta de nuevo.',
    cc_rejected_call_for_authorize: 'Tu banco requiere que autorices el pago directamente con ellos.',
    cc_rejected_card_disabled: 'La tarjeta está deshabilitada, contacta a tu banco.',
    cc_rejected_duplicated_payment: 'Ya se registró un pago con estos mismos datos.',
    cc_rejected_high_risk: 'El pago fue rechazado por seguridad.',
    cc_rejected_max_attempts: 'Se alcanzó el límite de intentos permitidos.',
};

function statusDetailLabel(statusDetail) {
    return STATUS_DETAIL_LABELS[statusDetail] || 'No se pudo procesar el pago, intenta con otra tarjeta.';
}

// in_process/authorized/pending (tarjeta de prueba "CONT" en sandbox, o una
// revisión manual real) NO es un rechazo -- charge() ya decide cuándo manda
// `redirect` para este caso (ver su comentario), aquí solo hace falta no
// pintarlo como error rojo con un mensaje de "intenta con otra tarjeta" que
// no aplica.
const PENDING_LIKE_STATUSES = ['in_process', 'authorized', 'pending'];

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function waitForMercadoPagoSdk() {
    return new Promise((resolve, reject) => {
        if (window.MercadoPago) {
            resolve(window.MercadoPago);
            return;
        }

        let attempts = 0;
        const interval = setInterval(() => {
            attempts += 1;
            if (window.MercadoPago) {
                clearInterval(interval);
                resolve(window.MercadoPago);
            } else if (attempts > 100) {
                clearInterval(interval);
                reject(new Error('No se pudo cargar el SDK de Mercado Pago.'));
            }
        }, 50);
    });
}

/**
 * Para el flujo inline (index.blade.php): el <script src> del SDK no vive en
 * esa página a propósito (no todo cliente elige Tarjeta, no tiene sentido
 * cargar un script externo para todos) -- se inyecta bajo demanda, solo
 * cuando el cliente de verdad selecciona/confirma este método.
 */
function loadMercadoPagoSdkScript() {
    if (window.MercadoPago) {
        return Promise.resolve(window.MercadoPago);
    }

    if (document.querySelector('script[data-mp-sdk]')) {
        return waitForMercadoPagoSdk();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://sdk.mercadopago.com/js/v2';
        script.dataset.mpSdk = '1';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('No se pudo cargar el SDK de Mercado Pago.'));
        document.head.appendChild(script);
    }).then(() => waitForMercadoPagoSdk());
}

function setMessage(block, text, kind) {
    const el = block.querySelector('[data-mp-message]');
    if (!el) return;
    el.textContent = text || '';
    el.classList.remove('mp-message--error', 'mp-message--info');
    if (kind) {
        el.classList.add(`mp-message--${kind}`);
    }
}

function disableBlock(block) {
    block.classList.add('mp-charge-disabled');
}

function enableBlock(block) {
    block.classList.remove('mp-charge-disabled');
}

async function chargePayment(chargeUrl, payload) {
    const res = await fetch(chargeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => ({}));

    return { ok: res.ok, data };
}

/**
 * Markup de cada "slot" de cobro (1 o 2 por pedido, split de MSI -- ver
 * CheckoutController::createMercadoPagoPaymentSlots()). La vista previa de
 * tarjeta solo refleja el nombre del titular en vivo (es un <input> normal,
 * sí podemos leerlo); número y vencimiento quedan permanentemente
 * enmascarados a propósito -- con iframe:true esos campos son iframes de MP,
 * nunca vemos las pulsaciones reales, así que nunca se simula una animación
 * de tecleo falsa.
 */
// El SDK de Mercado Pago cachea su instancia interna de CardForm por el
// STRING del id del <form> (confirmado en vivo: "Cardform already
// instantiated. Returning existing instance..." incluso tras limpiar
// innerHTML y recrear los nodos con el mismo id) -- reusar "cardForm-1" en
// cada remontaje (cambiar de método de pago y volver a Tarjeta) hacía que
// el SDK devolviera la instancia vieja, ligada a nodos ya destruidos, y el
// campo de tarjeta se quedaba sin su iframe seguro. Un sufijo que nunca se
// repite en la misma carga de página evita la colisión de raíz.
let mountSeq = 0;

function buildCardFormBlocksMarkup(payments, retryOnly, payerEmail, uid) {
    return payments.map((payment) => {
        const g = payment.chargeGroup; // id de bloque -- SIN el sufijo, se sigue usando para [data-mp-block] (disable/enable entre cobros, overlay, etc.)
        const fid = `${g}-${uid}`; // ids de campos del CardForm en sí -- únicos por montaje real (ver comentario de mountSeq arriba)

        const titleText = payments.length > 1
            ? `Cobro ${g} de ${payments.length}: ${payment.includesMsi ? 'productos con meses sin interés' : 'resto de tu pedido'} — $${payment.amount.toFixed(2)} MXN`
            : null;

        // Modo reintento (pay-mercadopago.blade.php, link firmado de un cobro
        // rechazado): los cobros que NO son el que se está reintentando se
        // muestran en solo lectura (aprobado/estatus), nunca un formulario
        // de tarjeta en blanco/no-funcional.
        if (retryOnly && payment.id !== retryOnly) {
            const statusMarkup = payment.status === 'approved'
                ? '<div class="checkout-alert" style="background:#e6f6ee;color:#0f7a4f;">Este cobro ya está aprobado.</div>'
                : `<div class="checkout-alert" style="background:#f1f2f4;color:#6b7280;">Estatus de este cobro: ${payment.status || 'pendiente'}</div>`;

            return `
                <div class="checkout-payment mp-charge-block" data-mp-block="${g}">
                    ${titleText ? `<div class="checkout-payment__head"><div class="checkout-payment__head-title">${titleText}</div></div>` : ''}
                    ${statusMarkup}
                </div>
            `;
        }

        const showOverlay = !retryOnly && g === 2;

        // Cuotas: oculto por completo si el slot no acepta MSI -- el server
        // (MercadoPagoCheckoutController::charge()) ya fuerza installments=1
        // sin importar lo que mande el cliente, así que mostrar un selector
        // ahí sería engañoso (ver mountCardFormsInto()).
        const installmentsMarkup = payment.includesMsi
            ? `<select class="mp-cardform-field checkout-form__span2" id="form-checkout__installments-${fid}"></select>`
            : `<select class="mp-cardform-field mp-cardform-field--hidden" id="form-checkout__installments-${fid}"></select>
               <div class="mp-cardform-installments-note checkout-form__span2">Pago en una sola exhibición.</div>`;

        return `
            <div class="checkout-payment mp-charge-block mp-cardform-block" data-mp-block="${g}">
                ${titleText ? `<div class="checkout-payment__head"><div class="checkout-payment__head-title">${titleText}</div></div>` : ''}

                <div class="mp-cardform-preview" data-mp-preview="${g}">
                    <div class="mp-cardform-preview__top">
                        <span class="mp-cardform-preview__chip"></span>
                        <span class="mp-cardform-preview__brand" data-mp-preview-brand>&nbsp;</span>
                    </div>
                    <div class="mp-cardform-preview__number">•••• •••• •••• ••••</div>
                    <div class="mp-cardform-preview__bottom">
                        <span data-mp-preview-name>NOMBRE EN LA TARJETA</span>
                        <span>••/••</span>
                    </div>
                </div>

                <form id="cardForm-${fid}" class="checkout-form__grid checkout-form__grid--tight">
                    <input type="hidden" id="form-checkout__cardholderEmail-${fid}" value="${payerEmail || ''}">

                    <div class="checkout-form__span2 mp-cardform-field" id="form-checkout__cardNumber-${fid}"></div>
                    <input type="text" id="form-checkout__cardholderName-${fid}" class="checkout-form__span2" placeholder="Nombre en la tarjeta" data-mp-name-input>
                    <div class="mp-cardform-field" id="form-checkout__expirationDate-${fid}"></div>
                    <div class="mp-cardform-field" id="form-checkout__securityCode-${fid}"></div>

                    ${installmentsMarkup}

                    <select class="mp-cardform-field" id="form-checkout__identificationType-${fid}"></select>
                    <input type="text" class="mp-cardform-field" id="form-checkout__identificationNumber-${fid}" placeholder="RFC o CURP (opcional)">
                    <select id="form-checkout__issuer-${fid}" style="display:none"></select>

                    <button type="submit" class="checkout-submit checkout-form__span2" id="cardFormSubmit-${fid}">Confirmar y pagar $${payment.amount.toFixed(2)} MXN</button>
                </form>

                <p data-mp-message class="mp-message"></p>

                <div class="mp-brand-logos-row">
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/visa.jpg" alt="Visa"></span>
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/mastercard.jpg" alt="Mastercard"></span>
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/amex.webp" alt="American Express"></span>
                    <span class="mp-trust-badge mp-trust-badge--pci">Certificado PCI-DSS</span>
                </div>

                ${showOverlay ? '<div class="mp-charge-overlay">Se habilita al aprobarse el Cobro 1</div>' : ''}
            </div>
        `;
    }).join('');
}

function mountCardFormsInto(mp, config, uid) {
    const { payments, retryOnly, publicKey } = config;
    const payerEmail = config.payerEmail || '';
    const instances = []; // se devuelven para que el llamador pueda unmount()-earlas más tarde (ver window.MercadoPagoCardForm.unmount)

    payments.forEach((payment, index) => {
        const g = payment.chargeGroup;
        const fid = `${g}-${uid}`; // mismo sufijo único que buildCardFormBlocksMarkup() -- ver comentario de mountSeq
        const block = document.querySelector(`[data-mp-block="${g}"]`);
        if (!block) return;

        // El segundo cobro (si existe) arranca deshabilitado hasta que el
        // primero se apruebe -- salvo en modo reintento, donde solo el cobro
        // reintentable es interactivo desde el inicio.
        const isFirstInteractive = index === 0;
        if (!isFirstInteractive && !retryOnly) {
            disableBlock(block);
        }

        if (retryOnly && payment.id !== retryOnly) {
            // Bloque de solo lectura: no se monta CardForm.
            return;
        }

        const nameInput = document.getElementById(`form-checkout__cardholderName-${fid}`);
        const previewName = block.querySelector('[data-mp-preview-name]');
        if (nameInput && previewName) {
            nameInput.addEventListener('input', () => {
                previewName.textContent = nameInput.value.trim().toUpperCase() || 'NOMBRE EN LA TARJETA';
            });
        }

        const cardForm = mp.cardForm({
            amount: String(payment.amount.toFixed(2)),
            iframe: true, // cardNumber/expirationDate/securityCode se vuelven <div> = iframes seguros de MP
            form: {
                id: `cardForm-${fid}`,
                cardholderName: { id: `form-checkout__cardholderName-${fid}` },
                cardholderEmail: { id: `form-checkout__cardholderEmail-${fid}` },
                cardNumber: { id: `form-checkout__cardNumber-${fid}` },
                expirationDate: { id: `form-checkout__expirationDate-${fid}` },
                securityCode: { id: `form-checkout__securityCode-${fid}` },
                installments: { id: `form-checkout__installments-${fid}` },
                identificationType: { id: `form-checkout__identificationType-${fid}` },
                identificationNumber: { id: `form-checkout__identificationNumber-${fid}` },
                issuer: { id: `form-checkout__issuer-${fid}` },
            },
            callbacks: {
                onFormMounted: (error) => {
                    if (error) {
                        // eslint-disable-next-line no-console
                        console.error('[mercadopago-checkout] CardForm onFormMounted error', error);
                        setMessage(block, 'No se pudo cargar el formulario de tarjeta. Recarga la página.', 'error');
                    }
                },
                // Best-effort: swap del ícono de marca en la vista previa en
                // cuanto el BIN resuelve lo suficiente para traer cuotas --
                // sin garantía documentada de shape exacto, por eso tiene un
                // respaldo estático (la fila de logos Visa/MC/Amex, siempre
                // visible e independiente de este evento).
                onInstallmentsReceived: (error, data) => {
                    if (error || !Array.isArray(data) || !data.length) return;
                    const brandEl = block.querySelector('[data-mp-preview-brand]');
                    const pmId = data[0]?.payment_method_id || data[0]?.paymentMethodId;
                    if (brandEl && pmId) brandEl.textContent = String(pmId).toUpperCase();
                },
                onValidityChange: (error, field) => {
                    const fieldEl = document.getElementById(`form-checkout__${field}-${fid}`);
                    if (!fieldEl) return;
                    fieldEl.classList.toggle('mp-cardform-field--invalid', !!error);
                },
                onSubmit: (event) => {
                    event.preventDefault();
                    setMessage(block, '', null);

                    const submitBtn = document.getElementById(`cardFormSubmit-${fid}`);
                    if (submitBtn) submitBtn.disabled = true;

                    const fd = cardForm.getCardFormData();
                    const identification = fd.identificationNumber
                        ? { type: fd.identificationType, number: fd.identificationNumber }
                        : undefined;

                    chargePayment(payment.chargeUrl, {
                        token: fd.token,
                        payment_method_id: fd.paymentMethodId,
                        // Espejo client-side de la defensa server-side --
                        // charge() ya fuerza 1 exhibición cuando el slot no
                        // acepta MSI, sin importar lo que se mande aquí.
                        installments: payment.includesMsi ? fd.installments : 1,
                        payer: { email: payerEmail, identification },
                    })
                        .then(({ ok, data }) => {
                            if (submitBtn) submitBtn.disabled = false;

                            if (ok && data.status === 'approved') {
                                setMessage(block, 'Pago aprobado.', 'info');

                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                    return;
                                }

                                const next = payments[index + 1];
                                if (next) {
                                    const nextBlock = document.querySelector(`[data-mp-block="${next.chargeGroup}"]`);
                                    if (nextBlock) enableBlock(nextBlock);
                                }
                            } else if (ok && PENDING_LIKE_STATUSES.includes(data.status)) {
                                setMessage(block, 'Tu pago quedó en revisión por Mercado Pago -- te confirmaremos por correo en cuanto se resuelva.', 'info');

                                if (data.redirect) {
                                    window.location.href = data.redirect;
                                }
                            } else if (ok) {
                                setMessage(block, statusDetailLabel(data.status_detail), 'error');
                            } else {
                                setMessage(block, data.status_detail ? statusDetailLabel(data.status_detail) : 'No se pudo procesar el pago, intenta con otra tarjeta.', 'error');
                            }
                        })
                        .catch(() => {
                            if (submitBtn) submitBtn.disabled = false;
                            setMessage(block, 'No se pudo procesar el pago, intenta con otra tarjeta.', 'error');
                        });
                },
                onError: (error) => {
                    // eslint-disable-next-line no-console
                    console.error('[mercadopago-checkout] CardForm error', error);
                },
            },
        });

        instances.push(cardForm);
    });

    return instances;
}

async function init() {
    const dataEl = document.getElementById('mp-checkout-data');
    if (!dataEl) return;

    const config = JSON.parse(dataEl.textContent);

    let MercadoPagoSdk;
    try {
        MercadoPagoSdk = await waitForMercadoPagoSdk();
    } catch (e) {
        config.payments.forEach((p) => {
            const block = document.querySelector(`[data-mp-block="${p.chargeGroup}"]`);
            if (block) setMessage(block, 'No se pudo cargar la pasarela de pago. Recarga la página.', 'error');
        });
        return;
    }

    const root = document.getElementById('mpCardFormRoot');
    if (!root || root.dataset.mpRendered === '1') return; // mismo guard que window.MercadoPagoCardForm.render()
    root.dataset.mpRendered = '1';
    const uid = ++mountSeq;
    root.innerHTML = buildCardFormBlocksMarkup(config.payments, config.retryOnly, config.payerEmail, uid);

    const mp = new MercadoPagoSdk(config.publicKey, { locale: 'es-MX' });
    mountCardFormsInto(mp, config, uid);
}

document.addEventListener('DOMContentLoaded', init);

/**
 * API para el flujo inline de index.blade.php (ver checkout-payment.js).
 * `container` es el elemento donde se inyecta el markup de los cobros;
 * `config` es exactamente el JSON que devuelve CheckoutController::confirm()
 * cuando se elige la Tarjeta de Mercado Pago (mismo shape que
 * #mp-checkout-data: publicKey/retryOnly/payments/payerEmail).
 */
window.MercadoPagoCardForm = {
    async render(container, config) {
        // Guard persistente en el contenedor real (nunca se recrea entre
        // llamadas, a diferencia del HTML que sí se reemplaza) -- evita
        // relanzar el fetch/montaje si render() se llama 2 veces mientras ya
        // hay uno en curso o montado.
        if (container.dataset.mpRendered === '1') return;
        container.dataset.mpRendered = '1';

        const uid = ++mountSeq;
        container.innerHTML = buildCardFormBlocksMarkup(config.payments, config.retryOnly, config.payerEmail, uid);

        let MercadoPagoSdk;
        try {
            MercadoPagoSdk = await loadMercadoPagoSdkScript();
        } catch (e) {
            container.innerHTML = '<p class="mp-message mp-message--error">No se pudo cargar la pasarela de pago. Recarga la página.</p>';
            throw e;
        }

        const mp = new MercadoPagoSdk(config.publicKey, { locale: 'es-MX' });
        // El registro interno de CardForm del SDK de MP NO está ligado al id
        // del <form> ni al elemento del DOM -- confirmado en vivo: incluso
        // con ids nunca antes usados, un 2º mp.cardForm() devuelve la
        // instancia vieja ("Cardform already instantiated. Returning
        // existing instance") si la anterior nunca se desmontó de verdad.
        // Se guardan las instancias en el propio contenedor para poder
        // llamar cardForm.unmount() (API real del SDK, confirmada en su
        // repo oficial) antes de montar de nuevo -- ver unmount() abajo,
        // invocado desde deactivateCardFlow() en checkout-payment.js.
        container._mpCardForms = mountCardFormsInto(mp, config, uid);
    },

    /**
     * Desmonta de verdad las instancias de CardForm ya creadas en este
     * contenedor (llama a su API real cardForm.unmount()) antes de que
     * checkout-payment.js limpie el innerHTML -- sin esto, un remontaje
     * posterior (el cliente cambia de método y regresa a Tarjeta) siempre
     * devuelve la instancia vieja sin importar qué ids se usen.
     */
    unmount(container) {
        (container._mpCardForms || []).forEach((cardForm) => {
            try {
                cardForm.unmount();
            } catch (e) {
                // eslint-disable-next-line no-console
                console.error('[mercadopago-checkout] Error al desmontar CardForm', e);
            }
        });
        container._mpCardForms = [];
        delete container.dataset.mpRendered;
    },
};
