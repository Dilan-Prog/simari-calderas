@push('scripts')
    <script>
        const serviceSectionsBaseUrl = '{{ url('/admin/servicios-web/' . $servicePage->id . '/secciones') }}';
        const serviceSectionModal = document.getElementById('serviceSectionModal');
        const serviceSectionForm = document.getElementById('serviceSectionForm');
        const ssErrorsContainer = document.getElementById('service-section-modal-errors');
        let currentServiceSectionId = null;
        let ssIsEditMode = false;

        const closeServiceSectionWithAnim = () => {
            const content = serviceSectionModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            serviceSectionModal.style.transition = 'opacity 0.2s ease-in';
            serviceSectionModal.style.opacity = '0';
            setTimeout(() => {
                serviceSectionModal.style.display = 'none';
                serviceSectionModal.style.opacity = '';
                serviceSectionModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        /* ── "Selección Manual": buscador de productos por nombre/SKU + chips ── */
        const serviceSectionProductSearchUrl = '{{ route('admin.service-pages.products.search') }}';

        function ssEscHtml(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function initServiceSectionProductPicker(opts) {
            const wrap = document.getElementById(opts.wrapId);
            const searchInput = document.getElementById(opts.inputId);
            const dropdown = document.getElementById(opts.dropdownId);
            const list = document.getElementById(opts.listId);
            const emptyMsg = document.getElementById(opts.emptyId);
            const chipsContainer = document.getElementById(opts.chipsId);
            const hiddenInput = document.getElementById(opts.hiddenId);

            let selected = [];
            let debounceTimer = null;

            function syncHidden() {
                hiddenInput.value = selected.map(p => p.id).join(',');
            }

            function renderChips() {
                chipsContainer.innerHTML = '';
                selected.forEach(p => {
                    const chip = document.createElement('span');
                    chip.className = 'hs-product-chip';
                    chip.innerHTML = `<span>${ssEscHtml(p.name)}</span><small>${ssEscHtml(p.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`;
                    chip.querySelector('button').addEventListener('click', () => {
                        selected = selected.filter(sp => sp.id !== p.id);
                        renderChips();
                        syncHidden();
                    });
                    chipsContainer.appendChild(chip);
                });
            }

            function hideDropdown() {
                dropdown.style.display = 'none';
                list.innerHTML = '';
            }

            function renderResults(products) {
                list.innerHTML = '';
                const filtered = products.filter(p => !selected.some(sp => sp.id === p.id));
                if (!filtered.length) {
                    emptyMsg.style.display = 'block';
                    list.style.display = 'none';
                    return;
                }
                emptyMsg.style.display = 'none';
                list.style.display = 'block';
                filtered.forEach(p => {
                    const li = document.createElement('li');
                    li.className = 'hs-product-search__item';
                    li.innerHTML = `<span>${ssEscHtml(p.name)}</span><small>SKU: ${ssEscHtml(p.sku)}</small>`;
                    li.addEventListener('click', () => {
                        selected.push({ id: p.id, name: p.name, sku: p.sku });
                        renderChips();
                        syncHidden();
                        searchInput.value = '';
                        hideDropdown();
                    });
                    list.appendChild(li);
                });
            }

            async function fetchProducts(params) {
                try {
                    const url = new URL(serviceSectionProductSearchUrl, window.location.origin);
                    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
                    const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                    if (!res.ok) return [];
                    return await res.json();
                } catch (err) {
                    return [];
                }
            }

            searchInput.addEventListener('input', function () {
                const q = this.value.trim();
                clearTimeout(debounceTimer);
                if (q.length < 2) { hideDropdown(); return; }
                debounceTimer = setTimeout(async () => {
                    const products = await fetchProducts({ q });
                    dropdown.style.display = 'block';
                    renderResults(products);
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#' + opts.wrapId)) hideDropdown();
            });

            return {
                reset() {
                    selected = [];
                    renderChips();
                    syncHidden();
                    searchInput.value = '';
                    hideDropdown();
                },
                async setSelectedIds(ids) {
                    selected = [];
                    renderChips();
                    syncHidden();
                    if (!ids || !ids.length) return;
                    const products = await fetchProducts({ ids: ids.join(',') });
                    selected = products.map(p => ({ id: p.id, name: p.name, sku: p.sku }));
                    renderChips();
                    syncHidden();
                },
            };
        }

        const ssMainProductPicker = initServiceSectionProductPicker({
            wrapId: 'ssProductSearch', inputId: 'ssProductSearchInput', dropdownId: 'ssProductSearchDropdown',
            listId: 'ssProductSearchList', emptyId: 'ssProductSearchEmpty', chipsId: 'ssProductChips', hiddenId: 'ssProductIds',
        });
        const ssPcbProductPicker = initServiceSectionProductPicker({
            wrapId: 'ssPcbProductSearch', inputId: 'ssPcbProductSearchInput', dropdownId: 'ssPcbProductSearchDropdown',
            listId: 'ssPcbProductSearchList', emptyId: 'ssPcbProductSearchEmpty', chipsId: 'ssPcbProductChips', hiddenId: 'ssPcbProductIds',
        });

        /* ── Filas repetibles genéricas (content_tabs / benefits_grid /
           process_steps) — mismo patrón de addRow/reindex que _faq_scripts,
           parametrizado para no triplicar la lógica. ── */
        function ssMakeRepeater(containerId, addBtnId, rowHtmlFn, fieldSelectors) {
            const container = document.getElementById(containerId);
            const addBtn = document.getElementById(addBtnId);

            function reindex() {
                Array.from(container.children).forEach((row, i) => {
                    const num = row.querySelector('.hs-repeat-row-num');
                    if (num) num.textContent = i + 1;
                });
            }

            function addRow(values = {}) {
                const row = document.createElement('div');
                row.className = 'hs-repeat-row';
                row.innerHTML = rowHtmlFn();
                fieldSelectors.forEach(sel => {
                    const el = row.querySelector('.' + sel);
                    if (el && values[sel] !== undefined) el.value = values[sel];
                });
                row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                    row.remove();
                    reindex();
                });
                container.appendChild(row);
                reindex();
            }

            addBtn.addEventListener('click', () => addRow());

            return {
                addRow,
                reset() { container.innerHTML = ''; },
                rows() { return Array.from(container.children); },
            };
        }

        const ssCtTabsRepeater = ssMakeRepeater('ssCtTabsRows', 'btnAddCtTab', () => `
            <div class="hs-repeat-row-head"><span class="hs-repeat-row-num"></span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
            <input type="text" class="users-manager-input row-label" placeholder="Título de pestaña (ej. En qué consiste)">
            <input type="text" class="users-manager-input row-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;">
            <textarea class="users-manager-input client-modal-textarea row-body" rows="2" placeholder="Párrafo" style="margin-top:6px;"></textarea>
            <textarea class="users-manager-input client-modal-textarea row-bullets" rows="2" placeholder="Viñetas, una por línea (opcional)" style="margin-top:6px;"></textarea>
        `, ['row-label', 'row-subtitle', 'row-body', 'row-bullets']);

        const ssBgItemsRepeater = ssMakeRepeater('ssBgItemsRows', 'btnAddBgItem', () => `
            <div class="hs-repeat-row-head"><span class="hs-repeat-row-num"></span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
            <input type="text" class="users-manager-input row-figure" placeholder="Cifra (ej. -12%)">
            <input type="text" class="users-manager-input row-title" placeholder="Título (ej. Menos combustible)" style="margin-top:6px;">
            <textarea class="users-manager-input client-modal-textarea row-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;"></textarea>
        `, ['row-figure', 'row-title', 'row-description']);

        const ssPsStepsRepeater = ssMakeRepeater('ssPsStepsRows', 'btnAddPsStep', () => `
            <div class="hs-repeat-row-head"><span class="hs-repeat-row-num"></span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
            <input type="text" class="users-manager-input row-title" placeholder="Título del paso (ej. Inspección)">
            <textarea class="users-manager-input client-modal-textarea row-description" rows="2" placeholder="Descripción" style="margin-top:6px;"></textarea>
            <input type="text" class="users-manager-input row-duration" placeholder="Duración (ej. 1 h, 6-10 h)" style="margin-top:6px;">
        `, ['row-title', 'row-description', 'row-duration']);

        function ssSyncConfigFields() {
            const type = document.getElementById('ssType').value;
            document.querySelectorAll('.config-fields').forEach(block => {
                block.style.display = block.dataset.type === type ? 'block' : 'none';
            });
        }

        function ssSyncSourceFields() {
            const source = document.getElementById('ssSource').value;
            document.querySelectorAll('.hs-source-field').forEach(block => {
                block.style.display = block.dataset.source === source ? 'block' : 'none';
            });
        }

        function ssSyncPcbSourceFields() {
            const source = document.getElementById('ssPcbSource').value;
            document.querySelectorAll('.hs-pcb-source-field').forEach(block => {
                block.style.display = block.dataset.source === source ? 'block' : 'none';
            });
        }

        document.getElementById('ssType').addEventListener('change', ssSyncConfigFields);
        document.getElementById('ssSource').addEventListener('change', ssSyncSourceFields);
        document.getElementById('ssPcbSource').addEventListener('change', ssSyncPcbSourceFields);

        const resetServiceSectionForm = () => {
            serviceSectionForm.reset();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            ssErrorsContainer.style.display = 'none';
            ssErrorsContainer.innerHTML = '';
            currentServiceSectionId = null;
            ssIsEditMode = false;
            document.getElementById('serviceSectionModalTitle').textContent = 'Nueva Sección';
            document.getElementById('serviceSectionSubmitBtn').textContent = 'Crear Sección';
            document.getElementById('ssSortOrder').value = '0';
            document.getElementById('ssIsActive').value = '1';
            ssMainProductPicker.reset();
            ssPcbProductPicker.reset();
            ssCtTabsRepeater.reset();
            ssBgItemsRepeater.reset();
            ssPsStepsRepeater.reset();
            document.getElementById('ssRhMetaLines').value = '';

            ssSyncConfigFields();
            ssSyncSourceFields();
            ssSyncPcbSourceFields();
        };

        const btnNewServiceSection = document.getElementById('btnNewServiceSection');
        if (btnNewServiceSection) {
            btnNewServiceSection.addEventListener('click', () => {
                resetServiceSectionForm();
                serviceSectionModal.style.display = 'flex';
            });
        }

        document.getElementById('closeServiceSectionModal').addEventListener('click', () => closeServiceSectionWithAnim());
        document.getElementById('cancelServiceSectionModal').addEventListener('click', () => closeServiceSectionWithAnim());
        serviceSectionModal.addEventListener('click', (e) => {
            if (e.target === serviceSectionModal) closeServiceSectionWithAnim();
        });

        // Marca un campo inválido inline: agrega .is-invalid al elemento y
        // muestra el mensaje real del servidor como .field-error-msg. La
        // limpieza de estas marcas ya existía (ver resetServiceSectionForm y
        // el inicio de este submit handler) pero nada las creaba todavía.
        function ssShowError(element, message) {
            element.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            const container = element.closest('.users-manager-email-camp') || element.parentElement;
            if (container) container.appendChild(errorSpan);
        }

        serviceSectionForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            ssErrorsContainer.style.display = 'none';

            const formData = new FormData(serviceSectionForm);

            const productIdsRaw = document.getElementById('ssProductIds').value;
            if (productIdsRaw.trim()) {
                productIdsRaw.split(',').map(v => v.trim()).filter(Boolean).forEach(id => {
                    formData.append('product_ids[]', id);
                });
            }

            const pcbProductIdsRaw = document.getElementById('ssPcbProductIds').value;
            if (pcbProductIdsRaw.trim()) {
                pcbProductIdsRaw.split(',').map(v => v.trim()).filter(Boolean).forEach(id => {
                    formData.append('pcb_product_ids[]', id);
                });
            }

            // rich_header: textarea de líneas -> rh_meta_lines[]
            document.getElementById('ssRhMetaLines').value.split('\n')
                .map(v => v.trim()).filter(Boolean)
                .forEach(line => formData.append('rh_meta_lines[]', line));

            // content_tabs
            ssCtTabsRepeater.rows().forEach((row, i) => {
                const label = row.querySelector('.row-label').value.trim();
                if (!label) return;
                formData.append(`ct_tabs[${i}][label]`, label);
                formData.append(`ct_tabs[${i}][subtitle]`, row.querySelector('.row-subtitle').value.trim());
                formData.append(`ct_tabs[${i}][body]`, row.querySelector('.row-body').value.trim());
                row.querySelector('.row-bullets').value.split('\n').map(v => v.trim()).filter(Boolean)
                    .forEach(b => formData.append(`ct_tabs[${i}][bullets][]`, b));
            });

            // benefits_grid
            ssBgItemsRepeater.rows().forEach((row, i) => {
                const title = row.querySelector('.row-title').value.trim();
                if (!title) return;
                formData.append(`bg_items[${i}][figure]`, row.querySelector('.row-figure').value.trim());
                formData.append(`bg_items[${i}][title]`, title);
                formData.append(`bg_items[${i}][description]`, row.querySelector('.row-description').value.trim());
            });

            // process_steps
            ssPsStepsRepeater.rows().forEach((row, i) => {
                const title = row.querySelector('.row-title').value.trim();
                if (!title) return;
                formData.append(`ps_steps[${i}][title]`, title);
                formData.append(`ps_steps[${i}][description]`, row.querySelector('.row-description').value.trim());
                formData.append(`ps_steps[${i}][duration]`, row.querySelector('.row-duration').value.trim());
            });

            const url = ssIsEditMode ?
                `${serviceSectionsBaseUrl}/${currentServiceSectionId}` :
                serviceSectionsBaseUrl;

            if (ssIsEditMode) formData.append('_method', 'PUT');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.status === 419) {
                    ssErrorsContainer.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    ssErrorsContainer.style.display = 'block';
                    showCenterToast('Tu sesión expiró. Por favor recarga la página e intenta de nuevo.', 'error');
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeServiceSectionWithAnim();
                    queueCenterToast(ssIsEditMode ? 'Sección actualizada correctamente.' : 'Sección creada correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errors = data.errors || {};
                    const errorList = Object.values(errors).flat();
                    ssErrorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    ssErrorsContainer.style.display = 'block';
                    showCenterToast('Revisa los campos marcados.', 'error');

                    Object.keys(errors).forEach(field => {
                        const input = serviceSectionForm.querySelector(`[name="${field}"]`);
                        if (input) ssShowError(input, errors[field][0]);
                    });
                }
            } catch (err) {
                console.error('Error:', err);
                showCenterToast('Error de conexión al guardar la sección.', 'error');
            }
        });

        function ssPopulateConfigFields(type, config) {
            config = config || {};

            if (type === 'banner') {
                document.getElementById('ssBannerImageUrl').value = config.image_url ?? '';
                document.getElementById('ssBannerLinkUrl').value = config.link_url ?? '';
                document.getElementById('ssBannerAlt').value = config.alt ?? '';
            } else if (type === 'dual_banner') {
                const left = config.left ?? {};
                const right = config.right ?? {};
                document.getElementById('ssLeftImageUrl').value = left.image_url ?? '';
                document.getElementById('ssLeftLinkUrl').value = left.link_url ?? '';
                document.getElementById('ssLeftAlt').value = left.alt ?? '';
                document.getElementById('ssRightImageUrl').value = right.image_url ?? '';
                document.getElementById('ssRightLinkUrl').value = right.link_url ?? '';
                document.getElementById('ssRightAlt').value = right.alt ?? '';
            } else if (type === 'product_carousel') {
                document.getElementById('ssSource').value = config.source ?? 'featured';
                document.getElementById('ssCategoryId').value = config.category_id ?? '';
                document.getElementById('ssBrandId').value = config.brand_id ?? '';
                document.getElementById('ssCollectionId').value = config.collection_id ?? '';
                document.getElementById('ssLimit').value = config.limit ?? 10;
                ssMainProductPicker.setSelectedIds(config.product_ids ?? []);
                ssSyncSourceFields();
            } else if (type === 'product_carousel_banner') {
                document.getElementById('ssPcbBannerImageUrl').value = config.banner_image_url ?? '';
                document.getElementById('ssPcbBannerLinkUrl').value = config.banner_link_url ?? '';
                document.getElementById('ssPcbBannerAlt').value = config.banner_alt ?? '';
                document.getElementById('ssPcbSource').value = config.source ?? 'featured';
                document.getElementById('ssPcbCategoryId').value = config.category_id ?? '';
                document.getElementById('ssPcbBrandId').value = config.brand_id ?? '';
                document.getElementById('ssPcbCollectionId').value = config.collection_id ?? '';
                document.getElementById('ssPcbLimit').value = config.limit ?? 10;
                ssPcbProductPicker.setSelectedIds(config.product_ids ?? []);
                ssSyncPcbSourceFields();
            } else if (type === 'category_grid') {
                const ids = (config.category_ids ?? []).map(String);
                Array.from(document.getElementById('ssCategoryIds').options).forEach(opt => {
                    opt.selected = ids.includes(opt.value);
                });
            } else if (type === 'html_block') {
                document.getElementById('ssHtml').value = config.html ?? '';
            } else if (type === 'faq') {
                document.getElementById('ssFaqDescription').value = config.description ?? '';
            } else if (type === 'rich_header') {
                document.getElementById('ssRhBadges').value = (config.badges ?? []).join(' · ');
                document.getElementById('ssRhWhatsappText').value = config.whatsapp_text ?? 'Cotizar por WhatsApp';
                document.getElementById('ssRhPriceLabel').value = config.price_label ?? '';
                document.getElementById('ssRhMetaLines').value = (config.meta_lines ?? []).join('\n');
                const bgIds = (config.background_image_ids ?? []).map(String);
                Array.from(document.getElementById('ssRhBackgroundImageIds').options).forEach(opt => {
                    opt.selected = bgIds.includes(opt.value);
                });
            } else if (type === 'content_tabs') {
                ssCtTabsRepeater.reset();
                (config.tabs ?? []).forEach(t => ssCtTabsRepeater.addRow({
                    'row-label': t.label ?? '', 'row-subtitle': t.subtitle ?? '',
                    'row-body': t.body ?? '', 'row-bullets': (t.bullets ?? []).join('\n'),
                }));
            } else if (type === 'benefits_grid') {
                ssBgItemsRepeater.reset();
                (config.items ?? []).forEach(i => ssBgItemsRepeater.addRow({
                    'row-figure': i.figure ?? '', 'row-title': i.title ?? '', 'row-description': i.description ?? '',
                }));
            } else if (type === 'process_steps') {
                ssPsStepsRepeater.reset();
                (config.steps ?? []).forEach(s => ssPsStepsRepeater.addRow({
                    'row-title': s.title ?? '', 'row-description': s.description ?? '', 'row-duration': s.duration ?? '',
                }));
            } else if (type === 'gallery_carousel') {
                const ids = (config.image_ids ?? []).map(String);
                Array.from(document.getElementById('ssGcImageIds').options).forEach(opt => {
                    opt.selected = ids.includes(opt.value);
                });
            } else if (type === 'rating_reviews') {
                document.getElementById('ssRrDescription').value = config.description ?? '';
                document.getElementById('ssRrReviewsPerPage').value = config.reviews_per_page ?? 3;
            } else if (type === 'cta_final') {
                document.getElementById('ssCtaHeadline').value = config.headline ?? '';
                document.getElementById('ssCtaSubtext').value = config.subtext ?? '';
                document.getElementById('ssCtaWhatsappText').value = config.whatsapp_text ?? 'Cotizar por WhatsApp';
                document.getElementById('ssCtaBackgroundImageId').value = config.background_image_id ?? '';
            }
        }

        document.querySelectorAll('.btn-edit-service-section').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                currentServiceSectionId = id;
                ssIsEditMode = true;

                fetch(`${serviceSectionsBaseUrl}/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(section => {
                        resetServiceSectionForm();
                        currentServiceSectionId = id;
                        ssIsEditMode = true;

                        document.getElementById('serviceSectionModalTitle').textContent = 'Editar Sección';
                        document.getElementById('serviceSectionSubmitBtn').textContent = 'Guardar Cambios';
                        document.getElementById('ssType').value = section.type ?? 'banner';
                        document.getElementById('ssTitle').value = section.title ?? '';
                        document.getElementById('ssSortOrder').value = section.sort_order ?? 0;
                        document.getElementById('ssIsActive').value = section.is_active ? '1' : '0';

                        ssSyncConfigFields();
                        ssPopulateConfigFields(section.type, section.config);

                        ssErrorsContainer.style.display = 'none';
                        serviceSectionModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading section:', err));
            });
        });

        const deleteServiceSectionModal = document.getElementById('deleteServiceSectionModal');
        let deleteServiceSectionId = null;

        document.querySelectorAll('.btn-delete-service-section').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteServiceSectionId = btn.dataset.id;
                document.getElementById('delServiceSectionTitle').textContent = btn.dataset.title;
                document.getElementById('delServiceSectionAvatar').textContent = btn.dataset.title.charAt(0).toUpperCase();
                deleteServiceSectionModal.classList.add('active');
            });
        });

        document.getElementById('delServiceSectionCancel').addEventListener('click', () => deleteServiceSectionModal.classList.remove('active'));
        deleteServiceSectionModal.addEventListener('click', (e) => {
            if (e.target === deleteServiceSectionModal) deleteServiceSectionModal.classList.remove('active');
        });

        document.getElementById('delServiceSectionConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${serviceSectionsBaseUrl}/${deleteServiceSectionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _method: 'DELETE' }),
                });

                const data = await response.json();

                if (response.ok) {
                    deleteServiceSectionModal.classList.remove('active');
                    queueCenterToast('Sección eliminada correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    showCenterToast(data.message ?? 'No se pudo eliminar la sección.', 'error');
                    deleteServiceSectionModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error deleting section:', err);
                showCenterToast('Error de conexión al eliminar la sección.', 'error');
            }
        });

        /* ── Arrastrar para reordenar secciones, guardado instantáneo vía AJAX ── */
        (function() {
            const tableBody = document.getElementById('serviceSectionsTableBody');
            if (!tableBody) return;

            const reorderUrl = `${serviceSectionsBaseUrl}/reordenar`;
            let dragSrcId = null;

            function currentOrder() {
                return Array.from(tableBody.querySelectorAll('.hs-row')).map(el => el.dataset.id);
            }

            function refreshSortOrderColumn() {
                Array.from(tableBody.querySelectorAll('.hs-row')).forEach((row, i) => {
                    const cell = row.querySelector('.hs-sort-order');
                    if (cell) cell.textContent = i;
                });
            }

            async function persistOrder() {
                try {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ order: currentOrder() }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        showCenterToast(data.message ?? 'No se pudo guardar el nuevo orden.', 'error');
                    } else {
                        showCenterToast('Orden actualizado.');
                    }
                } catch (err) {
                    console.error('Error saving order:', err);
                    showCenterToast('Error de conexión al guardar el orden.', 'error');
                }
            }

            tableBody.querySelectorAll('.hs-row').forEach(function(row) {
                row.addEventListener('dragstart', function() {
                    dragSrcId = row.dataset.id;
                    row.classList.add('is-dragging');
                });
                row.addEventListener('dragend', function() {
                    row.classList.remove('is-dragging');
                });
                row.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (dragSrcId === null || dragSrcId === row.dataset.id) return;
                    row.classList.add('drag-over');
                });
                row.addEventListener('dragleave', function() {
                    row.classList.remove('drag-over');
                });
                row.addEventListener('drop', function(e) {
                    e.preventDefault();
                    row.classList.remove('drag-over');
                    if (dragSrcId === null || dragSrcId === row.dataset.id) return;

                    const srcEl = tableBody.querySelector('.hs-row[data-id="' + dragSrcId + '"]');
                    dragSrcId = null;
                    if (!srcEl) return;

                    tableBody.insertBefore(srcEl, row);
                    refreshSortOrderColumn();
                    persistOrder();
                });
            });
        })();

        ssSyncConfigFields();
        ssSyncSourceFields();
        ssSyncPcbSourceFields();
    </script>
@endpush
