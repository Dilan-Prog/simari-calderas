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

            // ── Ancho de columnas ajustable (persistido en localStorage) ──
            const WIDTHS_STORAGE_KEY = 'admin_supplier_product_bulk_edit_column_widths';
            const PINNED_COLS = [];
            const MIN_COL_WIDTH = 50;
            const MAX_COL_WIDTH = 800;
            const DEFAULT_COL_WIDTHS = {
                product: 220, supplier: 160, sku: 140, cost: 110, lead_time_days: 130, is_primary: 100,
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

            syncBar();
        })();
    </script>
@endpush
