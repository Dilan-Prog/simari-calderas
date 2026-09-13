@push('scripts')
<script>
(function () {
    const list = document.getElementById('serviceReviewsList');
    if (!list) return;

    const baseUrl = list.dataset.baseUrl;
    const modal = document.getElementById('serviceReviewModal');
    const form = document.getElementById('serviceReviewForm');
    const errorsBox = document.getElementById('service-review-modal-errors');
    let currentReviewId = null;
    let isEditMode = false;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function resetForm() {
        form.reset();
        document.querySelectorAll('#serviceReviewForm .field-error-msg').forEach(el => el.remove());
        document.querySelectorAll('#serviceReviewForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        errorsBox.style.display = 'none';
        errorsBox.innerHTML = '';
        currentReviewId = null;
        isEditMode = false;
        document.getElementById('serviceReviewModalTitle').textContent = 'Nueva Reseña';
        document.getElementById('serviceReviewSubmitBtn').textContent = 'Crear Reseña';
        setStars(5);
        document.getElementById('srCommentCount').textContent = '0';
        document.getElementById('srIsVisible').checked = true;
        document.querySelectorAll('.sr-category-checkbox').forEach(cb => cb.checked = false);
    }

    function setStars(value) {
        document.getElementById('srRating').value = value;
        document.querySelectorAll('#srRatingStars .service-review-star').forEach(btn => {
            btn.classList.toggle('is-active', parseInt(btn.dataset.value, 10) <= value);
        });
    }

    document.querySelectorAll('#srRatingStars .service-review-star').forEach(btn => {
        btn.addEventListener('click', () => setStars(parseInt(btn.dataset.value, 10)));
    });

    document.getElementById('srComment').addEventListener('input', function () {
        document.getElementById('srCommentCount').textContent = this.value.length;
    });

    const btnNew = document.getElementById('btnNewServiceReview');
    if (btnNew) {
        btnNew.addEventListener('click', () => {
            resetForm();
            modal.style.display = 'flex';
        });
    }

    document.getElementById('closeServiceReviewModal').addEventListener('click', closeModal);
    document.getElementById('cancelServiceReviewModal').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    function showFieldError(name, message) {
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.classList.add('is-invalid');
        const span = document.createElement('span');
        span.className = 'field-error-msg';
        span.innerText = message;
        (el.closest('.users-manager-email-camp') || el.parentElement).appendChild(span);
    }

    function buildPayload() {
        return {
            customer_name: document.getElementById('srCustomerName').value,
            customer_role: document.getElementById('srCustomerRole').value || null,
            customer_company: document.getElementById('srCustomerCompany').value || null,
            customer_city: document.getElementById('srCustomerCity').value || null,
            customer_state: document.getElementById('srCustomerState').value || null,
            review_date: document.getElementById('srReviewDate').value || null,
            rating: parseInt(document.getElementById('srRating').value, 10),
            comment: document.getElementById('srComment').value,
            categories: Array.from(document.querySelectorAll('.sr-category-checkbox:checked')).map(cb => cb.value),
            is_verified: document.getElementById('srIsVerified').checked,
            is_visible: document.getElementById('srIsVisible').checked,
            business_response: document.getElementById('srBusinessResponse').value || null,
            business_response_date: document.getElementById('srBusinessResponseDate').value || null,
        };
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        document.querySelectorAll('#serviceReviewForm .field-error-msg').forEach(el => el.remove());
        document.querySelectorAll('#serviceReviewForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        errorsBox.style.display = 'none';

        const url = isEditMode ? `${baseUrl}/${currentReviewId}` : baseUrl;
        const payload = buildPayload();
        if (isEditMode) payload._method = 'PUT';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (res.ok) {
                closeModal();
                queueCenterToast(isEditMode ? 'Reseña actualizada.' : 'Reseña creada.');
                setTimeout(() => window.location.reload(), 200);
            } else if (res.status === 422) {
                const errors = data.errors || {};
                errorsBox.innerHTML = Object.values(errors).flat().map(m => `<p>${m}</p>`).join('');
                errorsBox.style.display = 'block';
                Object.keys(errors).forEach(field => showFieldError(field, errors[field][0]));
                showCenterToast('Revisa los campos marcados.', 'error');
            } else {
                showCenterToast('No se pudo guardar la reseña.', 'error');
            }
        } catch (err) {
            showCenterToast('Error de conexión al guardar la reseña.', 'error');
        }
    });

    document.querySelectorAll('.btn-edit-service-review').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() } })
                .then(res => res.json())
                .then(review => {
                    resetForm();
                    currentReviewId = id;
                    isEditMode = true;
                    document.getElementById('serviceReviewModalTitle').textContent = 'Editar Reseña';
                    document.getElementById('serviceReviewSubmitBtn').textContent = 'Guardar Cambios';

                    document.getElementById('srCustomerName').value = review.customer_name ?? '';
                    document.getElementById('srCustomerRole').value = review.customer_role ?? '';
                    document.getElementById('srCustomerCompany').value = review.customer_company ?? '';
                    document.getElementById('srCustomerCity').value = review.customer_city ?? '';
                    document.getElementById('srCustomerState').value = review.customer_state ?? '';
                    document.getElementById('srReviewDate').value = review.review_date ?? '';
                    setStars(review.rating ?? 5);
                    document.getElementById('srComment').value = review.comment ?? '';
                    document.getElementById('srCommentCount').textContent = (review.comment ?? '').length;
                    document.getElementById('srIsVerified').checked = !!review.is_verified;
                    document.getElementById('srIsVisible').checked = !!review.is_visible;
                    document.getElementById('srBusinessResponse').value = review.business_response ?? '';
                    document.getElementById('srBusinessResponseDate').value = review.business_response_date ?? '';
                    const cats = review.categories ?? [];
                    document.querySelectorAll('.sr-category-checkbox').forEach(cb => cb.checked = cats.includes(cb.value));

                    modal.style.display = 'flex';
                })
                .catch(() => showCenterToast('No se pudo cargar la reseña.', 'error'));
        });
    });

    document.querySelectorAll('.btn-delete-service-review').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm(`¿Eliminar la reseña de "${btn.dataset.name}"?`)) return;
            try {
                const res = await fetch(`${baseUrl}/${btn.dataset.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ _method: 'DELETE' }),
                });
                if (res.ok) {
                    queueCenterToast('Reseña eliminada.');
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    showCenterToast('No se pudo eliminar la reseña.', 'error');
                }
            } catch (err) {
                showCenterToast('Error de conexión al eliminar la reseña.', 'error');
            }
        });
    });

    /* ── Arrastrar para reordenar reseñas ── */
    (function () {
        let dragSrcId = null;

        function currentOrder() {
            return Array.from(list.querySelectorAll('.service-review-row')).map(el => el.dataset.id);
        }

        async function persistOrder() {
            try {
                const res = await fetch(`${baseUrl}/reordenar`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: currentOrder() }),
                });
                if (res.ok) {
                    showCenterToast('Orden de reseñas actualizado.');
                } else {
                    showCenterToast('No se pudo guardar el nuevo orden.', 'error');
                }
            } catch (err) {
                showCenterToast('Error de conexión al guardar el orden.', 'error');
            }
        }

        list.querySelectorAll('.service-review-row').forEach(row => {
            row.addEventListener('dragstart', () => { dragSrcId = row.dataset.id; row.classList.add('is-dragging'); });
            row.addEventListener('dragend', () => row.classList.remove('is-dragging'));
            row.addEventListener('dragover', (e) => { e.preventDefault(); if (dragSrcId !== row.dataset.id) row.classList.add('drag-over'); });
            row.addEventListener('dragleave', () => row.classList.remove('drag-over'));
            row.addEventListener('drop', (e) => {
                e.preventDefault();
                row.classList.remove('drag-over');
                if (dragSrcId === null || dragSrcId === row.dataset.id) return;
                const src = list.querySelector(`.service-review-row[data-id="${dragSrcId}"]`);
                dragSrcId = null;
                if (!src) return;
                list.insertBefore(src, row);
                persistOrder();
            });
        });
    })();
})();
</script>
@endpush
