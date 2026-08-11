@push('scripts')
    <script>
        (function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Catálogo completo (id/name/sku) precargado por el controller —
            // evita un <select> de cientos de <option>, mismo patrón que
            // supplier/partials/_scripts_show.blade.php.
            const allProductsData = @json($products->map(fn ($p) => [
                'id' => $p->id,
                'label' => $p->name . ($p->sku ? ' (' . $p->sku . ')' : ''),
            ]));

            function makeProductPicker(searchInput, dropdown, hiddenInput, onPick) {
                function render(query) {
                    const q = query.trim().toLowerCase();
                    const matches = (q === ''
                        ? allProductsData
                        : allProductsData.filter(p => p.label.toLowerCase().includes(q))
                    ).slice(0, 50);

                    dropdown.innerHTML = matches.length
                        ? matches.map(p => `<div class="ap-product-dropdown-item" data-id="${p.id}" data-label="${p.label.replace(/"/g, '&quot;')}">${p.label}</div>`).join('')
                        : '<div class="ap-product-dropdown-empty">Sin resultados.</div>';
                    dropdown.classList.add('active');
                }

                searchInput.addEventListener('focus', () => render(searchInput.value));
                searchInput.addEventListener('input', () => {
                    hiddenInput.value = '';
                    render(searchInput.value);
                });
                dropdown.addEventListener('mousedown', (e) => {
                    const item = e.target.closest('.ap-product-dropdown-item[data-id]');
                    if (!item) return;
                    e.preventDefault();
                    hiddenInput.value = item.dataset.id;
                    searchInput.value = item.dataset.label;
                    dropdown.classList.remove('active');
                    if (onPick) onPick(item.dataset.id, item.dataset.label);
                });
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.ap-product-picker') && !e.target.closest('.inv-product-filter-wrap')) {
                        dropdown.classList.remove('active');
                    }
                });
            }

            // ── Filtro de producto en el listado ──
            const filterSearch = document.getElementById('invProductFilterSearch');
            const filterDropdown = document.getElementById('invProductFilterDropdown');
            const filterHidden = document.getElementById('invProductFilterId');
            if (filterSearch && filterDropdown && filterHidden) {
                makeProductPicker(filterSearch, filterDropdown, filterHidden, () => {
                    document.getElementById('invFilterForm').submit();
                });
                filterSearch.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' || e.key === 'Delete') filterHidden.value = '';
                });
            }

            // ── Modal: registrar movimiento ──
            const modal = document.getElementById('movementModal');
            const openBtn = document.getElementById('btnNewMovement');
            if (!modal || !openBtn) return;

            const closeBtn = document.getElementById('closeMovementModal');
            const cancelBtn = document.getElementById('cancelMovementModal');
            const saveBtn = document.getElementById('mvSave');
            const errorsBox = document.getElementById('movement-modal-errors');

            const typeSelect = document.getElementById('mvType');
            const directionWrap = document.getElementById('mvDirectionWrap');
            const directionSelect = document.getElementById('mvDirection');
            const warehouseSelect = document.getElementById('mvWarehouseId');
            const productSearch = document.getElementById('mvProductSearch');
            const productDropdown = document.getElementById('mvProductDropdown');
            const productIdInput = document.getElementById('mvProductId');
            const quantityInput = document.getElementById('mvQuantity');
            const notesInput = document.getElementById('mvNotes');

            makeProductPicker(productSearch, productDropdown, productIdInput);

            typeSelect.addEventListener('change', () => {
                directionWrap.style.display = typeSelect.value === 'ajuste' ? 'block' : 'none';
            });

            function resetForm() {
                warehouseSelect.selectedIndex = 0;
                typeSelect.value = 'entrada';
                directionWrap.style.display = 'none';
                directionSelect.value = 'increase';
                productSearch.value = '';
                productIdInput.value = '';
                quantityInput.value = '';
                notesInput.value = '';
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';
            }

            function openModal() {
                resetForm();
                modal.classList.add('active');
            }

            function closeModal() {
                modal.classList.remove('active');
            }

            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            saveBtn.addEventListener('click', async () => {
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';

                const errors = [];
                if (!productIdInput.value) errors.push('Selecciona un producto de la lista.');
                if (!quantityInput.value || Number(quantityInput.value) < 1) errors.push('La cantidad debe ser mayor a cero.');

                if (errors.length) {
                    errorsBox.innerHTML = errors.map(m => `<p>${m}</p>`).join('');
                    errorsBox.style.display = 'block';
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.textContent = 'Guardando...';

                try {
                    const response = await fetch('{{ route('admin.inventory.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            warehouse_id: warehouseSelect.value,
                            product_id: productIdInput.value,
                            movement_type: typeSelect.value,
                            quantity: quantityInput.value,
                            adjustment_direction: typeSelect.value === 'ajuste' ? directionSelect.value : null,
                            notes: notesInput.value || null,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const messages = data.errors ? Object.values(data.errors).flat() : [data.message ?? 'No se pudo registrar el movimiento.'];
                        errorsBox.innerHTML = messages.map(m => `<p>${m}</p>`).join('');
                        errorsBox.style.display = 'block';
                        return;
                    }

                    closeModal();
                    window.location.reload();
                } catch (err) {
                    console.error('Error registering movement:', err);
                    errorsBox.innerHTML = '<p>Error de conexión. Intenta de nuevo.</p>';
                    errorsBox.style.display = 'block';
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Registrar';
                }
            });
        })();
    </script>
@endpush
