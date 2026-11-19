/**
 * live-editor-category-panels.js
 *
 * Panel "Información general" del editor en vivo de Categorías (los 3
 * niveles). Mismo patrón que renderCollectionGeneralPanel en
 * live-editor-collection-panels.js.
 *
 * Contrato del contexto `ctx` (lo arma service-page-live-editor.js, collectionCtx()):
 *   editPanel        HTMLElement (#leEditPanel)
 *   DATA             window.__LIVE_EDITOR__ (usa DATA.generalUrl)
 *   csrfToken        string
 *   escHtml(s)       escape HTML
 *   field(label, innerHtml, extraClass?)
 *   schedulePreview() refresca el iframe de vista previa
 *   generalData      objeto mutable {name, slug, description, image_url,
 *                    is_active, sort_order, seo_title, seo_description,
 *                    faqs:[{question,answer}], public_path}
 *   pageHeading      HTMLElement del encabezado (opcional)
 *   showToast?(msg, type?)
 *   openImagePicker  function(inputId|null, {onSelect})
 *
 * Guarda con PUT a DATA.generalUrl:
 *   {name, slug (último segmento), description, image_url, is_active, sort_order, seo_title,
 *    seo_description, faq_items:[{question,answer}]}
 * y espera {success, category:{..., faqs, public_path}}.
 */

const SEO_TITLE_MAX = 60;
const SEO_DESC_MAX = 160;

const IMG_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';

