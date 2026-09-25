@push('scripts')
    <script>
        // JS de los paneles "WhatsApp API" y "WhatsApp Web" -- fusión de la
        // antigua pantalla standalone admin.whatsapp-accounts.index dentro
        // de Integraciones. Mismo backend de siempre (WhatsappAccountController):
        // store()=POST base, update()=PUT base/{id} (via _method en FormData),
        // edit()=GET base/{id}/editar, destroy()=DELETE base/{id},
        // qrStatus()=GET base/{id}/qr. Solo cambió DÓNDE vive el HTML/JS y
        // cómo se separan las cuentas por connection_type -- ya no hay picker
        // "¿Cómo quieres conectar?" porque ahora el panel en el que estás
        // decide el tipo.
        const waAccountUrl = '{{ url('/admin/cuentas-whatsapp') }}';

        // ============================================================
        // Modal "WhatsApp API" (Meta Cloud API) -- crear/editar. Es también
        // el único modal de edición que existe (igual que en la pantalla
        // standalone original, donde "Editar" abría siempre este mismo
        // formulario sin importar el connection_type de la fila) -- así que
        // los botones .btn-edit-whatsapp-account de AMBOS paneles (API y
        // Web) abren este modal.
        // ============================================================
        const waApiAccountModal = document.getElementById('whatsappApiAccountModal');
        const waApiAccountForm = document.getElementById('whatsappApiAccountForm');
        const waApiAccountErrors = document.getElementById('whatsapp-api-account-modal-errors');
        let currentWaAccountId = null;
        let isWaAccountEditMode = false;

        const closeWaApiAccountModal = () => {
            waApiAccountModal.classList.remove('active');
        };

        const resetWaApiAccountForm = () => {
            waApiAccountForm.reset();
            document.getElementById('waApiIsActive').checked = true;
            waApiAccountErrors.style.display = 'none';
            waApiAccountErrors.innerHTML = '';
            currentWaAccountId = null;
            isWaAccountEditMode = false;
            document.getElementById('whatsappApiAccountModalTitle').textContent = 'Nueva Cuenta de WhatsApp API';
            document.getElementById('waApiSubmitBtn').textContent = 'Crear Cuenta';
        };

        // "+ Nueva cuenta" del panel WhatsApp API abre el formulario Meta
        // directo -- ya no pasa por el picker de Part D, porque estar en
        // este panel YA significa "quiero conectar por API".
        document.getElementById('btnNewWhatsappApiAccount')?.addEventListener('click', () => {
            resetWaApiAccountForm();
            waApiAccountModal.classList.add('active');
        });

        document.getElementById('closeWhatsappApiAccountModal').addEventListener('click', closeWaApiAccountModal);
        document.getElementById('cancelWhatsappApiAccountModal').addEventListener('click', closeWaApiAccountModal);
        waApiAccountModal.addEventListener('click', (e) => {
            if (e.target === waApiAccountModal) closeWaApiAccountModal();
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
            document.querySelectorAll('#whatsappApiAccountForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#whatsappApiAccountForm .is-invalid').forEach(el => el.classList.remove(
                'is-invalid'));
        };

        // Mapa usado para mostrar errores de validación del servidor junto
        // al campo correspondiente.
        const waFieldIdByName = {
            name: 'waApiName',
            phone_number: 'waApiPhoneNumber',
            phone_number_id: 'waApiPhoneNumberId',
            whatsapp_business_account_id: 'waApiBusinessAccountId',
            webhook_verify_token: 'waApiWebhookVerifyToken',
            access_token: 'waApiAccessToken',
        };

        // Submit (create/update)
        waApiAccountForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearWaFieldErrors();
            waApiAccountErrors.style.display = 'none';
            waApiAccountErrors.innerHTML = '';

            const formData = new FormData(waApiAccountForm);
            formData.set('is_active', document.getElementById('waApiIsActive').checked ? '1' : '0');

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
                    waApiAccountErrors.innerHTML =
                        '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    waApiAccountErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeWaApiAccountModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    waApiAccountErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    waApiAccountErrors.style.display = 'block';

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
        // Delegado a ambas tablas (API y Web) porque comparten la misma
        // clase .btn-edit-whatsapp-account.
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
                        resetWaApiAccountForm();
                        isWaAccountEditMode = true;
                        currentWaAccountId = account.id;

                        document.getElementById('whatsappApiAccountModalTitle').textContent =
                            'Editar Cuenta de WhatsApp';
                        document.getElementById('waApiSubmitBtn').textContent = 'Guardar Cambios';

                        document.getElementById('waApiName').value = account.name ?? '';
                        document.getElementById('waApiPhoneNumber').value = account.phone_number ?? '';
                        document.getElementById('waApiPhoneNumberId').value = account.phone_number_id ?? '';
                        document.getElementById('waApiBusinessAccountId').value = account
                            .whatsapp_business_account_id ?? '';
                        document.getElementById('waApiWebhookVerifyToken').value = account.webhook_verify_token ??
                            '';
                        document.getElementById('waApiAccessToken').value = '';
                        document.getElementById('waApiIsActive').checked = !!account.is_active;

                        waApiAccountModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // ============================================================
        // Delete -- modal compartido entre ambos paneles.
        // ============================================================
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

        // ============================================================
        // Panel "WhatsApp Web" -- crear (Part D) -- paso 1: modal "Nueva
        // Cuenta de WhatsApp Web" (solo Nombre), postea al mismo store() de
        // siempre con connection_type=baileys_qr (input oculto ya en el
        // formulario). Al crearse con éxito, abre directo el paso 2
        // (escaneo) para la cuenta recién creada. Ya no pasa por el picker
        // de Part D -- estar en este panel YA significa "quiero conectar
        // por QR".
        // ============================================================
        const waQrFormModal = document.getElementById('whatsappQrFormModal');
        const waQrForm = document.getElementById('whatsappQrForm');
        const waQrFormErrors = document.getElementById('whatsapp-qr-form-modal-errors');

        const resetWaQrForm = () => {
            waQrForm.reset();
            waQrFormErrors.style.display = 'none';
            waQrFormErrors.innerHTML = '';
            document.querySelectorAll('#whatsappQrForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#whatsappQrForm .is-invalid').forEach(el => el.classList.remove(
                'is-invalid'));
        };

        const closeWaQrFormModal = () => waQrFormModal.classList.remove('active');

        document.getElementById('btnNewWhatsappQrAccount')?.addEventListener('click', () => {
            resetWaQrForm();
            waQrFormModal.classList.add('active');
        });

        document.getElementById('closeWhatsappQrFormModal').addEventListener('click', closeWaQrFormModal);
        document.getElementById('cancelWhatsappQrForm').addEventListener('click', closeWaQrFormModal);
        waQrFormModal.addEventListener('click', (e) => {
            if (e.target === waQrFormModal) closeWaQrFormModal();
        });

        waQrForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            waQrFormErrors.style.display = 'none';
            waQrFormErrors.innerHTML = '';
            document.querySelectorAll('#whatsappQrForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#whatsappQrForm .is-invalid').forEach(el => el.classList.remove(
                'is-invalid'));

            const formData = new FormData(waQrForm);

            try {
                const response = await fetch(waAccountUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.status === 419) {
                    waQrFormErrors.innerHTML =
                        '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    waQrFormErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    const created = data.whatsappAccount;
                    closeWaQrFormModal();
                    openWaQrScanModal(created.id, created.name);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    waQrFormErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    waQrFormErrors.style.display = 'block';

                    if (data.errors?.name) {
                        const el = document.getElementById('waQrName');
                        el.classList.add('is-invalid');
                        const errorSpan = document.createElement('span');
                        errorSpan.className = 'field-error-msg';
                        errorSpan.innerText = data.errors.name[0];
                        (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
                    }
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // ============================================================
        // Panel "WhatsApp Web" -- paso 2: modal "Escanea el código QR". Hace
        // polling cada 3s a admin.whatsapp-accounts.qr-status. La primera
        // llamada dispara startSession() en el backend (porque la cuenta
        // recién creada, o una que se reconecta, no tiene sesión activa);
        // las siguientes solo consultan el estado. El QR puede renovarse
        // mientras siga 'qr_pending' (los códigos de Baileys expiran), así
        // que cada respuesta reemplaza la imagen mostrada.
        // ============================================================
        const waQrScanModal = document.getElementById('whatsappQrScanModal');
        const waQrScanLoading = document.getElementById('waQrScanLoading');
        const waQrScanImage = document.getElementById('waQrScanImage');
        const waQrScanSuccess = document.getElementById('waQrScanSuccess');
        const waQrScanError = document.getElementById('waQrScanError');

        let waQrPollTimer = null;
        let waQrPollAccountId = null;

        const stopWaQrPolling = () => {
            if (waQrPollTimer) {
                clearInterval(waQrPollTimer);
                waQrPollTimer = null;
            }
            waQrPollAccountId = null;
        };

        const showWaQrState = (state) => {
            waQrScanLoading.style.display = state === 'loading' ? 'block' : 'none';
            waQrScanImage.style.display = state === 'qr' ? 'block' : 'none';
            waQrScanSuccess.style.display = state === 'success' ? 'block' : 'none';
            waQrScanError.style.display = state === 'error' ? 'block' : 'none';
        };

        const pollWaQrStatus = async (accountId) => {
            try {
                const response = await fetch(`${waAccountUrl}/${accountId}/qr`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                if (!response.ok) throw new Error('qr-status request failed');

                const data = await response.json();

                // El modal pudo haberse cerrado mientras la petición estaba
                // en vuelo (o se lanzó otro polling encima) — no pisar nada.
                if (waQrPollAccountId !== accountId) return;

                if (data.status === 'connected') {
                    stopWaQrPolling();
                    showWaQrState('success');
                    setTimeout(() => {
                        waQrScanModal.classList.remove('active');
                        window.location.reload();
                    }, 1500);
                    return;
                }

                if (data.status === 'qr_pending' && data.qr) {
                    waQrScanImage.src = `data:image/png;base64,${data.qr}`;
                    showWaQrState('qr');
                    return;
                }

                // 'disconnected' sin QR todavía (p.ej. el microservicio no
                // respondió a tiempo) — se mantiene el loading y se sigue
                // reintentando en el próximo poll.
                showWaQrState('loading');
            } catch (err) {
                console.error('Error:', err);
                if (waQrPollAccountId !== accountId) return;
                showWaQrState('error');
                waQrScanError.textContent =
                    'No se pudo obtener el estado de la conexión. Reintentando…';
            }
        };

        const openWaQrScanModal = (accountId, accountName) => {
            stopWaQrPolling();
            showWaQrState('loading');
            document.getElementById('waQrScanTitle').textContent =
                accountName ? `Escanea el código QR — ${accountName}` : 'Escanea el código QR';
            waQrScanModal.classList.add('active');

            waQrPollAccountId = accountId;
            pollWaQrStatus(accountId);
            waQrPollTimer = setInterval(() => pollWaQrStatus(accountId), 3000);
        };

        const closeWaQrScanModal = () => {
            stopWaQrPolling();
            waQrScanModal.classList.remove('active');
        };

        document.getElementById('closeWhatsappQrScanModal').addEventListener('click', closeWaQrScanModal);
        document.getElementById('closeWhatsappQrScanModalBtn').addEventListener('click', closeWaQrScanModal);
        waQrScanModal.addEventListener('click', (e) => {
            if (e.target === waQrScanModal) closeWaQrScanModal();
        });

        // "Reconectar" en una fila existente (connection_type=baileys_qr,
        // session_status disconnected) reabre el mismo modal de escaneo.
        document.querySelectorAll('.btn-reconnect-whatsapp-account').forEach(btn => {
            btn.addEventListener('click', () => {
                openWaQrScanModal(btn.dataset.id, btn.dataset.name);
            });
        });
    </script>
@endpush
