/* Shared "Seleccionar Imagen" picker — reused by any admin form with a
 * single URL de Imagen field (Home Sections, Collections, Categorías,
 * slides, og_image, etc). Call window.openImagePicker('targetInputId')
 * from a trigger button to open it targeting that field.
 */
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('imagePickerModal');
    if (!modal) return;

    const libraryUrl = modal.dataset.libraryUrl;
    const uploadUrl = modal.dataset.uploadUrl;

    const uploadRow = document.getElementById('imagePickerUploadRow');
    const fileBtn = document.getElementById('imagePickerFileBtn');
    const fileBtnLabel = document.getElementById('imagePickerFileBtnLabel');
    const fileInput = document.getElementById('imagePickerFileInput');
    const urlBtn = document.getElementById('imagePickerUrlBtn');
    const urlPanel = document.getElementById('imagePickerUrlPanel');
    const urlInput = document.getElementById('imagePickerUrlInput');
    const urlPreviewWrap = document.getElementById('imagePickerUrlPreviewWrap');
    const urlPreviewImg = document.getElementById('imagePickerUrlPreviewImg');
    const urlStatus = document.getElementById('imagePickerUrlStatus');
    const urlAddBtn = document.getElementById('imagePickerUrlAddBtn');
    const backBtn = document.getElementById('imagePickerBackBtn');
    const cancelBtn = document.getElementById('imagePickerCancel');
    const librarySearch = document.getElementById('imagePickerSearch');
    const libraryGrid = document.getElementById('imagePickerLibraryGrid');
    const libraryEmpty = document.getElementById('imagePickerLibraryEmpty');
    const libraryLoading = document.getElementById('imagePickerLibraryLoading');
    const libraryLoadMore = document.getElementById('imagePickerLibraryLoadMore');

    let targetInputId = null;
    let onSelect = null;
    let urlIsValid = false;
    let urlCheckTimer = null;
    let librarySearchTimer = null;
    let libraryNextPage = 1;

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function resetPanel() {
        uploadRow.style.display = '';
        urlPanel.style.display = 'none';
        urlInput.value = '';
        urlPreviewWrap.style.display = 'none';
        urlStatus.textContent = '';
        urlAddBtn.disabled = true;
        urlAddBtn.textContent = 'Usar esta Imagen';
        urlIsValid = false;
    }

    function applyValue(url) {
        const input = document.getElementById(targetInputId);
        if (input) {
            input.value = url;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (typeof onSelect === 'function') onSelect(url);
        closeModal();
    }

    function openModal(inputId, options) {
        targetInputId = inputId;
        onSelect = (options || {}).onSelect || null;
        resetPanel();
        modal.classList.add('active');
        librarySearch.value = '';
        loadLibrary(1, false);
    }

    function closeModal() {
        modal.classList.remove('active');
        resetPanel();
    }

    fileBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async function () {
        const file = this.files[0];
        if (!file) return;

        fileBtn.disabled = true;
        fileBtnLabel.textContent = 'Subiendo...';

        try {
            const formData = new FormData();
            formData.append('file', file);
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();
            if (response.ok && data.success) {
                applyValue(data.url);
            } else {
                alert(data.message || 'No se pudo subir la imagen.');
            }
        } catch (e) {
            alert('Error de conexión al subir la imagen.');
        } finally {
            fileInput.value = '';
            fileBtn.disabled = false;
            fileBtnLabel.textContent = 'Subir desde tu equipo';
        }
    });

    urlBtn.addEventListener('click', function () {
        uploadRow.style.display = 'none';
        urlPanel.style.display = 'block';
        urlInput.focus();
    });

    backBtn.addEventListener('click', resetPanel);
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    urlInput.addEventListener('input', function () {
        clearTimeout(urlCheckTimer);
        urlIsValid = false;
        urlAddBtn.disabled = true;
        const value = this.value.trim();
        if (!value) {
            urlPreviewWrap.style.display = 'none';
            urlStatus.textContent = '';
            return;
        }
        urlStatus.textContent = 'Comprobando imagen...';
        urlCheckTimer = setTimeout(function () {
            const testImg = new Image();
            testImg.onload = function () {
                urlPreviewImg.src = value;
                urlPreviewWrap.style.display = 'block';
                urlStatus.textContent = 'Imagen encontrada.';
                urlIsValid = true;
                urlAddBtn.disabled = false;
            };
            testImg.onerror = function () {
                urlPreviewWrap.style.display = 'none';
                urlStatus.textContent = 'No se pudo cargar esa URL como imagen. Verifica que sea un enlace directo a una imagen.';
            };
            testImg.src = value;
        }, 400);
    });

    urlAddBtn.addEventListener('click', async function () {
        if (!urlIsValid) return;
        const url = urlInput.value.trim();
        urlAddBtn.disabled = true;
        urlAddBtn.textContent = 'Guardando...';

        try {
            const formData = new FormData();
            formData.append('url', url);
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: formData,
            });
            const data = await response.json();
            if (response.ok && data.success) {
                applyValue(data.url);
            } else {
                urlStatus.textContent = data.message || 'No se pudo descargar esa imagen.';
                urlAddBtn.disabled = false;
                urlAddBtn.textContent = 'Usar esta Imagen';
            }
        } catch (e) {
            urlStatus.textContent = 'Error de conexión.';
            urlAddBtn.disabled = false;
            urlAddBtn.textContent = 'Usar esta Imagen';
        }
    });

    function renderLibraryItems(items, append) {
        if (!append) libraryGrid.innerHTML = '';
        items.forEach(function (libItem) {
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'img-library-item';
            cell.title = libItem.product_name + (libItem.product_sku ? ' · ' + libItem.product_sku : '');

            const img = document.createElement('img');
            img.src = libItem.url;
            img.loading = 'lazy';
            cell.appendChild(img);

            const badge = document.createElement('span');
            badge.className = 'img-library-item-badge';
            badge.textContent = 'Usado en ' + libItem.used_count + (libItem.used_count === 1 ? ' lugar' : ' lugares');
            cell.appendChild(badge);

            const label = document.createElement('span');
            label.className = 'img-library-item-label';
            label.textContent = libItem.product_name || 'Sin nombre';
            cell.appendChild(label);

            cell.addEventListener('click', function () {
                applyValue(libItem.url);
            });

            libraryGrid.appendChild(cell);
        });
    }

    async function loadLibrary(page, append) {
        libraryLoading.style.display = 'block';
        libraryLoadMore.style.display = 'none';
        try {
            const params = new URLSearchParams({ page: page });
            if (librarySearch.value.trim()) params.set('search', librarySearch.value.trim());

            const response = await fetch(libraryUrl + '?' + params.toString(), {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            renderLibraryItems(data.data, append);
            libraryEmpty.style.display = (!append && data.data.length === 0) ? 'block' : 'none';
            libraryLoadMore.style.display = data.has_more ? 'block' : 'none';
            if (data.has_more) libraryNextPage = data.next_page;
        } catch (e) {
            // Library search failing shouldn't block upload/URL entry.
        } finally {
            libraryLoading.style.display = 'none';
        }
    }

    librarySearch.addEventListener('input', function () {
        clearTimeout(librarySearchTimer);
        librarySearchTimer = setTimeout(() => loadLibrary(1, false), 300);
    });

    libraryLoadMore.addEventListener('click', () => loadLibrary(libraryNextPage, true));

    window.openImagePicker = openModal;
});
