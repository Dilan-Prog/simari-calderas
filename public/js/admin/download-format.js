/**
 * Modal genérico "Elige el formato de descarga" (PDF / Excel / Hoja de
 * cálculo). Vanilla JS plano, sin build step — se sirve directo desde
 * public/js/admin/, igual que public/js/admin/column-visibility.js.
 *
 * Requiere que la vista incluya una sola vez el partial
 * admin.components._download_format_modal (id #downloadFormatModal).
 *
 * Uso: cualquier elemento clickeable con el atributo
 *
 *   data-download-url="{{ route('admin.algo.export') }}"
 *
 * abre el modal al hacer click. Al elegir un formato, navega a
 * "<data-download-url>?format=pdf|xlsx|sheets" (o "&format=..." si la URL
 * base ya trae query string) mediante window.location.href, para que el
 * navegador dispare la descarga normal (sin fetch/AJAX).
 *
 *   <script src="{{ asset('js/admin/download-format.js') }}"></script>
 *   <script>initDownloadFormatModal();</script>
 */
function initDownloadFormatModal() {
    'use strict';

    if (window.__downloadFormatModalInitialized) {
        return;
    }

    var modal = document.getElementById('downloadFormatModal');
    if (!modal) {
        return;
    }

    window.__downloadFormatModalInitialized = true;

    var cancelBtn = document.getElementById('downloadFormatCancel');
    var closeBtn = document.getElementById('downloadFormatCloseBtn');
    var pendingUrl = null;

    function openModal(url) {
        pendingUrl = url;
        modal.classList.add('active');
    }

    function closeModal() {
        modal.classList.remove('active');
        pendingUrl = null;
    }

    function buildUrl(baseUrl, format) {
        var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
        return baseUrl + separator + 'format=' + encodeURIComponent(format);
    }

    // Listener delegado: cubre cualquier botón/link con data-download-url,
    // incluyendo los que se agreguen dinámicamente después (tablas
    // renderizadas por JS, filas nuevas, etc.).
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest ? e.target.closest('[data-download-url]') : null;
        if (trigger) {
            e.preventDefault();
            openModal(trigger.getAttribute('data-download-url'));
            return;
        }

        var formatBtn = e.target.closest ? e.target.closest('.download-format-btn') : null;
        if (formatBtn && modal.contains(formatBtn)) {
            var format = formatBtn.dataset.format;
            if (pendingUrl && format) {
                window.location.href = buildUrl(pendingUrl, format);
            }
            closeModal();
            return;
        }

        if (e.target === modal) {
            // Click fuera de la caja (directamente sobre el overlay).
            closeModal();
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
}
