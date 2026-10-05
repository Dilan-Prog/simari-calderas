{{-- JS del editor de secciones (ver contrato en partials/editor.blade.php). --}}
@php
    $hsEditorCfg = [
        'baseUrl'          => url('/admin/inicio-secciones'),
        'productSearchUrl' => route('admin.home-sections.products.search'),
        'tagsUrl'          => route('admin.home-sections.tags.suggestions'),
        'pageTypes'        => $editorData['pageTypes'],
    ];
@endphp
@push('scripts')
    <script>
        if (!window.PRODUCT_VARIABLES) window.PRODUCT_VARIABLES = @json(\App\Models\Products::VARIABLE_CATALOG);
    </script>
    <script>
    (function () {
        if (window.HomeSectionEditor) return;

        const CFG = @json($hsEditorCfg);

        // Tipos que el modal sabe configurar (un tipo sin bloque
        // .config-fields no se ofrece aunque el servidor lo acepte).
        const TYPE_LABELS = {
            hero_slider: 'Slider Principal',
            banner: 'Banner',
            dual_banner: 'Banner Doble',
            product_carousel: 'Carrusel de Productos',
            product_carousel_banner: 'Carrusel con Banner',
            card_carousel: 'Carrusel de Fichas',
            category_grid: 'Grid de Categorías',
            brand_carousel: 'Carrusel de Marcas',
            html_block: 'Bloque HTML',
            faq: 'Preguntas Frecuentes',
        };
        const MAX_CARDS = 12;

        const $ = (id) => document.getElementById(id);
        const modal = $('homeSectionModal');
        // Dentro de un <form> ajeno (p. ej. la ficha de producto) el form del
        // modal quedaría anidado: se saca a <body>.
        if (modal.closest('form')) document.body.appendChild(modal);

        const form = $('homeSectionForm');
        const errorsBox = $('home-section-modal-errors');
        const typeSel = $('hsType');
        const zoneSel = $('hsZone');
        const submitBtn = $('homeSectionSubmitBtn');

        const state = { page: 'home', sectionId: null, isEdit: false, onSaved: null, mounted: false, links: {}, usage: 0 };

        const toast = (msg, kind) => {
            if (typeof window.showCenterToast === 'function') window.showCenterToast(msg, kind);
        };

        function escHtml(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        /* ── Cierre animado ── */
        function closeModal() {
            const content = modal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            modal.style.transition = 'opacity 0.2s ease-in';
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '';
                modal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        }

        /* ── "Selección Manual": buscador de productos por nombre/SKU + chips ──
           Mantiene un input oculto con los ids separados por coma; el submit
           los reparte en product_ids[] / pcb_product_ids[]. */
        function initManualProductPicker(opts) {
            const wrap = $(opts.wrapId);
            const searchInput = $(opts.inputId);
            const dropdown = $(opts.dropdownId);
            const list = $(opts.listId);
            const emptyMsg = $(opts.emptyId);
            const chipsContainer = $(opts.chipsId);
            const hiddenInput = $(opts.hiddenId);

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
                    chip.innerHTML = `<span>${escHtml(p.name)}</span><small>${escHtml(p.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`;
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
                    li.innerHTML = `<span>${escHtml(p.name)}</span><small>SKU: ${escHtml(p.sku)}</small>`;
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
                    const url = new URL(CFG.productSearchUrl, window.location.origin);
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

        const mainProductPicker = initManualProductPicker({
            wrapId: 'hsProductSearch', inputId: 'hsProductSearchInput', dropdownId: 'hsProductSearchDropdown',
            listId: 'hsProductSearchList', emptyId: 'hsProductSearchEmpty', chipsId: 'hsProductChips', hiddenId: 'hsProductIds',
        });
        const pcbProductPicker = initManualProductPicker({
            wrapId: 'hsPcbProductSearch', inputId: 'hsPcbProductSearchInput', dropdownId: 'hsPcbProductSearchDropdown',
            listId: 'hsPcbProductSearchList', emptyId: 'hsPcbProductSearchEmpty', chipsId: 'hsPcbProductChips', hiddenId: 'hsPcbProductIds',
        });

        /* ── Fuente "Por Etiqueta": sugerencias en un datalist ── */
        (function () {
            const dl = $('hsTagList');
            let timer = null;
            async function load(q) {
                try {
                    const res = await fetch(`${CFG.tagsUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                    if (!res.ok) return;
                    const tags = await res.json();
                    dl.innerHTML = tags.map(t => `<option value="${escHtml(t)}"></option>`).join('');
                } catch (e) { /* sin sugerencias */ }
            }
            ['hsTag', 'hsPcbTag'].forEach(id => {
                const el = $(id);
                el.addEventListener('focus', () => load(el.value.trim()));
                el.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => load(el.value.trim()), 250);
                });
            });
        })();

        /* ── Visibilidad según página / tipo / origen ── */
        const inPages = (el, page) => el.dataset.pages.split(/\s+/).includes(page);

        function applyPage(page) {
            state.page = page;
            $('hsPage').value = page;

            // Tipos permitidos en esta página.
            const allowed = (CFG.pageTypes[page] || CFG.pageTypes.home).filter(t => TYPE_LABELS[t]);
            const prev = typeSel.value;
            typeSel.innerHTML = allowed.map(t => `<option value="${t}">${TYPE_LABELS[t]}</option>`).join('');
            if (allowed.includes(prev)) typeSel.value = prev;

            // Campos propios de ciertas páginas (se ocultan Y se deshabilitan
            // para que no viajen en el FormData).
            modal.querySelectorAll('[data-pages]').forEach(el => {
                if (el.tagName === 'OPTION') return;
                const on = inPages(el, page);
                el.style.display = on ? '' : 'none';
                if (/^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName)) el.disabled = !on;
                el.querySelectorAll('input,select,textarea').forEach(i => { i.disabled = !on; });
            });

            // Opciones que dependen de la página (related_* solo con producto).
            modal.querySelectorAll('select').forEach(sel => {
                if (!sel._allOptions) {
                    if (!sel.querySelector('option[data-pages]')) return;
                    sel._allOptions = Array.from(sel.options);
                }
                const cur = sel.value;
                sel.innerHTML = '';
                sel._allOptions.forEach(o => {
                    if (!o.dataset.pages || inPages(o, page)) sel.appendChild(o);
                });
                if (Array.from(sel.options).some(o => o.value === cur)) sel.value = cur;
            });

            // El nombre interno solo es obligatorio en plantillas.
            const req = modal.querySelector('.hs-name-required');
            if (req) req.style.display = page === 'product_template' ? '' : 'none';

            syncConfigFields();
            syncZone();
        }

        function syncConfigFields() {
            const type = typeSel.value;
            modal.querySelectorAll('.config-fields').forEach(block => {
                block.style.display = block.dataset.type === type ? 'block' : 'none';
            });
        }

        function syncSourceFields() {
            const source = $('hsSource').value;
            modal.querySelectorAll('.hs-source-field').forEach(block => {
                block.style.display = block.dataset.source === source ? 'block' : 'none';
            });
        }

        function syncPcbSourceFields() {
            const source = $('hsPcbSource').value;
            modal.querySelectorAll('.hs-pcb-source-field').forEach(block => {
                block.style.display = block.dataset.source === source ? 'block' : 'none';
            });
        }

        // card_carousel fija la zona lateral; el resto puede ir en pila o lateral.
        function syncZone() {
            const note = $('hsZoneNote');
            const stackOpt = zoneSel.querySelector('option[value="stack"]');
            if (typeSel.value === 'card_carousel') {
                zoneSel.value = 'sidebar';
                stackOpt.disabled = true;
                note.textContent = 'El carrusel de fichas solo puede ir en la barra lateral.';
                note.style.display = '';
            } else {
                stackOpt.disabled = false;
                note.style.display = 'none';
            }
        }

        typeSel.addEventListener('change', () => { syncConfigFields(); syncZone(); });
        $('hsSource').addEventListener('change', syncSourceFields);
        $('hsPcbSource').addEventListener('change', syncPcbSourceFields);

        /* ── Campos de destino de enlace (window.LinkPicker.mountField) ── */
        function ensureMounted() {
            if (state.mounted) return true;
            if (!window.LinkPicker || !window.LinkPicker.mountField) return false;
            [
                ['heading', 'hsHeadingLinkMount'],
                ['banner', 'hsBannerLinkMount'],
                ['left', 'hsLeftLinkMount'],
                ['right', 'hsRightLinkMount'],
                ['pcb', 'hsPcbBannerLinkMount'],
            ].forEach(([key, id]) => {
                state.links[key] = window.LinkPicker.mountField($(id), { value: null });
            });
            state.mounted = true;
            return true;
        }

        // Config legada: link_url suelto → destino "URL personalizada".
        function linkFromConfig(link, legacyUrl) {
            if (link && link.type) return link;
            if (legacyUrl) return { type: 'custom', id: null, url: legacyUrl, new_tab: false };
            return null;
        }

        /* ── Carrusel de fichas (card_carousel) ── */
        const cardList = $('hsCardList');
        const cardAddBtn = $('hsCardAdd');
        let cardSeq = 0;

        function renumberCards() {
            Array.from(cardList.children).forEach((row, i) => {
                row.querySelector('.hs-card-row__num').textContent = 'Ficha ' + (i + 1);
            });
            cardAddBtn.disabled = cardList.children.length >= MAX_CARDS;
        }

        function addCardRow(data) {
            if (cardList.children.length >= MAX_CARDS) {
                toast('Máximo ' + MAX_CARDS + ' fichas.', 'error');
                return;
            }
            data = data || {};
            const n = ++cardSeq;
            const row = document.createElement('div');
            row.className = 'hs-card-row';
            row.innerHTML =
                '<div class="hs-card-row__head">' +
                    '<span class="hs-card-row__num"></span>' +
                    '<div class="hs-card-row__actions">' +
                        '<button type="button" class="hs-card-btn" data-act="up" title="Subir">↑</button>' +
                        '<button type="button" class="hs-card-btn" data-act="down" title="Bajar">↓</button>' +
                        '<button type="button" class="hs-card-btn hs-card-btn--remove" data-act="remove" title="Quitar ficha">&times;</button>' +
                    '</div>' +
                '</div>' +
                '<div class="users-manager-email-camp">' +
                    '<label class="supliers-manager-slider-label">Imagen</label>' +
                    '<div class="img-picker-field">' +
                        '<img class="hs-card-row__thumb" alt="" style="display:none;">' +
                        `<input type="text" class="users-manager-input" id="hsCardImg${n}" data-card="image_url" placeholder="https://...">` +
                        `<button type="button" class="img-picker-trigger-btn" data-act="pick">Seleccionar</button>` +
                    '</div>' +
                '</div>' +
                '<div class="user-manager-form">' +
                    '<div><label class="supliers-manager-slider-label">Texto</label>' +
                        '<input type="text" class="users-manager-input" data-card="text" maxlength="120" placeholder="Ej: Envío gratis"></div>' +
                    '<div><label class="supliers-manager-slider-label">Destino del enlace</label>' +
                        '<div class="hs-card-link"></div></div>' +
                '</div>';

            const imgInput = row.querySelector('[data-card="image_url"]');
            const thumb = row.querySelector('.hs-card-row__thumb');
            const syncThumb = () => {
                const v = imgInput.value.trim();
                thumb.style.display = v ? '' : 'none';
                if (v) thumb.src = v;
            };
            imgInput.addEventListener('input', syncThumb);
            imgInput.value = data.image_url ?? '';
            row.querySelector('[data-card="text"]').value = data.text ?? '';
            syncThumb();

            row._link = window.LinkPicker.mountField(row.querySelector('.hs-card-link'), { value: data.link ?? null });

            row.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-act]');
                if (!btn) return;
                const act = btn.dataset.act;
                if (act === 'pick') {
                    if (typeof window.openImagePicker === 'function') window.openImagePicker('hsCardImg' + n);
                } else if (act === 'remove') {
                    row.remove();
                    renumberCards();
                } else if (act === 'up' && row.previousElementSibling) {
                    cardList.insertBefore(row, row.previousElementSibling);
                    renumberCards();
                } else if (act === 'down' && row.nextElementSibling) {
                    cardList.insertBefore(row.nextElementSibling, row);
                    renumberCards();
                }
            });

            cardList.appendChild(row);
            renumberCards();
        }

        cardAddBtn.addEventListener('click', () => {
            if (ensureMounted()) addCardRow();
        });

        function clearCards() {
            cardList.innerHTML = '';
            renumberCards();
        }

        /* ── Reset / populate ── */
        function resetForm() {
            form.reset();
            modal.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            modal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            errorsBox.style.display = 'none';
            errorsBox.innerHTML = '';

            state.sectionId = null;
            state.isEdit = false;
            state.usage = 0;
            $('hsSortOrder').value = '0';
            $('hsIsActive').value = '1';
            $('hsProductId').value = '';
            $('hsProductId').disabled = true;
            $('hsTemplateWarning').style.display = 'none';
            $('hsTemplateWarning').textContent = '';
            mainProductPicker.reset();
            pcbProductPicker.reset();
            clearCards();
            Object.values(state.links).forEach(f => f.setValue(null));
        }

        const TITLES = {
            product_template: ['Nueva plantilla de producto', 'Editar plantilla de producto', 'Crear plantilla'],
            product_custom: ['Nueva sección propia', 'Editar sección propia', 'Crear sección'],
        };

        function setTexts(page, edit) {
            const t = TITLES[page];
            $('homeSectionModalTitle').textContent = t ? t[edit ? 1 : 0] : (edit ? 'Editar Sección' : 'Nueva Sección');
            submitBtn.textContent = edit ? 'Guardar Cambios' : (t ? t[2] : 'Crear Sección');
        }

        function populateConfigFields(type, config) {
            config = config || {};

            if (type === 'banner') {
                $('hsBannerImageUrl').value = config.image_url ?? '';
                $('hsBannerAlt').value = config.alt ?? '';
                state.links.banner.setValue(linkFromConfig(config.link, config.link_url));
            } else if (type === 'dual_banner') {
                const left = config.left ?? {};
                const right = config.right ?? {};
                $('hsLeftImageUrl').value = left.image_url ?? '';
                $('hsLeftAlt').value = left.alt ?? '';
                state.links.left.setValue(linkFromConfig(left.link, left.link_url));
                $('hsRightImageUrl').value = right.image_url ?? '';
                $('hsRightAlt').value = right.alt ?? '';
                state.links.right.setValue(linkFromConfig(right.link, right.link_url));
            } else if (type === 'product_carousel') {
                $('hsSource').value = config.source ?? 'featured';
                $('hsCategoryId').value = config.category_id ?? '';
                $('hsBrandId').value = config.brand_id ?? '';
                $('hsCollectionId').value = config.collection_id ?? '';
                $('hsTag').value = config.tag ?? '';
                $('hsEyebrow').value = config.eyebrow ?? '';
                $('hsExcludeCurrent').checked = config.exclude_current ?? true;
                $('hsLimit').value = config.limit ?? 10;
                mainProductPicker.setSelectedIds(config.product_ids ?? []);
                syncSourceFields();
            } else if (type === 'product_carousel_banner') {
                $('hsPcbBannerImageUrl').value = config.banner_image_url ?? '';
                $('hsPcbBannerAlt').value = config.banner_alt ?? '';
                state.links.pcb.setValue(linkFromConfig(config.banner_link, config.banner_link_url));
                $('hsPcbSource').value = config.source ?? 'featured';
                $('hsPcbCategoryId').value = config.category_id ?? '';
                $('hsPcbBrandId').value = config.brand_id ?? '';
                $('hsPcbCollectionId').value = config.collection_id ?? '';
                $('hsPcbTag').value = config.tag ?? '';
                $('hsPcbExcludeCurrent').checked = config.exclude_current ?? true;
                $('hsPcbLimit').value = config.limit ?? 10;
                pcbProductPicker.setSelectedIds(config.product_ids ?? []);
                syncPcbSourceFields();
            } else if (type === 'card_carousel') {
                clearCards();
                (config.items ?? []).slice(0, MAX_CARDS).forEach(item => addCardRow(item));
            } else if (type === 'category_grid') {
                const ids = (config.category_ids ?? []).map(String);
                Array.from($('hsCategoryIds').options).forEach(opt => {
                    opt.selected = ids.includes(opt.value);
                });
            } else if (type === 'html_block') {
                $('hsHtml').value = config.html ?? '';
            } else if (type === 'faq') {
                $('hsFaqDescription').value = config.description ?? '';
            }
        }

        function showModal() {
            errorsBox.style.display = 'none';
            modal.style.display = 'flex';
        }

        /* ── API pública ── */
        async function open(opts) {
            opts = opts || {};
            if (!ensureMounted()) {
                toast('El selector de enlaces todavía no carga. Recarga la página e intenta de nuevo.', 'error');
                return;
            }

            resetForm();
            state.onSaved = typeof opts.onSaved === 'function' ? opts.onSaved : null;

            if (!opts.sectionId) {
                const page = opts.page || 'home';
                applyPage(page);
                if (opts.zone && (page === 'product_template' || page === 'product_custom')) {
                    zoneSel.value = opts.zone === 'sidebar' ? 'sidebar' : 'stack';
                    syncZone();
                }
                if (opts.productId && page === 'product_custom') {
                    $('hsProductId').value = String(opts.productId);
                    $('hsProductId').disabled = false;
                }
                syncSourceFields();
                syncPcbSourceFields();
                setTexts(page, false);
                showModal();
                return;
            }

            try {
                const res = await fetch(`${CFG.baseUrl}/editar/${opts.sectionId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const section = await res.json();

                state.sectionId = opts.sectionId;
                state.isEdit = true;
                state.usage = section.usage_count || 0;

                applyPage(section.page || opts.page || 'home');
                typeSel.value = section.type;
                if (typeSel.value !== section.type) {
                    // Tipo que el modal no ofrece (p. ej. pool_calculator): se agrega para no perderlo al guardar.
                    typeSel.insertAdjacentHTML('beforeend', `<option value="${escHtml(section.type)}">${escHtml(section.type)}</option>`);
                    typeSel.value = section.type;
                }
                $('hsTitle').value = section.title ?? '';
                $('hsName').value = section.name ?? '';
                $('hsSortOrder').value = section.sort_order ?? 0;
                $('hsIsActive').value = section.is_active ? '1' : '0';
                zoneSel.value = section.zone === 'sidebar' ? 'sidebar' : 'stack';
                state.links.heading.setValue(section.heading_link ?? null);

                syncConfigFields();
                syncZone();
                populateConfigFields(section.type, section.config);

                if (state.usage > 0) {
                    const w = $('hsTemplateWarning');
                    w.textContent = `Esta plantilla está asignada a ${state.usage} producto(s). Los cambios se aplican a todos.`;
                    w.style.display = '';
                }

                setTexts(section.page, true);
                showModal();
            } catch (err) {
                console.error('Error loading home section:', err);
                toast('No se pudo cargar la sección.', 'error');
            }
        }

        /* ── Submit ── */
        function showFieldError(element, message) {
            element.classList.add('is-invalid');
            const span = document.createElement('span');
            span.className = 'field-error-msg';
            span.innerText = message;
            const container = element.closest('.mb-3') || element.closest('.mb-4') || element.parentElement;
            if (container) container.appendChild(span);
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            modal.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            modal.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            errorsBox.style.display = 'none';

            const fd = new FormData(form);
            const LP = window.LinkPicker;

            // Selección manual: los ids viajan en un input de texto separado por comas.
            [['hsProductIds', 'product_ids[]'], ['hsPcbProductIds', 'pcb_product_ids[]']].forEach(([id, name]) => {
                const raw = $(id).value;
                if (raw.trim()) raw.split(',').map(v => v.trim()).filter(Boolean).forEach(v => fd.append(name, v));
            });

            // Destinos de enlace.
            LP.appendToFormData(fd, 'heading_link', state.links.heading.getValue());
            const type = typeSel.value;
            if (type === 'banner') {
                LP.appendToFormData(fd, 'banner_link', state.links.banner.getValue());
            } else if (type === 'dual_banner') {
                LP.appendToFormData(fd, 'left_link', state.links.left.getValue());
                LP.appendToFormData(fd, 'right_link', state.links.right.getValue());
            } else if (type === 'product_carousel_banner') {
                LP.appendToFormData(fd, 'pcb_banner_link', state.links.pcb.getValue());
            } else if (type === 'card_carousel') {
                Array.from(cardList.children).forEach((row, i) => {
                    fd.append(`card_items[${i}][image_url]`, row.querySelector('[data-card="image_url"]').value.trim());
                    fd.append(`card_items[${i}][text]`, row.querySelector('[data-card="text"]').value.trim());
                    LP.appendToFormData(fd, `card_items[${i}][link]`, row._link.getValue());
                });
            }

            const url = state.isEdit ? `${CFG.baseUrl}/editar/${state.sectionId}` : `${CFG.baseUrl}/crear`;
            if (state.isEdit) fd.append('_method', 'PUT');

            submitBtn.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                if (response.status === 419) {
                    errorsBox.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    errorsBox.style.display = 'block';
                    toast('Tu sesión expiró. Por favor recarga la página e intenta de nuevo.', 'error');
                    return;
                }

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    const cb = state.onSaved;
                    closeModal();
                    if (cb) cb(data.section, data);
                } else if (response.status === 422) {
                    const errors = data.errors || {};
                    errorsBox.innerHTML = Object.values(errors).flat().map(m => `<p>${escHtml(m)}</p>`).join('');
                    errorsBox.style.display = 'block';
                    toast('Revisa los campos marcados.', 'error');

                    Object.keys(errors).forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) showFieldError(input, errors[field][0]);
                    });
                } else {
                    errorsBox.innerHTML = `<p>${escHtml(data.message || 'No se pudo guardar la sección.')}</p>`;
                    errorsBox.style.display = 'block';
                    toast(data.message || 'No se pudo guardar la sección.', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                toast('Error de conexión al guardar la sección.', 'error');
            } finally {
                submitBtn.disabled = false;
            }
        });

        $('closeHomeSectionModal').addEventListener('click', closeModal);
        $('cancelHomeSectionModal').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Estado inicial coherente (el modal arranca oculto).
        applyPage('home');
        syncSourceFields();
        syncPcbSourceFields();

        window.HomeSectionEditor = { open, close: closeModal };
    })();
    </script>
@endpush
