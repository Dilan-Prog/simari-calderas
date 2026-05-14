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

            // Delete modal
            const deleteProductModal = document.getElementById('deleteProductModal');
            const deleteProductForm = document.getElementById('deleteProductForm');
            const delProductCancelBtn = document.getElementById('delProductConfirmCancel');
            const deleteProductUrl = '{{ url('/admin/productos/eliminar') }}';

            document.querySelectorAll('.btn-delete-product').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('delProductConfirmName').textContent = btn.dataset.name;
                    document.getElementById('delProductConfirmSku').textContent = btn.dataset.sku;
                    document.getElementById('delProductConfirmAvatar').textContent =
                        btn.dataset.name.charAt(0).toUpperCase();
                    deleteProductForm.action = deleteProductUrl + '/' + btn.dataset.id;
                    deleteProductModal.classList.add('active');
                });
            });

            delProductCancelBtn.addEventListener('click', () =>
                deleteProductModal.classList.remove('active'));
            deleteProductModal.addEventListener('click', (e) => {
                if (e.target === deleteProductModal) deleteProductModal.classList.remove('active');
            });
        })();
    </script>
@endpush
