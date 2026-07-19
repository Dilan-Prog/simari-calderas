import Alpine from './alpine-init.js';

Alpine.data('productGallery', (images) => ({
    images,
    active: 0,
    isZooming: false,
    lensStyle: '',
    lightboxOpen: false,

    select(i) {
        this.active = i;
    },

    next() {
        this.active = (this.active + 1) % this.images.length;
    },

    prev() {
        this.active = (this.active - 1 + this.images.length) % this.images.length;
    },

    onZoomMove(e) {
        const rect = e.currentTarget.getBoundingClientRect();
        const relX = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
        const relY = Math.min(1, Math.max(0, (e.clientY - rect.top) / rect.height));
        this.lensStyle = `left:${relX * 100}%;top:${relY * 100}%;`;
    },

    openLightbox() {
        this.lightboxOpen = true;
    },

    closeLightbox() {
        this.lightboxOpen = false;
    },
}));

Alpine.start();
