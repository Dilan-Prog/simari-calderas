@push('scripts')
    <script>
        // Tab switching
        document.querySelectorAll('.tab-show-client').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-show-client').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content-show-client').forEach(c => c.classList.remove(
                    'active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });

        // Edit supplier button
        const supplierEditModal = document.getElementById('supplierEditModal');
        const closeSupplierEditBtn = document.getElementById('closeSupplierEditModal');
        const cancelSupplierEditBtn = document.getElementById('cancelSupplierEditModal');
        const supplierEditForm = document.getElementById('supplierEditForm');
        const supplierEditUrl = '{{ url('/admin/proveedores/editar-proveedor') }}';
        let currentSupplierId = '{{ $supplier->id }}';

        const closeEditSupplierWithAnim = () => {
            const content = supplierEditModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            supplierEditModal.style.transition = 'opacity 0.2s ease-in';
            supplierEditModal.style.opacity = '0';
            setTimeout(() => {
                supplierEditModal.style.display = 'none';
                supplierEditModal.style.opacity = '';
                supplierEditModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        document.querySelector('.btn-edit-supplier-show').addEventListener('click', () => {
            fetch(supplierEditUrl + '/' + currentSupplierId, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                })
                .then(res => res.json())
                .then(supplier => {
                    supplierEditForm.querySelector('[name="company_name"]').value = supplier.company_name ?? '';
                    supplierEditForm.querySelector('[name="rfc"]').value = supplier.rfc ?? '';
                    supplierEditForm.querySelector('[name="contact_name"]').value = supplier.contact_name ?? '';
                    supplierEditForm.querySelector('[name="phone"]').value = supplier.phone ?? '';
                    supplierEditForm.querySelector('[name="email"]').value = supplier.email ?? '';
                    supplierEditForm.querySelector('[name="website"]').value = supplier.website ?? '';
                    supplierEditForm.querySelector('[name="address"]').value = supplier.address ?? '';
                    supplierEditForm.querySelector('[name="payment_terms"]').value = supplier.payment_terms ??
                        '';
                    supplierEditForm.querySelector('[name="status"]').value = supplier.status ?? 'active';
                    supplierEditForm.querySelector('[name="rating_quality"]').value = supplier.rating_quality ??
                        0;
                    supplierEditForm.querySelector('[name="rating_compliance"]').value = supplier
                        .rating_compliance ?? 0;
                    supplierEditForm.querySelector('[name="notes"]').value = supplier.notes ?? '';
                    supplierEditModal.style.display = 'flex';
                })
                .catch(err => console.error('Error:', err));
        });

        closeSupplierEditBtn.addEventListener('click', () => closeEditSupplierWithAnim());
        cancelSupplierEditBtn.addEventListener('click', () => closeEditSupplierWithAnim());
        supplierEditModal.addEventListener('click', (e) => {
            if (e.target === supplierEditModal) closeEditSupplierWithAnim();
        });

        supplierEditForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorsContainer = document.getElementById('supplier-edit-errors');
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';

            const formData = new FormData(supplierEditForm);
            formData.append('_method', 'PUT');

            try {
                const response = await fetch(supplierEditUrl + '/' + currentSupplierId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                const data = await response.json();
                if (response.ok) {
                    closeEditSupplierWithAnim();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    errorsContainer.innerHTML = errorList.map(msg => `<p>${msg}</p>`).join('');
                    errorsContainer.style.display = 'block';
                }
            } catch (err) {
                console.error('Error updating supplier:', err);
            }
        });

        // ── Pestaña Productos: asignar / editar / eliminar vínculo ──
        (function () {
            const modal      = document.getElementById('assignProductModal');
            const modalTitle = document.getElementById('assignProductModalTitle');
            const addBtn     = document.getElementById('btnAssignProduct');
            const tbody      = document.getElementById('supplierProductsTbody');
            if (!modal || !addBtn || !tbody) return;

            const closeBtn   = document.getElementById('closeAssignProductModal');
            const cancelBtn  = document.getElementById('cancelAssignProductModal');
            const saveBtn    = document.getElementById('apSave');
            const errorsBox  = document.getElementById('assign-product-errors');

            const pivotIdInput   = document.getElementById('apPivotId');
            const productSelect  = document.getElementById('apProductSelect');
            const skuInput       = document.getElementById('apSku');
            const costInput      = document.getElementById('apCost');
            const leadTimeInput  = document.getElementById('apLeadTime');
            const isPrimaryInput = document.getElementById('apIsPrimary');

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const storeUrl  = '{{ route("admin.supplier-products.store") }}';

            function updateUrl(id) {
                return storeUrl + '/' + id;
            }

            function openModal(mode, row) {
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';
                pivotIdInput.value = '';
                productSelect.value = '';
                productSelect.disabled = false;
                skuInput.value = '';
                costInput.value = '';
                leadTimeInput.value = '';
                isPrimaryInput.checked = false;

                if (mode === 'edit' && row) {
                    modalTitle.textContent = 'Editar Producto';
                    pivotIdInput.value = row.dataset.pivotId;
                    productSelect.value = row.dataset.productId;
                    productSelect.disabled = true; // no se permite cambiar de producto/proveedor al editar
                    skuInput.value = row.dataset.sku !== 'null' ? row.dataset.sku : '';
                    costInput.value = row.dataset.cost !== 'null' ? row.dataset.cost : '';
                    leadTimeInput.value = row.dataset.leadTime !== 'null' ? row.dataset.leadTime : '';
                    isPrimaryInput.checked = row.dataset.isPrimary === '1';
                } else {
                    modalTitle.textContent = 'Asignar Producto';
                }

                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            addBtn.addEventListener('click', () => openModal('add'));
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

            tbody.addEventListener('click', function (e) {
                const row = e.target.closest('tr[data-pivot-id]');
                if (!row) return;

                if (e.target.closest('.btn-edit-supplier-product')) {
                    openModal('edit', row);
                }

                if (e.target.closest('.btn-delete-supplier-product')) {
                    if (!confirm('¿Quitar este producto del proveedor?')) return;
                    fetch(updateUrl(row.dataset.pivotId), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'No se pudo eliminar.');
                            }
                        });
                }
            });

            saveBtn.addEventListener('click', function () {
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';

                if (!productSelect.value) {
                    errorsBox.innerHTML = '<p>Selecciona un producto.</p>';
                    errorsBox.style.display = 'block';
                    return;
                }

                const payload = {
                    supplier_id: currentSupplierId,
                    product_id: productSelect.value,
                    sku: skuInput.value || null,
                    cost: costInput.value || null,
                    lead_time_days: leadTimeInput.value || null,
                    is_primary: isPrimaryInput.checked,
                };

                const isEdit = !!pivotIdInput.value;
                const url = isEdit ? updateUrl(pivotIdInput.value) : storeUrl;
                const method = isEdit ? 'PUT' : 'POST';

                fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                    .then(async (res) => {
                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            errorsBox.innerHTML = `<p>${data.message || 'No se pudo guardar.'}</p>`;
                            errorsBox.style.display = 'block';
                            return;
                        }
                        window.location.reload();
                    })
                    .catch(() => {
                        errorsBox.innerHTML = '<p>Error de red al guardar.</p>';
                        errorsBox.style.display = 'block';
                    });
            });
        })();
    </script>
@endpush
