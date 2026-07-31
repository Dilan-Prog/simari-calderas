@push('scripts')
<script>
(function () {
    const scanBtn = document.getElementById('btnDupScan');
    if (!scanBtn) return; // solo pestaña Duplicados

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = '{{ url('/admin/galeria/duplicados') }}';

    /* ── Escanear ── */
    scanBtn.addEventListener('click', async function () {
        scanBtn.disabled = true;
        const originalText = scanBtn.textContent;
        scanBtn.textContent = 'Escaneando...';

        try {
            const response = await fetch(baseUrl + '/escanear', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await response.json();

            if (response.ok && data.success) {
                queueCenterToast('Escaneo completo: ' + data.scan.groups_found + ' grupo(s) encontrado(s).');
                window.location.reload();
            } else {
                showCenterToast((data.scan && data.scan.error_message) || 'No se pudo completar el escaneo.', 'error');
                scanBtn.disabled = false;
                scanBtn.textContent = originalText;
            }
        } catch (err) {
            showCenterToast('Error de conexión al escanear.', 'error');
            scanBtn.disabled = false;
            scanBtn.textContent = originalText;
        }
    });

    /* ── Selección de checkboxes → habilitar "Reemplazar con..." ── */
    function refreshReplaceButton(groupId) {
        const count = document.querySelectorAll('.dup-checkbox[data-group-id="' + groupId + '"]:checked').length;
        const btn = document.querySelector('.dup-btn-replace[data-group-id="' + groupId + '"]');
        if (btn) btn.disabled = count === 0;
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('dup-checkbox')) {
            refreshReplaceButton(e.target.dataset.groupId);
        }
    });

    /* ── Descartar grupo ── */
    document.querySelectorAll('.dup-btn-dismiss').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (!window.confirm('¿Descartar este grupo? No volverá a aparecer mientras sus imágenes no cambien.')) return;

            const groupId = btn.dataset.groupId;
            try {
                const response = await fetch(baseUrl + '/' + groupId + '/descartar', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    queueCenterToast('Grupo descartado.');
                    window.location.reload();
                } else {
                    showCenterToast(data.message ?? 'No se pudo descartar el grupo.', 'error');
                }
            } catch (err) {
                showCenterToast('Error de conexión al descartar.', 'error');
            }
        });
    });

    /* ── Modal de reemplazo ── */
    const overlay = document.getElementById('dupReplaceOverlay');
    const summary = document.getElementById('dupReplaceSummary');
    const groupOptionsEl = document.getElementById('dupGroupOptions');
    const librarySearchInput = document.getElementById('dupLibrarySearch');
    const libraryResultsEl = document.getElementById('dupLibraryResults');
    const uploadDropzone = document.getElementById('dupUploadDropzone');
    const uploadInput = document.getElementById('dupUploadInput');
    const uploadPreview = document.getElementById('dupUploadPreview');
    const applyBtn = document.getElementById('dupApplyBtn');

    let currentGroupId = null;
    let currentRemoveUrls = [];
    let selected = null; // { mode: 'group'|'library'|'upload', imageUrl?, file? }
    let libraryDebounce = null;

    function renderOptionCard(container, item, onClick) {
        const card = document.createElement('div');
        card.className = 'dup-option';
        card.innerHTML = '<img src="' + item.url + '" alt="' + item.label + '" loading="lazy">'
            + '<div class="dup-option__label" title="' + item.label + '">' + item.label + '</div>';
        card.addEventListener('click', function () {
            container.querySelectorAll('.dup-option').forEach(el => el.classList.remove('is-selected'));
            card.classList.add('is-selected');
            onClick();
        });
        container.appendChild(card);
    }

    window.dupCloseReplace = function () {
        overlay.classList.remove('active');
        currentGroupId = null;
        selected = null;
    };

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) window.dupCloseReplace();
    });

    document.querySelectorAll('.dup-replace-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.dup-replace-tab').forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');
            document.querySelectorAll('.dup-replace-panel').forEach(function (panel) {
                panel.style.display = panel.dataset.panel === tab.dataset.mode ? '' : 'none';
            });
            selected = null;
            applyBtn.disabled = true;
        });
    });

    document.querySelectorAll('.dup-btn-replace').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentGroupId = btn.dataset.groupId;

            const groupEl = document.querySelector('.dup-group[data-group-id="' + currentGroupId + '"]');
            const allImages = JSON.parse(groupEl.dataset.images);
            currentRemoveUrls = Array.from(
                document.querySelectorAll('.dup-checkbox[data-group-id="' + currentGroupId + '"]:checked')
            ).map(cb => cb.dataset.imageUrl);

            const remaining = allImages.filter(img => currentRemoveUrls.indexOf(img.image_url) === -1);

            summary.textContent = 'Vas a eliminar ' + currentRemoveUrls.length
                + ' imagen(es) y reemplazarlas por la que elijas a continuación.';

            groupOptionsEl.innerHTML = '';
            if (remaining.length === 0) {
                groupOptionsEl.innerHTML = '<p class="dup-option-empty">No queda ninguna imagen del grupo sin marcar — usa "Buscar en biblioteca" o "Subir nueva".</p>';
            } else {
                remaining.forEach(function (img) {
                    renderOptionCard(groupOptionsEl, img, function () {
                        selected = { mode: 'group', imageUrl: img.image_url };
                        applyBtn.disabled = false;
                    });
                });
            }

            libraryResultsEl.innerHTML = '';
            librarySearchInput.value = '';
            uploadInput.value = '';
            uploadPreview.style.display = 'none';

            document.querySelectorAll('.dup-replace-tab').forEach(t => t.classList.remove('is-active'));
            document.querySelector('.dup-replace-tab[data-mode="group"]').classList.add('is-active');
            document.querySelectorAll('.dup-replace-panel').forEach(function (panel) {
                panel.style.display = panel.dataset.panel === 'group' ? '' : 'none';
            });

            selected = null;
            applyBtn.disabled = true;
            overlay.classList.add('active');
        });
    });

    librarySearchInput.addEventListener('input', function () {
        clearTimeout(libraryDebounce);
        const q = this.value.trim();
        libraryDebounce = setTimeout(async function () {
            try {
                const response = await fetch(baseUrl + '/buscar?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                libraryResultsEl.innerHTML = '';

                const results = (data.results || []).filter(r => currentRemoveUrls.indexOf(r.image_url) === -1);

                if (results.length === 0) {
                    libraryResultsEl.innerHTML = '<p class="dup-option-empty">Sin resultados.</p>';
                    return;
                }

                results.forEach(function (item) {
                    renderOptionCard(libraryResultsEl, item, function () {
                        selected = { mode: 'library', imageUrl: item.image_url };
                        applyBtn.disabled = false;
                    });
                });
            } catch (err) {
                libraryResultsEl.innerHTML = '<p class="dup-option-empty">Error al buscar.</p>';
            }
        }, 350);
    });

    uploadDropzone.addEventListener('click', () => uploadInput.click());
    uploadInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        uploadPreview.src = URL.createObjectURL(file);
        uploadPreview.style.display = 'block';
        selected = { mode: 'upload', file: file };
        applyBtn.disabled = false;
    });

    applyBtn.addEventListener('click', async function () {
        if (!selected || !currentGroupId) return;

        applyBtn.disabled = true;
        applyBtn.textContent = 'Aplicando...';

        const formData = new FormData();
        currentRemoveUrls.forEach(u => formData.append('remove_image_urls[]', u));

        if (selected.mode === 'upload') {
            formData.append('new_image', selected.file);
        } else {
            formData.append('replacement_image_url', selected.imageUrl);
        }

        try {
            const response = await fetch(baseUrl + '/' + currentGroupId + '/aplicar', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();

            if (response.ok && data.success) {
                queueCenterToast(data.message ?? 'Imágenes consolidadas.');
                window.location.reload();
            } else {
                showCenterToast(data.message ?? 'No se pudo aplicar el reemplazo.', 'error');
                applyBtn.disabled = false;
                applyBtn.textContent = 'Aplicar';
            }
        } catch (err) {
            showCenterToast('Error de conexión al aplicar el reemplazo.', 'error');
            applyBtn.disabled = false;
            applyBtn.textContent = 'Aplicar';
        }
    });
})();
</script>
@endpush
