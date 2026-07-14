@push('scripts')
    <script>
        (function() {
            const TOTAL = {{ $products->count() }};
            const searchInput = document.getElementById('prodSearch');
            const statusFilter = document.getElementById('prodStatusFilter');
            const btnGrid = document.getElementById('btnViewGrid');
            const btnList = document.getElementById('btnViewList');
            const listView = document.getElementById('prodListView');
            const gridView = document.getElementById('prodGridView');
            const emptyRow = document.getElementById('prodEmptyRow');
            const countLabel = document.getElementById('prodCountLabel');
            const tableRows = document.querySelectorAll('#prodTableBody tr[data-name]');
            const gridCards = document.querySelectorAll('#prodGridView .prod-grid-card');

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();
                const status = statusFilter.value;
                let visible = 0;

                tableRows.forEach((row, i) => {
                    const card = gridCards[i];
                    const nameMatch = row.dataset.name.includes(query) || row.dataset.sku.includes(query);
                    const stMatch = status === 'all' || row.dataset.status === status;
                    const show = nameMatch && stMatch;

                    row.style.display = show ? '' : 'none';
                    if (card) card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                emptyRow.style.display = visible === 0 ? '' : 'none';
                countLabel.textContent = `Mostrando ${visible} de ${TOTAL} productos`;
            }

            searchInput.addEventListener('input', applyFilters);
            statusFilter.addEventListener('change', applyFilters);

            function setView(view) {
                if (view === 'grid') {
                    listView.style.display = 'none';
                    gridView.classList.add('active');
                    btnGrid.classList.add('active');
                    btnList.classList.remove('active');
                } else {
                    listView.style.display = '';
                    gridView.classList.remove('active');
                    btnList.classList.add('active');
                    btnGrid.classList.remove('active');
                }
            }

            btnGrid.addEventListener('click', () => setView('grid'));
            btnList.addEventListener('click', () => setView('list'));

            // FIX BUG 2 (Paso B): self-contained toast — this module has no
            // shared toast helper/CSS class (unlike Users/Clientes), so it's
            // built here with inline styles to stay within this module's
            // isolated files.
            function showProductToast(message, type = 'success') {
                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.cssText = `
                    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
                    background: ${type === 'error' ? '#fef2f2' : '#f0fdf4'};
                    color: ${type === 'error' ? '#991b1b' : '#166534'};
                    border: 1px solid ${type === 'error' ? '#fecaca' : '#bbf7d0'};
                    padding: 14px 18px; border-radius: 10px; font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.12); max-width: 360px;
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }

            // FIX (Fase F finding): create() and update() redirect back here
            // with session('success') / session('error'), but nothing ever
            // rendered it — the user got zero feedback after creating or
            // editing a product. Reuse showProductToast() for it.
            @if (session('success'))
                showProductToast(@json(session('success')));
            @endif
            @if (session('error'))
                showProductToast(@json(session('error')), 'error');
            @endif

            // Delete modal
            const deleteProductModal = document.getElementById('deleteProductModal');
            const deleteProductForm = document.getElementById('deleteProductForm');
            const delProductCancelBtn = document.getElementById('delProductConfirmCancel');
            const deleteProductUrl = '{{ url('/admin/productos/eliminar') }}';
            let deleteProductId = null;

            document.querySelectorAll('.btn-delete-product').forEach(btn => {
                btn.addEventListener('click', () => {
                    deleteProductId = btn.dataset.id;
                    document.getElementById('delProductConfirmName').textContent = btn.dataset.name;
                    document.getElementById('delProductConfirmSku').textContent = btn.dataset.sku;
                    document.getElementById('delProductConfirmAvatar').textContent =
                        btn.dataset.name.charAt(0).toUpperCase();
                    deleteProductForm.action = deleteProductUrl + '/' + btn.dataset.id;
                    deleteProductModal.classList.add('active');
                });
            });

            // FIX BUG 2 (Paso B): intercept the submit and use fetch so a
            // 422 (product still referenced elsewhere) can be shown as a
            // toast instead of the browser navigating to a raw JSON page.
            // The form itself still submits _method=DELETE via FormData
            // (correct pattern, unchanged) — this only wraps it in fetch.
            deleteProductForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                try {
                    const response = await fetch(deleteProductForm.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: new FormData(deleteProductForm),
                    });

                    const data = await response.json();

                    if (response.ok) {
                        deleteProductModal.classList.remove('active');
                        const row = document.querySelector(`.btn-delete-product[data-id="${deleteProductId}"]`)
                            ?.closest('tr');
                        if (row) row.remove();
                        const card = document.querySelector(`.btn-delete-product[data-id="${deleteProductId}"]`)
                            ?.closest('.prod-grid-card');
                        if (card) card.remove();
                        showProductToast(data.message ?? 'Producto eliminado correctamente.');
                        // FIX QA: removing the row/card left "Mostrando X de Y
                        // productos" and "Total en inventario" stale at their
                        // pre-delete values (Y and the stock sum are only
                        // computed server-side on page load). A reload keeps
                        // both counters accurate without duplicating that
                        // arithmetic in JS.
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        deleteProductModal.classList.remove('active');
                        showProductToast(data.message ?? 'No se pudo eliminar el producto.', 'error');
                    }
                } catch (err) {
                    console.error('Error deleting product:', err);
                    deleteProductModal.classList.remove('active');
                    showProductToast('Error de conexión. Intenta de nuevo.', 'error');
                }
            });

            delProductCancelBtn.addEventListener('click', () =>
                deleteProductModal.classList.remove('active'));
            deleteProductModal.addEventListener('click', (e) => {
                if (e.target === deleteProductModal) deleteProductModal.classList.remove('active');
            });
        })();
    </script>
@endpush
