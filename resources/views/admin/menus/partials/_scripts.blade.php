@push('scripts')
    <script>
        const menuBaseUrl = '{{ url('/admin/menus') }}';
        const menuProductsSearchUrl = '{{ route('admin.menus.products.search') }}';

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
                document.getElementById('menuSortOrder').value = '0';
                // '' no calza con ninguna de las 3 <option> fijas, así que el
                // <select> queda sin selección visible (selectedIndex = -1)
                // — el estado "en blanco" pedido para un menú nuevo.
                document.getElementById('menuLocation').value = '';
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

                    // Un Menu legacy puede traer un location de texto libre
                    // que ya no está entre las 3 opciones fijas — en ese
                    // caso se deja el <select> en blanco en vez de inventar
                    // una 4ª opción o reventar al asignar un value inválido.
                    const locationSelect = document.getElementById('menuLocation');
                    const menuLocationValue = btn.dataset.location ?? '';
                    const hasLocationOption = Array.from(locationSelect.options)
                        .some(opt => opt.value === menuLocationValue);
                    locationSelect.value = hasLocationOption ? menuLocationValue : '';

                    document.getElementById('menuSortOrder').value = btn.dataset.sortOrder ?? 0;
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

            /* ============================================================
               "Tipo de destino" estructurado — muestra/oculta 1 de 6
               bloques según el <select id="menuItemLinkType">, mismo patrón
               show/hide-block-by-select-value que syncConfigFields() en
               admin/home-sections/partials/_scripts.blade.php.
               ============================================================ */
            const linkTypeSelect = document.getElementById('menuItemLinkType');
            const linkedEntityIdHidden = document.getElementById('menuItemLinkedEntityId');

            function syncItemDestinationFields() {
                const type = linkTypeSelect.value;
                document.querySelectorAll('.menu-dest-field').forEach(block => {
                    block.style.display = block.dataset.linkType === type ? '' : 'none';
                });
            }

            linkTypeSelect.addEventListener('change', () => {
                // Cambio manual de tipo: el destino elegido bajo el tipo
                // anterior ya no aplica — se limpia para no enviar un
                // linked_entity_id que no corresponde al nuevo tipo.
                linkedEntityIdHidden.value = '';
                syncItemDestinationFields();
            });

            /* ── Categoría: cascada de 3 niveles, mismo patrón que
               admin/products/create_product/_scripts_create.blade.php
               ("Category cascade") ── */
            const categoryMain = document.getElementById('menuItemCategoryMain');
            const categorySub = document.getElementById('menuItemCategorySub');
            const categoryChild = document.getElementById('menuItemCategoryChild');

            @php
                $menuCategoriesForJs = collect($categories ?? [])->mapWithKeys(function($c) {
                    return [
                        $c->id => collect($c->children ?? [])->map(function($s) {
                            return [
                                'id' => $s->id,
                                'name' => $s->name,
                                'children' => collect($s->children ?? [])->map(function($ch) {
                                    return ['id' => $ch->id, 'name' => $ch->name];
                                })->values()->toArray(),
                            ];
                        })->values()->toArray(),
                    ];
                })->toArray();
            @endphp
            const menuCategorySubcategories = @json($menuCategoriesForJs);

            function populateCategorySubOptions(mainId) {
                const subs = menuCategorySubcategories[mainId] ?? [];
                categorySub.innerHTML = '<option value="">Seleccionar...</option>';
                subs.forEach(sub => {
                    const opt = document.createElement('option');
                    opt.value = sub.id;
                    opt.textContent = sub.name;
                    categorySub.appendChild(opt);
                });
                categorySub.disabled = subs.length === 0;
            }

            function populateCategoryChildOptions(mainId, subId) {
                const subs = menuCategorySubcategories[mainId] ?? [];
                const subObj = subs.find(s => String(s.id) === String(subId));
                const children = subObj?.children ?? [];
                categoryChild.innerHTML = '<option value="">Seleccionar...</option>';
                children.forEach(child => {
                    const opt = document.createElement('option');
                    opt.value = child.id;
                    opt.textContent = child.name;
                    categoryChild.appendChild(opt);
                });
                categoryChild.disabled = children.length === 0;
            }

            function updateCategoryLinkedEntity() {
                linkedEntityIdHidden.value = categoryChild.value || categorySub.value || categoryMain.value || '';
            }

            categoryMain.addEventListener('change', function() {
                categorySub.innerHTML = '<option value="">Seleccionar categoría primero...</option>';
                categoryChild.innerHTML = '<option value="">Seleccionar subcategoría primero...</option>';
                categorySub.disabled = true;
                categoryChild.disabled = true;
                populateCategorySubOptions(this.value);
                updateCategoryLinkedEntity();
            });

            categorySub.addEventListener('change', function() {
                populateCategoryChildOptions(categoryMain.value, this.value);
                updateCategoryLinkedEntity();
            });

            categoryChild.addEventListener('change', updateCategoryLinkedEntity);

            // Busca en el mismo árbol que llena los 3 <select> la cadena
            // main/sub/child que contiene un id dado — así basta con
            // guardar el id final (linked_entity_id) para reconstruir los 3
            // niveles al editar, sin necesitar 3 data-* atributos aparte
            // por elemento (la categoría puede haber sido elegida en
            // cualquiera de los 3 niveles, no solo el más profundo).
            function findCategoryPath(id) {
                if (!id) return null;
                id = String(id);
                const mainMatch = Array.from(categoryMain.options).find(opt => opt.value === id);
                if (mainMatch) return { main: id, sub: '', child: '' };
                for (const [mainId, subs] of Object.entries(menuCategorySubcategories)) {
                    for (const sub of subs) {
                        if (String(sub.id) === id) return { main: mainId, sub: id, child: '' };
                        for (const child of (sub.children || [])) {
                            if (String(child.id) === id) return { main: mainId, sub: String(sub.id), child: id };
                        }
                    }
                }
                return null;
            }

            function setCategoryCascade(entityId) {
                categoryMain.value = '';
                categorySub.innerHTML = '<option value="">Seleccionar categoría primero...</option>';
                categorySub.disabled = true;
                categoryChild.innerHTML = '<option value="">Seleccionar subcategoría primero...</option>';
                categoryChild.disabled = true;

                const path = findCategoryPath(entityId);
                if (!path) return;

                categoryMain.value = path.main;
                populateCategorySubOptions(path.main);
                if (path.sub) {
                    categorySub.value = path.sub;
                    populateCategoryChildOptions(path.main, path.sub);
                    if (path.child) categoryChild.value = path.child;
                }
            }

            /* ── Colección / Marca: <select> simples ── */
            const collectionSelect = document.getElementById('menuItemCollectionSelect');
            const brandSelect = document.getElementById('menuItemBrandSelect');

            collectionSelect.addEventListener('change', function() {
                linkedEntityIdHidden.value = this.value;
            });
            brandSelect.addEventListener('change', function() {
                linkedEntityIdHidden.value = this.value;
            });

            /* ── Producto: autocompletar + chip de seleccionado (selección
               única — variante simplificada de initManualProductPicker en
               admin/home-sections/partials/_scripts.blade.php) ── */
            const productSearchInput = document.getElementById('menuProductSearchInput');
            const productSearchDropdown = document.getElementById('menuProductSearchDropdown');
            const productSearchList = document.getElementById('menuProductSearchList');
            const productSearchEmpty = document.getElementById('menuProductSearchEmpty');
            const productChipWrap = document.getElementById('menuProductChipWrap');
            const productChipName = document.getElementById('menuProductChipName');
            const productChipClear = document.getElementById('menuProductChipClear');
            let productSearchDebounce = null;

            function escMenuHtml(s) {
                return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function hideProductDropdown() {
                productSearchDropdown.style.display = 'none';
                productSearchList.innerHTML = '';
            }

            function selectProduct(product) {
                linkedEntityIdHidden.value = product.id;
                productChipName.textContent = product.sku ? `${product.name} (${product.sku})` : product.name;
                productChipWrap.style.display = '';
                productSearchInput.value = '';
                hideProductDropdown();
            }

            function clearSelectedProduct() {
                linkedEntityIdHidden.value = '';
                productChipWrap.style.display = 'none';
                productChipName.textContent = '';
            }

            productChipClear.addEventListener('click', clearSelectedProduct);

            productSearchInput.addEventListener('input', function() {
                const q = this.value.trim();
                clearTimeout(productSearchDebounce);
                if (q.length < 2) { hideProductDropdown(); return; }
                productSearchDebounce = setTimeout(async () => {
                    try {
                        const url = new URL(menuProductsSearchUrl, window.location.origin);
                        url.searchParams.set('q', q);
                        const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
                        // MenuController::searchProducts() responde
                        // { success: true, products: [...] }, no un array
                        // plano — a diferencia de home-sections, que sí
                        // devuelve el array directo.
                        const data = res.ok ? await res.json() : { products: [] };
                        const products = data.products ?? [];
                        productSearchDropdown.style.display = 'block';
                        productSearchList.innerHTML = '';
                        if (!products.length) {
                            productSearchEmpty.style.display = 'block';
                            productSearchList.style.display = 'none';
                            return;
                        }
                        productSearchEmpty.style.display = 'none';
                        productSearchList.style.display = 'block';
                        products.forEach(p => {
                            const li = document.createElement('li');
                            li.className = 'hs-product-search__item';
                            li.innerHTML = `<span>${escMenuHtml(p.name)}</span><small>SKU: ${escMenuHtml(p.sku)}</small>`;
                            li.addEventListener('click', () => selectProduct(p));
                            productSearchList.appendChild(li);
                        });
                    } catch (err) {
                        console.error('Error searching products:', err);
                        hideProductDropdown();
                    }
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#menuProductSearch')) hideProductDropdown();
            });

            /* ── Página estática ── */
            const staticPageSelect = document.getElementById('menuItemStaticPageRoute');

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

                // Destino: custom_url es el default razonable (única
                // opción de hoy) — limpia los 6 bloques condicionales para
                // que ninguno arrastre datos del elemento anterior.
                linkTypeSelect.value = 'custom_url';
                linkedEntityIdHidden.value = '';
                categoryMain.value = '';
                categorySub.innerHTML = '<option value="">Seleccionar categoría primero...</option>';
                categorySub.disabled = true;
                categoryChild.innerHTML = '<option value="">Seleccionar subcategoría primero...</option>';
                categoryChild.disabled = true;
                collectionSelect.value = '';
                brandSelect.value = '';
                staticPageSelect.value = 'privacy-notice';
                clearSelectedProduct();
                syncItemDestinationFields();

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

                    // Destino estructurado: un link_type desconocido (dato
                    // legacy o mismatch) cae a custom_url en vez de dejar el
                    // <select> en un valor inexistente.
                    const linkType = btn.dataset.linkType || 'custom_url';
                    const linkedEntityId = btn.dataset.linkedEntityId || '';
                    const linkedEntityLabel = btn.dataset.linkedEntityLabel || '';
                    const hasLinkTypeOption = Array.from(linkTypeSelect.options)
                        .some(opt => opt.value === linkType);
                    linkTypeSelect.value = hasLinkTypeOption ? linkType : 'custom_url';
                    syncItemDestinationFields();

                    if (linkType === 'category') {
                        setCategoryCascade(linkedEntityId);
                        linkedEntityIdHidden.value = linkedEntityId;
                    } else if (linkType === 'collection') {
                        collectionSelect.value = linkedEntityId;
                        linkedEntityIdHidden.value = linkedEntityId;
                    } else if (linkType === 'brand') {
                        brandSelect.value = linkedEntityId;
                        linkedEntityIdHidden.value = linkedEntityId;
                    } else if (linkType === 'product') {
                        linkedEntityIdHidden.value = linkedEntityId;
                        if (linkedEntityId) {
                            productChipName.textContent = linkedEntityLabel || `Producto #${linkedEntityId}`;
                            productChipWrap.style.display = '';
                        }
                    } else if (linkType === 'static_page') {
                        // El backend guarda la ruta de página estática en la
                        // columna url, no en linked_entity_id (ver contrato).
                        staticPageSelect.value = btn.dataset.url || 'privacy-notice';
                    }

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

                    // Si el campo con error vive dentro de uno de los 6
                    // bloques de destino condicionales (porque ya no
                    // corresponde al tipo actualmente seleccionado, p.ej.
                    // tras cambiar de tipo después de un intento fallido),
                    // hay que des-ocultar el bloque o el mensaje queda
                    // invisible.
                    const destBlock = element.closest('.menu-dest-field');
                    if (destBlock) destBlock.style.display = '';
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
                        // 'title'/'parent_id' — así 'url', 'sort_order',
                        // 'link_type' y 'static_page_route' (y cualquier otra
                        // regla que se agregue después en
                        // MenuController::storeItem()/updateItem()) también
                        // marcan su campo en rojo. querySelector se limita a
                        // itemForm, así que solo puede marcar campos que existen
                        // en ESTE formulario (no cruza con el de menuForm).
                        //
                        // 'linked_entity_id' es un caso especial: vive en un
                        // <input type="hidden"> fuera de cualquier
                        // .menu-dest-field (es compartido por category/
                        // collection/brand/product), así que showError()
                        // no tendría dónde colgar visiblemente el mensaje —
                        // se adjunta en cambio al bloque de destino
                        // actualmente visible.
                        Object.keys(data.errors).forEach(field => {
                            if (field === 'linked_entity_id') {
                                const activeBlock = document.querySelector(
                                    `.menu-dest-field[data-link-type="${linkTypeSelect.value}"]`
                                );
                                if (activeBlock) {
                                    activeBlock.style.display = '';
                                    const errorSpan = document.createElement('span');
                                    errorSpan.className = 'field-error-msg';
                                    errorSpan.innerText = data.errors[field][0];
                                    activeBlock.appendChild(errorSpan);
                                }
                                return;
                            }
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
