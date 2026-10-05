{{--
    JS propio de la pantalla Secciones del Sitio (listado): botones
    nuevo/editar (abren HomeSectionEditor, ver partials/editor.blade.php),
    borrado con confirmación (las plantillas en uso piden doble confirmación)
    y reordenamiento por arrastre. El modal de crear/editar y su JS viven en
    partials/editor.blade.php + _editor_scripts.blade.php.
--}}
@push('scripts')
    <script>
        (function () {
            const homeSectionUrl = '{{ url('/admin/inicio-secciones') }}';
            const currentPage = @json($page ?? 'home');
            const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
            const reloadSoon = () => setTimeout(() => window.location.reload(), 200);

            /* ── Nuevo / editar: delegan en el editor reutilizable ── */
            const newBtn = document.getElementById('btnNewHomeSection');
            if (newBtn) {
                newBtn.addEventListener('click', () => {
                    window.HomeSectionEditor.open({
                        page: currentPage,
                        onSaved: () => {
                            queueCenterToast(currentPage === 'product_template' ? 'Plantilla creada correctamente.' : 'Sección creada correctamente.');
                            reloadSoon();
                        },
                    });
                });
            }

            document.querySelectorAll('.btn-edit-home-section').forEach(btn => {
                btn.addEventListener('click', () => {
                    window.HomeSectionEditor.open({
                        sectionId: btn.dataset.id,
                        onSaved: () => {
                            queueCenterToast('Sección actualizada correctamente.');
                            reloadSoon();
                        },
                    });
                });
            });

            /* ── Delete modal ──
               Una plantilla en uso responde 409 {in_use: N}: se pide una
               segunda confirmación y se reenvía con confirm=1. */
            const deleteModal = document.getElementById('deleteHomeSectionModal');
            const delDesc = document.getElementById('delHomeSectionDesc');
            const delConfirmBtn = document.getElementById('delHomeSectionConfirm');
            const defaultDesc = delDesc.textContent;
            let deleteId = null;
            let deleteNeedsConfirm = false;

            function resetDeleteModal() {
                deleteNeedsConfirm = false;
                delDesc.textContent = defaultDesc;
                delConfirmBtn.textContent = 'Eliminar';
            }

            document.querySelectorAll('.btn-delete-home-section').forEach(btn => {
                btn.addEventListener('click', () => {
                    deleteId = btn.dataset.id;
                    resetDeleteModal();
                    document.getElementById('delHomeSectionTitle').textContent = btn.dataset.title;
                    document.getElementById('delHomeSectionAvatar').textContent = btn.dataset.title.charAt(0).toUpperCase();
                    deleteModal.classList.add('active');
                });
            });

            document.getElementById('delHomeSectionCancel').addEventListener('click', () => deleteModal.classList.remove('active'));
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) deleteModal.classList.remove('active');
            });

            delConfirmBtn.addEventListener('click', async () => {
                const payload = { _method: 'DELETE' };
                if (deleteNeedsConfirm) payload.confirm = 1;

                try {
                    const response = await fetch(`${homeSectionUrl}/eliminar/${deleteId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.ok) {
                        deleteModal.classList.remove('active');
                        queueCenterToast('Sección eliminada correctamente.');
                        reloadSoon();
                    } else if (response.status === 409 && data.in_use) {
                        deleteNeedsConfirm = true;
                        delDesc.textContent = `Esta plantilla se usa en ${data.in_use} producto(s). Si la eliminas, desaparece de todos ellos. Esta acción no se puede deshacer.`;
                        delConfirmBtn.textContent = 'Eliminar de todos';
                    } else {
                        showCenterToast(data.message ?? 'No se pudo eliminar la sección.', 'error');
                        deleteModal.classList.remove('active');
                    }
                } catch (err) {
                    console.error('Error deleting home section:', err);
                    showCenterToast('Error de conexión al eliminar la sección.', 'error');
                }
            });

            /* ── Drag to reorder sections, saved instantly via AJAX ──
               (las plantillas no se arrastran: su orden no importa) */
            (function () {
                const tableBody = document.getElementById('homeSectionsTableBody');
                if (!tableBody || currentPage === 'product_template') return;

                const reorderUrl = `${homeSectionUrl}/reordenar`;
                let dragSrcId = null;

                function currentOrder() {
                    return Array.from(tableBody.querySelectorAll('.hs-row')).map(el => el.dataset.id);
                }

                function refreshSortOrderColumn() {
                    Array.from(tableBody.querySelectorAll('.hs-row')).forEach((row, i) => {
                        const cell = row.querySelector('.hs-sort-order');
                        if (cell) cell.textContent = i;
                    });
                }

                async function persistOrder() {
                    try {
                        const response = await fetch(reorderUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf(),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ order: currentOrder() }),
                        });
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            showCenterToast(data.message ?? 'No se pudo guardar el nuevo orden.', 'error');
                        } else {
                            showCenterToast('Orden actualizado.');
                        }
                    } catch (err) {
                        console.error('Error saving order:', err);
                        showCenterToast('Error de conexión al guardar el orden.', 'error');
                    }
                }

                tableBody.querySelectorAll('.hs-row').forEach(function (row) {
                    row.addEventListener('dragstart', function () {
                        dragSrcId = row.dataset.id;
                        row.classList.add('is-dragging');
                    });
                    row.addEventListener('dragend', function () {
                        row.classList.remove('is-dragging');
                    });
                    row.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        if (dragSrcId === null || dragSrcId === row.dataset.id) return;
                        row.classList.add('drag-over');
                    });
                    row.addEventListener('dragleave', function () {
                        row.classList.remove('drag-over');
                    });
                    row.addEventListener('drop', function (e) {
                        e.preventDefault();
                        row.classList.remove('drag-over');
                        if (dragSrcId === null || dragSrcId === row.dataset.id) return;

                        const srcEl = tableBody.querySelector('.hs-row[data-id="' + dragSrcId + '"]');
                        dragSrcId = null;
                        if (!srcEl) return;

                        tableBody.insertBefore(srcEl, row);
                        refreshSortOrderColumn();
                        persistOrder();
                    });
                });
            })();
        })();
    </script>
@endpush
