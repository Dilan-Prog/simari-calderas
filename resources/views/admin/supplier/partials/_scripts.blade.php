@push('scripts')
    <script>
        // --- Create modal ---
        const supplierCreateModal = document.getElementById('supplierCreateModal');
        const closeSupplierCreateBtn = document.getElementById('closeSupplierCreateModal');
        const cancelSupplierCreateBtn = document.getElementById('cancelSupplierCreateModal');
        const openSupplierBtn = document.querySelector('.button-primary.size-adjustment.suppliers');

        //  Validations

        // Create
        dynamicPhone('#supplierCreateForm [name="phone"]');
        dynamicRFC('#supplierCreateForm [name="rfc"]');

        // Edit
        dynamicPhone('#supplierEditForm [name="phone"]');
        dynamicRFC('#supplierEditForm [name="rfc"]');

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
            const localErrors = validateSupplierFields(supplierEditForm);

            if (localErrors.length > 0) {
                errorsContainer.innerHTML = localErrors.map(msg => `<p>⚠️ ${msg}</p>`).join('');
                errorsContainer.style.display = 'block';
                return;
            }

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

            // Update stars with colored spans and data-rating
            const starSvg = (filled) => `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
            fill="${filled ? '#facc15' : 'none'}" stroke="${filled ? '#facc15' : '#d1d5db'}"
            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
            </svg>`;

            const stars = (rating) => [...Array(5)].map((_, i) => starSvg(i < rating)).join('');

            const starEls = row.querySelectorAll('.supplier-stars-svg');
            if (starEls[0]) {
                starEls[0].innerHTML = stars(supplier.rating_quality ?? 0);
                starEls[0].dataset.rating = supplier.rating_quality ?? 0;
            }
            if (starEls[1]) {
                starEls[1].innerHTML = stars(supplier.rating_compliance ?? 0);
                starEls[1].dataset.rating = supplier.rating_compliance ?? 0;
            }

            const statusBadge = row.querySelector('.supplier-status-badge');
            if (statusBadge) {
                const labels = {
                    active: 'Activo',
                    inactive: 'Inactivo',
                    suspended: 'Suspendido'
                };
                statusBadge.textContent = labels[supplier.status] ?? supplier.status;
                statusBadge.className = 'users-manager-badge supplier-status-badge ' +
                    (supplier.status === 'active' ? 'status' : 'status-inactive');
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
                const badge = row.querySelector('.supplier-status-badge');
                const payBadge = row.querySelector('.supplier-payment');
                const qualEl = row.querySelectorAll('.supplier-stars-svg')[0];
                const complianceEl = row.querySelectorAll('.supplier-stars-svg')[1];
                const qualRating = parseInt(qualEl?.dataset.rating ?? '0');
                const compRating = parseInt(complianceEl?.dataset.rating ?? '0');

                const matchSearch = !search || company.includes(search) || contact.includes(search) ||
                    email.includes(search) || phone.includes(search);
                const matchStatus = status === 'all' || badge?.dataset.status === status;
                const matchPayment = payment === 'all' || payBadge?.dataset.payment === payment;
                const matchRating = qualRating >= minRating || compRating >= minRating;

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
            const snappedPct = value * step;
            handle.style.left = `calc(${snappedPct}% - 9px)`;
            fill.style.width = `${snappedPct}%`;
            label.innerText = `Rating mínimo: ${value}/5`;
            filterSuppliers();
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

        // --- CFDI / Constancia SAT reader (Proveedor) ---
        (function() {
            const btn = document.getElementById('supplierCfdiReadBtn');
            const input = document.getElementById('supplierCfdiFileInput');
            const fileName = document.getElementById('supplierCfdiFileName');
            const status = document.getElementById('supplierCfdiStatus');
            const form = document.getElementById('supplierCreateForm');

            function showStatus(type, message) {
                status.style.display = 'block';
                status.className = `cfdi-status cfdi-status--${type}`;
                status.textContent = message;
            }

            btn.addEventListener('click', () => input.click());

            input.addEventListener('change', async function() {
                if (!this.files.length) return;

                fileName.textContent = this.files[0].name;
                btn.disabled = true;
                btn.textContent = '⏳ Leyendo...';
                showStatus('info', 'Procesando el PDF, espera un momento...');

                const formData = new FormData();
                formData.append('cfdi_pdf', this.files[0]);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                try {
                    const res = await fetch('{{ route('admin.clients.parse-cfdi') }}', {
                        method: 'POST',
                        body: formData,
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        showStatus('error', data.error || 'Error al procesar el PDF.');
                        return;
                    }

                    // Mapeo: campos CFDI → campos del formulario de proveedor
                    const map = {
                        rfc: '[name="rfc"]',
                        full_name: '[name="contact_name"]',
                        company: '[name="company_name"]',
                        phone: '[name="phone"]',
                        email: '[name="email"]',
                    };

                    let filled = 0;
                    for (const [key, selector] of Object.entries(map)) {
                        if (!data[key]) continue;
                        const el = form.querySelector(selector);
                        if (!el) continue;
                        el.value = data[key];
                        filled++;
                    }

                    // Dirección: construir desde partes en el textarea único
                    const addressEl = form.querySelector('[name="address"]');
                    if (addressEl) {
                        const parts = [
                            data.address_line1,
                            data.city,
                            data.state,
                            data.postal_code ? `C.P. ${data.postal_code}` : '',
                            data.country,
                        ].filter(Boolean);
                        if (parts.length) {
                            addressEl.value = parts.join(', ');
                            filled++;
                        }
                    }

                    showStatus('success', `✅ Se llenaron ${filled} campos automáticamente.`);

                } catch {
                    showStatus('error', 'Error de conexión. Intenta de nuevo.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = '📄 Seleccionar Constancia (PDF)';
                    input.value = '';
                }
            });
        })();


        // VALIDATIONS
        const regex = {
            email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
            phone: /^\d{10}$/,
            rfc: /^([A-ZÑ&]{3,4}) ?(\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])) ?([A-Z\d]{3})$/
        };

        function dynamicPhone(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) value = value.slice(0, 10);
                e.target.value = value;
            });
        }

        function dynamicRFC(selector) {
            const input = document.querySelector(selector);
            if (!input) return;
            input.addEventListener('input', function(e) {
                let value = e.target.value.toUpperCase().replace(/[^A-Z0-9&Ñ]/g, '');
                if (value.length > 13) value = value.slice(0, 13);
                e.target.value = value;
            });
        }

        function validateSupplierFields(form) {
            const errors = [];
            const email = form.querySelector('[name="email"]')?.value.trim();
            const phone = form.querySelector('[name="phone"]')?.value.trim();
            const rfc = form.querySelector('[name="rfc"]')?.value.trim();

            if (email && !regex.email.test(email)) errors.push('El formato del Correo Electrónico no es válido.');
            if (phone && !regex.phone.test(phone)) errors.push(
                'El Teléfono debe contener exactamente 10 dígitos numéricos.');
            if (rfc && !regex.rfc.test(rfc)) errors.push(
                'El RFC no tiene un formato válido (Ej: ABC900511XXX o VECJ880326XXX).');

            return errors;
        }
    </script>
@endpush
