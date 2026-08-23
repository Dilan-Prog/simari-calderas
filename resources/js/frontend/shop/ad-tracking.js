// Tracking de visitantes publicitarios (gclid/wbraid/UTM) para poder subir
// conversiones offline a Google Ads más adelante. Todo el módulo vive dentro
// de un try/catch de nivel superior: si localStorage está bloqueado o
// cualquier otra cosa falla, el script debe quedarse callado y no romper
// ninguna página del shop.
(function () {
    try {
        var STORAGE_KEY = 'ad_visitor_uuid';
        var AD_PARAM_KEYS = [
            'gclid',
            'wbraid',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'utm_matchtype',
        ];

        function uuidv4() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }
            // Fallback simple para navegadores viejos sin crypto.randomUUID().
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = (Math.random() * 16) | 0;
                var v = c === 'x' ? r : (r & 0x3) | 0x8;
                return v.toString(16);
            });
        }

        function readAdParams() {
            var params = new URLSearchParams(window.location.search);
            var data = {};
            var hasAny = false;
            AD_PARAM_KEYS.forEach(function (key) {
                var value = params.get(key);
                if (value) {
                    data[key] = value;
                    hasAny = true;
                }
            });
            return { data: data, hasAny: hasAny };
        }

        // Devuelve la promesa del fetch (nunca rechazada -- el .catch() ya
        // absorbe cualquier error de red) para que quien llame pueda
        // encadenar sobre ella cuando el orden importa. Si fetch() ni
        // siquiera existe/lanza de forma síncrona, se resuelve igual: un
        // fallo de red en tracking nunca debe ser visible ni bloquear nada.
        function postJson(url, body, keepalive) {
            try {
                return fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify(body),
                    keepalive: !!keepalive,
                }).catch(function () {});
            } catch (err) {
                return Promise.resolve();
            }
        }

        // 1. Visitor UUID existente (si lo hay) + parámetros de ad en la URL actual.
        var visitorUuid = null;
        try {
            visitorUuid = window.localStorage.getItem(STORAGE_KEY);
        } catch (err) {
            visitorUuid = null;
        }

        var adParamsResult = readAdParams();
        var adParams = adParamsResult.data;
        var hasAdParams = adParamsResult.hasAny;
        var isNewVisitor = false;

        if (!visitorUuid && hasAdParams) {
            visitorUuid = uuidv4();
            isNewVisitor = true;
            try {
                window.localStorage.setItem(STORAGE_KEY, visitorUuid);
            } catch (err) {
                // localStorage bloqueado: seguimos con el uuid en memoria para
                // esta carga de página, solo no persiste entre páginas.
            }
        }

        // 2. Exponer el uuid actual de forma síncrona, ANTES de cualquier fetch,
        // para que otro código de la página (agregar al carrito, forms de
        // login/registro) lo pueda leer sin esperar ninguna promesa.
        window.__adVisitorUuid = visitorUuid || null;

        // 3. Poblar el input oculto de los forms de login/registro, si existe
        // en esta página. Se usa querySelectorAll en vez de getElementById
        // porque la vista de auth tiene DOS forms (login y registro) que
        // comparten el mismo id="visitorUuidField" -- getElementById solo
        // devolvería el primero, dejando el otro form sin el dato al enviarlo.
        if (window.__adVisitorUuid) {
            var hiddenFields = document.querySelectorAll('[id="visitorUuidField"]');
            hiddenFields.forEach(function (field) {
                field.value = window.__adVisitorUuid;
                field.setAttribute('value', window.__adVisitorUuid);
            });
        }

        // 4. Reportar la visita: uuid nuevo recién creado, o uuid ya existente
        // que trae parámetros de ad de nuevo (el backend decide qué hacer con
        // el last-touch, aquí solo se manda la data).
        //
        // IMPORTANTE: si se manda este POST, el evento page_view del punto 5
        // debe esperar a que ESTE termine antes de dispararse. Si ambos
        // salieran en paralelo, en un visitante nuevo la fila de AdVisit
        // podría no existir todavía en el servidor cuando llega el POST del
        // evento -- y como un evento huérfano se descarta en silencio (nunca
        // debe fallar visiblemente), el page_view automático se perdería en
        // la mayoría de las visitas nuevas. Bug real detectado en producción:
        // visitas creadas correctamente pero siempre con "0 eventos".
        var visitPromise = Promise.resolve();
        if (visitorUuid && (isNewVisitor || hasAdParams)) {
            var visitBody = Object.assign(
                { visitor_uuid: visitorUuid, landing_url: window.location.href },
                adParams
            );
            visitPromise = postJson('/api/v1/ad-tracking/visit', visitBody, false);
        }

        // 5. page_view automático en cualquier página donde ya tengamos uuid.
        // Encadenado sobre visitPromise -- ver nota del punto 4.
        if (visitorUuid) {
            visitPromise.then(function () {
                postJson(
                    '/api/v1/ad-tracking/event',
                    {
                        visitor_uuid: visitorUuid,
                        event_type: 'page_view',
                        url: window.location.href,
                        product_id: null,
                    },
                    false
                );
            });
        }

        // 6. API pública reutilizable para el resto del sitio.
        window.AdTracking = {
            track: function (eventType, opts) {
                try {
                    if (!window.__adVisitorUuid) return; // evita eventos huérfanos
                    opts = opts || {};
                    postJson(
                        '/api/v1/ad-tracking/event',
                        {
                            visitor_uuid: window.__adVisitorUuid,
                            event_type: eventType,
                            url: window.location.href,
                            product_id: opts.productId != null ? opts.productId : null,
                        },
                        true // keepalive: estos eventos suelen ir de la mano de una
                        // navegación (WhatsApp en pestaña nueva, checkout) y no deben
                        // ser cancelados por el navegador.
                    );
                } catch (err) {
                    // Un evento de tracking nunca debe romper la interacción del usuario.
                }
            },
        };

        // 7. Listener delegado único a nivel documento para cualquier elemento
        // marcado con data-ad-track (botón de WhatsApp flotante, "Solicitar
        // cotización" en ficha de producto y en tarjetas de catálogo, etc.).
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-ad-track]');
            if (!el) return;
            window.AdTracking.track(el.dataset.adTrack, { productId: el.dataset.productId || null });
        });
    } catch (err) {
        // Cualquier fallo inesperado aquí (localStorage bloqueado, API no
        // soportada, etc.) no debe generar errores visibles ni romper la página.
    }
})();
