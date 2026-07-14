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

            document.querySelectorAll('[name="document_type"]').forEach(select => {
                select.addEventListener('change', function() {
                    if (this.value && this.value !== 'seleccione') {
                        this.classList.remove('val-error-select');
                    }
                });
            });

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

            // ── BIND DYNAMIC VALIDATION RULES ──────────────────────

            // CREATE
            dynamicPhone('#clientCreateModal [name="phone"]');
            dynamicPhone('#clientCreateModal [name="whatsapp"]');
            dynamicRFC('#clientCreateModal [name="rfc"]');
            dynamicCP('#clientCreateModal [name="postal_code"]');
            dynamicLettersOnly('#clientCreateModal [name="full_name"]');
            dynamicNumericOnly('#clientCreateModal [name="social_security_number"]');
            dynamicAlphanumeric('#clientCreateModal [name="notes"]');
            dynamicAlphanumeric('#clientCreateModal [name="company"]');
            dynamicEmailLowercase('#clientCreateModal [name="email"]');

            // EDIT
            dynamicPhone('#clientEditForm [name="phone"]');
            dynamicPhone('#clientEditForm [name="whatsapp"]');
            dynamicRFC('#clientEditForm [name="rfc"]');
            dynamicCP('#clientEditForm [name="postal_code"]');
            dynamicLettersOnly('#clientEditForm [name="full_name"]');
            dynamicNumericOnly('#clientEditForm [name="social_security_number"]');
            dynamicAlphanumeric('#clientEditForm [name="notes"]');
            dynamicAlphanumeric('#clientEditForm [name="company"]');
            dynamicEmailLowercase('#clientEditForm [name="email"]');

            const createDocType = document.querySelector('#clientCreateModal [name="document_type"]');
            if (createDocType) {
                createDocType.addEventListener('change', () => {
                    valClear(createDocType.closest('form'), 'document_type');
                });
            }

            ['#clientCreateModal [name="rfc"]', '#clientEditForm [name="rfc"]'].forEach(sel => {
                const el = document.querySelector(sel);
                if (el) el.addEventListener('input', () => valClear(el.closest('form'), 'rfc'));
            });

            const editDocType = document.querySelector('#clientEditForm [name="document_type"]');
            const editSource = document.querySelector('#clientEditForm [name="source"]');
            if (editDocType) {
                editDocType.addEventListener('change', () => {
                    valClear(editClientForm, 'document_type');
                });
            }
            if (editSource) {
                editSource.addEventListener('change', () => {
                    valClear(editClientForm, 'source');
                });
            }

            ['#clientCreateModal [name="full_name"]', '#clientEditForm [name="full_name"]'].forEach(sel => {
                const el = document.querySelector(sel);
                if (el) el.addEventListener('input', () => valClear(el.closest('form'), 'full_name'));
            });

            // SUBMIT CREATE
            const createClientForm = modal.querySelector('form');
            if (createClientForm) {
                createClientForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const localErrors = validateFormFields(createClientForm);
                    if (localErrors.length > 0) {
                        return;
                    }
                    this.submit();
                });
            }
        });

        // ── TOAST ───────────────────────────────────────────────────
        const showClientToast = (message, type = 'success') => {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML =
                `
                <div class="toast-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                        fill="none" stroke="${type === 'error' ? '#dc2626' : '#16a34a'}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
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

        // ── UPDATE TABLE ROW ────────────────────────────────────────
        const updateClientTableRow = (customer) => {
            const editBtn = document.querySelector(`.btn-edit-client[data-id="${customer.id}"]`);
            if (!editBtn) return;
            const row = editBtn.closest('tr');

            // COMPANY 
            const companyCell = row.querySelector('td:first-child');
            if (companyCell) {
                const companyNameEl = companyCell.querySelector('.clients-manager-name-client');
                if (companyNameEl) companyNameEl.textContent = customer.company ?? '—';

                const locEl = companyCell.querySelector('.clients-manager-ubication-client');
                if (locEl) {
                    const addr = customer.customer_addresses?.[0] ?? null;
                    locEl.textContent = addr ? `${addr.city}, ${addr.state}` : '—';
                }
            }

            // CLIENT">
            const clientCell = row.querySelector('td:nth-child(2)');
            if (clientCell) {
                const nameEl = clientCell.querySelector('.breadcrumb-clients-manager');
                if (nameEl) nameEl.textContent = `${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim();
            }

            // CONTACT"
            const contactCell = row.querySelector('td:nth-child(3)');
            if (contactCell) {
                const contactPs = contactCell.querySelectorAll('.breadcrumb-clients-manager');
                // THE FIRST ONE HAS THE MAIL SVG + EMAIL
                if (contactPs[0]) {
                    const mailSvg = contactPs[0].querySelector('svg');
                    contactPs[0].textContent = '';
                    if (mailSvg) contactPs[0].appendChild(mailSvg);
                    contactPs[0].append(` ${customer.email ?? '—'}`);
                }
                // THE SECOND ONE HAS THE PHONE SVG + PHONE
                if (contactPs[1]) {
                    const phoneSvg = contactPs[1].querySelector('svg');
                    contactPs[1].textContent = '';
                    if (phoneSvg) contactPs[1].appendChild(phoneSvg);
                    contactPs[1].append(` ${customer.phone ?? '—'}`);
                }
            }

            // RFC — td:nth-child(4)
            const rfcEl = row.querySelector('.client-rfc');
            if (rfcEl) rfcEl.textContent = customer.rfc ?? '—';

            // STATUS — badge
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
                statusBadge.dataset.status = customer.status;
            }

            // DELETE BUTTON — UPDATE DATA ATTRIBUTES
            const deleteBtn = row.querySelector('.btn-delete-client');
            if (deleteBtn) {
                deleteBtn.dataset.name = `${customer.first_name ?? ''} ${customer.last_name ?? ''}`.trim();
                deleteBtn.dataset.email = customer.email ?? '';
                deleteBtn.dataset.initial = (customer.first_name ?? 'C').charAt(0).toUpperCase();
            }
        };

        // ── EDIT MODAL ──────────────────────────────────────────────
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

                        editClientForm.querySelector('[name="full_name"]').value = [customer.first_name,
                            customer.last_name
                        ].filter(Boolean).join(' ');
                        editClientForm.querySelector('[name="company"]').value = customer.company ?? '';
                        editClientForm.querySelector('[name="email"]').value = customer.email ?? '';
                        editClientForm.querySelector('[name="phone"]').value = customer.phone ?? '';
                        editClientForm.querySelector('[name="rfc"]').value = customer.rfc ?? '';
                        editClientForm.querySelector('[name="source"]').value = customer.source ?? '';
                        editClientForm.querySelector('[name="status"]').value = customer.status ??
                            'active';
                        editClientForm.querySelector('[name="notes"]').value = customer.notes ?? '';

                        // DOCUMENT_TYPE — FORCE THE VALUE EVEN IF IT IS NOT IN THE OPTIONS
                        const docTypeSelect = editClientForm.querySelector('[name="document_type"]');
                        if (docTypeSelect) {
                            docTypeSelect.value = customer.document_type ?? '';
                            if (docTypeSelect.value !== (customer.document_type ?? '')) {
                                // FIX QA-8 Part B: This branch used to run silently for
                                // every customer with document_type='cfdi', since that
                                // option didn't exist in this select (Part A fixed the
                                // missing option). Kept as a defensive fallback for any
                                // future/legacy value that still doesn't match, now with
                                // a warning so a mismatch is visible during debugging.
                                console.warn(
                                    `document_type "${customer.document_type}" no coincide con ninguna opción del select; se agregó dinámicamente.`
                                );
                                const opt = document.createElement('option');
                                opt.value = customer.document_type;
                                opt.textContent = customer.document_type;
                                docTypeSelect.appendChild(opt);
                                docTypeSelect.value = customer.document_type;
                            }
                        }

                        const source = editClientForm.querySelector('[name="source"]');
                        if (source) {
                            source.value = customer.source ?? '';
                            if (source.value !== (customer.source ?? '')) {
                                const opt = document.createElement('option');
                                opt.value = customer.source;
                                opt.textContent = customer.source;
                                source.appendChild(opt);
                                source.value = customer.source;
                            }
                        }

                        // FISCAL ADDRESS
                        editClientForm.querySelector('[name="address_line1"]').value = addr
                            ?.address_line1 ?? '';
                        editClientForm.querySelector('[name="city"]').value = addr?.city ?? '';
                        editClientForm.querySelector('[name="state"]').value = addr?.state ?? '';
                        editClientForm.querySelector('[name="postal_code"]').value = addr
                            ?.postal_code ?? '';
                        editClientForm.querySelector('[name="country"]').value = addr?.country ?? '';
                        editClientForm.querySelector('[name="reference"]').value = addr?.reference ??
                            '';

                        // SAME FISCAL ADDRESS
                        if (customer.customer_addresses?.length === 1) {
                            editSameAsFiscal.checked = true;
                            editShippingAddr.disabled = true;
                            editShippingAddr.value = addr?.address_line1 ?? '';
                            editClientForm.querySelector('[name="address_line2"]').value = addr
                                ?.address_line1 ?? '';
                        } else if (customer.customer_addresses?.length >= 2) {
                            editSameAsFiscal.checked = false;
                            editShippingAddr.disabled = false;
                            editClientForm.querySelector('[name="address_line2"]').value = addr2
                                ?.address_line1 ?? '';
                            editShippingAddr.value = addr2?.address_line1 ?? '';
                        } else {
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



        // SUBMIT EDIT
        editClientForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const errorsContainer = document.getElementById('client-edit-errors');
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';

            const localErrors = validateFormFields(editClientForm);
            if (localErrors.length > 0) {
                errorsContainer.innerHTML = localErrors.map(msg => `<p>${msg}</p>`).join('');
                errorsContainer.style.display = 'block';
                return;
            }

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
                } else {
                    errorsContainer.innerHTML =
                        `<p>${data.message ?? 'Ocurrió un error al guardar el cliente.'}</p>`;
                    errorsContainer.style.display = 'block';
                }
            } catch (err) {
                console.error('Error updating client:', err);
                errorsContainer.innerHTML = '<p>Error de conexión. Intenta de nuevo.</p>';
                errorsContainer.style.display = 'block';
            }
        });

        // ── DELETE MODAL ────────────────────────────────────────────
        const deleteClientModal = document.getElementById('deleteClientModal');
        const deleteClientForm = document.getElementById('deleteClientForm');
        const delClientCancelBtn = document.getElementById('delClientConfirmCancel');
        const deleteClientUrl = '{{ url('/admin/clientes/eliminar-cliente') }}';
        let deleteClientId = null;

        document.querySelectorAll('.btn-delete-client').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteClientId = btn.dataset.id;
                document.getElementById('delClientConfirmName').textContent = btn.dataset.name;
                document.getElementById('delClientConfirmEmail').textContent = btn.dataset.email;
                document.getElementById('delClientConfirmAvatar').textContent = btn.dataset.initial;
                deleteClientModal.classList.add('active');
            });
        });

        // REPLACE DELETE FORM SUBMIT WITH A BUTTON WITH ID
        document.getElementById('delClientConfirmBtn').addEventListener('click', async () => {
            try {
                const response = await fetch(deleteClientUrl + '/' + deleteClientId, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'DELETE'
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    deleteClientModal.classList.remove('active');
                    // REMOVE THE ROW FROM THE TABLE
                    const row = document.querySelector(`.btn-delete-client[data-id="${deleteClientId}"]`)
                        ?.closest('tr');
                    if (row) row.remove();
                    showClientToast('Cliente eliminado correctamente.');
                } else {
                    deleteClientModal.classList.remove('active');
                    showClientToast(data.message ?? 'No se pudo eliminar el cliente.', 'error');
                }
            } catch (err) {
                console.error('Error deleting client:', err);
                deleteClientModal.classList.remove('active');
                showClientToast('Error de conexión. Intenta de nuevo.', 'error');
            }
        });

        delClientCancelBtn.addEventListener('click', () => deleteClientModal.classList.remove('active'));
        deleteClientModal.addEventListener('click', (e) => {
            if (e.target === deleteClientModal) deleteClientModal.classList.remove('active');
        });

        // ── SEARCH AND FILTER ───────────────────────────────────────
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

        // ── CFDI READER ─────────────────────────────────────────────
        const cfdiReadBtn = document.getElementById('cfdiReadBtn');
        const cfdiFileInput = document.getElementById('cfdiFileInput');
        const cfdiFileName = document.getElementById('cfdiFileName');
        const cfdiStatus = document.getElementById('cfdiStatus');

        const cfdiFieldMap = {
            rfc: '[name="rfc"]',
            full_name: '[name="full_name"]',
            company: '[name="company"]',
            document_type: '[name="document_type"]',
            address_line1: '[name="address_line1"]',
            city: '[name="city"]',
            state: '[name="state"]',
            postal_code: '[name="postal_code"]',
            country: '[name="country"]',
            status: '[name="status"]',
        };

        function cfdiShowStatus(type, message) {
            cfdiStatus.style.display = 'block';
            cfdiStatus.className = `cfdi-status cfdi-status--${type}`;
            cfdiStatus.textContent = message;
        }

        if (cfdiReadBtn) {
            cfdiReadBtn.addEventListener('click', () => cfdiFileInput.click());

            cfdiFileInput.addEventListener('change', async function() {
                if (!this.files.length) return;

                const file = this.files[0];
                cfdiFileName.textContent = file.name;
                cfdiReadBtn.disabled = true;
                cfdiReadBtn.textContent = '⏳ READING...';
                cfdiShowStatus('info', 'PROCESSING THE PDF, WAIT A MOMENT...');

                const formData = new FormData();
                formData.append('cfdi_pdf', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                try {
                    const response = await fetch('{{ route('admin.clients.parse-cfdi') }}', {
                        method: 'POST',
                        body: formData,
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        cfdiShowStatus('error', data.error || 'ERROR PROCESSING THE PDF.');
                        return;
                    }

                    let filled = 0;
                    for (const [key, selector] of Object.entries(cfdiFieldMap)) {
                        if (!data[key]) continue;
                        const el = document.querySelector(selector);
                        if (!el) continue;
                        el.value = data[key];
                        filled++;
                    }
                    cfdiShowStatus('success', `${filled} FIELDS WERE AUTOMATICALLY FILLED.`);
                } catch (err) {
                    cfdiShowStatus('error', 'CONNECTION ERROR. TRY AGAIN.');
                } finally {
                    cfdiReadBtn.disabled = false;
                    cfdiReadBtn.textContent = '📄 SELECT CERTIFICATE (PDF)';
                    cfdiFileInput.value = '';
                }
            });
        }

        // ── REGEX ───────────────────────────────────────────────────
        const REGEX = {
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            phone: /^\d{10}$/,
            rfc: /^([A-ZÑ&]{3,4}) ?(\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])) ?([A-Z\d]{3})$/,
            cp: /^\d{5}$/,
        };

        // ── DYNAMIC FORMAT FUNCTIONS ───────────────────────────
        function dynamicPhone(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 10) v = v.slice(0, 10);
                e.target.value = v;
            });
        }

        function dynamicCP(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 5) v = v.slice(0, 5);
                e.target.value = v;
            });
        }

        function dynamicRFC(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', (e) => {
                let v = e.target.value.toUpperCase().replace(/[^A-Z0-9&Ñ]/g, '');
                if (v.length > 13) v = v.slice(0, 13);
                e.target.value = v;
            });
        }

        function dynamicLettersOnly(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            });
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.value = (this.value + text).replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            });
        }

        function dynamicNumericOnly(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.value = (this.value + text).replace(/[^0-9]/g, '');
            });
        }

        function dynamicAlphanumeric(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            });
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.value = (this.value + text).replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            });
        }

        function dynamicEmailLowercase(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function() {
                const pos = this.selectionStart;
                this.value = this.value.toLowerCase();
                this.setSelectionRange(pos, pos);
            });
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                this.value = (this.value + text).toLowerCase();
            });
        }


        function valClear(form, name) {
            const f = form.querySelector(`[name="${name}"]`);
            if (!f) return;
            f.classList.remove('val-error-input', 'val-error-select');
            form.querySelectorAll(`.val-error-msg[data-for="${name}"]`).forEach(el => el.remove());
        }

        function valMark(form, name, label) {
            const f = form.querySelector(`[name="${name}"]`);
            if (!f) return;
            f.classList.add(f.tagName === 'SELECT' ? 'val-error-select' : 'val-error-input');
            if (!form.querySelector(`.val-error-msg[data-for="${name}"]`)) {
                const msg = document.createElement('span');
                msg.className = 'val-error-msg';
                msg.dataset.for = name;
                msg.textContent = `El campo "${label}" es requerido`;
                f.insertAdjacentElement('afterend', msg);
            }
        }

        function valMarkFormat(form, name, customMessage) {
            const f = form.querySelector(`[name="${name}"]`);
            if (!f) return;
            f.classList.add('val-error-input');
            if (!form.querySelector(`.val-error-msg[data-for="${name}"]`)) {
                const msg = document.createElement('span');
                msg.className = 'val-error-msg';
                msg.dataset.for = name;
                msg.textContent = customMessage;
                f.insertAdjacentElement('afterend', msg);
            }
        }

        // SUBMIT VALIDATIONS
        const validateFormFields = (form) => {
            const errors = [];

            // FIX QA-7: Added 'company' to the clear list so its red outline
            // resets on re-submit like the other required fields.
            ['full_name', 'document_type', 'source', 'email', 'phone', 'company', 'rfc', 'postal_code'].forEach(name => {
                valClear(form, name);
            });

            const fullName = form.querySelector('[name="full_name"]')?.value.trim();
            const documentType = form.querySelector('[name="document_type"]')?.value.trim();
            const company = form.querySelector('[name="company"]')?.value.trim();
            const email = form.querySelector('[name="email"]')?.value.trim();
            const phone = form.querySelector('[name="phone"]')?.value.trim();
            const whatsapp = form.querySelector('[name="whatsapp"]')?.value.trim();
            const rfc = form.querySelector('[name="rfc"]')?.value.trim();
            const cp = form.querySelector('[name="postal_code"]')?.value.trim();
            const source = form.querySelector('[name="source"]')?.value.trim();

            if (!fullName) {
                valMark(form, 'full_name', 'Nombre');
                errors.push('THE NAME FIELD IS REQUIRED.');
            }

            // FIX QA-7: 'company' had a required asterisk (*) in the view but no
            // client-side validation at all — extended with the same required
            // pattern used for full_name.
            if (!company) {
                valMark(form, 'company', 'Empresa');
                errors.push('THE COMPANY FIELD IS REQUIRED.');
            }

            if (!documentType || documentType === 'seleccione' || documentType.toLowerCase().includes('select')) {
                valMark(form, 'document_type', 'Tipo de Documento');
                errors.push('THE DOCUMENT TYPE FIELD IS REQUIRED.');
            }

            // FIX QA-6: Was comparing documentType.toLowerCase() instead of
            // source.toLowerCase() — a copy-paste bug that made this guard check
            // the wrong field's value.
            if (!source || source === 'seleccione' || source.toLowerCase().includes('select')) {
                valMark(form, 'source', 'Origen');
                errors.push('THE SOURCE FIELD IS REQUIRED.');
            }

            // FIX QA-7: 'email' and 'phone' had a required asterisk (*) in the
            // view but were only checked for format when non-empty — an empty
            // value silently skipped both checks. Extended with the same
            // required/else-format pattern already used for 'rfc' below.
            if (!email) {
                valMark(form, 'email', 'Correo Electrónico');
                errors.push('THE EMAIL FIELD IS REQUIRED.');
            } else if (!REGEX.email.test(email)) {
                valMark(form, 'email', 'Correo Electrónico');
                errors.push('THE EMAIL FORMAT IS NOT VALID.');
            }
            if (!phone) {
                valMark(form, 'phone', 'Teléfono');
                errors.push('THE PHONE FIELD IS REQUIRED.');
            } else if (!REGEX.phone.test(phone)) {
                valMark(form, 'phone', 'Teléfono');
                errors.push('THE PHONE MUST CONTAIN EXACTLY 10 NUMERIC DIGITS.');
            }
            if (whatsapp && !REGEX.phone.test(whatsapp)) {
                errors.push('WHATSAPP MUST CONTAIN EXACTLY 10 NUMERIC DIGITS.');
            }
            if (!rfc) {
                valMark(form, 'rfc', 'RFC');
                errors.push('THE RFC FIELD IS REQUIRED.');
            } else if (!REGEX.rfc.test(rfc)) {
                valMarkFormat(form, 'rfc', 'El formato del RFC no es válido (ej. VECJ880326XXX).');
                errors.push('THE RFC DOES NOT HAVE A VALID FORMAT.');
            }
            if (cp && !REGEX.cp.test(cp)) {
                valMark(form, 'postal_code', 'Código Postal');
                errors.push('THE POSTAL CODE MUST CONTAIN EXACTLY 5 DIGITS.');
            }
            const emailInput = form.querySelector('[name="email"]');
            if (emailInput) emailInput.value = emailInput.value.toLowerCase();

            return errors;
        };
    </script>
@endpush
