@push('scripts')
    <script>
        (function() {
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');
            const listView = document.getElementById('prodListView');
            const gridView = document.getElementById('prodGridView');

            // FIX: search/status/stock/category/per_page are now filtered and
            // paginated server-side (see ProductController::index) instead of
            // hiding already-loaded rows with JS — with hundreds of products,
            // loading everything on every page view was the actual "carga
            // pesada" being asked to fix, not just how the table looked.
            const filterForm     = document.getElementById('prodFilterForm');
            const searchInput    = document.getElementById('prodSearch');
            const categoryFilter = document.getElementById('prodCategoryFilter');
            const stockFilter    = document.getElementById('prodStockFilter');
            const statusFilter   = document.getElementById('prodStatusFilter');
            const perPageSelect  = document.getElementById('prodPerPage');

            [categoryFilter, stockFilter, statusFilter, perPageSelect].forEach(select => {
                select.addEventListener('change', () => filterForm.submit());
            });

            // Debounce free-text search so it doesn't reload on every keystroke.
            let searchDebounce = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(() => filterForm.submit(), 600);
            });

            const gridSelectAllBar = document.getElementById('prodGridSelectAllBar');

            function setView(view) {
                if (view === 'grid') {
                    listView.style.display = 'none';
                    gridView.classList.add('active');
                    gridSelectAllBar.classList.add('active');
                    btnGrid.classList.add('active');
                    btnList.classList.remove('active');
                } else {
                    listView.style.display = '';
                    gridView.classList.remove('active');
                    gridSelectAllBar.classList.remove('active');
                    btnList.classList.add('active');
                    btnGrid.classList.remove('active');
                }
            }

            btnGrid.addEventListener('click', () => setView('grid'));
            btnList.addEventListener('click', () => setView('list'));

            // FIX BUG 2 (Paso B): self-contained toast — this module has no
            // shared toast helper/CSS class (unlike Users/Clientes), so it's
            // built here with inline styles to stay within this module's
            // isolated files.
            function showProductToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.cssText = `
                    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
                    background: ${type === 'error' ? '#fef2f2' : '#f0fdf4'};
                    color: ${type === 'error' ? '#991b1b' : '#166534'};
                    border: 1px solid ${type === 'error' ? '#fecaca' : '#bbf7d0'};
                    padding: 14px 18px; border-radius: 10px; font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-width: 360px;
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }

            // FIX (Fase F finding): create() and update() redirect back here
            // with session('success') / session('error'), but nothing ever
            // rendered it — the user got zero feedback after creating or
            // editing a product. Reuse showProductToast() for it.
            @if (session('success'))
                showProductToast(@json(session('success')));
            @endif
            @if (session('error'))
                showProductToast(@json(session('error')), 'error');
            @endif

            // Delete modal
            const deleteProductModal = document.getElementById('deleteProductModal');
            const deleteProductForm = document.getElementById('deleteProductForm');
            const delProductCancelBtn = document.getElementById('delProductConfirmCancel');
            const deleteProductUrl = '{{ url('/admin/productos/eliminar') }}';
            let deleteProductId = null;

            document.querySelectorAll('.btn-delete-product').forEach(btn => {
                btn.addEventListener('click', () => {
                    deleteProductId = btn.dataset.id;
                    document.getElementById('delProductConfirmName').textContent = btn.dataset.name;
                    document.getElementById('delProductConfirmSku').textContent = btn.dataset.sku;
                    document.getElementById('delProductConfirmAvatar').textContent =
                        btn.dataset.name.charAt(0).toUpperCase();
                    deleteProductForm.action = deleteProductUrl + '/' + btn.dataset.id;
                    deleteProductModal.classList.add('active');
                });
            });

            // FIX BUG 2 (Paso B): intercept the submit and use fetch so a
            // 422 (product still referenced elsewhere) can be shown as a
            // toast instead of the browser navigating to a raw JSON page.
            // The form itself still submits _method=DELETE via FormData
            // (correct pattern, unchanged) — this only wraps it in fetch.
            deleteProductForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                try {
                    const response = await fetch(deleteProductForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: new FormData(deleteProductForm),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        deleteProductModal.classList.remove('active');
                        const row = document.querySelector(`.btn-delete-product[data-id="${deleteProductId}"]`)
                            ?.closest('tr');
                        if (row) row.remove();
                        const card = document.querySelector(`.btn-delete-product[data-id="${deleteProductId}"]`)
                            ?.closest('.prod-grid-card');
                        if (card) card.remove();
                        showProductToast(data.message ?? 'Producto eliminado correctamente.');
                        // FIX QA: removing the row/card left "Mostrando X de Y
                        // productos" and "Total en inventario" stale at their
                        // pre-delete values (Y and the stock sum are only
                        // computed server-side on page load). A reload keeps
                        // both counters accurate without duplicating that
                        // arithmetic in JS.
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        deleteProductModal.classList.remove('active');
                        showProductToast(data.message ?? 'No se pudo eliminar el producto.', 'error');
                    }
                } catch (err) {
                    console.error('Error deleting product:', err);
                    deleteProductModal.classList.remove('active');
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                }
            });

            delProductCancelBtn.addEventListener('click', () =>
                deleteProductModal.classList.remove('active'));
            deleteProductModal.addEventListener('click', (e) => {
                if (e.target === deleteProductModal) deleteProductModal.classList.remove('active');
            });

            // Import products modal
            const importModal = document.getElementById('importProductsModal');
            const btnOpenImportModal = document.getElementById('btnOpenImportModal');
            const importFileInput = document.getElementById('importFileInput');
            const importDropzone = document.getElementById('importDropzone');
            const importDropzoneText = document.getElementById('importDropzoneText');
            const importDropzoneHint = document.getElementById('importDropzoneHint');
            const importStatus = document.getElementById('importStatus');
            const importResultDetail = document.getElementById('importResultDetail');
            const importModalCancel = document.getElementById('importModalCancel');
            const btnDoImport = document.getElementById('btnDoImport');
            let importSelectedFile = null;

            const importUrls = {
                create: '{{ route('admin.products.import') }}',
                update: '{{ route('admin.products.import.update') }}',
            };
            function getImportMode() {
                return document.querySelector('input[name="importMode"]:checked')?.value ?? 'create';
            }

            function resetImportModal() {
                importSelectedFile = null;
                importFileInput.value = '';
                document.getElementById('importModeCreate').checked = true;
                importDropzone.classList.remove('has-file');
                importDropzoneText.innerHTML = '<strong>Haz clic para seleccionar un archivo</strong>';
                importDropzoneHint.textContent = '.xlsx, .xls o .csv';
                importStatus.style.display = 'none';
                importResultDetail.style.display = 'none';
                importResultDetail.innerHTML = '';
                btnDoImport.disabled = true;
            }

            function setImportFile(file) {
                if (!file) return;
                importSelectedFile = file;
                importDropzone.classList.add('has-file');
                importDropzoneText.innerHTML = `<strong>${escapeHtml(file.name)}</strong>`;
                importDropzoneHint.textContent = 'Haz clic para cambiar el archivo';
                btnDoImport.disabled = false;
            }

            btnOpenImportModal.addEventListener('click', () => {
                resetImportModal();
                importModal.classList.add('active');
            });
            importModalCancel.addEventListener('click', () => importModal.classList.remove('active'));
            importModal.addEventListener('click', (e) => {
                if (e.target === importModal) importModal.classList.remove('active');
            });

            importDropzone.addEventListener('click', () => importFileInput.click());
            importDropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    importFileInput.click();
                }
            });
            importFileInput.addEventListener('change', function() {
                if (this.files.length) setImportFile(this.files[0]);
            });

            ['dragover', 'dragenter'].forEach(evt => {
                importDropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    importDropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'dragend'].forEach(evt => {
                importDropzone.addEventListener(evt, () => importDropzone.classList.remove('is-dragover'));
            });
            importDropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                importDropzone.classList.remove('is-dragover');
                if (e.dataTransfer.files.length) {
                    importFileInput.files = e.dataTransfer.files;
                    setImportFile(e.dataTransfer.files[0]);
                }
            });

            function importShowStatus(type, message) {
                importStatus.style.display = 'block';
                importStatus.className = `prod-import-status prod-import-status--${type}`;
                importStatus.textContent = message;
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            btnDoImport.addEventListener('click', async () => {
                if (!importSelectedFile) return;
                btnDoImport.disabled = true;
                importResultDetail.style.display = 'none';
                importResultDetail.innerHTML = '';
                importShowStatus('info', 'Procesando archivo, esto puede tardar unos segundos...');

                const mode = getImportMode();
                const formData = new FormData();
                formData.append('file', importSelectedFile);

                try {
                    const response = await fetch(importUrls[mode], {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        let msg = data.message ?? 'No se pudo importar el archivo.';
                        if (data.debug) msg += ' — Detalle: ' + data.debug;
                        importShowStatus('error', msg);
                        btnDoImport.disabled = false;
                        return;
                    }

                    const failCount = data.failures.length;
                    const imgFailCount = (data.image_download_failures ?? []).length;
                    let summary, skippedList, skippedLabel, processedCount;

                    if (mode === 'update') {
                        processedCount = data.updated;
                        skippedList = data.skipped_not_found;
                        skippedLabel = 'SKU no encontrado';
                        summary = `${data.updated} producto(s) actualizados. ${skippedList.length} omitido(s) por SKU no encontrado. ${failCount} fila(s) con error.`;
                    } else {
                        processedCount = data.created;
                        skippedList = data.skipped_duplicates;
                        skippedLabel = 'SKU duplicado';
                        summary = `${data.created} producto(s) creados. ${skippedList.length} omitido(s) por SKU duplicado. ${failCount} fila(s) con error.`;
                    }
                    if (imgFailCount) {
                        summary += ` ${imgFailCount} imagen(es) no se pudieron descargar.`;
                    }
                    importShowStatus('success', summary);

                    let html = '';
                    if (skippedList.length) {
                        html += `<p><strong>Omitidos por ${skippedLabel}:</strong></p><ul>` +
                            skippedList.map(d => `<li>Fila ${d.row}: SKU ${escapeHtml(d.sku)}</li>`).join('') +
                            '</ul>';
                    }
                    if (failCount) {
                        html += '<p><strong>Filas con error:</strong></p><ul>' +
                            data.failures.map(f =>
                                `<li>Fila ${f.row} (${escapeHtml(f.campo)}): ${escapeHtml(f.errores.join(', '))}</li>`
                            ).join('') + '</ul>';
                    }
                    if (imgFailCount) {
                        html += '<p><strong>Productos sin la imagen nueva (no se pudo descargar):</strong></p><ul>' +
                            data.image_download_failures.map(f =>
                                `<li>SKU ${escapeHtml(f.sku)}: ${escapeHtml(f.url)}</li>`
                            ).join('') + '</ul>';
                    }
                    if (html) {
                        importResultDetail.innerHTML = html;
                        importResultDetail.style.display = 'block';
                    }

                    if (processedCount > 0) {
                        setTimeout(() => window.location.reload(), 2500);
                    } else {
                        btnDoImport.disabled = false;
                    }
                } catch (err) {
                    console.error('Error importing products:', err);
                    importShowStatus('error', 'Error de conexión. Intenta de nuevo.');
                    btnDoImport.disabled = false;
                }
            });

            // ── Fase A: selección + acciones masivas (estilo Shopify) ──
            const selectedIds = new Set();
            const bulkBar = document.getElementById('prodBulkBar');
            const bulkCount = document.getElementById('prodBulkCount');
            const selectAllList = document.getElementById('prodSelectAllList');
            const selectAllGrid = document.getElementById('prodSelectAllGrid');
            const bulkUrl = '{{ route('admin.products.bulk') }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function allRowCheckboxIds() {
                // Ambas vistas (lista y tarjetas) renderizan el mismo conjunto
                // paginado de productos, así que basta con leer los
                // data-id de una sola vista para tener el universo completo.
                return Array.from(document.querySelectorAll('#prodListView .prod-row-checkbox[data-id]'))
                    .map(cb => cb.dataset.id);
            }

            function syncCheckboxesUI() {
                // Selecciona/deselecciona el checkbox correspondiente en AMBAS
                // vistas (lista y tarjetas) para el mismo producto, así la
                // selección no se pierde visualmente al cambiar de vista.
                document.querySelectorAll('.prod-row-checkbox[data-id]').forEach(cb => {
                    cb.checked = selectedIds.has(cb.dataset.id);
                });

                const total = allRowCheckboxIds().length;
                const selectedOnPage = allRowCheckboxIds().filter(id => selectedIds.has(id)).length;
                [selectAllList, selectAllGrid].forEach(el => {
                    if (!el) return;
                    el.checked = total > 0 && selectedOnPage === total;
                    el.indeterminate = selectedOnPage > 0 && selectedOnPage < total;
                });
            }

            function syncBulkBar() {
                bulkCount.textContent = selectedIds.size;
                bulkBar.classList.toggle('active', selectedIds.size > 0);
                syncCheckboxesUI();
            }

            document.addEventListener('change', (e) => {
                if (!e.target.matches('.prod-row-checkbox[data-id]')) return;
                const id = e.target.dataset.id;
                if (e.target.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                syncBulkBar();
            });

            [selectAllList, selectAllGrid].forEach(el => {
                if (!el) return;
                el.addEventListener('change', () => {
                    if (el.checked) {
                        allRowCheckboxIds().forEach(id => selectedIds.add(id));
                    } else {
                        allRowCheckboxIds().forEach(id => selectedIds.delete(id));
                    }
                    syncBulkBar();
                });
            });

            document.getElementById('prodBulkCancelBtn').addEventListener('click', () => {
                selectedIds.clear();
                syncBulkBar();
            });

            const bulkActionLabels = {
                activate: 'activado(s)',
                deactivate: 'desactivado(s)',
                publish: 'publicado(s) en la web',
                unpublish: 'despublicado(s) de la web',
            };

            async function runBulkAction(action) {
                if (!selectedIds.size) return;

                try {
                    const response = await fetch(bulkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: Array.from(selectedIds), action }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showProductToast(data.message ?? 'No se pudo aplicar la acción.', 'error');
                        return;
                    }

                    showProductToast(`${data.updated} producto(s) ${bulkActionLabels[action]}.`);
                    selectedIds.clear();
                    setTimeout(() => window.location.reload(), 1200);
                } catch (err) {
                    console.error('Error running bulk action:', err);
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                }
            }

            document.querySelectorAll('#prodBulkBar .prod-bulk-btn[data-action]').forEach(btn => {
                btn.addEventListener('click', () => runBulkAction(btn.dataset.action));
            });

            // Bulk delete
            const bulkDeleteModal = document.getElementById('bulkDeleteProductModal');
            const bulkDeleteCount = document.getElementById('bulkDeleteCount');
            const bulkDeleteCancelBtn = document.getElementById('bulkDeleteCancelBtn');
            const bulkDeleteConfirmBtn = document.getElementById('bulkDeleteConfirmBtn');

            const bulkDeleteResult = document.getElementById('bulkDeleteResult');
            const bulkDeleteActions = document.getElementById('bulkDeleteActions');

            function resetBulkDeleteModal() {
                bulkDeleteResult.style.display = 'none';
                bulkDeleteResult.innerHTML = '';
                bulkDeleteActions.style.display = '';
                bulkDeleteConfirmBtn.disabled = false;
            }

            document.getElementById('prodBulkDeleteBtn').addEventListener('click', () => {
                if (!selectedIds.size) return;
                resetBulkDeleteModal();
                bulkDeleteCount.textContent = selectedIds.size;
                bulkDeleteModal.classList.add('active');
            });
            bulkDeleteCancelBtn.addEventListener('click', () => bulkDeleteModal.classList.remove('active'));
            bulkDeleteModal.addEventListener('click', (e) => {
                if (e.target === bulkDeleteModal) bulkDeleteModal.classList.remove('active');
            });

            bulkDeleteConfirmBtn.addEventListener('click', async () => {
                bulkDeleteConfirmBtn.disabled = true;

                try {
                    const response = await fetch(bulkUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ids: Array.from(selectedIds), action: 'delete' }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        bulkDeleteModal.classList.remove('active');
                        bulkDeleteConfirmBtn.disabled = false;
                        showProductToast(data.message ?? 'No se pudo eliminar el lote.', 'error');
                        return;
                    }

                    showProductToast(`${data.deleted.length} producto(s) eliminados.`);
                    selectedIds.clear();

                    if (data.blocked.length) {
                        // Se queda abierto mostrando el detalle de lo que no se
                        // pudo eliminar y por qué, en vez de recargar de
                        // inmediato — el usuario necesita leer esto.
                        bulkDeleteActions.style.display = 'none';
                        bulkDeleteResult.innerHTML =
                            `<p><strong>${data.deleted.length} eliminado(s). ${data.blocked.length} no se pudieron eliminar:</strong></p><ul>` +
                            data.blocked.map(b => `<li>${escapeHtml(b.name)}: ${escapeHtml(b.reasons.join(', '))}</li>`).join('') +
                            '</ul><button type="button" class="button-primary size-adjustment" id="bulkDeleteCloseBtn">Entendido</button>';
                        bulkDeleteResult.style.display = 'block';
                        document.getElementById('bulkDeleteCloseBtn').addEventListener('click', () => {
                            window.location.reload();
                        });
                    } else {
                        bulkDeleteModal.classList.remove('active');
                        setTimeout(() => window.location.reload(), 1200);
                    }
                } catch (err) {
                    console.error('Error running bulk delete:', err);
                    bulkDeleteModal.classList.remove('active');
                    bulkDeleteConfirmBtn.disabled = false;
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                }
            });

            // ── Columnas configurables + vistas guardadas (listado normal) ──
            // Mismo patrón que _bulk_edit_scripts.blade.php (columnsBtn/
            // applyColumnVisibility/loadStoredColumns/viewSelect/etc.), solo
            // con IDs/clases propios (prodIndex*/.prod-index-col-toggle) para
            // no colisionar con Edición Masiva, y un solo selector de
            // data-col que cubre tabla Y tarjetas a la vez.
            const INDEX_COLUMNS_STORAGE_KEY = 'admin_products_index_visible_columns';
            const indexColumnsBtn = document.getElementById('prodIndexColumnsBtn');
            const indexColumnsMenu = document.getElementById('prodIndexColumnsMenu');
            const indexColToggles = document.querySelectorAll('.prod-index-col-toggle');

            function applyIndexColumnVisibility() {
                indexColToggles.forEach(cb => {
                    document.querySelectorAll(`[data-col="${cb.value}"]`).forEach(el => {
                        el.classList.toggle('prod-col-hidden', !cb.checked);
                    });
                });
            }

            function loadStoredIndexColumns() {
                let raw;
                try {
                    raw = localStorage.getItem(INDEX_COLUMNS_STORAGE_KEY);
                } catch (e) {
                    return;
                }
                if (!raw) return; // nada guardado todavía: se respeta el default del servidor
                try {
                    const visible = JSON.parse(raw);
                    indexColToggles.forEach(cb => { cb.checked = visible.includes(cb.value); });
                } catch (e) {
                    // localStorage corrupto/no es JSON válido: se ignora y se
                    // mantienen los defaults renderizados por el servidor.
                }
            }

            function saveIndexColumnsPreference() {
                const visible = Array.from(indexColToggles).filter(cb => cb.checked).map(cb => cb.value);
                try {
                    localStorage.setItem(INDEX_COLUMNS_STORAGE_KEY, JSON.stringify(visible));
                } catch (e) {
                    // Almacenamiento lleno/bloqueado: la preferencia
                    // simplemente no persiste, no es un error fatal.
                }
            }

            indexColToggles.forEach(cb => cb.addEventListener('change', () => {
                applyIndexColumnVisibility();
                saveIndexColumnsPreference();
            }));

            indexColumnsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                indexColumnsMenu.classList.toggle('active');
            });
            document.addEventListener('click', (e) => {
                if (indexColumnsMenu.classList.contains('active') && !indexColumnsMenu.contains(e.target) && e.target !== indexColumnsBtn) {
                    indexColumnsMenu.classList.remove('active');
                }
            });

            loadStoredIndexColumns();
            applyIndexColumnVisibility();

            // ── Vistas guardadas de columnas (hasta 10, por usuario) ──
            const INDEX_VIEWS_MAX = 10;
            const indexViewSelect = document.getElementById('prodIndexViewSelect');
            const indexViewNameInput = document.getElementById('prodIndexViewNameInput');
            const indexViewSaveBtn = document.getElementById('prodIndexViewSaveBtn');
            const indexViewManageRow = document.getElementById('prodIndexViewManageRow');
            const indexViewUpdateBtn = document.getElementById('prodIndexViewUpdateBtn');
            const indexViewDeleteBtn = document.getElementById('prodIndexViewDeleteBtn');
            const prodIndexViewsUrl = '{{ url('/admin/productos/vistas') }}';

            function updateIndexViewManageVisibility() {
                indexViewManageRow.style.display = indexViewSelect.value ? 'flex' : 'none';
            }

            function applyIndexColumnsList(keys) {
                indexColToggles.forEach(cb => { cb.checked = keys.includes(cb.value); });
                applyIndexColumnVisibility();
                saveIndexColumnsPreference();
            }

            indexViewSelect.addEventListener('change', () => {
                const opt = indexViewSelect.options[indexViewSelect.selectedIndex];
                if (!opt.value) {
                    updateIndexViewManageVisibility();
                    return;
                }
                let keys = [];
                try {
                    keys = JSON.parse(opt.dataset.columns || '[]');
                } catch (e) {
                    keys = [];
                }
                applyIndexColumnsList(keys);
                updateIndexViewManageVisibility();
            });

            function currentCheckedIndexColumns() {
                return Array.from(indexColToggles).filter(cb => cb.checked).map(cb => cb.value);
            }

            indexViewSaveBtn.addEventListener('click', async () => {
                const name = indexViewNameInput.value.trim();
                if (!name) {
                    showProductToast('Ponle un nombre a la vista antes de guardarla.', 'error');
                    return;
                }
                // Solo UX — el límite real lo aplica el servidor.
                if (indexViewSelect.options.length - 1 >= INDEX_VIEWS_MAX) {
                    showProductToast(`Ya tienes el máximo de ${INDEX_VIEWS_MAX} vistas guardadas. Elimina una para poder guardar otra.`, 'error');
                    return;
                }

                indexViewSaveBtn.disabled = true;
                try {
                    const response = await fetch(prodIndexViewsUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, columns: currentCheckedIndexColumns() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showProductToast(data.message ?? 'No se pudo guardar la vista.', 'error');
                        return;
                    }

                    const opt = document.createElement('option');
                    opt.value = data.view.id;
                    opt.dataset.columns = JSON.stringify(data.view.columns);
                    opt.textContent = data.view.name;
                    indexViewSelect.appendChild(opt);
                    indexViewSelect.value = data.view.id;
                    indexViewNameInput.value = '';
                    updateIndexViewManageVisibility();
                    showProductToast(`Vista "${data.view.name}" guardada.`);
                } catch (err) {
                    console.error('Error saving index view:', err);
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    indexViewSaveBtn.disabled = false;
                }
            });

            indexViewUpdateBtn.addEventListener('click', async () => {
                const id = indexViewSelect.value;
                if (!id) return;
                const name = indexViewSelect.options[indexViewSelect.selectedIndex].textContent.trim();

                indexViewUpdateBtn.disabled = true;
                try {
                    const response = await fetch(prodIndexViewsUrl + '/' + id, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name, columns: currentCheckedIndexColumns() }),
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showProductToast(data.message ?? 'No se pudo actualizar la vista.', 'error');
                        return;
                    }

                    indexViewSelect.options[indexViewSelect.selectedIndex].dataset.columns = JSON.stringify(data.view.columns);
                    showProductToast(`Vista "${data.view.name}" actualizada.`);
                } catch (err) {
                    console.error('Error updating index view:', err);
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    indexViewUpdateBtn.disabled = false;
                }
            });

            indexViewDeleteBtn.addEventListener('click', async () => {
                const id = indexViewSelect.value;
                if (!id) return;
                const name = indexViewSelect.options[indexViewSelect.selectedIndex].textContent.trim();
                if (!confirm(`¿Eliminar la vista "${name}"?`)) return;

                indexViewDeleteBtn.disabled = true;
                try {
                    const response = await fetch(prodIndexViewsUrl + '/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        showProductToast(data.message ?? 'No se pudo eliminar la vista.', 'error');
                        return;
                    }

                    indexViewSelect.options[indexViewSelect.selectedIndex].remove();
                    indexViewSelect.value = '';
                    updateIndexViewManageVisibility();
                    showProductToast(`Vista "${name}" eliminada.`);
                } catch (err) {
                    console.error('Error deleting index view:', err);
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                } finally {
                    indexViewDeleteBtn.disabled = false;
                }
            });
        })();
    </script>
@endpush
