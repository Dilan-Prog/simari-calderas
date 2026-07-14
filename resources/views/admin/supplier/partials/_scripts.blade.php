@push('scripts')
<script>
    // ── Slider ──────────────────────────────────────────────
    (function () {
        const handle = document.getElementById('slider-handle');
        const fill   = document.getElementById('slider-fill');
        const track  = document.getElementById('slider-container');
        const label  = document.getElementById('rating-label');

        if (!handle || !fill || !track || !label) return;

        const min = 0;
        const max = 5;
        let currentValue = 0;
        let dragging     = false;

        function setVal(val) {
            currentValue      = Math.min(max, Math.max(min, Math.round(val)));
            const pct         = (currentValue / max) * 100;
            handle.style.left = pct + '%';
            fill.style.width  = pct + '%';
            label.textContent = `Rating mínimo: ${currentValue}/5`;
            if (typeof filterSuppliers === 'function') filterSuppliers();
        }

        function valFromEvent(e) {
            const rect    = track.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const ratio   = (clientX - rect.left) / rect.width;
            return min + ratio * (max - min);
        }

        handle.addEventListener('mousedown', function (e) {
            dragging = true;
            e.preventDefault();
        });

        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            setVal(valFromEvent(e));
        });

        document.addEventListener('mouseup', function () {
            dragging = false;
        });

        handle.addEventListener('touchstart', function (e) {
            dragging = true;
            e.preventDefault();
        }, { passive: false });

        document.addEventListener('touchmove', function (e) {
            if (!dragging) return;
            setVal(valFromEvent(e));
        }, { passive: false });

        document.addEventListener('touchend', function () {
            dragging = false;
        });

        track.addEventListener('click', function (e) {
            if (e.target === handle) return;
            setVal(valFromEvent(e));
        });

        setVal(0);
    })();

    // ── Funciones de formato ────────────────────────────────
    function supplierBindAlphanumeric(input) {
        if (input.dataset.bound) return;
        input.dataset.bound = '1';
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.\-_/,:]/g, '');
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = (this.value + text).replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.\-_/,:]/g, '');
        });
    }

    function supplierBindLettersOnly(input) {
        if (input.dataset.bound) return;
        input.dataset.bound = '1';
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = (this.value + text).replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        });
    }

    function supplierBindRating(input) {
        if (input.dataset.bound) return;
        input.dataset.bound = '1';
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^1-5]/g, '').slice(0, 1);
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = (this.value + text).replace(/[^1-5]/g, '').slice(0, 1);
        });
    }

    function supplierBindPhone(input) {
        if (input.dataset.bound) return;
        input.dataset.bound = '1';
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9+\s\-()]/g, '').slice(0, 20);
        });
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            this.value = (this.value + text).replace(/[^0-9+\s\-()]/g, '').slice(0, 20);
        });
    }

    // Bind create modal inputs al cargar
    (function bindCreateInputs() {
        const modal = document.getElementById('supplierCreateModal');
        if (!modal) return;
        const company = modal.querySelector('[name="company_name"]');
        const website = modal.querySelector('[name="website"]');
        const contact = modal.querySelector('[name="contact_name"]');
        const phone   = modal.querySelector('[name="phone"]');
        const ratingQ = modal.querySelector('[name="rating_quality"]');
        const ratingC = modal.querySelector('[name="rating_compliance"]');
        if (company) supplierBindAlphanumeric(company);
        if (website) supplierBindAlphanumeric(website);
        if (contact) supplierBindLettersOnly(contact);
        if (phone)   supplierBindPhone(phone);
        if (ratingQ) supplierBindRating(ratingQ);
        if (ratingC) supplierBindRating(ratingC);
    })();

    // ── Filter ──────────────────────────────────────────────
    function filterSuppliers() {
        const search    = document.getElementById('supplierSearch')?.value.toLowerCase()  ?? '';
        const status    = document.getElementById('supplierStatusFilter')?.value          ?? 'all';
        const payment   = document.getElementById('supplierPaymentFilter')?.value         ?? 'all';
        const ratingLabelEl = document.getElementById('rating-label');
        const ratingText    = ratingLabelEl?.textContent ?? 'Rating mínimo: 0/5';
        const minRating     = parseInt(ratingText.replace('Rating mínimo: ', '').split('/')[0]) || 0;

        document.querySelectorAll('.supplier-row').forEach(row => {
            const rowText     = row.textContent.toLowerCase();
            const statusBadge = row.querySelector('.supplier-status-badge');
            const paymentEl   = row.querySelector('.supplier-payment');
            const starsEls    = row.querySelectorAll('.supplier-stars-svg[data-rating]');

            const rowStatus  = statusBadge?.dataset.status ?? '';
            const rowPayment = paymentEl?.dataset.payment  ?? '';
            const qualRating = parseInt(starsEls[0]?.dataset.rating) || 0;
            const compRating = parseInt(starsEls[1]?.dataset.rating) || 0;

            const matchSearch  = !search      || rowText.includes(search);
            const matchStatus  = status  === 'all' || rowStatus  === status;
            const matchPayment = payment === 'all' || rowPayment === payment;
            const matchRating  = minRating === 0   || qualRating >= minRating || compRating >= minRating;

            row.style.display = matchSearch && matchStatus && matchPayment && matchRating ? '' : 'none';
        });
    }

    document.getElementById('supplierSearch')?.addEventListener('input',        filterSuppliers);
    document.getElementById('supplierStatusFilter')?.addEventListener('change', filterSuppliers);
    document.getElementById('supplierPaymentFilter')?.addEventListener('change',filterSuppliers);

    // ── Toast ───────────────────────────────────────────────
    const showSupplierToast = (message, type = 'success') => {
        const existing = document.getElementById('supplierToast');
        if (existing) existing.remove();

        const toast     = document.createElement('div');
        toast.id        = 'supplierToast';
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                    fill="none" stroke="${type === 'error' ? '#dc2626' : '#16a34a'}"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    ${type === 'error'
                        ? '<circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/>'
                        : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>'}
                </svg>
            </div>
            <div class="toast-body">
                <p class="toast-title">${type === 'error' ? 'Error' : 'Acción realizada'}</p>
                <p class="toast-message">${message}</p>
            </div>
            <button class="toast-close"
                onclick="const t=this.closest('.toast-notification');t.style.animation='toastOut 0.3s ease forwards';setTimeout(()=>t.remove(),300)">✕</button>`;

        document.querySelector('.container.user-manager')?.prepend(toast);
        setTimeout(() => {
            toast.style.animation = 'toastOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 7000);
    };

    // ── Stars builder ───────────────────────────────────────
    function buildStars(rating) {
        let html = '';
        for (let s = 1; s <= 5; s++) {
            const filled = s <= rating;
            html += `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                viewBox="0 0 24 24" fill="${filled ? '#facc15' : 'none'}"
                stroke="${filled ? '#facc15' : '#d1d5db'}" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>
            </svg>`;
        }
        return html;
    }

    // ── Update table row ────────────────────────────────────
    const updateSupplierTableRow = (supplier) => {
        const editBtn = document.querySelector(`.btn-edit-supplier[data-id="${supplier.id}"]`);
        if (!editBtn) return;
        const row = editBtn.closest('tr');

        const nameEl = row.querySelector('.supplier-name');
        if (nameEl) nameEl.textContent = supplier.company_name ?? '—';

        const contactEl = row.querySelector('.supplier-contact');
        if (contactEl) contactEl.textContent = supplier.contact_name ?? '—';

        const statusBadge = row.querySelector('.supplier-status-badge');
        if (statusBadge) {
            const labels = { active: 'Activo', inactive: 'Inactivo', suspended: 'Suspendido' };
            statusBadge.textContent    = labels[supplier.status] ?? supplier.status;
            statusBadge.className      = 'users-manager-badge supplier-status-badge ' +
                (supplier.status === 'active' ? 'status' : 'status-inactive');
            statusBadge.dataset.status = supplier.status;
        }

        const starsEls = row.querySelectorAll('.supplier-stars-svg');
        if (starsEls[0]) {
            starsEls[0].dataset.rating = supplier.rating_quality ?? 0;
            starsEls[0].innerHTML      = buildStars(supplier.rating_quality ?? 0);
        }
        if (starsEls[1]) {
            starsEls[1].dataset.rating = supplier.rating_compliance ?? 0;
            starsEls[1].innerHTML      = buildStars(supplier.rating_compliance ?? 0);
        }

        const deleteBtn = row.querySelector('.btn-delete-supplier');
        if (deleteBtn) {
            deleteBtn.dataset.name  = supplier.company_name ?? '';
            deleteBtn.dataset.email = supplier.email        ?? '';
        }
    };

    // ── Create modal ────────────────────────────────────────
    const supplierModal       = document.getElementById('supplierCreateModal');
    const closeSupplierModal  = document.getElementById('closeSupplierModal');
    const cancelSupplierModal = document.getElementById('cancelSupplierModal');

    const closeCreateWithAnim = () => {
        const content = supplierModal?.querySelector('.user-manager-modal-content');
        if (content) {
            content.style.transition = 'transform 0.2s ease-in';
            content.style.transform  = 'translateX(100%)';
        }
        supplierModal.style.transition = 'opacity 0.2s ease-in';
        supplierModal.style.opacity    = '0';
        setTimeout(() => {
            supplierModal.style.display    = 'none';
            supplierModal.style.opacity    = '';
            supplierModal.style.transition = '';
            if (content) {
                content.style.transform  = '';
                content.style.transition = '';
            }
        }, 200);
    };

    document.querySelector('.new-supplier-btn')?.addEventListener('click', () => {
        supplierModal.style.display = 'flex';
    });
    closeSupplierModal?.addEventListener('click',  closeCreateWithAnim);
    cancelSupplierModal?.addEventListener('click', closeCreateWithAnim);
    supplierModal?.addEventListener('click', (e) => {
        if (e.target === supplierModal) closeCreateWithAnim();
    });

    document.getElementById('supplierCreateForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const createErrors = [];
        const cForm    = e.target;
        const cPhone   = cForm.querySelector('[name="phone"]')?.value.trim()              ?? '';
        const cRatingQ = cForm.querySelector('[name="rating_quality"]')?.value.trim()    ?? '';
        const cRatingC = cForm.querySelector('[name="rating_compliance"]')?.value.trim() ?? '';

        if (cPhone && cPhone.length > 20) {
            createErrors.push('El teléfono no puede tener más de 20 caracteres.');
        }
        // FIX: rating inputs default to "0" (= sin calificar todavía, valor
        // legítimo — así se muestran las estrellas vacías y el filtro
        // "Rating mínimo: 0/5"). "0" && (0 < 1) era truthy, así que este
        // check rechazaba SIEMPRE el formulario de creación incluso sin
        // tocar los ratings — ningún proveedor se podía crear jamás.
        if (cRatingQ && cRatingQ !== '0' && (parseInt(cRatingQ) < 1 || parseInt(cRatingQ) > 5)) {
            createErrors.push('El rating de calidad debe ser entre 1 y 5.');
        }
        if (cRatingC && cRatingC !== '0' && (parseInt(cRatingC) < 1 || parseInt(cRatingC) > 5)) {
            createErrors.push('El rating de cumplimiento debe ser entre 1 y 5.');
        }

        if (createErrors.length > 0) {
            const errBox = document.getElementById('supplier-create-errors');
            if (errBox) {
                errBox.innerHTML     = createErrors.map(m => `<p>${m}</p>`).join('');
                errBox.style.display = 'block';
                errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const formData = new FormData(cForm);
        try {
            const res  = await fetch('{{ route('admin.suppliers.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
                body: formData,
            });
            const data = await res.json();

            if (res.ok) {
                closeCreateWithAnim();
                cForm.reset();
                showSupplierToast('Proveedor creado correctamente.');
                setTimeout(() => window.location.reload(), 1500);
            } else if (res.status === 422) {
                const errBox = document.getElementById('supplier-create-errors');
                if (errBox) {
                    errBox.innerHTML     = Object.values(data.errors).flat().map(m => `<p>${m}</p>`).join('');
                    errBox.style.display = 'block';
                }
            } else {
                showSupplierToast(data.message ?? 'Error al crear el proveedor.', 'error');
            }
        } catch (err) {
            console.error(err);
            showSupplierToast('Error de conexión.', 'error');
        }
    });

    // ── Edit modal ──────────────────────────────────────────
    const supplierEditModal       = document.getElementById('supplierEditModal');
    const closeSupplierEditModal  = document.getElementById('closeSupplierEditModal');
    const cancelSupplierEditModal = document.getElementById('cancelSupplierEditModal');

    const closeEditWithAnim = () => {
        const content = supplierEditModal?.querySelector('.user-manager-modal-content');
        if (content) {
            content.style.transition = 'transform 0.2s ease-in';
            content.style.transform  = 'translateX(100%)';
        }
        supplierEditModal.style.transition = 'opacity 0.2s ease-in';
        supplierEditModal.style.opacity    = '0';
        setTimeout(() => {
            supplierEditModal.style.display    = 'none';
            supplierEditModal.style.opacity    = '';
            supplierEditModal.style.transition = '';
            if (content) {
                content.style.transform  = '';
                content.style.transition = '';
            }
        }, 200);
    };

    closeSupplierEditModal?.addEventListener('click',  closeEditWithAnim);
    cancelSupplierEditModal?.addEventListener('click', closeEditWithAnim);
    supplierEditModal?.addEventListener('click', (e) => {
        if (e.target === supplierEditModal) closeEditWithAnim();
    });

    document.querySelectorAll('.btn-edit-supplier').forEach(btn => {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const form = document.getElementById('supplierEditForm');

            fetch(`{{ url('/admin/proveedores/editar-proveedor') }}/${id}`, {
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(res => res.json())
            .then(supplier => {
                form.querySelector('[name="company_name"]').value      = supplier.company_name      ?? '';
                form.querySelector('[name="contact_name"]').value      = supplier.contact_name      ?? '';
                form.querySelector('[name="email"]').value             = supplier.email             ?? '';
                form.querySelector('[name="phone"]').value             = supplier.phone             ?? '';
                form.querySelector('[name="rfc"]').value               = supplier.rfc               ?? '';
                form.querySelector('[name="website"]').value           = supplier.website           ?? '';
                form.querySelector('[name="address"]').value           = supplier.address           ?? '';
                form.querySelector('[name="payment_terms"]').value     = supplier.payment_terms     ?? '';
                form.querySelector('[name="status"]').value            = supplier.status            ?? 'active';
                form.querySelector('[name="notes"]').value             = supplier.notes             ?? '';
                form.querySelector('[name="rating_quality"]').value    = supplier.rating_quality    ?? '';
                form.querySelector('[name="rating_compliance"]').value = supplier.rating_compliance ?? '';

                const phoneInput = form.querySelector('[name="phone"]');
                if (phoneInput) phoneInput.dataset.original = supplier.phone ?? '';

                const bindTargets = [
                    [form.querySelector('[name="company_name"]'),      supplierBindAlphanumeric],
                    [form.querySelector('[name="website"]'),           supplierBindAlphanumeric],
                    [form.querySelector('[name="contact_name"]'),      supplierBindLettersOnly],
                    [form.querySelector('[name="phone"]'),             supplierBindPhone],
                    [form.querySelector('[name="rating_quality"]'),    supplierBindRating],
                    [form.querySelector('[name="rating_compliance"]'), supplierBindRating],
                ];
                bindTargets.forEach(([el, fn]) => { if (el) fn(el); });

                form.dataset.supplierId = id;

                const errBox = document.getElementById('supplier-edit-errors');
                if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }

                supplierEditModal.style.display = 'flex';
            })
            .catch(err => console.error('Error loading supplier:', err));
        });
    });

    document.getElementById('supplierEditForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const editErrors  = [];
        const eForm       = e.target;
        const ePhoneInput = eForm.querySelector('[name="phone"]');
        const ePhoneVal   = ePhoneInput?.value.trim()     ?? '';
        const ePhoneOrig  = ePhoneInput?.dataset.original ?? '';

        if (ePhoneVal !== ePhoneOrig && ePhoneVal.length > 20) {
            editErrors.push('El teléfono no puede tener más de 20 caracteres.');
        }

        if (editErrors.length > 0) {
            const errBox = document.getElementById('supplier-edit-errors');
            if (errBox) {
                errBox.innerHTML     = editErrors.map(m => `<p>${m}</p>`).join('');
                errBox.style.display = 'block';
                errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const id       = eForm.dataset.supplierId;
        const formData = new FormData(eForm);
        formData.append('_method', 'PUT');

        try {
            const res  = await fetch(`{{ url('/admin/proveedores/editar-proveedor') }}/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                },
                body: formData,
            });
            const data = await res.json();

            if (res.ok) {
                closeEditWithAnim();
                updateSupplierTableRow(data.supplier);
                showSupplierToast('Proveedor actualizado correctamente.');
            } else if (res.status === 422) {
                const errBox = document.getElementById('supplier-edit-errors');
                if (errBox) {
                    errBox.innerHTML     = Object.values(data.errors).flat().map(m => `<p>${m}</p>`).join('');
                    errBox.style.display = 'block';
                }
            } else {
                showSupplierToast(data.message ?? 'Error al actualizar.', 'error');
            }
        } catch (err) {
            console.error(err);
            showSupplierToast('Error de conexión.', 'error');
        }
    });

    // ── Delete modal ────────────────────────────────────────
    let currentDeleteSupplierId = null;
    const deleteSupplierModal   = document.getElementById('deleteSupplierModal');
    const deleteSupplierForm    = document.getElementById('deleteSupplierForm');
    const delCancelBtn          = document.getElementById('delSupplierConfirmCancel');
    const deleteSupplierUrl     = '{{ url('/admin/proveedores/eliminar-proveedor') }}';

    document.querySelectorAll('.btn-delete-supplier').forEach(btn => {
        btn.addEventListener('click', function () {
            currentDeleteSupplierId = this.dataset.id;
            document.getElementById('delSupplierConfirmName').textContent   = this.dataset.name  ?? '—';
            document.getElementById('delSupplierConfirmEmail').textContent  = this.dataset.email ?? '—';
            document.getElementById('delSupplierConfirmAvatar').textContent =
                (this.dataset.name ?? 'P').charAt(0).toUpperCase();
            deleteSupplierModal.classList.add('active');
        });
    });

    delCancelBtn?.addEventListener('click', () => {
        deleteSupplierModal.classList.remove('active');
    });

    deleteSupplierModal?.addEventListener('click', (e) => {
        if (e.target === deleteSupplierModal) deleteSupplierModal.classList.remove('active');
    });

    deleteSupplierForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await fetch(`${deleteSupplierUrl}/${currentDeleteSupplierId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _method: 'DELETE' }),
            });
            const data = await res.json();

            if (res.ok) {
                deleteSupplierModal.classList.remove('active');
                document.querySelector(
                    `.btn-delete-supplier[data-id="${currentDeleteSupplierId}"]`
                )?.closest('tr')?.remove();
                showSupplierToast('Proveedor eliminado correctamente.');
            } else {
                deleteSupplierModal.classList.remove('active');
                showSupplierToast(data.message ?? 'No se pudo eliminar.', 'error');
            }
        } catch (err) {
            console.error(err);
            showSupplierToast('Error de conexión.', 'error');
        }
    });
</script>
@endpush
