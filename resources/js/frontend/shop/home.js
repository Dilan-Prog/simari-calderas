import Alpine from './alpine-init.js';

Alpine.data('heroCarousel', (slideCount) => ({
    active: 0,
    slideCount,
    timer: null,

    init() {
        this.timer = setInterval(() => this.next(), 6000);
    },

    goTo(i) {
        this.active = i;
    },

    next() {
        this.active = (this.active + 1) % this.slideCount;
    },

    prev() {
        this.active = (this.active - 1 + this.slideCount) % this.slideCount;
    },
}));

Alpine.start();
