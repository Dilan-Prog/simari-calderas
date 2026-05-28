@push('scripts')
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

            /* ── Character counter (short desc) ── */
            const shortDesc = document.getElementById('pformShortDesc');
            const charCount = document.getElementById('pformCharCount');
            if (shortDesc && charCount) {
                charCount.textContent = shortDesc.value.length + '/200';
                shortDesc.addEventListener('input', function() {
                    charCount.textContent = this.value.length + '/200';
                });
            }

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

            // Pre-load existing description
            const existingDesc = document.getElementById('pformDescHidden').value;
            if (existingDesc) quillInstance.setText(existingDesc);

            /* ── Back button ── */
            document.getElementById('pformBackBtn').addEventListener('click', function() {
                window.location.href = '{{ route('admin.products.index') }}';
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
                document.getElementById('productEditForm').querySelector('[name=is_active]').value = 0;
                const badge = document.getElementById('pformSavedBadge');
                badge.style.color = '#16a34a';
                badge.textContent = '✓ Guardado';
                badge.classList.add('visible');
                clearTimeout(badge._timer);
                badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
                document.getElementById('productEditForm').submit();
            });

            /* ── Publish ── */
            document.getElementById('pformBtnPublish').addEventListener('click', function() {
                if (!validateForm()) return;
                document.getElementById('pformDescHidden').value = quillInstance.getText().trim();
                document.getElementById('productEditForm').querySelector('[name=is_active]').value = 1;
                const badge = document.getElementById('pformSavedBadge');
                badge.style.color = '#ff6213';
                badge.textContent = '✓ Publicado';
                badge.classList.add('visible');
                clearTimeout(badge._timer);
                badge._timer = setTimeout(() => badge.classList.remove('visible'), 3000);
                document.getElementById('productEditForm').submit();
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
            updateProfit(); // Initialize with existing values

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
            document.querySelectorAll('.pform-badge-card:not(#badgeFeatured):not(#badgeNew):not(#badgeRecommended)')
                .forEach(function(card) {
                    card.addEventListener('click', function() {
                        this.classList.toggle('active');
                    });
                });

            /* ── Tags ── */
            function addTag() {
                const input = document.getElementById('pformTagInput');
                const val = input.value.trim();
                if (!val) return;
                const chip = document.createElement('span');
                chip.className = 'pform-tag-chip';
                chip.textContent = val;
                chip.title = 'Clic para eliminar';
                chip.addEventListener('click', function() {
                    this.remove();
                });
                document.getElementById('pformTagList').appendChild(chip);
                input.value = '';
                input.focus();
            }

            document.getElementById('pformTagAdd').addEventListener('click', addTag);
            document.getElementById('pformTagInput').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTag();
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

            /* ── SEO modal ── */
            const seoModal = document.getElementById('pformSeoModal');

            document.getElementById('pformBtnSeo').addEventListener('click', function() {
                seoModal.style.display = 'flex';
            });

            document.getElementById('pformSeoClose').addEventListener('click', function() {
                seoModal.style.display = 'none';
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

            // Inicializar contadores si ya traen datos cargados del servidor
            if (seoTitle) seoTitle.dispatchEvent(new Event('input'));
            if (seoMeta) seoMeta.dispatchEvent(new Event('input'));
            if (seoSlug) seoSlug.dispatchEvent(new Event('input'));

        })();

        /* ── Category cascade ── */
        const categoryMain = document.getElementById('pformCategoryMain');
        const categorySub = document.getElementById('pformCategorySub');
        const categoryChild = document.getElementById('pformCategoryChild');
        const breadcrumb = document.getElementById('pformBreadcrumbText');

        @php
            $subcategoriesForJs = $categories
                ->mapWithKeys(function ($c) {
                    return [
                        $c->id => $c->children
                            ->map(function ($s) {
                                return [
                                    'id' => $s->id,
                                    'name' => $s->name,
                                    'children' => $s->children
                                        ->map(function ($ch) {
                                            return [
                                                'id' => $ch->id,
                                                'name' => $ch->name,
                                            ];
                                        })
                                        ->values()
                                        ->toArray(),
                                ];
                            })
                            ->values()
                            ->toArray(),
                    ];
                })
                ->toArray();
        @endphp

        const subcategories = @json($subcategoriesForJs);

        categoryMain.addEventListener('change', function() {
            const id = this.value;
            const name = this.options[this.selectedIndex].text;
            const subs = subcategories[id] ?? [];

            breadcrumb.textContent = id ? `Catálogo > ${name}` : 'Catálogo';

            categorySub.innerHTML = '<option value="">Seleccionar...</option>';
            categoryChild.innerHTML = '<option value="">Seleccionar subcategoría primero...</option>';
            categoryChild.disabled = true;

            if (subs.length > 0) {
                subs.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name;
                    categorySub.appendChild(opt);
                });
                categorySub.disabled = false;
            } else {
                categorySub.disabled = true;
            }
        });

        categorySub.addEventListener('change', function() {
            const subId = this.value;
            const subName = this.options[this.selectedIndex].text;
            const mainName = categoryMain.options[categoryMain.selectedIndex].text;

            // Find children of selected sub
            const mainId = categoryMain.value;
            const mainSubs = subcategories[mainId] ?? [];
            const subObj = mainSubs.find(s => String(s.id) === String(subId));
            const children = subObj?.children ?? [];

            breadcrumb.textContent = subId ?
                `Catálogo > ${mainName} > ${subName}` :
                `Catálogo > ${mainName}`;

            categoryChild.innerHTML = '<option value="">Seleccionar...</option>';
            if (children.length > 0) {
                children.forEach(child => {
                    const opt = document.createElement('option');
                    opt.value = child.id;
                    opt.textContent = child.name;
                    categoryChild.appendChild(opt);
                });
                categoryChild.disabled = false;
            } else {
                categoryChild.disabled = true;
            }
        });

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

        /* ── Image Gallery ── */
        (function() {
            const input = document.getElementById('pformImageInput');
            const dropzone = document.querySelector('#pformPanel1 .pform-dropzone');
            const placeholder = document.getElementById('pformImagePlaceholder');
            const grid = document.getElementById('pformImageGrid');
            let dt = new DataTransfer();

            function renderPreviews() {
                grid.innerHTML = '';
                if (dt.files.length === 0) {
                    placeholder.style.display = '';
                    return;
                }
                placeholder.style.display = 'none';
                Array.from(dt.files).forEach(function(file, index) {
                    const item = document.createElement('div');
                    item.style.cssText =
                        'position:relative;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;';

                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.style.cssText = 'width:100%;height:100px;object-fit:cover;display:block;';

                    const info = document.createElement('div');
                    info.style.cssText =
                        'padding:4px 6px;font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;';
                    info.title = file.name;
                    info.textContent = file.name + ' · ' + (file.size / 1024).toFixed(0) + ' KB';

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = '✕';
                    btn.style.cssText =
                        'position:absolute;top:4px;right:4px;background:#dc2626;color:#fff;border:none;border-radius:50%;width:22px;height:22px;cursor:pointer;font-size:13px;line-height:1;display:flex;align-items:center;justify-content:center;';
                    btn.addEventListener('click', function() {
                        const newDt = new DataTransfer();
                        Array.from(dt.files).forEach(function(f, i) {
                            if (i !== index) newDt.items.add(f);
                        });
                        dt = newDt;
                        input.files = dt.files;
                        renderPreviews();
                    });

                    item.appendChild(img);
                    item.appendChild(info);
                    item.appendChild(btn);
                    grid.appendChild(item);
                });
            }

            if (input) {
                input.addEventListener('change', function() {
                    Array.from(this.files).forEach(function(f) {
                        dt.items.add(f);
                    });
                    input.files = dt.files;
                    renderPreviews();
                });
            }

            if (dropzone) {
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
                        if (['image/png', 'image/jpeg', 'image/jpg'].includes(f.type)) dt.items.add(f);
                    });
                    input.files = dt.files;
                    renderPreviews();
                });
            }
        })();
    </script>
@endpush
