@push('scripts')
    <script>
        // Fetch AJAX contra las rutas REST del módulo (mismo patrón CSRF+fetch
        // que payment-methods/partials/_scripts.blade.php): toggle()=POST
        // base/{id}/toggle, destroy()=DELETE base/{id} (via _method en body).
        // El controller responde con redirect()->back() (no JSON), así que
        // aquí solo nos interesa el status HTTP; el estado final se refleja
        // recargando la página.
        const workflowUrl = '{{ url('/admin/automatizaciones') }}';

        // Toggle activo/inactivo. El controller responde con
        // redirect()->back() (200 tras seguir el redirect), así que el
        // status HTTP no distingue éxito de error de sesión — se recarga
        // siempre y el toast de la página (flash "success") confirma el
        // resultado real.
        document.querySelectorAll('.wf-toggle-active').forEach(toggle => {
            toggle.addEventListener('change', async () => {
                const id = toggle.dataset.id;
                toggle.disabled = true;

                try {
                    const response = await fetch(`${workflowUrl}/${id}/toggle`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    if (response.status === 419) {
                        alert('Tu sesión expiró. Por favor recarga la página e intenta de nuevo.');
                        toggle.checked = !toggle.checked;
                        toggle.disabled = false;
                        return;
                    }

                    window.location.reload();
                } catch (err) {
                    console.error('Error:', err);
                    toggle.checked = !toggle.checked;
                    toggle.disabled = false;
                }
            });
        });

        // Borrado con modal de confirmación
        const deleteWorkflowModal = document.getElementById('deleteWorkflowModal');
        let deleteWorkflowId = null;

        const openDeleteWorkflowModal = () => {
            deleteWorkflowModal.style.display = 'flex';
        };
        const closeDeleteWorkflowModal = () => {
            deleteWorkflowModal.style.display = 'none';
        };

        document.querySelectorAll('.btn-delete-workflow').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteWorkflowId = btn.dataset.id;
                document.getElementById('delWorkflowName').textContent = btn.dataset.name;
                openDeleteWorkflowModal();
            });
        });

        document.getElementById('delWorkflowCancel').addEventListener('click', closeDeleteWorkflowModal);
        deleteWorkflowModal.addEventListener('click', (e) => {
            if (e.target === deleteWorkflowModal) closeDeleteWorkflowModal();
        });

        document.getElementById('delWorkflowConfirm').addEventListener('click', async () => {
            try {
                // El controller responde con redirect (éxito o error de
                // "tiene inscripciones activas") vía flash session, no JSON
                // — se recarga siempre y el toast muestra el resultado real.
                await fetch(`${workflowUrl}/${deleteWorkflowId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'DELETE',
                    }),
                });

                closeDeleteWorkflowModal();
                window.location.reload();
            } catch (err) {
                console.error('Error:', err);
                closeDeleteWorkflowModal();
            }
        });
    </script>
@endpush
