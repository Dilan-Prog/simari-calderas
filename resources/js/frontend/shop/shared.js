import Alpine from 'alpinejs';

Alpine.store('shop', {
    cartCount: 0,

    // Alpine llama init() automáticamente al registrar el store (mismo
    // momento en que Alpine.start() arranca) — así el badge del carrito
    // se llena solo al cargar cualquier página del shop, sin que cada
    // vista tenga que acordarse de llamarlo.
    init() {
        this.refreshCartCount();
    },

    async refreshCartCount() {
        try {
            const response = await fetch('/carrito/mini', { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            this.cartCount = data.cartCount ?? 0;
        } catch (err) {
            // Silencioso: si falla, el badge simplemente se queda en su
            // último valor conocido en vez de romper la página.
        }
    },
});

Alpine.data('searchOverlay', () => ({
    open: false,
    loading: false,
    query: '',
    orden: 'relevancia',
    selectedCategory: null,
    selectedBrand: null,
    categories: [],
    brands: [],
    total: 0,
    productsHtml: '',
    debounceTimer: null,
    requestId: 0,

    // El overlay usa --eq-header-height (ver shared.css) para no quedar
    // pegado al header en vez de un padding-top fijo, que se desincroniza
    // cada vez que el header gana/pierde una franja (topbar, nav, etc).
    init() {
        this.syncHeaderHeight();
        window.addEventListener('resize', () => this.syncHeaderHeight());
    },

    syncHeaderHeight() {
        const header = document.querySelector('.eq-header');
        if (!header) return;
        document.documentElement.style.setProperty('--eq-header-height', header.getBoundingClientRect().height + 'px');
    },

    get viewAllUrl() {
        const params = new URLSearchParams();
        if (this.query.trim()) params.set('q', this.query.trim());
        if (this.selectedCategory) params.set('categoria[]', this.selectedCategory);
        if (this.selectedBrand) params.set('marca[]', this.selectedBrand);
        if (this.orden !== 'relevancia') params.set('orden', this.orden);
        return '/catalogo?' + params.toString();
    },

    onInput() {
        clearTimeout(this.debounceTimer);
        if (this.query.trim().length < 2) {
            this.open = false;
            return;
        }
        this.debounceTimer = setTimeout(() => this.fetchResults(), 350);
    },

    onFocus() {
        if (this.query.trim().length >= 2) this.open = true;
    },

    submit() {
        if (this.query.trim().length < 2) return;
        this.fetchResults();
    },

    toggleCategory(id) {
        this.selectedCategory = this.selectedCategory === id ? null : id;
        this.fetchResults();
    },

    toggleBrand(id) {
        this.selectedBrand = this.selectedBrand === id ? null : id;
        this.fetchResults();
    },

    async fetchResults() {
        const q = this.query.trim();
        if (q.length < 2) return;

        // Guards against out-of-order responses: if the user keeps typing,
        // an earlier request can resolve after a later one and overwrite
        // fresher results with stale ones. Only the most recently issued
        // request is allowed to apply its response.
        const thisRequestId = ++this.requestId;

        this.loading = true;
        this.open = true;

        try {
            const params = new URLSearchParams({ q, orden: this.orden });
            if (this.selectedCategory) params.set('categoria[]', this.selectedCategory);
            if (this.selectedBrand) params.set('marca[]', this.selectedBrand);

            const response = await fetch('/buscar-en-vivo?' + params.toString(), {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (thisRequestId !== this.requestId) return;

            this.total = data.total;
            this.categories = data.categories;
            this.brands = data.brands;
            this.productsHtml = data.productsHtml;
        } catch (err) {
            // Live preview failing shouldn't break the page — "Ver todos los
            // resultados" still works as a plain link to the real catalog.
        } finally {
            if (thisRequestId === this.requestId) this.loading = false;
        }
    },

    close() {
        this.open = false;
    },
}));

Alpine.data('megaMenu', () => ({
    activeMenu: null,
    activeCategoryId: null,
    activeSubCategoryId: null,
    activeModelId: null,
    closeTimer: null,

    open(name) {
        this.cancelClose();
        this.activeMenu = name;
    },

    close() {
        this.cancelClose();
        this.closeTimer = setTimeout(() => {
            this.activeMenu = null;
        }, 150);
    },

    cancelClose() {
        if (this.closeTimer) {
            clearTimeout(this.closeTimer);
            this.closeTimer = null;
        }
    },

    setCategory(id) {
        this.activeCategoryId = id;
        this.activeSubCategoryId = null;
        this.activeModelId = null;
    },

    setSubCategory(id) {
        this.activeSubCategoryId = id;
        this.activeModelId = null;
    },

    setModel(id) {
        this.activeModelId = id;
    },
}));

Alpine.data('productCardGallery', (count) => ({
    active: 0,
    count,

    next() {
        this.active = (this.active + 1) % this.count;
    },

    prev() {
        this.active = (this.active - 1 + this.count) % this.count;
    },
}));

// "Agregar al carrito" en las tarjetas de producto (catálogo, colecciones,
// carruseles, resultados de búsqueda). Delegado a nivel documento porque
// hay muchas tarjetas por página (a diferencia del único botón de la ficha
// de producto en product-detail.js) — así funciona también en tarjetas
// cargadas dinámicamente (ej. resultados de búsqueda en vivo).
document.addEventListener('click', async (e) => {
    const addBtn = e.target.closest('.product-card__add-btn');
    if (!addBtn) return;

    const productId = addBtn.dataset.productId;
    if (!productId || addBtn.disabled) return;

    const originalLabel = addBtn.textContent;
    addBtn.disabled = true;

    try {
        const response = await fetch('/carrito/agregar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
            body: JSON.stringify({ product_id: productId, quantity: 1, visitor_uuid: window.__adVisitorUuid }),
        });

        if (!response.ok) throw new Error('add-to-cart failed');

        await Alpine.store('shop').refreshCartCount();
        window.AdTracking?.track('add_to_cart', { productId });

        // GA4 e-commerce (vía GTM) -- independiente de AdTracking de arriba,
        // que es nuestro propio sistema de atribución y no se toca. Cantidad
        // siempre 1 aquí: las tarjetas de catálogo/carrusel no tienen selector
        // de cantidad (solo la ficha de producto lo tiene).
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({ ecommerce: null }); // limpia el ecommerce previo antes de cada push (recomendación de Google)
        dataLayer.push({
            event: 'add_to_cart',
            ecommerce: {
                currency: 'MXN',
                value: parseFloat(addBtn.dataset.price),
                items: [{
                    item_id: addBtn.dataset.sku,
                    item_name: addBtn.dataset.name,
                    price: parseFloat(addBtn.dataset.price),
                    quantity: 1,
                }],
            },
        });

        addBtn.textContent = 'Agregado ✓';
        setTimeout(() => {
            addBtn.textContent = originalLabel;
            addBtn.disabled = false;
        }, 1500);
    } catch (err) {
        addBtn.textContent = 'Error, intenta de nuevo';
        setTimeout(() => {
            addBtn.textContent = originalLabel;
            addBtn.disabled = false;
        }, 1500);
    }
});

Alpine.data('carouselTrack', () => ({
    scrollBy(amount) {
        this.$refs.track.scrollBy({ left: amount, behavior: 'smooth' });
    },
    prev() {
        this.scrollBy(-400);
    },
    next() {
        this.scrollBy(400);
    },
}));
