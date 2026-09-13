/**
 * service-page-live-editor.js
 *
 * Editor en vivo (BETA, exclusivo de Páginas de Servicio) — panel de 3
 * columnas: lista de bloques arrastrable, formulario de edición por tipo de
 * sección, y preview en <iframe> renderizado por el servidor a partir de un
 * borrador en memoria (sin persistir nada hasta "Guardar cambios").
 *
 * Datos de arranque inyectados por resources/views/admin/service-pages/live-
 * editor.blade.php en window.__LIVE_EDITOR__:
 *   { previewUrl, saveUrl, editUrl, productsSearchUrl,
 *     sections, categories, brands, collections, images }
 *
 * Patrones reusados de otros archivos del admin (ver comentarios inline):
 *  - Debounce + iframe.srcdoc: resources/js/admin/email-template-editor.js
 *  - Drag & drop nativo (dragstart/dragover/dragleave/drop):
 *    resources/views/admin/service-pages/partials/_section_scripts.blade.php
 *  - Buscador de productos con chips: mismo archivo (initServiceSectionProductPicker)
 *  - Config exacta por tipo de sección: app/Http/Controllers/Backend/ServicePageController.php
 *    (método que arma el config desde el modal clásico) — se siguió ESE shape
 *    real, no el de la descripción de la tarea, cuando divergían (ver nota en
 *    product_carousel_banner más abajo).
 */
