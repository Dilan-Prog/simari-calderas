import Alpine from './alpine-init.js';

Alpine.data('priceRangeSlider', (boundMin, boundMax, initialMin, initialMax) => ({
    bounds: [boundMin, boundMax],
    min: initialMin,
    max: initialMax,

    syncMin() {
        if (this.min > this.max) {
            this.min = this.max;
        }
    },

    syncMax() {
        if (this.max < this.min) {
            this.max = this.min;
        }
    },
}));

Alpine.start();
