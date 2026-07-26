/* Variables/placeholders ({marca}, {modelo}, ...) en Descripción corta y
 * larga de productos. Catálogo inyectado por la vista como
 * window.PRODUCT_VARIABLES (Products::VARIABLE_CATALOG) — misma fuente de
 * verdad que resolveVariables() en el modelo, así el menú nunca se
 * desincroniza de lo que realmente se sustituye en público.
 *
 * Textareas: se adjunta por delegación de eventos (funciona igual en el
 * campo único de crear/editar y en las N celdas dinámicas del editor en
 * lote). Quill: requiere la instancia real, así que cada vista la registra
 * explícitamente con window.VariablePicker.attachQuill(quill, containerId)
 * justo después de crearla.
 */
/* Ejecuta de inmediato (no espera DOMContentLoaded): este archivo se carga
 * como script tipo módulo, que el navegador difiere hasta después de que
 * el HTML termina de parsearse — es decir, DESPUÉS de que ya corrieron los
 * <script> clásicos inline de cada vista (Quill, etc). Si expusiéramos
 * window.VariablePicker dentro de un listener de DOMContentLoaded, un
 * listener de DOMContentLoaded registrado antes por uno de esos scripts
 * clásicos se ejecutaría primero y encontraría window.VariablePicker aún
 * indefinido. */
