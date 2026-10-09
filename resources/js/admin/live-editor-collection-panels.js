/**
 * live-editor-collection-panels.js
 *
 * Paneles del editor en vivo exclusivos de Colecciones (paridad con el modal
 * antiguo de admin/collections): "Información general" y "Productos y tipo".
 *
 * Contrato del contexto `ctx` (lo arma service-page-live-editor.js):
 *   editPanel        HTMLElement (#leEditPanel) donde se renderiza el panel
 *   DATA             window.__LIVE_EDITOR__ con las claves:
 *                      generalUrl, settingsUrl, productsSearchUrl,
 *                      productsAddUrl, productsRemoveUrlTemplate (con __ID__),
 *                      productsReorderUrl, ruleOptions {categories, brands}
 *                      [opcional] tagsSuggestUrl (GET ?q= -> ["etiqueta", ...])
 *   csrfToken        string
 *   escHtml(s)       escape HTML
 *   field(label, innerHtml, extraClass?)  helper del editor de servicios
 *   markDirty()      (se acepta pero estos paneles guardan por su cuenta)
 *   schedulePreview() refresca el iframe de vista previa
 *   generalData      objeto mutable {name, slug, description, image_url,
 *                    sort_order, is_active, seo_title, seo_description,
 *                    og_image_url, faqs:[{question,answer}], public_path}
 *   collectionState  objeto mutable {type, match_type, rules:[{field,operator,
 *                    value}], products:[{id,name,sku,thumb}]}
 *   showToast?(msg, type?)   cae a window.showCenterToast
 *   openDeleteModal?(label, onConfirm)  si existe se usa para quitar productos
 *   pageHeading      HTMLElement del encabezado (opcional)
 *   openImagePicker  function(inputId|null, {onSelect}) (cae a window.openImagePicker)
 */
import Sortable from 'sortablejs';

const RULE_FIELDS = [
    { value: 'tag', label: 'Etiqueta' },
    { value: 'category_id', label: 'Categoría' },
    { value: 'brand_id', label: 'Marca' },
    { value: 'price', label: 'Precio' },
];
const RULE_OPERATORS = [
    { value: 'equals', label: 'Es igual a' },
    { value: 'greater_than', label: 'Mayor que' },
    { value: 'less_than', label: 'Menor que' },
];

const IMG_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';

export function collectionPanelsDefaults() {
    return {
        generalData: {
            name: '', slug: '', description: '', image_url: '', sort_order: 0, is_active: true,
            seo_title: '', seo_description: '', og_image_url: '', faqs: [], public_path: '',
        },
        collectionState: {
            type: 'manual',           // 'manual' | 'automatic'
            match_type: 'all',        // 'all' | 'any' (solo automática)
            rules: [],                // [{field:'tag|category_id|brand_id|price', operator:'equals|greater_than|less_than', value:''}]
            products: [],             // [{id, name, sku, thumb}] (solo manual)
        },
    };
}