// El slug guardado incluye la ruta de los padres (principal/sub/hija); aquí
// solo se edita el último segmento: el servidor reconstruye el resto.
function lastSegment(slug) {
    const parts = String(slug ?? '').split('/');
    return parts[parts.length - 1] || '';
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

export function renderCategoryGeneralPanel(ctx) {
    const { editPanel, escHtml, field, generalData } = ctx;
    // Las FAQ pueden llegar como `faqs` (modelo) o `faq_items` (payload).
    if (!Array.isArray(generalData.faqs)) {
        generalData.faqs = Array.isArray(generalData.faq_items) ? generalData.faq_items : [];
    }

    editPanel.innerHTML = `
        <div class="live-editor-panel-header">
            <span class="live-editor-panel-header-info">
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                </span>
                <span><strong>Información general</strong><small>Nombre, descripción, imagen, SEO y preguntas frecuentes</small></span>
            </span>
        </div>
        ${field('Nombre', `<input type="text" class="users-manager-input" id="leCatName" maxlength="120" value="${escHtml(generalData.name)}">`)}
        ${field('Slug (última parte de la URL)', `
            <div style="display:flex;gap:8px;">
                <input type="text" class="users-manager-input" id="leCatSlug" maxlength="150" value="${escHtml(lastSegment(generalData.slug))}" style="flex:1;" data-no-link="1">
                <button type="button" id="leCatSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
            </div>
            <p class="hs-config-note" id="leCatPublicPath" style="margin-top:6px;"></p>`)}
        ${field('Descripción', `<textarea class="users-manager-input client-modal-textarea" id="leCatDesc" rows="3">${escHtml(generalData.description)}</textarea>`)}
        ${field('Imagen de la categoría', `
            <div class="img-picker-field" style="display:flex;gap:8px;">
                <input type="text" class="users-manager-input" id="leCatImage" value="${escHtml(generalData.image_url)}" placeholder="https://..." style="flex:1;" data-no-link="1">
                <button type="button" class="img-picker-trigger-btn" id="leCatImagePick">${IMG_ICON} Seleccionar</button>
            </div>
            <div id="leCatImagePreview" style="margin-top:8px;"></div>`)}
        <div class="live-editor-field-row">
            ${field('Orden', `<input type="number" min="0" step="1" class="users-manager-input" id="leCatSort" value="${escHtml(generalData.sort_order ?? 0)}">`)}
            ${field('Estado', `
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;min-height:38px;">
                    <input type="checkbox" id="leCatActive" ${generalData.is_active ? 'checked' : ''}> Activa
                </label>`)}
        </div>
        <div class="show-user-divider" style="margin:10px 0;"></div>
        ${field(`Título SEO (máx. ${SEO_TITLE_MAX})`, `
            <input type="text" class="users-manager-input" id="leCatSeoTitle" value="${escHtml(generalData.seo_title)}" maxlength="${SEO_TITLE_MAX}" placeholder="Vacío = nombre de la categoría">
            <div class="pform-char-row"><span class="pform-char-count" id="leCatSeoTitleCount">0/${SEO_TITLE_MAX}</span></div>`)}
        ${field(`Descripción SEO (máx. ${SEO_DESC_MAX})`, `
            <textarea class="users-manager-input client-modal-textarea" id="leCatSeoDesc" rows="2" maxlength="${SEO_DESC_MAX}" placeholder="Resumen para Google">${escHtml(generalData.seo_description)}</textarea>
            <div class="pform-char-row"><span class="pform-char-count" id="leCatSeoDescCount">0/${SEO_DESC_MAX}</span></div>`)}
        <div class="show-user-divider" style="margin:10px 0;"></div>
        ${field('Preguntas frecuentes de la categoría', `
            <div id="leCatFaqRows" class="hs-faq-items"></div>
            <button type="button" id="leCatFaqAdd" class="button-secondary size-adjustment" style="margin-top:10px;">+ Agregar pregunta</button>`)}
        <div id="leCatErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
        <button type="button" id="leCatSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        <p id="leCatSavedMsg" class="hs-config-note" style="display:none;text-align:center;color:#16a34a;margin-top:8px;">Guardado</p>
    `;

    const $ = (id) => editPanel.querySelector('#' + id);

    const paintPath = () => {
        const path = generalData.public_path || ('/catalogo/' + (generalData.slug || ''));
        $('leCatPublicPath').innerHTML = `URL actual: <code>${escHtml(path)}</code>. Si cambias el slug, la URL anterior redirige (301) a la nueva.`;
    };
    paintPath();
    $('leCatSlugGenerate').addEventListener('click', () => { $('leCatSlug').value = slugify($('leCatName').value); });

    const updateCounters = () => {
        $('leCatSeoTitleCount').textContent = $('leCatSeoTitle').value.length + '/' + SEO_TITLE_MAX;
        $('leCatSeoDescCount').textContent = $('leCatSeoDesc').value.length + '/' + SEO_DESC_MAX;
    };
    $('leCatSeoTitle').addEventListener('input', updateCounters);
    $('leCatSeoDesc').addEventListener('input', updateCounters);
    updateCounters();

    const imgInput = $('leCatImage');
    const paintImage = () => {
        const url = imgInput.value.trim();
        $('leCatImagePreview').innerHTML = url
            ? `<img src="${escHtml(url)}" alt="" style="max-width:160px;max-height:110px;border-radius:8px;border:1px solid #e5e7eb;object-fit:cover;">`
            : '';
    };
    imgInput.addEventListener('input', paintImage);
    paintImage();
    $('leCatImagePick').addEventListener('click', () => {
        pickImage(ctx, (url) => {
            if (!url) return;
            imgInput.value = url;
            paintImage();
        });
    });

    // Repetidor de FAQ -- se re-renderiza al agregar/quitar.
    const renderFaqs = () => {
        const rows = $('leCatFaqRows');
        rows.innerHTML = '';
        if (!generalData.faqs.length) {
            rows.innerHTML = '<p class="hs-config-note">Sin preguntas frecuentes.</p>';
            return;
        }
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
    $('leCatFaqAdd').addEventListener('click', () => {
        generalData.faqs.push({ question: '', answer: '' });
        renderFaqs();
    });

    // Cualquier edición oculta el aviso "Guardado".
    // (editPanel persiste entre renders: el listener delegado se registra una vez.)
    if (!editPanel.dataset.leCatSavedHide) {
        editPanel.dataset.leCatSavedHide = '1';
        editPanel.addEventListener('input', () => {
            const m = editPanel.querySelector('#leCatSavedMsg');
            if (m) m.style.display = 'none';
        });
    }

    $('leCatSaveBtn').addEventListener('click', () => saveCategoryGeneral(ctx));
}

async function saveCategoryGeneral(ctx) {
    const { editPanel, DATA, generalData, csrfToken } = ctx;
    const $ = (id) => editPanel.querySelector('#' + id);
    const btn = $('leCatSaveBtn');
    const errorsBox = $('leCatErrors');
    const savedMsg = $('leCatSavedMsg');
    errorsBox.style.display = 'none';
    errorsBox.innerHTML = '';
    savedMsg.style.display = 'none';
    editPanel.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));

    const payload = {
        name: $('leCatName').value.trim(),
        slug: $('leCatSlug').value.trim(),
        description: $('leCatDesc').value,
        image_url: $('leCatImage').value.trim(),
        is_active: $('leCatActive').checked,
        sort_order: parseInt($('leCatSort').value, 10) || 0,
        seo_title: $('leCatSeoTitle').value,
        seo_description: $('leCatSeoDesc').value,
        // Las filas totalmente vacías no se mandan.
        faq_items: (generalData.faqs || [])
            .map((f) => ({ question: String(f.question ?? '').trim(), answer: String(f.answer ?? '').trim() }))
            .filter((f) => f.question || f.answer),
    };

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Guardando...';

    try {
        const res = await fetch(DATA.generalUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            body: JSON.stringify(payload),
        });
        if (res.status === 419) {
            errorsBox.innerHTML = '<p>Tu sesión expiró. Recarga la página e intenta de nuevo.</p>';
            errorsBox.style.display = 'block';
            return;
        }
        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            const saved = data.category || null;
            if (saved) Object.assign(generalData, saved);
            else Object.assign(generalData, payload);
            generalData.faqs = Array.isArray(generalData.faqs) ? generalData.faqs : payload.faq_items;

            if (ctx.pageHeading) ctx.pageHeading.textContent = generalData.name;
            document.title = 'Editor en vivo - ' + generalData.name + ' - Admin';
            const slugInput = $('leCatSlug');
            if (slugInput) slugInput.value = lastSegment(generalData.slug);
            if ($('leCatPublicPath')) {
                const path = generalData.public_path || ('/catalogo/' + (generalData.slug || ''));
                $('leCatPublicPath').innerHTML = `URL actual: <code>${ctx.escHtml(path)}</code>. Si cambias el slug, la URL anterior redirige (301) a la nueva.`;
            }
            const crumb = document.getElementById('leCrumbCurrent');
            if (crumb) crumb.textContent = generalData.name;
            if (generalData.public_path) {
                const urlEl = document.getElementById('leBrowserUrl');
                if (urlEl) urlEl.textContent = window.location.host + generalData.public_path;
                const liveLink = document.getElementById('leViewLiveLink');
                if (liveLink) liveLink.href = window.location.origin + generalData.public_path;
            }

            savedMsg.style.display = 'block';
            toast(ctx, 'Información general guardada.');
            ctx.schedulePreview();
        } else if (res.status === 422) {
            const errors = data.errors || {};
            errorsBox.innerHTML = Object.values(errors).flat().map((m) => `<p>${ctx.escHtml(m)}</p>`).join('') || '<p>Revisa los campos.</p>';
            errorsBox.style.display = 'block';
            const map = {
                name: 'leCatName', slug: 'leCatSlug', description: 'leCatDesc', image_url: 'leCatImage', sort_order: 'leCatSort',
                seo_title: 'leCatSeoTitle', seo_description: 'leCatSeoDesc',
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
