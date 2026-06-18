@push('scripts')
    <script>
        (function() {
            const catalogProducts = @json($catalogProducts);
            const existingItems = @json($existingItems);
            // Pre-load items from existing order
            let items = existingItems.map((item, i) => ({
                ...item,
                id: i + 1
            }));
            let itemCounter = items.length;

            // ── Supplier info
            const supplierSelect = document.getElementById('supplierSelect');
            const supplierInfoCard = document.getElementById('supplierInfoCard');

            supplierSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (!this.value) {
                    supplierInfoCard.style.display = 'none';
                    return;
                }
                document.getElementById('infoCompany').textContent = opt.dataset.company || '—';
                document.getElementById('infoContact').textContent = opt.dataset.contact || '—';
                document.getElementById('infoEmail').textContent = opt.dataset.email || '—';
                document.getElementById('infoPhone').textContent = opt.dataset.phone || '—';
                document.getElementById('infoRfc').textContent = opt.dataset.rfc || '—';
                document.getElementById('infoTerms').textContent = opt.dataset.terms || '—';
                supplierInfoCard.style.display = 'block';
            });

            //  Totals
            function fmt(n) {
                return '$' + (parseFloat(n) || 0).toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function calcTotals() {
                let subtotal = 0;
                items.forEach(item => {
                    const qty = parseFloat(item.qty) || 0;
                    const price = parseFloat(item.price) || 0;
                    const disc = parseFloat(item.disc) || 0;
                    item.lineTotal = qty * price * (1 - disc / 100);
                    subtotal += item.lineTotal;
                    const el = document.getElementById('po-line-' + item.id);
                    if (el) el.textContent = fmt(item.lineTotal);
                });

                const discPct = parseFloat(document.getElementById('globalDiscount').value) || 0;
                const taxPct = parseFloat(document.getElementById('taxRate').value) || 0;
                const discount = subtotal * (discPct / 100);
                const base = subtotal - discount;
                const tax = base * (taxPct / 100);
                const total = base + tax;

                document.getElementById('summarySubtotal').textContent = fmt(subtotal);
                document.getElementById('summaryDiscount').textContent = '-' + fmt(discount);
                document.getElementById('summaryBase').textContent = fmt(base);
                document.getElementById('summaryTax').textContent = fmt(tax);
                document.getElementById('summaryTotal').textContent = fmt(total);
                document.getElementById('hiddenSubtotal').value = subtotal.toFixed(2);
                document.getElementById('hiddenTaxTotal').value = tax.toFixed(2);
                document.getElementById('hiddenTotal').value = total.toFixed(2);
            }

            document.getElementById('globalDiscount').addEventListener('input', calcTotals);
            document.getElementById('taxRate').addEventListener('input', calcTotals);

            // Render rows
            function renderItems() {
                const tbody = document.getElementById('poItemsBody');
                const emptyRow = document.getElementById('poEmptyRow');

                tbody.querySelectorAll('.po-item-row').forEach(r => r.remove());

                if (items.length === 0) {
                    emptyRow.style.display = '';
                    return;
                }
                emptyRow.style.display = 'none';

                items.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    tr.className = 'po-item-row';
                    tr.dataset.id = item.id;

                    tr.innerHTML = `
                <td style="color:#9ca3af;font-size:13px;">${index + 1}</td>
                <td>
                    ${item.fromCatalog
                        ? `<p class="po-item-name">${item.name}</p>`
                        : `<input type="text" class="po-item-input" data-field="name"
                                        value="${item.name}"
                                        placeholder="Descripción del producto o servicio">`
                    }
                    <input type="text" class="po-item-comment" data-field="comment"
                        value="${item.comment}" placeholder="Comentario opcional...">
                    <input type="hidden" name="items[${index}][product_id]"       value="${item.productId ?? ''}">
                    <input type="hidden" name="items[${index}][product_name]"     value="${item.name}"    class="h-name">
                    <input type="hidden" name="items[${index}][product_sku]"      value="${item.sku}"     class="h-sku">
                    <input type="hidden" name="items[${index}][comment]"          value="${item.comment}" class="h-comment">
                    <input type="hidden" name="items[${index}][quantity]"         value="${item.qty}"     class="h-qty">
                    <input type="hidden" name="items[${index}][unit_price]"       value="${item.price}"   class="h-price">
                    <input type="hidden" name="items[${index}][discount_percent]" value="${item.disc}"    class="h-disc">
                    <input type="hidden" name="items[${index}][line_total]"       value="${item.lineTotal ?? 0}" class="h-line">
                    <input type="hidden" name="items[${index}][sort_order]"       value="${index}">
                </td>
                <td>
                    <input type="text"
                        class="po-item-input ${item.fromCatalog ? 'po-item-input--readonly' : ''}"
                        data-field="sku" value="${item.sku}" placeholder="SKU"
                        ${item.fromCatalog ? 'readonly' : ''}>
                </td>
                <td>
                    <input type="number" class="po-item-input" data-field="qty"
                        value="${item.qty}" min="1" style="text-align:center;">
                </td>
                <td>
                    <input type="number" class="po-item-input" data-field="price"
                        value="${item.price}" min="0" step="0.01" style="text-align:center;">
                </td>
                <td>
                    <input type="number" class="po-item-input" data-field="disc"
                        value="${item.disc}" min="0" max="100" step="0.01" style="text-align:center;">
                </td>
                <td>
                    <span id="po-line-${item.id}" class="po-item-total">
                        ${fmt(item.lineTotal ?? 0)}
                    </span>
                </td>
                <td>
                    <button type="button" class="po-item-remove" data-remove="${item.id}">✕</button>
                </td>
            `;

                    tr.querySelectorAll('[data-field]').forEach(input => {
                        input.addEventListener('input', function() {
                            const f = this.dataset.field;
                            if (f === 'qty') {
                                item.qty = this.value;
                                tr.querySelector('.h-qty').value = this.value;
                            }
                            if (f === 'price') {
                                item.price = this.value;
                                tr.querySelector('.h-price').value = this.value;
                            }
                            if (f === 'disc') {
                                item.disc = this.value;
                                tr.querySelector('.h-disc').value = this.value;
                            }
                            if (f === 'name') {
                                item.name = this.value;
                                tr.querySelector('.h-name').value = this.value;
                            }
                            if (f === 'sku') {
                                item.sku = this.value;
                                tr.querySelector('.h-sku').value = this.value;
                            }
                            if (f === 'comment') {
                                item.comment = this.value;
                                tr.querySelector('.h-comment').value = this.value;
                            }
                            calcTotals();
                        });
                    });

                    tr.querySelector('[data-remove]').addEventListener('click', () => {
                        items = items.filter(i => i.id !== item.id);
                        renderItems();
                        calcTotals();
                    });

                    tbody.appendChild(tr);
                });

                calcTotals();
            }

            // ── Add free line ───────────────────────────────────────
            function addFreeLine() {
                itemCounter++;
                items.push({
                    id: itemCounter,
                    productId: null,
                    fromCatalog: false,
                    name: '',
                    sku: '',
                    comment: '',
                    qty: 1,
                    price: 0,
                    disc: 0,
                    lineTotal: 0
                });
                renderItems();
            }

            document.getElementById('btnAddFreeLine').addEventListener('click', addFreeLine);

            // ── Add from catalog ────────────────────────────────────
            function addFromCatalog(product) {
                itemCounter++;
                items.push({
                    id: itemCounter,
                    productId: product.id,
                    fromCatalog: true,
                    name: product.name,
                    sku: product.sku,
                    comment: '',
                    qty: 1,
                    price: product.price,
                    disc: 0,
                    lineTotal: 0
                });
                renderItems();
            }

            // ── Inline product search ───────────────────────────────
            const poInput = document.getElementById('poProductInput');
            const poDropdown = document.getElementById('poProductDropdown');
            const poList = document.getElementById('poProductList');
            const poLoading = document.getElementById('poProductLoading');
            const poEmpty = document.getElementById('poProductEmpty');
            const poClear = document.getElementById('poProductClear');

            function renderSearchResults(query) {
                const q = query.toLowerCase();
                const filtered = q.length >= 2 ?
                    catalogProducts.filter(p =>
                        p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)) :
                    catalogProducts.slice(0, 15);

                poLoading.style.display = 'none';

                if (filtered.length === 0) {
                    poEmpty.style.display = 'flex';
                    poList.style.display = 'none';
                    return;
                }

                poEmpty.style.display = 'none';
                poList.style.display = 'block';
                poList.innerHTML = '';

                filtered.forEach(p => {
                    const li = document.createElement('li');
                    li.className = 'po-inline-search__item';
                    li.innerHTML = `
                <div class="po-inline-search__item-info">
                    <span class="po-inline-search__item-name">${p.name}</span>
                    <span class="po-inline-search__item-sku">${p.sku || 'Sin SKU'}</span>
                </div>
                <span class="po-inline-search__item-price">${fmt(p.price)}</span>
                <button type="button" class="po-inline-search__add-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14"/><path d="M5 12h14"/>
                    </svg>
                </button>
            `;
                    li.querySelector('.po-inline-search__add-btn').addEventListener('click', () => {
                        addFromCatalog(p);
                        poInput.value = '';
                        poClear.style.display = 'none';
                        poDropdown.style.display = 'none';
                        poInput.focus();
                    });
                    poList.appendChild(li);
                });
            }

            poInput.addEventListener('input', function() {
                poClear.style.display = this.value ? 'block' : 'none';
                poDropdown.style.display = 'block';
                renderSearchResults(this.value);
            });

            poInput.addEventListener('focus', () => {
                poDropdown.style.display = 'block';
                renderSearchResults(poInput.value);
            });

            poClear.addEventListener('click', () => {
                poInput.value = '';
                poClear.style.display = 'none';
                poDropdown.style.display = 'none';
                poInput.focus();
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('#poInlineSearch')) poDropdown.style.display = 'none';
            });

            // ── Validation ──────────────────────────────────────────
            document.getElementById('poEditForm').addEventListener('submit', function(e) {
                let valid = true;

                document.querySelectorAll('.po-field-error').forEach(el => el.classList.remove(
                    'po-field-error'));
                document.querySelectorAll('.po-error-msg').forEach(el => el.remove());

                function markError(el, msg) {
                    el.classList.add('po-field-error');
                    if (!el.nextElementSibling?.classList.contains('po-error-msg')) {
                        const span = document.createElement('span');
                        span.className = 'po-error-msg';
                        span.textContent = msg;
                        el.insertAdjacentElement('afterend', span);
                    }
                    if (valid) {
                        valid = false;
                        setTimeout(() => el.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        }), 50);
                    }
                }

                const supplier = document.getElementById('supplierSelect');
                if (!supplier.value) markError(supplier, 'Selecciona un proveedor');

                const orderDate = document.querySelector('[name="order_date"]');
                if (!orderDate.value) markError(orderDate, 'La fecha de la orden es obligatoria');

                if (items.length === 0) {
                    const toolbar = document.querySelector('.po-products-toolbar');
                    if (!toolbar.nextElementSibling?.querySelector?.('.po-error-msg')) {
                        const span = document.createElement('span');
                        span.className = 'po-error-msg';
                        span.textContent = 'Agrega al menos un producto o línea';
                        toolbar.insertAdjacentElement('afterend', span);
                    }
                    if (valid) {
                        valid = false;
                        toolbar.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                    valid = false;
                }

                items.forEach((item) => {
                    const row = document.querySelector(`.po-item-row[data-id="${item.id}"]`);
                    if (!row) return;

                    if (!item.fromCatalog) {
                        const nameInput = row.querySelector('[data-field="name"]');
                        if (nameInput && !nameInput.value.trim()) {
                            markError(nameInput, 'El nombre del producto es obligatorio');
                        }
                    }

                    const priceInput = row.querySelector('[data-field="price"]');
                    if (priceInput && (parseFloat(priceInput.value) || 0) <= 0) {
                        markError(priceInput, 'El precio debe ser mayor a 0');
                    }

                    const qtyInput = row.querySelector('[data-field="qty"]');
                    if (qtyInput && (parseInt(qtyInput.value) || 0) <= 0) {
                        markError(qtyInput, 'La cantidad debe ser mayor a 0');
                    }

                    // Sync line total
                    const line = (parseFloat(item.qty) || 0) *
                        (parseFloat(item.price) || 0) *
                        (1 - (parseFloat(item.disc) || 0) / 100);
                    if (row) row.querySelector('.h-line').value = line.toFixed(2);
                });

                if (!valid) e.preventDefault();
            });
            renderItems();
        })();
    </script>
@endpush
