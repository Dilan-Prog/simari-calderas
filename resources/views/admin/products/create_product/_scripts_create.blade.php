@push('scripts')
    <script>window.PRODUCT_VARIABLES = @json(\App\Models\Products::VARIABLE_CATALOG);</script>
    <script>window.PFORM_IVA_RATE = @json(\App\Models\Products::ivaRate());</script>
    <script>window.PFORM_EXCHANGE_RATE = @json(\App\Models\Products::exchangeRate());</script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script>
        (function() {

            /* ── Tab switching ── */
            const tabs = document.querySelectorAll('.pform-tab');
            const panels = document.querySelectorAll('.pform-tab-panel');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    panels.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    document.getElementById(this.dataset.tab).classList.add('active');
                });
            });

            /* ── Toggle "¿Es la URL Canónica?" — muestra/oculta el campo de
               URL Canónica según el checkbox (misma lógica que edit.blade.php). */
            const isCanonicalCheckbox = document.getElementById('pformIsCanonical');
            const canonicalUrlWrap = document.getElementById('pformCanonicalUrlWrap');
            const canonicalUrlInput = document.getElementById('pformCanonicalUrl');
            if (isCanonicalCheckbox && canonicalUrlWrap) {
                isCanonicalCheckbox.addEventListener('change', () => {
                    canonicalUrlWrap.style.display = isCanonicalCheckbox.checked ? 'none' : '';
                    if (isCanonicalCheckbox.checked && canonicalUrlInput) {
                        canonicalUrlInput.value = '';
                    }
                });
            }

            /* ── Character counter (short desc) ── */
            const shortDesc = document.getElementById('pformShortDesc');
            const charCount = document.getElementById('pformCharCount');
            shortDesc.addEventListener('input', function() {
                charCount.textContent = this.value.length + '/200';
            });

            /* ── Quill editor ── */
            const quillInstance = new Quill('#pformQuillEditor', {
                theme: 'snow',
                placeholder: 'Escribe la descripción detallada del producto. Puedes incluir títulos, listas, tablas, imágenes y enlaces...',
                modules: {
                    toolbar: [
                        [{
                            header: [1, 2, 3, 4, 5, 6, false]
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{
                            list: 'ordered'
                        }, {
                            list: 'bullet'
                        }],
                        [{
                            align: []
                        }],
                        ['link', 'image'],
                        [{
                            color: []
                        }, {
                            background: []
                        }],
                        ['clean'],
                    ]
                }
            });

            // Re-carga la descripción si la página se está re-renderizando
            // tras un error de validación del servidor (withErrors()) —
            // old('description') viaja en el input oculto pformDescHidden.
            const oldDescription = document.getElementById('pformDescHidden').value;
            if (oldDescription) quillInstance.setText(oldDescription);

            // variable-picker.js es un script tipo módulo (diferido): se
            // ejecuta después de este script inline clásico, por eso el
            // registro espera a DOMContentLoaded en vez de comprobar
            // window.VariablePicker aquí directamente (llegaría undefined).
            document.addEventListener('DOMContentLoaded', function() {
                window.VariablePicker.attachQuill(quillInstance, 'pformQuillEditor');
            });

            /* ── Back button ── */
            document.getElementById('pformBackBtn').addEventListener('click', function() {
                window.location.href = '/admin/productos';
            });

            /* ── Validation helpers ── */
            function getErrorAnchor(el) {
                return (el.parentElement && el.parentElement.classList.contains('pform-price-wrap')) ?
                    el.parentElement :
                    el;
            }

            function showFieldError(el, msg) {
                el.classList.add('pform-field-error');
                const anchor = getErrorAnchor(el);
                if (!anchor.nextElementSibling || !anchor.nextElementSibling.classList.contains('pform-error-msg')) {
                    const p = document.createElement('p');
                    p.className = 'pform-error-msg';
                    p.textContent = msg;
                    anchor.insertAdjacentElement('afterend', p);
                }
                const clearFn = () => {
                    el.classList.remove('pform-field-error');
                    const errEl = getErrorAnchor(el).nextElementSibling;
                    if (errEl && errEl.classList.contains('pform-error-msg')) errEl.remove();
                };
                el.addEventListener('input', clearFn, {
                    once: true
                });
                el.addEventListener('change', clearFn, {
                    once: true
                });
            }

            function clearAllErrors() {
                document.querySelectorAll('.pform-field-error').forEach(el => el.classList.remove('pform-field-error'));
                document.querySelectorAll('.pform-error-msg').forEach(el => el.remove());
            }

            function switchToPanel(panelId) {
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                document.getElementById(panelId).classList.add('active');
                tabs.forEach(t => {
                    if (t.dataset.tab === panelId) t.classList.add('active');
                });
            }

            function validateForm() {
                clearAllErrors();
                const required = [{
                        el: document.getElementById('pformName'),
                        msg: 'El nombre del producto es obligatorio',
                        panel: 'pformPanel0'
                    },
                    {
                        el: document.getElementById('pformCost'),
                        msg: 'El costo del artículo es obligatorio',
                        panel: 'pformPanel2'
                    },
                    {
                        el: document.getElementById('pformPrice'),
                        msg: 'El precio de venta es obligatorio',
                        panel: 'pformPanel2'
                    },
                    {
                        el: document.querySelector('[name="stock"]'),
                        msg: 'El inventario disponible es obligatorio',
                        panel: 'pformPanel2'
                    },
                    {
                        el: document.getElementById('pformCategoryMain'),
                        msg: 'La categoría principal es obligatoria',
                        panel: 'pformPanel3'
                    },
                    {
                        el: document.querySelector('select[name="brand_id"]'),
                        msg: 'La marca es obligatoria',
                        panel: 'pformPanel0'
                    },
                ];

                let firstErrorEl = null;
                let firstErrorPanel = null;

                required.forEach(({
                    el,
                    msg,
                    panel
                }) => {
                    if (!el) return;
                    const empty = el.tagName === 'SELECT' ? !el.value : !el.value.trim();
                    if (empty) {
                        showFieldError(el, msg);
                        if (!firstErrorEl) {
                            firstErrorEl = el;
                            firstErrorPanel = panel;
                        }
                    }
                });

                if (firstErrorEl) {
                    switchToPanel(firstErrorPanel);
                    setTimeout(() => firstErrorEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    }), 100);
                    return false;
                }
                return true;
            }

            /* ── Save draft ── */
            document.getElementById('pformBtnDraft').addEventListener('click', function() {
                if (!validateForm()) return;
                document.getElementById('pformDescHidden').value = quillInstance.getText().trim();
                document.getElementById('productCreateForm').querySelector('[name=is_active]').value = 0;
                const badge = document.getElementById('pformSavedBadge');
                badge.style.color = '#16a34a';
                badge.textContent = '✓ Guardado';
                badge.classList.add('visible');
                clearTimeout(badge._timer);
                badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
                document.getElementById('productCreateForm').submit();
            });

            /* ── Publish ── */
            document.getElementById('pformBtnPublish').addEventListener('click', function() {
                if (!validateForm()) return;
                document.getElementById('pformDescHidden').value = quillInstance.getText().trim();
                document.getElementById('productCreateForm').querySelector('[name=is_active]').value = 1;
                const badge = document.getElementById('pformSavedBadge');
                badge.style.color = '#ff6213';
                badge.textContent = '✓ Publicado';
                badge.classList.add('visible');
                clearTimeout(badge._timer);
                badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
                document.getElementById('productCreateForm').submit();
            });

            /* ── Profitability card ── */
            const costInput = document.getElementById('pformCost');
            const priceInput = document.getElementById('pformPrice');

            function updateProfit() {
                const cost = parseFloat(costInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const profit = price - cost;
                const margin = price > 0 ? ((profit / price) * 100).toFixed(2) : '0.00';
                document.getElementById('pformProfitCost').textContent = '$' + cost.toLocaleString('es-MX');
                document.getElementById('pformProfitPrice').textContent = '$' + price.toLocaleString('es-MX');
                document.getElementById('pformProfitUtil').textContent = '$' + profit.toLocaleString('es-MX');
                document.getElementById('pformProfitMargin').textContent = margin + '%';
                const color = profit >= 0 ? '#16a34a' : '#dc2626';
                document.getElementById('pformProfitUtil').style.color = color;
                document.getElementById('pformProfitMargin').style.color = color;
            }

            costInput.addEventListener('input', updateProfit);
            priceInput.addEventListener('input', updateProfit);

            /* ── Desglose de IVA (con conversión USD→MXN si aplica) ── */
            const includesTaxInput = document.getElementById('pformPriceIncludesTax');
            const currencySelect = document.querySelector('select[name="currency"]');
            const exchangeRateHint = document.getElementById('pformExchangeRateHint');

            function updateIvaBreakdown() {
                const rawPrice = parseFloat(priceInput.value) || 0;
                const rate = window.PFORM_IVA_RATE || 16;
                const includesTax = includesTaxInput.checked;
                const isUsd = currencySelect && currencySelect.value === 'USD';
                const exchangeRate = window.PFORM_EXCHANGE_RATE || 1;

                // Conversión primero, IVA después — mismo orden que Products::getFinalPriceAttribute().
                const price = isUsd ? rawPrice * exchangeRate : rawPrice;

                if (exchangeRateHint) {
                    exchangeRateHint.style.display = isUsd ? '' : 'none';
                    if (isUsd) {
                        exchangeRateHint.textContent = '1 USD = $' + exchangeRate.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' MXN';
                    }
                }

                let base, ivaAmount, final;
                if (includesTax) {
                    ivaAmount = price - (price / (1 + rate / 100));
                    base = price - ivaAmount;
                    final = price;
                } else {
                    ivaAmount = price * (rate / 100);
                    base = price;
                    final = price + ivaAmount;
                }

                document.getElementById('pformIvaRateLabel').textContent = 'IVA (' + rate + '%)';
                document.getElementById('pformIvaBase').textContent = '$' + base.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('pformIvaAmount').textContent = '$' + ivaAmount.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                document.getElementById('pformIvaFinal').textContent = '$' + final.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            priceInput.addEventListener('input', updateIvaBreakdown);
            includesTaxInput.addEventListener('change', updateIvaBreakdown);
            if (currencySelect) currencySelect.addEventListener('change', updateIvaBreakdown);
            updateIvaBreakdown();

            /* ── Availability buttons ── */
            document.querySelectorAll('.pform-avail-row').forEach(function(row) {
                row.querySelectorAll('.pform-avail-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        row.querySelectorAll('.pform-avail-btn').forEach(b => b.classList
                            .remove('active'));
                        this.classList.add('active');

                        // Sync to hidden input
                        const titleEl = this.querySelector('.pform-avail-btn-title');
                        const map = {
                            'Disponible': 'available',
                            'Bajo Pedido': 'on_order',
                            'Agotado': 'out_of_stock',
                        };
                        document.getElementById('pformAvailability').value =
                            map[titleEl?.textContent.trim()] ?? 'available';
                    });
                });
            });

            /* ── Badge toggle cards ── */
            document.querySelectorAll('.pform-badge-card:not(#badgeFeatured):not(#badgeNew):not(#badgeRecommended):not(#badgePublishOnWebsite)')
                .forEach(function(card) {
                    card.addEventListener('click', function() {
                        this.classList.toggle('active');
                    });
                });

            /* ── Tags ── */
            // FIX BUG 3: chips were purely visual with no way to reach the
            // server. syncTagsHidden() collects the current chip text into
            // the hidden #pformTagsHidden input as a JSON array string,
            // called on every add/remove so it's always in sync at submit.
            function syncTagsHidden() {
                const values = Array.from(document.querySelectorAll('#pformTagList .pform-tag-chip'))
                    .map(chip => chip.textContent);
                document.getElementById('pformTagsHidden').value = JSON.stringify(values);
            }

            function addTagChip(val) {
                const chip = document.createElement('span');
                chip.className = 'pform-tag-chip';
                chip.textContent = val;
                chip.title = 'Clic para eliminar';
                chip.addEventListener('click', function() {
                    this.remove();
                    syncTagsHidden();
                });
                document.getElementById('pformTagList').appendChild(chip);
            }

            function addTag() {
                const input = document.getElementById('pformTagInput');
                const val = input.value.trim();
                if (!val) return;
                addTagChip(val);
                input.value = '';
                input.focus();
                syncTagsHidden();
                closeTagSuggestions();
            }

            document.getElementById('pformTagAdd').addEventListener('click', addTag);

            // Restaura los chips de etiquetas si la página se re-renderiza
            // tras un error de validación del servidor en OTRO campo —
            // old('tags') trae el mismo JSON que el input oculto ya
            // serializaba antes de enviarse.
            @if (old('tags'))
                (function restoreOldTags() {
                    try {
                        const oldTags = JSON.parse(@json(old('tags')));
                        if (Array.isArray(oldTags)) {
                            oldTags.forEach(t => addTagChip(t));
                            syncTagsHidden();
                        }
                    } catch (e) {}
                })();
            @endif

            /* ── Tag autocomplete: sugiere etiquetas ya usadas en otros
               productos (no existe tabla de tags, se buscan en vivo). Si
               no hay ninguna sugerencia y se presiona Enter, se crea la
               etiqueta tal cual se escribió (comportamiento ya existente
               de addTag()). ── */
            const tagInput = document.getElementById('pformTagInput');
            const tagSuggestionsList = document.getElementById('pformTagSuggestions');
            let tagSuggestionsTimer = null;
            let tagSuggestionActiveIndex = -1;

            function closeTagSuggestions() {
                tagSuggestionsList.classList.remove('is-open');
                tagSuggestionsList.innerHTML = '';
                tagSuggestionActiveIndex = -1;
            }

            function currentTagValuesLower() {
                return Array.from(document.querySelectorAll('#pformTagList .pform-tag-chip'))
                    .map(chip => chip.textContent.toLowerCase());
            }

            function renderTagSuggestions(tags) {
                const existing = currentTagValuesLower();
                const filtered = tags.filter(t => !existing.includes(t.toLowerCase()));
                if (!filtered.length) {
                    closeTagSuggestions();
                    return;
                }

                tagSuggestionsList.innerHTML = filtered
                    .map(t => `<li class="pform-tag-suggestion-item">${t}</li>`)
                    .join('');
                tagSuggestionsList.classList.add('is-open');
                tagSuggestionActiveIndex = -1;

                tagSuggestionsList.querySelectorAll('.pform-tag-suggestion-item').forEach(item => {
                    item.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        addTagChip(this.textContent);
                        tagInput.value = '';
                        tagInput.focus();
                        syncTagsHidden();
                        closeTagSuggestions();
                    });
                });
            }

            async function fetchTagSuggestions(term) {
                try {
                    const res = await fetch(`{{ route('admin.products.tags.suggestions') }}?q=${encodeURIComponent(term)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    renderTagSuggestions(await res.json());
                } catch (err) {
                    console.error('Error fetching tag suggestions:', err);
                }
            }

            tagInput.addEventListener('input', function() {
                clearTimeout(tagSuggestionsTimer);
                const term = this.value.trim();
                tagSuggestionsTimer = setTimeout(() => fetchTagSuggestions(term), 250);
            });

            tagInput.addEventListener('focus', function() {
                fetchTagSuggestions(this.value.trim());
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.pform-tag-input-wrap')) closeTagSuggestions();
            });

            tagInput.addEventListener('keydown', function(e) {
                const items = tagSuggestionsList.querySelectorAll('.pform-tag-suggestion-item');

                if (e.key === 'ArrowDown' && items.length) {
                    e.preventDefault();
                    tagSuggestionActiveIndex = Math.min(tagSuggestionActiveIndex + 1, items.length - 1);
                    items.forEach((it, i) => it.classList.toggle('is-active', i === tagSuggestionActiveIndex));
                } else if (e.key === 'ArrowUp' && items.length) {
                    e.preventDefault();
                    tagSuggestionActiveIndex = Math.max(tagSuggestionActiveIndex - 1, 0);
                    items.forEach((it, i) => it.classList.toggle('is-active', i === tagSuggestionActiveIndex));
                } else if (e.key === 'Escape') {
                    closeTagSuggestions();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (tagSuggestionActiveIndex >= 0 && items[tagSuggestionActiveIndex]) {
                        addTagChip(items[tagSuggestionActiveIndex].textContent);
                        tagInput.value = '';
                        tagInput.focus();
                        syncTagsHidden();
                        closeTagSuggestions();
                    } else {
                        addTag();
                    }
                }
            });

            /* ── Specs ── */
            const addSpecBtn = document.getElementById('pformAddSpec');
            const specsList = document.getElementById('pformSpecsList');
            const specsEmpty = document.getElementById('pformSpecsEmpty');

            addSpecBtn.addEventListener('click', function() {
                specsEmpty.style.display = 'none';
                specsList.style.display = 'flex';

                const row = document.createElement('div');
                row.className = 'pform-spec-row';
                row.innerHTML =
                    '<input type="text" class="pform-input" name="spec_key[]" placeholder="Nombre del campo (ej: Potencia)">' +
                    '<input type="text" class="pform-input" name="spec_value[]" placeholder="Valor (ej: 20 HP)">' +
                    '<button type="button" class="pform-spec-del" title="Eliminar">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                    '</svg>' +
                    '</button>';

                row.querySelector('.pform-spec-del').addEventListener('click', function() {
                    row.remove();
                    if (specsList.children.length === 0) {
                        specsList.style.display = 'none';
                        specsEmpty.style.display = 'flex';
                    }
                });

                specsList.appendChild(row);
            });

            /* ── FAQ del producto (panel dentro del modal SEO) ──
               El modal SEO vive FUERA del <form>, por eso cada input lleva
               form="productCreateForm" — sin ese atributo no viajan en el
               POST. Names indexados faq_items[N][question|answer]. */
            const addFaqBtn = document.getElementById('pformAddFaq');
            const faqList = document.getElementById('pformFaqList');
            const faqEmpty = document.getElementById('pformFaqEmpty');

            function reindexFaqRows() {
                Array.from(faqList.querySelectorAll('.pform-faq-row')).forEach(function(row, i) {
                    row.querySelector('.pform-faq-question').name = 'faq_items[' + i + '][question]';
                    row.querySelector('.pform-faq-answer').name = 'faq_items[' + i + '][answer]';
                });
            }

            function addFaqRow(question, answer) {
                faqEmpty.style.display = 'none';
                faqList.style.display = 'flex';

                const row = document.createElement('div');
                row.className = 'pform-faq-row';
                row.innerHTML =
                    '<div class="pform-faq-fields">' +
                    '<input type="text" class="pform-input pform-faq-question" form="productCreateForm" placeholder="Pregunta (ej: ¿Incluye instalación?)">' +
                    '<div class="pform-faq-toolbar">' +
                    '<button type="button" class="pform-insert-variable-btn" data-variable-target="faq-answer">{ } Insertar variable</button>' +
                    '<button type="button" class="pform-insert-link-btn">🔗 Insertar enlace</button>' +
                    '</div>' +
                    '<textarea class="pform-input pform-faq-answer" form="productCreateForm" rows="2" placeholder="Respuesta"></textarea>' +
                    '</div>' +
                    '<button type="button" class="pform-spec-del" title="Eliminar">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                    '</svg>' +
                    '</button>';

                row.querySelector('.pform-faq-question').value = question || '';
                row.querySelector('.pform-faq-answer').value = answer || '';

                row.querySelector('.pform-spec-del').addEventListener('click', function() {
                    row.remove();
                    reindexFaqRows();
                    if (faqList.children.length === 0) {
                        faqList.style.display = 'none';
                        faqEmpty.style.display = 'flex';
                    }
                });

                faqList.appendChild(row);
                reindexFaqRows();
            }

            addFaqBtn.addEventListener('click', function() { addFaqRow(); });

            /* ── SEO modal ── */
            const seoModal = document.getElementById('pformSeoModal');

            document.getElementById('pformBtnSeo').addEventListener('click', function() {
                seoModal.style.display = 'flex';
            });

            document.getElementById('pformSeoClose').addEventListener('click', function() {
                seoModal.style.display = 'none';
            });

            // FIX BUG 6: "Guardar Producto" inside the SEO panel just clicks
            // the real "Publicar Producto" button, reusing its existing
            // validateForm() + submit flow instead of duplicating it.
            document.getElementById('pformSeoSaveBtn').addEventListener('click', function() {
                document.getElementById('pformBtnPublish').click();
            });

            /* ── SEO: title char counter ── */
            const seoTitle = document.getElementById('pformSeoTitle');
            const seoTitleCount = document.getElementById('pformSeoTitleCount');

            seoTitle.addEventListener('input', function() {
                seoTitleCount.textContent = this.value.length + '/60';
                updateGooglePreview();
                updateSeoScore();
            });

            /* ── SEO: meta char counter ── */
            const seoMeta = document.getElementById('pformSeoMeta');
            const seoMetaCount = document.getElementById('pformSeoMetaCount');

            seoMeta.addEventListener('input', function() {
                seoMetaCount.textContent = this.value.length + '/160';
                updateGooglePreview();
                updateSeoScore();
            });

            /* ── SEO: slug ── */
            const seoSlug = document.getElementById('pformSeoSlug');

            seoSlug.addEventListener('input', function() {
                const s = this.value || 'producto-ejemplo';
                document.getElementById('pformSeoSlugPreview').textContent = s;
                document.getElementById('pformSeoSlugPreview2').textContent = s;
                updateSeoScore();
            });

            document.getElementById('pformSeoAutoSlug').addEventListener('click', function() {
                const src = document.getElementById('pformName').value || seoTitle.value || 'producto-ejemplo';
                const slug = src.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim().replace(/\s+/g, '-');
                seoSlug.value = slug;
                seoSlug.dispatchEvent(new Event('input'));
            });

            function updateGooglePreview() {
                document.getElementById('pformGoogleTitle').textContent =
                    seoTitle.value || 'Bomba de Calor Rinnai 20HP';
                document.getElementById('pformGoogleDesc').textContent =
                    seoMeta.value ||
                    'Agrega una meta descripción para ver cómo se mostrará tu producto en los resultados de búsqueda de Google...';
            }

            function updateSeoScore() {
                const tLen = seoTitle.value.length;
                const mLen = seoMeta.value.length;
                const sLen = seoSlug.value.length;
                let score = 0;

                if (tLen >= 30 && tLen <= 60) score += 34;
                else if (tLen > 0) score += 17;

                if (mLen >= 120 && mLen <= 160) score += 33;
                else if (mLen > 0) score += 16;

                if (sLen > 0) score += 33;

                const scoreEl = document.getElementById('pformSeoScoreVal');
                scoreEl.textContent = score + '%';
                scoreEl.style.color = score >= 67 ? '#16a34a' : score >= 34 ? '#d97706' : '#dc2626';

                const items = document.querySelectorAll('.pform-seo-item');
                const titleOk = tLen >= 30 && tLen <= 60;
                const metaOk = mLen >= 120 && mLen <= 160;
                const slugOk = sLen > 0;

                setCheck(items[0], titleOk,
                    'Título SEO: ' + (titleOk ? 'Longitud óptima ✓' : 'Mejorar longitud (30-60 caracteres)'));
                setCheck(items[1], metaOk,
                    'Meta Description: ' + (metaOk ? 'Longitud óptima ✓' : 'Mejorar longitud (120-160 caracteres)'));
                setCheck(items[2], slugOk,
                    'URL Slug: ' + (slugOk ? 'Configurado ✓' : 'Falta configurar'));
            }

            function setCheck(el, ok, text) {
                el.querySelector('span').textContent = text;
                el.querySelector('svg').style.stroke = ok ? '#16a34a' : '#d97706';
            }

            /* ── Server-side validation errors (withErrors() redirect back) ──
               El servidor marca cada campo inválido con la clase
               .pform-field-error vía @@error() en el blade (ver
               showFieldError() arriba, mismo nombre de clase que ya usa la
               validación del lado del cliente). Si el primer error cae en
               una pestaña que no es la activa por defecto, o dentro del
               modal de SEO (que vive fuera del sistema de pestañas), esto
               la muestra automáticamente para que el admin no tenga que
               ir clic por clic buscando cuál de los ~20+ campos falló. */
            const firstServerError = document.querySelector('.pform-field-error');
            if (firstServerError) {
                if (seoModal.contains(firstServerError)) {
                    seoModal.style.display = 'flex';
                } else {
                    const panel = firstServerError.closest('.pform-tab-panel');
                    if (panel) switchToPanel(panel.id);
                }
                setTimeout(() => firstServerError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                }), 150);
            }

        })();

        /* ── Category cascade ── */
        const categoryMain = document.getElementById('pformCategoryMain');
        const categorySub = document.getElementById('pformCategorySub');
        const categoryChild = document.getElementById('pformCategoryChild');
        const categoryIdHidden = document.getElementById('pformCategoryIdHidden');
        const breadcrumb = document.getElementById('pformBreadcrumbText');

        @php
            $subcategoriesForJs = $categories->mapWithKeys(function($c) {
                return [
                    $c->id => $c->children->map(function($s) {
                        return [
                            'id' => $s->id,
                            'name' => $s->name,
                            'children' => $s->children->map(function($ch) {
                                return [
                                    'id' => $ch->id,
                                    'name' => $ch->name,
                                ];
                            })->values()->toArray(),
                        ];
                    })->values()->toArray(),
                ];
            })->toArray();
        @endphp

        const subcategories = @json($subcategoriesForJs);

        function updateCategoryIdHidden() {
            categoryIdHidden.value = categoryChild.value || categorySub.value || categoryMain.value || '';
        }

        function populateSubOptions(mainId) {
            const subs = subcategories[mainId] ?? [];
            categorySub.innerHTML = '<option value="">Seleccionar...</option>';
            subs.forEach(sub => {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                categorySub.appendChild(opt);
            });
            categorySub.disabled = subs.length === 0;
        }

        function populateChildOptions(mainId, subId) {
            const mainSubs = subcategories[mainId] ?? [];
            const subObj = mainSubs.find(s => String(s.id) === String(subId));
            const children = subObj?.children ?? [];
            categoryChild.innerHTML = '<option value="">Seleccionar...</option>';
            children.forEach(child => {
                const opt = document.createElement('option');
                opt.value = child.id;
                opt.textContent = child.name;
                categoryChild.appendChild(opt);
            });
            categoryChild.disabled = children.length === 0;
        }

        function updateBreadcrumb() {
            const parts = ['Catálogo'];
            if (categoryMain.value) parts.push(categoryMain.options[categoryMain.selectedIndex].text);
            if (categorySub.value) parts.push(categorySub.options[categorySub.selectedIndex].text);
            if (categoryChild.value) parts.push(categoryChild.options[categoryChild.selectedIndex].text);
            breadcrumb.textContent = parts.join(' > ');
        }

        categoryMain.addEventListener('change', function() {
            categorySub.innerHTML = '<option value="">Seleccionar categoría primero...</option>';
            categoryChild.innerHTML = '<option value="">Seleccionar subcategoría primero...</option>';
            categoryChild.disabled = true;
            populateSubOptions(this.value);
            updateBreadcrumb();
            updateCategoryIdHidden();
        });

        categorySub.addEventListener('change', function() {
            populateChildOptions(categoryMain.value, this.value);
            updateBreadcrumb();
            updateCategoryIdHidden();
        });

        categoryChild.addEventListener('change', function() {
            updateBreadcrumb();
            updateCategoryIdHidden();
        });

        @php
            // FIX (validación de servidor): si la categoría enviada falló la
            // validación por otro motivo distinto a category_id (ej. SKU
            // duplicado), old('category_id') permite restaurar la selección
            // en las 3 pestañas en cascada — sin esto, un error en OTRO
            // campo hacía que el admin tuviera que volver a elegir la
            // categoría desde cero.
            $oldCategoryId = old('category_id');
            $oldCategory = $oldCategoryId ? \App\Models\Category::with('parent.parent')->find($oldCategoryId) : null;
            $initialMainId = $initialSubId = $initialChildId = null;
            if ($oldCategory) {
                $level = $oldCategory->level;
                if ($level === 1) {
                    $initialMainId = $oldCategory->id;
                } elseif ($level === 2) {
                    $initialMainId = $oldCategory->parent_id;
                    $initialSubId = $oldCategory->id;
                } else {
                    $initialSubId = $oldCategory->parent_id;
                    $initialChildId = $oldCategory->id;
                    $initialMainId = $oldCategory->parent?->parent_id;
                }
            }
        @endphp

        (function restoreCategoryCascadeFromOldInput() {
            const initialMainId = @json($initialMainId);
            const initialSubId = @json($initialSubId);
            const initialChildId = @json($initialChildId);

            if (!initialMainId) return;

            categoryMain.value = initialMainId;
            populateSubOptions(initialMainId);

            if (initialSubId) {
                categorySub.value = initialSubId;
                populateChildOptions(initialMainId, initialSubId);
                if (initialChildId) categoryChild.value = initialChildId;
            }

            updateBreadcrumb();
            updateCategoryIdHidden();
        })();

        /* ── Badge sync ── */
        const badgeFeatured = document.getElementById('badgeFeatured');
        if (badgeFeatured) {
            badgeFeatured.addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('pformIsFeatured').value = this.classList.contains('active') ? 1 : 0;
            });
        }

        const badgeNew = document.getElementById('badgeNew');
        if (badgeNew) {
            badgeNew.addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('pformIsNew').value = this.classList.contains('active') ? 1 : 0;
            });
        }

        const badgeRecommended = document.getElementById('badgeRecommended');
        if (badgeRecommended) {
            badgeRecommended.addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('pformIsRecommended').value = this.classList.contains('active') ? 1 : 0;
            });
        }

        const badgePublishOnWebsite = document.getElementById('badgePublishOnWebsite');
        if (badgePublishOnWebsite) {
            badgePublishOnWebsite.addEventListener('click', function() {
                this.classList.toggle('active');
                document.getElementById('pformPublishOnWebsite').value = this.classList.contains('active') ? 1 : 0;
            });
        }

        /* ── Image Gallery ── */
        (function() {
            const input = document.getElementById('pformImageInput');
            const dropzone = document.getElementById('pformDropzone');
            const placeholder = document.getElementById('pformImagePlaceholder');
            const grid = document.getElementById('pformImageGrid');
            const urlInputsWrap = document.getElementById('pformImageUrlInputs');
            const orderInputsWrap = document.getElementById('pformImageOrderInputs');

            // Combined, orderable list: {type:'file', file:File} | {type:'url', url:string} | {type:'existing', id:number, url:string}
            let items = [];
            let dragSrcIndex = null;

            function syncFormInputs() {
                const newDt = new DataTransfer();
                items.forEach(function(item) {
                    if (item.type === 'file') newDt.items.add(item.file);
                });
                input.files = newDt.files;

                urlInputsWrap.innerHTML = '';
                orderInputsWrap.innerHTML = '';
                items.forEach(function(item) {
                    if (item.type === 'url') {
                        const h = document.createElement('input');
                        h.type = 'hidden';
                        h.name = 'image_urls[]';
                        h.value = item.url;
                        urlInputsWrap.appendChild(h);
                    } else if (item.type === 'existing') {
                        // FIX (image duplication): sent as existing_image_ids[]
                        // instead of image_urls[] so the server copies the
                        // existing row's path instead of re-downloading it.
                        const h = document.createElement('input');
                        h.type = 'hidden';
                        h.name = 'existing_image_ids[]';
                        h.value = item.id;
                        urlInputsWrap.appendChild(h);
                    }
                    const o = document.createElement('input');
                    o.type = 'hidden';
                    o.name = 'image_source_order[]';
                    o.value = item.type;
                    orderInputsWrap.appendChild(o);
                });
            }

            function renderPreviews() {
                grid.innerHTML = '';
                if (items.length === 0) {
                    placeholder.style.display = '';
                    syncFormInputs();
                    return;
                }
                placeholder.style.display = 'none';

                items.forEach(function(item, index) {
                    const el = document.createElement('div');
                    el.className = 'pform-img-item';
                    el.draggable = true;
                    el.title = 'Arrastra para reordenar';

                    const img = document.createElement('img');
                    img.className = 'pform-img-item-thumb';
                    img.src = item.type === 'file' ? URL.createObjectURL(item.file) : item.url;
                    el.appendChild(img);

                    if (index === 0) {
                        const badge = document.createElement('span');
                        badge.className = 'pform-img-item-badge';
                        badge.textContent = 'Portada';
                        el.appendChild(badge);
                    }

                    const info = document.createElement('div');
                    info.className = 'pform-img-item-info';
                    if (item.type === 'file') {
                        info.title = item.file.name;
                        info.textContent = item.file.name + ' · ' + (item.file.size / 1024).toFixed(0) + ' KB';
                    } else if (item.type === 'existing') {
                        info.title = item.url;
                        info.textContent = 'Imagen de la biblioteca';
                    } else {
                        info.title = item.url;
                        info.textContent = 'Imagen por URL';
                    }
                    el.appendChild(info);

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'pform-img-item-remove';
                    btn.textContent = '✕';
                    btn.addEventListener('click', function() {
                        items.splice(index, 1);
                        renderPreviews();
                    });
                    el.appendChild(btn);

                    el.addEventListener('dragstart', function(e) {
                        dragSrcIndex = index;
                        el.classList.add('is-dragging');
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    el.addEventListener('dragend', function() {
                        el.classList.remove('is-dragging');
                    });
                    el.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        if (dragSrcIndex === null || dragSrcIndex === index) return;
                        el.classList.add('drag-over');
                    });
                    el.addEventListener('dragleave', function() {
                        el.classList.remove('drag-over');
                    });
                    el.addEventListener('drop', function(e) {
                        e.preventDefault();
                        el.classList.remove('drag-over');
                        if (dragSrcIndex === null || dragSrcIndex === index) return;
                        const moved = items.splice(dragSrcIndex, 1)[0];
                        items.splice(index, 0, moved);
                        dragSrcIndex = null;
                        renderPreviews();
                    });

                    grid.appendChild(el);
                });

                syncFormInputs();
            }

            input.addEventListener('change', function() {
                Array.from(this.files).forEach(function(f) {
                    items.push({
                        type: 'file',
                        file: f
                    });
                });
                renderPreviews();
            });

            // Clicking the dropzone opens the "subir archivo / usar URL"
            // choice modal instead of jumping straight to the OS file
            // picker. Dropping a file directly onto it still works without
            // the extra step, since that's already an unambiguous action.
            dropzone.addEventListener('click', function() {
                openImageSourceModal();
            });

            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ff6213';
                this.style.background = '#fff7f5';
            });

            dropzone.addEventListener('dragleave', function() {
                this.style.borderColor = '';
                this.style.background = '';
            });

            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '';
                this.style.background = '';
                Array.from(e.dataTransfer.files).forEach(function(f) {
                    if (['image/png', 'image/jpeg', 'image/jpg'].includes(f.type)) {
                        items.push({
                            type: 'file',
                            file: f
                        });
                    }
                });
                renderPreviews();
            });

            /* ── "Agregar Imagen" modal: subir archivo / URL / biblioteca ── */
            const modal = document.getElementById('imageSourceModal');
            const uploadRow = document.getElementById('imgSourceUploadRow');
            const urlPanel = document.getElementById('imgSourceUrlPanel');
            const fileBtn = document.getElementById('imgSourceFileBtn');
            const urlBtn = document.getElementById('imgSourceUrlBtn');
            const backBtn = document.getElementById('imgSourceBackBtn');
            const cancelBtn = document.getElementById('imageSourceCancel');
            const urlInput = document.getElementById('imgUrlInput');
            const urlPreviewWrap = document.getElementById('imgUrlPreviewWrap');
            const urlPreviewImg = document.getElementById('imgUrlPreviewImg');
            const urlStatus = document.getElementById('imgUrlStatus');
            const urlAddBtn = document.getElementById('imgUrlAddBtn');
            let urlIsValid = false;

            function openImageSourceModal() {
                resetImageSourceModal();
                modal.classList.add('active');
                librarySearch.value = '';
                loadLibrary(1, false);
            }

            function closeImageSourceModal() {
                modal.classList.remove('active');
                resetImageSourceModal();
            }

            function resetImageSourceModal() {
                uploadRow.style.display = '';
                urlPanel.style.display = 'none';
                urlInput.value = '';
                urlPreviewWrap.style.display = 'none';
                urlStatus.textContent = '';
                urlIsValid = false;
                urlAddBtn.disabled = true;
            }

            fileBtn.addEventListener('click', function() {
                closeImageSourceModal();
                input.click();
            });

            urlBtn.addEventListener('click', function() {
                uploadRow.style.display = 'none';
                urlPanel.style.display = 'block';
                urlInput.focus();
            });

            backBtn.addEventListener('click', function() {
                uploadRow.style.display = '';
                urlPanel.style.display = 'none';
                urlInput.value = '';
                urlPreviewWrap.style.display = 'none';
                urlStatus.textContent = '';
                urlIsValid = false;
                urlAddBtn.disabled = true;
            });
            cancelBtn.addEventListener('click', closeImageSourceModal);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeImageSourceModal();
            });

            let urlCheckTimer = null;
            urlInput.addEventListener('input', function() {
                clearTimeout(urlCheckTimer);
                urlIsValid = false;
                urlAddBtn.disabled = true;
                const value = this.value.trim();
                if (!value) {
                    urlPreviewWrap.style.display = 'none';
                    urlStatus.textContent = '';
                    return;
                }
                urlStatus.textContent = 'Comprobando imagen...';
                urlCheckTimer = setTimeout(function() {
                    const testImg = new Image();
                    testImg.onload = function() {
                        urlPreviewImg.src = value;
                        urlPreviewWrap.style.display = 'block';
                        urlStatus.textContent = 'Imagen encontrada.';
                        urlIsValid = true;
                        urlAddBtn.disabled = false;
                    };
                    testImg.onerror = function() {
                        urlPreviewWrap.style.display = 'none';
                        urlStatus.textContent = 'No se pudo cargar esa URL como imagen. Verifica que sea un enlace directo a una imagen.';
                    };
                    testImg.src = value;
                }, 400);
            });

            urlAddBtn.addEventListener('click', function() {
                if (!urlIsValid) return;
                items.push({
                    type: 'url',
                    url: urlInput.value.trim()
                });
                closeImageSourceModal();
                renderPreviews();
            });

            /* ── Biblioteca: imágenes ya subidas en cualquier producto ── */
            const librarySearch = document.getElementById('imgLibrarySearch');
            const libraryGrid = document.getElementById('imgLibraryGrid');
            const libraryEmpty = document.getElementById('imgLibraryEmpty');
            const libraryLoading = document.getElementById('imgLibraryLoading');
            const libraryLoadMore = document.getElementById('imgLibraryLoadMore');
            const libraryUrl = '{{ route('admin.products.images.library') }}';
            let libraryNextPage = 1;
            let librarySearchTimer = null;

            function isUrlAlreadyPending(url) {
                return items.some(function(it) {
                    return (it.type === 'url' || it.type === 'existing') && it.url === url;
                });
            }

            function renderLibraryItems(libraryItems, append) {
                if (!append) libraryGrid.innerHTML = '';
                libraryItems.forEach(function(libItem) {
                    const cell = document.createElement('button');
                    cell.type = 'button';
                    cell.className = 'img-library-item';
                    cell.title = libItem.product_name + (libItem.product_sku ? ' · ' + libItem.product_sku : '');

                    const img = document.createElement('img');
                    img.src = libItem.url;
                    img.loading = 'lazy';
                    cell.appendChild(img);

                    const badge = document.createElement('span');
                    badge.className = 'img-library-item-badge';
                    badge.textContent = 'Usado en ' + libItem.used_count + (libItem.used_count === 1 ? ' producto' : ' productos');
                    cell.appendChild(badge);

                    const label = document.createElement('span');
                    label.className = 'img-library-item-label';
                    label.textContent = libItem.product_name || 'Sin nombre';
                    cell.appendChild(label);

                    const alreadyAdded = isUrlAlreadyPending(libItem.url);
                    if (alreadyAdded) {
                        cell.classList.add('is-added');
                        cell.disabled = true;
                        cell.title += ' — ya agregada';
                    }

                    cell.addEventListener('click', function() {
                        if (isUrlAlreadyPending(libItem.url)) return;
                        // FIX (image duplication): tagged 'existing' (not 'url')
                        // so the server reuses the same physical file via
                        // existing_image_ids[] instead of re-downloading it as
                        // a new copy through the "usar URL" pipeline.
                        items.push({
                            type: 'existing',
                            id: libItem.id,
                            url: libItem.url
                        });
                        renderPreviews();
                        cell.classList.add('is-added');
                        cell.disabled = true;
                    });

                    libraryGrid.appendChild(cell);
                });
            }

            async function loadLibrary(page, append) {
                libraryLoading.style.display = 'block';
                libraryLoadMore.style.display = 'none';
                try {
                    const params = new URLSearchParams({
                        page: page
                    });
                    if (librarySearch.value.trim()) params.set('search', librarySearch.value.trim());

                    const response = await fetch(libraryUrl + '?' + params.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        },
                    });
                    const data = await response.json();

                    renderLibraryItems(data.data, append);
                    libraryEmpty.style.display = (!append && data.data.length === 0) ? 'block' : 'none';

                    if (data.has_more) {
                        libraryNextPage = data.next_page;
                        libraryLoadMore.style.display = 'block';
                    }
                } catch (err) {
                    console.error('Error loading image library:', err);
                } finally {
                    libraryLoading.style.display = 'none';
                }
            }

            librarySearch.addEventListener('input', function() {
                clearTimeout(librarySearchTimer);
                librarySearchTimer = setTimeout(function() {
                    loadLibrary(1, false);
                }, 350);
            });

            libraryLoadMore.addEventListener('click', function() {
                loadLibrary(libraryNextPage, true);
            });
        })();

        /* ── FIX (Documentación tab): visual feedback for the 6 document
           uploads — the inputs are visually hidden behind a "Subir" label,
           so without this the user had no confirmation a file was picked. ── */
        document.querySelectorAll('.pform-doc-input').forEach(function(input) {
            const row = input.closest('.pform-doc-row');
            const info = row?.querySelector('.pform-doc-info p');
            const originalText = info?.textContent;
            input.addEventListener('change', function() {
                if (this.files.length && info) {
                    info.textContent = '📎 ' + this.files[0].name;
                    info.style.color = '#16a34a';
                } else if (info && originalText) {
                    info.textContent = originalText;
                    info.style.color = '';
                }
            });
        });
    </script>
@endpush
