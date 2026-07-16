@push('scripts')
    <script>
        (function() {
            const TOTAL = {{ $products->count() }};
            const searchInput = document.getElementById('prodSearch');
            const statusFilter = document.getElementById('prodStatusFilter');
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');
            const listView = document.getElementById('prodListView');
            const gridView = document.getElementById('prodGridView');
            const emptyRow = document.getElementById('prodEmptyRow');
            const countLabel = document.getElementById('prodCountLabel');
            const tableRows = document.querySelectorAll('#prodTableBody tr[data-name]');
            const gridCards = document.querySelectorAll('#prodGridView .prod-grid-card');

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();
                const status = statusFilter.value;
                let visible = 0;

                tableRows.forEach((row, i) => {
                    const card = gridCards[i];
                    const nameMatch = row.dataset.name.includes(query) || row.dataset.sku.includes(query);
                    const stMatch = status === 'all' || row.dataset.status === status;
                    const show = nameMatch && stMatch;

                    row.style.display = show ? '' : 'none';
                    if (card) card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                emptyRow.style.display = visible === 0 ? '' : 'none';
                countLabel.textContent = `Mostrando ${visible} de ${TOTAL} productos`;
            }

            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);

            function setView(view) {
                if (view === 'grid') {
                    listView.style.display = 'none';
                    gridView.classList.add('active');
                    btnGrid.classList.add('active');
                    btnList.classList.remove('active');
                } else {
                    listView.style.display = '';
                    gridView.classList.remove('active');
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

            function resetImportModal() {
                importSelectedFile = null;
                importFileInput.value = '';
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

                const formData = new FormData();
                formData.append('file', importSelectedFile);

                try {
                    const response = await fetch('{{ route('admin.products.import') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        importShowStatus('error', data.message ?? 'No se pudo importar el archivo.');
                        btnDoImport.disabled = false;
                        return;
                    }

                    const dupCount = data.skipped_duplicates.length;
                    const failCount = data.failures.length;
                    const imgFailCount = (data.image_download_failures ?? []).length;
                    let summary = `${data.created} producto(s) creados. ${dupCount} omitido(s) por SKU duplicado. ${failCount} fila(s) con error.`;
                    if (imgFailCount) {
                        summary += ` ${imgFailCount} imagen(es) no se pudieron descargar.`;
                    }
                    importShowStatus('success', summary);

                    let html = '';
                    if (dupCount) {
                        html += '<p><strong>Omitidos por SKU duplicado:</strong></p><ul>' +
                            data.skipped_duplicates.map(d => `<li>Fila ${d.row}: SKU ${escapeHtml(d.sku)}</li>`).join('') +
                            '</ul>';
                    }
                    if (failCount) {
                        html += '<p><strong>Filas con error:</strong></p><ul>' +
                            data.failures.map(f =>
                                `<li>Fila ${f.row} (${escapeHtml(f.campo)}): ${escapeHtml(f.errores.join(', '))}</li>`
                            ).join('') + '</ul>';
                    }
                    if (imgFailCount) {
                        html += '<p><strong>Productos creados pero sin la imagen (no se pudo descargar):</strong></p><ul>' +
                            data.image_download_failures.map(f =>
                                `<li>SKU ${escapeHtml(f.sku)}: ${escapeHtml(f.url)}</li>`
                            ).join('') + '</ul>';
                    }
                    if (html) {
                        importResultDetail.innerHTML = html;
                        importResultDetail.style.display = 'block';
                    }

                    if (data.created > 0) {
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
        })();
    </script>
@endpush
