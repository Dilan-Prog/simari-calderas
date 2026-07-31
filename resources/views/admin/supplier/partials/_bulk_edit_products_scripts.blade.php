@push('scripts')
    <script>
        (function() {
            const filterForm    = document.getElementById('spBulkEditFilterForm');
            const searchInput   = document.getElementById('spBulkEditSearch');
            const supplierFilter = document.getElementById('spBulkEditSupplierFilter');
            const perPageSelect = document.getElementById('spBulkEditPerPage');
            const filterControls = [searchInput, supplierFilter, perPageSelect];

            const changes = new Map();
            const bar   = document.getElementById('spBulkEditBar');
            const count = document.getElementById('spBulkEditCount');
            const saveBtn = document.getElementById('spBulkEditSaveBtn');
            const discardBtn = document.getElementById('spBulkEditDiscardBtn');
            const saveUrl = '{{ route('admin.suppliers.products.bulk-edit.save') }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function cellKey(id, field) {
                return `${id}:${field}`;
            }

            function readValue(input) {
                if (input.type === 'checkbox') return input.checked;
                return input.value;
            }

            function syncBar() {
                count.textContent = changes.size;
                bar.classList.toggle('active', changes.size > 0);
                filterControls.forEach(el => el.disabled = changes.size > 0);
                saveBtn.disabled = changes.size === 0;
            }

            [supplierFilter, perPageSelect].forEach(select => {
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

            window.addEventListener('beforeunload', (e) => {
                if (changes.size > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            function onCellChange(input) {
                const id = input.dataset.id;
                const field = input.dataset.field;
                input.classList.add('dirty');
                input.classList.remove('has-error', 'saved-ok');
                input.title = '';
                changes.set(cellKey(id, field), { id: Number(id), field, value: readValue(input) });
                syncBar();
            }

            document.addEventListener('input', (e) => {
                if (!e.target.matches('.prod-bulk-input') || e.target.tagName === 'SELECT') return;
                onCellChange(e.target);
            });
            document.addEventListener('change', (e) => {
                if (!e.target.matches('.prod-bulk-input')) return;
                if (e.target.tagName === 'SELECT' || e.target.type === 'checkbox') {
                    onCellChange(e.target);
                }
            });

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
                        const input = document.querySelector(
                            `.prod-bulk-input[data-id="${r.id}"][data-field="${r.field}"]`
                        );
                        if (!input) return;

                        if (r.ok) {
                            okCount++;
                            input.classList.remove('dirty', 'has-error');
                            input.classList.add('saved-ok');
                            setTimeout(() => input.classList.remove('saved-ok'), 2000);
                            changes.delete(cellKey(r.id, r.field));
                        } else {
                            errCount++;
                            input.classList.add('has-error');
                            input.title = r.error ?? 'No se pudo guardar.';
                        }
                    });

                    showToast(
                        `${okCount} cambio(s) guardados${errCount ? `, ${errCount} fallaron — revisa las celdas en rojo` : ''}.`,
                        errCount ? 'error' : 'success'
                    );

                    saveBtn.textContent = 'Guardar cambios';
                    syncBar();
                } catch (err) {
                    console.error('Error saving supplier-product bulk changes:', err);
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

            syncBar();
        })();
    </script>
@endpush
