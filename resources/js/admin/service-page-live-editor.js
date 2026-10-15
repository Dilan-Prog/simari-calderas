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
 *   { previewUrl, saveUrl, generalUrl, productsSearchUrl,
 *     sections, categories, brands, collections, images }
 *
 * Patrones reusados de otros archivos del admin (ver comentarios inline):
 *  - Debounce + iframe.srcdoc: resources/js/admin/email-template-editor.js
 *  - Reordenar con SortableJS (mismo paquete ya usado en dashboard-editor.js/
 *    pipeline-board.js/deals-board.js) para las 3 listas arrastrables
 *    (bloques, galería, reseñas) — se migró desde HTML5 drag-and-drop nativo
 *    (dragstart/dragover/drop) porque, en uso real, el navegador podía dejar
 *    una sesión de arrastre nativa "colgada" (el drop no siempre concluye de
 *    forma confiable, sobre todo cerca del <iframe> de preview, que es un
 *    documento aparte) — eso bloqueaba TODO clic en la página hasta recargar,
 *    no solo un problema visual. SortableJS no depende de esa API nativa.
 *  - Buscador de productos con chips: mismo archivo (initServiceSectionProductPicker)
 *  - Config exacta por tipo de sección: app/Http/Controllers/Backend/ServicePageController.php
 *    (método que arma el config desde el modal clásico) — se siguió ESE shape
 *    real, no el de la descripción de la tarea, cuando divergían (ver nota en
 *    product_carousel_banner más abajo).
 */
import Sortable from 'sortablejs';
import { mountLinkTools } from './live-editor-links.js';
import { renderCollectionGeneralPanel, renderCollectionProductsPanel } from './live-editor-collection-panels.js';