(function () {
    const CATALOG = window.PRODUCT_VARIABLES || [];
    if (!CATALOG.length) return;

    // Campos de texto/textarea elegibles para variables: descripciones,
    // nombre del producto y los campos del modal SEO — tanto en el
    // formulario individual (por id) como en las celdas del editor en
    // lote (por data-field, ya que se repiten una vez por producto).
    const TEXTAREA_SELECTOR = [
        '#pformShortDesc',
        '#pformName',
        '#pformSeoTitle',
        '#pformSeoMeta',
        '#pformOgTitle',
        '#pformOgDescription',
        '.prod-bulk-textarea-cell[data-field="short_description"]',
        '.prod-bulk-textarea-cell[data-field="description"]',
        '.prod-bulk-input[data-field="name"]',
        '.prod-bulk-input[data-field="seo_title"]',
        '.prod-bulk-textarea-cell[data-field="seo_description"]',
        '.prod-bulk-input[data-field="og_title"]',
        '.prod-bulk-textarea-cell[data-field="og_description"]',
        '.pform-faq-question',
        '.pform-faq-answer',
        '.bulk-faq-question',
        '.bulk-faq-answer',
    ].join(', ');

    const quillRegistry = {};
    let lastFocusedTextarea = null;
    // deleteText()+insertText() son dos operaciones separadas: entre una y
    // otra, el propio listener de 'text-change' vería el texto SIN la "{"
    // (ya borrada) y cerraría el menú (anulando activeField) antes de que
    // insertText llegue a ejecutarse. Esta bandera evita que el listener
    // reaccione a esos dos cambios intermedios.
    let suppressQuillChange = false;

    let menu = null;
    let activeField = null; // { type: 'textarea', el, bracketIndex, replaceLength } | { type: 'quill', quill, bracketIndex, replaceLength }
    let activeIndex = -1;
    let currentItems = [];

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function filterCatalog(term) {
        if (!term) return CATALOG;
        const t = term.toLowerCase();
        return CATALOG.filter((v) => v.key.toLowerCase().includes(t) || v.label.toLowerCase().includes(t));
    }

    function ensureMenu() {
        if (!menu) {
            menu = document.createElement('ul');
            menu.className = 'pform-variable-menu';
            document.body.appendChild(menu);
        }
        return menu;
    }

    function closeMenu() {
        if (menu) {
            menu.classList.remove('is-open');
            menu.innerHTML = '';
        }
        activeField = null;
        activeIndex = -1;
        currentItems = [];
    }

    function positionMenu(coords) {
        menu.style.left = Math.round(coords.left) + 'px';
        menu.style.top = Math.round(coords.top) + 'px';
    }

    function openMenu(items, coords) {
        if (!items.length) {
            closeMenu();
            return;
        }
        const el = ensureMenu();
        currentItems = items;
        activeIndex = -1;
        el.innerHTML = items
            .map(
                (v) => `<li class="pform-variable-item" data-key="${escapeHtml(v.key)}">` +
                    `<span class="pform-variable-item-label">{${escapeHtml(v.key)}}</span>` +
                    `<span class="pform-variable-item-desc">${escapeHtml(v.description)}</span>` +
                    `</li>`
            )
            .join('');
        positionMenu(coords);
        el.classList.add('is-open');

        Array.from(el.children).forEach((li, i) => {
            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                applySelection(items[i].key);
            });
        });
    }

    function highlight(index) {
        activeIndex = index;
        Array.from(menu.children).forEach((it, i) => it.classList.toggle('is-active', i === activeIndex));
    }

    function getCaretCoordinates(textarea, position) {
        const style = getComputedStyle(textarea);
        const mirror = document.createElement('div');
        const props = [
            'boxSizing', 'width', 'overflowX', 'overflowY',
            'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'borderStyle',
            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'fontStyle', 'fontVariant', 'fontWeight', 'fontSize', 'lineHeight', 'fontFamily',
            'textAlign', 'textTransform', 'textIndent', 'letterSpacing', 'wordSpacing',
            'whiteSpace', 'wordWrap', 'wordBreak',
        ];
        mirror.style.position = 'absolute';
        mirror.style.visibility = 'hidden';
        mirror.style.whiteSpace = 'pre-wrap';
        mirror.style.wordWrap = 'break-word';
        mirror.style.top = '0';
        mirror.style.left = '-9999px';
        props.forEach((p) => {
            mirror.style[p] = style[p];
        });
        document.body.appendChild(mirror);

        mirror.textContent = textarea.value.substring(0, position);
        const marker = document.createElement('span');
        marker.textContent = textarea.value.substring(position) || '.';
        mirror.appendChild(marker);

        const rect = textarea.getBoundingClientRect();
        const markerRect = marker.getBoundingClientRect();
        const mirrorRect = mirror.getBoundingClientRect();
        const lineHeight = parseInt(style.lineHeight, 10) || 20;

        const coords = {
            top: rect.top + (markerRect.top - mirrorRect.top) - textarea.scrollTop + lineHeight,
            left: rect.left + (markerRect.left - mirrorRect.left) - textarea.scrollLeft,
        };

        document.body.removeChild(mirror);

        return coords;
    }

    function getQuillCaretCoordinates(quill, index) {
        const bounds = quill.getBounds(index);
        const editorRect = quill.root.getBoundingClientRect();

        return {
            top: editorRect.top + bounds.bottom + 4,
            left: editorRect.left + bounds.left,
        };
    }

    function applySelection(key) {
        if (!activeField) return;

        if (activeField.type === 'textarea') {
            const el = activeField.el;
            const before = el.value.slice(0, activeField.bracketIndex);
            const after = el.value.slice(activeField.bracketIndex + activeField.replaceLength);
            const token = `{${key}}`;
            el.value = before + token + after;
            const newCursor = activeField.bracketIndex + token.length;
            el.focus();
            el.setSelectionRange(newCursor, newCursor);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        } else if (activeField.type === 'quill') {
            const quill = activeField.quill;
            const { bracketIndex, replaceLength } = activeField;
            const token = `{${key}}`;
            suppressQuillChange = true;
            quill.deleteText(bracketIndex, replaceLength, 'user');
            quill.insertText(bracketIndex, token, 'user');
            quill.setSelection(bracketIndex + token.length, 0, 'user');
            suppressQuillChange = false;
        }

        closeMenu();
    }

    /* ── Typeahead: escribir "{" + letras en un textarea ── */
    function handleTextareaTypeahead(el) {
        const cursor = el.selectionStart;
        const before = el.value.slice(0, cursor);
        const match = before.match(/\{([a-zA-Z_]*)$/);

        if (!match) {
            if (activeField && activeField.type === 'textarea' && activeField.el === el) closeMenu();
            return;
        }

        const items = filterCatalog(match[1]);
        activeField = { type: 'textarea', el, bracketIndex: cursor - match[0].length, replaceLength: match[0].length };
        openMenu(items, getCaretCoordinates(el, cursor));
    }

    document.addEventListener('focusin', function (e) {
        if (e.target.matches(TEXTAREA_SELECTOR)) lastFocusedTextarea = e.target;
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches(TEXTAREA_SELECTOR)) handleTextareaTypeahead(e.target);
    });

    document.addEventListener('keydown', function (e) {
        if (!menu || !menu.classList.contains('is-open')) return;
        if (activeField && activeField.type === 'textarea' && activeField.el !== e.target) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlight(Math.min(activeIndex + 1, currentItems.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlight(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Escape') {
            closeMenu();
        } else if (e.key === 'Enter' && activeIndex >= 0 && currentItems[activeIndex]) {
            e.preventDefault();
            applySelection(currentItems[activeIndex].key);
        }
    }, true);

    document.addEventListener('mousedown', function (e) {
        if (menu && !e.target.closest('.pform-variable-menu') && !e.target.matches(TEXTAREA_SELECTOR)) {
            closeMenu();
        }
    });

    window.addEventListener(
        'scroll',
        function () {
            if (!activeField || !menu || !menu.classList.contains('is-open')) return;
            if (activeField.type === 'textarea') {
                positionMenu(getCaretCoordinates(activeField.el, activeField.el.selectionStart));
            }
        },
        true
    );

    /* ── Botón "Insertar variable" ── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.pform-insert-variable-btn');
        if (!btn) return;
        e.preventDefault();

        const target = btn.dataset.variableTarget;

        if (target === 'last-focused') {
            if (!lastFocusedTextarea || !document.body.contains(lastFocusedTextarea)) {
                alert('Selecciona primero un campo de descripción para insertar la variable.');
                return;
            }
            const el = lastFocusedTextarea;
            el.focus();
            const cursor = el.selectionStart;
            activeField = { type: 'textarea', el, bracketIndex: cursor, replaceLength: 0 };
            openMenu(CATALOG, getCaretCoordinates(el, cursor));
            return;
        }

        if (target.startsWith('quill:')) {
            const entry = quillRegistry[target.slice('quill:'.length)];
            if (!entry) return;
            entry.quill.focus();
            let sel = entry.quill.getSelection();
            if (!sel) sel = { index: entry.quill.getLength() };
            activeField = { type: 'quill', quill: entry.quill, bracketIndex: sel.index, replaceLength: 0 };
            openMenu(CATALOG, getQuillCaretCoordinates(entry.quill, sel.index));
            return;
        }

        if (target === 'faq-answer') {
            const row = btn.closest('.pform-faq-fields, .bulk-faq-fields');
            const el = row ? row.querySelector('.pform-faq-answer, .bulk-faq-answer') : null;
            if (!el) return;
            el.focus();
            const cursor = el.selectionStart;
            activeField = { type: 'textarea', el, bracketIndex: cursor, replaceLength: 0 };
            openMenu(CATALOG, getCaretCoordinates(el, cursor));
            return;
        }

        const el = document.getElementById(target);
        if (!el) return;
        el.focus();
        const cursor = el.selectionStart;
        activeField = { type: 'textarea', el, bracketIndex: cursor, replaceLength: 0 };
        openMenu(CATALOG, getCaretCoordinates(el, cursor));
    });

    /* ── Botón "Insertar enlace" (solo preguntas frecuentes) ──
       Inserta la sintaxis [texto](url), que TextLinks::render() convierte
       en un <a> real al mostrarse en público (ver app/Support/TextLinks.php).
       A diferencia del menú de variables, aquí primero se elige un TIPO de
       destino y luego se busca/selecciona un elemento concreto. */
    const LINK_TYPES = [
        { type: 'product', label: 'Producto' },
        { type: 'collection', label: 'Colección' },
        { type: 'category', label: 'Categoría / Catálogo' },
        { type: 'contact', label: 'Contacto' },
    ];

    let linkPanel = null;
    let linkTarget = null;
    let linkBracketIndex = 0;
    let linkSearchTimer = null;
    let linkRequestId = 0;

    function ensureLinkPanel() {
        if (!linkPanel) {
            linkPanel = document.createElement('div');
            linkPanel.className = 'pform-link-picker';
            document.body.appendChild(linkPanel);
        }
        return linkPanel;
    }

    function closeLinkPanel() {
        if (linkPanel) {
            linkPanel.classList.remove('is-open');
            linkPanel.innerHTML = '';
        }
        linkTarget = null;
    }

    function positionLinkPanel(el) {
        const rect = el.getBoundingClientRect();
        linkPanel.style.left = Math.round(rect.left) + 'px';
        linkPanel.style.top = Math.round(rect.bottom + 4) + 'px';
    }

    function insertLink(label, url) {
        if (!linkTarget) return;
        const token = `[${label}](${url})`;
        const before = linkTarget.value.slice(0, linkBracketIndex);
        const after = linkTarget.value.slice(linkBracketIndex);
        linkTarget.value = before + token + after;
        const newCursor = linkBracketIndex + token.length;
        linkTarget.focus();
        linkTarget.setSelectionRange(newCursor, newCursor);
        linkTarget.dispatchEvent(new Event('input', { bubbles: true }));
        closeLinkPanel();
    }

    function renderLinkTypeStep() {
        const panel = ensureLinkPanel();
        panel.innerHTML =
            '<div class="pform-link-picker-title">Insertar enlace hacia…</div>' +
            LINK_TYPES.map((t) => `<button type="button" class="pform-link-picker-type" data-type="${t.type}">${t.label}</button>`).join('');

        panel.querySelectorAll('.pform-link-picker-type').forEach((btn) => {
            btn.addEventListener('click', function () {
                if (this.dataset.type === 'contact') {
                    insertLink('Contáctanos', '/contacto');
                } else {
                    renderLinkSearchStep(this.dataset.type);
                }
            });
        });
    }

    function renderLinkSearchStep(type) {
        const panel = ensureLinkPanel();
        const typeLabel = LINK_TYPES.find((t) => t.type === type)?.label ?? '';
        panel.innerHTML =
            '<div class="pform-link-picker-title">' +
            `<button type="button" class="pform-link-picker-back">←</button> ${typeLabel}` +
            '</div>' +
            '<input type="text" class="pform-input pform-link-picker-search" placeholder="Buscar...">' +
            '<ul class="pform-link-picker-results"></ul>';

        panel.querySelector('.pform-link-picker-back').addEventListener('click', renderLinkTypeStep);

        const input = panel.querySelector('.pform-link-picker-search');
        const results = panel.querySelector('.pform-link-picker-results');

        async function search(term) {
            const myRequestId = ++linkRequestId;
            try {
                const res = await fetch(`/admin/productos/faq-enlaces/buscar?type=${type}&q=${encodeURIComponent(term)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok || myRequestId !== linkRequestId) return;
                const items = await res.json();
                results.innerHTML = items
                    .map((it) => `<li class="pform-link-picker-item" data-label="${escapeHtml(it.label)}" data-url="${escapeHtml(it.url)}">${escapeHtml(it.label)}</li>`)
                    .join('') || '<li class="pform-link-picker-empty">Sin resultados</li>';

                results.querySelectorAll('.pform-link-picker-item').forEach((li) => {
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        insertLink(this.dataset.label, this.dataset.url);
                    });
                });
            } catch (err) {
                console.error('Error buscando destino de enlace:', err);
            }
        }

        input.addEventListener('input', function () {
            clearTimeout(linkSearchTimer);
            linkSearchTimer = setTimeout(() => search(this.value.trim()), 250);
        });
        input.focus();
        search('');
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.pform-insert-link-btn');
        if (!btn) return;
        e.preventDefault();

        const row = btn.closest('.pform-faq-fields, .bulk-faq-fields');
        const target = row ? row.querySelector('.pform-faq-answer, .bulk-faq-answer') : null;
        if (!target) return;

        target.focus();
        linkTarget = target;
        linkBracketIndex = target.selectionStart;
        ensureLinkPanel().classList.add('is-open');
        positionLinkPanel(target);
        renderLinkTypeStep();
    });

    document.addEventListener('mousedown', function (e) {
        if (linkPanel && linkPanel.classList.contains('is-open') && !e.target.closest('.pform-link-picker') && !e.target.closest('.pform-insert-link-btn')) {
            closeLinkPanel();
        }
    });

    /* ── API pública para el adaptador de Quill ── */
    window.VariablePicker = {
        attachQuill: function (quill, containerId) {
            quillRegistry[containerId] = { quill };

            quill.on('text-change', function (delta, oldDelta, source) {
                if (suppressQuillChange || source !== 'user') return;

                const sel = quill.getSelection();
                if (!sel) {
                    closeMenu();
                    return;
                }

                const before = quill.getText(0, sel.index);
                const match = before.match(/\{([a-zA-Z_]*)$/);

                if (!match) {
                    if (activeField && activeField.type === 'quill' && activeField.quill === quill) closeMenu();
                    return;
                }

                const items = filterCatalog(match[1]);
                activeField = { type: 'quill', quill, bracketIndex: sel.index - match[0].length, replaceLength: match[0].length };
                openMenu(items, getQuillCaretCoordinates(quill, sel.index));
            });

            quill.root.addEventListener('keydown', function (e) {
                if (!menu || !menu.classList.contains('is-open')) return;
                if (!activeField || activeField.type !== 'quill' || activeField.quill !== quill) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    e.stopPropagation();
                    highlight(Math.min(activeIndex + 1, currentItems.length - 1));
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    e.stopPropagation();
                    highlight(Math.max(activeIndex - 1, 0));
                } else if (e.key === 'Escape') {
                    closeMenu();
                } else if (e.key === 'Enter' && activeIndex >= 0 && currentItems[activeIndex]) {
                    e.preventDefault();
                    e.stopPropagation();
                    applySelection(currentItems[activeIndex].key);
                }
            }, true);
        },
    };
})();
