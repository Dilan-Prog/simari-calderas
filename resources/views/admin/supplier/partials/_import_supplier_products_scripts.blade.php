@push('scripts')
    <script>
        (function() {
            const modal = document.getElementById('importSupplierProductsModal');
            const openBtn = document.getElementById('btnOpenSupplierProductImportModal');
            const fileInput = document.getElementById('spImportFileInput');
            const dropzone = document.getElementById('spImportDropzone');
            const dropzoneText = document.getElementById('spImportDropzoneText');
            const dropzoneHint = document.getElementById('spImportDropzoneHint');
            const status = document.getElementById('spImportStatus');
            const resultDetail = document.getElementById('spImportResultDetail');
            const cancelBtn = document.getElementById('spImportModalCancel');
            const doImportBtn = document.getElementById('btnDoSupplierProductImport');
            const scopedSupplierId = {{ isset($scopedSupplier) ? (int) $scopedSupplier->id : 'null' }};
            let selectedFile = null;

            if (!modal || !openBtn) return;

            function showToast(message, type = 'success') {
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

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function resetModal() {
                selectedFile = null;
                fileInput.value = '';
                dropzone.classList.remove('has-file');
                dropzoneText.innerHTML = '<strong>Haz clic para seleccionar un archivo</strong>';
                dropzoneHint.textContent = '.xlsx, .xls o .csv';
                status.style.display = 'none';
                resultDetail.style.display = 'none';
                resultDetail.innerHTML = '';
                doImportBtn.disabled = true;
            }

            function setFile(file) {
                if (!file) return;
                selectedFile = file;
                dropzone.classList.add('has-file');
                dropzoneText.innerHTML = `<strong>${escapeHtml(file.name)}</strong>`;
                dropzoneHint.textContent = 'Haz clic para cambiar el archivo';
                doImportBtn.disabled = false;
            }

            openBtn.addEventListener('click', () => {
                resetModal();
                modal.classList.add('active');
            });
            cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('active');
            });

            dropzone.addEventListener('click', () => fileInput.click());
            dropzone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
            });
            fileInput.addEventListener('change', function() {
                if (this.files.length) setFile(this.files[0]);
            });

            ['dragover', 'dragenter'].forEach(evt => {
                dropzone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'dragend'].forEach(evt => {
                dropzone.addEventListener(evt, () => dropzone.classList.remove('is-dragover'));
            });
            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    setFile(e.dataTransfer.files[0]);
                }
            });

            function showStatus(type, message) {
                status.style.display = 'block';
                status.className = `prod-import-status prod-import-status--${type}`;
                status.textContent = message;
            }

            doImportBtn.addEventListener('click', async () => {
                if (!selectedFile) return;
                doImportBtn.disabled = true;
                resultDetail.style.display = 'none';
                resultDetail.innerHTML = '';
                showStatus('info', 'Procesando archivo, esto puede tardar unos segundos...');

                const formData = new FormData();
                formData.append('file', selectedFile);
                if (scopedSupplierId) formData.append('supplier_id', scopedSupplierId);

                try {
                    const response = await fetch('{{ route('admin.suppliers.products.import') }}', {
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
                        showStatus('error', msg);
                        doImportBtn.disabled = false;
                        return;
                    }

                    const failCount = data.failures.length;
                    let summary = `${data.created} vínculo(s) creados. ${data.updated} actualizado(s). ${failCount} fila(s) con error.`;
                    showStatus('success', summary);

                    if (failCount) {
                        resultDetail.innerHTML = '<p><strong>Filas con error:</strong></p><ul>' +
                            data.failures.map(f =>
                                `<li>Fila ${f.row} (${escapeHtml(f.campo)}): ${escapeHtml(f.errores.join(', '))}</li>`
                            ).join('') + '</ul>';
                        resultDetail.style.display = 'block';
                    }

                    if (data.created > 0 || data.updated > 0) {
                        showToast('Importación completada.');
                        setTimeout(() => window.location.reload(), 2500);
                    } else {
                        doImportBtn.disabled = false;
                    }
                } catch (err) {
                    console.error('Error importing supplier-products:', err);
                    showStatus('error', 'Error de conexión. Intenta de nuevo.');
                    doImportBtn.disabled = false;
                }
            });
        })();
    </script>
@endpush