(function () {
    const DATA = window.__LIVE_EDITOR__;
    if (!DATA) return;

    // 'service' (editor original) | 'collection' (mismo editor para Colecciones).
    const KIND = DATA.kind || 'service';
    const IS_COLLECTION = KIND === 'collection';

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    const TYPE_LABELS = {
        banner: 'Banner',
        dual_banner: 'Banner Doble',
        product_carousel: 'Carrusel de Productos',
        product_carousel_banner: 'Carrusel con Banner',
        category_grid: 'Grid de Categorías',
        brand_carousel: 'Carrusel de Marcas',
        brand_logos: 'Bloque de Marcas',
        html_block: 'Bloque HTML',
        faq: 'Preguntas Frecuentes',
        rich_header: 'Encabezado enriquecido',
        content_tabs: 'Descripción por secciones',
        benefits_grid: 'Beneficios / características',
        process_steps: 'Proceso / cómo funciona',
        gallery_carousel: 'Galería / carrusel',
        rating_reviews: 'Rating y reseñas',
        cta_final: 'CTA final',
        button: 'Botón',
        table_block: 'Tabla',
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
        brand_logos: '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><path d="M14 17.5h7"/><path d="M17.5 14v7"/>',
        html_block: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        faq: '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
        rich_header: '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',
        content_tabs: '<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',
        benefits_grid: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        process_steps: '<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',
        gallery_carousel: '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        rating_reviews: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        cta_final: '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        button: '<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',
        table_block: '<path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/>',
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
                return { image_url: '', link_url: '', alt: '', no_link: false };
            case 'dual_banner':
                return {
                    left: { image_url: '', link_url: '', alt: '', no_link: false },
                    right: { image_url: '', link_url: '', alt: '', no_link: false },
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
                    banner_image_url: '', banner_link_url: '', banner_alt: '', banner_no_link: false,
                    source: 'featured', category_id: null, brand_id: null, collection_id: null,
                    product_ids: [], limit: 10,
                };
            case 'category_grid':
                return { category_ids: [] };
            case 'brand_carousel':
                return {};
            case 'brand_logos':
                return { logos: [], grayscale: false };
            case 'html_block':
                return { html: '' };
            case 'faq':
                return { description: '' };
            case 'rich_header':
                return { badges: [], whatsapp_text: 'Cotizar por WhatsApp', meta_lines: [], background_image_ids: [], price_label: '' };
            case 'content_tabs':
                return { tabs: [] };
            case 'benefits_grid':
                return { items: [], layout: 'horizontal' };
            case 'process_steps':
                return { steps: [] };
            case 'gallery_carousel':
                return { image_ids: [] };
            case 'rating_reviews':
                return { description: '', reviews_per_page: 3, mode: 'auto', review_ids: [], show_breakdown: false };
            case 'cta_final':
                return { headline: '', subtext: '', whatsapp_text: 'Cotizar por WhatsApp', background_image_id: null, secondary_button: { text: '', url: '', style: 'outline', color: '#ff6213' } };
            case 'button':
                return { buttons: [{ text: 'Cotizar ahora', url: '', style: 'solid', color: '#ff6213' }], align: 'center' };
            case 'table_block':
                return { title: '', description: '', headers: [], rows: [], buttons: [], align: 'left' };
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
    let panelMode = 'empty'; // 'empty' | 'block' | 'general' | 'gallery' | 'reviews'
    let isDirty = false;
    let viewport = 'desktop';

    const generalData = Object.assign({
        name: '', slug: '', sort_order: 0, short_description: '', price: '', currency: 'MXN', show_price: true,
        background_color: null,
        seo_title: '', seo_description: '', canonical_url: '', is_active: false, faqs: [],
        cover_image_alt: '', og_image_url: '', og_image_alt: '',
        // Estadísticas de marketing (ver ServicePageController::fillRatingStats).
        rating_average_displayed: '', rating_total_rated: '', rating_recommend_percent: '',
        rating_punctuality_average: '', rating_recurring_clients: '', rating_since_year: '',
        rating_distribution: {},
    }, DATA.general || {});

    // ── Panel "Reseñas" — lista editable en memoria, sincronizada 1:1 con lo
    //    persistido tras cada alta/edición/borrado/reorden (a diferencia de
    //    draftSections, estos cambios NO esperan al botón "Guardar cambios":
    //    mismo criterio inmediato que la galería). ──
    const reviewsData = (DATA.reviews || []).map((r) => Object.assign({}, r));
    // Opiniones de clientes (ServiceRating enviadas) de esta página; publicar/
    // ocultar/reordenar también es inmediato (no espera "Guardar cambios").
    const clientRatingsData = (DATA.clientRatings || []).map((r) => Object.assign({}, r));

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
    const galleryBtn = document.getElementById('leGalleryBtn');
    const reviewsBtn = document.getElementById('leReviewsBtn');
    const productsBtn = document.getElementById('leProductsBtn');
    const pageHeading = document.querySelector('.live-editor-heading-row__left h1');

    // ── Modal de confirmación al eliminar (reemplaza confirm()) — genérico:
    //    antes solo borraba bloques (draftSections), ahora también lo usan el
    //    panel Galería (borrar imagen) y el panel Reseñas (borrar reseña), así
    //    que en vez de acoplarlo a un _uid de sección guarda un callback a
    //    ejecutar al confirmar. ──
    const deleteModal = document.getElementById('leDeleteModal');
    const deleteModalTitle = document.getElementById('leDeleteModalTitle');
    const deleteModalAvatar = document.getElementById('leDeleteModalAvatar');
    const deleteModalCancel = document.getElementById('leDeleteModalCancel');
    const deleteModalConfirm = document.getElementById('leDeleteModalConfirm');
    let pendingDeleteAction = null;

    // Ids de bloques ya guardados que el admin quitó; se mandan al guardar.
    const deletedIds = [];

    function openDeleteModal(label, onConfirm) {
        pendingDeleteAction = onConfirm;
        deleteModalTitle.textContent = label;
        deleteModalAvatar.textContent = (label || '?').charAt(0).toUpperCase();
        deleteModal.classList.add('active');
    }

    function closeDeleteModal() {
        pendingDeleteAction = null;
        deleteModal.classList.remove('active');
    }

    deleteModalCancel.addEventListener('click', closeDeleteModal);
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) closeDeleteModal();
    });

    deleteModalConfirm.addEventListener('click', () => {
        const action = pendingDeleteAction;
        closeDeleteModal();
        if (typeof action === 'function') action();
    });

    // Quita el marcado de enlaces [texto](destino) para mostrar solo el texto
    // (nombres en la lista de bloques y encabezados del panel).
    function plainLabel(text) {
        return String(text ?? '').replace(/\[([^\]]+)\]\(((?:\/(?!\/)|https?:\/\/)[^\s)]*)\)/g, '$1');
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Mismo criterio de slugify ya usado en Colecciones/Productos/Marcas/
    // Categorías (ver _scripts.blade.php de cada uno) -- aquí como botón
    // "Generar" explícito en vez de auto-sync al teclear el Nombre, porque
    // este panel siempre edita un servicio ya existente y no queremos
    // reescribir su slug (rompiendo la URL pública) sin que el usuario lo
    // pida a propósito.
    function slugify(value) {
        return String(value ?? '').toLowerCase()
            .normalize('NFD').replace(/[^\x00-\x7F]/g, '')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
    }

    // ── Color picker (puerto sin React del ColorField de Email Marketing,
    //    resources/js/admin/email-marketing/builder/PropertiesPanel.jsx:185-299)
    //    -- misma paleta y mismo localStorage key 'emb-custom-colors', para
    //    que "Mis colores" sea una sola lista compartida en todo el admin. ──
    const COLOR_PALETTE = [
        '#000000', '#141516', '#374151', '#4b5563', '#6b7280', '#9ca3af', '#d1d5db', '#f3f4f6', '#ffffff',
        '#ef4444', '#f97316', '#ff6213', '#f59e0b', '#eab308', '#84cc16', '#22c55e', '#10b981',
        '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
        '#ec4899', '#f43f5e', '#7c2d12', '#78350f', '#365314', '#134e4a', '#1e3a8a', '#4c1d95',
    ];
    const CUSTOM_COLORS_KEY = 'emb-custom-colors';

    function loadCustomColors() {
        try {
            const raw = window.localStorage.getItem(CUSTOM_COLORS_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function saveCustomColors(colors) {
        try {
            window.localStorage.setItem(CUSTOM_COLORS_KEY, JSON.stringify(colors));
        } catch (err) {
            // localStorage no disponible (modo privado, cuota llena) -- el
            // color elegido igual se aplica, solo no persiste entre sesiones.
        }
    }

    function isValidHex(hex) {
        return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(hex);
    }

    /**
     * Monta un color picker dentro de `container`. `value` es el hex actual
     * (o vacío/null), `onChange(hex)` se llama al elegir uno.
     */
    function mountColorPicker(container, value, onChange) {
        let customColors = loadCustomColors();

        function swatchHtml(hex, removable) {
            const active = value && value.toLowerCase() === hex.toLowerCase();
            return `<button type="button" class="le-color-swatch ${active ? 'is-active' : ''}" data-hex="${hex}" title="${hex}" style="background:${hex}">
                ${removable ? '<span class="le-color-swatch-remove" data-remove="' + hex + '" title="Quitar de mis colores">&times;</span>' : ''}
            </button>`;
        }

        function render() {
            container.innerHTML = `
                <div class="le-color-swatches">${COLOR_PALETTE.map((hex) => swatchHtml(hex, false)).join('')}</div>
                ${customColors.length ? `
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${customColors.map((hex) => swatchHtml(hex, true)).join('')}</div>
                ` : ''}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${isValidHex(value) ? value : '#ff6213'}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${escHtml(value || '')}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `;

            container.querySelectorAll('.le-color-swatch').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    if (e.target.closest('.le-color-swatch-remove')) return;
                    onChange(btn.dataset.hex);
                    render();
                });
            });
            container.querySelectorAll('.le-color-swatch-remove').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const hex = btn.dataset.remove;
                    customColors = customColors.filter((c) => c !== hex);
                    saveCustomColors(customColors);
                    render();
                });
            });

            const nativeInput = container.querySelector('.le-color-native');
            const hexInput = container.querySelector('.le-color-hex');
            nativeInput.addEventListener('input', () => { hexInput.value = nativeInput.value; });
            container.querySelector('.le-color-save').addEventListener('click', () => {
                let hex = hexInput.value.trim();
                if (!hex) return;
                if (!hex.startsWith('#')) hex = '#' + hex;
                if (!isValidHex(hex)) return;
                if (!customColors.includes(hex)) {
                    customColors = [...customColors, hex];
                    saveCustomColors(customColors);
                }
                onChange(hex);
                render();
            });
        }

        render();
    }

    /**
     * Monta los controles de tipografía (alineación, tamaño, familia,
     * color, y opcionalmente etiqueta de encabezado) sobre `target`
     * (el objeto plano de config al que se le lee/escribe directo, ej.
     * cfg.title_style). `opts.tagChoices` es un array de tags permitidos
     * (ej. ['h2','h3']) o null/omitido si el campo nunca es un encabezado
     * (ej. un párrafo). `opts.onChange` se llama tras cada cambio.
     */
    function mountTypographyFields(container, target, opts) {
        opts = opts || {};
        const tagChoices = opts.tagChoices || null;
        const onChange = opts.onChange || (() => {});
        const idPrefix = 'leStyle' + (++uidCounter);

        function notify() {
            markDirty();
            onChange();
            schedulePreview();
        }

        const tagField = tagChoices ? field('Etiqueta de encabezado', `
            <select class="users-manager-select" id="${idPrefix}Tag">
                ${tagChoices.map((t) => `<option value="${t}" ${(target.heading_tag || tagChoices[0]) === t ? 'selected' : ''}>${t.toUpperCase()}</option>`).join('')}
            </select>
        `) : '';

        container.innerHTML = `
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${tagField}
            <div class="live-editor-field-row">
                ${field('Alineación', `
                    <div class="le-align-toggle" id="${idPrefix}Align">
                        <button type="button" data-align="left" class="${(target.text_align || 'left') === 'left' ? 'is-active' : ''}" title="Izquierda">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="center" class="${target.text_align === 'center' ? 'is-active' : ''}" title="Centro">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="17" x2="7" y1="12" y2="12"/><line x1="19" x2="5" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="right" class="${target.text_align === 'right' ? 'is-active' : ''}" title="Derecha">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                `)}
                ${field('Tamaño (px)', `<input type="number" class="users-manager-input" id="${idPrefix}Size" min="10" max="72" placeholder="Auto" value="${escHtml(target.font_size || '')}">`)}
            </div>
            ${field('Familia tipográfica', `
                <select class="users-manager-select" id="${idPrefix}Family">
                    <option value="" ${!target.font_family ? 'selected' : ''}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${target.font_family === 'Inter Tight' ? 'selected' : ''}>Inter Tight</option>
                </select>
            `)}
            ${field('Color de texto', `<div id="${idPrefix}Color"></div>`)}
        `;

        if (tagChoices) {
            container.querySelector('#' + idPrefix + 'Tag').addEventListener('change', (e) => {
                target.heading_tag = e.target.value;
                notify();
            });
        }

        container.querySelector('#' + idPrefix + 'Align').addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-align]');
            if (!btn) return;
            target.text_align = btn.dataset.align;
            container.querySelectorAll('#' + idPrefix + 'Align button').forEach((b) => b.classList.toggle('is-active', b === btn));
            notify();
        });

        container.querySelector('#' + idPrefix + 'Size').addEventListener('input', (e) => {
            const v = parseInt(e.target.value, 10);
            target.font_size = Number.isFinite(v) ? v : null;
            notify();
        });

        container.querySelector('#' + idPrefix + 'Family').addEventListener('change', (e) => {
            target.font_family = e.target.value || null;
            notify();
        });

        mountColorPicker(container.querySelector('#' + idPrefix + 'Color'), target.text_color, (hex) => {
            target.text_color = hex;
            notify();
        });
    }

    /**
     * Controles compartidos de un botón genérico: texto, enlace (vía el
     * selector estilo Shopify de window.LinkPicker), estilo sólido/outline,
     * color y (opcional) alineación. Usado tanto por el bloque "Botón"
     * standalone como por el botón secundario de cta_final.
     */
    function mountButtonFields(container, target, opts) {
        opts = opts || {};
        const showAlign = opts.alignField !== false;
        const idPrefix = 'leBtn' + (++uidCounter);

        container.innerHTML = `
            ${field('Texto del botón', `<input type="text" class="users-manager-input" id="${idPrefix}Text" value="${escHtml(target.text)}" placeholder="Cotizar ahora">`)}
            ${field('Enlace', `
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${idPrefix}Url" value="${escHtml(target.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${idPrefix}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${field('Estilo', `
                    <select class="users-manager-select" id="${idPrefix}Style">
                        <option value="solid" ${(target.style || 'solid') === 'solid' ? 'selected' : ''}>Sólido</option>
                        <option value="outline" ${target.style === 'outline' ? 'selected' : ''}>Contorno</option>
                    </select>
                `)}
                ${showAlign ? field('Alineación', `
                    <select class="users-manager-select" id="${idPrefix}Align">
                        <option value="left" ${target.align === 'left' ? 'selected' : ''}>Izquierda</option>
                        <option value="center" ${(target.align || 'center') === 'center' ? 'selected' : ''}>Centro</option>
                        <option value="right" ${target.align === 'right' ? 'selected' : ''}>Derecha</option>
                    </select>
                `) : ''}
            </div>
            ${field('Color', `<div id="${idPrefix}Color"></div>`)}
        `;

        container.querySelector('#' + idPrefix + 'Text').addEventListener('input', (e) => {
            target.text = e.target.value;
            markDirty();
            schedulePreview();
        });
        const urlInput = container.querySelector('#' + idPrefix + 'Url');
        urlInput.addEventListener('input', (e) => {
            target.url = e.target.value;
            markDirty();
            schedulePreview();
        });
        container.querySelector('#' + idPrefix + 'LinkPick').addEventListener('click', (e) => {
            if (typeof window.LinkPicker !== 'function' && !(window.LinkPicker && window.LinkPicker.open)) return;
            window.LinkPicker.open({
                anchorEl: e.target,
                onSelect: (url) => {
                    urlInput.value = url;
                    target.url = url;
                    markDirty();
                    schedulePreview();
                },
            });
        });
        container.querySelector('#' + idPrefix + 'Style').addEventListener('change', (e) => {
            target.style = e.target.value;
            markDirty();
            schedulePreview();
        });
        if (showAlign) {
            container.querySelector('#' + idPrefix + 'Align').addEventListener('change', (e) => {
                target.align = e.target.value;
                markDirty();
                schedulePreview();
            });
        }
        mountColorPicker(container.querySelector('#' + idPrefix + 'Color'), target.color || '#ff6213', (hex) => {
            target.color = hex;
            markDirty();
            schedulePreview();
        });
    }

    function renderButtonFields(container, cfg) {
        // Uno o varios botones en la misma fila (ej. CTA sólido + teléfono en
        // contorno) — antes era un solo botón plano (text/url/style/color a
        // nivel raíz de cfg); si un config viejo con ese shape llega aquí, se
        // migra en memoria a la lista para no perder el dato ya guardado.
        if (!Array.isArray(cfg.buttons)) {
            cfg.buttons = cfg.text || cfg.url
                ? [{ text: cfg.text || '', url: cfg.url || '', style: cfg.style || 'solid', color: cfg.color || '#ff6213' }]
                : [{ text: 'Cotizar ahora', url: '', style: 'solid', color: '#ff6213' }];
        }
        cfg.align = cfg.align || 'center';

        container.innerHTML = `
            <p class="hs-config-note">Uno o varios botones en la misma fila (ej. "Agendar revisión" + un teléfono de contacto).</p>
            ${field('Alineación de la fila', `
                <select class="users-manager-select" id="leBtnAlign">
                    <option value="left" ${cfg.align === 'left' ? 'selected' : ''}>Izquierda</option>
                    <option value="center" ${cfg.align === 'center' ? 'selected' : ''}>Centro</option>
                    <option value="right" ${cfg.align === 'right' ? 'selected' : ''}>Derecha</option>
                </select>
            `)}
            <div id="leBtnRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leBtnAdd" style="margin-top:10px;">+ Agregar botón</button>
        `;

        container.querySelector('#leBtnAlign').addEventListener('change', (e) => {
            cfg.align = e.target.value;
            markDirty();
            schedulePreview();
        });

        const rows = container.querySelector('#leBtnRows');

        cfg.buttons.forEach((btn, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            const head = document.createElement('div');
            head.className = 'hs-repeat-row-head';
            head.innerHTML = `<span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`;
            row.appendChild(head);
            const fieldsWrap = document.createElement('div');
            row.appendChild(fieldsWrap);
            mountButtonFields(fieldsWrap, btn, { alignField: false });
            head.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.buttons.splice(i, 1);
                markDirty();
                renderButtonFields(container, cfg);
                schedulePreview();
            });
            rows.appendChild(row);
        });

        container.querySelector('#leBtnAdd').addEventListener('click', () => {
            cfg.buttons.push({ text: '', url: '', style: 'outline', color: '#ff6213' });
            markDirty();
            renderButtonFields(container, cfg);
            schedulePreview();
        });
    }

    function renderTableBlockFields(container, cfg) {
        cfg.headers = Array.isArray(cfg.headers) ? cfg.headers : [];
        cfg.rows = Array.isArray(cfg.rows) ? cfg.rows : [];
        cfg.buttons = Array.isArray(cfg.buttons) ? cfg.buttons : [];
        cfg.align = cfg.align || 'left';

        const colCount = Math.max(cfg.headers.length, 1);

        container.innerHTML = `
            ${field('Descripción (opcional, arriba de la tabla)', `<textarea class="users-manager-input client-modal-textarea" id="leTbDesc" rows="2">${escHtml(cfg.description)}</textarea>`)}
            <p class="hs-config-subtitle">Columnas</p>
            <div id="leTbHeaders" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddCol" style="margin-top:6px;">+ Agregar columna</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Filas</p>
            <div id="leTbRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddRow" style="margin-top:6px;" ${!cfg.headers.length ? 'disabled title="Agrega al menos una columna primero"' : ''}>+ Agregar fila</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Botones debajo de la tabla (opcional)</p>
            <div id="leTbButtons" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddBtn" style="margin-top:6px;">+ Agregar botón</button>
        `;

        container.querySelector('#leTbDesc').addEventListener('input', (e) => {
            cfg.description = e.target.value;
            markDirty();
            schedulePreview();
        });

        // Columnas: cambiar el texto de un encabezado no afecta el número de
        // celdas de las filas ya capturadas (las filas guardan un array de
        // celdas por posición) — agregar/quitar columnas sí ajusta cada fila
        // para mantenerlas alineadas con los encabezados.
        const headersWrap = container.querySelector('#leTbHeaders');
        cfg.headers.forEach((header, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar columna">&times;</button></div>
                <input type="text" class="users-manager-input le-header" placeholder="Nombre de columna" value="${escHtml(header)}">
            `;
            row.querySelector('.le-header').addEventListener('input', (e) => {
                cfg.headers[i] = e.target.value;
                markDirty();
                schedulePreview();
            });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.headers.splice(i, 1);
                cfg.rows.forEach((r) => r.splice(i, 1));
                markDirty();
                renderTableBlockFields(container, cfg);
                schedulePreview();
            });
            headersWrap.appendChild(row);
        });
        container.querySelector('#leTbAddCol').addEventListener('click', () => {
            cfg.headers.push('');
            cfg.rows.forEach((r) => r.push(''));
            markDirty();
            renderTableBlockFields(container, cfg);
            schedulePreview();
        });

        // Filas: cada fila es un array de N celdas (N = num. de columnas).
        const rowsWrap = container.querySelector('#leTbRows');
        cfg.rows.forEach((cells, ri) => {
            while (cells.length < colCount) cells.push('');
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            const cellInputs = cells.map((cell, ci) => `
                <input type="text" class="users-manager-input le-cell" data-ci="${ci}" placeholder="${escHtml(cfg.headers[ci] || 'Celda ' + (ci + 1))}" value="${escHtml(cell)}" style="${ci > 0 ? 'margin-top:6px;' : ''}">
            `).join('');
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${ri + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar fila">&times;</button></div>
                ${cellInputs}
            `;
            row.querySelectorAll('.le-cell').forEach((input) => {
                input.addEventListener('input', (e) => {
                    cells[parseInt(e.target.dataset.ci, 10)] = e.target.value;
                    markDirty();
                    schedulePreview();
                });
            });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.rows.splice(ri, 1);
                markDirty();
                renderTableBlockFields(container, cfg);
                schedulePreview();
            });
            rowsWrap.appendChild(row);
        });
        container.querySelector('#leTbAddRow').addEventListener('click', () => {
            cfg.rows.push(new Array(colCount).fill(''));
            markDirty();
            renderTableBlockFields(container, cfg);
            schedulePreview();
        });

        // Botones (mismo campo compartido que el bloque "Botón" standalone).
        const buttonsWrap = container.querySelector('#leTbButtons');
        cfg.buttons.forEach((btn, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            const head = document.createElement('div');
            head.className = 'hs-repeat-row-head';
            head.innerHTML = `<span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`;
            row.appendChild(head);
            const fieldsWrap = document.createElement('div');
            row.appendChild(fieldsWrap);
            mountButtonFields(fieldsWrap, btn, { alignField: false });
            head.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.buttons.splice(i, 1);
                markDirty();
                renderTableBlockFields(container, cfg);
                schedulePreview();
            });
            buttonsWrap.appendChild(row);
        });
        container.querySelector('#leTbAddBtn').addEventListener('click', () => {
            cfg.buttons.push({ text: '', url: '', style: 'outline', color: '#ff6213' });
            markDirty();
            renderTableBlockFields(container, cfg);
            schedulePreview();
        });
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
    let blocksSortable = null;

    function renderBlocksList() {
        blocksList.innerHTML = '';

        if (!draftSections.length) {
            const empty = document.createElement('div');
            empty.className = 'live-editor-blocks-empty';
            empty.textContent = (IS_COLLECTION ? 'Esta colección' : 'Este servicio') + ' todavía no tiene bloques. Agrega uno abajo.';
            blocksList.appendChild(empty);
            return;
        }

        draftSections.forEach((section) => {
            const row = document.createElement('div');
            row.className = 'live-editor-block-row';
            if (panelMode === 'block' && section._uid === selectedUid) row.classList.add('is-selected');
            if (!section.is_active) row.classList.add('is-inactive');
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
                    <span class="live-editor-block-name">${escHtml(plainLabel(section.title) || TYPE_LABELS[section.type] || section.type)}</span>
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
                const label = plainLabel(section.title) || TYPE_LABELS[section.type] || section.type;
                openDeleteModal(label, () => {
                    const idx = draftSections.findIndex((s) => s._uid === section._uid);
                    if (idx !== -1) draftSections.splice(idx, 1);
                    if (section.id) deletedIds.push(section.id);
                    if (selectedUid === section._uid) {
                        selectedUid = null;
                        renderPanel();
                    }
                    markDirty();
                    renderBlocksList();
                    schedulePreview();
                });
            });

            blocksList.appendChild(row);
        });

        // SortableJS (mismo paquete que dashboard-editor.js/pipeline-board.js)
        // en vez de HTML5 drag-and-drop nativo — ver la nota en el docblock
        // del archivo sobre por qué se migró (una sesión de arrastre nativa
        // podía quedar "colgada" bloqueando toda la página hasta recargar).
        // El contenedor (blocksList) nunca se destruye entre renders, solo
        // sus hijos, así que la instancia se crea una sola vez.
        if (!blocksSortable) {
            blocksSortable = new Sortable(blocksList, {
                animation: 150,
                handle: '.live-editor-block-drag-handle',
                // forceFallback: SortableJS por defecto todavía delega en la
                // API nativa de Drag and Drop del navegador (solo activa
                // draggable=true dinámicamente al hacer mousedown en el
                // handle) — exactamente la API cuya sesión se podía quedar
                // "colgada" cerca del <iframe> de preview. Con esto, usa su
                // propio manejo por eventos de mouse/touch de principio a
                // fin, sin tocar la API nativa en ningún momento.
                forceFallback: true,
                ghostClass: 'live-editor-block-row--ghost',
                dragClass: 'live-editor-block-row--dragging',
                onEnd(evt) {
                    if (evt.oldIndex === evt.newIndex) return;
                    const [moved] = draftSections.splice(evt.oldIndex, 1);
                    draftSections.splice(evt.newIndex, 0, moved);
                    markDirty();
                    renderBlocksList();
                    schedulePreview();
                },
            });
        }
    }

    // Los 3 botones "generales" (Información general / Galería / Reseñas) se
    // resaltan como mutuamente excluyentes entre sí y con la lista de bloques
    // -- de ahí este helper compartido por los 3 select*() de abajo.
    function clearSidebarSelection() {
        generalInfoBtn.classList.remove('is-selected');
        if (galleryBtn) galleryBtn.classList.remove('is-selected');
        if (reviewsBtn) reviewsBtn.classList.remove('is-selected');
        if (productsBtn) productsBtn.classList.remove('is-selected');
    }

    function selectSection(uid) {
        selectedUid = uid;
        panelMode = 'block';
        clearSidebarSelection();
        renderBlocksList();
        renderPanel();
    }

    function selectGeneral() {
        selectedUid = null;
        panelMode = 'general';
        clearSidebarSelection();
        generalInfoBtn.classList.add('is-selected');
        renderBlocksList();
        renderPanel();
    }

    function selectGallery() {
        selectedUid = null;
        panelMode = 'gallery';
        clearSidebarSelection();
        galleryBtn.classList.add('is-selected');
        renderBlocksList();
        renderPanel();
    }

    function selectReviews() {
        selectedUid = null;
        panelMode = 'reviews';
        clearSidebarSelection();
        reviewsBtn.classList.add('is-selected');
        renderBlocksList();
        renderPanel();
    }

    function selectProducts() {
        selectedUid = null;
        panelMode = 'products';
        clearSidebarSelection();
        if (productsBtn) productsBtn.classList.add('is-selected');
        renderBlocksList();
        renderPanel();
    }

    generalInfoBtn.addEventListener('click', selectGeneral);
    if (galleryBtn) galleryBtn.addEventListener('click', selectGallery);
    if (reviewsBtn) reviewsBtn.addEventListener('click', selectReviews);
    if (productsBtn) productsBtn.addEventListener('click', selectProducts);

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

    /**
     * Selector visual de imágenes en miniatura, multi-selección con clic --
     * reemplaza el <select multiple> nativo (feo, y sin forma obvia de
     * vaciar la selección salvo Ctrl/Cmd+clic en cada opción). Opera 100%
     * sobre DATA.images (la galería de ESTE servicio, ya en memoria) -- a
     * diferencia de window.openImagePicker() (picker global del admin, que
     * siempre trae la librería completa del sitio vía AJAX y es de una sola
     * imagen a la vez), por eso no se reutiliza ese aquí.
     *
     * @param {HTMLElement} container   Dónde pintar el grid.
     * @param {number[]} selectedIds    IDs ya seleccionados (cfg.xxx actual).
     * @param {Array}    images         DATA.images.
     * @param {(ids:number[]) => void} onChange  Se llama con la nueva selección completa en cada clic.
     * @param {{clearAllLabel?: string}} [opts]  Si se pasa clearAllLabel, agrega un botón para vaciar la selección entera de un clic (ej. "Mostrar toda la galería").
     */
    function renderImagePickerGrid(container, selectedIds, images, onChange, opts) {
        const selected = new Set((selectedIds || []).map(String));
        const clearAllLabel = opts && opts.clearAllLabel;

        function paint() {
            if (!images || !images.length) {
                container.innerHTML = '<p class="hs-config-note">Esta página todavía no tiene imágenes en su Galería — sube alguna ahí primero.</p>';
                return;
            }

            const tiles = images.map((img) => {
                const isSelected = selected.has(String(img.id));
                return `
                    <button type="button" class="img-library-item le-img-picker-item ${isSelected ? 'is-selected' : ''}" data-id="${img.id}" title="${escHtml(img.alt_text || ('Imagen #' + img.id))}">
                        <img src="${escHtml(img.url)}" alt="${escHtml(img.alt_text || '')}">
                        ${isSelected ? '<span class="le-img-picker-check">&check;</span>' : ''}
                    </button>
                `;
            }).join('');

            container.innerHTML = `
                <div class="img-library-grid">${tiles}</div>
                ${clearAllLabel ? `<button type="button" class="button-secondary size-adjustment le-img-picker-clear" style="margin-top:8px;">${escHtml(clearAllLabel)}</button>` : ''}
            `;

            container.querySelectorAll('.le-img-picker-item').forEach((el) => {
                el.addEventListener('click', () => {
                    const id = el.dataset.id;
                    if (selected.has(id)) selected.delete(id); else selected.add(id);
                    paint();
                    onChange(Array.from(selected).map((v) => parseInt(v, 10)));
                });
            });

            const clearBtn = container.querySelector('.le-img-picker-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    selected.clear();
                    paint();
                    onChange([]);
                });
            }
        }

        paint();
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
            ${field('Slug (URL)', `
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${escHtml(generalData.slug)}" style="flex:1;">
                    <button type="button" id="leGenSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
                </div>
            `, '')}
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
            ${field('Orden', `<input type="number" min="0" step="1" class="users-manager-input" id="leGenSortOrder" value="${escHtml(generalData.sort_order)}">`, '')}
            <p class="hs-config-note" style="margin-top:-8px;">Controla el orden entre páginas con el mismo padre (menor primero) -- se ve reflejado en la lista "Páginas de Servicio" del admin y en el menú/mega-menú del sitio público.</p>
            ${field('Descripción corta', `<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${escHtml(generalData.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${field('Precio (opcional)', `<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${escHtml(generalData.price)}">`)}
                ${field('Moneda', `<input type="text" class="users-manager-input" id="leGenCurrency" value="${escHtml(generalData.currency)}" maxlength="10">`)}
            </div>
            ${field('Visibilidad del precio', `
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenShowPrice" ${generalData.show_price ? 'checked' : ''}> Mostrar el precio en el sitio público
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Si lo desmarcas, el servicio se sigue publicando pero sin precio visible — útil para cotizar en privado.</p>
            `)}
            ${field('Estado', `
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${generalData.is_active ? 'checked' : ''}> Publicado (visible en el sitio público)
                </label>
            `)}
            ${field('Color de fondo de la página (opcional)', `
                <div id="leGenBgColorPicker"></div>
                <button type="button" id="leGenBgColorClear" class="live-editor-btn live-editor-btn--outline" style="margin-top:8px;" ${generalData.background_color ? '' : 'disabled'}>Quitar color (usar blanco)</button>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${field('ALT de la portada', `
                <input type="text" class="users-manager-input" id="leGenCoverAlt" maxlength="255" value="${escHtml(generalData.cover_image_alt)}" placeholder="Describe la portada (vacío = nombre del servicio)">
                <p class="hs-config-note" id="leGenCoverAltCount" style="margin-top:4px;"></p>
            `)}
            ${field('Imagen OG (redes sociales, 1200×630)', `
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="leGenOgImage" value="${escHtml(generalData.og_image_url)}" placeholder="Vacío = se usa la portada">
                    <button type="button" class="img-picker-trigger-btn" data-target="leGenOgImage">Seleccionar</button>
                </div>
                <div id="leGenOgPreview" style="margin-top:8px;width:100%;max-width:300px;aspect-ratio:1200/630;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;display:${generalData.og_image_url ? 'block' : 'none'};">
                    <img id="leGenOgPreviewImg" src="${escHtml(ogPreviewSrc(generalData.og_image_url))}" alt="" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <p class="hs-config-note" style="margin-top:4px;">Medida recomendada: 1200×630 px.</p>
            `)}
            <div id="leGenOgAltWrap" style="${generalData.og_image_url ? '' : 'display:none;'}">
                ${field('ALT de la imagen OG', `
                    <input type="text" class="users-manager-input" id="leGenOgAlt" maxlength="255" value="${escHtml(generalData.og_image_alt)}" placeholder="Describe la imagen OG">
                    <p class="hs-config-note" id="leGenOgAltCount" style="margin-top:4px;"></p>
                `)}
            </div>
            ${field('Título SEO', `<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${escHtml(generalData.seo_title)}" maxlength="160">`)}
            ${field('Descripción SEO', `<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${escHtml(generalData.seo_description)}</textarea>`)}
            ${field('', `
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsCanonical" ${!generalData.canonical_url ? 'checked' : ''}> Es la URL Canónica de este servicio
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Marcado (normal): Google usa la URL de este mismo servicio. Desmárcalo solo si este servicio es muy parecido a otro y quieres que Google indexe ese otro en su lugar.</p>
            `)}
            <div id="leGenCanonicalUrlWrap" style="${!generalData.canonical_url ? 'display:none;' : ''}">
                ${field('URL Canónica', `<input type="url" class="users-manager-input" id="leGenCanonicalUrl" value="${escHtml(generalData.canonical_url)}" maxlength="255" placeholder="https://equitermindustries.com.mx/servicio/otro-servicio-similar">`)}
            </div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Rating y reseñas — Promedio mostrado</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Contenido curado por el equipo, igual que la FAQ. Estas cifras son de marketing (no tienen por qué coincidir con el número de reseñas capturadas en el panel "Reseñas") y nunca alimentan el marcado SEO — el marcado usa siempre el conteo real.</p>
            </div>
            <div class="live-editor-field-row">
                ${field('Promedio mostrado (0–5)', `<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingAvg" value="${escHtml(generalData.rating_average_displayed)}">`)}
                ${field('Total de servicios calificados', `<input type="number" min="0" class="users-manager-input" id="leGenRatingTotal" value="${escHtml(generalData.rating_total_rated)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${field('% que recomendaría el servicio', `<input type="number" step="0.1" min="0" max="100" class="users-manager-input" id="leGenRatingRecommend" value="${escHtml(generalData.rating_recommend_percent)}">`)}
                ${field('Puntualidad de cuadrilla (0–5)', `<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingPunctuality" value="${escHtml(generalData.rating_punctuality_average)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${field('Clientes recurrentes', `<input type="number" min="0" class="users-manager-input" id="leGenRatingRecurring" value="${escHtml(generalData.rating_recurring_clients)}">`)}
                ${field('Calificando desde (año, opcional)', `<input type="number" min="2000" max="2100" class="users-manager-input" id="leGenRatingSince" value="${escHtml(generalData.rating_since_year)}">`)}
            </div>
            ${field('Distribución por estrella', `
                <div class="live-editor-field-row" style="grid-template-columns:repeat(5,1fr);">
                    ${[5, 4, 3, 2, 1].map((star) => `
                        <div>
                            <label style="font-size:11px;color:#6b7280;display:block;margin-bottom:2px;">${star} ★</label>
                            <input type="number" min="0" class="users-manager-input le-gen-rating-dist" data-star="${star}" value="${escHtml((generalData.rating_distribution || {})[star] ?? (generalData.rating_distribution || {})[String(star)] ?? '')}">
                        </div>
                    `).join('')}
                </div>
            `)}
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `;

        editPanel.querySelector('#leGenSaveBtn').addEventListener('click', () => saveGeneralInfo());

        // ALT de portada / imagen OG: contador (recomendado hasta 125) y vista previa.
        const bindAltCount = (inputId, countId) => {
            const input = editPanel.querySelector('#' + inputId);
            const count = editPanel.querySelector('#' + countId);
            if (!input || !count) return;
            const update = () => {
                const n = input.value.length;
                count.textContent = n + ' caracteres (recomendado: hasta 125)';
                count.style.color = n > 125 ? '#b45309' : '';
            };
            input.addEventListener('input', update);
            update();
        };
        bindAltCount('leGenCoverAlt', 'leGenCoverAltCount');
        bindAltCount('leGenOgAlt', 'leGenOgAltCount');

        const ogInput = editPanel.querySelector('#leGenOgImage');
        const refreshOg = () => {
            const has = ogInput.value.trim() !== '';
            editPanel.querySelector('#leGenOgPreview').style.display = has ? 'block' : 'none';
            if (has) editPanel.querySelector('#leGenOgPreviewImg').src = ogPreviewSrc(ogInput.value.trim());
            editPanel.querySelector('#leGenOgAltWrap').style.display = has ? '' : 'none';
        };
        ogInput.addEventListener('input', refreshOg);
        ogInput.addEventListener('change', refreshOg);
        editPanel.querySelector('.img-picker-trigger-btn[data-target="leGenOgImage"]').addEventListener('click', () => {
            if (typeof window.openImagePicker === 'function') window.openImagePicker('leGenOgImage');
        });

        editPanel.querySelector('#leGenSlugGenerate').addEventListener('click', () => {
            const nameInput = editPanel.querySelector('#leGenName');
            editPanel.querySelector('#leGenSlug').value = slugify(nameInput.value);
            markDirty();
        });

        const isCanonicalCheckbox = editPanel.querySelector('#leGenIsCanonical');
        const canonicalUrlWrap = editPanel.querySelector('#leGenCanonicalUrlWrap');
        isCanonicalCheckbox.addEventListener('change', () => {
            canonicalUrlWrap.style.display = isCanonicalCheckbox.checked ? 'none' : '';
        });

        const pageTypeSelect = editPanel.querySelector('#leGenPageType');
        const parentField = editPanel.querySelector('.leGenParentField');
        const toggleParentField = () => {
            const hidesParent = pageTypeSelect.value === 'hub' || pageTypeSelect.value === 'category';
            if (parentField) parentField.style.display = hidesParent ? 'none' : '';
        };
        pageTypeSelect.addEventListener('change', toggleParentField);
        toggleParentField();

        const bgColorClearBtn = editPanel.querySelector('#leGenBgColorClear');
        mountColorPicker(editPanel.querySelector('#leGenBgColorPicker'), generalData.background_color || '', (hex) => {
            generalData.background_color = hex;
            bgColorClearBtn.disabled = false;
            markDirty();
        });
        bgColorClearBtn.addEventListener('click', () => {
            generalData.background_color = null;
            bgColorClearBtn.disabled = true;
            renderGeneralPanel();
            markDirty();
        });
    }

    // Repetidor de FAQ (pregunta/respuesta) -- vive en el panel del bloque
    // "Preguntas Frecuentes" (renderFaqFields), no en "Información general",
    // pero opera sobre generalData.faqs (compartido con toda la página, ver
    // ServicePage::$faqs) y se persiste vía saveGeneralInfo() con el mismo
    // criterio: re-renderizado al agregar/quitar en vez de reindexar name=
    // (aquí no hay <form> real, el payload se arma a mano).
    function renderFaqRepeater(rowsContainer) {
        generalData.faqs = generalData.faqs || [];
        const rows = rowsContainer;
        if (!rows) return;
        rows.innerHTML = '';

        generalData.faqs.forEach((faqItem, i) => {
            const row = document.createElement('div');
            row.className = 'hs-faq-row';
            row.innerHTML = `
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${i + 1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${escHtml(faqItem.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${escHtml(faqItem.answer)}</textarea>
            `;
            row.querySelector('.hs-faq-question').addEventListener('input', (e) => { faqItem.question = e.target.value; markDirty(); });
            row.querySelector('.hs-faq-answer').addEventListener('input', (e) => { faqItem.answer = e.target.value; markDirty(); });
            row.querySelector('.hs-faq-remove').addEventListener('click', () => {
                generalData.faqs.splice(i, 1);
                markDirty();
                renderFaqRepeater(rowsContainer);
            });
            rows.appendChild(row);
        });
    }

    // Se llama tanto desde el botón de "Información general" como desde el
    // botón "Guardar preguntas frecuentes" del panel del bloque FAQ (ver
    // renderFaqFields) -- en ese segundo caso el DOM de "Información general"
    // no existe, así que cada campo cae a su valor ya cargado en generalData
    // si no encuentra el elemento en el panel actualmente renderizado.
    async function saveGeneralInfo(opts) {
        opts = opts || {};
        const btn = editPanel.querySelector('#' + (opts.btnId || 'leGenSaveBtn'));
        const errorsBox = editPanel.querySelector('#' + (opts.errorsBoxId || 'leGenErrors'));
        errorsBox.style.display = 'none';
        errorsBox.innerHTML = '';
        document.querySelectorAll('#leEditPanel .is-invalid').forEach((el) => el.classList.remove('is-invalid'));

        const el = (id) => editPanel.querySelector('#' + id);
        const val = (id, fallback) => { const node = el(id); return node ? node.value : fallback; };
        const checked = (id, fallback) => { const node = el(id); return node ? node.checked : fallback; };

        const pageType = val('leGenPageType', generalData.page_type);
        const distNodes = editPanel.querySelectorAll('.le-gen-rating-dist');
        const payload = {
            name: val('leGenName', generalData.name),
            slug: val('leGenSlug', generalData.slug),
            page_type: pageType,
            parent_id: pageType === 'hub' ? '' : val('leGenParentId', generalData.parent_id || ''),
            sort_order: val('leGenSortOrder', generalData.sort_order),
            short_description: val('leGenShortDesc', generalData.short_description),
            price: val('leGenPrice', generalData.price),
            currency: val('leGenCurrency', generalData.currency),
            show_price: checked('leGenShowPrice', generalData.show_price),
            // No hay un <input> plano detrás de este campo (lo maneja
            // mountColorPicker sobre generalData directamente), a diferencia
            // del resto del payload que sí puede releer el DOM vía val()/checked().
            background_color: generalData.background_color || '',
            is_active: checked('leGenIsActive', generalData.is_active),
            seo_title: val('leGenSeoTitle', generalData.seo_title),
            seo_description: val('leGenSeoDesc', generalData.seo_description),
            is_canonical: checked('leGenIsCanonical', !generalData.canonical_url),
            canonical_url: val('leGenCanonicalUrl', generalData.canonical_url),
            cover_image_alt: val('leGenCoverAlt', generalData.cover_image_alt),
            og_image_url: val('leGenOgImage', generalData.og_image_url),
            og_image_alt: val('leGenOgAlt', generalData.og_image_alt),
            faq_items: generalData.faqs || [],
            // Estadísticas de marketing — ver ServicePageController::fillRatingStats().
            // NO se marca isDirty/markDirty() por estos campos, mismo criterio
            // que el resto de "Información general": este panel tiene su
            // propio botón "Guardar información general", independiente del
            // indicador de cambios sin guardar de los bloques.
            rating_average_displayed: val('leGenRatingAvg', generalData.rating_average_displayed),
            rating_total_rated: val('leGenRatingTotal', generalData.rating_total_rated),
            rating_recommend_percent: val('leGenRatingRecommend', generalData.rating_recommend_percent),
            rating_punctuality_average: val('leGenRatingPunctuality', generalData.rating_punctuality_average),
            rating_recurring_clients: val('leGenRatingRecurring', generalData.rating_recurring_clients),
            rating_since_year: val('leGenRatingSince', generalData.rating_since_year),
            rating_distribution: distNodes.length
                ? Array.from(distNodes).reduce((acc, node) => { acc[node.dataset.star] = node.value; return acc; }, {})
                : (generalData.rating_distribution || {}),
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
                const fieldMap = {
                    name: 'leGenName', slug: 'leGenSlug', page_type: 'leGenPageType', parent_id: 'leGenParentId',
                    short_description: 'leGenShortDesc', price: 'leGenPrice', currency: 'leGenCurrency',
                    seo_title: 'leGenSeoTitle', seo_description: 'leGenSeoDesc', canonical_url: 'leGenCanonicalUrl',
                    cover_image_alt: 'leGenCoverAlt', og_image_url: 'leGenOgImage', og_image_alt: 'leGenOgAlt',
                    rating_average_displayed: 'leGenRatingAvg', rating_total_rated: 'leGenRatingTotal',
                    rating_recommend_percent: 'leGenRatingRecommend', rating_punctuality_average: 'leGenRatingPunctuality',
                    rating_recurring_clients: 'leGenRatingRecurring', rating_since_year: 'leGenRatingSince',
                };
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

    // ── Panel "Galería" — puerto de partials/_gallery.blade.php +
    //    _gallery_scripts.blade.php. A diferencia de la galería clásica (que
    //    hace un <form multipart>), aquí "subir" una imagen reusa el picker
    //    de media compartido (window.openImagePicker, resources/js/admin/
    //    image-picker.js, ya cargado globalmente por admin.layouts.master) —
    //    storeImage() nunca recibió un archivo directo, solo {image_url,
    //    alt_text}, así que ESE es el contrato real a imitar, no un upload
    //    multipart nuevo. Cada alta/borrado/reorden muta DATA.images in-place
    //    (mismo array ya leído por imagesOptionsHtml() en rich_header/
    //    content_tabs/cta_final/gallery_carousel) para que esos otros
    //    pickers vean el cambio la próxima vez que se rendericen, sin reload. ──

    // Vista previa de la imagen OG: las rutas relativas se sirven bajo /media/
    // (mismo criterio que UploadPath::url()).
    function ogPreviewSrc(u) {
        if (!u) return '';
        return /^https?:/i.test(u) || u.startsWith('/') ? u : '/media/' + u.replace(/^\/+/, '');
    }

    function renderGalleryPanel() {
        editPanel.innerHTML = `
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </span>
                    <span>
                        <strong>Galería del servicio</strong>
                        <small>La primera imagen se usa como portada. Arrastra para reordenar.</small>
                    </span>
                </span>
            </div>
            <div id="leGalleryGrid" class="service-gallery-grid"></div>
        `;
        renderGalleryGrid();
    }

    function renderGalleryGrid() {
        const grid = editPanel.querySelector('#leGalleryGrid');
        if (!grid) return;
        grid.innerHTML = '';

        (DATA.images || []).forEach((img, i) => {
            const item = document.createElement('div');
            item.className = 'service-gallery-item';
            item.dataset.id = img.id;
            item.innerHTML = `
                <div class="service-gallery-item__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                ${i === 0 ? '<span class="service-gallery-item__badge">PORTADA</span>' : ''}
                <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
                <img src="${img.url}" alt="${escHtml(img.alt_text || '')}">
                <input type="text" class="users-manager-input service-gallery-item__alt" maxlength="255" placeholder="Texto alternativo" value="${escHtml(img.alt_text || '')}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-top:4px;">
                    <small class="service-gallery-item__count" style="font-size:11px;color:#6b7280;"></small>
                    <button type="button" class="hs-faq-btn service-gallery-item__use-name" style="font-size:11px;width:auto;padding:2px 6px;" title="Rellena el ALT con el nombre del servicio si está vacío">Usar nombre del servicio</button>
                </div>
            `;

            item.querySelector('.service-gallery-item__remove').addEventListener('click', () => {
                openDeleteModal(img.alt_text || ('Imagen #' + img.id), () => deleteGalleryImage(img.id));
            });

            let altDebounce = null;
            const altInput = item.querySelector('.service-gallery-item__alt');
            const altCount = item.querySelector('.service-gallery-item__count');
            const refreshAltCount = () => {
                const n = altInput.value.length;
                altCount.textContent = n + '/125';
                altCount.style.color = n > 125 ? '#b45309' : '#6b7280';
            };
            refreshAltCount();
            altInput.addEventListener('input', (e) => {
                img.alt_text = e.target.value;
                refreshAltCount();
                clearTimeout(altDebounce);
                altDebounce = setTimeout(() => updateGalleryImageAlt(img.id, img.alt_text), 500);
            });
            item.querySelector('.service-gallery-item__use-name').addEventListener('click', () => {
                // Solo rellena si está vacío: nunca pisa un ALT ya escrito.
                if (altInput.value.trim() !== '') return;
                altInput.value = generalData.name || '';
                altInput.dispatchEvent(new Event('input'));
            });

            grid.appendChild(item);
        });

        const addTile = document.createElement('div');
        addTile.className = 'service-gallery-item service-gallery-item--add';
        addTile.id = 'leGalleryAddTile';
        addTile.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Arrastra o selecciona</span>
        `;
        addTile.addEventListener('click', () => {
            if (typeof window.openImagePicker !== 'function') return;
            window.openImagePicker(null, { onSelect: addGalleryImage });
        });
        grid.appendChild(addTile);

        // SortableJS en vez de HTML5 drag-and-drop nativo — ver la nota en
        // el docblock del archivo. `grid` se recrea en cada render (viene de
        // un querySelector sobre el panel, no es un contenedor estable como
        // blocksList), así que la instancia se crea fresca cada vez; la
        // anterior queda huérfana junto con sus hijos ya reemplazados.
        // filter excluye el tile "+" de ser arrastrable/reordenado — siempre
        // se vuelve a agregar al final en el próximo render de cualquier forma.
        new Sortable(grid, {
            animation: 150,
            handle: '.service-gallery-item__drag',
            filter: '.service-gallery-item--add',
            forceFallback: true, // ver la nota en renderBlocksList()
            ghostClass: 'service-gallery-item--ghost',
            dragClass: 'service-gallery-item--dragging',
            onEnd(evt) {
                if (evt.oldIndex === evt.newIndex) return;
                const [moved] = DATA.images.splice(evt.oldIndex, 1);
                DATA.images.splice(evt.newIndex, 0, moved);
                renderGalleryGrid();
                persistGalleryOrder();
            },
        });
    }

    async function addGalleryImage(url) {
        try {
            const res = await fetch(DATA.imagesStoreUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ image_url: url, alt_text: '' }),
            });
            const data = await res.json();
            if (res.ok) {
                DATA.images.push({ id: data.image.id, url: data.image.url, alt_text: data.image.alt_text });
                renderGalleryGrid();
                if (window.showCenterToast) showCenterToast('Imagen agregada.');
                schedulePreview();
            } else if (window.showCenterToast) {
                showCenterToast('No se pudo agregar la imagen.', 'error');
            }
        } catch (err) {
            if (window.showCenterToast) showCenterToast('Error de conexión al agregar la imagen.', 'error');
        }
    }

    async function deleteGalleryImage(id) {
        try {
            const res = await fetch(DATA.imageDestroyUrlTemplate.replace('__IMAGE_ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const idx = DATA.images.findIndex((im) => String(im.id) === String(id));
                if (idx !== -1) DATA.images.splice(idx, 1);
                renderGalleryGrid();
                if (window.showCenterToast) showCenterToast('Imagen eliminada.');
                schedulePreview();
            } else if (window.showCenterToast) {
                showCenterToast('No se pudo eliminar la imagen.', 'error');
            }
        } catch (err) {
            if (window.showCenterToast) showCenterToast('Error de conexión al eliminar la imagen.', 'error');
        }
    }

    async function updateGalleryImageAlt(id, altText) {
        try {
            await fetch(DATA.imageUpdateUrlTemplate.replace('__IMAGE_ID__', id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ alt_text: altText }),
            });
        } catch (err) {
            // Silencioso, igual que la galería clásica -- no bloquea la edición.
        }
    }

    async function persistGalleryOrder() {
        const order = DATA.images.map((img) => img.id);
        try {
            const res = await fetch(DATA.imagesReorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ order }),
            });
            if (res.ok) {
                if (window.showCenterToast) showCenterToast('Orden de galería actualizado.');
                schedulePreview();
            } else if (window.showCenterToast) {
                showCenterToast('No se pudo guardar el nuevo orden de la galería.', 'error');
            }
        } catch (err) {
            if (window.showCenterToast) showCenterToast('Error de conexión al reordenar la galería.', 'error');
        }
    }

    // ── Panel "Reseñas" — puerto de partials/_reviews.blade.php +
    //    _review_modal.blade.php + _reviews_scripts.blade.php. El alta/edición
    //    se hace en un formulario inline (no modal aparte) que se abre/cierra
    //    dentro del mismo panel -- mismo criterio de "un solo panel central"
    //    que el resto del editor en vivo. Campos exactos = columnas reales de
    //    ServicePageReview (ver app/Models/ServicePageReview.php), no los
    //    inventados: customer_name/role/company/city/state, review_date,
    //    rating, comment, categories[], is_verified, is_visible,
    //    business_response, business_response_date. ──

    function renderReviewsPanel() {
        const visibleCount = reviewsData.filter((r) => r.is_visible).length;
        editPanel.innerHTML = `
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <span>
                        <strong>Reseñas capturadas</strong>
                        <small>${visibleCount} visibles de ${reviewsData.length}</small>
                    </span>
                </span>
            </div>
            <div id="leClientRatings"></div>
            <p class="live-editor-col-title" style="margin-top:14px;">Reseñas capturadas</p>
            <div id="leReviewsList" class="hs-repeat-rows"></div>
            <button type="button" id="leReviewAddBtn" class="live-editor-btn live-editor-btn--outline live-editor-btn--block" style="margin-top:10px;">+ Agregar reseña</button>
            <div id="leReviewFormWrap"></div>
        `;
        renderClientRatings();
        renderReviewsList();
        editPanel.querySelector('#leReviewAddBtn').addEventListener('click', () => openReviewForm(null));
    }

    // ── Opiniones de clientes: lista con interruptor "Mostrar en el sitio" ──

    function syncClientRatingsFromReviews() {
        clientRatingsData.forEach((cr) => {
            const rev = reviewsData.find((r) => String(r.service_rating_id) === String(cr.id));
            cr.review_id = rev ? rev.id : null;
            cr.is_published = !!(rev && rev.is_visible);
        });
    }

    function clientRatingOrderIndex(cr) {
        return reviewsData.findIndex((r) => String(r.id) === String(cr.review_id));
    }

    function clientRatingRowHtml(cr, opts) {
        const stars = `${'★'.repeat(cr.rating || 0)}${'☆'.repeat(5 - (cr.rating || 0))}`;
        const comment = cr.comment || '';
        const chips = (cr.answers || []).map((a) => `<span class="users-manager-badge status" style="font-size:10px;margin:2px 3px 0 0;">${escHtml(a.label)} ${a.score}</span>`).join('');
        return `
            ${opts.shown ? `<div class="service-review-row__drag" title="Arrastrar para reordenar">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>` : ''}
            <div class="service-review-row__body">
                <div class="service-review-row__head">
                    <strong>${escHtml(cr.customer_name)}</strong>
                    <span class="service-review-row__stars" role="img" aria-label="${cr.rating || 0} de 5 estrellas">${stars}</span>
                    ${cr.card_label ? `<span class="users-manager-badge status" style="font-size:10px;">${escHtml(cr.card_label)}</span>` : ''}
                    ${cr.date ? `<small>${escHtml(cr.date)}</small>` : ''}
                </div>
                ${comment ? `<p class="service-review-row__comment">${escHtml(comment.length > 140 ? comment.slice(0, 140) + '…' : comment)}</p>` : ''}
                ${chips ? `<div>${chips}</div>` : ''}
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin-top:6px;">
                    <input type="checkbox" class="le-cr-toggle" ${cr.is_published ? 'checked' : ''}> Mostrar en el sitio
                </label>
            </div>
            ${opts.shown ? `<div class="header-right-user-manager">
                <button type="button" class="table-users-manager-action-btn le-cr-up" title="Subir" ${opts.index === 0 ? 'disabled' : ''}>↑</button>
                <button type="button" class="table-users-manager-action-btn le-cr-down" title="Bajar" ${opts.index === opts.total - 1 ? 'disabled' : ''}>↓</button>
            </div>` : ''}
        `;
    }

    function renderClientRatings() {
        const box = editPanel.querySelector('#leClientRatings');
        if (!box) return;
        syncClientRatingsFromReviews();

        const shown = clientRatingsData.filter((cr) => cr.is_published)
            .sort((a, b) => clientRatingOrderIndex(a) - clientRatingOrderIndex(b));
        const others = clientRatingsData.filter((cr) => !cr.is_published);

        box.innerHTML = `
            <p class="live-editor-col-title">Opiniones de clientes</p>
            <p class="hs-config-note">Calificaciones enviadas por clientes de este servicio. Activa "Mostrar en el sitio" para publicarlas como reseña verificada; el nombre y el comentario no se editan.</p>
            <div id="leClientShown" class="hs-repeat-rows"></div>
            <div id="leClientOthers" class="hs-repeat-rows"></div>
        `;

        if (!clientRatingsData.length) {
            box.querySelector('#leClientShown').innerHTML = '<p class="hs-config-note">Todavía no hay opiniones de clientes para este servicio.</p>';
            return;
        }

        const bind = (row, cr) => {
            row.querySelector('.le-cr-toggle').addEventListener('change', (e) => toggleClientRating(cr, e.target.checked, e.target));
        };

        const shownList = box.querySelector('#leClientShown');
        shown.forEach((cr, index) => {
            const row = document.createElement('div');
            row.className = 'service-review-row';
            row.dataset.reviewId = cr.review_id;
            row.innerHTML = clientRatingRowHtml(cr, { shown: true, index, total: shown.length });
            bind(row, cr);
            const move = (delta) => {
                const ids = shown.map((s) => s.review_id);
                const j = index + delta;
                if (j < 0 || j >= ids.length) return;
                [ids[index], ids[j]] = [ids[j], ids[index]];
                persistClientRatingsOrder(ids);
            };
            row.querySelector('.le-cr-up').addEventListener('click', () => move(-1));
            row.querySelector('.le-cr-down').addEventListener('click', () => move(1));
            shownList.appendChild(row);
        });

        if (shown.length > 1) {
            new Sortable(shownList, {
                animation: 150,
                handle: '.service-review-row__drag',
                forceFallback: true,
                ghostClass: 'service-review-row--ghost',
                dragClass: 'service-review-row--dragging',
                onEnd(evt) {
                    if (evt.oldIndex === evt.newIndex) return;
                    const ids = shown.map((s) => s.review_id);
                    const [moved] = ids.splice(evt.oldIndex, 1);
                    ids.splice(evt.newIndex, 0, moved);
                    persistClientRatingsOrder(ids);
                },
            });
        }

        const othersList = box.querySelector('#leClientOthers');
        others.forEach((cr) => {
            const row = document.createElement('div');
            row.className = 'service-review-row';
            row.innerHTML = clientRatingRowHtml(cr, { shown: false });
            bind(row, cr);
            othersList.appendChild(row);
        });
    }

    async function toggleClientRating(cr, on, checkbox) {
        const tpl = on ? DATA.clientRatingPublishUrlTemplate : DATA.clientRatingHideUrlTemplate;
        if (checkbox) checkbox.disabled = true;
        try {
            const res = await fetch(tpl.replace('__RATING_ID__', cr.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (data.review) {
                    const idx = reviewsData.findIndex((r) => String(r.id) === String(data.review.id));
                    if (idx !== -1) reviewsData[idx] = Object.assign({}, reviewsData[idx], data.review);
                    else reviewsData.push(data.review);
                }
                if (data.client_rating) Object.assign(cr, data.client_rating);
                renderReviewsPanel();
                if (window.showCenterToast) showCenterToast(on ? 'Opinión publicada en el sitio.' : 'Opinión oculta del sitio.');
                schedulePreview();
            } else {
                throw new Error('toggle-client-rating-failed');
            }
        } catch (err) {
            if (checkbox) { checkbox.disabled = false; checkbox.checked = !on; }
            if (window.showCenterToast) showCenterToast('No se pudo actualizar la opinión.', 'error');
        }
    }

    async function persistClientRatingsOrder(ids) {
        try {
            const res = await fetch(DATA.clientRatingsReorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ order: ids }),
            });
            if (!res.ok) throw new Error('reorder-failed');
            // Misma lógica que el servidor: reparte los sort_order que ya ocupaban.
            const involved = ids.map((id) => reviewsData.find((r) => String(r.id) === String(id))).filter(Boolean);
            let slots = involved.map((r) => r.sort_order || 0).sort((a, b) => a - b);
            if (new Set(slots).size !== slots.length) {
                const base = Math.min(...slots);
                slots = slots.map((_, i) => base + i);
            }
            ids.forEach((id, i) => {
                const r = reviewsData.find((x) => String(x.id) === String(id));
                if (r) r.sort_order = slots[i];
            });
            reviewsData.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
            renderReviewsPanel();
            if (window.showCenterToast) showCenterToast('Orden de opiniones actualizado.');
            schedulePreview();
        } catch (err) {
            if (window.showCenterToast) showCenterToast('No se pudo guardar el nuevo orden.', 'error');
            renderReviewsPanel();
        }
    }

    function renderReviewsList() {
        const list = editPanel.querySelector('#leReviewsList');
        if (!list) return;
        list.innerHTML = '';

        if (!reviewsData.length) {
            list.innerHTML = '<p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>';
            return;
        }

        reviewsData.forEach((review) => {
            const row = document.createElement('div');
            row.className = 'service-review-row';
            row.dataset.id = review.id;
            const comment = review.comment || '';
            row.innerHTML = `
                <div class="service-review-row__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                <div class="service-review-row__body">
                    <div class="service-review-row__head">
                        <strong>${escHtml(review.customer_name)}</strong>
                        <span class="service-review-row__stars">${'★'.repeat(review.rating || 0)}${'☆'.repeat(5 - (review.rating || 0))}</span>
                        ${review.is_verified ? '<span class="users-manager-badge status" style="font-size:10px;">Verificado</span>' : ''}
                        ${review.source === 'client' ? '<span class="users-manager-badge status" style="font-size:10px;">Cliente</span>' : ''}
                        <span class="users-manager-badge ${review.is_visible ? 'status' : 'status-inactive'}" style="font-size:10px;">${review.is_visible ? 'Visible' : 'Oculta'}</span>
                    </div>
                    <p class="service-review-row__comment">${escHtml(comment.length > 140 ? comment.slice(0, 140) + '…' : comment)}</p>
                </div>
                <div class="header-right-user-manager">
                    <button type="button" class="table-users-manager-action-btn edit" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    </button>
                    <button type="button" class="table-users-manager-action-btn delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </div>
            `;

            row.querySelector('.edit').addEventListener('click', () => openReviewForm(review));
            row.querySelector('.delete').addEventListener('click', () => {
                openDeleteModal(review.customer_name || 'Reseña', () => deleteReview(review.id));
            });

            list.appendChild(row);
        });

        // SortableJS en vez de HTML5 drag-and-drop nativo — ver la nota en
        // el docblock del archivo. Igual que renderGalleryGrid(), `list` se
        // recrea en cada render, así que la instancia se crea fresca cada vez.
        new Sortable(list, {
            animation: 150,
            handle: '.service-review-row__drag',
            forceFallback: true, // ver la nota en renderBlocksList()
            ghostClass: 'service-review-row--ghost',
            dragClass: 'service-review-row--dragging',
            onEnd(evt) {
                if (evt.oldIndex === evt.newIndex) return;
                const [moved] = reviewsData.splice(evt.oldIndex, 1);
                reviewsData.splice(evt.newIndex, 0, moved);
                renderReviewsList();
                renderClientRatings();
                persistReviewsOrder();
            },
        });
    }

    function openReviewForm(review) {
        const isEdit = !!review;
        const wrap = editPanel.querySelector('#leReviewFormWrap');
        if (!wrap) return;
        const draft = isEdit ? Object.assign({}, review) : {
            customer_name: '', customer_role: '', customer_company: '', customer_city: '', customer_state: '',
            review_date: '', rating: 5, comment: '', categories: [], is_verified: false, is_visible: true,
            business_response: '', business_response_date: '',
        };

        wrap.innerHTML = `
            <div class="show-user-divider" style="margin:14px 0 10px;"></div>
            <p class="live-editor-col-title">${isEdit ? 'Editar reseña' : 'Nueva reseña'}</p>
            ${field('Cliente', `<input type="text" class="users-manager-input" id="leRevName" value="${escHtml(draft.customer_name)}">`)}
            <div class="live-editor-field-row">
                ${field('Puesto (opcional)', `<input type="text" class="users-manager-input" id="leRevRole" value="${escHtml(draft.customer_role)}" placeholder="Jefe de mantenimiento">`)}
                ${field('Empresa (opcional)', `<input type="text" class="users-manager-input" id="leRevCompany" value="${escHtml(draft.customer_company)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${field('Ciudad (opcional)', `<input type="text" class="users-manager-input" id="leRevCity" value="${escHtml(draft.customer_city)}">`)}
                ${field('Estado (opcional)', `<input type="text" class="users-manager-input" id="leRevState" value="${escHtml(draft.customer_state)}" placeholder="JAL">`)}
            </div>
            ${field('Fecha', `<input type="date" class="users-manager-input" id="leRevDate" value="${escHtml(draft.review_date)}">`)}
            ${field('Calificación', `<div class="service-review-stars-input" id="leRevStars"></div><input type="hidden" id="leRevRating" value="${draft.rating || 5}">`)}
            <div class="live-editor-field">
                <label>Comentario &middot; <span id="leRevCommentCount">${(draft.comment || '').length}</span>/240</label>
                <textarea class="users-manager-input client-modal-textarea" id="leRevComment" rows="3" maxlength="240">${escHtml(draft.comment)}</textarea>
            </div>
            ${field('Categorías', `
                <div class="hs-product-chips" style="flex-wrap:wrap;">
                    ${Object.entries(DATA.reviewCategories || {}).map(([key, label]) => `
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;font-weight:400;">
                            <input type="checkbox" class="le-rev-category" value="${escHtml(key)}" ${(draft.categories || []).includes(key) ? 'checked' : ''}> ${escHtml(label)}
                        </label>
                    `).join('')}
                </div>
            `)}
            <div class="live-editor-field-row">
                ${field('', `<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVerified" ${draft.is_verified ? 'checked' : ''}> Cliente verificado</label>`)}
                ${field('', `<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVisible" ${draft.is_visible ? 'checked' : ''}> Visible en público</label>`)}
            </div>
            ${field('Respuesta de Equiterm (opcional)', `<textarea class="users-manager-input client-modal-textarea" id="leRevResponse" rows="2">${escHtml(draft.business_response)}</textarea>`)}
            ${field('Fecha de respuesta (opcional)', `<input type="date" class="users-manager-input" id="leRevResponseDate" value="${escHtml(draft.business_response_date)}">`)}
            <div id="leRevErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <div style="display:flex;gap:8px;">
                <button type="button" id="leRevCancel" class="live-editor-btn live-editor-btn--outline" style="flex:1;">Cancelar</button>
                <button type="button" id="leRevSave" class="live-editor-btn live-editor-btn--solid" style="flex:1;">${isEdit ? 'Guardar cambios' : 'Crear reseña'}</button>
            </div>
        `;

        const starsWrap = wrap.querySelector('#leRevStars');
        function setStars(v) {
            wrap.querySelector('#leRevRating').value = v;
            starsWrap.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'service-review-star' + (i <= v ? ' is-active' : '');
                btn.textContent = '★';
                btn.addEventListener('click', () => setStars(i));
                starsWrap.appendChild(btn);
            }
        }
        setStars(draft.rating || 5);

        wrap.querySelector('#leRevComment').addEventListener('input', function () {
            wrap.querySelector('#leRevCommentCount').textContent = this.value.length;
        });

        // Reseña de origen cliente: solo se editan respuesta y visibilidad.
        if (draft.source === 'client') {
            wrap.querySelectorAll('#leRevName, #leRevRole, #leRevCompany, #leRevCity, #leRevState, #leRevDate, #leRevComment, #leRevVerified, .le-rev-category')
                .forEach((el) => { el.disabled = true; });
            starsWrap.style.pointerEvents = 'none';
            const note = document.createElement('p');
            note.className = 'hs-config-note';
            note.textContent = 'Opinión de un cliente: el nombre, la calificación y el comentario no se pueden editar. Solo puedes responder, ocultarla o reordenarla.';
            wrap.querySelector('.live-editor-col-title').after(note);
        }

        wrap.querySelector('#leRevCancel').addEventListener('click', () => { wrap.innerHTML = ''; });
        wrap.querySelector('#leRevSave').addEventListener('click', () => saveReview(isEdit ? review.id : null, wrap));

        wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function saveReview(id, wrap) {
        const errorsBox = wrap.querySelector('#leRevErrors');
        errorsBox.style.display = 'none';
        errorsBox.innerHTML = '';

        const payload = {
            customer_name: wrap.querySelector('#leRevName').value,
            customer_role: wrap.querySelector('#leRevRole').value || null,
            customer_company: wrap.querySelector('#leRevCompany').value || null,
            customer_city: wrap.querySelector('#leRevCity').value || null,
            customer_state: wrap.querySelector('#leRevState').value || null,
            review_date: wrap.querySelector('#leRevDate').value || null,
            rating: parseInt(wrap.querySelector('#leRevRating').value, 10) || 5,
            comment: wrap.querySelector('#leRevComment').value,
            categories: Array.from(wrap.querySelectorAll('.le-rev-category:checked')).map((cb) => cb.value),
            is_verified: wrap.querySelector('#leRevVerified').checked,
            is_visible: wrap.querySelector('#leRevVisible').checked,
            business_response: wrap.querySelector('#leRevResponse').value || null,
            business_response_date: wrap.querySelector('#leRevResponseDate').value || null,
        };

        const editedReview = id ? reviewsData.find((r) => String(r.id) === String(id)) : null;
        if (editedReview && editedReview.source === 'client') {
            ['customer_name', 'customer_role', 'customer_company', 'customer_city', 'customer_state', 'review_date', 'rating', 'comment', 'categories', 'is_verified']
                .forEach((k) => { delete payload[k]; });
        }

        const saveBtn = wrap.querySelector('#leRevSave');
        saveBtn.disabled = true;
        const originalText = saveBtn.textContent;
        saveBtn.textContent = 'Guardando...';

        try {
            const url = id ? DATA.reviewUpdateUrlTemplate.replace('__REVIEW_ID__', id) : DATA.reviewsStoreUrl;
            const res = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (res.ok) {
                if (id) {
                    const idx = reviewsData.findIndex((r) => String(r.id) === String(id));
                    if (idx !== -1) reviewsData[idx] = Object.assign({}, reviewsData[idx], data.review);
                } else {
                    reviewsData.push(data.review);
                }
                renderReviewsPanel();
                if (window.showCenterToast) showCenterToast(id ? 'Reseña actualizada.' : 'Reseña creada.');
                schedulePreview();
            } else if (res.status === 422) {
                const errors = data.errors || {};
                errorsBox.innerHTML = Object.values(errors).flat().map((m) => `<p>${m}</p>`).join('');
                errorsBox.style.display = 'block';
                saveBtn.disabled = false;
                saveBtn.textContent = originalText;
            } else {
                throw new Error('save-review-failed');
            }
        } catch (err) {
            errorsBox.innerHTML = '<p>No se pudo guardar. Intenta de nuevo.</p>';
            errorsBox.style.display = 'block';
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    }

    async function deleteReview(id) {
        try {
            const res = await fetch(DATA.reviewDestroyUrlTemplate.replace('__REVIEW_ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const idx = reviewsData.findIndex((r) => String(r.id) === String(id));
                if (idx !== -1) reviewsData.splice(idx, 1);
                renderReviewsPanel();
                if (window.showCenterToast) showCenterToast('Reseña eliminada.');
                schedulePreview();
            } else if (window.showCenterToast) {
                showCenterToast('No se pudo eliminar la reseña.', 'error');
            }
        } catch (err) {
            if (window.showCenterToast) showCenterToast('Error de conexión al eliminar la reseña.', 'error');
        }
    }

    async function persistReviewsOrder() {
        const order = reviewsData.map((r) => r.id);
        try {
            const res = await fetch(DATA.reviewsReorderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ order }),
            });
            if (res.ok) {
                reviewsData.forEach((r, i) => { r.sort_order = i; });
                if (window.showCenterToast) showCenterToast('Orden de reseñas actualizado.');
            } else if (window.showCenterToast) {
                showCenterToast('No se pudo guardar el nuevo orden.', 'error');
            }
        } catch (err) {
            if (window.showCenterToast) showCenterToast('Error de conexión al guardar el orden.', 'error');
        }
    }

    // Contexto que consumen los paneles propios de Colecciones
    // (live-editor-collection-panels.js).
    const collectionState = DATA.collectionState || { type: 'manual', match_type: 'all', rules: [], products: [] };
    function collectionCtx() {
        return {
            editPanel, DATA, csrfToken, escHtml, field, markDirty, schedulePreview,
            generalData, collectionState, pageHeading,
            showToast: (msg) => { if (window.showCenterToast) window.showCenterToast(msg); },
            openDeleteModal,
            openImagePicker: (...args) => window.openImagePicker && window.openImagePicker(...args),
        };
    }

    function renderPanel() {
        if (IS_COLLECTION && panelMode === 'general') {
            renderCollectionGeneralPanel(collectionCtx());
            return;
        }
        if (IS_COLLECTION && panelMode === 'products') {
            renderCollectionProductsPanel(collectionCtx());
            return;
        }
        if (panelMode === 'general') {
            renderGeneralPanel();
            return;
        }
        if (panelMode === 'gallery') {
            renderGalleryPanel();
            return;
        }
        if (panelMode === 'reviews') {
            renderReviewsPanel();
            return;
        }

        const section = findSection(selectedUid);

        if (!section) {
            editPanel.innerHTML = `<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para ${IS_COLLECTION ? 'nombre, slug y SEO' : 'nombre, slug, precio y SEO'}.</div>`;
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
                        <strong>${escHtml(plainLabel(section.title) || TYPE_LABELS[section.type] || section.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${field(IS_COLLECTION ? 'Título (opcional, puedes usar {coleccion})' : 'Título (opcional, puedes usar {servicio})', `<input type="text" class="users-manager-input" id="leTitle" value="${escHtml(section.title)}" placeholder="${IS_COLLECTION ? 'Ej: Por qué elegir esta colección' : 'Ej: Beneficios del servicio'}">`)}
            ${TITLE_STYLE_TYPES.includes(section.type) ? '<div id="leTitleStyle"></div>' : ''}
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

        if (TITLE_STYLE_TYPES.includes(section.type)) {
            const cfg = section.config = section.config || {};
            cfg.title_style = cfg.title_style || {};
            mountTypographyFields(editPanel.querySelector('#leTitleStyle'), cfg.title_style, { tagChoices: ['h2', 'h3'] });
        }

        renderTypeFields(editPanel.querySelector('#leTypeFields'), section);
    }

    // Tipos cuyo título de bloque (section.title) se renderiza públicamente
    // como encabezado (<h2> por defecto) -- ganan el control de tipografía
    // compartido justo debajo del campo Título.
    const TITLE_STYLE_TYPES = ['benefits_grid', 'process_steps', 'content_tabs', 'gallery_carousel', 'faq', 'brand_logos'];

    function renderTypeFields(container, section) {
        const cfg = section.config = section.config || {};

        switch (section.type) {
            case 'banner':
                renderBannerFields(container, cfg, '', null, { recommendedSize: '1200×400px, horizontal (ancho completo de la página)' });
                break;
            case 'dual_banner':
                container.innerHTML = '<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>';
                cfg.left = cfg.left || { image_url: '', link_url: '', alt: '', no_link: false };
                cfg.right = cfg.right || { image_url: '', link_url: '', alt: '', no_link: false };
                renderBannerFields(container.querySelector('#leDbLeft'), cfg.left, 'Left', null, { recommendedSize: '800×500px cada uno (mismo tamaño en ambos para que se vean parejos)' });
                renderBannerFields(container.querySelector('#leDbRight'), cfg.right, 'Right', null, { recommendedSize: '800×500px cada uno (mismo tamaño en ambos para que se vean parejos)' });
                break;
            case 'product_carousel':
                renderProductCarouselFields(container, cfg, '');
                break;
            case 'product_carousel_banner':
                container.innerHTML = '<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>';
                renderBannerFields(container.querySelector('#lePcbBanner'), {
                    image_url: cfg.banner_image_url, link_url: cfg.banner_link_url, alt: cfg.banner_alt, no_link: cfg.banner_no_link,
                }, 'PcbBanner', (key, value) => {
                    if (key === 'image_url') cfg.banner_image_url = value;
                    if (key === 'link_url') cfg.banner_link_url = value;
                    if (key === 'alt') cfg.banner_alt = value;
                    if (key === 'no_link') cfg.banner_no_link = value;
                }, { recommendedSize: '440×640px, vertical (se recorta para llenar el espacio junto al carrusel)' });
                renderProductCarouselFields(container.querySelector('#lePcbCarousel'), cfg, 'Pcb');
                break;
            case 'category_grid':
                renderCategoryGridFields(container, cfg);
                break;
            case 'brand_carousel':
                container.innerHTML = '<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';
                break;
            case 'brand_logos':
                renderBrandLogosFields(container, cfg);
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
            case 'button':
                renderButtonFields(container, cfg);
                break;
            case 'table_block':
                renderTableBlockFields(container, cfg);
                break;
            default:
                container.innerHTML = '<p class="hs-config-note">Tipo de bloque desconocido.</p>';
        }
    }

    // ── banner / dual_banner (izq/der) / product_carousel_banner.banner ──
    function renderBannerFields(container, cfg, suffix, customSetter, opts) {
        const idImg = 'leBanner' + suffix + 'Image';
        const idLink = 'leBanner' + suffix + 'Link';
        const idAlt = 'leBanner' + suffix + 'Alt';
        const recommendedSize = opts && opts.recommendedSize;

        container.innerHTML = `
            ${field('URL de Imagen', `
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${idImg}" value="${escHtml(cfg.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${idImg}">Seleccionar</button>
                </div>
                ${recommendedSize ? `<p class="hs-config-note">Medida recomendada: ${escHtml(recommendedSize)}.</p>` : ''}
            `)}
            <div class="live-editor-field-row">
                ${field('URL de Enlace', `<input type="text" class="users-manager-input" id="${idLink}" value="${escHtml(cfg.link_url)}" placeholder="/servicio/otro-servicio" ${cfg.no_link ? 'disabled' : ''}>`)}
                ${field('Texto Alternativo', `<input type="text" class="users-manager-input" id="${idAlt}" value="${escHtml(cfg.alt)}">`)}
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin-top:-8px;">
                <input type="checkbox" id="${idLink}NoLink" ${cfg.no_link ? 'checked' : ''}> Sin enlace (la imagen no redirige a ningún lado)
            </label>
        `;

        const setImg = (v) => customSetter ? customSetter('image_url', v) : (cfg.image_url = v);
        const setLink = (v) => customSetter ? customSetter('link_url', v) : (cfg.link_url = v);
        const setAlt = (v) => customSetter ? customSetter('alt', v) : (cfg.alt = v);
        const setNoLink = (v) => customSetter ? customSetter('no_link', v) : (cfg.no_link = v);

        const imgInput = container.querySelector('#' + idImg);
        imgInput.addEventListener('input', () => { setImg(imgInput.value); markDirty(); schedulePreview(); });

        const linkInput = container.querySelector('#' + idLink);
        linkInput.addEventListener('input', (e) => { setLink(e.target.value); markDirty(); schedulePreview(); });
        container.querySelector('#' + idAlt).addEventListener('input', (e) => { setAlt(e.target.value); markDirty(); schedulePreview(); });

        container.querySelector('#' + idLink + 'NoLink').addEventListener('change', (e) => {
            setNoLink(e.target.checked);
            linkInput.disabled = e.target.checked;
            markDirty();
            schedulePreview();
        });

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

    // Grid de categorías: el admin elige EXACTAMENTE qué categorías mostrar (de cualquier nivel)
    // y en qué orden. Sin ninguna elegida se muestran todas las principales activas.
    function renderCategoryGridFields(container, cfg) {
        const all = DATA.categories || [];
        const byId = new Map(all.map((c) => [String(c.id), c]));
        const pathOf = (c) => {
            const parts = [];
            let node = c;
            for (let i = 0; node && i < 6; i++) {
                parts.unshift(node.name);
                node = node.parent_id ? byId.get(String(node.parent_id)) : null;
            }
            return parts.join(' › ');
        };
        const entries = all.map((c) => ({ id: Number(c.id), path: pathOf(c) })).sort((x, y) => x.path.localeCompare(y.path, 'es'));
        cfg.category_ids = Array.isArray(cfg.category_ids) ? cfg.category_ids.map(Number).filter((n) => n > 0) : [];

        container.innerHTML = `
            <p class="hs-config-note">Elige qué categorías se muestran y en qué orden. Si no eliges ninguna, se muestran todas las categorías principales activas.</p>
            <div class="live-editor-field">
                <label>Categorías elegidas <span id="leCatCount" style="font-weight:400;color:#6b7280;"></span></label>
                <div id="leCatSel"></div>
            </div>
            <div class="live-editor-field">
                <label for="leCatSearch">Agregar categoría</label>
                <input type="text" class="users-manager-input" id="leCatSearch" placeholder="Buscar categoría…" autocomplete="off" data-no-link="1">
                <div id="leCatList" style="max-height:220px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;margin-top:6px;background:#fff;"></div>
            </div>
        `;
        const selBox = container.querySelector('#leCatSel');
        const listBox = container.querySelector('#leCatList');
        const search = container.querySelector('#leCatSearch');
        const count = container.querySelector('#leCatCount');
        const label = (id) => (entries.find((e) => e.id === id) || { path: 'Categoría #' + id }).path;
        const commit = () => { markDirty(); schedulePreview(); renderSelected(); renderList(); };
        const rowStyle = 'display:flex;align-items:center;gap:8px;padding:7px 10px;border-bottom:1px solid #f3f4f6;font-size:13px;';
        const btnStyle = 'border:1px solid #d1d5db;background:#fff;border-radius:6px;padding:2px 8px;cursor:pointer;line-height:1.4;';

        function renderSelected() {
            count.textContent = cfg.category_ids.length ? `(${cfg.category_ids.length})` : '(todas las principales)';
            if (!cfg.category_ids.length) {
                selBox.innerHTML = '<div style="padding:10px;border:1px dashed #d1d5db;border-radius:8px;font-size:12.5px;color:#6b7280;">Ninguna elegida: se muestran todas las categorías principales activas.</div>';
                return;
            }
            selBox.innerHTML = `<div style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;background:#fff;">${cfg.category_ids.map((id, i) => `
                <div style="${rowStyle}" data-i="${i}">
                    <span style="color:#9ca3af;width:18px;text-align:right;">${i + 1}</span>
                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escHtml(label(id))}">${escHtml(label(id))}</span>
                    <button type="button" data-act="up" style="${btnStyle}" ${i === 0 ? 'disabled' : ''} aria-label="Subir">↑</button>
                    <button type="button" data-act="down" style="${btnStyle}" ${i === cfg.category_ids.length - 1 ? 'disabled' : ''} aria-label="Bajar">↓</button>
                    <button type="button" data-act="rm" style="${btnStyle}color:#b91c1c;" aria-label="Quitar">×</button>
                </div>`).join('')}</div>
                <button type="button" class="button-secondary size-adjustment" id="leCatClear" style="margin-top:8px;">Quitar todas</button>`;
        }

        function renderList() {
            const q = search.value.trim().toLowerCase();
            const rows = entries.filter((e) => !cfg.category_ids.includes(e.id) && (!q || e.path.toLowerCase().includes(q))).slice(0, 80);
            listBox.innerHTML = rows.length
                ? rows.map((e) => `<button type="button" data-add="${e.id}" style="${rowStyle}width:100%;border:0;border-bottom:1px solid #f3f4f6;background:#fff;text-align:left;cursor:pointer;"><span style="color:#f97316;font-weight:700;">+</span><span style="flex:1;">${escHtml(e.path)}</span></button>`).join('')
                : '<div style="padding:10px;font-size:12.5px;color:#6b7280;">Sin resultados.</div>';
        }

        selBox.addEventListener('click', (ev) => {
            const clear = ev.target.closest('#leCatClear');
            if (clear) { cfg.category_ids = []; commit(); return; }
            const btn = ev.target.closest('button[data-act]');
            if (!btn) return;
            const i = Number(btn.closest('[data-i]').dataset.i);
            const ids = cfg.category_ids;
            if (btn.dataset.act === 'rm') ids.splice(i, 1);
            else if (btn.dataset.act === 'up' && i > 0) [ids[i - 1], ids[i]] = [ids[i], ids[i - 1]];
            else if (btn.dataset.act === 'down' && i < ids.length - 1) [ids[i + 1], ids[i]] = [ids[i], ids[i + 1]];
            commit();
        });
        listBox.addEventListener('click', (ev) => {
            const btn = ev.target.closest('button[data-add]');
            if (!btn) return;
            cfg.category_ids.push(Number(btn.dataset.add));
            commit();
        });
        search.addEventListener('input', renderList);

        renderSelected();
        renderList();
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
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Preguntas frecuentes</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Alimentan el <code>FAQPage</code> de Google y el acordeón de este bloque. Se comparten con toda la página, aunque haya más de un bloque "Preguntas Frecuentes".</p>
                <div id="leFaqRows" class="hs-faq-items"></div>
                <button type="button" class="button-secondary size-adjustment" id="leFaqAdd" style="margin-top:10px;">+ Agregar pregunta</button>
            </div>
            <div id="leFaqErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leFaqSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar preguntas frecuentes</button>
        `;
        container.querySelector('#leFaqDescription').addEventListener('input', (e) => {
            cfg.description = e.target.value;
            markDirty();
            schedulePreview();
        });

        container.querySelector('#leFaqAdd').addEventListener('click', () => {
            generalData.faqs = generalData.faqs || [];
            generalData.faqs.push({ question: '', answer: '' });
            markDirty();
            renderFaqRepeater(container.querySelector('#leFaqRows'));
        });
        renderFaqRepeater(container.querySelector('#leFaqRows'));

        container.querySelector('#leFaqSaveBtn').addEventListener('click', () => saveGeneralInfo({ btnId: 'leFaqSaveBtn', errorsBoxId: 'leFaqErrors' }));
    }

    function renderRichHeaderFields(container, cfg) {
        container.innerHTML = `
            ${field('Badges cortos (separados por ·, máx. 3)', `<input type="text" class="users-manager-input" id="leRhBadges" value="${escHtml((cfg.badges || []).join(' · '))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${field('Líneas de meta (una por línea)', `<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${escHtml((cfg.meta_lines || []).join('\n'))}</textarea>`)}
            ${field('Texto del botón', `<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${escHtml(cfg.whatsapp_text || 'Cotizar por WhatsApp')}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${field('Precio mostrado (opcional)', `<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${escHtml(cfg.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${field('Imagen de fondo (galería del servicio)', `<div id="leRhBgImages"></div><p class="hs-config-note" style="margin-top:6px;">Si marcas varias, solo se usa la primera.</p>`)}
            ${field('ALT de la imagen de fondo', `<input type="text" class="users-manager-input" id="leRhBgAlt" maxlength="255" value="${escHtml(cfg.background_alt)}" placeholder="Vacío = ALT de la galería">`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
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
        container.querySelector('#leRhBgAlt').addEventListener('input', (e) => { cfg.background_alt = e.target.value; markDirty(); schedulePreview(); });
        renderImagePickerGrid(container.querySelector('#leRhBgImages'), cfg.background_image_ids, DATA.images, (ids) => {
            cfg.background_image_ids = ids;
            markDirty();
            schedulePreview();
        });

        cfg.title_style = cfg.title_style || {};
        cfg.subtitle_style = cfg.subtitle_style || {};
        mountTypographyFields(container.querySelector('#leRhTitleStyle'), cfg.title_style, {});
        mountTypographyFields(container.querySelector('#leRhSubtitleStyle'), cfg.subtitle_style, {});
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
                ${IS_COLLECTION ? '' : `<select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${imagesOptionsHtml(tab.image_id ? [tab.image_id] : [], DATA.images)}
                </select>
                <input type="text" class="users-manager-input le-image-alt" maxlength="255" placeholder="ALT de la imagen (vacío = ALT de la galería)" style="margin-top:6px;" value="${escHtml(tab.image_alt)}">`}
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `;
            row.querySelector('.le-label').addEventListener('input', (e) => { tab.label = e.target.value; markDirty(); renderBlocksList(); schedulePreview(); });
            row.querySelector('.le-subtitle').addEventListener('input', (e) => { tab.subtitle = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-body').addEventListener('input', (e) => { tab.body = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-bullets').addEventListener('input', (e) => { tab.bullets = e.target.value.split('\n').map((v) => v.trim()).filter(Boolean); markDirty(); schedulePreview(); });
            row.querySelector('.le-image')?.addEventListener('change', (e) => { tab.image_id = e.target.value || null; markDirty(); schedulePreview(); });
            row.querySelector('.le-image-alt')?.addEventListener('input', (e) => { tab.image_alt = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.tabs.splice(i, 1);
                markDirty();
                renderContentTabsFields(container, cfg);
                schedulePreview();
            });
            tab.style = tab.style || {};
            mountTypographyFields(row.querySelector('.le-tab-style'), tab.style, {});
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
        cfg.layout = cfg.layout === 'vertical' ? 'vertical' : 'horizontal';
        container.innerHTML = `
            ${field('Diseño de las tarjetas', `
                <select class="users-manager-select" id="leBgLayout">
                    <option value="horizontal" ${cfg.layout === 'horizontal' ? 'selected' : ''}>Horizontal (en fila)</option>
                    <option value="vertical" ${cfg.layout === 'vertical' ? 'selected' : ''}>Vertical (apiladas)</option>
                </select>
            `)}
            <p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>
        `;
        container.querySelector('#leBgLayout').addEventListener('change', (e) => {
            cfg.layout = e.target.value;
            markDirty();
            schedulePreview();
        });
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

    // ── brand_logos ("Bloque de Marcas"): logotipos propios del servicio ──
    // Cada logo se agrega con el picker global de medios (galería existente,
    // subir desde el equipo o URL) -- mismo window.openImagePicker que usa la
    // Galería -- y puede llevar nombre (alt) y enlace opcional.
    function renderBrandLogosFields(container, cfg) {
        cfg.logos = Array.isArray(cfg.logos) ? cfg.logos : [];
        container.innerHTML = `
            <p class="hs-config-note">Logotipos de las marcas con las que trabajas en este servicio. Usa imágenes con fondo transparente (PNG/SVG/WebP) para que se vean parejas.</p>
            <div id="leBlRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leBlAdd" style="margin-top:10px;">+ Agregar logotipo</button>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin-top:14px;">
                <input type="checkbox" id="leBlGray" ${cfg.grayscale ? 'checked' : ''}> Mostrar en escala de grises (a color al pasar el cursor)
            </label>
        `;
        const rows = container.querySelector('#leBlRows');

        const rerender = () => { renderBrandLogosFields(container, cfg); schedulePreview(); };

        cfg.logos.forEach((logo, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head">
                    <span class="hs-repeat-row-num">${i + 1}</span>
                    <span style="margin-left:auto;display:flex;gap:4px;">
                        <button type="button" class="hs-faq-btn le-bl-up" title="Subir" ${i === 0 ? 'disabled' : ''}>&uarr;</button>
                        <button type="button" class="hs-faq-btn le-bl-down" title="Bajar" ${i === cfg.logos.length - 1 ? 'disabled' : ''}>&darr;</button>
                        <button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>
                    </span>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="width:84px;height:56px;flex-shrink:0;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        ${logo.url ? `<img src="${escHtml(logo.url)}" alt="" style="max-width:100%;max-height:100%;object-fit:contain;">` : '<span style="font-size:11px;color:#9ca3af;">Sin imagen</span>'}
                    </div>
                    <button type="button" class="button-secondary size-adjustment le-bl-pick">${logo.url ? 'Cambiar logotipo' : 'Seleccionar logotipo'}</button>
                </div>
                <input type="text" class="users-manager-input le-bl-alt" placeholder="Nombre de la marca (texto alternativo)" style="margin-top:6px;" value="${escHtml(logo.alt)}">
                <input type="text" class="users-manager-input le-bl-link" placeholder="Enlace (opcional): https://… o /servicios/…" style="margin-top:6px;" value="${escHtml(logo.link_url)}">
            `;
            row.querySelector('.le-bl-alt').addEventListener('input', (e) => { logo.alt = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-bl-link').addEventListener('input', (e) => { logo.link_url = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-bl-pick').addEventListener('click', () => {
                if (typeof window.openImagePicker !== 'function') return;
                window.openImagePicker(null, {
                    onSelect: (url) => { logo.url = url; markDirty(); rerender(); },
                });
            });
            row.querySelector('.le-bl-up').addEventListener('click', () => {
                if (i === 0) return;
                [cfg.logos[i - 1], cfg.logos[i]] = [cfg.logos[i], cfg.logos[i - 1]];
                markDirty(); rerender();
            });
            row.querySelector('.le-bl-down').addEventListener('click', () => {
                if (i === cfg.logos.length - 1) return;
                [cfg.logos[i + 1], cfg.logos[i]] = [cfg.logos[i], cfg.logos[i + 1]];
                markDirty(); rerender();
            });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => {
                cfg.logos.splice(i, 1);
                markDirty(); rerender();
            });
            rows.appendChild(row);
        });

        container.querySelector('#leBlAdd').addEventListener('click', () => {
            if (typeof window.openImagePicker !== 'function') return;
            window.openImagePicker(null, {
                onSelect: (url) => {
                    cfg.logos.push({ url, alt: '', link_url: '' });
                    markDirty(); rerender();
                },
            });
        });

        container.querySelector('#leBlGray').addEventListener('change', (e) => {
            cfg.grayscale = e.target.checked;
            markDirty(); schedulePreview();
        });
    }

    // Galería de una COLECCIÓN: no hay galería propia como en un servicio, así que
    // las imágenes se eligen con el selector general (biblioteca, subir o URL) y
    // se guardan como config.images = [{url, alt}] (lo que ya lee gallery-carousel).
    function renderCollectionGalleryFields(container, cfg) {
        cfg.images = Array.isArray(cfg.images) ? cfg.images : [];
        container.innerHTML = `
            <p class="hs-config-note">Imágenes de la galería de esta colección.</p>
            <div id="leCgRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leCgAdd" style="margin-top:10px;">+ Agregar imagen</button>
        `;
        const rows = container.querySelector('#leCgRows');
        const rerender = () => { renderCollectionGalleryFields(container, cfg); schedulePreview(); };

        cfg.images.forEach((img, i) => {
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="width:84px;height:56px;flex-shrink:0;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        ${img.url ? `<img src="${escHtml(img.url)}" alt="" style="max-width:100%;max-height:100%;object-fit:cover;">` : '<span style="font-size:11px;color:#9ca3af;">Sin imagen</span>'}
                    </div>
                    <button type="button" class="button-secondary size-adjustment le-cg-pick">${img.url ? 'Cambiar imagen' : 'Seleccionar imagen'}</button>
                </div>
                <input type="text" class="users-manager-input le-cg-alt" placeholder="Texto alternativo" style="margin-top:6px;" value="${escHtml(img.alt)}">
            `;
            row.querySelector('.le-cg-alt').addEventListener('input', (e) => { img.alt = e.target.value; markDirty(); schedulePreview(); });
            row.querySelector('.le-cg-pick').addEventListener('click', () => {
                if (typeof window.openImagePicker !== 'function') return;
                window.openImagePicker(null, { onSelect: (url) => { img.url = url; markDirty(); rerender(); } });
            });
            row.querySelector('.hs-repeat-remove').addEventListener('click', () => { cfg.images.splice(i, 1); markDirty(); rerender(); });
            rows.appendChild(row);
        });

        container.querySelector('#leCgAdd').addEventListener('click', () => {
            if (typeof window.openImagePicker !== 'function') return;
            window.openImagePicker(null, { onSelect: (url) => { cfg.images.push({ url, alt: '' }); markDirty(); rerender(); } });
        });
    }

    function renderGalleryCarouselFields(container, cfg) {
        if (IS_COLLECTION) {
            renderCollectionGalleryFields(container, cfg);
            return;
        }
        container.innerHTML = field('Imágenes a mostrar (vacío = toda la galería)', `<div id="leGcImages"></div>`);
        renderImagePickerGrid(container.querySelector('#leGcImages'), cfg.image_ids, DATA.images, (ids) => {
            cfg.image_ids = ids;
            markDirty();
            schedulePreview();
        }, { clearAllLabel: 'Vaciar selección' });
    }

    function renderRatingReviewsFields(container, cfg) {
        container.innerHTML = `
            ${field('Texto descriptivo (opcional)', `<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${escHtml(cfg.description)}</textarea>`)}
            ${field('Reseñas visibles antes de "Ver más"', `<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${cfg.reviews_per_page ?? 3}">`)}
            ${field('Qué opiniones mostrar', `
                <select class="users-manager-input" id="leRrMode">
                    <option value="auto" ${cfg.mode !== 'selected' ? 'selected' : ''}>Todas las visibles (automático)</option>
                    <option value="selected" ${cfg.mode === 'selected' ? 'selected' : ''}>Elegir opiniones</option>
                </select>
            `)}
            <div id="leRrPicker"></div>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin:8px 0;">
                <input type="checkbox" id="leRrBreakdown" ${cfg.show_breakdown ? 'checked' : ''}> Mostrar promedio por variable (Limpieza, Ordenado…)
            </label>
            <p class="hs-config-note">Las reseñas se capturan en el panel <strong>Reseñas</strong> del sidebar (ahí también publicas las opiniones de clientes), y las estadísticas de "Promedio mostrado" en <strong>Información general</strong>. El promedio por variable se calcula con las opiniones de clientes publicadas.</p>
        `;
        container.querySelector('#leRrDescription').addEventListener('input', (e) => { cfg.description = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leRrPerPage').addEventListener('input', (e) => { cfg.reviews_per_page = parseInt(e.target.value, 10) || 3; markDirty(); schedulePreview(); });
        container.querySelector('#leRrBreakdown').addEventListener('change', (e) => { cfg.show_breakdown = e.target.checked; markDirty(); schedulePreview(); });
        container.querySelector('#leRrMode').addEventListener('change', (e) => {
            cfg.mode = e.target.value === 'selected' ? 'selected' : 'auto';
            markDirty(); renderPicker(); schedulePreview();
        });

        function renderPicker() {
            const picker = container.querySelector('#leRrPicker');
            if (cfg.mode !== 'selected') { picker.innerHTML = ''; return; }
            if (!Array.isArray(cfg.review_ids)) cfg.review_ids = [];
            // Descarta ids de reseñas que ya no existen.
            cfg.review_ids = cfg.review_ids.filter((id) => reviewsData.some((r) => String(r.id) === String(id)));
            const selected = cfg.review_ids.map((id) => reviewsData.find((r) => String(r.id) === String(id)));
            const rest = reviewsData.filter((r) => !cfg.review_ids.some((id) => String(id) === String(r.id)));
            picker.innerHTML = `<p class="live-editor-col-title">Opiniones elegidas (${selected.length})</p><div id="leRrPickList" class="hs-repeat-rows"></div>`;
            if (!reviewsData.length) {
                picker.querySelector('#leRrPickList').innerHTML = '<p class="hs-config-note">Todavía no hay reseñas ni opiniones para elegir.</p>';
                return;
            }
            const list = picker.querySelector('#leRrPickList');
            const rows = selected.map((r, i) => ({ r, i, on: true })).concat(rest.map((r) => ({ r, i: -1, on: false })));
            rows.forEach(({ r, i, on }) => {
                const row = document.createElement('div');
                row.className = 'service-review-row';
                const comment = r.comment || '';
                row.innerHTML = `
                    <div class="service-review-row__body">
                        <label style="display:flex;align-items:flex-start;gap:8px;font-weight:400;cursor:pointer;">
                            <input type="checkbox" class="le-rr-pick" ${on ? 'checked' : ''}>
                            <span>
                                <strong>${escHtml(r.customer_name)}</strong>
                                <span class="service-review-row__stars" role="img" aria-label="${r.rating || 0} de 5 estrellas">${'★'.repeat(r.rating || 0)}${'☆'.repeat(5 - (r.rating || 0))}</span>
                                ${r.source === 'client' ? '<span class="users-manager-badge status" style="font-size:10px;">Cliente</span>' : ''}
                                ${r.is_visible ? '' : '<span class="users-manager-badge status-inactive" style="font-size:10px;">Oculta (no se mostrará)</span>'}
                                <br><small>${escHtml(comment.length > 90 ? comment.slice(0, 90) + '…' : comment)}</small>
                            </span>
                        </label>
                    </div>
                    ${on ? `<div class="header-right-user-manager">
                        <button type="button" class="table-users-manager-action-btn le-rr-up" title="Subir" ${i === 0 ? 'disabled' : ''}>↑</button>
                        <button type="button" class="table-users-manager-action-btn le-rr-down" title="Bajar" ${i === selected.length - 1 ? 'disabled' : ''}>↓</button>
                    </div>` : ''}
                `;
                row.querySelector('.le-rr-pick').addEventListener('change', (e) => {
                    if (e.target.checked) cfg.review_ids.push(r.id);
                    else cfg.review_ids = cfg.review_ids.filter((id) => String(id) !== String(r.id));
                    markDirty(); renderPicker(); schedulePreview();
                });
                const move = (delta) => {
                    const j = i + delta;
                    if (j < 0 || j >= cfg.review_ids.length) return;
                    [cfg.review_ids[i], cfg.review_ids[j]] = [cfg.review_ids[j], cfg.review_ids[i]];
                    markDirty(); renderPicker(); schedulePreview();
                };
                row.querySelector('.le-rr-up')?.addEventListener('click', () => move(-1));
                row.querySelector('.le-rr-down')?.addEventListener('click', () => move(1));
                list.appendChild(row);
            });
        }
        renderPicker();
    }

    function renderCtaFinalFields(container, cfg) {
        container.innerHTML = `
            ${field('Título', `<input type="text" class="users-manager-input" id="leCtaHeadline" value="${escHtml(cfg.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${field('Texto de apoyo', `<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${escHtml(cfg.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${field('Texto del botón', `<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${escHtml(cfg.whatsapp_text || 'Cotizar por WhatsApp')}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${IS_COLLECTION ? '' : field('Imagen de fondo (opcional)', `<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${imagesOptionsHtml(cfg.background_image_id ? [cfg.background_image_id] : [], DATA.images)}</select>`)}
            ${IS_COLLECTION ? '' : field('ALT de la imagen de fondo', `<input type="text" class="users-manager-input" id="leCtaBgAlt" maxlength="255" value="${escHtml(cfg.background_alt)}" placeholder="Vacío = ALT de la galería">`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `;
        container.querySelector('#leCtaHeadline').addEventListener('input', (e) => { cfg.headline = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leCtaSubtext').addEventListener('input', (e) => { cfg.subtext = e.target.value; markDirty(); schedulePreview(); });
        container.querySelector('#leCtaWhatsapp').addEventListener('input', (e) => { cfg.whatsapp_text = e.target.value; markDirty(); schedulePreview(); });
        const ctaBg = container.querySelector('#leCtaBg');
        if (ctaBg) ctaBg.addEventListener('change', (e) => { cfg.background_image_id = e.target.value || null; markDirty(); schedulePreview(); });
        const ctaBgAlt = container.querySelector('#leCtaBgAlt');
        if (ctaBgAlt) ctaBgAlt.addEventListener('input', (e) => { cfg.background_alt = e.target.value; markDirty(); schedulePreview(); });

        cfg.headline_style = cfg.headline_style || {};
        cfg.subtext_style = cfg.subtext_style || {};
        mountTypographyFields(container.querySelector('#leCtaHeadlineStyle'), cfg.headline_style, { tagChoices: ['h2', 'h3'] });
        mountTypographyFields(container.querySelector('#leCtaSubtextStyle'), cfg.subtext_style, {});

        cfg.secondary_button = cfg.secondary_button || { text: '', url: '', style: 'outline', color: '#ff6213' };
        mountButtonFields(container.querySelector('#leCtaSecondaryBtn'), cfg.secondary_button, { alignField: false });
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

        // "Información general" (nombre/slug/precio/descripción/SEO/rating)
        // y el repetidor de FAQ se guardan con su propio botón, endpoint
        // aparte -- este botón grande solo guardaba el orden/config de los
        // bloques. Si el panel abierto es uno de esos dos, este botón
        // también dispara ESE guardado primero: de lo contrario, editar
        // "Descripción corta" y luego pulsar el botón grande (lo más
        // intuitivo) no guardaba nada ahí, pareciendo que el campo "no deja
        // actualizarse".
        if (IS_COLLECTION) {
            // Los paneles de Colecciones guardan con su propio botón; si hay uno
            // abierto se dispara antes, para no perder lo capturado ahí.
            const colBtn = editPanel.querySelector('#leColSaveBtn') || editPanel.querySelector('#leColSettingsSave');
            if (colBtn) {
                colBtn.click();
                // Espera a que el botón del panel termine (se deshabilita mientras guarda)
                // en lugar de un retraso fijo; tope de 10 s.
                await new Promise((r) => setTimeout(r, 120));
                for (let i = 0; i < 80 && colBtn.disabled; i++) {
                    await new Promise((r) => setTimeout(r, 125));
                }
            }
        } else if (editPanel.querySelector('#leGenSaveBtn')) {
            await saveGeneralInfo();
        } else if (editPanel.querySelector('#leFaqSaveBtn')) {
            await saveGeneralInfo({ btnId: 'leFaqSaveBtn', errorsBoxId: 'leFaqErrors' });
        }

        try {
            const res = await fetch(DATA.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({
                    sections: draftSections.map((s, i) => ({ ...s, sort_order: i })),
                    deleted_ids: deletedIds,
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

    // ── Enlaces en cualquier texto (botón 🔗 por campo) ──────────────
    if (DATA.linkSearchUrl) {
        mountLinkTools(editPanel, {
            searchUrl: DATA.linkSearchUrl,
            csrfToken,
            // Nombre/descripción/SEO se imprimen como texto plano (title, meta, JSON-LD):
            // ahí un enlace aparecería literal.
            exclude: (f) => /^le(Col|Gen)(Name|Desc|ShortDesc|Seo)/.test(f.id || ''),
        });
    }

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