function slugify(value) {
    return String(value ?? '').toLowerCase()
        .normalize('NFD').replace(/[^\x00-\x7F]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-');
}

function toast(ctx, msg, type) {
    if (typeof ctx.showToast === 'function') ctx.showToast(msg, type);
    else if (window.showCenterToast) window.showCenterToast(msg, type);
}

function pickImage(ctx, onSelect) {
    const fn = ctx.openImagePicker || window.openImagePicker;
    if (typeof fn === 'function') fn(null, { onSelect: (v) => onSelect(typeof v === 'string' ? v : (v && (v.url || v.image_url)) || '') });
}

function headerHtml(title, subtitle, iconPath) {
    return `
        <div class="live-editor-panel-header">
            <span class="live-editor-panel-header-info">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconPath}</svg>
                </span>
                <span><strong>${title}</strong><small>${subtitle}</small></span>
            </span>
        </div>`;
}

// ════════════════════════════════════════════════════════════════════
// Panel: Información general
// ════════════════════════════════════════════════════════════════════
export function renderCollectionGeneralPanel(ctx) {
    const { editPanel, escHtml, field, generalData } = ctx;
    generalData.faqs = Array.isArray(generalData.faqs) ? generalData.faqs : [];

    const imgField = (id, value, placeholder) => `
        <div class="img-picker-field" style="display:flex;gap:8px;">
            <input type="text" class="users-manager-input" id="${id}" value="${escHtml(value)}" placeholder="${placeholder}" style="flex:1;">
            <button type="button" class="img-picker-trigger-btn" data-pick="${id}">${IMG_ICON} Seleccionar</button>
        </div>`;

    editPanel.innerHTML = `
        ${headerHtml('Información general', 'Nombre, slug, imagen, SEO y preguntas frecuentes', '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>')}
        ${field('Nombre', `<input type="text" class="users-manager-input" id="leColName" value="${escHtml(generalData.name)}">`)}
        ${field('Slug (URL)', `
            <div style="display:flex;gap:8px;">
                <input type="text" class="users-manager-input" id="leColSlug" value="${escHtml(generalData.slug)}" style="flex:1;">
                <button type="button" id="leColSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
            </div>`)}
        ${field('Descripción', `<textarea class="users-manager-input client-modal-textarea" id="leColDesc" rows="3">${escHtml(generalData.description)}</textarea>`)}
        ${field('URL de imagen', imgField('leColImage', generalData.image_url, 'https://...'))}
        ${field('Orden', `<input type="number" min="0" step="1" class="users-manager-input" id="leColSort" value="${escHtml(generalData.sort_order ?? 0)}">`)}
        ${field('Estado', `
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                <input type="checkbox" id="leColActive" ${generalData.is_active ? 'checked' : ''}> Activa (visible en el sitio público)
            </label>`)}
        <div class="show-user-divider" style="margin:10px 0;"></div>
        ${field('Título SEO (máx. 160)', `
            <input type="text" class="users-manager-input" id="leColSeoTitle" value="${escHtml(generalData.seo_title)}" maxlength="160">
            <div class="pform-char-row"><span class="pform-char-count" id="leColSeoTitleCount">0/160</span></div>`)}
        ${field('Descripción SEO (máx. 500)', `
            <textarea class="users-manager-input client-modal-textarea" id="leColSeoDesc" rows="2" maxlength="500">${escHtml(generalData.seo_description)}</textarea>
            <div class="pform-char-row"><span class="pform-char-count" id="leColSeoDescCount">0/500</span></div>`)}
        ${field('Imagen para redes (Open Graph, 1200x630 recomendado)', imgField('leColOg', generalData.og_image_url, 'https://...'))}
        <div class="show-user-divider" style="margin:10px 0;"></div>
        ${field('Preguntas frecuentes de la colección', `
            <div id="leColFaqRows" class="hs-faq-items"></div>
            <button type="button" id="leColFaqAdd" class="button-secondary size-adjustment" style="margin-top:10px;">+ Agregar pregunta</button>`)}
        <div id="leColErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
        <button type="button" id="leColSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
    `;

    const $ = (id) => editPanel.querySelector('#' + id);

    // Contadores de caracteres (mismo estilo que el modal antiguo).
    const updateCounters = () => {
        $('leColSeoTitleCount').textContent = $('leColSeoTitle').value.length + '/160';
        $('leColSeoDescCount').textContent = $('leColSeoDesc').value.length + '/500';
    };
    $('leColSeoTitle').addEventListener('input', updateCounters);
    $('leColSeoDesc').addEventListener('input', updateCounters);
    updateCounters();

    $('leColSlugGenerate').addEventListener('click', () => {
        $('leColSlug').value = slugify($('leColName').value);
    });

    editPanel.querySelectorAll('[data-pick]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = $(btn.dataset.pick);
            pickImage(ctx, (url) => { if (url) input.value = url; });
        });
    });

    // Repetidor de FAQ -- re-renderiza al agregar/quitar (sin <form>).
    const renderFaqs = () => {
        const rows = $('leColFaqRows');
        rows.innerHTML = '';
        generalData.faqs.forEach((item, i) => {
            const row = document.createElement('div');
            row.className = 'hs-faq-row';
            row.innerHTML = `
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${i + 1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${escHtml(item.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${escHtml(item.answer)}</textarea>`;
            row.querySelector('.hs-faq-question').addEventListener('input', (e) => { item.question = e.target.value; });
            row.querySelector('.hs-faq-answer').addEventListener('input', (e) => { item.answer = e.target.value; });
            row.querySelector('.hs-faq-remove').addEventListener('click', () => {
                generalData.faqs.splice(i, 1);
                renderFaqs();
            });
            rows.appendChild(row);
        });
    };
    renderFaqs();
    $('leColFaqAdd').addEventListener('click', () => {
        generalData.faqs.push({ question: '', answer: '' });
        renderFaqs();
    });

    $('leColSaveBtn').addEventListener('click', () => saveCollectionGeneral(ctx));
}

