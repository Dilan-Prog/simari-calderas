/**
 * resources/views/frontend/shop/checkout/index.blade.php -- intercepta el
 * submit del formulario de "Método de pago" cuando el método elegido es
 * Mercado Pago, que hoy se ofrece como 2 radios independientes que comparten
 * el mismo payment_method_id (ver index.blade.php, sección "Método de
 * pago"):
 *
 *  - data-mp-flow="card": Tarjeta de crédito/débito, vía Mercado Pago
 *    Checkout API/CardForm (mercadopago-checkout.js) -- se monta inline en
 *    esta misma página (fetch con Accept: application/json contra
 *    checkout.confirm, que ya rama a este shape para Mercado Pago).
 *  - data-mp-flow="checkout_pro": Wallet/Efectivo/Transferencia/Mercado
 *    Crédito, 100% redirect al entorno hospedado de Mercado Pago
 *    (checkoutPro(), ya probado en vivo). El submit del formulario, en este
 *    flujo, hace 2 llamadas encadenadas: (1) checkout.confirm crea el
 *    pedido + el/los cobro(s), (2) checkoutPro() crea la preferencia real de
 *    MP y regresa su URL hospedada -- se navega ahí con una redirección de
 *    página completa.
 *
 * Un hidden input (#mpPaymentFlow) sincroniza cuál de los 2 sub-flujos está
 * activo antes de cada submit -- CheckoutController::confirm() lo lee para
 * ramificar. Cualquier otro método de pago sigue el POST clásico de
 * siempre -- cero cambio de comportamiento para transferencia/referencia/
 * crédito/otro.
 */

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function init() {
    const form = document.getElementById('paymentForm');
    if (!form) return;

    const mpMethodId = form.dataset.mpMethodId;
    const flowInput = document.getElementById('mpPaymentFlow');
    if (!mpMethodId || !flowInput) return; // No hay método Mercado Pago activo -- nada que interceptar.

    const actionsWrap = document.getElementById('paymentActionsWrap');
    const cardPanel = document.getElementById('mpCardPanel');
    const cardContainer = document.getElementById('mpCardContainer');
    const checkoutProPanel = document.getElementById('mpCheckoutProPanel'); // puede no existir (carrito con split de MSI)
    const submitBtn = document.getElementById('paymentSubmitBtn');
    const submitBtnLabel = document.getElementById('paymentSubmitBtnLabel');
    const errorBox = document.getElementById('mpInlineError');

    if (!actionsWrap) return;

    const originalLabel = submitBtnLabel ? submitBtnLabel.textContent : '';
    let cardFormStarted = false;

    // Los radios viven FUERA de <form id="paymentForm"> (asociados vía el
    // atributo form="paymentForm") -- ver el comentario en index.blade.php
    // sobre por qué (evitar anidar el <form> que mercadopago-checkout.js
    // inyecta dentro de #mpCardContainer). form.querySelector() solo busca
    // descendientes del DOM, así que aquí se consulta document en su lugar.
    function checkedMpRadio() {
        return document.querySelector('input[name="payment_method_id"][data-mp-radio]:checked');
    }

    // null cuando el método marcado NO es ninguno de los 2 sub-flujos de
    // Mercado Pago (ej. transferencia) -- el submit clásico sigue su curso.
    function currentFlow() {
        const radio = checkedMpRadio();
        return radio ? radio.dataset.mpFlow : null;
    }

    function syncFlowInput() {
        const flow = currentFlow();
        if (flow) flowInput.value = flow;
    }

    function syncSubmitLabel() {
        if (!submitBtnLabel) return;
        const flow = currentFlow();
        if (flow === 'checkout_pro') {
            submitBtnLabel.textContent = 'Continuar a Mercado Pago';
        } else if (flow === 'card') {
            submitBtnLabel.textContent = 'Continuar con Mercado Pago';
        } else {
            submitBtnLabel.textContent = originalLabel;
        }
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    }

    function hideError() {
        if (!errorBox) return;
        errorBox.style.display = 'none';
    }

    function deactivateCardFlow() {
        if (cardPanel) cardPanel.classList.remove('is-open');
        if (cardContainer) {
            // unmount() real (API del SDK de MP) ANTES de limpiar el HTML --
            // sin esto, el registro interno del SDK sigue viendo el CardForm
            // como "instanciado" y un remontaje posterior (el cliente vuelve
            // a elegir Tarjeta) devuelve la instancia vieja en vez de crear
            // una real, dejando el campo de tarjeta sin su iframe seguro.
            window.MercadoPagoCardForm?.unmount?.(cardContainer);
            cardContainer.innerHTML = '';
        }
        cardFormStarted = false;
    }

    function deactivateCheckoutProFlow() {
        if (checkoutProPanel) checkoutProPanel.classList.remove('is-open');
    }

    // Solo cambia clases/visibilidad -- nunca dispara una petición de red,
    // así es seguro llamarla en cualquier momento (incluso con la sección
    // de Pago todavía colapsada).
    function syncPanels() {
        const flow = currentFlow();

        if (flow === 'card') {
            deactivateCheckoutProFlow();
            if (cardPanel) cardPanel.classList.add('is-open');
            actionsWrap.style.display = 'none'; // el propio CardForm trae su botón de envío por cobro
        } else if (flow === 'checkout_pro') {
            deactivateCardFlow();
            if (checkoutProPanel) checkoutProPanel.classList.add('is-open');
            actionsWrap.style.display = '';
            // deactivateCardFlow() no toca este flag -- sin esto, el botón
            // queda deshabilitado para siempre si antes se llegó a iniciar
            // el flujo de Tarjeta (startCardFlow() lo deshabilita mientras
            // hace su propio fetch) y luego el cliente cambia de opinión.
            if (submitBtn) submitBtn.disabled = false;
        } else {
            deactivateCardFlow();
            deactivateCheckoutProFlow();
            actionsWrap.style.display = '';
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    function startCardFlow() {
        if (cardFormStarted) return; // ya montado/en curso -- evita relanzarlo
        cardFormStarted = true;
        hideError();

        if (submitBtn) submitBtn.disabled = true;
        if (cardContainer) cardContainer.innerHTML = '<p class="mp-inline-loading">Preparando el pago con Mercado Pago…</p>';

        fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: new FormData(form),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const message = data.message || 'No se pudo iniciar el pago. Intenta de nuevo.';
                    deactivateCardFlow();
                    showError(message);
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }

                window.MercadoPagoCardForm.render(cardContainer, data).catch(() => {
                    // El propio render() ya deja un mensaje de error dentro
                    // del contenedor -- aquí solo se re-habilita el botón
                    // por si el cliente reintenta (el panel se queda abierto).
                    if (submitBtn) submitBtn.disabled = false;
                });
            })
            .catch(() => {
                deactivateCardFlow();
                showError('No se pudo conectar con el servidor. Intenta de nuevo.');
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    // 2º paso del flujo "checkout_pro" -- mismo POST que ya usaba el link
    // secundario "¿Prefieres pagar con OXXO...?" cuando vivía bajo la
    // Tarjeta; ahora lo dispara el submit principal del radio dedicado. En
    // vez de navegar la pestaña completa al checkout hospedado de MP, se
    // abre en una ventana emergente -- el cliente nunca pierde de vista
    // nuestro sitio de fondo. Si el navegador bloquea el popup (ej. Safari
    // sin gesto de usuario directo), se cae al respaldo de navegación
    // completa de siempre.
    //
    // NOTA: se probó cambiar esto a redirección de página completa
    // (sospechando que el popup causaba el 403 de CloudFront visto en QA),
    // pero el 403 volvió a pasar igual sin popup -- confirmado que NO es la
    // causa. Se revierte a este comportamiento (con modal) porque es la
    // experiencia que se quiere conservar.
    function goToCheckoutPro(checkoutProUrl, thanksUrl) {
        fetch(checkoutProUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));

                if (!res.ok || !data.redirectUrl) {
                    throw new Error(data.message || 'No se pudo iniciar el pago.');
                }

                openCheckoutProPopup(data.redirectUrl, thanksUrl);
            })
            .catch((err) => {
                if (submitBtn) submitBtn.disabled = false;
                showError(err.message || 'No se pudo conectar con el servidor.');
            });
    }

    function openCheckoutProPopup(url, thanksUrl) {
        const width = Math.round(window.screen.availWidth * 0.7);
        const height = Math.round(window.screen.availHeight * 0.7);
        const left = window.screen.availLeft + Math.max(0, (window.screen.availWidth - width) / 2);
        const top = window.screen.availTop + Math.max(0, (window.screen.availHeight - height) / 2);
        const popup = window.open(
            url,
            'mp_checkout_pro',
            `width=${width},height=${height},left=${left},top=${top},noopener=no`
        );

        if (!popup) {
            // Bloqueado por el navegador -- respaldo: navegación completa,
            // mismo comportamiento que antes de tener popup.
            window.location.href = url;
            return;
        }

        // El popup navega dentro del dominio de Mercado Pago mientras el
        // cliente paga -- leer popup.location ahí truena por same-origin.
        // Solo se puede leer de nuevo cuando MP lo regresa a nuestro propio
        // dominio (back_urls de checkoutPro(), todas apuntan a thanksUrl) --
        // SOLO en ese momento se navega la pestaña principal. Si el cliente
        // cierra el popup a mano (con la "X") sin que eso haya pasado nunca,
        // NO se navega a ningún lado -- se queda en esta misma pestaña/paso
        // de Método de pago y se le avisa con un modal que el pago no se
        // completó, para que pueda reintentar de inmediato.
        let reachedThanks = false;
        const poll = setInterval(() => {
            if (popup.closed) {
                clearInterval(poll);
                if (!reachedThanks) {
                    if (submitBtn) submitBtn.disabled = false;
                    showPaymentIncompleteModal('Cerraste la ventana de Mercado Pago antes de terminar. No se realizó ningún cargo -- por favor vuelve a intentarlo.');
                }
                return;
            }

            if (!thanksUrl) return;

            let popupUrl = null;
            try {
                popupUrl = popup.location.href;
            } catch (e) {
                return; // Todavía en mercadopago.com -- seguir esperando.
            }

            if (popupUrl && popupUrl.indexOf(thanksUrl) === 0) {
                reachedThanks = true;
                clearInterval(poll);
                popup.close();

                // No se navega la pestaña principal todavía -- MP puede
                // regresar aquí tanto por un pago aprobado como por uno
                // rechazado (mismos 3 back_urls, ver checkoutPro()). Se le
                // pregunta primero al propio thanks() (en JSON, sin
                // renderizar nada) si el pago de verdad se completó antes de
                // decidir a dónde ir -- si falló, JAMÁS se navega a la página
                // de "confirmado", se avisa aquí mismo con el modal.
                fetch(popupUrl, { headers: { Accept: 'application/json' } })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.failed) {
                            if (submitBtn) submitBtn.disabled = false;
                            showPaymentIncompleteModal('Tu pago con Mercado Pago no se completó. No te preocupes, no se realizó ningún cargo -- por favor vuelve a intentarlo.');
                        } else {
                            window.location.href = popupUrl;
                        }
                    })
                    .catch(() => {
                        // No se pudo ni preguntar -- más seguro navegar a la
                        // página real (que reconcilia de nuevo desde cero)
                        // que dejar al cliente sin ningún resultado.
                        window.location.href = popupUrl;
                    });
            }
        }, 600);
    }

    // Reusa el mismo componente visual de .checkout-address-modal (ver
    // "¿Eliminar esta dirección?" en index.blade.php) en vez de inventar un
    // modal aparte -- mismo look & feel, cero CSS nuevo.
    function showPaymentIncompleteModal(message) {
        if (document.getElementById('mpPaymentIncompleteModal')) return; // ya visible -- no duplicar

        const overlay = document.createElement('div');
        overlay.id = 'mpPaymentIncompleteModal';
        overlay.className = 'checkout-address-modal';
        overlay.style.display = 'flex';
        overlay.innerHTML = `
            <div class="checkout-address-modal__backdrop" data-mp-modal-close></div>
            <div class="checkout-address-modal__box checkout-address-modal__box--sm">
                <div class="checkout-address-modal__icon checkout-address-modal__icon--danger">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="checkout-address-modal__title" style="text-align:center;">Tu pago no se completó</div>
                <p class="checkout-address-modal__text"></p>
                <div class="checkout-address-modal__actions">
                    <button type="button" class="checkout-submit" data-mp-modal-close>Entendido</button>
                </div>
            </div>
        `;
        overlay.querySelector('.checkout-address-modal__text').textContent = message;
        document.body.appendChild(overlay);

        const close = () => overlay.remove();
        overlay.querySelectorAll('[data-mp-modal-close]').forEach((el) => el.addEventListener('click', close));
    }

    document.querySelectorAll('input[name="payment_method_id"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            hideError();

            // Ancla de scroll: colapsar el panel de Tarjeta (CardForm, bastante
            // alto) al elegir Mercado Pago -- o viceversa -- encoge/estira el
            // contenido ARRIBA del scroll actual. El navegador conserva el
            // mismo scrollTop en píxeles, pero como el contenido de arriba ya
            // no mide lo mismo, ese píxel pasa a corresponder a otra parte de
            // la página -- se ve como si "la pantalla saltara" sin que el
            // usuario haya scrolleado. Se fija el radio recién elegido en el
            // mismo punto visual del viewport antes/después del cambio.
            const anchor = radio.closest('.checkout-payment-method-group') || radio;
            const beforeTop = anchor.getBoundingClientRect().top;

            syncFlowInput();
            syncSubmitLabel();
            syncPanels();
            if (currentFlow() === 'card') startCardFlow();

            // 2 pasadas: el clear de innerHTML es instantáneo (rAF ya lo
            // captura), pero el max-height de .mp-accordion-panel anima 0.25s
            // (ver checkout.css) -- una 2ª corrección tras la transición
            // atrapa el remanente que el primer frame todavía no reflejaba.
            const correct = () => {
                const afterTop = anchor.getBoundingClientRect().top;
                const delta = afterTop - beforeTop;
                if (delta !== 0) window.scrollBy(0, delta);
            };
            requestAnimationFrame(correct);
            setTimeout(correct, 300);
        });
    });
    syncFlowInput();
    syncSubmitLabel();
    syncPanels();

    form.addEventListener('submit', (e) => {
        const flow = currentFlow();
        if (!flow) return; // Otro método de pago -- deja el submit clásico seguir su curso.

        e.preventDefault();

        if (flow === 'card') {
            startCardFlow(); // Normalmente ya está en curso (actionsWrap oculto) -- guard por si acaso.
            return;
        }

        // flow === 'checkout_pro': crea el pedido + el cobro (mismo endpoint
        // de siempre, checkout.confirm) y, si todo sale bien, encadena el
        // 2º paso ya probado en vivo (checkoutPro()).
        hideError();
        if (submitBtn) submitBtn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: new FormData(form),
        })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    showError(data.message || 'No se pudo iniciar el pago. Intenta de nuevo.');
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }

                if (data.flow === 'checkout_pro' && data.checkoutProUrl) {
                    goToCheckoutPro(data.checkoutProUrl, data.thanksUrl);
                    return;
                }

                if (submitBtn) submitBtn.disabled = false;
            })
            .catch(() => {
                showError('No se pudo conectar con el servidor. Intenta de nuevo.');
                if (submitBtn) submitBtn.disabled = false;
            });
    });

    // Tarjeta es el sub-flujo pre-seleccionado por defecto -- en vez de
    // pedir un clic extra antes de mostrar el CardForm, se arma solo apenas
    // la sección de Pago queda abierta (llamado desde checkout-accordion.js,
    // con guard propio vía cardFormStarted para no relanzarse).
    window.CheckoutPaymentFlow = {
        autoStartIfMercadoPago() {
            syncPanels();
            if (currentFlow() === 'card') startCardFlow();
        },
    };
}

document.addEventListener('DOMContentLoaded', init);
