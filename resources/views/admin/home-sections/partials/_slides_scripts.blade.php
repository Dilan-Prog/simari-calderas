@push('scripts')
    <script>
        const hsSlidesGrid = document.getElementById('hsSlidesGrid');
        const homeSectionId = hsSlidesGrid.dataset.homeSectionId;
        const slidesBaseUrl = `{{ url('/admin/inicio-secciones') }}/${homeSectionId}/slides`;

        const slideModal = document.getElementById('slideModal');
        const slideForm = document.getElementById('slideForm');
        const slideErrorsContainer = document.getElementById('slide-modal-errors');
        let currentSlideId = null;
        let isSlideEditMode = false;

        const closeSlideModalWithAnim = () => {
            const content = slideModal.querySelector('.user-manager-modal-content');
            if (content) {
                content.style.transition = 'transform 0.2s ease-in';
                content.style.transform = 'translateX(100%)';
            }
            slideModal.style.transition = 'opacity 0.2s ease-in';
            slideModal.style.opacity = '0';
            setTimeout(() => {
                slideModal.style.display = 'none';
                slideModal.style.opacity = '';
                slideModal.style.transition = '';
                if (content) {
                    content.style.transform = '';
                    content.style.transition = '';
                }
            }, 200);
        };

        const resetSlideForm = () => {
            slideForm.reset();
            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            slideErrorsContainer.style.display = 'none';
            slideErrorsContainer.innerHTML = '';
            currentSlideId = null;
            isSlideEditMode = false;
            document.getElementById('slideModalTitle').textContent = 'Nuevo Slide';
            document.getElementById('slideSubmitBtn').textContent = 'Crear Slide';
            document.getElementById('slideIsActive').value = '1';
        };

        document.getElementById('btnNewSlide').addEventListener('click', () => {
            resetSlideForm();
            slideModal.style.display = 'flex';
        });

        document.getElementById('closeSlideModal').addEventListener('click', () => closeSlideModalWithAnim());
        document.getElementById('cancelSlideModal').addEventListener('click', () => closeSlideModalWithAnim());
        slideModal.addEventListener('click', (e) => {
            if (e.target === slideModal) closeSlideModalWithAnim();
        });

        slideForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            slideErrorsContainer.style.display = 'none';

            const formData = new FormData(slideForm);

            const url = isSlideEditMode ?
                `${slidesBaseUrl}/${currentSlideId}` :
                slidesBaseUrl;

            if (isSlideEditMode) formData.append('_method', 'PUT');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok) {
                    closeSlideModalWithAnim();
                    queueCenterToast(isSlideEditMode ? 'Slide actualizado correctamente.' : 'Slide creado correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else if (response.status === 422) {
                    const errorList = Object.values(data.errors).flat();
                    slideErrorsContainer.innerHTML = errorList.map(m => `<p>${m}</p>`).join('');
                    slideErrorsContainer.style.display = 'block';
                    showCenterToast('Revisa los campos marcados.', 'error');
                }
            } catch (err) {
                console.error('Error:', err);
                showCenterToast('Error de conexión al guardar el slide.', 'error');
            }
        });

        document.querySelectorAll('.btn-edit-slide').forEach(btn => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.hs-slide-card');
                if (!card) return;

                resetSlideForm();
                currentSlideId = card.dataset.id;
                isSlideEditMode = true;

                document.getElementById('slideModalTitle').textContent = 'Editar Slide';
                document.getElementById('slideSubmitBtn').textContent = 'Guardar Cambios';
                document.getElementById('slideImageUrl').value = card.dataset.imageUrl ?? '';
                document.getElementById('slideBadgeText').value = card.dataset.badgeText ?? '';
                document.getElementById('slideTitleHighlight').value = card.dataset.titleHighlight ?? '';
                document.getElementById('slideTitle').value = card.dataset.title ?? '';
                document.getElementById('slideDescription').value = card.dataset.description ?? '';
                document.getElementById('slideLinkUrl').value = card.dataset.linkUrl ?? '';
                document.getElementById('slideIsActive').value = card.dataset.isActive === '1' ? '1' : '0';

                slideModal.style.display = 'flex';
            });
        });

        // Delete modal
        const deleteSlideModal = document.getElementById('deleteSlideModal');
        let deleteSlideId = null;

        document.querySelectorAll('.btn-delete-slide').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteSlideId = btn.dataset.id;
                document.getElementById('delSlideTitle').textContent = btn.dataset.title;
                document.getElementById('delSlideAvatar').textContent = btn.dataset.title.charAt(0).toUpperCase();
                deleteSlideModal.classList.add('active');
            });
        });

        document.getElementById('delSlideCancel').addEventListener('click', () => deleteSlideModal.classList.remove('active'));
        deleteSlideModal.addEventListener('click', (e) => {
            if (e.target === deleteSlideModal) deleteSlideModal.classList.remove('active');
        });

        document.getElementById('delSlideConfirm').addEventListener('click', async () => {
            try {
                const response = await fetch(`${slidesBaseUrl}/${deleteSlideId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ _method: 'DELETE' }),
                });

                const data = await response.json();

                if (response.ok) {
                    deleteSlideModal.classList.remove('active');
                    queueCenterToast('Slide eliminado correctamente.');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    showCenterToast(data.message ?? 'No se pudo eliminar el slide.', 'error');
                    deleteSlideModal.classList.remove('active');
                }
            } catch (err) {
                console.error('Error deleting slide:', err);
                showCenterToast('Error de conexión al eliminar el slide.', 'error');
            }
        });

        /* ── Drag to reorder slides, saved instantly via AJAX ── */
        (function() {
            const reorderUrl = `${slidesBaseUrl}/reordenar`;
            let dragSrcId = null;

            function currentOrder() {
                return Array.from(hsSlidesGrid.querySelectorAll('.hs-slide-card')).map(el => el.dataset.id);
            }

            async function persistOrder() {
                try {
                    const response = await fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ order: currentOrder() }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        showCenterToast(data.message ?? 'No se pudo guardar el nuevo orden.', 'error');
                    } else {
                        showCenterToast('Orden de slides actualizado.');
                    }
                } catch (err) {
                    console.error('Error saving order:', err);
                    showCenterToast('Error de conexión al guardar el orden.', 'error');
                }
            }

            hsSlidesGrid.querySelectorAll('.hs-slide-card').forEach(function(card) {
                card.addEventListener('dragstart', function() {
                    dragSrcId = card.dataset.id;
                    card.classList.add('is-dragging');
                });
                card.addEventListener('dragend', function() {
                    card.classList.remove('is-dragging');
                });
                card.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (dragSrcId === null || dragSrcId === card.dataset.id) return;
                    card.classList.add('drag-over');
                });
                card.addEventListener('dragleave', function() {
                    card.classList.remove('drag-over');
                });
                card.addEventListener('drop', function(e) {
                    e.preventDefault();
                    card.classList.remove('drag-over');
                    if (dragSrcId === null || dragSrcId === card.dataset.id) return;

                    const srcEl = hsSlidesGrid.querySelector('.hs-slide-card[data-id="' + dragSrcId + '"]');
                    dragSrcId = null;
                    if (!srcEl) return;

                    hsSlidesGrid.insertBefore(srcEl, card);
                    persistOrder();
                });
            });
        })();
    </script>
@endpush
