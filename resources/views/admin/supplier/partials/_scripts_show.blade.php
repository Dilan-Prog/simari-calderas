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
    </script>
@endpush
