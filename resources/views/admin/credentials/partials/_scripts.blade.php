@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón que
        // Métodos de Pago): store()=POST base, update()=PUT base/{id} (via
        // _method en FormData), edit()=GET base/{id}/editar, destroy()=DELETE
        // base/{id}.
        const credentialUrl = '{{ url('/admin/credenciales') }}';
        const credentialModal = document.getElementById('credentialModal');
        const credentialForm = document.getElementById('credentialForm');
        const credentialErrors = document.getElementById('credential-modal-errors');
        let currentCredentialId = null;
        let isCredentialEditMode = false;

        const closeCredentialModal = () => {
            credentialModal.classList.remove('active');
        };

        // Fase 8 -- toggle entre el textarea JSON genérico y el sub-formulario
        // de campos mysql (host/puerto/base de datos/usuario/contraseña)
        // según lo que el usuario escriba en "Tipo".
        const credMysqlFields = document.getElementById('credMysqlFields');
        const credPayloadGroup = document.getElementById('credPayloadGroup');
        const credTestResult = document.getElementById('credTestConnectionResult');

        const isMysqlType = () => (document.getElementById('credType').value || '').trim().toLowerCase() === 'mysql';

        const syncCredentialTypeUI = () => {
            const mysql = isMysqlType();
            credMysqlFields.style.display = mysql ? 'grid' : 'none';
            credPayloadGroup.style.display = mysql ? 'none' : 'block';
            credTestResult.style.display = 'none';
        };

        document.getElementById('credType').addEventListener('input', syncCredentialTypeUI);

        const buildMysqlPayload = () => ({
            host: document.getElementById('credMysqlHost').value || '127.0.0.1',
            port: document.getElementById('credMysqlPort').value || '3306',
            database: document.getElementById('credMysqlDatabase').value || '',
            username: document.getElementById('credMysqlUsername').value || '',
            password: document.getElementById('credMysqlPassword').value || '',
        });

        document.getElementById('credTestConnectionBtn').addEventListener('click', async () => {
            credTestResult.style.display = 'block';
            credTestResult.textContent = 'Probando conexión...';

            try {
                const response = await fetch('{{ route('admin.credentials.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ type: 'mysql', payload: buildMysqlPayload() }),
                });

                const data = await response.json();
                credTestResult.textContent = data.ok ?
                    'Conexión exitosa.' :
                    (data.message || 'No se pudo conectar.');
                credTestResult.style.color = data.ok ? '#16a34a' : '#ef4444';
            } catch (err) {
                credTestResult.textContent = 'No se pudo probar la conexión.';
                credTestResult.style.color = '#ef4444';
            }
        });

        const resetCredentialForm = () => {
            credentialForm.reset();
            credentialErrors.style.display = 'none';
            credentialErrors.innerHTML = '';
            currentCredentialId = null;
            isCredentialEditMode = false;
            document.getElementById('credentialModalTitle').textContent = 'Nueva Credencial';
            document.getElementById('credSubmitBtn').textContent = 'Crear Credencial';
            syncCredentialTypeUI();
        };

        // Open create
        document.getElementById('btnNewCredential')?.addEventListener('click', () => {
            resetCredentialForm();
            credentialModal.classList.add('active');
        });

        // Close
        document.getElementById('closeCredentialModal').addEventListener('click', closeCredentialModal);
        document.getElementById('cancelCredentialModal').addEventListener('click', closeCredentialModal);
        credentialModal.addEventListener('click', (e) => {
            if (e.target === credentialModal) closeCredentialModal();
        });

        const showCredFieldError = (id, message) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-invalid');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'field-error-msg';
            errorSpan.innerText = message;
            (el.closest('.ap-field-group') || el.parentElement)?.appendChild(errorSpan);
        };

        const clearCredFieldErrors = () => {
            document.querySelectorAll('#credentialForm .field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('#credentialForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        };

        // Mapa usado para mostrar errores de validación del servidor junto
        // al campo correspondiente.
        const credFieldIdByName = {
            name: 'credName',
            type: 'credType',
            payload: 'credPayload',
        };

        // Submit (create/update)
        credentialForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            clearCredFieldErrors();
            credentialErrors.style.display = 'none';
            credentialErrors.innerHTML = '';

            const formData = new FormData(credentialForm);

            // Fase 8 -- si type=mysql, el payload real viene del sub-formulario
            // de campos separados, no del textarea genérico (oculto en ese caso).
            if (isMysqlType()) {
                const mysqlPayload = buildMysqlPayload();
                const hasAnyValue = Object.values(mysqlPayload).some(v => v !== '');
                if (hasAnyValue) {
                    formData.set('payload', JSON.stringify(mysqlPayload));
                } else {
                    formData.delete('payload');
                }
            }

            const url = isCredentialEditMode ?
                `${credentialUrl}/${currentCredentialId}` :
                credentialUrl;

            if (isCredentialEditMode) formData.append('_method', 'PUT');

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
                    credentialErrors.innerHTML = '<p>Tu sesión expiró. Por favor recarga la página e intenta de nuevo.</p>';
                    credentialErrors.style.display = 'block';
                    return;
                }

                const data = await response.json();

                if (response.ok) {
                    closeCredentialModal();
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors ?? {}).flat();
                    credentialErrors.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    credentialErrors.style.display = 'block';

                    Object.keys(data.errors ?? {}).forEach(field => {
                        if (credFieldIdByName[field]) {
                            showCredFieldError(credFieldIdByName[field], data.errors[field][0]);
                        }
                    });
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });

        // Edit — solo precarga name/type; el payload nunca vuelve del
        // servidor, se deja vacío con su placeholder de ayuda.
        document.querySelectorAll('.btn-edit-credential').forEach(btn => {
            btn.addEventListener('click', () => {
                currentCredentialId = btn.dataset.id;
                isCredentialEditMode = true;

                fetch(`${credentialUrl}/${currentCredentialId}/editar`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    })
                    .then(res => res.json())
                    .then(cred => {
                        resetCredentialForm();
                        isCredentialEditMode = true;
                        currentCredentialId = cred.id;

                        document.getElementById('credentialModalTitle').textContent = 'Editar Credencial';
                        document.getElementById('credSubmitBtn').textContent = 'Guardar Cambios';

                        document.getElementById('credName').value = cred.name ?? '';
                        document.getElementById('credType').value = cred.type ?? '';
                        document.getElementById('credPayload').value = '';
                        syncCredentialTypeUI();

                        credentialModal.classList.add('active');
                    })
                    .catch(err => console.error('Error:', err));
            });
        });

        // Delete
        const deleteCredentialModal = document.getElementById('deleteCredentialModal');
        let deleteCredentialId = null;

        document.querySelectorAll('.btn-delete-credential').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteCredentialId = btn.dataset.id;
                document.getElementById('delCredentialName').textContent = btn.dataset.name;
                document.getElementById('delCredentialAvatar').textContent =
                    btn.dataset.name.charAt(0).toUpperCase();
                deleteCredentialModal.classList.add('active');
            });
        });

        document.getElementById('delCredentialCancel').addEventListener('click', () =>
            deleteCredentialModal.classList.remove('active'));
        deleteCredentialModal.addEventListener('click', (e) => {
            if (e.target === deleteCredentialModal) deleteCredentialModal.classList.remove('active');
        });

        document.getElementById('delCredentialConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${credentialUrl}/${deleteCredentialId}`, {
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
                    deleteCredentialModal.classList.remove('active');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(data.message ?? 'No se pudo eliminar la credencial.');
                    deleteCredentialModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });
    </script>
@endpush
