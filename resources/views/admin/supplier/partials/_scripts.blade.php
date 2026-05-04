@push('scripts')
    <script>
        // --- Create modal ---
        const supplierCreateModal = document.getElementById('supplierCreateModal');
        const closeSupplierCreateBtn = document.getElementById('closeSupplierCreateModal');
        const cancelSupplierCreateBtn = document.getElementById('cancelSupplierCreateModal');
        const openSupplierBtn = document.querySelector('.button-primary.size-adjustment.suppliers');

        const closeCreateSupplierWithAnim = () => {
            const content = supplierCreateModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            supplierCreateModal.style.transition = 'opacity 0.2s ease-in';
            supplierCreateModal.style.opacity = '0';
            setTimeout(() => {
                supplierCreateModal.style.display = 'none';
                supplierCreateModal.style.opacity = '';
                supplierCreateModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        openSupplierBtn.addEventListener('click', () => supplierCreateModal.style.display = 'flex');
        closeSupplierCreateBtn.addEventListener('click', () => closeCreateSupplierWithAnim());
        cancelSupplierCreateBtn.addEventListener('click', () => closeCreateSupplierWithAnim());
        supplierCreateModal.addEventListener('click', (e) => {
            if (e.target === supplierCreateModal) closeCreateSupplierWithAnim();
        });

        // --- Edit modal ---
        const supplierEditModal = document.getElementById('supplierEditModal');
        const closeSupplierEditBtn = document.getElementById('closeSupplierEditModal');
        const cancelSupplierEditBtn = document.getElementById('cancelSupplierEditModal');
        const supplierEditForm = document.getElementById('supplierEditForm');
        const supplierEditUrl = '{{ url('/admin/proveedores/editar-proveedor') }}';
        let currentSupplierId = null;

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

        document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                currentSupplierId = id;

                fetch(supplierEditUrl + '/' + id, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(supplier => {
                        supplierEditForm.querySelector('[name="company_name"]').value = supplier
                            .company_name ?? '';
                        supplierEditForm.querySelector('[name="rfc"]').value = supplier.rfc ?? '';
                        supplierEditForm.querySelector('[name="contact_name"]').value = supplier
                            .contact_name ?? '';
                        supplierEditForm.querySelector('[name="phone"]').value = supplier.phone ?? '';
                        supplierEditForm.querySelector('[name="email"]').value = supplier.email ?? '';
                        supplierEditForm.querySelector('[name="website"]').value = supplier.website ??
                            '';
                        supplierEditForm.querySelector('[name="address"]').value = supplier.address ??
                            '';
                        supplierEditForm.querySelector('[name="payment_terms"]').value = supplier
                            .payment_terms ?? '';
                        supplierEditForm.querySelector('[name="status"]').value = supplier.status ??
                            'active';
                        supplierEditForm.querySelector('[name="rating_quality"]').value = supplier
                            .rating_quality ?? 0;
                        supplierEditForm.querySelector('[name="rating_compliance"]').value = supplier
                            .rating_compliance ?? 0;
                        supplierEditForm.querySelector('[name="notes"]').value = supplier.notes ?? '';

                        supplierEditModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading supplier:', err));
            });
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
                    updateSupplierTableRow(data.supplier);
                    showSupplierToast('Proveedor actualizado correctamente.');
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    errorsContainer.innerHTML = errorList.map(msg => `<p>${msg}</p>`).join('');
                    errorsContainer.style.display = 'block';
                }
            } catch (err) {
                console.error('Error updating supplier:', err);
            }
        });

        // Update table row
        const updateSupplierTableRow = (supplier) => {
            const editBtn = document.querySelector(`.btn-edit-supplier[data-id="${supplier.id}"]`);
            if (!editBtn) return;
            const row = editBtn.closest('tr');

            row.querySelector('.supplier-company').textContent = supplier.company_name ?? '—';
            row.querySelector('.supplier-rfc').textContent = supplier.rfc ?? '—';
            row.querySelector('.supplier-contact').textContent = supplier.contact_name ?? '—';
            row.querySelector('.supplier-email').innerHTML =
                `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg> ${supplier.email ?? '—'}`;
            row.querySelector('.supplier-phone').innerHTML =
                `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> ${supplier.phone ?? '—'}`;

            const paymentBadge = row.querySelector('.supplier-payment');
            if (paymentBadge) {
                paymentBadge.textContent = supplier.payment_terms ?? '—';
                paymentBadge.dataset.payment = supplier.payment_terms ?? '';
            }

            // Update stars
            const stars = (rating) => [...Array(5)].map((_, i) => i < rating ? '★' : '☆').join('');
            const starEls = row.querySelectorAll('.supplier-stars');
            if (starEls[0]) starEls[0].textContent = stars(supplier.rating_quality ?? 0);
            if (starEls[1]) starEls[1].textContent = stars(supplier.rating_compliance ?? 0);

            const statusBadge = row.querySelector('.users-manager-badge');
            if (statusBadge) {
                const labels = {
                    active: 'Activo',
                    inactive: 'Inactivo',
                    suspended: 'Suspendido'
                };
                statusBadge.textContent = labels[supplier.status] ?? supplier.status;
                statusBadge.className = 'users-manager-badge ' + (supplier.status === 'active' ? 'status' :
                    'status-inactive');
                statusBadge.dataset.status = supplier.status;
            }

            const deleteBtn = row.querySelector('.btn-delete-supplier');
            if (deleteBtn) {
                deleteBtn.dataset.name = supplier.company_name;
                deleteBtn.dataset.email = supplier.email ?? '';
                deleteBtn.dataset.initial = supplier.company_name.charAt(0).toUpperCase();
            }
        };

        // Show toast
        const showSupplierToast = (message, type = 'success') => {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML =
                `
            <div class="toast-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                    fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <path d="m9 11 3 3L22 4"></path>
                </svg>
            </div>
            <div class="toast-body">
                <p class="toast-title">Acción realizada</p>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close"
                onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>`;
            document.querySelector('.container.user-manager').prepend(toast);
            setTimeout(() => {
                toast.style.animation = 'toastOut 0.3s ease forwards';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        };

        // --- Delete modal ---
        const deleteSupplierModal = document.getElementById('deleteSupplierModal');
        const deleteSupplierForm = document.getElementById('deleteSupplierForm');
        const delSupplierCancelBtn = document.getElementById('delSupplierConfirmCancel');
        const deleteSupplierUrl = '{{ url('/admin/proveedores/eliminar-proveedor') }}';

        document.querySelectorAll('.btn-delete-supplier').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delSupplierConfirmName').textContent = btn.dataset.name;
                document.getElementById('delSupplierConfirmEmail').textContent = btn.dataset.email;
                document.getElementById('delSupplierConfirmAvatar').textContent = btn.dataset.initial;
                deleteSupplierForm.action = deleteSupplierUrl + '/' + btn.dataset.id;
                deleteSupplierModal.classList.add('active');
            });
        });

        delSupplierCancelBtn.addEventListener('click', () => deleteSupplierModal.classList.remove('active'));
        deleteSupplierModal.addEventListener('click', (e) => {
            if (e.target === deleteSupplierModal) deleteSupplierModal.classList.remove('active');
        });

        // --- Search and filter ---
        const supplierSearch = document.getElementById('supplierSearch');
        const supplierStatusFilter = document.getElementById('supplierStatusFilter');
        const supplierPaymentFilter = document.getElementById('supplierPaymentFilter');

        const filterSuppliers = () => {
            const search = supplierSearch.value.toLowerCase().trim();
            const status = supplierStatusFilter.value;
            const payment = supplierPaymentFilter.value;
            const minRating = parseInt(document.getElementById('rating-label').innerText.match(/\d+/)?.[0] ?? '0');

            document.querySelectorAll('.supplier-row').forEach(row => {
                const company = row.querySelector('.supplier-company')?.textContent.toLowerCase() ?? '';
                const contact = row.querySelector('.supplier-contact')?.textContent.toLowerCase() ?? '';
                const email = row.querySelector('.supplier-email')?.textContent.toLowerCase() ?? '';
                const phone = row.querySelector('.supplier-phone')?.textContent.toLowerCase() ?? '';
                const badge = row.querySelector('.users-manager-badge');
                const payBadge = row.querySelector('.supplier-payment');
                const qualEl = row.querySelectorAll('.supplier-stars')[0];
                const qualRating = (qualEl?.textContent.match(/★/g) || []).length;

                const matchSearch = !search || company.includes(search) || contact.includes(search) ||
                    email.includes(search) || phone.includes(search);
                const matchStatus = status === 'all' || badge?.dataset.status === status;
                const matchPayment = payment === 'all' || payBadge?.dataset.payment === payment;
                const matchRating = qualRating >= minRating;

                row.style.display = matchSearch && matchStatus && matchPayment && matchRating ? '' : 'none';
            });
        };

        supplierSearch.addEventListener('input', filterSuppliers);
        supplierStatusFilter.addEventListener('change', filterSuppliers);
        supplierPaymentFilter.addEventListener('change', filterSuppliers);

        // --- Slider ---
        const slider = document.getElementById('slider-container');
        const handle = document.getElementById('slider-handle');
        const fill = document.getElementById('slider-fill');
        const label = document.getElementById('rating-label');
        let isDragging = false;

        function updateSlider(e) {
            const rect = slider.getBoundingClientRect();
            let clientX = e.clientX || (e.touches && e.touches[0].clientX);
            let x = clientX - rect.left;
            let rawPercentage = (x / rect.width) * 100;
            if (rawPercentage < 0) rawPercentage = 0;
            if (rawPercentage > 100) rawPercentage = 100;
            const step = 20;
            const value = Math.round(rawPercentage / step);
            const snappedPercentage = value * step;
            handle.style.left = `calc(${snappedPercentage}% - 9px)`;
            fill.style.width = `${snappedPercentage}%`;
            label.innerText = `Rating mínimo: ${value}/5`;
            filterSuppliers(); // Re-filter on slider change
        }

        handle.addEventListener('mousedown', () => isDragging = true);
        document.addEventListener('mouseup', () => isDragging = false);
        document.addEventListener('mousemove', (e) => {
            if (isDragging) updateSlider(e);
        });
        handle.addEventListener('touchstart', () => isDragging = true);
        document.addEventListener('touchend', () => isDragging = false);
        document.addEventListener('touchmove', (e) => {
            if (isDragging) updateSlider(e);
        });
        slider.addEventListener('click', (e) => {
            if (!isDragging) updateSlider(e);
        });
    </script>
@endpush
