@push('scripts')
    <script>
        const categoryUrl = '{{ url('/admin/categorias') }}';
        const categoryModal = document.getElementById('categoryModal');
        const categoryForm = document.getElementById('categoryForm');
        const errorsContainer = document.getElementById('category-modal-errors');
        let currentCategoryId = null;
        let isEditMode = false;

        // Close modal with animation
        const closeCategoryWithAnim = () => {
            const content = categoryModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            categoryModal.style.transition = 'opacity 0.2s ease-in';
            categoryModal.style.opacity = '0';
            setTimeout(() => {
                categoryModal.style.display = 'none';
                categoryModal.style.opacity = '';
                categoryModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        // Reset form
        const resetCategoryForm = () => {
            categoryForm.reset();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            document.getElementById('categorySlug').value = '';
            document.getElementById('categorySortOrder').value = '1';
            document.getElementById('categoryIsActive').value = '1';
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';
            currentCategoryId = null;
            isEditMode = false;
            document.getElementById('categoryModalTitle').textContent = 'Nueva Categoría';
            document.getElementById('categorySubmitBtn').textContent = 'Crear Categoría';

            resetCategoryFaqs();

            document.getElementById('categoryLevel').dispatchEvent(new Event('change'));
        };

        /* ── FAQ de la categoría ──
           Names indexados faq_items[N][question|answer]; el campo vive
           dentro de #categoryForm así que viaja en el FormData sin
           necesitar el atributo form="". Sin filas al enviar, el backend
           guarda faqs = null (mismo patrón que products/collections). */
        const categoryAddFaqBtn = document.getElementById('categoryAddFaq');
        const categoryFaqList = document.getElementById('categoryFaqList');
        const categoryFaqEmpty = document.getElementById('categoryFaqEmpty');

        function reindexCategoryFaqRows() {
            Array.from(categoryFaqList.querySelectorAll('.category-faq-row')).forEach(function(row, i) {
                row.querySelector('.category-faq-question').name = 'faq_items[' + i + '][question]';
                row.querySelector('.category-faq-answer').name = 'faq_items[' + i + '][answer]';
            });
        }

        function addCategoryFaqRow(question = '', answer = '') {
            categoryFaqEmpty.style.display = 'none';
            categoryFaqList.style.display = 'flex';

            const row = document.createElement('div');
            row.className = 'category-faq-row';
            row.style.cssText = 'display:flex;gap:8px;align-items:flex-start;';
            row.innerHTML =
                '<div style="flex:1;display:flex;flex-direction:column;gap:6px;">' +
                '<input type="text" class="users-manager-input category-faq-question" placeholder="Pregunta (ej: ¿Qué garantía tiene?)">' +
                '<textarea class="users-manager-input client-modal-textarea category-faq-answer" rows="2" placeholder="Respuesta"></textarea>' +
                '</div>' +
                '<button type="button" class="table-users-manager-action-btn delete category-faq-del" title="Eliminar" style="flex-shrink:0;">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' +
                '</svg>' +
                '</button>';

            row.querySelector('.category-faq-question').value = question;
            row.querySelector('.category-faq-answer').value = answer;

            row.querySelector('.category-faq-del').addEventListener('click', function() {
                row.remove();
                reindexCategoryFaqRows();
                if (categoryFaqList.children.length === 0) {
                    categoryFaqList.style.display = 'none';
                    categoryFaqEmpty.style.display = 'block';
                }
            });

            categoryFaqList.appendChild(row);
            reindexCategoryFaqRows();
        }

        function resetCategoryFaqs() {
            categoryFaqList.innerHTML = '';
            categoryFaqList.style.display = 'none';
            categoryFaqEmpty.style.display = 'block';
        }

        categoryAddFaqBtn.addEventListener('click', () => addCategoryFaqRow());

        // Open create modal
        document.getElementById('btnNewCategory').addEventListener('click', () => {
            resetCategoryForm();
            categoryModal.style.display = 'flex';
        });

        // Close modal
        document.getElementById('closeCategoryModal').addEventListener('click', () => closeCategoryWithAnim());
        document.getElementById('cancelCategoryModal').addEventListener('click', () => closeCategoryWithAnim());
        categoryModal.addEventListener('click', (e) => {
            if (e.target === categoryModal) closeCategoryWithAnim();
        });

        // Helper to normalize strings into slug segments
        function toSlugSegment(value) {
            return value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-');
        }

        // Core function to build hierarchy slugs
        function buildSlug() {
            const level = parseInt(document.getElementById('categoryLevel').value);
            const name = document.getElementById('categoryName').value;
            const parentSel = document.getElementById('categoryParent');
            const nameSlug = toSlugSegment(name);

            if (level === 1) {
                document.getElementById('categorySlug').value = nameSlug;
                return;
            }

            const parentOption = parentSel.options[parentSel.selectedIndex];

            if (!parentOption || !parentOption.value) {
                document.getElementById('categorySlug').value = nameSlug;
                return;
            }

            const parentName = parentOption.text.replace(/^[—\s]+/, '').trim();
            const parentSlug = toSlugSegment(parentName);

            if (level === 2) {
                document.getElementById('categorySlug').value = `${parentSlug}/${nameSlug}`;
            } else if (level === 3) {
                const grandparentSlug = parentOption.dataset.parentSlug ?? '';
                const prefix = grandparentSlug ? `${grandparentSlug}/${parentSlug}` : parentSlug;
                document.getElementById('categorySlug').value = `${prefix}/${nameSlug}`;
            }
        }

        document.getElementById('categoryName').addEventListener('input', function() {
            if (!isEditMode) {
                const slug = this.value.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim().replace(/\s+/g, '-');
                document.getElementById('categorySlug').value = slug;
            }
        });

        document.getElementById('categoryParent').addEventListener('change', function() {
            syncCategoryParentDisplay();
            buildSlug();
        });

        document.getElementById('categoryLevel').addEventListener('change', function() {
            const level = parseInt(this.value);
            const parent = document.getElementById('categoryParent');
            const options = parent.querySelectorAll('option[data-level]');

            options.forEach(opt => {
                const optLevel = parseInt(opt.dataset.level);
                opt.style.display = (level === 2 && optLevel === 1) ||
                    (level === 3 && optLevel === 2) ? '' : 'none';
            });

            parent.disabled = level === 1;
            document.getElementById('categoryParentSearch').disabled = level === 1;

            if (document.activeElement === this) {
                parent.value = '';
            }

            syncCategoryParentDisplay();
            buildSlug();
        });

        // Combobox de búsqueda para "Categoría Padre": el <select> original
        // sigue siendo la fuente de la verdad (mismas <option data-level>,
        // sigue enviándose en el FormData); esto solo agrega una capa de
        // búsqueda/estilo encima para no duplicar la lógica de niveles.
        const categoryParentSelect = document.getElementById('categoryParent');
        const categoryParentSearch = document.getElementById('categoryParentSearch');
        const categoryParentList = document.getElementById('categoryParentList');

        function getVisibleParentOptions() {
            return Array.from(categoryParentSelect.options).filter(opt => opt.style.display !== 'none');
        }

        function renderParentList(term = '') {
            const needle = term.toLowerCase().trim();
            categoryParentList.innerHTML = '';

            const matches = getVisibleParentOptions().filter(opt =>
                !needle || opt.text.toLowerCase().includes(needle)
            );

            if (matches.length === 0) {
                const li = document.createElement('li');
                li.className = 'cat-combobox-empty';
                li.textContent = 'Sin resultados';
                categoryParentList.appendChild(li);
                return;
            }

            matches.forEach(opt => {
                const li = document.createElement('li');
                li.className = 'cat-combobox-option' + (opt.value === categoryParentSelect.value ? ' active' : '');
                li.textContent = opt.value ? opt.text.replace(/^[—\s]+/, '') : opt.text;
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    categoryParentSelect.value = opt.value;
                    categoryParentSelect.dispatchEvent(new Event('change'));
                    closeParentList();
                });
                categoryParentList.appendChild(li);
            });
        }

        function openParentList() {
            if (categoryParentSelect.disabled) return;
            renderParentList(categoryParentSearch.value);
            categoryParentList.classList.add('open');
        }

        function closeParentList() {
            categoryParentList.classList.remove('open');
        }

        function syncCategoryParentDisplay() {
            const opt = categoryParentSelect.options[categoryParentSelect.selectedIndex];
            categoryParentSearch.value = (opt && opt.value) ? opt.text.replace(/^[—\s]+/, '') : '';
        }

        categoryParentSearch.addEventListener('focus', openParentList);
        categoryParentSearch.addEventListener('input', () => {
            if (!categoryParentSearch.value.trim()) {
                categoryParentSelect.value = '';
            }
            openParentList();
        });
        categoryParentSearch.addEventListener('blur', () => {
            setTimeout(() => {
                closeParentList();
                syncCategoryParentDisplay();
            }, 120);
        });

        // SUBMIT
        categoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            errorsContainer.style.display = 'none';

            let hasErrors = false;

            const nameInput = document.getElementById('categoryName');
            const parentSelect = document.getElementById('categoryParent');
            const level = parseInt(document.getElementById('categoryLevel').value);

            const showError = (element, message) => {
                element.classList.add('is-invalid');

                const errorSpan = document.createElement('span');
                errorSpan.className = 'field-error-msg';
                errorSpan.innerText = message;

                const container = element.closest('.mb-3') || element.closest('.mb-4') || element.parentElement;
                if (container) {
                    container.appendChild(errorSpan);
                }
                hasErrors = true;
            };

            // Validaciones locales
            if (!nameInput.value.trim()) {
                showError(nameInput, 'El nombre de la categoría es obligatorio.');
            }

            if (level > 1 && !parentSelect.value) {
                showError(categoryParentSearch, 'Debes seleccionar una categoría padre para este nivel.');
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

            // --- Envío Fetch ---
            const formData = new FormData(categoryForm);

            // Fix — ensure parent_id is empty for level 1
            if (document.getElementById('categoryLevel').value === '1') {
                formData.set('parent_id', '');
            }

            const url = isEditMode ?
                `${categoryUrl}/editar/${currentCategoryId}` :
                `${categoryUrl}/crear`;

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
                    closeCategoryWithAnim();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    errorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    errorsContainer.style.display = 'block';

                    // Asignar errores devueltos por el servidor a los inputs correspondientes
                    if (data.errors.name) showError(nameInput, data.errors.name[0]);
                    if (data.errors.parent_id) showError(categoryParentSearch, data.errors.parent_id[0]);
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit
        document.querySelectorAll('.btn-edit-category').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                currentCategoryId = id;
                isEditMode = true;

                fetch(`${categoryUrl}/editar/${id}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(cat => {
                        document.getElementById('categoryModalTitle').textContent = 'Editar Categoría';
                        document.getElementById('categorySubmitBtn').textContent = 'Guardar Cambios';
                        document.getElementById('categoryName').value = cat.name ?? '';
                        document.getElementById('categorySlug').value = cat.slug ?? '';
                        document.getElementById('categoryDescription').value = cat.description ?? '';
                        document.getElementById('categoryImageUrl').value = cat.image_url ?? '';
                        document.getElementById('categorySortOrder').value = cat.sort_order ?? 0;
                        document.getElementById('categoryIsActive').value = cat.is_active ? '1' : '0';
                        document.getElementById('categorySeoTitle').value = cat.seo_title ?? '';
                        document.getElementById('categorySeoDesc').value = cat.seo_description ?? '';

                        resetCategoryFaqs();
                        (cat.faqs ?? []).forEach(faq => addCategoryFaqRow(faq.question ?? '', faq.answer ?? ''));

                        const level = cat.parent_id ?
                            (cat.parent?.parent_id ? 3 : 2) :
                            1;

                        const levelSelect = document.getElementById('categoryLevel');
                        const parentSelect = document.getElementById('categoryParent');

                        levelSelect.value = level;
                        // FIX: dispatch 'change' so the level-based option
                        // filtering (which options are hidden per level) is
                        // actually applied when opening edit mode — it was
                        // never triggered before, only on manual level switches.
                        levelSelect.dispatchEvent(new Event('change'));

                        parentSelect.value = cat.parent_id ?? '';
                        syncCategoryParentDisplay();

                        errorsContainer.style.display = 'none';
                        categoryModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading category:', err));
            });
        });

        // Delete modal
        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        let deleteCategoryId = null;

        document.querySelectorAll('.btn-delete-category').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteCategoryId = btn.dataset.id;
                document.getElementById('delCategoryName').textContent = btn.dataset.name;
                document.getElementById('delCategorySlug').textContent = '/' + btn.dataset.slug;
                document.getElementById('delCategoryAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteCategoryModal.classList.add('active');
            });
        });

        document.getElementById('delCategoryCancel').addEventListener('click', () => deleteCategoryModal.classList.remove('active'));
        deleteCategoryModal.addEventListener('click', (e) => {
            if (e.target === deleteCategoryModal) deleteCategoryModal.classList.remove('active');
        });

        document.getElementById('delCategoryConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${categoryUrl}/eliminar/${deleteCategoryId}`, {
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
                    deleteCategoryModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la categoría.');
                    deleteCategoryModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error deleting category:', err);
            }
        });

        // Search and filter
        const categorySearch = document.getElementById('categorySearch');
        const categoryLevelFilter = document.getElementById('categoryLevelFilter');
        const categoryStatusFilter = document.getElementById('categoryStatusFilter');
        const btnFilter = document.getElementById('btnFilter');

        const filterCategories = () => {
            const search = categorySearch.value.toLowerCase().trim();
            const level = categoryLevelFilter.value;
            const status = categoryStatusFilter.value;

            document.querySelectorAll('.cat-row').forEach(row => {
                const name = row.dataset.name ?? '';
                const rowLevel = row.dataset.level ?? '';
                const rowStatus = row.dataset.status ?? '';

                const matchSearch = !search || name.includes(search);
                const matchLevel = level === 'all' || rowLevel === level;
                const matchStatus = status === 'all' || rowStatus === status;

                row.style.display = matchSearch && matchLevel && matchStatus ? '' : 'none';
            });
        };

        categorySearch.addEventListener('input', filterCategories);
        categoryLevelFilter.addEventListener('change', filterCategories);
        categoryStatusFilter.addEventListener('change', filterCategories);
        btnFilter.addEventListener('click', filterCategories);


        categorySearch.addEventListener('input', filterCategories);
        categoryLevelFilter.addEventListener('change', filterCategories);

        document.querySelectorAll('[data-toggle]').forEach(function(el) {
            el.addEventListener('click', function() {
                const targetId = this.dataset.toggle;
                const parentRow = this.closest('tr');
                const allRows = Array.from(document.querySelectorAll('#categoriesTable tbody tr'));
                const parentIndex = allRows.indexOf(parentRow);
                const parentLevel = parseInt(parentRow.dataset.level);

                // Find all descendant rows
                let i = parentIndex + 1;
                while (i < allRows.length) {
                    const rowLevel = parseInt(allRows[i].dataset.level);
                    if (rowLevel <= parentLevel) break;
                    const current = allRows[i];
                    const isHidden = current.style.display === 'none';
                    current.style.display = isHidden ? '' : 'none';
                    i++;
                }
            });
        });
    </script>
@endpush
