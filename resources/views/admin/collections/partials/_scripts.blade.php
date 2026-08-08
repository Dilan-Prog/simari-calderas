@push('scripts')
    <script>
        const collectionUrl = '{{ url('/admin/colecciones') }}';
        const collectionModal = document.getElementById('collectionModal');
        const collectionForm = document.getElementById('collectionForm');
        const errorsContainer = document.getElementById('collection-modal-errors');
        let currentCollectionId = null;
        let isEditMode = false;

        // Close modal with animation
        const closeCollectionWithAnim = () => {
            const content = collectionModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            collectionModal.style.transition = 'opacity 0.2s ease-in';
            collectionModal.style.opacity = '0';
            setTimeout(() => {
                collectionModal.style.display = 'none';
                collectionModal.style.opacity = '';
                collectionModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        // ── Rule rows (automatic collections) ─────────────────
        const ruleRowsContainer = document.getElementById('collectionRuleRows');
        const ruleRowTemplate = document.getElementById('collectionRuleRowTemplate');

        function wireRuleRow(row) {
            const fieldSelect = row.querySelector('.rule-field');
            const operatorSelect = row.querySelector('.rule-operator');
            const removeBtn = row.querySelector('.btn-remove-rule');

            const valueInputs = {
                tag: row.querySelector('.rule-value-text'),
                category_id: row.querySelector('.rule-value-category'),
                brand_id: row.querySelector('.rule-value-brand'),
                price: row.querySelector('.rule-value-price'),
            };

            const operatorOptions = operatorSelect.querySelectorAll('option');

            function toggleByField() {
                const field = fieldSelect.value;

                Object.entries(valueInputs).forEach(([key, input]) => {
                    const active = key === field;
                    input.style.display = active ? '' : 'none';
                    input.disabled = !active;
                });

                if (field === 'price') {
                    operatorOptions.forEach(opt => { opt.style.display = ''; });
                } else {
                    operatorOptions.forEach(opt => {
                        opt.style.display = opt.value === 'equals' ? '' : 'none';
                    });
                    operatorSelect.value = 'equals';
                }
            }

            fieldSelect.addEventListener('change', toggleByField);
            removeBtn.addEventListener('click', () => row.remove());

            toggleByField();
        }

        function addRuleRow() {
            const fragment = ruleRowTemplate.content.cloneNode(true);
            const row = fragment.querySelector('.collection-rule-row');
            ruleRowsContainer.appendChild(fragment);
            wireRuleRow(ruleRowsContainer.lastElementChild);
        }

        document.getElementById('btnAddRule').addEventListener('click', addRuleRow);

        // Toggle automatic-only fields based on selected type
        const collectionTypeSelect = document.getElementById('collectionType');
        const automaticFields = document.getElementById('collectionAutomaticFields');

        function toggleTypeFields() {
            automaticFields.style.display = collectionTypeSelect.value === 'automatic' ? '' : 'none';
        }

        collectionTypeSelect.addEventListener('change', toggleTypeFields);

        /* ── FAQ de la colección (repeater indexado faq_items[N][...]) ── */
        const collectionFaqRows = document.getElementById('collectionFaqRows');

        function reindexCollectionFaqs() {
            Array.from(collectionFaqRows.querySelectorAll('.hs-faq-row')).forEach((row, i) => {
                row.querySelector('.hs-faq-question').name = `faq_items[${i}][question]`;
                row.querySelector('.hs-faq-answer').name = `faq_items[${i}][answer]`;
                row.querySelector('.hs-faq-row-num').textContent = i + 1;
            });
        }

        function addCollectionFaqRow(question = '', answer = '') {
            const row = document.createElement('div');
            row.className = 'hs-faq-row';
            row.innerHTML = `
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num"></span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta"></textarea>`;

            row.querySelector('.hs-faq-question').value = question;
            row.querySelector('.hs-faq-answer').value = answer;
            row.querySelector('.hs-faq-remove').addEventListener('click', () => {
                row.remove();
                reindexCollectionFaqs();
            });

            collectionFaqRows.appendChild(row);
            reindexCollectionFaqs();
        }

        document.getElementById('btnAddCollectionFaq').addEventListener('click', () => addCollectionFaqRow());

        // Reset form
        const resetCollectionForm = () => {
            collectionForm.reset();
            document.getElementById('collectionSortOrder').value = '0';
            document.getElementById('collectionIsActive').value = '1';
            collectionFaqRows.innerHTML = '';
            ruleRowsContainer.innerHTML = '';
            toggleTypeFields();
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';
            currentCollectionId = null;
            isEditMode = false;
            document.getElementById('collectionModalTitle').textContent = 'Nueva Colección';
            document.getElementById('collectionSubmitBtn').textContent = 'Crear Colección';
        };

        // Open create modal
        document.getElementById('btnNewCollection').addEventListener('click', () => {
            resetCollectionForm();
            addRuleRow();
            collectionModal.style.display = 'flex';
        });

        // Close modal
        document.getElementById('closeCollectionModal').addEventListener('click', () => closeCollectionWithAnim());
        document.getElementById('cancelCollectionModal').addEventListener('click', () => closeCollectionWithAnim());
        collectionModal.addEventListener('click', (e) => {
            if (e.target === collectionModal) closeCollectionWithAnim();
        });

        // Auto-generate slug from name
        document.getElementById('collectionName').addEventListener('input', function() {
            if (!isEditMode) {
                const slug = this.value.toLowerCase()
                    .normalize('NFD').replace(/[^\x00-\x7F]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim().replace(/\s+/g, '-');
                document.getElementById('collectionSlug').value = slug;
            }
        });

        // Marca un campo inválido inline: agrega .is-invalid al elemento y
        // muestra el mensaje real del servidor como .field-error-msg, junto
        // al patrón ya usado en home-sections/service-pages para 422s.
        function showCollectionFieldError(element, message) {
            element.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            const container = element.closest('.users-manager-email-camp') || element.parentElement;
            if (container) container.appendChild(errorSpan);
        }

        // Submit form (create or update)
        collectionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';

            const formData = new FormData(collectionForm);

            const url = isEditMode ?
                `${collectionUrl}/editar/${currentCollectionId}` :
                `${collectionUrl}/crear`;

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

                if (response.status === 419) {
                    errorsContainer.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    errorsContainer.style.display = 'block';
                    showCenterToast('Tu sesión expiró. Por favor recarga la página e intenta de nuevo.', 'error');
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeCollectionWithAnim();
                    queueCenterToast(isEditMode ? 'Colección actualizada correctamente.' : 'Colección creada correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errors = data.errors || {};
                    const errorList = Object.values(errors).flat();
                    errorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    errorsContainer.style.display = 'block';
                    showCenterToast('Revisa los campos marcados.', 'error');

                    Object.keys(errors).forEach(field => {
                        const input = collectionForm.querySelector(`[name="${field}"]`);
                        if (input) showCollectionFieldError(input, errors[field][0]);
                    });
                }
            } catch (err) {
                console.error('Error:', err);
                showCenterToast('Error de conexión al guardar la colección.', 'error');
            }
        });

        // Edit
        document.querySelectorAll('.btn-edit-collection').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                currentCollectionId = id;
                isEditMode = true;

                fetch(`${collectionUrl}/editar/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(col => {
                        document.getElementById('collectionModalTitle').textContent = 'Editar Colección';
                        document.getElementById('collectionSubmitBtn').textContent = 'Guardar Cambios';
                        document.getElementById('collectionName').value = col.name ?? '';
                        document.getElementById('collectionSlug').value = col.slug ?? '';
                        document.getElementById('collectionDescription').value = col.description ?? '';
                        document.getElementById('collectionImageUrl').value = col.image_url ?? '';
                        document.getElementById('collectionSortOrder').value = col.sort_order ?? 0;
                        document.getElementById('collectionIsActive').value = col.is_active ? '1' : '0';
                        document.getElementById('collectionType').value = col.type ?? 'manual';
                        document.getElementById('collectionMatchType').value = col.match_type ?? 'all';
                        document.getElementById('collectionSeoTitle').value = col.seo_title ?? '';
                        document.getElementById('collectionSeoDescription').value = col.seo_description ?? '';
                        document.getElementById('collectionOgImageUrl').value = col.og_image_url ?? '';
                        collectionFaqRows.innerHTML = '';
                        (col.faqs ?? []).forEach(f => addCollectionFaqRow(f.question ?? '', f.answer ?? ''));

                        toggleTypeFields();

                        ruleRowsContainer.innerHTML = '';
                        if (col.type === 'automatic' && Array.isArray(col.rules) && col.rules.length) {
                            col.rules.forEach(rule => {
                                addRuleRow();
                                const row = ruleRowsContainer.lastElementChild;
                                row.querySelector('.rule-field').value = rule.field;
                                row.querySelector('.rule-field').dispatchEvent(new Event('change'));
                                row.querySelector('.rule-operator').value = rule.operator;

                                const activeInput = row.querySelector(`.rule-value-${rule.field === 'category_id' ? 'category' : rule.field === 'brand_id' ? 'brand' : rule.field === 'price' ? 'price' : 'text'}`);
                                if (activeInput) activeInput.value = rule.value;
                            });
                        } else if (col.type === 'automatic') {
                            addRuleRow();
                        }

                        errorsContainer.style.display = 'none';
                        collectionModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading collection:', err));
            });
        });

        // Delete modal
        const deleteCollectionModal = document.getElementById('deleteCollectionModal');
        let deleteCollectionId = null;

        document.querySelectorAll('.btn-delete-collection').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteCollectionId = btn.dataset.id;
                document.getElementById('delCollectionName').textContent = btn.dataset.name;
                document.getElementById('delCollectionSlug').textContent = '/' + btn.dataset.slug;
                document.getElementById('delCollectionAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteCollectionModal.classList.add('active');
            });
        });

        document.getElementById('delCollectionCancel').addEventListener('click', () =>
            deleteCollectionModal.classList.remove('active'));
        deleteCollectionModal.addEventListener('click', (e) => {
            if (e.target === deleteCollectionModal) deleteCollectionModal.classList.remove('active');
        });

        document.getElementById('delCollectionConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${collectionUrl}/eliminar/${deleteCollectionId}`, {
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
                    deleteCollectionModal.classList.remove('active');
                    queueCenterToast('Colección eliminada correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    showCenterToast(data.message ?? 'No se pudo eliminar la colección.', 'error');
                    deleteCollectionModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error deleting collection:', err);
                showCenterToast('Error de conexión al eliminar la colección.', 'error');
            }
        });
    </script>
@endpush
