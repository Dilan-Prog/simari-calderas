@push('scripts')
    <script>
        // CRUD de Webhooks — mismo patrón fetch/CSRF que
        // admin/credentials/partials/_scripts.blade.php: store()=POST base,
        // update()=PUT base/{id} (via _method), edit()=GET base/{id}/editar,
        // destroy()=DELETE base/{id}. Reload de página tras cada acción en
        // vez de parchar el DOM, igual que el resto del admin.
        const webhookUrl = '{{ url('/admin/integraciones/webhooks') }}';
        const webhookModal = document.getElementById('webhookModal');
        const webhookForm = document.getElementById('webhookForm');
        const webhookErrors = document.getElementById('webhook-modal-errors');
        let currentWebhookId = null;
        let isWebhookEditMode = false;

        const closeWebhookModal = () => {
            webhookModal.classList.remove('active');
        };

        // ---------- Filas de headers (key/value repetibles) ----------
        const whHeadersRows = document.getElementById('whHeadersRows');
        const whHeaderRowTemplate = document.getElementById('whHeaderRowTemplate');

        const addWebhookHeaderRow = (key = '', value = '') => {
            const node = whHeaderRowTemplate.content.cloneNode(true);
            node.querySelector('.wh-header-key').value = key;
            node.querySelector('.wh-header-value').value = value;
            node.querySelector('.wh-header-remove').addEventListener('click', (e) => {
                e.target.closest('.wh-header-row').remove();
            });
            whHeadersRows.appendChild(node);
        };

        document.getElementById('whAddHeaderBtn').addEventListener('click', () => addWebhookHeaderRow());

        const collectWebhookHeaders = () => {
            return Array.from(whHeadersRows.querySelectorAll('.wh-header-row'))
                .map(row => ({
                    key: row.querySelector('.wh-header-key').value.trim(),
                    value: row.querySelector('.wh-header-value').value.trim(),
                }))
                .filter(h => h.key !== '');
        };

        // ---------- Select de credenciales (solo type === 'webhook') ----------
        const whCredentialSelect = document.getElementById('whCredentialId');

        const loadWebhookCredentialOptions = async (selectedId = '') => {
            whCredentialSelect.innerHTML = '<option value="">— ninguna —</option>';
            try {
                const response = await fetch('{{ url('/admin/integraciones/webhooks/credenciales') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const credentials = await response.json();

                credentials.forEach(({ id, name }) => {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = name || `Credencial #${id}`;
                    whCredentialSelect.appendChild(option);
                });

                if (selectedId) whCredentialSelect.value = selectedId;
            } catch (err) {
                console.error('Error al cargar credenciales:', err);
            }
        };

        // ---------- Probar conexión ----------
        const whTestResult = document.getElementById('whTestConnectionResult');

        document.getElementById('whTestConnectionBtn').addEventListener('click', async () => {
            whTestResult.style.display = 'block';
            whTestResult.textContent = 'Probando conexión...';
            whTestResult.style.color = '';

            try {
                const response = await fetch('{{ url('/admin/integraciones/webhooks/probar') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        url: document.getElementById('whUrl').value,
                        method: document.getElementById('whMethod').value,
                        headers: collectWebhookHeaders(),
                        credential_id: whCredentialSelect.value || null,
                    }),
                });

                const data = await response.json();
                if (data.ok) {
                    whTestResult.textContent = `Conexión exitosa (HTTP ${data.status}, ${data.ms} ms).`;
                    whTestResult.style.color = '#16a34a';
                } else {
                    whTestResult.textContent = data.message || `Falló la conexión (HTTP ${data.status ?? '—'}).`;
                    whTestResult.style.color = '#ef4444';
                }
            } catch (err) {
                whTestResult.textContent = 'No se pudo probar la conexión.';
                whTestResult.style.color = '#ef4444';
            }
        });

        // ---------- Abrir / cerrar / reset ----------
        const resetWebhookForm = () => {
            webhookForm.reset();
            webhookErrors.style.display = 'none';
            webhookErrors.innerHTML = '';
            whHeadersRows.innerHTML = '';
            whTestResult.style.display = 'none';
            currentWebhookId = null;
            isWebhookEditMode = false;
            document.getElementById('webhookModalTitle').textContent = 'Nuevo Webhook';
            document.getElementById('whSubmitBtn').textContent = 'Crear Webhook';
        };

        document.getElementById('btnNewWebhook')?.addEventListener('click', () => {
            resetWebhookForm();
            loadWebhookCredentialOptions();
            webhookModal.classList.add('active');
        });

        document.getElementById('closeWebhookModal').addEventListener('click', closeWebhookModal);
        document.getElementById('cancelWebhookModal').addEventListener('click', closeWebhookModal);
        webhookModal.addEventListener('click', (e) => {
            if (e.target === webhookModal) closeWebhookModal();
        });

        const showWhFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const clearWhFieldErrors = () => {
            document.querySelectorAll('#webhookForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#webhookForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };

        const whFieldIdByName = {
            name: 'whName',
            url: 'whUrl',
            method: 'whMethod',
            credential_id: 'whCredentialId',
        };

        // ---------- Submit (create/update) ----------
        webhookForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearWhFieldErrors();
            webhookErrors.style.display = 'none';
            webhookErrors.innerHTML = '';

            const payload = {
                name: document.getElementById('whName').value,
                url: document.getElementById('whUrl').value,
                method: document.getElementById('whMethod').value,
                headers: collectWebhookHeaders(),
                credential_id: whCredentialSelect.value || null,
            };

            const url = isWebhookEditMode ? `${webhookUrl}/${currentWebhookId}` : webhookUrl;

            try {
                const response = await fetch(url, {
                    method: isWebhookEditMode ? 'PUT' : 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (response.status === 419) {
                    webhookErrors.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    webhookErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeWebhookModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    webhookErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    webhookErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (whFieldIdByName[field]) {
                            showWhFieldError(whFieldIdByName[field], data.errors[field][0]);
                        }
                    });
                } else {
                    webhookErrors.innerHTML = `<p>${data.message ?? 'No se pudo guardar el webhook.'}</p>`;
                    webhookErrors.style.display = 'block';
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // ---------- Editar ----------
        document.querySelectorAll('.btn-edit-webhook').forEach(btn => {
            btn.addEventListener('click', () => {
                currentWebhookId = btn.dataset.id;
                isWebhookEditMode = true;

                fetch(`${webhookUrl}/${currentWebhookId}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(async (webhook) => {
                        resetWebhookForm();
                        isWebhookEditMode = true;
                        currentWebhookId = webhook.id;

                        document.getElementById('webhookModalTitle').textContent = 'Editar Webhook';
                        document.getElementById('whSubmitBtn').textContent = 'Guardar Cambios';

                        document.getElementById('whName').value = webhook.name ?? '';
                        document.getElementById('whUrl').value = webhook.url ?? '';
                        document.getElementById('whMethod').value = webhook.method ?? 'POST';

                        (webhook.headers ?? []).forEach(h => addWebhookHeaderRow(h.key ?? '', h.value ?? ''));

                        await loadWebhookCredentialOptions(webhook.credential_id ?? '');

                        webhookModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // ---------- Eliminar ----------
        const deleteWebhookModal = document.getElementById('deleteWebhookModal');
        let deleteWebhookId = null;

        document.querySelectorAll('.btn-delete-webhook').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteWebhookId = btn.dataset.id;
                document.getElementById('delWebhookName').textContent = btn.dataset.name;
                document.getElementById('delWebhookAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteWebhookModal.classList.add('active');
            });
        });

        document.getElementById('delWebhookCancel').addEventListener('click', () =>
            deleteWebhookModal.classList.remove('active'));
        deleteWebhookModal.addEventListener('click', (e) => {
            if (e.target === deleteWebhookModal) deleteWebhookModal.classList.remove('active');
        });

        document.getElementById('delWebhookConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${webhookUrl}/${deleteWebhookId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });

                const data = await response.json();

                if (response.ok) {
                    deleteWebhookModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar el webhook.');
                    deleteWebhookModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });
    </script>
@endpush
