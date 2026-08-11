import Alpine from './alpine-init.js';

// Botón "Agregar al carrito" de la ficha de producto (price-box.blade.php).
// Plain JS (no Alpine.data) porque solo hay un botón por página y no
// necesita estado reactivo — feedback simple con cambio de texto, ya que
// el proyecto no tiene un helper showToast()/Toast() reutilizable todavía.
document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.querySelector('.product-price-box__add-btn');
    if (!addBtn) return;

    const originalLabel = addBtn.textContent;

    addBtn.addEventListener('click', async () => {
        const productId = addBtn.dataset.productId;
        if (!productId || addBtn.disabled) return;

        const quantityInput = document.querySelector('[name="quantity"], .product-price-box__quantity-input');
        const quantity = quantityInput ? Math.max(1, parseInt(quantityInput.value, 10) || 1) : 1;

        addBtn.disabled = true;

        try {
            const response = await fetch('/carrito/agregar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ product_id: productId, quantity }),
            });

            if (!response.ok) throw new Error('add-to-cart failed');

            await Alpine.store('shop').refreshCartCount();

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
});

Alpine.data('productGallery', (images, labels = []) => ({
    images,
    labels,
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
