@push('scripts')
    <script>
        /* "Plantillas de bloques" del editor por lotes. Opera sobre los productos
           marcados con el checkbox de fila (solo los de la página actual, igual
           que "Aplicar a seleccionados"; para cubrir todo el filtro, cambia el
           selector de página a "Todos"). A diferencia de las columnas del editor,
           esto se guarda en BD al confirmar, tras una vista previa (dry-run). */
        (function () {
            const openBtn = document.getElementById('prodBulkBlocksBtn');
            const modal = document.getElementById('bulkBlocksModal');
            if (!openBtn || !modal) return;

            const URLS = {
                templates: @json(route('admin.products.blocks.templates')),
                preview: @json(route('admin.products.blocks.bulk.preview')),
                apply: @json(route('admin.products.blocks.bulk.apply')),
            };
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const $ = id => document.getElementById(id);
            const stepConfig = $('bbkStepConfig'), stepPreview = $('bbkStepPreview');
            const btnCancel = $('bbkCancel'), btnBack = $('bbkBack'), btnPreview = $('bbkPreviewBtn'), btnApply = $('bbkApplyBtn');
            const search = $('bbkSearch'), list = $('bbkList');

            let productIds = [];
            let selected = new Set();
            let timer = null, seq = 0;

            function el(tag, cls, text) {
                const n = document.createElement(tag);
                if (cls) n.className = cls;
                if (text !== undefined) n.textContent = text;
                return n;
            }

            function toast(message, type) {
                const t = el('div', '', message);
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;padding:14px 18px;border-radius:10px;font-size:14px;'
                    + 'box-shadow:0 4px 12px rgba(0,0,0,.12);max-width:380px;border:1px solid;'
                    + (type === 'error' ? 'background:#fef2f2;color:#991b1b;border-color:#fecaca;' : 'background:#f0fdf4;color:#166534;border-color:#bbf7d0;');
                document.body.appendChild(t);
                setTimeout(() => t.remove(), 5000);
            }

            async function api(url, method, body) {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'X-CSRF-TOKEN': csrf, 'Accept': 'application/json',
                        ...(body ? { 'Content-Type': 'application/json' } : {}),
                    },
                    body: body ? JSON.stringify(body) : undefined,
                });
                let data = {};
                try { data = await res.json(); } catch (e) { /* no JSON */ }
                if (!res.ok || data.success === false) {
                    const first = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(data.message || first || 'No se pudo completar la acción.');
                }
                return data;
            }

            const mode = () => (modal.querySelector('input[name="bbkMode"]:checked') || {}).value || 'append';

            function syncButtons() {
                const n = selected.size;
                $('bbkSelectedCount').textContent = n === 0 ? 'Ninguna plantilla elegida'
                    : n + (n === 1 ? ' plantilla elegida' : ' plantillas elegidas');
                btnPreview.disabled = n === 0;
            }

            function showStep(step) {
                const preview = step === 'preview';
                stepConfig.style.display = preview ? 'none' : '';
                stepPreview.style.display = preview ? '' : 'none';
                btnBack.style.display = preview ? '' : 'none';
                btnApply.style.display = preview ? '' : 'none';
                btnPreview.style.display = preview ? 'none' : '';
                btnCancel.style.display = preview ? 'none' : '';
            }

            function note(text) {
                list.innerHTML = '';
                const li = el('li', ''); li.appendChild(el('div', 'bbk-note', text)); list.appendChild(li);
            }

            async function loadTemplates() {
                const mine = ++seq;
                note('Buscando…');
                const params = new URLSearchParams({ include_inactive: 1, q: search.value.trim() });
                try {
                    const data = await api(URLS.templates + '?' + params.toString(), 'GET');
                    if (mine !== seq) return;
                    list.innerHTML = '';
                    if (!data.templates.length) {
                        note(search.value.trim() ? 'Ninguna plantilla coincide con la búsqueda.' : 'Aún no hay plantillas de bloques creadas.');
                        return;
                    }
                    data.templates.forEach(t => {
                        const li = el('li', '');
                        const label = el('label', '');
                        const cb = document.createElement('input');
                        cb.type = 'checkbox'; cb.checked = selected.has(t.id);
                        cb.addEventListener('change', () => { cb.checked ? selected.add(t.id) : selected.delete(t.id); syncButtons(); });
                        const text = el('div', '');
                        text.appendChild(el('div', 'bbk-name', t.name + (t.is_active ? '' : ' (inactiva)')));
                        text.appendChild(el('div', 'bbk-sub',
                            t.type_label + ' · ' + t.zone_label + ' · usada en ' + t.usage + (t.usage === 1 ? ' producto' : ' productos')));
                        label.append(cb, text); li.appendChild(label); list.appendChild(li);
                    });
                } catch (e) {
                    if (mine === seq) note(e.message);
                }
            }

            function open() {
                productIds = Array.from(document.querySelectorAll('.prod-bulk-row-select:checked')).map(cb => Number(cb.value));
                if (!productIds.length) { toast('Selecciona al menos un producto.', 'error'); return; }
                selected = new Set();
                search.value = '';
                modal.querySelector('input[name="bbkMode"][value="append"]').checked = true;
                $('bbkSubtitle').textContent = productIds.length + (productIds.length === 1 ? ' producto seleccionado.' : ' productos seleccionados.')
                    + ' Elige las plantillas y qué hacer con ellas.';
                showStep('config');
                syncButtons();
                modal.classList.add('active');
                loadTemplates();
                setTimeout(() => search.focus(), 50);
            }

            const payload = () => ({ ids: productIds, section_ids: Array.from(selected), mode: mode() });

            btnPreview.addEventListener('click', async () => {
                btnPreview.disabled = true;
                try {
                    const p = await api(URLS.preview, 'POST', payload());
                    $('bbkPreviewMsg').textContent = p.message;
                    const ul = $('bbkBreakdown'); ul.innerHTML = '';
                    p.breakdown.forEach(b => {
                        const text = p.mode === 'remove'
                            ? b.name + ': la tienen ' + b.already + ' de ' + p.products
                            : b.name + ': ya la tenían ' + b.already + ' de ' + p.products;
                        ul.appendChild(el('li', '', '• ' + text));
                    });
                    showStep('preview');
                } catch (e) {
                    toast(e.message, 'error');
                } finally {
                    btnPreview.disabled = selected.size === 0;
                }
            });

            btnBack.addEventListener('click', () => showStep('config'));

            btnApply.addEventListener('click', async () => {
                btnApply.disabled = true;
                try {
                    const r = await api(URLS.apply, 'POST', payload());
                    // Refresca la columna informativa "Plantillas" de las filas afectadas.
                    Object.entries(r.templates_by_product || {}).forEach(([pid, names]) => {
                        const cell = document.querySelector('[data-blocks-cell="' + pid + '"]');
                        if (!cell) return;
                        cell.innerHTML = '';
                        if (!names.length) { cell.textContent = '—'; return; }
                        names.forEach(n => cell.appendChild(el('div', '', n)));
                    });
                    modal.classList.remove('active');
                    toast(r.message);
                } catch (e) {
                    toast(e.message, 'error');
                } finally {
                    btnApply.disabled = false;
                }
            });

            search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(loadTemplates, 250); });
            btnCancel.addEventListener('click', () => modal.classList.remove('active'));
            modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); });
            openBtn.addEventListener('click', open);
        })();
    </script>
@endpush