async function saveCollectionGeneral(ctx) {
    const { editPanel, DATA, generalData, csrfToken } = ctx;
    const $ = (id) => editPanel.querySelector('#' + id);
    const btn = $('leColSaveBtn');
    const errorsBox = $('leColErrors');
    errorsBox.style.display = 'none';
    errorsBox.innerHTML = '';
    editPanel.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));

    const payload = {
        name: $('leColName').value,
        slug: $('leColSlug').value,
        description: $('leColDesc').value,
        image_url: $('leColImage').value,
        sort_order: $('leColSort').value,
        is_active: $('leColActive').checked,
        seo_title: $('leColSeoTitle').value,
        seo_description: $('leColSeoDesc').value,
        og_image_url: $('leColOg').value,
        faq_items: generalData.faqs || [],
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
        if (res.status === 419) {
            errorsBox.innerHTML = '<p>Tu sesión expiró. Recarga la página e intenta de nuevo.</p>';
            errorsBox.style.display = 'block';
            return;
        }
        const data = await res.json();

        if (res.ok) {
            // Se acepta {collection:{...}} (por analogía con servicePage) o los campos planos.
            const saved = data.collection || data.servicePage || data.generalData || null;
            if (saved) Object.assign(generalData, saved);
            else Object.assign(generalData, payload, { faqs: payload.faq_items });

            if (ctx.pageHeading) ctx.pageHeading.textContent = generalData.name;
            document.title = 'Editor en vivo - ' + generalData.name + ' - Admin';
            const urlEl = document.getElementById('leBrowserUrl');
            if (urlEl && generalData.public_path) urlEl.textContent = 'equitermindustries.com.mx' + generalData.public_path;
            const liveLink = document.getElementById('leViewLiveLink');
            if (liveLink && generalData.public_path) liveLink.href = window.location.origin + generalData.public_path;

            toast(ctx, 'Información general guardada.');
            ctx.schedulePreview();
        } else if (res.status === 422) {
            const errors = data.errors || {};
            errorsBox.innerHTML = Object.values(errors).flat().map((m) => `<p>${ctx.escHtml(m)}</p>`).join('');
            errorsBox.style.display = 'block';
            const map = {
                name: 'leColName', slug: 'leColSlug', description: 'leColDesc', image_url: 'leColImage',
                sort_order: 'leColSort', seo_title: 'leColSeoTitle', seo_description: 'leColSeoDesc', og_image_url: 'leColOg',
            };
            Object.keys(errors).forEach((f) => {
                const el = map[f] ? $(map[f]) : null;
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

// ════════════════════════════════════════════════════════════════════
// Panel: Productos y tipo
// ════════════════════════════════════════════════════════════════════
export function renderCollectionProductsPanel(ctx) {
    const { editPanel, escHtml, field, collectionState: st } = ctx;
    st.rules = Array.isArray(st.rules) ? st.rules : [];
    st.products = Array.isArray(st.products) ? st.products : [];
    st.type = st.type === 'automatic' ? 'automatic' : 'manual';
    st.match_type = st.match_type === 'any' ? 'any' : 'all';

    editPanel.innerHTML = `
        ${headerHtml('Productos y tipo', 'Productos manuales o reglas automáticas', '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>')}
        ${field('Tipo de colección', `
            <select class="users-manager-select" id="leColType">
                <option value="manual" ${st.type === 'manual' ? 'selected' : ''}>Manual (eliges los productos)</option>
                <option value="automatic" ${st.type === 'automatic' ? 'selected' : ''}>Automática (por condiciones)</option>
            </select>`)}
        <div id="leColManualWrap"></div>
        <div id="leColAutoWrap"></div>
    `;

    const typeSel = editPanel.querySelector('#leColType');
    const manualWrap = editPanel.querySelector('#leColManualWrap');
    const autoWrap = editPanel.querySelector('#leColAutoWrap');

    const toggleType = () => {
        manualWrap.style.display = st.type === 'manual' ? '' : 'none';
        autoWrap.style.display = st.type === 'automatic' ? '' : 'none';
    };
    typeSel.addEventListener('change', () => {
        st.type = typeSel.value;
        toggleType();
        // El tipo solo se persiste con "Guardar tipo y reglas".
    });

    renderManual(ctx, manualWrap);
    renderAutomatic(ctx, autoWrap);
    toggleType();
}

// ── Manual ───────────────────────────────────────────────────────────
function renderManual(ctx, wrap) {
    const { DATA, csrfToken, escHtml, collectionState: st } = ctx;
    const jsonHeaders = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' };

    wrap.innerHTML = `
        <div class="live-editor-field">
            <label>Agregar producto</label>
            <input type="text" class="users-manager-input" id="leColProdSearch" placeholder="Buscar por nombre o SKU..." autocomplete="off">
            <div id="leColProdResults" style="display:none;margin-top:6px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;max-height:240px;overflow:auto;"></div>
        </div>
        <div class="live-editor-field">
            <label>Productos de la colección (<span id="leColProdCount">0</span>)</label>
            <p class="hs-config-note" style="margin:0 0 8px;">Arrastra para reordenar. Los cambios se guardan al instante.</p>
            <div id="leColProdList"></div>
        </div>`;

    const search = wrap.querySelector('#leColProdSearch');
    const results = wrap.querySelector('#leColProdResults');
    const list = wrap.querySelector('#leColProdList');
    const countEl = wrap.querySelector('#leColProdCount');

    const thumbHtml = (thumb) => thumb
        ? `<img src="${escHtml(thumb)}" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;flex:none;">`
        : '<span style="width:36px;height:36px;border-radius:6px;background:#f3f4f6;flex:none;"></span>';

    const renderList = () => {
        countEl.textContent = st.products.length;
        if (!st.products.length) {
            list.innerHTML = '<p class="hs-config-note">Esta colección todavía no tiene productos. Usa el buscador de arriba para agregar.</p>';
            return;
        }
        list.innerHTML = st.products.map((p) => `
            <div class="le-col-prod-row hs-repeat-row" data-id="${p.id}" style="display:flex;align-items:center;gap:8px;padding:6px 8px;margin-bottom:6px;">
                <span class="le-col-prod-handle" style="cursor:grab;color:#9ca3af;font-size:16px;line-height:1;" title="Arrastrar">&#8942;&#8942;</span>
                ${thumbHtml(p.thumb)}
                <span style="flex:1;min-width:0;">
                    <strong style="display:block;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(p.name)}</strong>
                    <small style="color:#6b7280;">SKU: ${escHtml(p.sku || '—')}</small>
                </span>
                <button type="button" class="hs-faq-btn le-col-prod-remove" title="Quitar">&times;</button>
            </div>`).join('');
        list.querySelectorAll('.le-col-prod-remove').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.closest('.le-col-prod-row').dataset.id;
                const prod = st.products.find((p) => String(p.id) === String(id));
                if (typeof ctx.openDeleteModal === 'function') ctx.openDeleteModal(prod ? prod.name : 'este producto', () => removeProduct(id));
                else removeProduct(id);
            });
        });
        if (sortable) sortable.destroy();
        sortable = Sortable.create(list, {
            animation: 150,
            handle: '.le-col-prod-handle',
            draggable: '.le-col-prod-row',
            onEnd: persistOrder,
        });
    };
    let sortable = null;

    async function persistOrder() {
        const order = Array.from(list.querySelectorAll('.le-col-prod-row')).map((r) => parseInt(r.dataset.id, 10));
        const previous = st.products.slice();
        st.products = order.map((id) => previous.find((p) => Number(p.id) === id)).filter(Boolean);
        try {
            const res = await fetch(DATA.productsReorderUrl, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ order }) });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) {
                st.products = previous;
                renderList();
                toast(ctx, data.message || 'No se pudo guardar el nuevo orden.', 'error');
            } else {
                toast(ctx, 'Orden actualizado.');
                ctx.schedulePreview();
            }
        } catch (e) {
            st.products = previous;
            renderList();
            toast(ctx, 'Error de conexión al guardar el orden.', 'error');
        }
    }

    async function removeProduct(id) {
        try {
            // Laravel DELETE directo con JSON; el method-spoofing también funcionaría.
            const res = await fetch(DATA.productsRemoveUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
                method: 'DELETE', headers: jsonHeaders,
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                st.products = st.products.filter((p) => String(p.id) !== String(id));
                renderList();
                toast(ctx, 'Producto quitado de la colección.');
                ctx.schedulePreview();
            } else {
                toast(ctx, data.message || 'No se pudo quitar el producto.', 'error');
            }
        } catch (e) {
            toast(ctx, 'Error de conexión al quitar el producto.', 'error');
        }
    }

    async function addProduct(prod) {
        results.style.display = 'none';
        search.value = '';
        try {
            const res = await fetch(DATA.productsAddUrl, { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ product_id: prod.id }) });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                const p = data.product || prod;
                st.products.push({ id: p.id, name: p.name, sku: p.sku, thumb: p.cover_image_url || p.thumb || '' });
                renderList();
                toast(ctx, 'Producto agregado a la colección.');
                ctx.schedulePreview();
            } else {
                const msg = data.message || (data.errors && Object.values(data.errors).flat()[0]) || 'No se pudo agregar el producto.';
                toast(ctx, msg, 'error');
            }
        } catch (e) {
            toast(ctx, 'Error de conexión al agregar el producto.', 'error');
        }
    }

    let timer = null;
    let reqId = 0;
    search.addEventListener('input', () => {
        clearTimeout(timer);
        const q = search.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }
        timer = setTimeout(async () => {
            const my = ++reqId;
            try {
                const url = new URL(DATA.productsSearchUrl, window.location.origin);
                url.searchParams.set('q', q);
                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok || my !== reqId) return;
                const products = await res.json();
                const assigned = new Set(st.products.map((p) => String(p.id)));
                const rows = products.filter((p) => !assigned.has(String(p.id)));
                results.style.display = 'block';
                if (!rows.length) {
                    results.innerHTML = '<p class="hs-config-note" style="padding:8px 10px;margin:0;">Sin resultados.</p>';
                    return;
                }
                results.innerHTML = rows.map((p, i) => `
                    <div class="le-col-prod-result" data-i="${i}" style="display:flex;align-items:center;gap:8px;padding:6px 10px;cursor:pointer;border-bottom:1px solid #f3f4f6;">
                        ${thumbHtml(p.cover_image_url)}
                        <span style="flex:1;min-width:0;">
                            <strong style="display:block;font-size:13px;">${escHtml(p.name)}</strong>
                            <small style="color:#6b7280;">SKU: ${escHtml(p.sku || '—')}</small>
                        </span>
                    </div>`).join('');
                results.querySelectorAll('.le-col-prod-result').forEach((el) => {
                    el.addEventListener('mouseenter', () => { el.style.background = '#f9fafb'; });
                    el.addEventListener('mouseleave', () => { el.style.background = ''; });
                    el.addEventListener('click', () => addProduct(rows[parseInt(el.dataset.i, 10)]));
                });
            } catch (e) {
                // búsqueda fallida: se ignora en silencio
            }
        }, 250);
    });

    renderList();
}

