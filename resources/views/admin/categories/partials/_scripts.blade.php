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
            document.getElementById('categorySlug').value = '';
            document.getElementById('categorySortOrder').value = '1';
            document.getElementById('categoryIsActive').value = '1';
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';
            currentCategoryId = null;
            isEditMode = false;
            document.getElementById('categoryModalTitle').textContent = 'Nueva Categoría';
            document.getElementById('categorySubmitBtn').textContent = 'Crear Categoría';

            // Forzar el cambio de nivel para que oculte/bloquee el selector de padres al iniciar limpio
            document.getElementById('categoryLevel').dispatchEvent(new Event('change'));
        };

        // Open create modal
        document.getElementById('btnNewCategory').addEventListener('click', () => {
            resetCategoryForm();
            categoryModal.style.display = 'flex';
        });

        // Close modal events
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

        // --- LISTENERS REACTIVOS PARA EL SLUG Y FILTROS DEL FORMULARIO ---

        // Al escribir el nombre: Solo auto-genera si NO está editando (para proteger SEO)
        document.getElementById('categoryName').addEventListener('input', function() {
            if (!isEditMode) buildSlug();
        });

        // Al cambiar de padre: Se ejecuta SIEMPRE (incluso editando) porque la estructura cambió
        document.getElementById('categoryParent').addEventListener('change', function() {
            buildSlug();
        });

        // Al cambiar de nivel: Filtra las opciones de padre, bloquea/desbloquea y recalcula el slug
        document.getElementById('categoryLevel').addEventListener('change', function() {
            const level = parseInt(this.value);
            const parent = document.getElementById('categoryParent');
            const options = parent.querySelectorAll('option[data-level]');

            // 1. Mostrar/ocultar los padres válidos según el nivel jerárquico
            options.forEach(opt => {
                const optLevel = parseInt(opt.dataset.level);
                opt.style.display = (level === 2 && optLevel === 1) ||
                    (level === 3 && optLevel === 2) ? '' : 'none';
            });

            // 2. Si el nivel pasa a ser Principal (1), bloqueamos el selector de padres
            parent.disabled = level === 1;

            // 3. Resetear el valor seleccionado del padre si es un cambio manual del usuario
            // (Si estamos cargando el formulario en edición, evitamos romper el valor original)
            if (document.activeElement === this) {
                parent.value = '';
            }

            // 4. Recalcular la ruta del slug en base a la nueva realidad del nivel
            buildSlug();
        });


        // Submit form (create or update)
        categoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorsContainer.style.display = 'none';
            errorsContainer.innerHTML = '';

            const formData = new FormData(categoryForm);

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
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit Mode Loader
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

                        const level = cat.parent_id ? (cat.parent?.parent_id ? 3 : 2) : 1;
                        const levelSelect = document.getElementById('categoryLevel');
                        const parentSelect = document.getElementById('categoryParent');

                        // Asignamos el nivel y disparamos de forma controlada el filtro visual de las opciones
                        levelSelect.value = level;
                        levelSelect.dispatchEvent(new Event('change'));

                        // Insertamos el parent_id real de la categoría a editar
                        parentSelect.value = cat.parent_id ?? '';

                        errorsContainer.style.display = 'none';
                        categoryModal.style.display = 'flex';
                    })
                    .catch(err => console.error('Error loading category:', err));
            });
        });

        // Delete action handlers
        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        let deleteCategoryId = null;

        document.querySelectorAll('.btn-delete-category').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteCategoryId = btn.dataset.id;
                document.getElementById('delCategoryName').textContent = btn.dataset.name;
                document.getElementById('delCategorySlug').textContent = '/' + btn.dataset.slug;
                document.getElementById('delCategoryAvatar').textContent = btn.dataset.name.charAt(0)
                    .toUpperCase();
                deleteCategoryModal.classList.add('active');
            });
        });

        document.getElementById('delCategoryCancel').addEventListener('click', () => deleteCategoryModal.classList.remove(
            'active'));
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

        // Index Table Search and filtering
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

        // Tree structure toggle rows view
        document.querySelectorAll('[data-toggle]').forEach(function(el) {
            el.addEventListener('click', function() {
                const parentRow = this.closest('tr');
                const allRows = Array.from(document.querySelectorAll('#categoriesTable tbody tr'));
                const parentIndex = allRows.indexOf(parentRow);
                const parentLevel = parseInt(parentRow.dataset.level);

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
