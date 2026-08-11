@push('scripts')
    <script>
        (function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const baseUrl = '{{ url('/admin/inventario/almacenes') }}';

            const modal = document.getElementById('warehouseModal');
            const form = document.getElementById('warehouseForm');
            const errorsBox = document.getElementById('warehouse-modal-errors');
            let currentId = null;
            let isEditMode = false;

            function resetForm() {
                form.reset();
                document.getElementById('warehouseIsActive').value = '1';
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';
                currentId = null;
                isEditMode = false;
                document.getElementById('warehouseModalTitle').textContent = 'Nuevo Almacén';
                document.getElementById('warehouseSubmitBtn').textContent = 'Crear Almacén';
            }

            document.getElementById('btnNewWarehouse').addEventListener('click', () => {
                resetForm();
                modal.style.display = 'flex';
            });

            document.getElementById('closeWarehouseModal').addEventListener('click', () => modal.style.display = 'none');
            document.getElementById('cancelWarehouseModal').addEventListener('click', () => modal.style.display = 'none');
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.style.display = 'none';
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = '';

                const name = document.getElementById('warehouseName').value.trim();
                if (!name) {
                    errorsBox.innerHTML = '<p>El nombre del almacén es obligatorio.</p>';
                    errorsBox.style.display = 'block';
                    return;
                }

                const payload = {
                    name,
                    location: document.getElementById('warehouseLocation').value.trim() || null,
                    is_active: document.getElementById('warehouseIsActive').value === '1',
                };

                const url = isEditMode ? `${baseUrl}/${currentId}` : baseUrl;
                const method = isEditMode ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        const messages = data.errors ? Object.values(data.errors).flat() : [data.message ?? 'No se pudo guardar el almacén.'];
                        errorsBox.innerHTML = messages.map(m => `<p>${m}</p>`).join('');
                        errorsBox.style.display = 'block';
                        return;
                    }

                    modal.style.display = 'none';
                    window.location.reload();
                } catch (err) {
                    console.error('Error saving warehouse:', err);
                    errorsBox.innerHTML = '<p>Error de conexión. Intenta de nuevo.</p>';
                    errorsBox.style.display = 'block';
                }
            });

            document.querySelectorAll('.btn-edit-warehouse').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentId = btn.dataset.id;
                    isEditMode = true;
                    document.getElementById('warehouseModalTitle').textContent = 'Editar Almacén';
                    document.getElementById('warehouseSubmitBtn').textContent = 'Guardar Cambios';
                    document.getElementById('warehouseName').value = btn.dataset.name ?? '';
                    document.getElementById('warehouseLocation').value = btn.dataset.location ?? '';
                    document.getElementById('warehouseIsActive').value = btn.dataset.active === '1' ? '1' : '0';
                    errorsBox.style.display = 'none';
                    modal.style.display = 'flex';
                });
            });

            // Delete
            const deleteModal = document.getElementById('deleteWarehouseModal');
            let deleteId = null;

            document.querySelectorAll('.btn-delete-warehouse').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.dataset.blocked === '1') {
                        alert(`No puedes eliminar "${btn.dataset.name}" porque tiene movimientos de inventario asociados.`);
                        return;
                    }
                    deleteId = btn.dataset.id;
                    document.getElementById('delWarehouseName').textContent = btn.dataset.name;
                    document.getElementById('delWarehouseAvatar').textContent = btn.dataset.name.charAt(0).toUpperCase();
                    deleteModal.classList.add('active');
                });
            });

            document.getElementById('delWarehouseCancel').addEventListener('click', () => deleteModal.classList.remove('active'));
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) deleteModal.classList.remove('active');
            });

            document.getElementById('delWarehouseConfirm').addEventListener('click', async () => {
                try {
                    const response = await fetch(`${baseUrl}/${deleteId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        deleteModal.classList.remove('active');
                        window.location.reload();
                    } else {
                        alert(data.message ?? 'No se pudo eliminar el almacén.');
                        deleteModal.classList.remove('active');
                    }
                } catch (err) {
                    console.error('Error deleting warehouse:', err);
                }
            });
        })();
    </script>
@endpush
