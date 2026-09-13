@push('scripts')
<script>
(function () {
    const grid = document.getElementById('serviceGalleryGrid');
    if (!grid) return;

    const baseUrl = grid.dataset.baseUrl;
    const dropzone = document.getElementById('serviceGalleryDropzone');
    const addBtn = document.getElementById('btnAddServiceImage');

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function refreshBadges() {
        Array.from(grid.querySelectorAll('.service-gallery-item:not(.service-gallery-item--add)')).forEach((item, i) => {
            const existing = item.querySelector('.service-gallery-item__badge');
            if (i === 0) {
                if (!existing) {
                    const badge = document.createElement('span');
                    badge.className = 'service-gallery-item__badge';
                    badge.textContent = 'PORTADA';
                    item.prepend(badge);
                }
            } else if (existing) {
                existing.remove();
            }
        });
    }

    function buildItem(image) {
        const item = document.createElement('div');
        item.className = 'service-gallery-item';
        item.dataset.id = image.id;
        item.draggable = true;
        item.innerHTML = `
            <div class="service-gallery-item__drag">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
            <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
            <img src="${image.url}" alt="">
            <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo">
        `;
        bindItem(item);
        return item;
    }

    function bindItem(item) {
        const id = item.dataset.id;

        item.querySelector('.service-gallery-item__remove').addEventListener('click', async () => {
            if (!confirm('¿Quitar esta imagen de la galería?')) return;
            try {
                const res = await fetch(`${baseUrl}/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _method: 'DELETE' }),
                });
                if (res.ok) {
                    item.remove();
                    refreshBadges();
                    queueCenterToast('Imagen eliminada.');
                } else {
                    showCenterToast('No se pudo eliminar la imagen.', 'error');
                }
            } catch (e) {
                showCenterToast('Error de conexión al eliminar la imagen.', 'error');
            }
        });

        let altDebounce = null;
        const altInput = item.querySelector('.service-gallery-item__alt');
        altInput.addEventListener('input', () => {
            clearTimeout(altDebounce);
            altDebounce = setTimeout(async () => {
                try {
                    await fetch(`${baseUrl}/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ _method: 'PUT', alt_text: altInput.value }),
                    });
                } catch (e) { /* silencioso, no bloquea la edición */ }
            }, 500);
        });

        item.addEventListener('dragstart', () => item.classList.add('is-dragging'));
        item.addEventListener('dragend', () => item.classList.remove('is-dragging'));
        item.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (item.classList.contains('service-gallery-item--add')) return;
            item.classList.add('drag-over');
        });
        item.addEventListener('dragleave', () => item.classList.remove('drag-over'));
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            item.classList.remove('drag-over');
            const dragging = grid.querySelector('.is-dragging');
            if (!dragging || dragging === item) return;
            grid.insertBefore(dragging, item);
            refreshBadges();
            persistOrder();
        });
    }

    async function persistOrder() {
        const order = Array.from(grid.querySelectorAll('.service-gallery-item:not(.service-gallery-item--add)')).map(el => el.dataset.id);
        if (!order.length) return;
        try {
            const res = await fetch(`${baseUrl}/reordenar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ order }),
            });
            if (res.ok) {
                queueCenterToast('Orden de galería actualizado.');
            } else {
                showCenterToast('No se pudo guardar el nuevo orden de la galería.', 'error');
            }
        } catch (e) {
            showCenterToast('Error de conexión al reordenar la galería.', 'error');
        }
    }

    async function addImage(url) {
        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ image_url: url, alt_text: '' }),
            });
            const data = await res.json();
            if (res.ok) {
                grid.insertBefore(buildItem(data.image), dropzone);
                refreshBadges();
                queueCenterToast('Imagen agregada.');
            } else {
                showCenterToast('No se pudo agregar la imagen.', 'error');
            }
        } catch (e) {
            showCenterToast('Error de conexión al agregar la imagen.', 'error');
        }
    }

    addBtn.addEventListener('click', () => openImagePicker(null, { onSelect: addImage }));
    dropzone.addEventListener('click', () => openImagePicker(null, { onSelect: addImage }));

    Array.from(grid.querySelectorAll('.service-gallery-item:not(.service-gallery-item--add)')).forEach(bindItem);
})();
</script>
@endpush
