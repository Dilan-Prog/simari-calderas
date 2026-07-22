import Alpine from 'alpinejs';

Alpine.store('shop', {
    cartOpen: false,
    cartCount: 0,
    quoteOpen: false,
    quoteSubmitted: false,

    toggleCart() {
        this.cartOpen = !this.cartOpen;
    },

    openQuote() {
        this.quoteOpen = true;
        this.quoteSubmitted = false;
        this.cartOpen = false;
    },

    closeQuote() {
        this.quoteOpen = false;
        this.quoteSubmitted = false;
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
    },

    setSubCategory(id) {
        this.activeSubCategoryId = id;
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
