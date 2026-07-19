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

Alpine.data('megaMenu', () => ({
    activeMenu: null,
    activeCategoryId: null,
    activeBrandId: null,
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
        this.activeBrandId = null;
    },

    setBrand(id) {
        this.activeBrandId = id;
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
