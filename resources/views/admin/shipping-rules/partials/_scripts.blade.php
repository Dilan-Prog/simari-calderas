@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón que
        // Métodos de Pago): store()=POST base, update()=PUT base/{id} (via
        // _method en FormData), edit()=GET base/{id}/editar, destroy()=DELETE
        // base/{id}.
        const shippingRuleUrl = '{{ url('/admin/reglas-de-envio') }}';
        const shippingRuleModal = document.getElementById('shippingRuleModal');
        const shippingRuleForm = document.getElementById('shippingRuleForm');
        const shippingRuleErrors = document.getElementById('shipping-rule-modal-errors');
        let currentShippingRuleId = null;
        let isShippingRuleEditMode = false;

        const closeShippingRuleModal = () => {
            shippingRuleModal.classList.remove('active');
        };

        // Los selects de marca/categoría solo muestran el bloque de su
        // scope activo — mismo patrón que syncPmConfigFields() en
        // Métodos de Pago.
        const syncSrScopeFields = () => {
            const scope = document.getElementById('srScopeType').value;
            document.querySelectorAll('.sr-scope-fields').forEach(block => {
                block.style.display = block.dataset.scope === scope ? 'flex' : 'none';
            });
        };

        // Marcas/categorías que YA tienen una regla se deshabilitan en el
        // select para que el admin no intente crear una segunda regla
        // duplicada. `keepValue` (el id de la marca/categoría de la regla
        // que se está editando) se re-habilita aunque esté marcada como
        // usada, para que su propia opción siga apareciendo seleccionable.
        const applySrUsedOptions = (selectId, keepValue) => {
            document.querySelectorAll(`#${selectId} option[data-used]`).forEach(opt => {
                const isUsed = opt.dataset.used === '1';
                const isKept = keepValue != null && String(opt.value) === String(keepValue);
                opt.disabled = isUsed && !isKept;
            });
        };

        const resetShippingRuleForm = () => {
            shippingRuleForm.reset();
            document.getElementById('srScopeType').value = 'brand';
            document.querySelectorAll('.sr-scope-card').forEach(card => {
                card.classList.toggle('active', card.dataset.scope === 'brand');
            });
            document.getElementById('srIsActive').checked = true;
            shippingRuleErrors.style.display = 'none';
            shippingRuleErrors.innerHTML = '';
            currentShippingRuleId = null;
            isShippingRuleEditMode = false;
            document.getElementById('shippingRuleModalTitle').textContent = 'Nueva Regla de Envío';
            document.getElementById('srSubmitBtn').textContent = 'Crear Regla';
            applySrUsedOptions('srBrandId', null);
            applySrUsedOptions('srCategoryId', null);
            syncSrScopeFields();
        };

        // Selección exclusiva de scope (Marca vs Categoría) — mismo patrón
        // que .pm-type-card en Métodos de Pago.
        document.querySelectorAll('.sr-scope-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.sr-scope-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                document.getElementById('srScopeType').value = card.dataset.scope;
                syncSrScopeFields();
            });
        });

        // Open create
        document.getElementById('btnNewShippingRule').addEventListener('click', () => {
            resetShippingRuleForm();
            shippingRuleModal.classList.add('active');
        });

        // Close
        document.getElementById('closeShippingRuleModal').addEventListener('click', closeShippingRuleModal);
        document.getElementById('cancelShippingRuleModal').addEventListener('click', closeShippingRuleModal);
        shippingRuleModal.addEventListener('click', (e) => {
            if (e.target === shippingRuleModal) closeShippingRuleModal();
        });

        const showSrFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const clearSrFieldErrors = () => {
            document.querySelectorAll('#shippingRuleForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#shippingRuleForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };

        const fieldIdByName = {
            scope_type: 'srScopeType',
            brand_id: 'srBrandId',
            category_id: 'srCategoryId',
            shipping_cost: 'srShippingCost',
            free_shipping_threshold: 'srFreeShippingThreshold',
            is_active: 'srIsActive',
        };

        // Submit (create/update)
        shippingRuleForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearSrFieldErrors();
            shippingRuleErrors.style.display = 'none';
            shippingRuleErrors.innerHTML = '';

            const formData = new FormData(shippingRuleForm);
            // Los checkboxes no marcados no se envían en FormData — se fija
            // explícitamente para que el backend siempre reciba el valor.
            formData.set('is_active', document.getElementById('srIsActive').checked ? '1' : '0');

            const url = isShippingRuleEditMode ?
                `${shippingRuleUrl}/${currentShippingRuleId}` :
                shippingRuleUrl;

            if (isShippingRuleEditMode) formData.append('_method', 'PUT');

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
                    shippingRuleErrors.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    shippingRuleErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeShippingRuleModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    shippingRuleErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    shippingRuleErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (fieldIdByName[field]) {
                            showSrFieldError(fieldIdByName[field], data.errors[field][0]);
                        }
                    });
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit
        document.querySelectorAll('.btn-edit-shipping-rule').forEach(btn => {
            btn.addEventListener('click', () => {
                currentShippingRuleId = btn.dataset.id;
                isShippingRuleEditMode = true;

                fetch(`${shippingRuleUrl}/${currentShippingRuleId}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(rule => {
                        resetShippingRuleForm();
                        isShippingRuleEditMode = true;
                        currentShippingRuleId = rule.id;

                        document.getElementById('shippingRuleModalTitle').textContent = 'Editar Regla de Envío';
                        document.getElementById('srSubmitBtn').textContent = 'Guardar Cambios';

                        const scope = rule.brand_id ? 'brand' : 'category';
                        document.getElementById('srScopeType').value = scope;
                        document.querySelectorAll('.sr-scope-card').forEach(card => {
                            card.classList.toggle('active', card.dataset.scope === scope);
                        });

                        applySrUsedOptions('srBrandId', rule.brand_id);
                        applySrUsedOptions('srCategoryId', rule.category_id);

                        document.getElementById('srBrandId').value = rule.brand_id ?? '';
                        document.getElementById('srCategoryId').value = rule.category_id ?? '';
                        document.getElementById('srShippingCost').value = rule.shipping_cost ?? '';
                        document.getElementById('srFreeShippingThreshold').value = rule.free_shipping_threshold ?? '';
                        document.getElementById('srIsActive').checked = !!rule.is_active;

                        syncSrScopeFields();

                        shippingRuleModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // Delete
        const deleteShippingRuleModal = document.getElementById('deleteShippingRuleModal');
        let deleteShippingRuleId = null;

        document.querySelectorAll('.btn-delete-shipping-rule').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteShippingRuleId = btn.dataset.id;
                document.getElementById('delShippingRuleName').textContent = btn.dataset.name;
                document.getElementById('delShippingRuleAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteShippingRuleModal.classList.add('active');
            });
        });

        document.getElementById('delShippingRuleCancel').addEventListener('click', () =>
            deleteShippingRuleModal.classList.remove('active'));
        deleteShippingRuleModal.addEventListener('click', (e) => {
            if (e.target === deleteShippingRuleModal) deleteShippingRuleModal.classList.remove('active');
        });

        document.getElementById('delShippingRuleConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${shippingRuleUrl}/${deleteShippingRuleId}`, {
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
                    deleteShippingRuleModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la regla de envío.');
                    deleteShippingRuleModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Estado inicial al cargar la página (antes de abrir el modal por
        // primera vez), para que el bloque de scope no arranque vacío.
        syncSrScopeFields();
    </script>
@endpush
