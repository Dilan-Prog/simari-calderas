@push('scripts')
    <script>
        const menuBaseUrl = '{{ url('/admin/menus') }}';

        /* ============================================================
           Menú (create / rename) — solo actúa si #menuModal existe,
           es decir, cuando este partial se incluye desde index.blade.php
           ============================================================ */
        (function() {
            const menuModal = document.getElementById('menuModal');
            if (!menuModal) return;

            const menuForm = document.getElementById('menuForm');
            const errorsContainer = document.getElementById('menu-modal-errors');
            let currentMenuId = null;
            let isEditMode = false;

            const closeMenuModalWithAnim = () => {
                const content = menuModal.querySelector('.user-manager-modal-content');
                if (content) {
                    content.style.transition = 'transform 0.2s ease-in';
                    content.style.transform = 'translateX(100%)';
                }
                menuModal.style.transition = 'opacity 0.2s ease-in';
                menuModal.style.opacity = '0';
                setTimeout(() => {
                    menuModal.style.display = 'none';
                    menuModal.style.opacity = '';
                    menuModal.style.transition = '';
                    if (content) {
                        content.style.transform = '';
                        content.style.transition = '';
                    }
                }, 200);
            };

            const resetMenuForm = () => {
                menuForm.reset();
                document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                errorsContainer.style.display = 'none';
                errorsContainer.innerHTML = '';
                currentMenuId = null;
                isEditMode = false;
                document.getElementById('menuModalTitle').textContent = 'Nuevo Menú';
                document.getElementById('menuSubmitBtn').textContent = 'Crear Menú';
                document.getElementById('menuIsActive').value = '1';
            };

            document.getElementById('btnNewMenu').addEventListener('click', () => {
                resetMenuForm();
                menuModal.style.display = 'flex';
            });

            document.getElementById('closeMenuModal').addEventListener('click', () => closeMenuModalWithAnim());
            document.getElementById('cancelMenuModal').addEventListener('click', () => closeMenuModalWithAnim());
            menuModal.addEventListener('click', (e) => {
                if (e.target === menuModal) closeMenuModalWithAnim();
            });

            menuForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                errorsContainer.style.display = 'none';

                const showError = (element, message) => {
                    element.classList.add('is-invalid');
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'field-error-msg';
                    errorSpan.innerText = message;
                    const container = element.closest('.users-manager-email-camp') || element.parentElement;
                    if (container) container.appendChild(errorSpan);
                };

                const nameInput = document.getElementById('menuName');
                if (!nameInput.value.trim()) {
                    showError(nameInput, 'El nombre del menú es obligatorio.');
                    nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                const formData = new FormData(menuForm);
                const url = isEditMode ? `${menuBaseUrl}/${currentMenuId}/editar` : `${menuBaseUrl}/nuevo`;
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
                        return;
                    }

                    const data = await response.json();

                    if (response.ok) {
                        closeMenuModalWithAnim();
                        setTimeout(() => window.location.reload(), 200);
                    } else if (response.status === 422) {
                        const errorList = Object.values(data.errors).flat();
                        errorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                        errorsContainer.style.display = 'block';

                        // Loop dinámico sobre data.errors en vez de solo revisar
                        // 'name' — así 'location' (y cualquier otra regla que se
                        // agregue después en MenuController::store()/update())
                        // también marca su campo en rojo. querySelector se limita
                        // a menuForm, así que solo puede marcar campos que existen
                        // en ESTE formulario (no cruza con el de menuItemForm).
                        Object.keys(data.errors).forEach(field => {
                            const input = menuForm.querySelector(`[name="${field}"]`);
                            if (input) showError(input, data.errors[field][0]);
                        });
                    }
                } catch (err) {
                    console.error('Error saving menu:', err);
                }
            });

            document.querySelectorAll('.btn-edit-menu').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentMenuId = btn.dataset.id;
                    isEditMode = true;

                    document.getElementById('menuModalTitle').textContent = 'Editar Menú';
                    document.getElementById('menuSubmitBtn').textContent = 'Guardar Cambios';
                    document.getElementById('menuName').value = btn.dataset.name ?? '';
                    document.getElementById('menuLocation').value = btn.dataset.location ?? '';
                    document.getElementById('menuIsActive').value = btn.dataset.active === '1' ? '1' : '0';

                    errorsContainer.style.display = 'none';
                    menuModal.style.display = 'flex';
                });
            });
        })();

        /* ============================================================
           Menú — eliminar
           ============================================================ */
        (function() {
            const deleteMenuModal = document.getElementById('deleteMenuModal');
            if (!deleteMenuModal) return;

            let deleteMenuId = null;

            document.querySelectorAll('.btn-delete-menu').forEach(btn => {
                btn.addEventListener('click', () => {
                    deleteMenuId = btn.dataset.id;
                    document.getElementById('delMenuName').textContent = btn.dataset.name;
                    document.getElementById('delMenuAvatar').textContent = btn.dataset.name.charAt(0).toUpperCase();
                    deleteMenuModal.classList.add('active');
                });
            });

            document.getElementById('delMenuCancel').addEventListener('click', () => deleteMenuModal.classList.remove('active'));
            deleteMenuModal.addEventListener('click', (e) => {
                if (e.target === deleteMenuModal) deleteMenuModal.classList.remove('active');
            });

            document.getElementById('delMenuConfirm').addEventListener('click', async () => {
                try {
                    const response = await fetch(`${menuBaseUrl}/${deleteMenuId}/eliminar`, {
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
                        deleteMenuModal.classList.remove('active');
                        setTimeout(() => window.location.reload(), 200);
                    } else {
                        alert(data.message ?? 'No se pudo eliminar el menú.');
                        deleteMenuModal.classList.remove('active');
                    }
                } catch (err) {
                    console.error('Error deleting menu:', err);
                }
            });
        })();

        /* ============================================================
           Elemento de menú (crear / editar / agregar hijo) — solo actúa
           si #menuItemModal existe (incluido desde edit.blade.php)
           ============================================================ */
        (function() {
            const itemModal = document.getElementById('menuItemModal');
            if (!itemModal) return;

            const rootList = document.getElementById('menuRootList');
            const currentMenuId = rootList ? rootList.dataset.menuId : null;
            const itemsUrl = `${menuBaseUrl}/${currentMenuId}/items`;

            const itemForm = document.getElementById('menuItemForm');
            const errorsContainer = document.getElementById('menu-item-modal-errors');
            const parentGroup = document.getElementById('menuItemParentGroup');
            const parentSelect = document.getElementById('menuItemParent');

            let currentItemId = null;
            let fixedParentId = null;

            const closeItemModalWithAnim = () => {
                const content = itemModal.querySelector('.user-manager-modal-content');
                if (content) {
                    content.style.transition = 'transform 0.2s ease-in';
                    content.style.transform = 'translateX(100%)';
                }
                itemModal.style.transition = 'opacity 0.2s ease-in';
                itemModal.style.opacity = '0';
                setTimeout(() => {
                    itemModal.style.display = 'none';
                    itemModal.style.opacity = '';
                    itemModal.style.transition = '';
                    if (content) {
                        content.style.transform = '';
                        content.style.transition = '';
                    }
                }, 200);
            };

            const resetItemForm = () => {
                itemForm.reset();
                document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                errorsContainer.style.display = 'none';
                errorsContainer.innerHTML = '';
                currentItemId = null;
                fixedParentId = null;
                parentGroup.style.display = '';
                parentSelect.value = '';
                document.getElementById('menuItemTarget').value = '_self';
                document.getElementById('menuItemIsActive').value = '1';
                document.getElementById('menuItemSortOrder').value = '0';

                Array.from(parentSelect.options).forEach(opt => opt.disabled = false);
            };

            // Agregar elemento raíz
            const btnNewRootItem = document.getElementById('btnNewRootItem');
            if (btnNewRootItem) {
                btnNewRootItem.addEventListener('click', () => {
                    resetItemForm();
                    document.getElementById('menuItemModalTitle').textContent = 'Nuevo Elemento';
                    document.getElementById('menuItemSubmitBtn').textContent = 'Crear Elemento';
                    itemModal.style.display = 'flex';
                });
            }

            // Agregar elemento hijo (contextual — no requiere elegir padre)
            document.querySelectorAll('.btn-add-child').forEach(btn => {
                btn.addEventListener('click', () => {
                    resetItemForm();
                    fixedParentId = btn.dataset.parentId;
                    parentSelect.value = fixedParentId;
                    parentGroup.style.display = 'none';
                    document.getElementById('menuItemModalTitle').textContent =
                        `Nuevo elemento hijo de "${btn.dataset.parentTitle}"`;
                    document.getElementById('menuItemSubmitBtn').textContent = 'Crear Elemento';
                    itemModal.style.display = 'flex';
                });
            });

            // Editar elemento
            document.querySelectorAll('.btn-edit-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    resetItemForm();
                    currentItemId = btn.dataset.id;

                    document.getElementById('menuItemModalTitle').textContent = 'Editar Elemento';
                    document.getElementById('menuItemSubmitBtn').textContent = 'Guardar Cambios';
                    document.getElementById('menuItemTitle').value = btn.dataset.title ?? '';
                    document.getElementById('menuItemUrl').value = btn.dataset.url ?? '';
                    document.getElementById('menuItemTarget').value = btn.dataset.target || '_self';
                    document.getElementById('menuItemSortOrder').value = btn.dataset.sortOrder ?? 0;
                    document.getElementById('menuItemIsActive').value = btn.dataset.active === '1' ? '1' : '0';

                    parentGroup.style.display = '';
                    parentSelect.value = btn.dataset.parentId || '';

                    // Un elemento no puede ser su propio padre
                    Array.from(parentSelect.options).forEach(opt => {
                        opt.disabled = opt.value !== '' && opt.value === String(currentItemId);
                    });

                    errorsContainer.style.display = 'none';
                    itemModal.style.display = 'flex';
                });
            });

            document.getElementById('closeMenuItemModal').addEventListener('click', () => closeItemModalWithAnim());
            document.getElementById('cancelMenuItemModal').addEventListener('click', () => closeItemModalWithAnim());
            itemModal.addEventListener('click', (e) => {
                if (e.target === itemModal) closeItemModalWithAnim();
            });

            itemForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                errorsContainer.style.display = 'none';

                const showError = (element, message) => {
                    element.classList.add('is-invalid');
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'field-error-msg';
                    errorSpan.innerText = message;
                    const container = element.closest('.users-manager-email-camp') || element.parentElement;
                    if (container) container.appendChild(errorSpan);
                };

                const titleInput = document.getElementById('menuItemTitle');
                if (!titleInput.value.trim()) {
                    showError(titleInput, 'El título es obligatorio.');
                    titleInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (fixedParentId !== null) {
                    parentSelect.value = fixedParentId;
                }

                const formData = new FormData(itemForm);
                const isEditMode = currentItemId !== null;
                const url = isEditMode ? `${itemsUrl}/${currentItemId}` : itemsUrl;
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
                        return;
                    }

                    const data = await response.json();

                    if (response.ok) {
                        closeItemModalWithAnim();
                        setTimeout(() => window.location.reload(), 200);
                    } else if (response.status === 422) {
                        const errorList = Object.values(data.errors).flat();
                        errorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                        errorsContainer.style.display = 'block';

                        // Loop dinámico sobre data.errors en vez de solo revisar
                        // 'title'/'parent_id' — así 'url' y 'sort_order' (y
                        // cualquier otra regla que se agregue después en
                        // MenuController::storeItem()/updateItem()) también
                        // marcan su campo en rojo. querySelector se limita a
                        // itemForm, así que solo puede marcar campos que existen
                        // en ESTE formulario (no cruza con el de menuForm).
                        Object.keys(data.errors).forEach(field => {
                            const input = itemForm.querySelector(`[name="${field}"]`);
                            if (input) showError(input, data.errors[field][0]);
                        });
                    }
                } catch (err) {
                    console.error('Error saving menu item:', err);
                }
            });
        })();

        /* ============================================================
           Elemento de menú — eliminar
           ============================================================ */
        (function() {
            const deleteItemModal = document.getElementById('deleteMenuItemModal');
            if (!deleteItemModal) return;

            const rootList = document.getElementById('menuRootList');
            const currentMenuId = rootList ? rootList.dataset.menuId : null;
            const itemsUrl = `${menuBaseUrl}/${currentMenuId}/items`;

            let deleteItemId = null;

            document.querySelectorAll('.btn-delete-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    deleteItemId = btn.dataset.id;
                    document.getElementById('delMenuItemName').textContent = btn.dataset.title;
                    document.getElementById('delMenuItemAvatar').textContent = btn.dataset.title.charAt(0).toUpperCase();
                    deleteItemModal.classList.add('active');
                });
            });

            document.getElementById('delMenuItemCancel').addEventListener('click', () => deleteItemModal.classList.remove('active'));
            deleteItemModal.addEventListener('click', (e) => {
                if (e.target === deleteItemModal) deleteItemModal.classList.remove('active');
            });

            document.getElementById('delMenuItemConfirm').addEventListener('click', async () => {
                try {
                    const response = await fetch(`${itemsUrl}/${deleteItemId}`, {
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
                        deleteItemModal.classList.remove('active');
                        setTimeout(() => window.location.reload(), 200);
                    } else {
                        alert(data.message ?? 'No se pudo eliminar el elemento.');
                        deleteItemModal.classList.remove('active');
                    }
                } catch (err) {
                    console.error('Error deleting menu item:', err);
                }
            });
        })();

        /* ============================================================
           Árbol de elementos: arrastrar para reordenar, guardado
           instantáneo vía AJAX. Mismo patrón que
           admin/products/edit_product/_scripts.blade.php
           ("Existing images: drag to reorder"), adaptado a una lista
           anidada de 2 niveles — cada nivel (raíz / hijos de un mismo
           padre) reordena de forma independiente.
           ============================================================ */
        (function() {
            const rootList = document.getElementById('menuRootList');
            if (!rootList) return;

            const currentMenuId = rootList.dataset.menuId;
            const reorderUrl = `${menuBaseUrl}/${currentMenuId}/items/reordenar`;
            let dragSrcId = null;
            let dragSrcList = null;

            function currentOrder(list) {
                return Array.from(list.children)
                    .filter(el => el.classList.contains('menu-tree-item'))
                    .map(el => el.dataset.id);
            }

            async function persistOrder(list, parentId) {
                try {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            order: currentOrder(list),
                            parent_id: parentId,
                        }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        alert(data.message ?? 'No se pudo guardar el nuevo orden.');
                    }
                } catch (err) {
                    console.error('Error saving order:', err);
                }
            }

            function attachDragEvents(list, parentId) {
                Array.from(list.children).forEach(function(el) {
                    if (!el.classList.contains('menu-tree-item')) return;

                    el.addEventListener('dragstart', function(e) {
                        dragSrcId = el.dataset.id;
                        dragSrcList = list;
                        el.classList.add('is-dragging');
                        e.stopPropagation();
                    });
                    el.addEventListener('dragend', function() {
                        el.classList.remove('is-dragging');
                        dragSrcId = null;
                        dragSrcList = null;
                    });
                    el.addEventListener('dragover', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (dragSrcList !== list || dragSrcId === null || dragSrcId === el.dataset.id) return;
                        el.classList.add('drag-over');
                    });
                    el.addEventListener('dragleave', function() {
                        el.classList.remove('drag-over');
                    });
                    el.addEventListener('drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        el.classList.remove('drag-over');
                        if (dragSrcList !== list || dragSrcId === null || dragSrcId === el.dataset.id) return;

                        const srcEl = list.querySelector('.menu-tree-item[data-id="' + dragSrcId + '"]');
                        dragSrcId = null;
                        dragSrcList = null;
                        if (!srcEl) return;

                        list.insertBefore(srcEl, el);
                        persistOrder(list, parentId);
                    });
                });
            }

            attachDragEvents(rootList, null);
            document.querySelectorAll('.menu-tree-children').forEach(function(childList) {
                const parentId = parseInt(childList.dataset.parentId, 10);
                attachDragEvents(childList, parentId);
            });
        })();
    </script>
@endpush
