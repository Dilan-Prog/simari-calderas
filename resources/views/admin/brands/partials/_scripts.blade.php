@push('scripts')
    <script>
        const brandUrl = '{{ url('/admin/marcas') }}';
        const brandModal = document.getElementById('brandModal');
        const brandForm = document.getElementById('brandForm');
        const brandErrors = document.getElementById('brand-modal-errors');
        let currentBrandId = null;
        let isBrandEditMode = false;

        // Close modal with animation
        const closeBrandWithAnim = () => {
            const content = brandModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            brandModal.style.transition = 'opacity 0.2s ease-in';
            brandModal.style.opacity = '0';
            setTimeout(() => {
                brandModal.style.display = 'none';
                brandModal.style.opacity = '';
                brandModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        // Reset form
        const resetBrandForm = () => {
            brandForm.reset();
            document.getElementById('brandSlug').value = '';
            document.getElementById('brandIsActive').value = '1';
            brandErrors.style.display = 'none';
            brandErrors.innerHTML = '';
            currentBrandId = null;
            isBrandEditMode = false;
            document.getElementById('brandModalTitle').textContent = 'Nueva Marca';
            document.getElementById('brandSubmitBtn').textContent = 'Crear Marca';
        };

        // Open create
        document.getElementById('btnNewBrand').addEventListener('click', () => {
            resetBrandForm();
            brandModal.style.display = 'flex';
        });

        // Close
        document.getElementById('closeBrandModal').addEventListener('click', () => closeBrandWithAnim());
        document.getElementById('cancelBrandModal').addEventListener('click', () => closeBrandWithAnim());
        brandModal.addEventListener('click', (e) => {
            if (e.target === brandModal) closeBrandWithAnim();
        });

        // Auto-generate slug
        document.getElementById('brandName').addEventListener('input', function() {
            if (!isBrandEditMode) {
                document.getElementById('brandSlug').value = this.value.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim().replace(/\s+/g, '-');
            }
        });

        // Submit
        brandForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            brandErrors.style.display = 'none';
            brandErrors.innerHTML = '';

            let hasErrors = false;
            const nameInput = document.getElementById('brandName');
            const slugInput = document.getElementById('brandSlug');

            const showFieldError = (element, message) => {
                element.classList.add('is-invalid');

                const errorSpan = document.createElement('span');
                errorSpan.className = 'field-error-msg';
                errorSpan.innerText = message;

                const container = element.closest('.mb-3') || element.closest('.mb-4') || element
                    .parentElement;
                if (container) {
                    container.appendChild(errorSpan);
                }
                hasErrors = true;
            };

            if (!nameInput.value.trim()) {
                showFieldError(nameInput, 'El nombre de la marca es obligatorio.');
            }

            if (!slugInput.value.trim()) {
                showFieldError(slugInput, 'El slug (URL) de la marca es obligatorio.');
            }

            if (hasErrors) {
                const firstInvalid = document.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
                return;
            }

            const formData = new FormData(brandForm);
            const url = isBrandEditMode ?
                `${brandUrl}/editar/${currentBrandId}` :
                `${brandUrl}/crear`;

            if (isBrandEditMode) formData.append('_method', 'PUT');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok) {
                    closeBrandWithAnim();
                    if (isBrandEditMode) {
                        updateBrandTableRow(data.brand);
                    } else {
                        setTimeout(() => window.location.reload(), 200);
                    }
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    brandErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    brandErrors.style.display = 'block';

                    if (data.errors.name) showFieldError(nameInput, data.errors.name[0]);
                    if (data.errors.slug) showFieldError(slugInput, data.errors.slug[0]);
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Update table row without reload
        const updateBrandTableRow = (brand) => {
            const editBtn = document.querySelector(`.btn-edit-brand[data-id="${brand.id}"]`);
            if (!editBtn) return;
            const row = editBtn.closest('tr');

            row.querySelector('.brand-name').textContent = brand.name;
            row.querySelector('.brand-slug').textContent = brand.slug;
            row.querySelector('.brand-description').textContent =
                brand.description ?
                brand.description.substring(0, 60) + (brand.description.length > 60 ? '...' : '') :
                '—';

            const badge = row.querySelector('.brand-status-badge');
            if (badge) {
                badge.textContent = brand.is_active ? 'Activa' : 'Inactiva';
                badge.className =
                    `users-manager-badge brand-status-badge ${brand.is_active ? 'status' : 'status-inactive'}`;
                badge.dataset.status = brand.is_active ? 'active' : 'inactive';
            }

            const deleteBtn = row.querySelector('.btn-delete-brand');
            if (deleteBtn) {
                deleteBtn.dataset.name = brand.name;
                deleteBtn.dataset.slug = brand.slug;
            }

            row.dataset.name = brand.name.toLowerCase();
            row.dataset.slug = brand.slug.toLowerCase();
            row.dataset.status = brand.is_active ? 'active' : 'inactive';
        };

        // Edit
        document.querySelectorAll('.btn-edit-brand').forEach(btn => {
            btn.addEventListener('click', () => {
                currentBrandId = btn.dataset.id;
                isBrandEditMode = true;

                fetch(`${brandUrl}/editar/${currentBrandId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(brand => {
                        document.getElementById('brandModalTitle').textContent = 'Editar Marca';
                        document.getElementById('brandSubmitBtn').textContent = 'Guardar Cambios';
                        document.getElementById('brandName').value = brand.name ?? '';
                        document.getElementById('brandSlug').value = brand.slug ?? '';
                        document.getElementById('brandDescription').value = brand.description ?? '';
                        document.getElementById('brandLogoUrl').value = brand.logo_url ?? '';
                        document.getElementById('brandIsActive').value = brand.is_active ? '1' : '0';
                        brandErrors.style.display = 'none';
                        brandModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // Delete
        const deleteBrandModal = document.getElementById('deleteBrandModal');
        let deleteBrandId = null;

        document.querySelectorAll('.btn-delete-brand').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteBrandId = btn.dataset.id;
                document.getElementById('delBrandName').textContent = btn.dataset.name;
                document.getElementById('delBrandSlug').textContent = '/' + btn.dataset.slug;
                document.getElementById('delBrandAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteBrandModal.classList.add('active');
            });
        });

        document.getElementById('delBrandCancel').addEventListener('click', () =>
            deleteBrandModal.classList.remove('active'));
        deleteBrandModal.addEventListener('click', (e) => {
            if (e.target === deleteBrandModal) deleteBrandModal.classList.remove('active');
        });

        document.getElementById('delBrandConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${brandUrl}/eliminar/${deleteBrandId}`, {
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
                    deleteBrandModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la marca.');
                    deleteBrandModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Search and filter
        const brandSearch = document.getElementById('brandSearch');
        const brandStatusFilter = document.getElementById('brandStatusFilter');

        const filterBrands = () => {
            const search = brandSearch.value.toLowerCase().trim();
            const status = brandStatusFilter.value;

            document.querySelectorAll('.brand-row').forEach(row => {
                const name = row.dataset.name ?? '';
                const slug = row.dataset.slug ?? '';
                const rowStatus = row.dataset.status ?? '';
                const matchSearch = !search || name.includes(search) || slug.includes(search);
                const matchStatus = status === 'all' || rowStatus === status;
                row.style.display = matchSearch && matchStatus ? '' : 'none';
            });
        };

        brandSearch.addEventListener('input', filterBrands);
        brandStatusFilter.addEventListener('change', filterBrands);
    </script>
@endpush