(function () {
    const DATA = window.__LIVE_EDITOR__;
    if (!DATA) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const TYPE_LABELS = {
        banner: 'Banner',
        dual_banner: 'Banner Doble',
        product_carousel: 'Carrusel de Productos',
        product_carousel_banner: 'Carrusel con Banner',
        category_grid: 'Grid de Categorías',
        brand_carousel: 'Carrusel de Marcas',
        html_block: 'Bloque HTML',
        faq: 'Preguntas Frecuentes',
        rich_header: 'Encabezado enriquecido',
        content_tabs: 'Descripción por secciones',
        benefits_grid: 'Beneficios / características',
        process_steps: 'Proceso / cómo funciona',
        gallery_carousel: 'Galería / carrusel',
        rating_reviews: 'Rating y reseñas',
        cta_final: 'CTA final',
    };

    // Iconos por tipo de bloque (mismo set de stroke-icons ya usado en el
    // resto del admin) — puramente decorativo, no afecta el config guardado.
    const ICONS = {
        banner: '<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',
        dual_banner: '<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',
        product_carousel: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        product_carousel_banner: '<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',
        category_grid: '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',
        brand_carousel: '<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
        html_block: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        faq: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
        rich_header: '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',
        content_tabs: '<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',
        benefits_grid: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        process_steps: '<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',
        gallery_carousel: '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        rating_reviews: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        cta_final: '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        default: '<rect width="18" height="18" x="3" y="3" rx="2"/>',
    };

    const SOURCE_LABELS = {
        featured: 'Destacados',
        new: 'Nuevos',
        recommended: 'Recomendados',
        category: 'Por Categoría',
        brand: 'Por Marca',
        collection: 'Por Colección',
        manual: 'Selección Manual',
    };

    function defaultConfigForType(type) {
        switch (type) {
            case 'banner':
                return { image_url: '', link_url: '', alt: '' };
            case 'dual_banner':
                return {
                    left: { image_url: '', link_url: '', alt: '' },
                    right: { image_url: '', link_url: '', alt: '' },
                };
            case 'product_carousel':
                return { source: 'featured', category_id: null, brand_id: null, collection_id: null, product_ids: [], limit: 10 };
            case 'product_carousel_banner':
                // NOTA: aunque la tarea sugería nombres "limpios" (ej. banner:{...})
                // ya que este panel arma el JSON directo, el partial público real
                // (resources/views/frontend/shop/home/sections/product-carousel-banner.blade.php)
                // lee banner_image_url/banner_link_url/banner_alt en el nivel raíz
                // del config — mismo shape que ServicePageController ya guarda desde
                // el modal clásico. Se sigue ESE shape verificado para que el
                // preview y el guardado no queden desalineados con el render público.
                return {
                    banner_image_url: '', banner_link_url: '', banner_alt: '',
                    source: 'featured', category_id: null, brand_id: null, collection_id: null,
                    product_ids: [], limit: 10,
                };
            case 'category_grid':
                return { category_ids: [] };
            case 'brand_carousel':
                return {};
            case 'html_block':
                return { html: '' };
            case 'faq':
                return { description: '' };
            case 'rich_header':
                return { badges: [], whatsapp_text: 'Cotizar por WhatsApp', meta_lines: [], background_image_ids: [], price_label: '' };
            case 'content_tabs':
                return { tabs: [] };
            case 'benefits_grid':
                return { items: [] };
            case 'process_steps':
                return { steps: [] };
            case 'gallery_carousel':
                return { image_ids: [] };
            case 'rating_reviews':
                return { description: '', reviews_per_page: 3 };
            case 'cta_final':
                return { headline: '', subtext: '', whatsapp_text: 'Cotizar por WhatsApp', background_image_id: null };
            default:
                return {};
        }
    }

    // ── Estado en memoria ────────────────────────────────────────────
    let uidCounter = 0;
    const nextUid = () => 'u' + (++uidCounter);

    const draftSections = (DATA.sections || []).map((s) => ({
        _uid: nextUid(),
        id: s.id ?? null,
        type: s.type,
        title: s.title ?? '',
        config: s.config && typeof s.config === 'object' ? s.config : {},
        is_active: s.is_active !== false,
    }));

    let selectedUid = null;
    let panelMode = 'empty'; // 'empty' | 'block' | 'general'
    let isDirty = false;
    let viewport = 'desktop';

    const generalData = Object.assign({
        name: '', slug: '', short_description: '', price: '', currency: 'MXN',
        seo_title: '', seo_description: '', is_active: false,
    }, DATA.general || {});

    // ── Elementos ────────────────────────────────────────────────────
    const blocksList = document.getElementById('leBlocksList');
    const addTypeSelect = document.getElementById('leAddBlockType');
    const addBtn = document.getElementById('leAddBlockBtn');
    const editPanel = document.getElementById('leEditPanel');
    const iframe = document.getElementById('leIframe');
    const dirtyIndicator = document.getElementById('leDirtyIndicator');
    const saveBtn = document.getElementById('leSaveBtn');
    const viewportToggle = document.getElementById('leViewportToggle');
    const generalInfoBtn = document.getElementById('leGeneralInfoBtn');
    const pageHeading = document.querySelector('.live-editor-heading-row__left h1');

    // ── Modal de confirmación al eliminar un bloque (reemplaza confirm()) ──
    const deleteModal = document.getElementById('leDeleteModal');
    const deleteModalTitle = document.getElementById('leDeleteModalTitle');
    const deleteModalAvatar = document.getElementById('leDeleteModalAvatar');
    const deleteModalCancel = document.getElementById('leDeleteModalCancel');
    const deleteModalConfirm = document.getElementById('leDeleteModalConfirm');
    let pendingDeleteUid = null;

    function openDeleteModal(section) {
        pendingDeleteUid = section._uid;
        const label = section.title || TYPE_LABELS[section.type] || section.type;
        deleteModalTitle.textContent = label;
        deleteModalAvatar.textContent = label.charAt(0).toUpperCase();
        deleteModal.classList.add('active');
    }

    function closeDeleteModal() {
        pendingDeleteUid = null;
        deleteModal.classList.remove('active');
    }

    deleteModalCancel.addEventListener('click', closeDeleteModal);
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeDeleteModal();
    });

    deleteModalConfirm.addEventListener('click', () => {
        if (!pendingDeleteUid) return;
        const uid = pendingDeleteUid;
        const idx = draftSections.findIndex((s) => s._uid === uid);
        if (idx !== -1) draftSections.splice(idx, 1);
        if (selectedUid === uid) {
            selectedUid = null;
            renderPanel();
        }
        closeDeleteModal();
        markDirty();
        renderBlocksList();
        schedulePreview();
    });

    function escHtml(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function markDirty() {
        if (isDirty) return;
        isDirty = true;
        dirtyIndicator.textContent = '● Cambios sin guardar';
        dirtyIndicator.className = 'live-editor-status is-dirty';
    }

    function markSaved() {
        isDirty = false;
        dirtyIndicator.textContent = 'Guardado';
        dirtyIndicator.className = 'live-editor-status is-saved';
    }

    function findSection(uid) {
        return draftSections.find((s) => s._uid === uid) || null;
    }

    // ── Columna izquierda: lista de bloques ─────────────────────────
    let dragSrcUid = null;

    function renderBlocksList() {
        blocksList.innerHTML = '';

        if (!draftSections.length) {
            const empty = document.createElement('div');
            empty.className = 'live-editor-blocks-empty';
            empty.textContent = 'Este servicio todavía no tiene bloques. Agrega uno abajo.';
            blocksList.appendChild(empty);
            return;
        }

        draftSections.forEach((section) => {
            const row = document.createElement('div');
            row.className = 'live-editor-block-row';
            if (panelMode === 'block' && section._uid === selectedUid) row.classList.add('is-selected');
            if (!section.is_active) row.classList.add('is-inactive');
            row.draggable = true;
            row.dataset.uid = section._uid;

            const iconPath = ICONS[section.type] || ICONS.default;

            row.innerHTML = `
                <span class="live-editor-block-drag-handle" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPath}</svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">${escHtml(section.title || TYPE_LABELS[section.type] || section.type)}</span>
                    <span class="live-editor-block-type">${escHtml(TYPE_LABELS[section.type] || section.type)}</span>
                </span>
                <span class="live-editor-block-actions">
                    <button type="button" class="live-editor-toggle-active ${section.is_active ? 'is-on' : ''}" title="Activa/Inactiva"></button>
                    <button type="button" class="live-editor-block-delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            `;

            row.addEventListener('click', (e) => {
                if (e.target.closest('.live-editor-toggle-active') || e.target.closest('.live-editor-block-delete')) return;
                selectSection(section._uid);
            });

            row.querySelector('.live-editor-toggle-active').addEventListener('click', (e) => {
                e.stopPropagation();
                section.is_active = !section.is_active;
                markDirty();
                renderBlocksList();
                schedulePreview();
            });

            row.querySelector('.live-editor-block-delete').addEventListener('click', (e) => {
                e.stopPropagation();
                openDeleteModal(section);
            });

            // ── Drag & drop nativo, mismo patrón que _section_scripts.blade.php
            //    (Arrastrar para reordenar secciones), adaptado para reordenar
            //    solo el array en memoria en vez de hacer fetch inmediato. ──
            row.addEventListener('dragstart', () => {
                dragSrcUid = section._uid;
                row.classList.add('is-dragging');
            });
            row.addEventListener('dragend', () => {
                row.classList.remove('is-dragging');
            });
            row.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (dragSrcUid === null || dragSrcUid === section._uid) return;
                row.classList.add('drag-over');
            });
            row.addEventListener('dragleave', () => {
                row.classList.remove('drag-over');
            });
            row.addEventListener('drop', (e) => {
                e.preventDefault();
                row.classList.remove('drag-over');
                if (dragSrcUid === null || dragSrcUid === section._uid) return;

                const srcIdx = draftSections.findIndex((s) => s._uid === dragSrcUid);
                const targetIdx = draftSections.findIndex((s) => s._uid === section._uid);
                dragSrcUid = null;
                if (srcIdx === -1 || targetIdx === -1) return;

                const [moved] = draftSections.splice(srcIdx, 1);
                draftSections.splice(targetIdx, 0, moved);

                markDirty();
                renderBlocksList();
                schedulePreview();
            });

            blocksList.appendChild(row);
        });
    }

    function selectSection(uid) {
        selectedUid = uid;
        panelMode = 'block';
        generalInfoBtn.classList.remove('is-selected');
        renderBlocksList();
        renderPanel();
    }

    function selectGeneral() {
        selectedUid = null;
        panelMode = 'general';
        generalInfoBtn.classList.add('is-selected');
        renderBlocksList();
        renderPanel();
    }

    generalInfoBtn.addEventListener('click', selectGeneral);

    addBtn.addEventListener('click', () => {
        const type = addTypeSelect.value;
        const section = {
            _uid: nextUid(),
            id: null,
            type,
            title: '',
            config: defaultConfigForType(type),
            is_active: true,
        };
        draftSections.push(section);
        markDirty();
        selectSection(section._uid);
        schedulePreview();
    });

    // ── Columna central: panel de edición ───────────────────────────
    function field(labelText, innerHtml, extraClass) {
        return `<div class="live-editor-field ${extraClass || ''}">
            <label>${escHtml(labelText)}</label>
            ${innerHtml}
        </div>`;
    }

    function imagesOptionsHtml(selectedIds, images) {
        const ids = (selectedIds || []).map(String);
        return (images || []).map((img) => (
            `<option value="${img.id}" ${ids.includes(String(img.id)) ? 'selected' : ''}>${escHtml(img.alt_text || ('Imagen #' + img.id))}</option>`
        )).join('');
    }

    // ── Panel "Información general" — campos propios de ServicePage
    //    (no son una sección), se guardan aparte vía PUT a DATA.generalUrl
    //    (ServicePageController::updateGeneral), sin tocar rating_*/faqs/
    //    imágenes para no pisar esos datos si se guarda solo este panel. ──
    function renderGeneralPanel() {
        editPanel.innerHTML = `
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </span>
                    <span>
                        <strong>Información general</strong>
                        <small>Nombre, slug, precio y SEO</small>
                    </span>
                </span>
            </div>
            ${field('Nombre', `<input type="text" class="users-manager-input" id="leGenName" value="${escHtml(generalData.name)}">`)}
            ${field('Slug (URL)', `<input type="text" class="users-manager-input" id="leGenSlug" value="${escHtml(generalData.slug)}">`, '')}
            <div class="live-editor-field-row">
                ${field('Tipo de página', `
                    <select class="users-manager-select" id="leGenPageType">
                        <option value="service" ${generalData.page_type === 'service' ? 'selected' : ''}>Servicio (nivel 3)</option>
                        <option value="category" ${generalData.page_type === 'category' ? 'selected' : ''}>Categoría (nivel 2)</option>
                        <option value="hub" ${generalData.page_type === 'hub' ? 'selected' : ''}>Hub — /servicios (nivel 1)</option>
                    </select>
                `)}
                ${field('Página padre', `
                    <select class="users-manager-select" id="leGenParentId">
                        <option value="">Sin padre (nivel raíz)</option>
                        ${(DATA.eligibleParents || []).map((p) => `<option value="${p.id}" ${String(generalData.parent_id) === String(p.id) ? 'selected' : ''}>${escHtml(p.name)} (${p.page_type === 'hub' ? 'Hub' : 'Categoría'})</option>`).join('')}
                    </select>
                `, 'leGenParentField')}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${field('Descripción corta', `<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${escHtml(generalData.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${field('Precio (opcional)', `<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${escHtml(generalData.price)}">`)}
                ${field('Moneda', `<input type="text" class="users-manager-input" id="leGenCurrency" value="${escHtml(generalData.currency)}" maxlength="10">`)}
            </div>
            ${field('Estado', `
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${generalData.is_active ? 'checked' : ''}> Publicado (visible en el sitio público)
                </label>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${field('Título SEO', `<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${escHtml(generalData.seo_title)}" maxlength="160">`)}
            ${field('Descripción SEO', `<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${escHtml(generalData.seo_description)}</textarea>`)}
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `;

        editPanel.querySelector('#leGenSaveBtn').addEventListener('click', saveGeneralInfo);

        const pageTypeSelect = editPanel.querySelector('#leGenPageType');
        const parentField = editPanel.querySelector('.leGenParentField');
        const toggleParentField = () => {
            const hidesParent = pageTypeSelect.value === 'hub' || pageTypeSelect.value === 'category';
            if (parentField) parentField.style.display = hidesParent ? 'none' : '';
        };
        pageTypeSelect.addEventListener('change', toggleParentField);
        toggleParentField();
    }

    async function saveGeneralInfo() {
        const btn = editPanel.querySelector('#leGenSaveBtn');
        const errorsBox = editPanel.querySelector('#leGenErrors');
        errorsBox.style.display = 'none';
        errorsBox.innerHTML = '';
        document.querySelectorAll('#leEditPanel .is-invalid').forEach((el) => el.classList.remove('is-invalid'));

        const pageType = editPanel.querySelector('#leGenPageType').value;
        const payload = {
            name: editPanel.querySelector('#leGenName').value,
            slug: editPanel.querySelector('#leGenSlug').value,
            page_type: pageType,
            parent_id: pageType === 'hub' ? '' : editPanel.querySelector('#leGenParentId').value,
            short_description: editPanel.querySelector('#leGenShortDesc').value,
            price: editPanel.querySelector('#leGenPrice').value,
            currency: editPanel.querySelector('#leGenCurrency').value,
            is_active: editPanel.querySelector('#leGenIsActive').checked,
            seo_title: editPanel.querySelector('#leGenSeoTitle').value,
            seo_description: editPanel.querySelector('#leGenSeoDesc').value,
        };

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Guardando...';

        try {
            const res = await fetch(DATA.generalUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (res.ok) {
                Object.assign(generalData, data.servicePage);
                if (pageHeading) pageHeading.textContent = generalData.name;
                document.title = 'Editor en vivo - ' + generalData.name + ' - Admin';

                const browserUrlEl = document.getElementById('leBrowserUrl');
                if (browserUrlEl) browserUrlEl.textContent = 'equitermindustries.com.mx' + generalData.public_path;
                const viewLiveLink = document.getElementById('leViewLiveLink');
                if (viewLiveLink) {
                    const origin = window.location.origin;
                    viewLiveLink.href = origin + generalData.public_path;
                }

                if (window.showCenterToast) showCenterToast('Información general guardada.');
                schedulePreview();
            } else if (res.status === 422) {
                const errors = data.errors || {};
                errorsBox.innerHTML = Object.values(errors).flat().map((m) => `<p>${m}</p>`).join('');
                errorsBox.style.display = 'block';
                const fieldMap = { name: 'leGenName', slug: 'leGenSlug', page_type: 'leGenPageType', parent_id: 'leGenParentId', short_description: 'leGenShortDesc', price: 'leGenPrice', currency: 'leGenCurrency', seo_title: 'leGenSeoTitle', seo_description: 'leGenSeoDesc' };
                Object.keys(errors).forEach((f) => {
                    const el = editPanel.querySelector('#' + (fieldMap[f] || ''));
                    if (el) el.classList.add('is-invalid');
                });
            } else {
                throw new Error('save-general-failed');
            }
        } catch (err) {
            errorsBox.innerHTML = '<p>No se pudo guardar. Intenta de nuevo.</p>';
            errorsBox.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    function renderPanel() {
        if (panelMode === 'general') {
            renderGeneralPanel();
            return;
        }

        const section = findSection(selectedUid);

        if (!section) {
            editPanel.innerHTML = '<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';
            return;
        }

        const iconPath = ICONS[section.type] || ICONS.default;

        editPanel.innerHTML = `
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPath}</svg>
                    </span>
                    <span>
                        <strong>${escHtml(section.title || TYPE_LABELS[section.type] || section.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${field('Título (opcional, puedes usar {servicio})', `<input type="text" class="users-manager-input" id="leTitle" value="${escHtml(section.title)}" placeholder="Ej: Beneficios del servicio">`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `;

        editPanel.querySelector('#lePanelClose').addEventListener('click', () => {
            selectedUid = null;
            renderBlocksList();
            renderPanel();
        });

        editPanel.querySelector('#leTitle').addEventListener('input', (e) => {
            section.title = e.target.value;
            markDirty();
            renderBlocksList();
            schedulePreview();
        });

        renderTypeFields(editPanel.querySelector('#leTypeFields'), section);
    }

    function renderTypeFields(container, section) {
        const cfg = section.config = section.config || {};

        switch (section.type) {
            case 'banner':
                renderBannerFields(container, cfg, '');
                break;
            case 'dual_banner':
                container.innerHTML = '<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>';
                cfg.left = cfg.left || { image_url: '', link_url: '', alt: '' };
                cfg.right = cfg.right || { image_url: '', link_url: '', alt: '' };
                renderBannerFields(container.querySelector('#leDbLeft'), cfg.left, 'Left');
                renderBannerFields(container.querySelector('#leDbRight'), cfg.right, 'Right');
                break;
            case 'product_carousel':
                renderProductCarouselFields(container, cfg, '');
                break;
            case 'product_carousel_banner':
                container.innerHTML = '<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>';
                renderBannerFields(container.querySelector('#lePcbBanner'), {
                    image_url: cfg.banner_image_url, link_url: cfg.banner_link_url, alt: cfg.banner_alt,
                }, 'PcbBanner', (key, value) => {
                    if (key === 'image_url') cfg.banner_image_url = value;
                    if (key === 'link_url') cfg.banner_link_url = value;
                    if (key === 'alt') cfg.banner_alt = value;
                });
                renderProductCarouselFields(container.querySelector('#lePcbCarousel'), cfg, 'Pcb');
                break;
            case 'category_grid':
                renderCategoryGridFields(container, cfg);
                break;
            case 'brand_carousel':
                container.innerHTML = '<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';
                break;
            case 'html_block':
                renderHtmlBlockFields(container, cfg);
                break;
            case 'faq':
                renderFaqFields(container, cfg);
                break;
            case 'rich_header':
                renderRichHeaderFields(container, cfg);
                break;
            case 'content_tabs':
                renderContentTabsFields(container, cfg);
                break;
            case 'benefits_grid':
                renderBenefitsGridFields(container, cfg);
                break;
            case 'process_steps':
                renderProcessStepsFields(container, cfg);
                break;
            case 'gallery_carousel':
                renderGalleryCarouselFields(container, cfg);
                break;
            case 'rating_reviews':
                renderRatingReviewsFields(container, cfg);
                break;
            case 'cta_final':
                renderCtaFinalFields(container, cfg);
                break;
            default:
                container.innerHTML = '<p class="hs-config-note">Tipo de bloque desconocido.</p>';
        }
    }

    // ── banner / dual_banner (izq/der) / product_carousel_banner.banner ──
    function renderBannerFields(container, cfg, suffix, customSetter) {
        const idImg = 'leBanner' + suffix + 'Image';
        const idLink = 'leBanner' + suffix + 'Link';
        const idAlt = 'leBanner' + suffix + 'Alt';

        container.innerHTML = `
            ${field('URL de Imagen', `
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${idImg}" value="${escHtml(cfg.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${idImg}">Seleccionar</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${field('URL de Enlace', `<input type="text" class="users-manager-input" id="${idLink}" value="${escHtml(cfg.link_url)}" placeholder="/servicio/otro-servicio">`)}
                ${field('Texto Alternativo', `<input type="text" class="users-manager-input" id="${idAlt}" value="${escHtml(cfg.alt)}">`)}
            </div>
        `;

        const setImg = (v) => customSetter ? customSetter('image_url', v) : (cfg.image_url = v);
        const setLink = (v) => customSetter ? customSetter('link_url', v) : (cfg.link_url = v);
        const setAlt = (v) => customSetter ? customSetter('alt', v) : (cfg.alt = v);

        const imgInput = container.querySelector('#' + idImg);
        imgInput.addEventListener('input', () => { setImg(imgInput.value); markDirty(); schedulePreview(); });

        container.querySelector('#' + idLink).addEventListener('input', (e) => { setLink(e.target.value); markDirty(); schedulePreview(); });
        container.querySelector('#' + idAlt).addEventListener('input', (e) => { setAlt(e.target.value); markDirty(); schedulePreview(); });

        const trigger = container.querySelector('.img-picker-trigger-btn');
        trigger.addEventListener('click', () => {
            if (typeof window.openImagePicker === 'function') {
                window.openImagePicker(idImg);
            }
        });
        // El picker global escribe el valor en el <input> y dispara 'input',
        // el listener de arriba ya sincroniza el objeto de config.
    }

    // ── product_carousel (y la parte "carrusel" de product_carousel_banner) ──
    function renderProductCarouselFields(container, cfg, suffix) {
        const idSource = 'leSource' + suffix;
        const idLimit = 'leLimit' + suffix;
        const idCategory = 'leCategory' + suffix;
        const idBrand = 'leBrand' + suffix;
        const idCollection = 'leCollection' + suffix;
        const idManualWrap = 'leManualWrap' + suffix;

        const sourceOptions = Object.keys(SOURCE_LABELS).map((s) => (
            `<option value="${s}" ${cfg.source === s ? 'selected' : ''}>${SOURCE_LABELS[s]}</option>`
        )).join('');

        container.innerHTML = `
            <div class="live-editor-field-row">
                ${field('Origen de Productos', `<select class="users-manager-select" id="${idSource}">${sourceOptions}</select>`)}
                ${field('Límite de Productos', `<input type="number" class="users-manager-input" id="${idLimit}" min="1" max="50" value="${cfg.limit ?? 10}">`)}
            </div>
            <div id="leSourceFields${suffix}"></div>
        `;

        container.querySelector('#' + idSource).addEventListener('change', (e) => {
            cfg.source = e.target.value;
            markDirty();
            renderSourceFields();
            schedulePreview();
        });
        container.querySelector('#' + idLimit).addEventListener('input', (e) => {
            cfg.limit = parseInt(e.target.value, 10) || 10;
            markDirty();
            schedulePreview();
        });

        function renderSourceFields() {
            const wrap = container.querySelector('#leSourceFields' + suffix);
            if (cfg.source === 'category') {
                wrap.innerHTML = field('Categoría', `<select class="users-manager-select" id="${idCategory}">
                    <option value="">Selecciona una categoría</option>
                    ${(DATA.categories || []).map((c) => `<option value="${c.id}" ${String(cfg.category_id) === String(c.id) ? 'selected' : ''}>${escHtml(c.name)}</option>`).join('')}
                </select>`);
                wrap.querySelector('#' + idCategory).addEventListener('change', (e) => {
                    cfg.category_id = e.target.value || null;
                    markDirty();
                    schedulePreview();
                });
            } else if (cfg.source === 'brand') {
                wrap.innerHTML = field('Marca', `<select class="users-manager-select" id="${idBrand}">
                    <option value="">Selecciona una marca</option>
                    ${(DATA.brands || []).map((b) => `<option value="${b.id}" ${String(cfg.brand_id) === String(b.id) ? 'selected' : ''}>${escHtml(b.name)}</option>`).join('')}
                </select>`);
                wrap.querySelector('#' + idBrand).addEventListener('change', (e) => {
                    cfg.brand_id = e.target.value || null;
                    markDirty();
                    schedulePreview();
                });
            } else if (cfg.source === 'collection') {
                wrap.innerHTML = field('Colección', `<select class="users-manager-select" id="${idCollection}">
                    <option value="">Selecciona una colección</option>
                    ${(DATA.collections || []).map((c) => `<option value="${c.id}" ${String(cfg.collection_id) === String(c.id) ? 'selected' : ''}>${escHtml(c.name)}</option>`).join('')}
                </select>`);
                wrap.querySelector('#' + idCollection).addEventListener('change', (e) => {
                    cfg.collection_id = e.target.value || null;
                    markDirty();
                    schedulePreview();
                });
            } else if (cfg.source === 'manual') {
                wrap.innerHTML = `<div class="live-editor-field"><label>Productos</label><div id="${idManualWrap}"></div></div>`;
                mountProductPicker(wrap.querySelector('#' + idManualWrap), cfg.product_ids || [], (ids) => {
                    cfg.product_ids = ids;
                    markDirty();
                    schedulePreview();
                });
            } else {
                wrap.innerHTML = '';
            }
        }

        renderSourceFields();
    }

    // ── Buscador de productos con chips — mismo patrón que
    //    initServiceSectionProductPicker en _section_scripts.blade.php,
    //    reescrito aquí para montarse dentro de un contenedor dinámico en
    //    vez de IDs fijos del modal clásico. ──
    function mountProductPicker(container, initialIds, onChangeIds) {
        container.innerHTML = `
            <div class="hs-product-search">
                <div class="hs-product-search__input-wrap">
                    <input type="text" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                </div>
                <div class="hs-product-search__dropdown" style="display:none;">
                    <div class="hs-product-search__empty" style="display:none;">Sin resultados</div>
                    <ul class="hs-product-search__list"></ul>
                </div>
            </div>
            <div class="hs-product-chips"></div>
        `;

        const searchInput = container.querySelector('.hs-product-search__input');
        const dropdown = container.querySelector('.hs-product-search__dropdown');
        const list = container.querySelector('.hs-product-search__list');
        const emptyMsg = container.querySelector('.hs-product-search__empty');
        const chipsContainer = container.querySelector('.hs-product-chips');

        let selected = [];
        let debounceTimer = null;

        function renderChips() {
            chipsContainer.innerHTML = '';
            selected.forEach((p) => {
                const chip = document.createElement('span');
                chip.className = 'hs-product-chip';
                chip.innerHTML = `<span>${escHtml(p.name)}</span><small>${escHtml(p.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`;
                chip.querySelector('button').addEventListener('click', () => {
                    selected = selected.filter((sp) => sp.id !== p.id);
                    renderChips();
                    onChangeIds(selected.map((p) => p.id));
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
            const filtered = products.filter((p) => !selected.some((sp) => sp.id === p.id));
            if (!filtered.length) {
                emptyMsg.style.display = 'block';
                list.style.display = 'none';
                return;
            }
            emptyMsg.style.display = 'none';
            list.style.display = 'block';
            filtered.forEach((p) => {
                const li = document.createElement('li');
                li.className = 'hs-product-search__item';
                li.innerHTML = `<span>${escHtml(p.name)}</span><small>SKU: ${escHtml(p.sku)}</small>`;
                li.addEventListener('click', () => {
                    selected.push({ id: p.id, name: p.name, sku: p.sku });
                    renderChips();
                    onChangeIds(selected.map((p) => p.id));
                    searchInput.value = '';
                    hideDropdown();
                });
                list.appendChild(li);
            });
        }

        async function fetchProducts(params) {
            try {
                const url = new URL(DATA.productsSearchUrl, window.location.origin);
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
            if (!container.contains(e.target)) hideDropdown();
        });

        if (initialIds && initialIds.length) {
            fetchProducts({ ids: initialIds.join(',') }).then((products) => {
                selected = products.map((p) => ({ id: p.id, name: p.name, sku: p.sku }));
                renderChips();
            });
        }
    }

    function renderCategoryGridFields(container, cfg) {
        container.innerHTML = field('Categorías a mostrar (vacío = todas las principales activas)', `
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(DATA.categories || []).map((c) => `<option value="${c.id}" ${(cfg.category_ids || []).map(String).includes(String(c.id)) ? 'selected' : ''}>${escHtml(c.name)}</option>`).join('')}
            </select>
        `);
        container.querySelector('#leCategoryIds').addEventListener('change', (e) => {
            cfg.category_ids = Array.from(e.target.selectedOptions).map((o) => parseInt(o.value, 10));
            markDirty();
            schedulePreview();
        });
    }

    function renderHtmlBlockFields(container, cfg) {
        container.innerHTML = field('Contenido HTML', `<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${escHtml(cfg.html)}</textarea>`);
        container.querySelector('#leHtml').addEventListener('input', (e) => {
            cfg.html = e.target.value;
            markDirty();
            schedulePreview();
        });
    }

    function renderFaqFields(container, cfg) {
        container.innerHTML = `
            ${field('Texto descriptivo (opcional)', `<textarea class="users-manager-input client-modal-textarea" id="leFaqDescription" rows="2">${escHtml(cfg.description)}</textarea>`)}
            <p class="hs-config-note">Las preguntas y respuestas se capturan en este Servicio (pestaña SEO y Preguntas Frecuentes del formulario clásico). Esta sección solo define el título y el texto descriptivo.</p>
        `;
        container.querySelector('#leFaqDescription').addEventListener('input', (e) => {
            cfg.description = e.target.value;
            markDirty();
            schedulePreview();
        });
    }

    function renderRichHeaderFields(container, cfg) {
        container.innerHTML = `
            ${field('Badges cortos (separados por ·, máx. 3)', `<input type="text" class="users-manager-input" id="leRhBadges" value="${escHtml((cfg.badges || []).join(' · '))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${field('Líneas de meta (una por línea)', `<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${escHtml((cfg.meta_lines || []).join('\n'))}</textarea>`)}
            ${field('Texto del botón', `<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${escHtml(cfg.whatsapp_text || 'Cotizar por WhatsApp')}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${field('Precio mostrado (opcional)', `<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${escHtml(cfg.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${field('Imágenes de fondo (galería del servicio)', `<select class="users-manager-select" id="leRhBgImages" multiple size="4">${imagesOptionsHtml(cfg.background_image_ids, DATA.images)}</select>`)}
        `;

        container.querySelector('#leRhBadges').addEventListener('input', (e) => {
            cfg.badges = e.target.value.split('·').map((v) => v.trim()).filter(Boolean).slice(0, 3);
            markDirty();
            schedulePreview();
        });
        container.querySelector('#leRhMetaLines').addEventListener('input', (e) => {
            cfg.meta_lines = e.target.value.split('\n').map((v) => v.trim()).filter(Boolean);
            markDirty();
            schedulePreview();
        });
        container.querySelector('#leRhWhatsapp').addEventListener('input', (e) => {
            cfg.whatsapp_text = e.target.value;
            markDirty();
            schedulePreview();
        });
        container.querySelector('#leRhPriceLabel').addEventListener('input', (e) => {
            cfg.price_label = e.target.value;
            markDirty();
            schedulePreview();
        });
        container.querySelector('#leRhBgImages').addEventListener('change', (e) => {
            cfg.background_image_ids = Array.from(e.target.selectedOptions).map((o) => parseInt(o.value, 10));
            markDirty();
            schedulePreview();
        });
    }

    // ── Repetibles simples (content_tabs / benefits_grid / process_steps):
    //    fieldsets apilados con botón "+", re-renderizando el bloque completo
    //    al agregar/quitar filas (BETA — se prioriza que funcione sobre un
    //    repeater visual elaborado, mismo criterio que la tarea pide para
    //    rich_header). ──
    function renderContentTabsFields(container, cfg) {
        cfg.tabs = cfg.tabs || [];
        let html = '<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';
        container.innerHTML = html;
        const rows = container.querySelector('#leCtRows');

        cfg.tabs.forEach((tab, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${escHtml(tab.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${escHtml(tab.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${escHtml(tab.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${escHtml((tab.bullets || []).join('\n'))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${imagesOptionsHtml(tab.image_id ? [tab.image_id] : [], DATA.images)}
                </select>
            `;
            row.querySelector('.le-label').addEventListener('input', (e) => { tab.label = e.target.value; markDirty(); renderBlocksList(); schedulePreview(); });
            row.querySelector('.le-subtitle').addEventListener('input', (e) => { tab.subtitle = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-body').addEventListener('input', (e) => { tab.body = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-bullets').addEventListener('input', (e) => { tab.bullets = e.target.value.split('\n').map((v) => v.trim()).filter(Boolean); markDirty(); schedulePreview(); });
            row.querySelector('.le-image').addEventListener('change', (e) => { tab.image_id = e.target.value || null; markDirty(); schedulePreview(); });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.tabs.splice(i, 1);
                markDirty();
                renderContentTabsFields(container, cfg);
                schedulePreview();
            });
            rows.appendChild(row);
        });

        container.querySelector('#leCtAdd').addEventListener('click', () => {
            cfg.tabs.push({ label: '', subtitle: '', body: '', bullets: [], image_id: null });
            markDirty();
            renderContentTabsFields(container, cfg);
            schedulePreview();
        });
    }

    function renderBenefitsGridFields(container, cfg) {
        cfg.items = cfg.items || [];
        container.innerHTML = '<p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>';
        const rows = container.querySelector('#leBgRows');

        cfg.items.forEach((item, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${escHtml(item.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${escHtml(item.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${escHtml(item.description)}</textarea>
            `;
            row.querySelector('.le-figure').addEventListener('input', (e) => { item.figure = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-title').addEventListener('input', (e) => { item.title = e.target.value; markDirty(); renderBlocksList(); schedulePreview(); });
            row.querySelector('.le-description').addEventListener('input', (e) => { item.description = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.items.splice(i, 1);
                markDirty();
                renderBenefitsGridFields(container, cfg);
                schedulePreview();
            });
            rows.appendChild(row);
        });

        container.querySelector('#leBgAdd').addEventListener('click', () => {
            cfg.items.push({ figure: '', title: '', description: '' });
            markDirty();
            renderBenefitsGridFields(container, cfg);
            schedulePreview();
        });
    }

    function renderProcessStepsFields(container, cfg) {
        cfg.steps = cfg.steps || [];
        container.innerHTML = '<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';
        const rows = container.querySelector('#lePsRows');

        cfg.steps.forEach((step, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${escHtml(step.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${escHtml(step.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${escHtml(step.duration)}">
            `;
            row.querySelector('.le-title').addEventListener('input', (e) => { step.title = e.target.value; markDirty(); renderBlocksList(); schedulePreview(); });
            row.querySelector('.le-description').addEventListener('input', (e) => { step.description = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-duration').addEventListener('input', (e) => { step.duration = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.steps.splice(i, 1);
                markDirty();
                renderProcessStepsFields(container, cfg);
                schedulePreview();
            });
            rows.appendChild(row);
        });

        container.querySelector('#lePsAdd').addEventListener('click', () => {
            cfg.steps.push({ title: '', description: '', duration: '' });
            markDirty();
            renderProcessStepsFields(container, cfg);
            schedulePreview();
        });
    }

    function renderGalleryCarouselFields(container, cfg) {
        container.innerHTML = field('Imágenes a mostrar (vacío = toda la galería)', `
            <select class="users-manager-select" id="leGcImages" multiple size="6">${imagesOptionsHtml(cfg.image_ids, DATA.images)}</select>
            ${(!DATA.images || !DATA.images.length) ? '<p class="hs-config-note" style="margin-top:6px;">Este servicio todavía no tiene imágenes en su galería.</p>' : ''}
        `);
        container.querySelector('#leGcImages').addEventListener('change', (e) => {
            cfg.image_ids = Array.from(e.target.selectedOptions).map((o) => parseInt(o.value, 10));
            markDirty();
            schedulePreview();
        });
    }

    function renderRatingReviewsFields(container, cfg) {
        container.innerHTML = `
            ${field('Texto descriptivo (opcional)', `<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${escHtml(cfg.description)}</textarea>`)}
            ${field('Reseñas visibles antes de "Ver más"', `<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${cfg.reviews_per_page ?? 3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en la pestaña Rating y reseñas del formulario clásico.</p>
        `;
        container.querySelector('#leRrDescription').addEventListener('input', (e) => { cfg.description = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leRrPerPage').addEventListener('input', (e) => { cfg.reviews_per_page = parseInt(e.target.value, 10) || 3; markDirty(); schedulePreview(); });
    }

    function renderCtaFinalFields(container, cfg) {
        container.innerHTML = `
            ${field('Título', `<input type="text" class="users-manager-input" id="leCtaHeadline" value="${escHtml(cfg.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            ${field('Texto de apoyo', `<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${escHtml(cfg.subtext)}</textarea>`)}
            ${field('Texto del botón', `<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${escHtml(cfg.whatsapp_text || 'Cotizar por WhatsApp')}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${field('Imagen de fondo (opcional)', `<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${imagesOptionsHtml(cfg.background_image_id ? [cfg.background_image_id] : [], DATA.images)}</select>`)}
        `;
        container.querySelector('#leCtaHeadline').addEventListener('input', (e) => { cfg.headline = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leCtaSubtext').addEventListener('input', (e) => { cfg.subtext = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leCtaWhatsapp').addEventListener('input', (e) => { cfg.whatsapp_text = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leCtaBg').addEventListener('change', (e) => { cfg.background_image_id = e.target.value || null; markDirty(); schedulePreview(); });
    }

    // ── Columna derecha: preview vía POST al servidor ───────────────
    let previewDebounce = null;

    function schedulePreview() {
        clearTimeout(previewDebounce);
        previewDebounce = setTimeout(renderPreview, 400);
    }

    async function renderPreview() {
        try {
            const res = await fetch(DATA.previewUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    sections: draftSections.filter((s) => s.is_active).map((s, i) => ({ ...s, sort_order: i })),
                }),
            });
            const data = await res.json();
            iframe.srcdoc = data.html ?? '';
        } catch (err) {
            console.error('Error generando el preview:', err);
        }
    }

    iframe.addEventListener('load', () => {
        try {
            const doc = iframe.contentDocument;
            if (!doc) return;

            const section = findSection(selectedUid);
            if (section && section.id) {
                const style = doc.createElement('style');
                style.textContent = `[data-section-id="${section.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`;
                doc.head.appendChild(style);
            }

            // Click sobre cualquier bloque de la vista previa selecciona ese
            // mismo bloque en el panel izquierdo/central — solo funciona para
            // bloques ya guardados (con id real), ver nota en data-section-id
            // de los partials públicos.
            doc.body.addEventListener('click', (e) => {
                const el = e.target.closest('[data-section-id]');
                if (!el) return;
                const sectionId = el.getAttribute('data-section-id');
                const match = draftSections.find((s) => String(s.id) === String(sectionId));
                if (match) {
                    e.preventDefault();
                    selectSection(match._uid);
                }
            }, true);
        } catch (err) {
            // Cross-origin u otro problema de acceso al iframe — no rompe el editor.
        }
    });

    // ── Toggle Escritorio / Móvil ────────────────────────────────────
    const viewportCaption = document.getElementById('leViewportCaption');

    function updateViewportCaption() {
        if (!viewportCaption) return;
        viewportCaption.textContent = viewport === 'mobile' ? 'Móvil · 375px' : 'Escritorio · 1440px';
    }

    viewportToggle.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-viewport]');
        if (!btn) return;
        viewport = btn.dataset.viewport;
        viewportToggle.querySelectorAll('button').forEach((b) => b.classList.toggle('is-active', b === btn));
        iframe.classList.toggle('is-mobile', viewport === 'mobile');
        updateViewportCaption();
    });

    // ── Guardar cambios ──────────────────────────────────────────────
    saveBtn.addEventListener('click', async () => {
        saveBtn.disabled = true;
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Guardando...';
        dirtyIndicator.textContent = 'Guardando…';
        dirtyIndicator.className = 'live-editor-status is-saving';

        try {
            const res = await fetch(DATA.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    sections: draftSections.map((s, i) => ({ ...s, sort_order: i })),
                }),
            });

            if (!res.ok) throw new Error('save request failed');

            // Recarga para traer los id reales de secciones nuevas y dejar el
            // borrador en memoria sincronizado 1:1 con lo persistido — opción
            // simple explícitamente permitida para esta pantalla BETA.
            window.location.reload();
        } catch (err) {
            console.error('Error guardando la página:', err);
            alert('No se pudieron guardar los cambios. Intenta de nuevo.');
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
            markDirty();
        }
    });

    // ── Arranque ───────────────────────────────────────────────────
    // Si el servicio no tiene bloques todavía (recién creado desde "+ Nuevo
    // Servicio"), abre directo el panel de Información general — es lo
    // primero que hace falta llenar antes de agregar bloques.
    if (!draftSections.length) {
        selectGeneral();
    } else {
        renderBlocksList();
        renderPanel();
    }
    renderPreview();
})();
