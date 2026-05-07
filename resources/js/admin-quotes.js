/* ═══════════════════════════════════════════════════════════
   admin-quotes.js
   Vanilla JS for admin quotes module (create + show)
   Depends on: QuoteForm (defined in public/js/quotes.js)
═══════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    /* ── Accordion height animation ── */
    document.querySelectorAll('.accordion-conditions').forEach(function (details) {
        var summary = details.querySelector('.accordion-conditions__summary');
        var body    = details.querySelector('.accordion-conditions__body');
        if (!summary || !body) return;

        summary.addEventListener('click', function (e) {
            e.preventDefault();

            if (details.open) {
                /* Closing */
                var h = body.scrollHeight;
                body.style.maxHeight  = h + 'px';
                body.style.overflow   = 'hidden';
                body.style.transition = 'max-height 240ms ease';
                requestAnimationFrame(function () {
                    body.style.maxHeight = '0';
                });
                body.addEventListener('transitionend', function onEnd() {
                    details.open     = false;
                    body.style.maxHeight  = '';
                    body.style.overflow   = '';
                    body.style.transition = '';
                    body.removeEventListener('transitionend', onEnd);
                });
            } else {
                /* Opening */
                details.open = true;
                var h = body.scrollHeight;
                body.style.maxHeight  = '0';
                body.style.overflow   = 'hidden';
                body.style.transition = 'max-height 240ms ease';
                requestAnimationFrame(function () {
                    body.style.maxHeight = h + 'px';
                });
                body.addEventListener('transitionend', function onEnd() {
                    body.style.maxHeight  = '';
                    body.style.overflow   = '';
                    body.style.transition = '';
                    body.removeEventListener('transitionend', onEnd);
                });
            }
        });
    });

    /* ── Create form: only run when the quote form exists ── */
    var quoteForm   = document.getElementById('quoteForm');
    var taxRateEl   = document.getElementById('taxRate');
    var discountEl  = document.getElementById('discountTotal');

    if (!quoteForm) return; /* show page — nothing else needed */

    /* Sync IVA display and recalculate on change */
    if (taxRateEl) {
        taxRateEl.addEventListener('input', function () {
            var display = document.getElementById('displayTaxRate');
            if (display) display.textContent = this.value || '0';
            if (window.QuoteForm) QuoteForm.calculateGlobalTotals();
        });
    }

    /* Recalculate when global discount changes */
    if (discountEl) {
        discountEl.addEventListener('input', function () {
            if (window.QuoteForm) QuoteForm.calculateGlobalTotals();
        });
    }

    /* Validate items before submit */
    quoteForm.addEventListener('submit', function (e) {
        if (window.QuoteForm && !QuoteForm.serializeItems()) {
            e.preventDefault();
            alert('Debe agregar al menos un producto.');
        }
    });

    /* ── Product search modal ── */
    var SEARCH_URL = (window.ADMIN_QUOTES_CONFIG && window.ADMIN_QUOTES_CONFIG.searchUrl) || '';

    window.openProductSearch = function () {
        var backdrop = document.getElementById('searchBackdrop');
        var input    = document.getElementById('searchInput');
        var results  = document.getElementById('searchResults');
        if (!backdrop) return;
        backdrop.classList.add('open');
        if (input) input.value = '';
        if (results) results.innerHTML = '<div class="product-search-empty">Escribe para buscar productos...</div>';
        setTimeout(function () { if (input) input.focus(); }, 80);
    };

    window.closeProductSearch = function () {
        var backdrop = document.getElementById('searchBackdrop');
        if (backdrop) backdrop.classList.remove('open');
    };

    window.closeSearchOnBackdrop = function (e) {
        if (e.target === document.getElementById('searchBackdrop')) closeProductSearch();
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeProductSearch();
    });

    var searchTimer;
    var searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = this.value.trim();
            var results = document.getElementById('searchResults');
            if (q.length < 2) {
                if (results) results.innerHTML = '<div class="product-search-empty">Escribe al menos 2 caracteres...</div>';
                return;
            }
            if (results) results.innerHTML = '<div class="product-search-loading">Buscando...</div>';
            searchTimer = setTimeout(function () {
                QuoteSearch.search(q, SEARCH_URL);
            }, 300);
        });
    }

    /* ── QuoteSearch — fetch + render results ── */
    window.QuoteSearch = {
        search: function (q, url) {
            fetch(url + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (products) {
                    var container = document.getElementById('searchResults');
                    if (!container) return;
                    if (!products.length) {
                        container.innerHTML = '<div class="product-search-empty">No se encontraron productos.</div>';
                        return;
                    }
                    container.innerHTML = products.map(function (p) {
                        var imgHtml = p.cover_image_url
                            ? '<img src="' + p.cover_image_url + '" class="product-search-img" loading="lazy" onerror="this.style.display=\'none\'">'
                            : '<div class="product-search-img-placeholder">'
                              + '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">'
                              + '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
                              + '<path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>'
                              + '</svg></div>';
                        var safeP = JSON.stringify(p).replace(/"/g, '&quot;');
                        return '<div class="product-search-item" onclick="QuoteSearch.select(' + safeP + ')">'
                            + imgHtml
                            + '<div class="product-search-info">'
                            + '<div class="product-search-name">' + p.name + '</div>'
                            + '<div class="product-search-sku">SKU: ' + (p.sku || '—') + ' · Stock: ' + p.stock + '</div>'
                            + '</div>'
                            + '<div class="product-search-price">$' + parseFloat(p.price).toFixed(2) + '</div>'
                            + '</div>';
                    }).join('');
                })
                .catch(function () {
                    var container = document.getElementById('searchResults');
                    if (container) container.innerHTML = '<div class="product-search-empty">Error al buscar. Intenta de nuevo.</div>';
                });
        },

        select: function (product) {
            if (window.QuoteForm) QuoteForm.addProductRow(product);
            closeProductSearch();
        }
    };

})();
