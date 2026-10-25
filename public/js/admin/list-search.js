/**
 * Buscador de listados del admin (Productos, Editar en lote, Colecciones).
 *
 * Envía el formulario GET al escribir (con retraso), al presionar Enter o al
 * limpiar, y devuelve el foco al campo después de la recarga para poder seguir
 * escribiendo sin tener que volver a hacer clic.
 *
 * initListSearch({
 *   input:      HTMLInputElement,
 *   form:       HTMLFormElement,
 *   clearBtn:   HTMLElement|null   botón "×" (se muestra solo si hay texto),
 *   storageKey: string             llave de sessionStorage para devolver el foco,
 *   delay:      number             milisegundos de espera tras escribir (450),
 *   canSubmit:  () => boolean      false = no recargar (p. ej. cambios sin guardar),
 * })
 */
(function (global) {
    function initListSearch(opts) {
        var input = opts.input;
        var form = opts.form;
        if (!input || !form) return;

        var clearBtn = opts.clearBtn || null;
        var storageKey = opts.storageKey || 'listSearchFocus';
        var delay = typeof opts.delay === 'number' ? opts.delay : 450;
        var canSubmit = typeof opts.canSubmit === 'function' ? opts.canSubmit : function () { return true; };
        var timer = null;
        var initial = input.value;

        function store(value) {
            try {
                if (value) sessionStorage.setItem(storageKey, '1');
                else sessionStorage.removeItem(storageKey);
            } catch (e) { /* sessionStorage no disponible */ }
        }

        function stored() {
            try { return sessionStorage.getItem(storageKey) === '1'; } catch (e) { return false; }
        }

        function syncClear() {
            if (clearBtn) clearBtn.hidden = input.value === '';
        }

        function submit() {
            clearTimeout(timer);
            if (!canSubmit()) return;
            if (input.value.trim() === initial.trim()) return; // nada nuevo que buscar
            store(true);
            // requestSubmit respeta los demás campos del formulario (categoría, stock, estado, más filtros…).
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        }

        input.addEventListener('input', function () {
            syncClear();
            clearTimeout(timer);
            timer = setTimeout(submit, delay);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                // Enter busca ya (sin esperar el retraso); el formulario no se envía dos veces.
                e.preventDefault();
                submit();
            } else if (e.key === 'Escape' && input.value !== '') {
                e.preventDefault();
                input.value = '';
                syncClear();
                submit();
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (!canSubmit()) return;
                input.value = '';
                syncClear();
                if (initial.trim() === '') { input.focus(); return; }
                store(true);
                clearTimeout(timer);
                if (typeof form.requestSubmit === 'function') form.requestSubmit();
                else form.submit();
            });
        }

        syncClear();

        // Tras la recarga provocada por escribir, el foco vuelve al final del texto.
        if (stored()) {
            store(false);
            if (!input.disabled) {
                input.focus();
                var end = input.value.length;
                try { input.setSelectionRange(end, end); } catch (e) { /* tipo no soportado */ }
            }
        }
    }

    global.initListSearch = initListSearch;
})(window);
