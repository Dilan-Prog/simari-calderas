{{-- Shared "Seleccionar Imagen" modal — reused by any admin form with a
     single URL de Imagen field. Mounted once in the admin master layout.
     Opened via openImagePicker('targetInputId'). --}}
<div id="imagePickerModal" class="del-confirm-overlay" data-library-url="{{ route('admin.media.library') }}" data-upload-url="{{ route('admin.media.upload') }}">
    <div class="del-confirm-box img-source-box">
        <h2 class="del-confirm-title">Seleccionar Imagen</h2>

        <div class="img-source-search-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input type="text" id="imagePickerSearch" class="img-source-search-input"
                placeholder="Buscar por producto o SKU...">
        </div>

        <div class="img-source-upload-row" id="imagePickerUploadRow">
            <button type="button" class="img-source-action-btn" id="imagePickerFileBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" x2="12" y1="3" y2="15" />
                </svg>
                <span id="imagePickerFileBtnLabel">Subir desde tu equipo</span>
            </button>
            <button type="button" class="img-source-action-btn" id="imagePickerUrlBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </svg>
                Usar una URL
            </button>
        </div>
        <input type="file" id="imagePickerFileInput" accept="image/png,image/jpeg,image/jpg" style="display:none">

        <div class="img-source-url-panel" id="imagePickerUrlPanel" style="display:none">
            <input type="url" id="imagePickerUrlInput" class="pform-input" placeholder="https://ejemplo.com/imagen.jpg" autocomplete="off">
            <div class="img-url-preview-wrap" id="imagePickerUrlPreviewWrap" style="display:none">
                <img id="imagePickerUrlPreviewImg" alt="Vista previa">
            </div>
            <p class="img-url-status" id="imagePickerUrlStatus"></p>
            <div class="img-source-url-actions">
                <button type="button" class="img-source-back" id="imagePickerBackBtn">&larr; Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="imagePickerUrlAddBtn" disabled>
                    Usar esta Imagen
                </button>
            </div>
        </div>

        <p class="img-library-label">Imágenes ya subidas en tu catálogo</p>
        <div class="img-library-grid" id="imagePickerLibraryGrid"></div>
        <p class="img-library-empty" id="imagePickerLibraryEmpty" style="display:none">
            Aún no has subido imágenes a ningún producto.
        </p>
        <p class="img-library-loading" id="imagePickerLibraryLoading" style="display:none">Cargando...</p>
        <button type="button" class="img-library-load-more" id="imagePickerLibraryLoadMore" style="display:none">
            Cargar más
        </button>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="imagePickerCancel">Cerrar</button>
        </div>
    </div>
</div>
