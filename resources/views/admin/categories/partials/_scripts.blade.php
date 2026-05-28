@push('scripts')
<script>
    const categoryUrl    = '{{ url('/admin/categorias') }}';
    const categoryModal  = document.getElementById('categoryModal');
    const categoryForm   = document.getElementById('categoryForm');
    const errorsContainer = document.getElementById('category-modal-errors');
    let currentCategoryId = null;
    let isEditMode        = false;

    // Close modal with animation
    const closeCategoryWithAnim = () => {
        const content = categoryModal.querySelector('.user-manager-modal-content');
        if (content) {
            content.style.transition = 'transform 0.2s ease-in';
            content.style.transform  = 'translateX(100%)';
        }
        categoryModal.style.transition = 'opacity 0.2s ease-in';
        categoryModal.style.opacity    = '0';
        setTimeout(() => {
            categoryModal.style.display    = 'none';
            categoryModal.style.opacity    = '';
            categoryModal.style.transition = '';
            if (content) { content.style.transform = ''; content.style.transition = ''; }
        }, 200);
    };

    // Reset form
    const resetCategoryForm = () => {
        categoryForm.reset();
        document.getElementById('categorySlug').value      = '';
        document.getElementById('categorySortOrder').value = '1';
        document.getElementById('categoryIsActive').value  = '1';
        errorsContainer.style.display = 'none';
        errorsContainer.innerHTML     = '';
        currentCategoryId = null;
        isEditMode        = false;
        document.getElementById('categoryModalTitle').textContent  = 'Nueva Categoría';
        document.getElementById('categorySubmitBtn').textContent   = 'Crear Categoría';
    };

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

    // Auto-generate slug from name
    document.getElementById('categoryName').addEventListener('input', function () {
        if (!isEditMode) {
            const slug = this.value.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-');
            document.getElementById('categorySlug').value = slug;
        }
    });

    // Filter parent options by level
    document.getElementById('categoryLevel').addEventListener('change', function () {
        const level   = parseInt(this.value);
        const parent  = document.getElementById('categoryParent');
        const options = parent.querySelectorAll('option[data-level]');

        options.forEach(opt => {
            const optLevel = parseInt(opt.dataset.level);
            opt.style.display = (level === 2 && optLevel === 1) ||
                                 (level === 3 && optLevel === 2) ? '' : 'none';
        });

        parent.value = '';
        parent.disabled = level === 1;
    });

    // Submit form (create or update)
    categoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorsContainer.style.display = 'none';
        errorsContainer.innerHTML     = '';

        const formData = new FormData(categoryForm);
        const url      = isEditMode
            ? `${categoryUrl}/editar/${currentCategoryId}`
            : `${categoryUrl}/crear`;

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
                errorsContainer.innerHTML     = errorList.map(m => `<p>${m}</p>`).join('');
                errorsContainer.style.display = 'block';
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
            isEditMode        = true;

            fetch(`${categoryUrl}/editar/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            .then(res => res.json())
            .then(cat => {
                document.getElementById('categoryModalTitle').textContent = 'Editar Categoría';
                document.getElementById('categorySubmitBtn').textContent  = 'Guardar Cambios';
                document.getElementById('categoryName').value        = cat.name        ?? '';
                document.getElementById('categorySlug').value        = cat.slug        ?? '';
                document.getElementById('categoryDescription').value = cat.description ?? '';
                document.getElementById('categoryImageUrl').value    = cat.image_url   ?? '';
                document.getElementById('categorySortOrder').value   = cat.sort_order  ?? 0;
                document.getElementById('categoryIsActive').value    = cat.is_active   ? '1' : '0';
                document.getElementById('categoryParent').value      = cat.parent_id   ?? '';
                errorsContainer.style.display = 'none';
                categoryModal.style.display   = 'flex';
            })
            .catch(err => console.error('Error loading category:', err));
        });
    });

    // Delete modal
    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
    let deleteCategoryId      = null;

    document.querySelectorAll('.btn-delete-category').forEach(btn => {
        btn.addEventListener('click', () => {
            deleteCategoryId = btn.dataset.id;
            document.getElementById('delCategoryName').textContent  = btn.dataset.name;
            document.getElementById('delCategorySlug').textContent  = '/' + btn.dataset.slug;
            document.getElementById('delCategoryAvatar').textContent =
                btn.dataset.name.charAt(0).toUpperCase();
            deleteCategoryModal.classList.add('active');
        });
    });

    document.getElementById('delCategoryCancel').addEventListener('click', () =>
        deleteCategoryModal.classList.remove('active'));
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
                body: JSON.stringify({ _method: 'DELETE' }),
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
    const categorySearch      = document.getElementById('categorySearch');
    const categoryLevelFilter = document.getElementById('categoryLevelFilter');

    const filterCategories = () => {
        const search = categorySearch.value.toLowerCase().trim();
        const level  = categoryLevelFilter.value;

        document.querySelectorAll('.category-row').forEach(row => {
            const name      = row.dataset.name  ?? '';
            const rowLevel  = row.dataset.level ?? '';
            const matchName  = !search || name.includes(search);
            const matchLevel = level === 'all' || rowLevel === level;
            row.style.display = matchName && matchLevel ? '' : 'none';
        });
    };

    categorySearch.addEventListener('input', filterCategories);
    categoryLevelFilter.addEventListener('change', filterCategories);
</script>
@endpush
