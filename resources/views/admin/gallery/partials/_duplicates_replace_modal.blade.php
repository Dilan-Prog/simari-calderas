{{-- Modal de reemplazo: elegir imagen del mismo grupo, de la biblioteca, o subir una nueva --}}
<div id="dupReplaceOverlay" class="del-confirm-overlay" style="z-index:100000">
    <div class="del-confirm-box gal-upload-box dup-replace-box">
        <div class="gal-upload-header">
            <h3 style="margin:0;">Reemplazar imágenes duplicadas</h3>
            <button type="button" class="table-users-manager-action-btn cancel" onclick="dupCloseReplace()">✕</button>
        </div>

        <p class="dup-replace-summary" id="dupReplaceSummary"></p>

        <div class="dup-replace-tabs">
            <button type="button" class="dup-replace-tab is-active" data-mode="group">Del grupo</button>
            <button type="button" class="dup-replace-tab" data-mode="library">Buscar en biblioteca</button>
            <button type="button" class="dup-replace-tab" data-mode="upload">Subir nueva</button>
        </div>

        <div class="dup-replace-panel" data-panel="group">
            <div class="dup-replace-grid" id="dupGroupOptions"></div>
        </div>

        <div class="dup-replace-panel" data-panel="library" style="display:none;">
            <input type="text" class="users-manager-input" id="dupLibrarySearch"
                placeholder="Buscar por nombre, SKU, marca, categoría...">
            <div class="dup-replace-grid" id="dupLibraryResults"></div>
        </div>

        <div class="dup-replace-panel" data-panel="upload" style="display:none;">
            <div class="gal-dropzone" id="dupUploadDropzone">
                <p><strong>Haz clic</strong> para elegir una imagen nueva</p>
                <input type="file" id="dupUploadInput" accept="image/png,image/jpeg,image/jpg" hidden>
            </div>
            <img id="dupUploadPreview" class="gal-url-preview" style="display:none;" alt="Vista previa">
        </div>

        <div class="gal-upload-footer">
            <button type="button" class="button-secondary size-adjustment" onclick="dupCloseReplace()">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="dupApplyBtn"
                style="background:#ff6213;border-color:#ff6213;" disabled>Aplicar</button>
        </div>
    </div>
</div>
