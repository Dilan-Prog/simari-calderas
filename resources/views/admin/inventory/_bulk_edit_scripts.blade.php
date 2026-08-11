@push('scripts')
    <script>
        (function() {
            const filterForm    = document.getElementById('bulkEditFilterForm');
            const searchInput   = document.getElementById('bulkEditSearch');
            const warehouseFilter = document.getElementById('bulkEditWarehouseFilter');
            const perPageSelect = document.getElementById('bulkEditPerPage');
            const filterControls = [searchInput, warehouseFilter, perPageSelect];

            [warehouseFilter, perPageSelect].forEach(select => {
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
            const saveUrl = '{{ route('admin.inventory.bulk.save') }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function cellKey(id, field) {
                return `${id}:${field}`;
            }

            function readValue(input) {
                return input.value;
            }

            function syncUnsavedBar() {
                bulkEditCount.textContent = changes.size;
                bulkEditBar.classList.toggle('active', changes.size > 0);
                filterControls.forEach(el => el.disabled = changes.size > 0);
                saveBtn.disabled = changes.size === 0;
            }

            window.addEventListener('beforeunload', (e) => {
                if (changes.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            document.addEventListener('input', (e) => {
                if (!e.target.matches('.prod-bulk-input')) return;
                onCellChange(e.target);
            });

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
                        body: JSON.stringify({
                            warehouse_id: warehouseFilter.value,
                            changes: Array.from(changes.values()),
                        }),
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

            discardBtn.addEventListener('click', () => {
                if (!changes.size) return;
                if (confirm('¿Descartar todos los cambios sin guardar?')) {
                    window.location.reload();
                }
            });

            // ── Menú de columnas visibles (persistido en localStorage) ──
            const COLUMNS_STORAGE_KEY = 'admin_inventory_bulk_edit_visible_columns';
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
                if (!raw) return;
                try {
                    const visible = JSON.parse(raw);
                    colToggles.forEach(cb => { cb.checked = visible.includes(cb.value); });
                } catch (e) {
                    // localStorage corrupto: se ignora y se mantienen los defaults del servidor.
                }
            }

            function saveColumnsPreference() {
                const visible = Array.from(colToggles).filter(cb => cb.checked).map(cb => cb.value);
                try {
                    localStorage.setItem(COLUMNS_STORAGE_KEY, JSON.stringify(visible));
                } catch (e) {
                    // Almacenamiento lleno/bloqueado: no es fatal.
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

            // ── Ancho de columnas ajustable ──
            const WIDTHS_STORAGE_KEY = 'admin_inventory_bulk_edit_column_widths';
            const PINNED_COLS = ['name'];
            const MIN_COL_WIDTH = 50;
            const MAX_COL_WIDTH = 800;
            const DEFAULT_COL_WIDTHS = {
                name: 220, sku: 140, quantity: 120, category: 160, brand: 160,
            };

            function getCol(key) {
                return document.querySelector(`colgroup col[data-col="${key}"]`);
            }

            function clampWidth(px) {
                return Math.min(MAX_COL_WIDTH, Math.max(MIN_COL_WIDTH, px));
            }

            function getColWidth(key) {
                const col = getCol(key);
                const raw = col ? parseInt(col.style.width, 10) : NaN;
                return Number.isFinite(raw) ? raw : (DEFAULT_COL_WIDTHS[key] ?? 140);
            }

            function setColWidth(key, px) {
                const col = getCol(key);
                if (col) col.style.width = clampWidth(px) + 'px';
            }

            function recomputePinnedOffsets() {
                let left = 0;
                PINNED_COLS.forEach(key => {
                    document.querySelectorAll(`[data-col="${key}"].prod-bulk-pinned-col`).forEach(el => {
                        el.style.left = left + 'px';
                    });
                    left += getColWidth(key);
                });
            }

            function applyStoredWidths() {
                let stored = {};
                try {
                    stored = JSON.parse(localStorage.getItem(WIDTHS_STORAGE_KEY) || '{}');
                } catch (e) {
                    stored = {};
                }
                document.querySelectorAll('colgroup col[data-col]').forEach(col => {
                    const key = col.dataset.col;
                    setColWidth(key, stored[key] ?? DEFAULT_COL_WIDTHS[key] ?? 140);
                });
            }

            function currentColumnWidths() {
                const widths = {};
                document.querySelectorAll('colgroup col[data-col]').forEach(col => {
                    widths[col.dataset.col] = parseInt(col.style.width, 10) || DEFAULT_COL_WIDTHS[col.dataset.col] || 140;
                });
                return widths;
            }

            function saveWidthsPreference() {
                try {
                    localStorage.setItem(WIDTHS_STORAGE_KEY, JSON.stringify(currentColumnWidths()));
                } catch (e) {
                    // Almacenamiento lleno/bloqueado: no es fatal, solo no persiste.
                }
            }

            function applyWidthsMap(widths) {
                document.querySelectorAll('colgroup col[data-col]').forEach(col => {
                    const key = col.dataset.col;
                    setColWidth(key, (widths && widths[key]) ?? DEFAULT_COL_WIDTHS[key] ?? 140);
                });
                recomputePinnedOffsets();
                saveWidthsPreference();
            }

            applyStoredWidths();
            recomputePinnedOffsets();

            let resizing = null;
            document.querySelectorAll('.prod-bulk-resize-handle').forEach(handle => {
                handle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const key = handle.dataset.resizeCol;
                    resizing = { key, startX: e.clientX, startWidth: getColWidth(key) };
                    handle.classList.add('resizing');
                });
            });
            document.addEventListener('mousemove', (e) => {
                if (!resizing) return;
                const newWidth = resizing.startWidth + (e.clientX - resizing.startX);
                setColWidth(resizing.key, newWidth);
                if (PINNED_COLS.includes(resizing.key)) recomputePinnedOffsets();
            });
            document.addEventListener('mouseup', () => {
                if (!resizing) return;
                document.querySelectorAll('.prod-bulk-resize-handle.resizing').forEach(h => h.classList.remove('resizing'));
                resizing = null;
                saveWidthsPreference();
            });

            // ── Vistas guardadas de columnas (hasta 10, por usuario) ──
            const VIEWS_MAX = 10;
            const viewSelect = document.getElementById('bulkEditViewSelect');
            const viewNameInput = document.getElementById('bulkEditViewNameInput');
            const viewSaveBtn = document.getElementById('bulkEditViewSaveBtn');
            const viewManageRow = document.getElementById('bulkEditViewManageRow');
            const viewUpdateBtn = document.getElementById('bulkEditViewUpdateBtn');
            const viewDeleteBtn = document.getElementById('bulkEditViewDeleteBtn');
            const bulkEditViewsUrl = '{{ url('/admin/inventario/edicion-masiva/vistas') }}';

            function updateViewManageVisibility() {
                viewManageRow.style.display = viewSelect.value ? 'flex' : 'none';
            }

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
                let widths = {};
                try {
                    widths = JSON.parse(opt.dataset.widths || '{}');
                } catch (e) {
                    widths = {};
                }
                applyWidthsMap(widths);
                updateViewManageVisibility();
            });

            function currentCheckedColumns() {
                return Array.from(colToggles).filter(cb => cb.checked).map(cb => cb.value);
            }

            viewSaveBtn.addEventListener('click', async () => {
                const name = viewNameInput.value.trim();
                if (!name) {
                    showToast('Ponle un nombre a la vista antes de guardarla.', 'error');
                    return;
                }
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
                        body: JSON.stringify({ name, columns: currentCheckedColumns(), widths: currentColumnWidths() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudo guardar la vista.', 'error');
                        return;
                    }

                    const opt = document.createElement('option');
                    opt.value = data.view.id;
                    opt.dataset.columns = JSON.stringify(data.view.columns);
                    opt.dataset.widths = JSON.stringify(data.view.widths);
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
                        body: JSON.stringify({ name, columns: currentCheckedColumns(), widths: currentColumnWidths() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showToast(data.message ?? 'No se pudo actualizar la vista.', 'error');
                        return;
                    }

                    viewSelect.options[viewSelect.selectedIndex].dataset.columns = JSON.stringify(data.view.columns);
                    viewSelect.options[viewSelect.selectedIndex].dataset.widths = JSON.stringify(data.view.widths);
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

            syncUnsavedBar();
        })();
    </script>
@endpush
