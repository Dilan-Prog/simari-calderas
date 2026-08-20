@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón que
        // Credenciales/Métodos de Pago): store()=POST base, update()=PUT
        // base/{id} (via _method en FormData), edit()=GET base/{id}/editar,
        // destroy()=DELETE base/{id}.
        const waAccountUrl = '{{ url('/admin/cuentas-whatsapp') }}';
        const waAccountModal = document.getElementById('whatsappAccountModal');
        const waAccountForm = document.getElementById('whatsappAccountForm');
        const waAccountErrors = document.getElementById('whatsapp-account-modal-errors');
        let currentWaAccountId = null;
        let isWaAccountEditMode = false;

        const closeWaAccountModal = () => {
            waAccountModal.classList.remove('active');
        };

        const resetWaAccountForm = () => {
            waAccountForm.reset();
            document.getElementById('waIsActive').checked = true;
            waAccountErrors.style.display = 'none';
            waAccountErrors.innerHTML = '';
            currentWaAccountId = null;
            isWaAccountEditMode = false;
            document.getElementById('whatsappAccountModalTitle').textContent = 'Nueva Cuenta de WhatsApp';
            document.getElementById('waSubmitBtn').textContent = 'Crear Cuenta';
        };

        // Open create
        document.getElementById('btnNewWhatsappAccount')?.addEventListener('click', () => {
            resetWaAccountForm();
            waAccountModal.classList.add('active');
        });

        // Close
        document.getElementById('closeWhatsappAccountModal').addEventListener('click', closeWaAccountModal);
        document.getElementById('cancelWhatsappAccountModal').addEventListener('click', closeWaAccountModal);
        waAccountModal.addEventListener('click', (e) => {
            if (e.target === waAccountModal) closeWaAccountModal();
        });

        const showWaFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const clearWaFieldErrors = () => {
            document.querySelectorAll('#whatsappAccountForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#whatsappAccountForm .is-invalid').forEach(el => el.classList.remove(
                'is-invalid'));
        };

        // Mapa usado para mostrar errores de validación del servidor junto
        // al campo correspondiente.
        const waFieldIdByName = {
            name: 'waName',
            phone_number: 'waPhoneNumber',
            phone_number_id: 'waPhoneNumberId',
            whatsapp_business_account_id: 'waBusinessAccountId',
            webhook_verify_token: 'waWebhookVerifyToken',
            access_token: 'waAccessToken',
        };

        // Submit (create/update)
        waAccountForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearWaFieldErrors();
            waAccountErrors.style.display = 'none';
            waAccountErrors.innerHTML = '';

            const formData = new FormData(waAccountForm);
            formData.set('is_active', document.getElementById('waIsActive').checked ? '1' : '0');

            const url = isWaAccountEditMode ?
                `${waAccountUrl}/${currentWaAccountId}` :
                waAccountUrl;

            if (isWaAccountEditMode) formData.append('_method', 'PUT');

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
                    waAccountErrors.innerHTML =
                        '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    waAccountErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeWaAccountModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    waAccountErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    waAccountErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (waFieldIdByName[field]) {
                            showWaFieldError(waFieldIdByName[field], data.errors[field][0]);
                        }
                    });
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit — solo precarga los campos no sensibles; access_token nunca
        // vuelve del servidor, se deja vacío con su placeholder de ayuda.
        document.querySelectorAll('.btn-edit-whatsapp-account').forEach(btn => {
            btn.addEventListener('click', () => {
                currentWaAccountId = btn.dataset.id;
                isWaAccountEditMode = true;

                fetch(`${waAccountUrl}/${currentWaAccountId}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(account => {
                        resetWaAccountForm();
                        isWaAccountEditMode = true;
                        currentWaAccountId = account.id;

                        document.getElementById('whatsappAccountModalTitle').textContent =
                            'Editar Cuenta de WhatsApp';
                        document.getElementById('waSubmitBtn').textContent = 'Guardar Cambios';

                        document.getElementById('waName').value = account.name ?? '';
                        document.getElementById('waPhoneNumber').value = account.phone_number ?? '';
                        document.getElementById('waPhoneNumberId').value = account.phone_number_id ?? '';
                        document.getElementById('waBusinessAccountId').value = account
                            .whatsapp_business_account_id ?? '';
                        document.getElementById('waWebhookVerifyToken').value = account.webhook_verify_token ??
                            '';
                        document.getElementById('waAccessToken').value = '';
                        document.getElementById('waIsActive').checked = !!account.is_active;

                        waAccountModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // Delete
        const deleteWaAccountModal = document.getElementById('deleteWhatsappAccountModal');
        let deleteWaAccountId = null;

        document.querySelectorAll('.btn-delete-whatsapp-account').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteWaAccountId = btn.dataset.id;
                document.getElementById('delWhatsappAccountName').textContent = btn.dataset.name;
                document.getElementById('delWhatsappAccountAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteWaAccountModal.classList.add('active');
            });
        });

        // Copiar el Callback URL del webhook (tarjeta informativa arriba de la tabla).
        document.querySelectorAll('.btn-copy-webhook-value').forEach(btn => {
            btn.addEventListener('click', () => {
                navigator.clipboard.writeText(btn.dataset.value).then(() => {
                    const original = btn.title;
                    btn.title = 'Copiado';
                    setTimeout(() => btn.title = original, 1500);
                });
            });
        });

        document.getElementById('delWhatsappAccountCancel').addEventListener('click', () =>
            deleteWaAccountModal.classList.remove('active'));
        deleteWaAccountModal.addEventListener('click', (e) => {
            if (e.target === deleteWaAccountModal) deleteWaAccountModal.classList.remove('active');
        });

        document.getElementById('delWhatsappAccountConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${waAccountUrl}/${deleteWaAccountId}`, {
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
                    deleteWaAccountModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la cuenta.');
                    deleteWaAccountModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });
    </script>
@endpush
