(function () {
    'use strict';

    var rowCount = 0;

    var QuoteForm = {
        addProductRow: function (product, isExisting) {
            isExisting = isExisting || false;
            var existingEmpty = document.getElementById('emptyRow');
            if (existingEmpty) existingEmpty.remove();

            rowCount++;
            var qty       = isExisting ? (product.quantity || 1)                  : 1;
            var price     = isExisting ? (product.unit_price || product.price || 0) : (product.price || 0);
            var disc      = isExisting ? (product.discount_percent || 0)           : 0;
            var notes     = isExisting ? (product.notes || '')                     : '';
            var lineTotal = QuoteForm._calcLineTotal(qty, price, disc);

            var descHidden = notes ? '' : ' style="display:none"';

            var tr = document.createElement('tr');
            tr.dataset.productId  = product.product_id || product.id || '';
            tr.dataset.productSku = product.product_sku || product.sku || '';
            tr.innerHTML =
                '<td class="col-num">' + rowCount + '</td>' +
                '<td class="col-name">' +
                    '<div class="item-name-wrap">' +
                        '<div class="item-name-row">' +
                            '<input type="text" class="cell-input item-name" value="' + _esc(product.product_name || product.name || '') + '" placeholder="Nombre del producto o servicio">' +
                            '<button type="button" class="item-desc-toggle' + (notes ? ' item-desc-toggle--active' : '') + '" title="Agregar descripción detallada" aria-label="Agregar descripción">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
                            '</button>' +
                        '</div>' +
                        '<textarea class="cell-input item-desc" placeholder="Descripción detallada del servicio..." rows="3"' + descHidden + '>' + _esc(notes) + '</textarea>' +
                    '</div>' +
                '</td>' +
                '<td class="col-sku">' +
                    '<input type="text" class="cell-input item-sku" value="' + _esc(product.product_sku || product.sku || '') + '" placeholder="SKU">' +
                '</td>' +
                '<td class="col-qty">' +
                    '<input type="number" class="cell-input item-qty" value="' + qty + '" min="1" step="1">' +
                '</td>' +
                '<td class="col-price">' +
                    '<input type="number" class="cell-input item-price" value="' + parseFloat(price).toFixed(2) + '" min="0" step="0.01">' +
                '</td>' +
                '<td class="col-disc">' +
                    '<input type="number" class="cell-input item-disc" value="' + parseFloat(disc).toFixed(2) + '" min="0" max="100" step="0.01">' +
                '</td>' +
                '<td class="col-total item-line-total">$' + lineTotal.toFixed(2) + '</td>' +
                '<td class="col-del">' +
                    '<button type="button" class="qform-del-btn" onclick="QuoteForm.removeRow(this)" title="Eliminar" aria-label="Eliminar fila">' +
                        '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>' +
                    '</button>' +
                '</td>';

            /* Toggle description textarea */
            var toggleBtn = tr.querySelector('.item-desc-toggle');
            var descArea  = tr.querySelector('.item-desc');
            toggleBtn.addEventListener('click', function () {
                var hidden = descArea.style.display === 'none';
                descArea.style.display = hidden ? '' : 'none';
                toggleBtn.classList.toggle('item-desc-toggle--active', hidden);
                if (hidden) descArea.focus();
            });

            tr.querySelector('.item-qty').addEventListener('input', function () {
                QuoteForm.calculateRowTotal(tr);
            });
            tr.querySelector('.item-price').addEventListener('input', function () {
                QuoteForm.calculateRowTotal(tr);
            });
            tr.querySelector('.item-disc').addEventListener('input', function () {
                QuoteForm.calculateRowTotal(tr);
            });

            document.getElementById('itemsTableBody').appendChild(tr);
            QuoteForm.calculateGlobalTotals();
        },

        addFreeRow: function () {
            QuoteForm.addProductRow({
                product_id: null, product_name: '', product_sku: '',
                price: 0, quantity: 1, discount_percent: 0,
            });
        },

        removeRow: function (btn) {
            var tr = btn.closest('tr');
            tr.remove();
            QuoteForm._renumberRows();
            QuoteForm.calculateGlobalTotals();
            if (document.getElementById('itemsTableBody').children.length === 0) {
                var empty = document.createElement('tr');
                empty.id = 'emptyRow';
                empty.innerHTML = '<td colspan="8" style="text-align:center;padding:30px;color:#444;font-size:13px;">Agrega productos usando los botones de arriba</td>';
                document.getElementById('itemsTableBody').appendChild(empty);
            }
        },

        calculateRowTotal: function (tr) {
            var qty   = parseFloat(tr.querySelector('.item-qty').value)   || 0;
            var price = parseFloat(tr.querySelector('.item-price').value) || 0;
            var disc  = parseFloat(tr.querySelector('.item-disc').value)  || 0;
            var total = QuoteForm._calcLineTotal(qty, price, disc);
            tr.querySelector('.item-line-total').textContent = '$' + total.toFixed(2);
            QuoteForm.calculateGlobalTotals();
        },

        calculateGlobalTotals: function () {
            var rows = document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)');
            var subtotal = 0;
            rows.forEach(function (tr) {
                var qty   = parseFloat(tr.querySelector('.item-qty')   ? tr.querySelector('.item-qty').value   : 0) || 0;
                var price = parseFloat(tr.querySelector('.item-price') ? tr.querySelector('.item-price').value : 0) || 0;
                var disc  = parseFloat(tr.querySelector('.item-disc')  ? tr.querySelector('.item-disc').value  : 0) || 0;
                subtotal += QuoteForm._calcLineTotal(qty, price, disc);
            });

            var discountInput = document.getElementById('discountTotal');
            var discount = discountInput ? (parseFloat(discountInput.value) || 0) : 0;

            var taxRateInput = document.getElementById('taxRate');
            var taxRate = taxRateInput ? (parseFloat(taxRateInput.value) || 0) : 16;

            var taxable  = subtotal - discount;
            var taxTotal = taxable * (taxRate / 100);
            var total    = taxable + taxTotal;

            var el = function (id) { return document.getElementById(id); };
            if (el('displaySubtotal'))  el('displaySubtotal').textContent  = '$' + subtotal.toFixed(2);
            if (el('displayTaxRate'))   el('displayTaxRate').textContent   = taxRate;
            if (el('displayTaxTotal'))  el('displayTaxTotal').textContent  = '$' + taxTotal.toFixed(2);
            if (el('displayTotal'))     el('displayTotal').textContent     = '$' + total.toFixed(2);
        },

        serializeItems: function () {
            var rows = document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)');
            if (!rows.length) return false;

            var items = [];
            rows.forEach(function (tr) {
                var qty      = parseFloat(tr.querySelector('.item-qty').value)   || 0;
                var price    = parseFloat(tr.querySelector('.item-price').value) || 0;
                var disc     = parseFloat(tr.querySelector('.item-disc').value)  || 0;
                var notesEl  = tr.querySelector('.item-desc');

                items.push({
                    product_id:       tr.dataset.productId || null,
                    product_name:     tr.querySelector('.item-name').value.trim(),
                    product_sku:      tr.querySelector('.item-sku').value.trim() || null,
                    quantity:         qty,
                    unit_price:       price,
                    discount_percent: disc,
                    tax_percent:      parseFloat((document.getElementById('taxRate') || {}).value) || 16,
                    line_total:       QuoteForm._calcLineTotal(qty, price, disc),
                    notes:            notesEl ? (notesEl.value.trim() || null) : null,
                });
            });

            document.getElementById('itemsJson').value = JSON.stringify(items);
            return true;
        },

        _calcLineTotal: function (qty, price, discPercent) {
            return qty * price * (1 - discPercent / 100);
        },

        _renumberRows: function () {
            var rows = document.querySelectorAll('#itemsTableBody tr:not(#emptyRow)');
            rowCount = 0;
            rows.forEach(function (tr) {
                rowCount++;
                var numCell = tr.querySelector('.col-num');
                if (numCell) numCell.textContent = rowCount;
            });
        },
    };

    function _esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    window.QuoteForm = QuoteForm;
})();
