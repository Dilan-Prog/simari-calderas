@push('scripts')
    @php
        // Mismo formato que edit_product/_scripts.blade.php: por id de
        // categoría principal, su lista de subcategorías, cada una con su
        // propia lista de categorías hijas — para poblar en cascada sin
        // pedirle nada al servidor mientras se edita.
        $subcategoriesForJs = $categoryTree
            ->mapWithKeys(fn ($c) => [
                $c->id => $c->children
                    ->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'children' => $s->children
                            ->map(fn ($ch) => ['id' => $ch->id, 'name' => $ch->name])
                            ->values()
                            ->toArray(),
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();
    @endphp
    <script>
        (function() {
            const subcategories = @json($subcategoriesForJs);

            const filterForm     = document.getElementById('bulkEditFilterForm');
            const searchInput    = document.getElementById('bulkEditSearch');
            const categoryFilter = document.getElementById('bulkEditCategoryFilter');
            const stockFilter    = document.getElementById('bulkEditStockFilter');
            const statusFilter   = document.getElementById('bulkEditStatusFilter');
            const perPageSelect  = document.getElementById('bulkEditPerPage');
            const filterControls = [searchInput, categoryFilter, stockFilter, statusFilter, perPageSelect];

            [categoryFilter, stockFilter, statusFilter, perPageSelect].forEach(select => {
                select.addEventListener('change', () => {
                    if (!changes.size) filterForm.submit();
                });
            });

            let searchDebounce = null;
            searchInput.addEventListener('input', () => {
                if (changes.size) return;
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(() => filterForm.submit(), 600);
            });

            function showToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.cssText = `
                    position: fixed; bottom: 90px; right: 24px; z-index: 9999;
                    background: ${type === 'error' ? '#fef2f2' : '#f0fdf4'};
                    color: ${type === 'error' ? '#991b1b' : '#166534'};
                    border: 1px solid ${type === 'error' ? '#fecaca' : '#bbf7d0'};
                    padding: 14px 18px; border-radius: 10px; font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-width: 360px;
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }

            // Map<"id:field", {id, field, value}>
            const changes = new Map();
            const bulkEditBar = document.getElementById('prodBulkEditBar');
            const bulkEditCount = document.getElementById('prodBulkEditCount');
            const saveBtn = document.getElementById('prodBulkEditSaveBtn');
            const discardBtn = document.getElementById('prodBulkEditDiscardBtn');
            const saveUrl = '{{ route('admin.products.bulk.save') }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function cellKey(id, field) {
                return `${id}:${field}`;
            }

            function readValue(input) {
                if (input.type === 'checkbox') return input.checked;
                return input.value;
            }

            function syncUnsavedBar() {
                bulkEditCount.textContent = changes.size;
                bulkEditBar.classList.toggle('active', changes.size > 0);
                filterControls.forEach(el => el.disabled = changes.size > 0);
                saveBtn.disabled = changes.size === 0;
            }

            // Advierte antes de cerrar/navegar con cambios sin guardar — evitar
            // perder ediciones en curso al cambiar de pestaña/cerrar por error.
            window.addEventListener('beforeunload', (e) => {
                if (changes.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            document.addEventListener('input', (e) => {
                if (!e.target.matches('.prod-bulk-input') || e.target.tagName === 'SELECT') return;
                onCellChange(e.target);
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches('.prod-bulk-input')) return;
                // Los 3 selectores en cascada de categoría se manejan aparte
                // (más abajo): no tienen data-field propio, todos resuelven
                // al mismo category_id según cuál quedó más profundo.
                if (e.target.matches('.prod-bulk-cat-main, .prod-bulk-cat-sub, .prod-bulk-cat-child')) return;
                if (e.target.tagName === 'SELECT' || e.target.type === 'checkbox') {
                    onCellChange(e.target);
                }
            });

            // Punto único de entrada al Map de cambios pendientes — lo usan
            // tanto las celdas normales (via onCellChange) como los popovers
            // (Especificaciones), que no tienen un <input> visible propio.
            function setPendingChange(id, field, value, el) {
                if (el) {
                    el.classList.add('dirty');
                    el.classList.remove('has-error', 'saved-ok');
                    el.title = '';
                }
                changes.set(cellKey(id, field), { id: Number(id), field, value });
                syncUnsavedBar();
            }

            function onCellChange(input) {
                setPendingChange(input.dataset.id, input.dataset.field, readValue(input), input);
            }

            saveBtn.addEventListener('click', async () => {
                if (!changes.size) return;
                saveBtn.disabled = true;
                saveBtn.textContent = 'Guardando...';

                try {
                    const response = await fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ changes: Array.from(changes.values()) }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudieron guardar los cambios.', 'error');
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Guardar cambios';
                        return;
                    }

                    let okCount = 0, errCount = 0;
                    data.results.forEach(r => {
                        // Selector genérico (no solo .prod-bulk-input) para que
                        // también encuentre el botón del popover de
                        // Especificaciones, que no es una celda normal.
                        const el = document.querySelector(
                            `[data-id="${r.id}"][data-field="${r.field}"]`
                        );
                        if (!el) return;

                        if (r.ok) {
                            okCount++;
                            el.classList.remove('dirty', 'has-error');
                            el.classList.add('saved-ok');
                            setTimeout(() => el.classList.remove('saved-ok'), 2000);
                            changes.delete(cellKey(r.id, r.field));
                        } else {
                            errCount++;
                            el.classList.add('has-error');
                            el.title = r.error ?? 'No se pudo guardar.';
                        }
                    });

                    showToast(
                        `${okCount} cambio(s) guardados${errCount ? `, ${errCount} fallaron — revisa las celdas en rojo` : ''}.`,
                        errCount ? 'error' : 'success'
                    );

                    saveBtn.textContent = 'Guardar cambios';
                    syncUnsavedBar();
                } catch (err) {
                    console.error('Error saving bulk edit changes:', err);
                    showToast('Error de conexión. Intenta de nuevo.', 'error');
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Guardar cambios';
                }
            });

            const discardModal = document.getElementById('bulkDiscardModal');
            const discardDesc = document.getElementById('bulkDiscardDesc');
            const discardCancelBtn = document.getElementById('bulkDiscardCancelBtn');
            const discardConfirmBtn = document.getElementById('bulkDiscardConfirmBtn');

            discardBtn.addEventListener('click', () => {
                if (!changes.size) return;
                discardDesc.textContent = `Se perderán ${changes.size} cambio(s) sin guardar.`;
                discardModal.classList.add('active');
            });
            discardCancelBtn.addEventListener('click', () => discardModal.classList.remove('active'));
            discardModal.addEventListener('click', (e) => {
                if (e.target === discardModal) discardModal.classList.remove('active');
            });
            discardConfirmBtn.addEventListener('click', () => {
                // Vacía el Map antes de recargar para que el listener de
                // beforeunload (más abajo) no dispare a su vez el propio
                // aviso nativo del navegador — ya se confirmó el descarte
                // en el modal propio, no hace falta preguntarlo dos veces.
                changes.clear();
                window.location.reload();
            });

            // ── Menú de columnas visibles (persistido en localStorage) ──
            const COLUMNS_STORAGE_KEY = 'admin_products_bulk_edit_visible_columns';
            const columnsBtn = document.getElementById('bulkEditColumnsBtn');
            const columnsMenu = document.getElementById('bulkEditColumnsMenu');
            const colToggles = document.querySelectorAll('.prod-bulk-col-toggle');

            function applyColumnVisibility() {
                colToggles.forEach(cb => {
                    document.querySelectorAll(`[data-col="${cb.value}"]`).forEach(el => {
                        el.classList.toggle('prod-bulk-col-hidden', !cb.checked);
                    });
                });
            }

            function loadStoredColumns() {
                let raw;
                try {
                    raw = localStorage.getItem(COLUMNS_STORAGE_KEY);
                } catch (e) {
                    return;
                }
                if (!raw) return; // nada guardado todavía: se respeta el default del servidor
                try {
                    const visible = JSON.parse(raw);
                    colToggles.forEach(cb => { cb.checked = visible.includes(cb.value); });
                } catch (e) {
                    // localStorage corrupto/no es JSON válido: se ignora y se
                    // mantienen los defaults renderizados por el servidor.
                }
            }

            function saveColumnsPreference() {
                const visible = Array.from(colToggles).filter(cb => cb.checked).map(cb => cb.value);
                try {
                    localStorage.setItem(COLUMNS_STORAGE_KEY, JSON.stringify(visible));
                } catch (e) {
                    // Almacenamiento lleno/bloqueado: la preferencia
                    // simplemente no persiste, no es un error fatal.
                }
            }

            colToggles.forEach(cb => cb.addEventListener('change', () => {
                applyColumnVisibility();
                saveColumnsPreference();
            }));

            columnsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                columnsMenu.classList.toggle('active');
            });
            document.addEventListener('click', (e) => {
                if (columnsMenu.classList.contains('active') && !columnsMenu.contains(e.target) && e.target !== columnsBtn) {
                    columnsMenu.classList.remove('active');
                }
            });

            loadStoredColumns();
            applyColumnVisibility();

            // ── Vistas guardadas de columnas (hasta 10, por usuario) ──
            const VIEWS_MAX = 10;
            const viewSelect = document.getElementById('bulkEditViewSelect');
            const viewNameInput = document.getElementById('bulkEditViewNameInput');
            const viewSaveBtn = document.getElementById('bulkEditViewSaveBtn');
            const viewManageRow = document.getElementById('bulkEditViewManageRow');
            const viewUpdateBtn = document.getElementById('bulkEditViewUpdateBtn');
            const viewDeleteBtn = document.getElementById('bulkEditViewDeleteBtn');
            const bulkEditViewsUrl = '{{ url('/admin/productos/edicion-masiva/vistas') }}';

            function updateViewManageVisibility() {
                viewManageRow.style.display = viewSelect.value ? 'flex' : 'none';
            }

            // Reutiliza applyColumnVisibility()/saveColumnsPreference() ya
            // existentes en vez de reimplementarlas — así localStorage sigue
            // funcionando como respaldo para la próxima carga de página,
            // ahora también alimentado por la última vista elegida. Claves
            // guardadas que ya no existan como columna simplemente no
            // coinciden con ningún checkbox — se ignoran solas.
            function applyColumnsList(keys) {
                colToggles.forEach(cb => { cb.checked = keys.includes(cb.value); });
                applyColumnVisibility();
                saveColumnsPreference();
            }

            viewSelect.addEventListener('change', () => {
                const opt = viewSelect.options[viewSelect.selectedIndex];
                if (!opt.value) {
                    updateViewManageVisibility();
                    return;
                }
                let keys = [];
                try {
                    keys = JSON.parse(opt.dataset.columns || '[]');
                } catch (e) {
                    keys = [];
                }
                applyColumnsList(keys);
                updateViewManageVisibility();
            });

            // FIX: no se resetea el select a "Vista personalizada" al tocar
            // una casilla — si lo hiciera, "Actualizar vista actual"
            // desaparecería justo cuando hace falta (querer ajustar columnas
            // de una vista ya cargada y guardarlas). El select solo cambia
            // cuando el usuario lo elige explícitamente en el dropdown; para
            // volver a "personalizada" sin guardar, lo selecciona a mano.

            function currentCheckedColumns() {
                return Array.from(colToggles).filter(cb => cb.checked).map(cb => cb.value);
            }

            viewSaveBtn.addEventListener('click', async () => {
                const name = viewNameInput.value.trim();
                if (!name) {
                    showToast('Ponle un nombre a la vista antes de guardarla.', 'error');
                    return;
                }
                // Solo UX — el límite real lo aplica el servidor.
                if (viewSelect.options.length - 1 >= VIEWS_MAX) {
                    showToast(`Ya tienes el máximo de ${VIEWS_MAX} vistas guardadas. Elimina una para poder guardar otra.`, 'error');
                    return;
                }

                viewSaveBtn.disabled = true;
                try {
                    const response = await fetch(bulkEditViewsUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, columns: currentCheckedColumns() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudo guardar la vista.', 'error');
                        return;
                    }

                    const opt = document.createElement('option');
                    opt.value = data.view.id;
                    opt.dataset.columns = JSON.stringify(data.view.columns);
                    opt.textContent = data.view.name;
                    viewSelect.appendChild(opt);
                    viewSelect.value = data.view.id;
                    viewNameInput.value = '';
                    updateViewManageVisibility();
                    showToast(`Vista "${data.view.name}" guardada.`);
                } catch (err) {
                    console.error('Error saving bulk edit view:', err);
                    showToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    viewSaveBtn.disabled = false;
                }
            });

            viewUpdateBtn.addEventListener('click', async () => {
                const id = viewSelect.value;
                if (!id) return;
                const name = viewSelect.options[viewSelect.selectedIndex].textContent.trim();

                viewUpdateBtn.disabled = true;
                try {
                    const response = await fetch(bulkEditViewsUrl + '/' + id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, columns: currentCheckedColumns() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudo actualizar la vista.', 'error');
                        return;
                    }

                    viewSelect.options[viewSelect.selectedIndex].dataset.columns = JSON.stringify(data.view.columns);
                    showToast(`Vista "${data.view.name}" actualizada.`);
                } catch (err) {
                    console.error('Error updating bulk edit view:', err);
                    showToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    viewUpdateBtn.disabled = false;
                }
            });

            viewDeleteBtn.addEventListener('click', async () => {
                const id = viewSelect.value;
                if (!id) return;
                const name = viewSelect.options[viewSelect.selectedIndex].textContent.trim();
                if (!confirm(`¿Eliminar la vista "${name}"?`)) return;

                viewDeleteBtn.disabled = true;
                try {
                    const response = await fetch(bulkEditViewsUrl + '/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudo eliminar la vista.', 'error');
                        return;
                    }

                    viewSelect.options[viewSelect.selectedIndex].remove();
                    viewSelect.value = '';
                    updateViewManageVisibility();
                    showToast(`Vista "${name}" eliminada.`);
                } catch (err) {
                    console.error('Error deleting bulk edit view:', err);
                    showToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    viewDeleteBtn.disabled = false;
                }
            });

            // ── Popover de Especificaciones (clave/valor) ──
            const specsModal = document.getElementById('bulkSpecsModal');
            const specsList = document.getElementById('bulkSpecsList');
            const specsEmpty = document.getElementById('bulkSpecsEmpty');
            const specsAddBtn = document.getElementById('bulkSpecsAddBtn');
            const specsCancelBtn = document.getElementById('bulkSpecsCancelBtn');
            const specsSaveBtn = document.getElementById('bulkSpecsSaveBtn');
            let specsEditingTrigger = null;

            function specsAddRow(key = '', value = '') {
                specsEmpty.style.display = 'none';
                specsList.style.display = 'flex';

                const row = document.createElement('div');
                row.className = 'pform-spec-row';
                row.innerHTML =
                    '<input type="text" class="pform-input bulk-spec-key" placeholder="Nombre del campo (ej: Potencia)">' +
                    '<input type="text" class="pform-input bulk-spec-value" placeholder="Valor (ej: 20 HP)">' +
                    '<button type="button" class="pform-spec-del" title="Eliminar">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                    '</svg>' +
                    '</button>';
                row.querySelector('.bulk-spec-key').value = key;
                row.querySelector('.bulk-spec-value').value = value;

                row.querySelector('.pform-spec-del').addEventListener('click', () => {
                    row.remove();
                    if (!specsList.children.length) {
                        specsList.style.display = 'none';
                        specsEmpty.style.display = 'block';
                    }
                });

                specsList.appendChild(row);
            }

            specsAddBtn.addEventListener('click', () => specsAddRow());

            // ── Popover de FAQ (pregunta/respuesta, con variables y enlaces) ──
            const faqModal = document.getElementById('bulkFaqModal');
            const faqList = document.getElementById('bulkFaqList');
            const faqEmpty = document.getElementById('bulkFaqEmpty');
            const faqAddBtn = document.getElementById('bulkFaqAddBtn');
            const faqCancelBtn = document.getElementById('bulkFaqCancelBtn');
            const faqSaveBtn = document.getElementById('bulkFaqSaveBtn');
            let faqEditingTrigger = null;

            function faqAddRow(question = '', answer = '') {
                faqEmpty.style.display = 'none';
                faqList.style.display = 'flex';

                const row = document.createElement('div');
                row.className = 'pform-faq-row';
                row.innerHTML =
                    '<div class="bulk-faq-fields">' +
                    '<input type="text" class="pform-input bulk-faq-question" placeholder="Pregunta (ej: ¿Incluye instalación?)">' +
                    '<div class="pform-faq-toolbar">' +
                    '<button type="button" class="pform-insert-variable-btn" data-variable-target="faq-answer">{ } Insertar variable</button>' +
                    '<button type="button" class="pform-insert-link-btn">🔗 Insertar enlace</button>' +
                    '</div>' +
                    '<textarea class="pform-input bulk-faq-answer" rows="2" placeholder="Respuesta"></textarea>' +
                    '</div>' +
                    '<button type="button" class="pform-spec-del" title="Eliminar">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                    '</svg>' +
                    '</button>';

                row.querySelector('.bulk-faq-question').value = question || '';
                row.querySelector('.bulk-faq-answer').value = answer || '';

                row.querySelector('.pform-spec-del').addEventListener('click', () => {
                    row.remove();
                    if (!faqList.children.length) {
                        faqList.style.display = 'none';
                        faqEmpty.style.display = 'block';
                    }
                });

                faqList.appendChild(row);
            }

            faqAddBtn.addEventListener('click', () => faqAddRow());

            // ── Popover de Tags (chips + autocompletado de tags ya usados
            // en otros productos) — mismo widget de Crear/Editar producto,
            // adaptado al patrón de popover reutilizable de esta pantalla. ──
            const tagsModal = document.getElementById('bulkTagsModal');
            const tagList = document.getElementById('bulkTagList');
            const tagInput = document.getElementById('bulkTagInput');
            const tagSuggestionsList = document.getElementById('bulkTagSuggestions');
            const tagAddBtn = document.getElementById('bulkTagAdd');
            const tagsCancelBtn = document.getElementById('bulkTagsCancelBtn');
            const tagsSaveBtn = document.getElementById('bulkTagsSaveBtn');
            const tagSuggestionsUrl = '{{ route('admin.products.tags.suggestions') }}';
            let tagsEditingTrigger = null;
            let tagSuggestionsTimer = null;
            let tagSuggestionActiveIndex = -1;

            function addTagChip(val) {
                const chip = document.createElement('span');
                chip.className = 'pform-tag-chip';
                chip.textContent = val;
                chip.title = 'Clic para eliminar';
                chip.addEventListener('click', function() {
                    this.remove();
                });
                tagList.appendChild(chip);
            }

            function currentTagValuesLower() {
                return Array.from(tagList.querySelectorAll('.pform-tag-chip'))
                    .map(chip => chip.textContent.toLowerCase());
            }

            function addTag() {
                const val = tagInput.value.trim();
                if (!val) return;
                if (!currentTagValuesLower().includes(val.toLowerCase())) addTagChip(val);
                tagInput.value = '';
                tagInput.focus();
                closeTagSuggestions();
            }

            tagAddBtn.addEventListener('click', addTag);

            function closeTagSuggestions() {
                tagSuggestionsList.classList.remove('is-open');
                tagSuggestionsList.innerHTML = '';
                tagSuggestionActiveIndex = -1;
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
                        closeTagSuggestions();
                    });
                });
            }

            async function fetchTagSuggestions(term) {
                try {
                    const res = await fetch(`${tagSuggestionsUrl}?q=${encodeURIComponent(term)}`, {
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
                        closeTagSuggestions();
                    } else {
                        addTag();
                    }
                }
            });

            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('.prod-bulk-popover-trigger');
                if (!trigger) return;

                if (trigger.dataset.field === 'tags') {
                    tagsEditingTrigger = trigger;
                    tagList.innerHTML = '';
                    closeTagSuggestions();

                    let tagValues = [];
                    try {
                        tagValues = JSON.parse(trigger.dataset.tags || '[]');
                    } catch (err) {
                        tagValues = [];
                    }
                    tagValues.forEach(t => addTagChip(t));

                    tagsModal.classList.add('active');
                    return;
                }

                if (trigger.dataset.field === 'faqs') {
                    faqEditingTrigger = trigger;
                    faqList.innerHTML = '';

                    let items = [];
                    try {
                        items = JSON.parse(trigger.dataset.faqs || '[]');
                    } catch (err) {
                        items = [];
                    }

                    if (items.length) {
                        faqEmpty.style.display = 'none';
                        faqList.style.display = 'flex';
                        items.forEach(it => faqAddRow(it.question, it.answer));
                    } else {
                        faqList.style.display = 'none';
                        faqEmpty.style.display = 'block';
                    }

                    faqModal.classList.add('active');
                    return;
                }

                specsEditingTrigger = trigger;
                specsList.innerHTML = '';

                let rows = [];
                try {
                    rows = JSON.parse(trigger.dataset.specs || '[]');
                } catch (err) {
                    rows = [];
                }

                if (rows.length) {
                    specsEmpty.style.display = 'none';
                    specsList.style.display = 'flex';
                    rows.forEach(r => specsAddRow(r.key, r.value));
                } else {
                    specsList.style.display = 'none';
                    specsEmpty.style.display = 'block';
                }

                specsModal.classList.add('active');
            });

            specsCancelBtn.addEventListener('click', () => specsModal.classList.remove('active'));
            specsModal.addEventListener('click', (e) => {
                if (e.target === specsModal) specsModal.classList.remove('active');
            });

            specsSaveBtn.addEventListener('click', () => {
                if (!specsEditingTrigger) return;

                const rows = Array.from(specsList.querySelectorAll('.pform-spec-row')).map(row => ({
                    key: row.querySelector('.bulk-spec-key').value.trim(),
                    value: row.querySelector('.bulk-spec-value').value.trim(),
                })).filter(r => r.key !== '');

                const json = JSON.stringify(rows);
                specsEditingTrigger.dataset.specs = json;
                specsEditingTrigger.textContent = `Especificaciones (${rows.length})`;
                setPendingChange(specsEditingTrigger.dataset.id, 'specifications', json, specsEditingTrigger);
                specsModal.classList.remove('active');
            });

            faqCancelBtn.addEventListener('click', () => faqModal.classList.remove('active'));
            faqModal.addEventListener('click', (e) => {
                if (e.target === faqModal) faqModal.classList.remove('active');
            });

            faqSaveBtn.addEventListener('click', () => {
                if (!faqEditingTrigger) return;

                const items = Array.from(faqList.querySelectorAll('.pform-faq-row')).map(row => ({
                    question: row.querySelector('.bulk-faq-question').value.trim(),
                    answer: row.querySelector('.bulk-faq-answer').value.trim(),
                })).filter(it => it.question !== '' && it.answer !== '');

                const json = JSON.stringify(items);
                faqEditingTrigger.dataset.faqs = json;
                faqEditingTrigger.textContent = `FAQ (${items.length})`;
                setPendingChange(faqEditingTrigger.dataset.id, 'faqs', json, faqEditingTrigger);
                faqModal.classList.remove('active');
            });

            tagsCancelBtn.addEventListener('click', () => tagsModal.classList.remove('active'));
            tagsModal.addEventListener('click', (e) => {
                if (e.target === tagsModal) tagsModal.classList.remove('active');
            });

            tagsSaveBtn.addEventListener('click', () => {
                if (!tagsEditingTrigger) return;

                const tags = Array.from(tagList.querySelectorAll('.pform-tag-chip')).map(chip => chip.textContent);

                const json = JSON.stringify(tags);
                tagsEditingTrigger.dataset.tags = json;
                tagsEditingTrigger.textContent = `Tags (${tags.length})`;
                setPendingChange(tagsEditingTrigger.dataset.id, 'tags', json, tagsEditingTrigger);
                tagsModal.classList.remove('active');
            });

            // ── Categoría en cascada (Principal > Subcategoría > Hija) ──
            // Los 3 selects de una fila no tienen data-field propio: cada
            // cambio recalcula cuál id queda "más profundo" (igual que
            // create/edit) y ESE es el que se manda como category_id.
            function populateCatSelect(select, options, selectedId) {
                select.innerHTML = '<option value="">Seleccionar...</option>';
                options.forEach(opt => {
                    const el = document.createElement('option');
                    el.value = opt.id;
                    el.textContent = opt.name;
                    if (String(opt.id) === String(selectedId)) el.selected = true;
                    select.appendChild(el);
                });
                select.disabled = options.length === 0;
            }

            function catEffectiveValue(row) {
                const main = row.querySelector('.prod-bulk-cat-main');
                const sub = row.querySelector('.prod-bulk-cat-sub');
                const child = row.querySelector('.prod-bulk-cat-child');
                return child.value || sub.value || main.value || '';
            }

            document.addEventListener('change', (e) => {
                if (!e.target.matches('.prod-bulk-cat-main, .prod-bulk-cat-sub, .prod-bulk-cat-child')) return;

                const row = e.target.closest('tr');
                const main = row.querySelector('.prod-bulk-cat-main');
                const sub = row.querySelector('.prod-bulk-cat-sub');
                const child = row.querySelector('.prod-bulk-cat-child');

                if (e.target === main) {
                    populateCatSelect(sub, subcategories[main.value] || [], '');
                    populateCatSelect(child, [], '');
                } else if (e.target === sub) {
                    const subs = subcategories[main.value] || [];
                    const subObj = subs.find(s => String(s.id) === String(sub.value));
                    populateCatSelect(child, subObj?.children || [], '');
                }

                setPendingChange(main.dataset.id, 'category_id', catEffectiveValue(row), main);
            });

            syncUnsavedBar();

            // ── Backfill masivo: asignar N productos seleccionados a 1 proveedor ──
            const selectAllCb   = document.getElementById('prodBulkSelectAll');
            const rowCheckboxes = () => Array.from(document.querySelectorAll('.prod-bulk-row-select'));
            const assignBar     = document.getElementById('prodSupplierAssignBar');
            const assignCount   = document.getElementById('prodSupplierAssignCount');
            const assignSelect  = document.getElementById('prodSupplierAssignSelect');
            const assignBtn     = document.getElementById('prodSupplierAssignBtn');
            const assignUrl     = '{{ route("admin.products.bulk-edit.assign-supplier") }}';

            function syncAssignBar() {
                const selected = rowCheckboxes().filter(cb => cb.checked);
                assignCount.textContent = selected.length;
                assignBar.classList.toggle('active', selected.length > 0);
            }

            if (selectAllCb) {
                selectAllCb.addEventListener('change', function () {
                    rowCheckboxes().forEach(cb => { cb.checked = selectAllCb.checked; });
                    syncAssignBar();
                });
            }

            document.addEventListener('change', (e) => {
                if (!e.target.matches('.prod-bulk-row-select')) return;
                if (!e.target.checked) selectAllCb.checked = false;
                syncAssignBar();
            });

            if (assignBtn) {
                assignBtn.addEventListener('click', async () => {
                    const supplierId = assignSelect.value;
                    const productIds = rowCheckboxes().filter(cb => cb.checked).map(cb => cb.value);

                    if (!supplierId) {
                        showToast('Selecciona un proveedor.', 'error');
                        return;
                    }
                    if (!productIds.length) {
                        showToast('Selecciona al menos un producto.', 'error');
                        return;
                    }

                    try {
                        const response = await fetch(assignUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ supplier_id: supplierId, product_ids: productIds }),
                        });
                        const data = await response.json();
                        if (response.ok && data.success) {
                            showToast(data.message || 'Asignado correctamente.');
                            rowCheckboxes().forEach(cb => { cb.checked = false; });
                            if (selectAllCb) selectAllCb.checked = false;
                            syncAssignBar();
                        } else {
                            showToast(data.message || 'No se pudo asignar.', 'error');
                        }
                    } catch (err) {
                        showToast('Error de red al asignar.', 'error');
                    }
                });
            }
        })();
    </script>
@endpush
