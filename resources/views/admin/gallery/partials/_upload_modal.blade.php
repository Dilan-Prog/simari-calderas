{{-- Upload Modal --}}
<div id="galUploadOverlay" class="del-confirm-overlay" style="z-index:100000">
    <div class="del-confirm-box gal-upload-box">
        <div class="gal-upload-header">
            <h3 style="margin:0;">Subir imágenes</h3>
            <button type="button" class="table-users-manager-action-btn cancel" onclick="galCloseUpload()">✕</button>
        </div>

        {{-- Dropzone --}}
        <div class="gal-dropzone" id="galDropzone">
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            <p><strong>Arrastra imágenes aquí</strong> o haz clic para seleccionarlas</p>
            <p class="gal-dropzone__hint">JPG o PNG · máx. 8 MB c/u · hasta ~15 por lote</p>
            <input type="file" id="galFileInput" accept="image/png,image/jpeg,image/jpg" multiple hidden>
        </div>

        {{-- Previews --}}
        <div class="gal-previews" id="galPreviews"></div>

        {{-- URL --}}
        <div class="gal-url-row">
            <input type="url" class="users-manager-input" id="galUrlInput" placeholder="O pega la URL de una imagen (https://...)">
        </div>
        <img id="galUrlPreview" class="gal-url-preview" style="display:none;" alt="Vista previa">

        <div class="gal-upload-footer">
            <button type="button" class="button-secondary size-adjustment" onclick="galCloseUpload()">Cancelar</button>
            <button type="button" class="button-primary size-adjustment" id="galUploadBtn" disabled>Subir</button>
        </div>
    </div>
</div>
