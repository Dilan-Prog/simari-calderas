/**
 * email-template-editor.js
 *
 * Editor de plantillas de correo (create/edit):
 *  - Token picker: inserta el texto del token en la posición actual
 *    del cursor del textarea (sin librerías: selectionStart/selectionEnd
 *    nativos).
 *  - Preview: en modo creación inyecta el HTML crudo del textarea tal
 *    cual (sin resolver tokens). En modo edición hace fetch al endpoint
 *    de preview del backend, que resuelve los tokens {{contact.*}}/
 *    {{deal.*}} contra un Customer de ejemplo
 *    (EmailTemplateController::preview -> EmailTemplateService::render).
 *
 * Config leída desde data-attributes en #emailTemplateForm (ver
 * partials/_form.blade.php):
 *   data-is-edit="1|0"
 *   data-preview-url="..."   (solo presente en modo edición)
 */
(function () {
    const form = document.getElementById('emailTemplateForm');
    if (!form) return;

    const textarea = document.getElementById('etHtmlBody');
    const previewFrame = document.getElementById('etPreviewFrame');
    const isEdit = form.dataset.isEdit === '1';
    const previewUrl = form.dataset.previewUrl || null;

    // ── Token picker ──────────────────────────────────────────────
    document.querySelectorAll('.et-token-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const token = btn.dataset.token;
            const start = textarea.selectionStart ?? textarea.value.length;
            const end = textarea.selectionEnd ?? textarea.value.length;
            const before = textarea.value.slice(0, start);
            const after = textarea.value.slice(end);

            textarea.value = before + token + after;

            // Deja el cursor justo después del token insertado.
            const cursorPos = start + token.length;
            textarea.focus();
            textarea.setSelectionRange(cursorPos, cursorPos);

            // Dispara 'input' para que el preview se refresque igual que si
            // el usuario hubiera tecleado el token a mano.
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });

    // ── Preview ───────────────────────────────────────────────────
    let debounceTimer = null;

    if (!isEdit) {
        // Modo creación: sin backend todavía (la plantilla no existe en BD),
        // así que el preview solo inyecta el HTML crudo del textarea tal
        // cual, SIN resolver tokens. Ver nota en _form.blade.php.
        const renderRawPreview = () => {
            previewFrame.srcdoc = textarea.value || '<p style="font-family:sans-serif;color:#9CA3AF;padding:16px;">Escribe HTML en el editor para verlo aquí.</p>';
        };

        textarea.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(renderRawPreview, 300);
        });

        renderRawPreview();
        return;
    }

    // Modo edición: preview real vía fetch al backend, que resuelve los
    // tokens {{contact.*}}/{{deal.*}} contra un Customer de ejemplo.
    const previewSubjectEl = document.getElementById('etPreviewSubject');
    const refreshBtn = document.getElementById('etRefreshPreview');

    const fetchPreview = async () => {
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.textContent = 'Actualizando…';
        }

        try {
            const response = await fetch(previewUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) throw new Error('preview request failed');

            const data = await response.json();
            previewSubjectEl.textContent = 'Asunto: ' + (data.subject ?? '—');
            previewFrame.srcdoc = data.html ?? '';
        } catch (err) {
            console.error('Error al generar la vista previa:', err);
            previewSubjectEl.textContent = 'No se pudo generar la vista previa.';
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Actualizar vista previa';
            }
        }
    };

    // Nota: la vista previa se genera contra el html_body GUARDADO en BD
    // (la ruta de preview no recibe el contenido actual del textarea), así
    // que refleja la última versión guardada, no cambios sin guardar en el
    // formulario. Por eso se recarga al abrir la página y con el botón
    // manual, en vez de re-fetch en cada tecla.
    fetchPreview();
    if (refreshBtn) refreshBtn.addEventListener('click', fetchPreview);
})();
