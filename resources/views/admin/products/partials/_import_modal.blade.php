{{-- Import products modal --}}
<div id="importProductsModal" class="del-confirm-overlay">
    <div class="del-confirm-box prod-import-box">
        <h2 class="del-confirm-title">Importar Productos</h2>
        <p class="del-confirm-desc">
            Sube un archivo Excel (.xlsx, .xls) o CSV con tu catálogo.
        </p>

        <div class="prod-import-templates">
            <button type="button" class="prod-import-template-link" data-download-url="{{ route('admin.products.import.template') }}">
                ① Plantilla para crear productos nuevos
            </button>
            <button type="button" class="prod-import-template-link" data-download-url="{{ route('admin.products.import.template.update') }}">
                ② Plantilla para actualizar productos existentes
            </button>
        </div>

        <div class="prod-import-mode-toggle" role="radiogroup" aria-label="Tipo de importación">
            <label class="prod-import-mode-option">
                <input type="radio" name="importMode" value="create" id="importModeCreate" checked>
                Crear productos nuevos
            </label>
            <label class="prod-import-mode-option">
                <input type="radio" name="importMode" value="update" id="importModeUpdate">
                Actualizar productos existentes
            </label>
        </div>

        <div class="prod-import-dropzone" id="importDropzone" role="button" tabindex="0">
            <input type="file" id="importFileInput" accept=".xlsx,.xls,.csv" style="display:none">
            <svg class="prod-import-dropzone-icon" xmlns="http://www.w3.org/2000/svg" width="30" height="30"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 3v12" />
                <path d="m7 8 5-5 5 5" />
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            </svg>
            <p class="prod-import-dropzone-text" id="importDropzoneText">
                <strong>Haz clic para seleccionar un archivo</strong>
            </p>
            <p class="prod-import-dropzone-hint" id="importDropzoneHint">.xlsx, .xls o .csv</p>
        </div>

        <div id="importStatus" class="prod-import-status" style="display:none"></div>
        <div id="importResultDetail" class="prod-import-result" style="display:none"></div>

        <div class="del-confirm-actions">
            <button type="button" class="button-secondary size-adjustment" id="importModalCancel">Cerrar</button>
            <button type="button" class="button-primary size-adjustment" id="btnDoImport" disabled>
                Subir e Importar
            </button>
        </div>
    </div>
</div>
