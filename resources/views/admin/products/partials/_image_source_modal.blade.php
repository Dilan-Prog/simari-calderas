{{-- "Agregar Imagen" modal: subir archivo, pegar una URL, o reutilizar una
     imagen ya subida a cualquier producto del catálogo.
     Shared by create.blade.php and edit.blade.php. --}}
<div id="imageSourceModal" class="del-confirm-overlay">
    <div class="del-confirm-box img-source-box">
        <h2 class="del-confirm-title">Seleccionar Imagen</h2>

        <div class="img-source-search-row">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input type="text" id="imgLibrarySearch" class="img-source-search-input"
                placeholder="Buscar por producto o SKU...">
        </div>

        <div class="img-source-upload-row" id="imgSourceUploadRow">
            <button type="button" class="img-source-action-btn" id="imgSourceFileBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="17 8 12 3 7 8" />
                    <line x1="12" x2="12" y1="3" y2="15" />
                </svg>
                Subir desde tu equipo
            </button>
            <button type="button" class="img-source-action-btn" id="imgSourceUrlBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                </svg>
                Usar una URL
            </button>
        </div>

        <div class="img-source-url-panel" id="imgSourceUrlPanel" style="display:none">
            <input type="url" id="imgUrlInput" class="pform-input" placeholder="https://ejemplo.com/imagen.jpg" autocomplete="off">
            <div class="img-url-preview-wrap" id="imgUrlPreviewWrap" style="display:none">
                <img id="imgUrlPreviewImg" alt="Vista previa">
            </div>
            <p class="img-url-status" id="imgUrlStatus"></p>
            <div class="img-source-url-actions">
                <button type="button" class="img-source-back" id="imgSourceBackBtn">&larr; Cancelar</button>
                <button type="button" class="button-primary size-adjustment" id="imgUrlAddBtn" disabled>
                    Agregar Imagen
                </button>
            </div>
        </div>

        <p class="img-library-label">Imágenes ya subidas en tu catálogo</p>
        <div class="img-library-grid" id="imgLibraryGrid"></div>
        <p class="img-library-empty" id="imgLibraryEmpty" style="display:none">
            Aún no has subido imágenes a ningún producto.
        </p>
        <p class="img-library-loading" id="imgLibraryLoading" style="display:none">Cargando...</p>
        <button type="button" class="img-library-load-more" id="imgLibraryLoadMore" style="display:none">
            Cargar más
        </button>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="imageSourceCancel">Cerrar</button>
        </div>
    </div>
</div>
