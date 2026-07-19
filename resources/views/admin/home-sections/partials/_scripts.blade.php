@push('scripts')
    <script>
        const homeSectionUrl = '{{ url('/admin/inicio-secciones') }}';
        const homeSectionModal = document.getElementById('homeSectionModal');
        const homeSectionForm = document.getElementById('homeSectionForm');
        const errorsContainer = document.getElementById('home-section-modal-errors');
        let currentHomeSectionId = null;
        let isEditMode = false;

        const closeHomeSectionWithAnim = () => {
            const content = homeSectionModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            homeSectionModal.style.transition = 'opacity 0.2s ease-in';
            homeSectionModal.style.opacity = '0';
            setTimeout(() => {
                homeSectionModal.style.display = 'none';
                homeSectionModal.style.opacity = '';
                homeSectionModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        // Toggle config-fields blocks based on selected type
        function syncConfigFields() {
            const type = document.getElementById('hsType').value;
            document.querySelectorAll('.config-fields').forEach(block => {
                block.style.display = block.dataset.type === type ? 'block' : 'none';
            });
        }

        // Toggle product_carousel source-specific fields
        function syncSourceFields() {
            const source = document.getElementById('hsSource').value;
            document.querySelectorAll('.hs-source-field').forEach(block => {
                block.style.display = block.dataset.source === source ? 'block' : 'none';
            });
        }

        document.getElementById('hsType').addEventListener('change', syncConfigFields);
        document.getElementById('hsSource').addEventListener('change', syncSourceFields);

        const resetHomeSectionForm = () => {
            homeSectionForm.reset();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';
            currentHomeSectionId = null;
            isEditMode = false;
            document.getElementById('homeSectionModalTitle').textContent = 'Nueva Sección';
            document.getElementById('homeSectionSubmitBtn').textContent = 'Crear Sección';
            document.getElementById('hsSortOrder').value = '0';
            document.getElementById('hsIsActive').value = '1';
            document.getElementById('hsProductIds').value = '';

            syncConfigFields();
            syncSourceFields();
        };

        document.getElementById('btnNewHomeSection').addEventListener('click', () => {
            resetHomeSectionForm();
            homeSectionModal.style.display = 'flex';
        });

        document.getElementById('closeHomeSectionModal').addEventListener('click', () => closeHomeSectionWithAnim());
        document.getElementById('cancelHomeSectionModal').addEventListener('click', () => closeHomeSectionWithAnim());
        homeSectionModal.addEventListener('click', (e) => {
            if (e.target === homeSectionModal) closeHomeSectionWithAnim();
        });

        function showError(element, message) {
            element.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            const container = element.closest('.mb-3') || element.closest('.mb-4') || element.parentElement;
            if (container) container.appendChild(errorSpan);
        }

        homeSectionForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            errorsContainer.style.display = 'none';

            const formData = new FormData(homeSectionForm);

            // Manual product IDs come from a comma-separated text input, not
            // a real multi-value field — split it into product_ids[] here.
            const productIdsRaw = document.getElementById('hsProductIds').value;
            if (productIdsRaw.trim()) {
                productIdsRaw.split(',').map(v => v.trim()).filter(Boolean).forEach(id => {
                    formData.append('product_ids[]', id);
                });
            }

            const url = isEditMode ?
                `${homeSectionUrl}/editar/${currentHomeSectionId}` :
                `${homeSectionUrl}/crear`;

            if (isEditMode) formData.append('_method', 'PUT');

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
                    closeHomeSectionWithAnim();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    errorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    errorsContainer.style.display = 'block';
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        function populateConfigFields(type, config) {
            config = config || {};

            if (type === 'banner') {
                document.getElementById('hsBannerImageUrl').value = config.image_url ?? '';
                document.getElementById('hsBannerLinkUrl').value = config.link_url ?? '';
                document.getElementById('hsBannerAlt').value = config.alt ?? '';
            } else if (type === 'dual_banner') {
                const left = config.left ?? {};
                const right = config.right ?? {};
                document.getElementById('hsLeftImageUrl').value = left.image_url ?? '';
                document.getElementById('hsLeftLinkUrl').value = left.link_url ?? '';
                document.getElementById('hsLeftAlt').value = left.alt ?? '';
                document.getElementById('hsRightImageUrl').value = right.image_url ?? '';
                document.getElementById('hsRightLinkUrl').value = right.link_url ?? '';
                document.getElementById('hsRightAlt').value = right.alt ?? '';
            } else if (type === 'product_carousel') {
                document.getElementById('hsSource').value = config.source ?? 'featured';
                document.getElementById('hsCategoryId').value = config.category_id ?? '';
                document.getElementById('hsBrandId').value = config.brand_id ?? '';
                document.getElementById('hsCollectionId').value = config.collection_id ?? '';
                document.getElementById('hsLimit').value = config.limit ?? 10;
                document.getElementById('hsProductIds').value = (config.product_ids ?? []).join(', ');
                syncSourceFields();
            } else if (type === 'category_grid') {
                const ids = (config.category_ids ?? []).map(String);
                Array.from(document.getElementById('hsCategoryIds').options).forEach(opt => {
                    opt.selected = ids.includes(opt.value);
                });
            } else if (type === 'html_block') {
                document.getElementById('hsHtml').value = config.html ?? '';
            }
        }

        document.querySelectorAll('.btn-edit-home-section').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                currentHomeSectionId = id;
                isEditMode = true;

                fetch(`${homeSectionUrl}/editar/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(section => {
                        resetHomeSectionForm();

                        document.getElementById('homeSectionModalTitle').textContent = 'Editar Sección';
                        document.getElementById('homeSectionSubmitBtn').textContent = 'Guardar Cambios';
                        document.getElementById('hsType').value = section.type ?? 'hero_slider';
                        document.getElementById('hsTitle').value = section.title ?? '';
                        document.getElementById('hsSortOrder').value = section.sort_order ?? 0;
                        document.getElementById('hsIsActive').value = section.is_active ? '1' : '0';

                        syncConfigFields();
                        populateConfigFields(section.type, section.config);

                        errorsContainer.style.display = 'none';
                        homeSectionModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading home section:', err));
            });
        });

        // Delete modal
        const deleteHomeSectionModal = document.getElementById('deleteHomeSectionModal');
        let deleteHomeSectionId = null;

        document.querySelectorAll('.btn-delete-home-section').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteHomeSectionId = btn.dataset.id;
                document.getElementById('delHomeSectionTitle').textContent = btn.dataset.title;
                document.getElementById('delHomeSectionAvatar').textContent = btn.dataset.title.charAt(0).toUpperCase();
                deleteHomeSectionModal.classList.add('active');
            });
        });

        document.getElementById('delHomeSectionCancel').addEventListener('click', () => deleteHomeSectionModal.classList.remove('active'));
        deleteHomeSectionModal.addEventListener('click', (e) => {
            if (e.target === deleteHomeSectionModal) deleteHomeSectionModal.classList.remove('active');
        });

        document.getElementById('delHomeSectionConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${homeSectionUrl}/eliminar/${deleteHomeSectionId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _method: 'DELETE' }),
                });

                const data = await response.json();

                if (response.ok) {
                    deleteHomeSectionModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la sección.');
                    deleteHomeSectionModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error deleting home section:', err);
            }
        });

        /* ── Drag to reorder sections, saved instantly via AJAX ── */
        (function() {
            const tableBody = document.getElementById('homeSectionsTableBody');
            if (!tableBody) return;

            const reorderUrl = `${homeSectionUrl}/reordenar`;
            let dragSrcId = null;

            function currentOrder() {
                return Array.from(tableBody.querySelectorAll('.hs-row')).map(el => el.dataset.id);
            }

            function refreshSortOrderColumn() {
                Array.from(tableBody.querySelectorAll('.hs-row')).forEach((row, i) => {
                    const cell = row.querySelector('.hs-sort-order');
                    if (cell) cell.textContent = i;
                });
            }

            async function persistOrder() {
                try {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ order: currentOrder() }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        alert(data.message ?? 'No se pudo guardar el nuevo orden.');
                    }
                } catch (err) {
                    console.error('Error saving order:', err);
                }
            }

            tableBody.querySelectorAll('.hs-row').forEach(function(row) {
                row.addEventListener('dragstart', function() {
                    dragSrcId = row.dataset.id;
                    row.classList.add('is-dragging');
                });
                row.addEventListener('dragend', function() {
                    row.classList.remove('is-dragging');
                });
                row.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (dragSrcId === null || dragSrcId === row.dataset.id) return;
                    row.classList.add('drag-over');
                });
                row.addEventListener('dragleave', function() {
                    row.classList.remove('drag-over');
                });
                row.addEventListener('drop', function(e) {
                    e.preventDefault();
                    row.classList.remove('drag-over');
                    if (dragSrcId === null || dragSrcId === row.dataset.id) return;

                    const srcEl = tableBody.querySelector('.hs-row[data-id="' + dragSrcId + '"]');
                    dragSrcId = null;
                    if (!srcEl) return;

                    tableBody.insertBefore(srcEl, row);
                    refreshSortOrderColumn();
                    persistOrder();
                });
            });
        })();

        syncConfigFields();
        syncSourceFields();
    </script>
@endpush
