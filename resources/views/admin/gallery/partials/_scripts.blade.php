@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    /* ── Lightbox ── */
    window.galOpenLightbox = function (url) {
        document.getElementById('galLightboxImg').src = url;
        document.getElementById('galLightbox').classList.add('active');
    };
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.getElementById('galLightbox').classList.remove('active');
    });

    /* ── Copiar URL (clipboard con fallback para http local) ── */
    window.galCopyUrl = async function (url, btn) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                ta.remove();
            }
            showCenterToast('URL copiada al portapapeles.');
        } catch (err) {
            showCenterToast('No se pudo copiar la URL.', 'error');
        }
    };

    /* ── Subida (solo pestaña Galería) ── */
    const uploadOverlay = document.getElementById('galUploadOverlay');
    if (!uploadOverlay) return; // pestañas de solo lectura

    const dropzone = document.getElementById('galDropzone');
    const fileInput = document.getElementById('galFileInput');
    const previews = document.getElementById('galPreviews');
    const urlInput = document.getElementById('galUrlInput');
    const urlPreview = document.getElementById('galUrlPreview');
    const uploadBtn = document.getElementById('galUploadBtn');

    let selectedFiles = [];
    let urlValid = false;
    let urlDebounce = null;

    function syncUploadBtn() {
        uploadBtn.disabled = selectedFiles.length === 0 && !urlValid;
    }

    function renderPreviews() {
        previews.innerHTML = '';
        selectedFiles.forEach(function (file, i) {
            const item = document.createElement('div');
            item.className = 'gal-preview';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            const del = document.createElement('button');
            del.type = 'button';
            del.innerHTML = '&times;';
            del.title = 'Quitar';
            del.addEventListener('click', function () {
                selectedFiles.splice(i, 1);
                renderPreviews();
                syncUploadBtn();
            });
            item.appendChild(img);
            item.appendChild(del);
            previews.appendChild(item);
        });
    }

    function addFiles(fileList) {
        Array.from(fileList).forEach(function (file) {
            if (file.type.startsWith('image/')) selectedFiles.push(file);
        });
        renderPreviews();
        syncUploadBtn();
    }

    dropzone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', function () {
        addFiles(this.files);
        this.value = '';
    });
    dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.classList.add('is-dragover');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragover'));
    dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.classList.remove('is-dragover');
        addFiles(e.dataTransfer.files);
    });

    urlInput.addEventListener('input', function () {
        clearTimeout(urlDebounce);
        urlValid = false;
        urlPreview.style.display = 'none';
        syncUploadBtn();
        const value = this.value.trim();
        if (!value) return;
        urlDebounce = setTimeout(function () {
            const probe = new Image();
            probe.onload = function () {
                urlValid = true;
                urlPreview.src = value;
                urlPreview.style.display = 'block';
                syncUploadBtn();
            };
            probe.onerror = function () {
                urlValid = false;
                syncUploadBtn();
            };
            probe.src = value;
        }, 400);
    });

    window.galCloseUpload = function () {
        uploadOverlay.classList.remove('active');
    };

    document.getElementById('btnGalleryUpload').addEventListener('click', function () {
        selectedFiles = [];
        urlValid = false;
        urlInput.value = '';
        urlPreview.style.display = 'none';
        renderPreviews();
        syncUploadBtn();
        uploadOverlay.classList.add('active');
    });

    uploadOverlay.addEventListener('click', function (e) {
        if (e.target === uploadOverlay) galCloseUpload();
    });

    uploadBtn.addEventListener('click', async function () {
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Subiendo...';

        const formData = new FormData();
        selectedFiles.forEach(f => formData.append('files[]', f));
        if (urlValid && urlInput.value.trim()) formData.append('url', urlInput.value.trim());

        try {
            const response = await fetch('{{ route('admin.gallery.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();

            if (response.ok && data.success) {
                queueCenterToast(data.message ?? 'Imágenes subidas.');
                window.location.reload();
            } else {
                showCenterToast(data.message ?? (data.errors ? Object.values(data.errors).flat()[0] : 'Error al subir.'), 'error');
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Subir';
            }
        } catch (err) {
            showCenterToast('Error de conexión al subir.', 'error');
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Subir';
        }
    });

    /* ── Eliminar ── */
    const deleteOverlay = document.getElementById('galDeleteOverlay');
    let deleteId = null;

    window.galAskDelete = function (id, name) {
        deleteId = id;
        document.getElementById('galDeleteName').textContent = name;
        deleteOverlay.classList.add('active');
    };
    window.galCloseDelete = function () {
        deleteOverlay.classList.remove('active');
        deleteId = null;
    };
    deleteOverlay.addEventListener('click', function (e) {
        if (e.target === deleteOverlay) galCloseDelete();
    });

    document.getElementById('galDeleteConfirm').addEventListener('click', async function () {
        if (!deleteId) return;
        try {
            const response = await fetch('{{ url('/admin/galeria/eliminar') }}/' + deleteId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await response.json();
            if (response.ok && data.success) {
                queueCenterToast('Imagen eliminada.');
                window.location.reload();
            } else {
                showCenterToast(data.message ?? 'No se pudo eliminar.', 'error');
            }
        } catch (err) {
            showCenterToast('Error de conexión al eliminar.', 'error');
        }
    });
})();
</script>
@endpush