// ── Automática ───────────────────────────────────────────────────────
function renderAutomatic(ctx, wrap) {
    const { DATA, csrfToken, escHtml, collectionState: st } = ctx;
    const opts = DATA.ruleOptions || { categories: [], brands: [] };

    wrap.innerHTML = `
        <div class="live-editor-field">
            <label>Coincidencia</label>
            <select class="users-manager-select" id="leColMatch">
                <option value="all" ${st.match_type === 'all' ? 'selected' : ''}>Todas las condiciones deben cumplirse</option>
                <option value="any" ${st.match_type === 'any' ? 'selected' : ''}>Cualquiera de las condiciones</option>
            </select>
        </div>
        <div class="live-editor-field">
            <label>Condiciones</label>
            <div id="leColRuleRows"></div>
            <button type="button" id="leColRuleAdd" class="button-secondary size-adjustment" style="margin-top:8px;">+ Agregar condición</button>
        </div>
        <div id="leColRulesErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
        <button type="button" id="leColSettingsSave" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar tipo y reglas</button>`;

    const rowsEl = wrap.querySelector('#leColRuleRows');
    const matchSel = wrap.querySelector('#leColMatch');
    const errorsBox = wrap.querySelector('#leColRulesErrors');
    matchSel.addEventListener('change', () => { st.match_type = matchSel.value; });

    const optionsFor = (list, selected) =>
        '<option value=""></option>' + list.map((o) => `<option value="${o.id}" ${String(selected) === String(o.id) ? 'selected' : ''}>${escHtml(o.name)}</option>`).join('');

    const valueHtml = (rule, i) => {
        switch (rule.field) {
            case 'category_id':
            case 'brand_id': {
                const list = rule.field === 'category_id' ? opts.categories : opts.brands;
                const ph = rule.field === 'category_id' ? 'Buscar categoría...' : 'Buscar marca...';
                return `<input type="text" class="users-manager-input le-rule-filter" placeholder="${ph}" autocomplete="off" style="margin-bottom:4px;">
                        <select class="users-manager-select le-rule-value">${optionsFor(list || [], rule.value)}</select>`;
            }
            case 'price':
                return `<input type="number" class="users-manager-input le-rule-value" step="0.01" placeholder="0.00" value="${escHtml(rule.value)}">`;
            default:
                return `<input type="text" class="users-manager-input le-rule-value" placeholder="valor de la etiqueta" autocomplete="off" list="leColTagList${i}" value="${escHtml(rule.value)}">
                        <datalist id="leColTagList${i}"></datalist>`;
        }
    };

    const renderRules = () => {
        rowsEl.innerHTML = '';
        st.rules.forEach((rule, i) => {
            const isPrice = rule.field === 'price';
            if (!isPrice) rule.operator = 'equals';
            const row = document.createElement('div');
            row.className = 'hs-repeat-row';
            row.style.cssText = 'display:grid;grid-template-columns:1fr;gap:6px;padding:8px;margin-bottom:8px;';
            row.innerHTML = `
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i + 1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <select class="users-manager-select le-rule-field">
                    ${RULE_FIELDS.map((f) => `<option value="${f.value}" ${rule.field === f.value ? 'selected' : ''}>${f.label}</option>`).join('')}
                </select>
                <select class="users-manager-select le-rule-operator">
                    ${RULE_OPERATORS.filter((o) => isPrice || o.value === 'equals').map((o) => `<option value="${o.value}" ${rule.operator === o.value ? 'selected' : ''}>${o.label}</option>`).join('')}
                </select>
                <div class="le-rule-value-wrap">${valueHtml(rule, i)}</div>`;

            row.querySelector('.hs-repeat-remove').addEventListener('click', () => { st.rules.splice(i, 1); renderRules(); });
            row.querySelector('.le-rule-field').addEventListener('change', (e) => {
                rule.field = e.target.value;
                rule.value = '';
                rule.operator = 'equals';
                renderRules();
            });
            row.querySelector('.le-rule-operator').addEventListener('change', (e) => { rule.operator = e.target.value; });

            const valueEl = row.querySelector('.le-rule-value');
            valueEl.addEventListener('input', () => { rule.value = valueEl.value; });
            valueEl.addEventListener('change', () => { rule.value = valueEl.value; });

            const filter = row.querySelector('.le-rule-filter');
            if (filter) {
                filter.addEventListener('input', () => {
                    const term = filter.value.trim().toLowerCase();
                    Array.from(valueEl.options).forEach((o) => {
                        o.hidden = !!term && !!o.value && !o.textContent.toLowerCase().includes(term);
                    });
                    const first = Array.from(valueEl.options).find((o) => o.value && !o.hidden);
                    if (term && first && !valueEl.selectedOptions[0]?.textContent.toLowerCase().includes(term)) {
                        valueEl.value = first.value;
                        rule.value = first.value;
                    }
                });
                const cur = valueEl.selectedOptions[0];
                if (cur && cur.value) filter.value = cur.textContent;
            }

            // Sugerencias de etiquetas (ajax) vía <datalist>.
            const dl = row.querySelector('datalist');
            if (dl && DATA.tagsSuggestUrl) {
                let t = null;
                valueEl.addEventListener('input', () => {
                    clearTimeout(t);
                    t = setTimeout(async () => {
                        try {
                            const res = await fetch(`${DATA.tagsSuggestUrl}?q=${encodeURIComponent(valueEl.value)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                            if (!res.ok) return;
                            const tags = await res.json();
                            dl.innerHTML = (Array.isArray(tags) ? tags : []).map((tag) => `<option value="${escHtml(typeof tag === 'string' ? tag : tag.label || '')}"></option>`).join('');
                        } catch (e) { /* ignorar */ }
                    }, 200);
                });
            }
            rowsEl.appendChild(row);
        });
        if (!st.rules.length) rowsEl.innerHTML = '<p class="hs-config-note">Sin condiciones. Agrega al menos una para que la colección tenga productos.</p>';
    };

    wrap.querySelector('#leColRuleAdd').addEventListener('click', () => {
        st.rules.push({ field: 'tag', operator: 'equals', value: '' });
        renderRules();
    });
    if (st.type === 'automatic' && !st.rules.length) st.rules.push({ field: 'tag', operator: 'equals', value: '' });
    renderRules();

    wrap.querySelector('#leColSettingsSave').addEventListener('click', async () => {
        const btn = wrap.querySelector('#leColSettingsSave');
        errorsBox.style.display = 'none';
        errorsBox.innerHTML = '';
        const typeSel = ctx.editPanel.querySelector('#leColType');
        if (typeSel) st.type = typeSel.value;

        // Igual que syncRules en el servidor: se omiten filas sin valor.
        const rules = st.type === 'automatic'
            ? st.rules.filter((r) => r.field && String(r.value ?? '') !== '').map((r) => ({ field: r.field, operator: r.operator || 'equals', value: String(r.value) }))
            : [];
        const payload = { type: st.type, match_type: st.type === 'automatic' ? st.match_type : null, rules };

        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = 'Guardando...';
        try {
            const res = await fetch(DATA.settingsUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success !== false) {
                st.type = data.type || st.type;
                st.match_type = data.match_type || st.match_type || 'all';
                if (Array.isArray(data.rules)) {
                    st.rules = data.rules.map((r) => ({ field: r.field, operator: r.operator, value: r.value }));
                }
                toast(ctx, 'Tipo y reglas guardados.');
                renderCollectionProductsPanel(ctx);
                ctx.schedulePreview();
            } else if (res.status === 422) {
                errorsBox.innerHTML = Object.values(data.errors || {}).flat().map((m) => `<p>${escHtml(m)}</p>`).join('');
                errorsBox.style.display = 'block';
            } else {
                throw new Error('save-settings-failed');
            }
        } catch (e) {
            errorsBox.innerHTML = '<p>No se pudo guardar. Intenta de nuevo.</p>';
            errorsBox.style.display = 'block';
        } finally {
            btn.disabled = false;
            btn.textContent = original;
        }
    });
}
