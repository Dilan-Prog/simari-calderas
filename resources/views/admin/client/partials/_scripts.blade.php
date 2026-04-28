@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const openBtn = document.querySelector('.button-primary.size-adjustment.clients');
            const modal = document.getElementById('clientCreateModal');
            const closeBtn = document.getElementById('closeClientModal');
            const cancelBtn = document.getElementById('cancelClientModal');

            const closeCreateWithAnim = () => {
                const content = modal.querySelector('.user-manager-modal-content');
                if (content) {
                    content.style.transition = 'transform 0.2s ease-in';
                    content.style.transform = 'translateX(100%)';
                }
                modal.style.transition = 'opacity 0.2s ease-in';
                modal.style.opacity = '0';
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.style.opacity = '';
                    modal.style.transition = '';
                    if (content) {
                        content.style.transform = '';
                        content.style.transition = '';
                    }
                }, 200);
            };

            openBtn.addEventListener('click', () => modal.style.display = 'flex');
            [closeBtn, cancelBtn].forEach(btn => btn.addEventListener('click', () => closeCreateWithAnim()));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeCreateWithAnim();
            });

            const sameAsFiscal = document.getElementById('sameAsFiscal');
            const shippingAddr = document.getElementById('shippingAddress');
            const fiscalAddr = document.querySelector('[name="address_line1"]');

            sameAsFiscal.addEventListener('change', () => {
                shippingAddr.disabled = sameAsFiscal.checked;
                if (sameAsFiscal.checked) shippingAddr.value = fiscalAddr.value;
            });
            fiscalAddr.addEventListener('input', () => {
                if (sameAsFiscal.checked) shippingAddr.value = fiscalAddr.value;
            });
        });

        // Show toast
        const showClientToast = (message, type = 'success') => {
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
            }, 7000);
        };

        // Update table row
        const updateClientTableRow = (customer) => {
            const editBtn = document.querySelector(`.btn-edit-client[data-id="${customer.id}"]`);
            if (!editBtn) return;
            const row = editBtn.closest('tr');

            row.querySelector('.clients-manager-name-client').textContent = [customer.first_name, customer.last_name]
                .filter(Boolean).join(' ');

            const addr = customer.customer_addresses?.[0] ?? null;
            row.querySelector('.clients-manager-ubication-client').textContent =
                addr ? `${addr.city}, ${addr.state}` : '—';

            const companyEl = row.querySelector('.breadcrumb-clients-manager');
            if (companyEl) companyEl.textContent = customer.company ?? '—';

            const statusBadge = row.querySelector('.users-manager-badge');
            if (statusBadge) {
                const labels = {
                    active: 'Activo',
                    inactive: 'Inactivo',
                    suspended: 'Suspendido'
                };
                statusBadge.textContent = labels[customer.status] ?? customer.status;
                statusBadge.className = 'users-manager-badge ' +
                    (customer.status === 'active' ? 'status' : 'status-inactive');
                statusBadge.dataset.status = customer.status; // Keep data-status in sync
            }

            const deleteBtn = row.querySelector('.btn-delete-client');
            if (deleteBtn) {
                deleteBtn.dataset.name = [customer.first_name, customer.last_name].filter(Boolean).join(' ');
                deleteBtn.dataset.email = customer.email;
                deleteBtn.dataset.initial = customer.first_name.charAt(0).toUpperCase();
            }
        };

        // Edit modal
        const editClientModal = document.getElementById('clientEditModal');
        const closeEditClientBtn = document.getElementById('closeClientEditModal');
        const cancelEditClientBtn = document.getElementById('cancelClientEditModal');
        const editClientForm = document.getElementById('clientEditForm');
        const editClientUrl = '{{ url('/admin/clientes/editar-cliente') }}';
        let currentClientId = null;

        const closeEditWithAnim = () => {
            const content = editClientModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            editClientModal.style.transition = 'opacity 0.2s ease-in';
            editClientModal.style.opacity = '0';
            setTimeout(() => {
                editClientModal.style.display = 'none';
                editClientModal.style.opacity = '';
                editClientModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        const editSameAsFiscal = document.getElementById('editSameAsFiscal');
        const editShippingAddr = document.getElementById('editShippingAddress');
        const editFiscalAddr = document.getElementById('editFiscalAddress');

        editSameAsFiscal.addEventListener('change', () => {
            editShippingAddr.disabled = editSameAsFiscal.checked;
            if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
        });
        editFiscalAddr.addEventListener('input', () => {
            if (editSameAsFiscal.checked) editShippingAddr.value = editFiscalAddr.value;
        });

        document.querySelectorAll('.btn-edit-client').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                currentClientId = id;

                fetch(editClientUrl + '/' + id, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(customer => {
                        const addr = customer.customer_addresses?.[0] ?? null;
                        const addr2 = customer.customer_addresses?.[1] ?? null;

                        // Personal info
                        editClientForm.querySelector('[name="full_name"]').value = [customer.first_name,
                            customer.last_name
                        ].filter(Boolean).join(' ');
                        editClientForm.querySelector('[name="company"]').value = customer.company ?? '';
                        editClientForm.querySelector('[name="email"]').value = customer.email ?? '';
                        editClientForm.querySelector('[name="phone"]').value = customer.phone ?? '';
                        editClientForm.querySelector('[name="rfc"]').value = customer.rfc ?? '';
                        editClientForm.querySelector('[name="document_type"]').value = customer
                            .document_type ?? '';
                        editClientForm.querySelector('[name="document_numer"]').value = customer
                            .document_numer ?? '';
                        editClientForm.querySelector('[name="birth_date"]').value = customer
                            .birth_date ?? '';
                        editClientForm.querySelector('[name="source"]').value = customer.source ?? '';
                        editClientForm.querySelector('[name="status"]').value = customer.status ??
                            'active';
                        editClientForm.querySelector('[name="notes"]').value = customer.notes ?? '';

                        // Fiscal address
                        editClientForm.querySelector('[name="address_line1"]').value = addr
                            ?.address_line1 ?? '';
                        editClientForm.querySelector('[name="city"]').value = addr?.city ?? '';
                        editClientForm.querySelector('[name="state"]').value = addr?.state ?? '';
                        editClientForm.querySelector('[name="postal_code"]').value = addr
                            ?.postal_code ?? '';
                        editClientForm.querySelector('[name="country"]').value = addr?.country ?? '';
                        editClientForm.querySelector('[name="reference"]').value = addr?.reference ??
                        '';

                        // Same as fiscal detection
                        if (customer.customer_addresses?.length === 1) {
                            // Only one address — same as fiscal
                            editSameAsFiscal.checked = true;
                            editShippingAddr.disabled = true;
                            editShippingAddr.value = addr?.address_line1 ?? '';
                            editClientForm.querySelector('[name="address_line2"]').value = addr
                                ?.address_line1 ?? '';
                        } else if (customer.customer_addresses?.length >= 2) {
                            // Two addresses — different
                            editSameAsFiscal.checked = false;
                            editShippingAddr.disabled = false;
                            editClientForm.querySelector('[name="address_line2"]').value = addr2
                                ?.address_line1 ?? '';
                            editShippingAddr.value = addr2?.address_line1 ?? '';
                        } else {
                            // No addresses
                            editSameAsFiscal.checked = false;
                            editShippingAddr.disabled = false;
                            editClientForm.querySelector('[name="address_line2"]').value = '';
                        }

                        editClientModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading client:', err));
            });
        });

        closeEditClientBtn.addEventListener('click', () => closeEditWithAnim());
        cancelEditClientBtn.addEventListener('click', () => closeEditWithAnim());
        editClientModal.addEventListener('click', (e) => {
            if (e.target === editClientModal) closeEditWithAnim();
        });

        // Submit edit
        editClientForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const errorsContainer = document.getElementById('client-edit-errors');
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';

            const formData = new FormData(editClientForm);
            formData.append('_method', 'PUT');

            try {
                const response = await fetch(editClientUrl + '/' + currentClientId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok) {
                    closeEditWithAnim();
                    updateClientTableRow(data.customer);
                    showClientToast('Cliente actualizado correctamente.');
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    errorsContainer.innerHTML = errorList.map(msg => `<p>${msg}</p>`).join('');
                    errorsContainer.style.display = 'block';
                }
            } catch (err) {
                console.error('Error updating client:', err);
            }
        });

        // Delete modal
        const deleteClientModal = document.getElementById('deleteClientModal');
        const deleteClientForm = document.getElementById('deleteClientForm');
        const delClientCancelBtn = document.getElementById('delClientConfirmCancel');
        const deleteClientUrl = '{{ url('/admin/clientes/eliminar-cliente') }}';

        document.querySelectorAll('.btn-delete-client').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('delClientConfirmName').textContent = btn.dataset.name;
                document.getElementById('delClientConfirmEmail').textContent = btn.dataset.email;
                document.getElementById('delClientConfirmAvatar').textContent = btn.dataset.initial;
                deleteClientForm.action = deleteClientUrl + '/' + btn.dataset.id;
                deleteClientModal.classList.add('active');
            });
        });

        delClientCancelBtn.addEventListener('click', () => deleteClientModal.classList.remove('active'));
        deleteClientModal.addEventListener('click', (e) => {
            if (e.target === deleteClientModal) deleteClientModal.classList.remove('active');
        });

        // Search and filter
        const clientSearch = document.getElementById('clientSearch');
        const clientStatusFilter = document.getElementById('clientStatusFilter');

        const filterClients = () => {
            const search = clientSearch.value.toLowerCase().trim();
            const status = clientStatusFilter.value;

            document.querySelectorAll('.clients-manager-table tbody tr').forEach(row => {
                const name = row.querySelector('.clients-manager-name-client')?.textContent.toLowerCase() ?? '';
                const company = row.querySelector('.breadcrumb-clients-manager')?.textContent.toLowerCase() ??
                    '';
                const email = row.querySelectorAll('.breadcrumb-clients-manager')[1]?.textContent
                .toLowerCase() ?? '';
                const rfc = row.querySelector('.client-rfc')?.textContent.toLowerCase() ?? '';
                const badge = row.querySelector('.users-manager-badge');

                const matchSearch = !search ||
                    name.includes(search) ||
                    company.includes(search) ||
                    email.includes(search) ||
                    rfc.includes(search);

                const badgeStatus = badge?.dataset.status ?? '';
                const matchStatus = status === 'all' || badgeStatus === status;

                row.style.display = matchSearch && matchStatus ? '' : 'none';
            });
        };

        clientSearch.addEventListener('input', filterClients);
        clientStatusFilter.addEventListener('change', filterClients);
    </script>
@endpush
